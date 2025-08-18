<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\ApiLoggingService;


class DocumentsReportsController extends Controller
{
    protected $apiLogger;

    public function __construct(ApiLoggingService $apiLogger)
    {
        $this->apiLogger = $apiLogger;
    }

    // getting the list of clients
    public function getClientsList()
    {
        $data = DB::select('web.SP_ClientMembersList');
        
        return response()->json([
            'data' => $data
        ]);
    }

    // getting the list of debtors base on client key
    public function getDebtorsListByClientKey(Request $request)
    {
        $clientKey = $request->input('clientKey');
        $data = DB::select('web.SP_DebtorListByClient @clientKey = ?', [$clientKey]);
        
        return response()->json([
            'data' => $data
        ]);
    }

    // getting the list of debtors base on client key, excluding DEBTOR DOES NOT PAY FACTOR of nobuy debtors
    public function getNOADebtorsListByClientKey(Request $request)
    {
        $clientKey = $request->input('clientKey');
        $data = DB::select('web.SP_DebtorListByClientPayToFactor @clientKey = ?', [$clientKey]);
        
        return response()->json([
            'data' => $data
        ]);
    }

    // getting debtor list by debtor name
    public function getDebtorsListByName(Request $request)
    {
        $debtorName = $request->input('debtorName');
        $data = DB::select('web.SP_DebtorNameSearch @Name = ?', [$debtorName]);
        
        return response()->json([
            'data' => $data
        ]);
    }

    // using IRIS NOA API for creating base64 encoded PDF
    public function callNOAIRISAPI(Request $request)
    {    
        $postfields = array(
            'clientkey' => $request->ClientKey ?? null, // e.g. 11568
            'debtorkey' => $request->DebtorKey ?? null, // e.g. 72020
            'factor_signature' => $request->factor_signature ?? 0,
            'acknowledge_signature' => $request->acknowledge_signature ?? 1,
            'bankingdetails' => $request->bankingdetails ?? 0,
            'bankingdetails_included' => $request->bankingdetails_included ?? 1,
            'araging' => $request->araging ?? 0,
            'email_debtor' => $request->email_debtor ?? 0,
            'email_client' => $request->email_client ?? 0,
            'email_crm' => $request->email_crm ?? 0,
            'email_address' => $request->email_address ?? '',
            'email_contactname' => $request->email_contactname ?? '', // need for email template
            'email_contactemail' => $request->email_contactemail ?? '', // need for email template
            'email_contactext' => $request->email_contactext ?? '',
        );
        $postfields = json_encode($postfields);
        
        $url = "https://login.baron.finance/iris/public/api/noa/create_noa.php";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
        curl_setopt($ch, CURLOPT_HEADER, false);           // return response header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);    // return response
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_NOBODY, false);           // suppress result
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 30000);       // connection timeout (1 second = 1000)
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
        $response = curl_exec($ch);
        // $response = '{"test": "test"}'; // For testing purposes, replace with actual API call

        // Log the API call
        $this->apiLogger->logApiCall(
            'NOA_IRIS_API',
            json_decode($postfields, true),
            json_decode($response, true),
            curl_errno($ch) ? 'error' : 'success',
            $request->header('X-User-Id') 
        );

        curl_close($ch);

        $result = "";
        $resultType = "";
        $results = json_decode($response, true);
        if (isset($results['noa'])) {
            $resultType = "noa";
            $result = $results['noa'];
        }
        if (isset($results['araging'])) {
            $resultType = "araging";
            $result = $results['araging'];
        }
        if (isset($results['bankingdetails'])) {
            $resultType = "bankingdetails";
            $result = $results['bankingdetails'];
        }
        if (isset($results['email_sent'])) {
            $resultType = "email_sent";
            $result = $results['email_sent'];
        }
        
        return response()->json([
            'result' => $result,
            'resultType' => $resultType,
        ]);
    }

    // using IRIS Ansonia API for creating report url
    public function callAnsoniaAPI(Request $request)
    {
        $postfields = array(
            'MCNumber' => $request->MCNumber ?? '', //
            'Name' => $request->Name ?? '', // e.g. 'ROYAL TRANSPORTATION INC.'
            'Address' => $request->Address ?? '', // e.g. '51 KEATS TERR' 
            'City' => $request->City ?? '', // e.g. 'BRAMPTON'
            'State' => $request->State ?? '', // e.g. 'ON'
            'Country' => $request->Country ?? '' // e.g. 'CANADA'
        );
        $postfields = json_encode($postfields);
        
        //$url = "http://localhost/iris/public/api/ansonia/report_url.php";
        $url = "https://login.baron.finance/iris/public/api/ansonia/report_url.php";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
        curl_setopt($ch, CURLOPT_HEADER, false);           // return response header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);    // return response
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_NOBODY, false);           // suppress result
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 30000);       // connection timeout (1 second = 1000)
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
        $response = curl_exec($ch);
        curl_close($ch);
        
        return response()->json([
            'url' => $response,
        ]);
    }

    // using IRIS invoice_image API for getting invoice pdf
    public function callInvoiceImageAPI(Request $request)
    {
        $postfields = array(
            'invoicekey' =>  $request->invoicekey ?? null, // 4547377
            'include_stamp' => $request->includeStamp ?? 0
        );
        $postfields = json_encode($postfields);

        //$url = "http://localhost/iris/public/api/invoice_image/create.php";
        $url = "https://login.baron.finance/iris/public/api/invoice_image/create.php";


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
        curl_setopt($ch, CURLOPT_HEADER, false);           // return response header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);    // return response
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_NOBODY, false);           // suppress result
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 60000);       // connection timeout (1 second = 1000)
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
        $response = curl_exec($ch);
        curl_close($ch);

        // var_dump($response);

        $results = json_decode($response, true);
        // if (isset($results['status']) && $results['status'] == 'success') {
        //     $target = "Invoice_{$results['invno']}.pdf";
        //     $fileout = fopen($target, 'w');
        //     fwrite($fileout, base64_decode($results['pdf']));
        //     fclose($fileout);
        // }

        return response()->json($results);
    }


    // using IRIS release letter(LOR) API for creating base64 encoded PDF
    public function callLORCreatePDFAPI(Request $request)
    {    
        $postfields = array(
            'clientkey' => $request->ClientKey,
            'debtorkey' => $request->DebtorKey ?? 0,
            'marknobuy' => $request->Marknobuy ?? 0,
            'watermark' => $request->Watermark ?? 0,
            'userkey' => $request->header('X-User-Id')
        );
        $postfields = json_encode($postfields);
        
        $url = "https://login.baron.finance/iris/public/api/release_letter/create_pdf.php";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
        curl_setopt($ch, CURLOPT_HEADER, false);           // return response header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);    // return response
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_NOBODY, false);           // suppress result
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 30000);       // connection timeout (1 second = 1000)
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
        $response = curl_exec($ch);

        // Log the API call
        $this->apiLogger->logApiCall(
            'NOA_Release_Letter_Create_PDF_API',
            json_decode($postfields, true),
            json_decode($response, true),
            curl_errno($ch) ? 'error' : 'success',
            $request->header('X-User-Id') 
        );

        curl_close($ch);

        $decodedResponse = json_decode($response, true);
        return response()->json($decodedResponse);
    }

    // using IRIS release letter(LOR) API for creating base64 encoded PDF for all debtors of a client
    // this API includes sendemail parameter and no debtorKey parameter
    public function callLORCreatePDFsAPI(Request $request)
    {    
        $postfields = array(
            'clientkey' => $request->ClientKey,
            'marknobuy' => $request->Marknobuy ?? 0,
            'watermark' => $request->Watermark ?? 0,
            'sendemail' => $request->Sendemail ?? 0,
            'userkey' => $request->header('X-User-Id'),
            'userExtension' => $request->UserExtension ?? ''
        );
        $postfields = json_encode($postfields);
        
        $url = "https://login.baron.finance/iris/public/api/release_letter/create_pdfs.php";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
        curl_setopt($ch, CURLOPT_HEADER, false);           // return response header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);    // return response
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_NOBODY, false);           // suppress result
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 30000);       // connection timeout (1 second = 1000)
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
        $response = curl_exec($ch);

        // Log the API call
        $this->apiLogger->logApiCall(
            'NOA_Release_Letter_Create_PDFs_API',
            json_decode($postfields, true),
            json_decode($response, true),
            curl_errno($ch) ? 'error' : 'success',
            $request->header('X-User-Id') 
        );

        curl_close($ch);

        $decodedResponse = json_decode($response, true);
        return response()->json($decodedResponse);
    }

    //region clients documents
    // get clients document categories
    public function getClientDocumentCategory()
    {
        $data = DB::select('web.SP_DocumentCategoryClients');
        
        return response()->json([
            'data' => $data
        ]);
    }

    // getting the list of clients documents base on 
    public function getClientDocumentList(Request $request)
    {
        $ClientName = $request->ClientName ?? '';
        $DocCatKey = $request->DocCatKey ?? '';
        $FileName = $request->FileName ?? '';

        $data = DB::select('web.SP_DocumentsListByClient @ClientName = ?, @DocCatKey = ?, @FileName = ?', [$ClientName, $DocCatKey, $FileName]);
        
        return response()->json([
            'data' => $data
        ]);
    }


    //endregion clients documents

}
