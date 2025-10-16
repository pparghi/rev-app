<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\ApiLoggingService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;


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
        $debtorsArr = [];
        if ($request->DebtorKey) {
            $debtorsArr = array_map('intval', array_map('trim', explode(',', $request->DebtorKey)));
        }
        $postfields = array(
            'clientkey' => $request->ClientKey ?? null, // e.g. 4931
            'debtorkey' => $debtorsArr ?? null, // e.g. [79855,77613],
            'factor_signature' => $request->factor_signature ?? 0,
            'acknowledge_signature' => $request->acknowledge_signature ?? 1,
            'bankingdetails' => $request->bankingdetails ?? 0,
            'bankingdetails_included' => $request->bankingdetails_included ?? 1,
            'araging' => $request->araging ?? 0,
            'email_debtor' => $request->email_debtor ?? 0,
            'email_client' => $request->email_client ?? 0,
            'email_crm' => $request->email_crm ?? 0,
            'email_address' => $request->email_address ?? '',
            'email_contactname' => $request->email_contactname ?? '', 
            'email_contactemail' => $request->email_contactemail ?? '', 
            'email_contactext' => $request->email_contactext ?? '',
            'userkey' => $request->header('X-User-Id')
        );
        // $postfields = array(
        //     'clientkey' => $request->ClientKey ?? null, // e.g. 11568
        //     'debtorkey' => $request->DebtorKey ?? null, // e.g. 72020
        //     'factor_signature' => $request->factor_signature ?? 0,
        //     'acknowledge_signature' => $request->acknowledge_signature ?? 1,
        //     'bankingdetails' => $request->bankingdetails ?? 0,
        //     'bankingdetails_included' => $request->bankingdetails_included ?? 1,
        //     'araging' => $request->araging ?? 0,
        //     'email_debtor' => 0,
        //     'email_client' => 0,
        //     'email_crm' => 0,
        //     'email_address' => '', // test email on mine email address rsun@revinc.com
        //     'email_contactname' => $request->email_contactname ?? '', // need for email template
        //     'email_contactemail' => $request->email_contactemail ?? '', // need for email template
        //     'email_contactext' => $request->email_contactext ?? '',
        // );
        $postfields = json_encode($postfields);

        // $url = "https://login.baron.finance/iris/public/api/noa/create_noa.php";
        $url = "https://iris.revinc.com/iris/public/api/noa/create_noa.php";
        
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
        // $response = '{"test": "test"}'; // For testing purposes, replace with actual API call

        // Log the API call
        $this->apiLogger->logApiCall(
            'NOA_IRIS_API',
            json_decode($postfields, true),
            json_decode($response, true),
            curl_errno($ch) ? 'error #: ' . curl_errno($ch) . '; error detail: ' . curl_error($ch) : 'success',
            $request->header('X-User-Id')
        );

        curl_close($ch);

        $result = "";
        $resultType = "";
        $results = json_decode($response, true);
        // if (isset($results['noa'])) {
        //     $resultType = "noa";
        //     $result = $results['noa'];
        // }
        // if (isset($results['araging'])) {
        //     $resultType = "araging";
        //     $result = $results['araging'];
        // }
        // if (isset($results['bankingdetails'])) {
        //     $resultType = "bankingdetails";
        //     $result = $results['bankingdetails'];
        // }
        // if (isset($results['email_sent'])) {
        //     $resultType = "email_sent";
        //     $result = $results['email_sent'];
        // }

        $finalResult = '';
        $finalResultType = 'email_sent';

        if (isset($results)) {
            for ($i = 0; $i < count($results); $i++) {
                if (isset($results[$i]['noa'])) {
                    $finalResult = $results[$i]['noa'];
                    $finalResultType = "noa";
                }
                if (isset($results[$i]['araging'])) {
                    $finalResult = $results[$i]['araging'];
                    $finalResultType = "araging";
                }
                if (isset($results['bankingdetails'])) {
                    $finalResult = $results['bankingdetails'];
                    $finalResultType = "bankingdetails";
                }
            }
        }
        
        return response()->json([
            'result' => $finalResult,
            'resultType' => $finalResultType,
        ]);
    }

    // using IRIS NOA API for creating base64 encoded PDF
    public function callNOAIRISAPISendBulkEmail(Request $request)
    {
        try {
            $debtorsArr = [];
            if ($request->DebtorKey) {
                $debtorsArr = array_map('intval', array_map('trim', explode(',', $request->DebtorKey)));
            }

            $postfields = array(
                'clientkey' => $request->ClientKey ?? null,
                'debtorkey' => $debtorsArr ?? null,
                'factor_signature' => $request->factor_signature ?? 0,
                'acknowledge_signature' => $request->acknowledge_signature ?? 1,
                'bankingdetails' => $request->bankingdetails ?? 0,
                'bankingdetails_included' => $request->bankingdetails_included ?? 1,
                'araging' => $request->araging ?? 0,
                'email_debtor' => $request->email_debtor ?? 0,
                'email_client' => $request->email_client ?? 0,
                'email_crm' => $request->email_crm ?? 0,
                'email_address' => $request->email_address ?? '',
                'email_contactname' => $request->email_contactname ?? '',
                'email_contactemail' => $request->email_contactemail ?? '',
                'email_contactext' => $request->email_contactext ?? '',
                'userkey' => $request->header('X-User-Id')
            );
            $postfields = json_encode($postfields);

            $this->apiLogger->logApiCall(
                'NOA_IRIS_API_Send_Bulk_Email',
                json_decode($postfields, true),
                'Fire-and-forget request initiated',
                'Attempting to send NOA email asynchronously',
                $request->header('X-User-Id')
            );

            // Make async request in background
            $this->sendAsyncRequest($postfields);

            return response()->json([
                'status' => 'success',
                'message' => 'Bulk email request has been initiated'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to initiate bulk email request: ' . $e->getMessage()
            ], 500);
        }
    }

    private function sendAsyncRequest($postfields)
    {
        $url = "https://iris.revinc.com/iris/public/api/noa/create_noa.php";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);   // Don't return response
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_NOBODY, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);              // 1 second timeout
        curl_setopt($ch, CURLOPT_NOSIGNAL, 1);             // Avoid signals
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);

        curl_exec($ch);
        curl_close($ch);
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
        // $url = "https://login.baron.finance/iris/public/api/ansonia/report_url.php";
        $url = "https://iris.revinc.com/iris/public/api/ansonia/report_url.php";
        
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
            'message' => 'Ansonia API called successfully'
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
        // $url = "https://login.baron.finance/iris/public/api/invoice_image/create.php";
        $url = "https://iris.revinc.com/iris/public/api/invoice_image/create.php";


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
            'userkey' => $request->header('X-User-Id'),
            'email_debtor' => $request->EmailDebtor ?? 0
        );
        $postfields = json_encode($postfields);
        
        // $url = "https://login.baron.finance/iris/public/api/release_letter/create_pdf.php";
        $url = "https://iris.revinc.com/iris/public/api/release_letter/create_pdf.php";
        
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
        // $postfields = array(
        //     'clientkey' => $request->ClientKey,
        //     'marknobuy' => $request->Marknobuy ?? 0,
        //     'watermark' => $request->Watermark ?? 0,
        //     'sendemail' => 0,
        //     'userkey' => $request->header('X-User-Id'),
        //     'userExtension' => $request->UserExtension ?? ''
        // );
        $postfields = json_encode($postfields);
        
        // $url = "https://login.baron.finance/iris/public/api/release_letter/create_pdfs.php";
        $url = "https://iris.revinc.com/iris/public/api/release_letter/create_pdfs.php";
        
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

        // no need to wait for the response from the API
        // var_dump($response);
        // return response()->json(['status' => 'success', 'message' => 'PDFs created successfully.']);
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

    // display PDF file
    public function showFile(Request $request)
    {
        if ($request->has('encodeFilePath')) {
            $file = base64_decode($request->get('encodeFilePath'));

            $imageTypes = ['JPG', 'JPEG', 'PNG', 'GIF', 'BMP', 'SVG'];
            
            if ($request->has('fileType') && in_array(strtoupper($request->get('fileType')), $imageTypes)) {
                if (file_exists($file)) {
                    $mimeType = mime_content_type($file);
                    if (in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif'])) {
                        return response()->file($file, [
                            'Content-Type' => $mimeType,
                            'Content-Disposition' => 'inline; filename="' . basename($file) . '"',
                        ]);
                    }
                }
                 else {
                    return response()->json(['error' => 'File not found or not an image'], 404);
                }
            }
            else if ($request->has('fileType') && strtoupper($request->get('fileType')) == 'PDF') {
                $fileName = basename($file);
                if ($request->has('title')) {
                    $fileName = base64_decode($request->get('title')).'.pdf';
                } else if (!$fileName) {
                    $fileName = "document.pdf";
                }
                
                if (file_exists($file)) {
                    return response()->file($file, [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'inline; filename="' . $fileName . '"',
                    ]);
                }
            }
            else {
                $fileName = basename($file);
                if ($request->has('title')) {
                    $fileName = base64_decode($request->get('title')).'.'.pathinfo($file, PATHINFO_EXTENSION);
                } else if (!$fileName) {
                    $fileName = "document.".pathinfo($file, PATHINFO_EXTENSION);
                }
                
                if (file_exists($file)) {
                    return response()->download($file, $fileName);
                }
            }
        }
        
        return response()->json(['error' => 'File not found'], 404);
    }

    
    // getting full list of clients
    public function getClientFullList(Request $request)
    {
        $masterList = DB::select('web.SP_ClientListAll @MasterClient = 1');
        $memberList = DB::select('web.SP_ClientListAll @MasterClient = 0');
        
        return response()->json([
            'masterClients' => $masterList,
            'memberClients' => $memberList
        ]);
    }

    // Upload a client document and save to the database
    public function uploadClientDocument(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'file' => 'required|file',
                'clientKey' => 'required',
                'clientId' => 'required',
                'clientName' => 'required',
                'category' => 'required',
                'description' => 'nullable|string',
                'userID' => 'required'
            ]);

            // Get the uploaded file
            $file = $request->file('file');
            $originalFileName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            
            // Store the file temporarily
            // $tempPath = storage_path('app/temp');
            // if (!File::exists($tempPath)) {
            //     File::makeDirectory($tempPath, 0755, true);
            // }
            
            // $tempFile = $tempPath . '/' . $originalFileName;
            // move_uploaded_file($file->getRealPath(), $tempFile);
            
            // Get folder information from stored procedure
            $folderInfo = DB::select('EXEC [Web].[SP_DocumentsFolder]');
            
            if (empty($folderInfo)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to get document folder information'
                ], 500);
            }
            
            $destinationPath = $folderInfo[0]->Path ?? null;
            $folder = $folderInfo[0]->DocFolderKey ?? null;
            
            if (!$destinationPath || !$folder) {
                // Log failure - invalid path
                $this->apiLogger->logApiCall(
                    'ClientDocumentUpload',
                    [
                        'clientKey' => $request->input('clientKey'),
                        'clientId' => $request->input('clientId'),
                        'clientName' => $request->input('clientName'),
                        'category' => $request->input('category'),
                        'description' => $request->input('description'),
                        'fileName' => $originalFileName,
                        'extension' => $extension,
                        'fileSize' => $file->getSize(),
                        'fileType' => $file->getMimeType(),
                        'destinationPath' => $destinationPath,
                        'folder' => $folder
                    ],
                    ['error' => 'Invalid destination path or folder'],
                    'error',
                    $request->input('userID')
                );

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid destination path or folder'
                ], 500);
            }
            
            // Ensure destination directory exists
            // if (!File::exists($destinationPath)) {
            //     File::makeDirectory($destinationPath, 0755, true);
            // }
            
            // Add document to database using stored procedure
            $clientKey = $request->input('clientKey');
            $description = $request->input('description') ?? '';
            $category = $request->input('category');
            
            $result = DB::select('EXEC [Web].[SP_DocumentsAddByClient] ?, ?, ?, ?, ?', [
                $clientKey,
                $description,
                $originalFileName,
                $category,
                $folder
            ]);
            
            if (empty($result)) {
                // Log failure - database error
                $this->apiLogger->logApiCall(
                    'ClientDocumentUpload',
                    [
                        'clientKey' => $clientKey,
                        'clientId' => $request->input('clientId'),
                        'clientName' => $request->input('clientName'),
                        'category' => $category,
                        'description' => $description,
                        'fileName' => $originalFileName,
                        'extension' => $extension,
                        'fileSize' => $file->getSize(),
                        'fileType' => $file->getMimeType(),
                        'destinationPath' => $destinationPath,
                        'folder' => $folder
                    ],
                    ['error' => 'Failed to add document to database'],
                    'error',
                    $request->input('userID')
                );

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to add document to database'
                ], 500);
            }
            
            // Get the DocHdrKey from the stored procedure result
            $docHdrKey = $result[0]->DocHdrKey ?? null;
            
            if (!$docHdrKey) {
                // Log failure - missing DocHdrKey
                $this->apiLogger->logApiCall(
                    'ClientDocumentUpload',
                    [
                        'clientKey' => $clientKey,
                        'clientId' => $request->input('clientId'),
                        'clientName' => $request->input('clientName'),
                        'category' => $category,
                        'description' => $description,
                        'fileName' => $originalFileName,
                        'extension' => $extension,
                        'fileSize' => $file->getSize(),
                        'fileType' => $file->getMimeType(),
                        'destinationPath' => $destinationPath,
                        'folder' => $folder
                    ],
                    ['error' => 'Failed to get DocHdrKey from database'],
                    'error',
                    $request->input('userID')
                );
            
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to get DocHdrKey from database'
                ], 500);
            }
            
            // Create the final filename using DocHdrKey
            $newFileName = $docHdrKey . '.' . $extension;
            // $finalPath = $destinationPath . '\\' . $newFileName;
            
            // Move the file to the final destination
            $file->move($destinationPath, $newFileName);

            // Log success
            $this->apiLogger->logApiCall(
                'ClientDocumentUpload',
                [
                    'clientKey' => $clientKey,
                    'clientId' => $request->input('clientId'),
                    'clientName' => $request->input('clientName'),
                    'category' => $category,
                    'description' => $description,
                    'fileName' => $originalFileName,
                    'extension' => $extension
                ],
                [
                    'success' => true,
                    'docHdrKey' => $docHdrKey,
                    'fileName' => $newFileName,
                    'path' => $destinationPath,
                    'folder' => $folder
                ],
                'success',
                $request->input('userID')
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Document uploaded successfully',
                'data' => [
                    'docHdrKey' => $docHdrKey,
                    'fileName' => $newFileName,
                    'path' => $destinationPath
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Document upload error: ' . $e->getMessage());

            // Log the exception
            $this->apiLogger->logApiCall(
                'ClientDocumentUpload',
                [
                    'clientKey' => $clientKey,
                    'clientId' => $request->input('clientId'),
                    'clientName' => $request->input('clientName'),
                    'category' => $category,
                    'description' => $description,
                    'fileName' => $originalFileName,
                    'extension' => $extension
                ],
                [
                    'error' => 'Error uploading document: ' . $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ],
                'error',
                $request->input('userID')
            );
            
            return response()->json([
                'success' => false,
                'message' => 'Error uploading document: ' . $e->getMessage()
            ], 500);
        }
    }

    //endregion clients documents

    
    //region relationship documents
    // get relationship document list
    public function getRelationshipDocumentList(Request $request)
    {
        $ClientName = $request->ClientName ?? '';
        $DebtorName = $request->DebtorName ?? '';
        $DocCatKey = 0; // relationship documents only has one category and key is 0
        $FileName = $request->FileName ?? '';

        $data = DB::select('web.SP_DocumentsListByAging @ClientName = ?, @DebtorName = ?, @DocCatKey = ?, @FileName = ?', 
        [$ClientName, $DebtorName, $DocCatKey, $FileName]);

        return response()->json([
            'data' => $data
        ]);
    }

    // Upload a aging document and save to the database
    public function uploadAgingDocument(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'file' => 'required|file',
                'agingKey' => 'required',
                'clientName' => 'required',
                'clientKey' => 'required',
                'debtorName' => 'required',
                'debtorKey' => 'required',
                'category' => 'required',
                'description' => 'nullable|string',
                'userID' => 'required'
            ]);

            // Get the uploaded file
            $file = $request->file('file');
            $originalFileName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            
            // Store the file temporarily
            // $tempPath = storage_path('app/temp');
            // if (!File::exists($tempPath)) {
            //     File::makeDirectory($tempPath, 0755, true);
            // }
            
            // $tempFile = $tempPath . '/' . $originalFileName;
            // move_uploaded_file($file->getRealPath(), $tempFile);
            
            // Get folder information from stored procedure
            $folderInfo = DB::select('EXEC [Web].[SP_DocumentsFolder]');
            
            if (empty($folderInfo)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to get document folder information'
                ], 500);
            }
            
            $destinationPath = $folderInfo[0]->Path ?? null;
            $folder = $folderInfo[0]->DocFolderKey ?? null;
            
            if (!$destinationPath || !$folder) {
                // Log failure - invalid path
                $this->apiLogger->logApiCall(
                    'AgingDocumentUpload',
                    [
                        'agingKey' => $request->input('agingKey'),
                        'clientKey' => $request->input('clientKey'),
                        'clientName' => $request->input('clientName'),
                        'debtorName' => $request->input('debtorName'),
                        'debtorKey' => $request->input('debtorKey'),
                        'category' => $request->input('category'),
                        'description' => $request->input('description'),
                        'fileName' => $originalFileName,
                        'extension' => $extension,
                        'fileSize' => $file->getSize(),
                        'fileType' => $file->getMimeType(),
                        'destinationPath' => $destinationPath,
                        'folder' => $folder
                    ],
                    ['error' => 'Invalid destination path or folder'],
                    'error',
                    $request->input('userID')
                );

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid destination path or folder'
                ], 500);
            }
            
            // Ensure destination directory exists
            // if (!File::exists($destinationPath)) {
            //     File::makeDirectory($destinationPath, 0755, true);
            // }
            
            // Add document to database using stored procedure
            $agingKey = $request->input('agingKey');
            $description = $request->input('description') ?? '';
            $category = $request->input('category');
            
            $result = DB::select('EXEC [Web].[SP_DocumentsAddByAging] ?, ?, ?, ?, ?', [
                $agingKey,
                $description,
                $originalFileName,
                $category,
                $folder
            ]);
            
            if (empty($result)) {
                // Log failure - database error
                $this->apiLogger->logApiCall(
                    'AgingDocumentUpload',
                    [
                        'agingKey' => $request->input('agingKey'),
                        'clientKey' => $request->input('clientKey'),
                        'clientName' => $request->input('clientName'),
                        'debtorName' => $request->input('debtorName'),
                        'debtorKey' => $request->input('debtorKey'),
                        'category' => $category,
                        'description' => $description,
                        'fileName' => $originalFileName,
                        'extension' => $extension,
                        'fileSize' => $file->getSize(),
                        'fileType' => $file->getMimeType(),
                        'destinationPath' => $destinationPath,
                        'folder' => $folder
                    ],
                    ['error' => 'Failed to add document to database'],
                    'error',
                    $request->input('userID')
                );

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to add document to database'
                ], 500);
            }
            
            // Get the DocHdrKey from the stored procedure result
            $docHdrKey = $result[0]->DocHdrKey ?? null;
            
            if (!$docHdrKey) {
                // Log failure - missing DocHdrKey
                $this->apiLogger->logApiCall(
                    'AgingDocumentUpload',
                    [
                        'agingKey' => $request->input('agingKey'),
                        'clientKey' => $request->input('clientKey'),
                        'clientName' => $request->input('clientName'),
                        'debtorName' => $request->input('debtorName'),
                        'debtorKey' => $request->input('debtorKey'),
                        'category' => $category,
                        'description' => $description,
                        'fileName' => $originalFileName,
                        'extension' => $extension,
                        'fileSize' => $file->getSize(),
                        'fileType' => $file->getMimeType(),
                        'destinationPath' => $destinationPath,
                        'folder' => $folder
                    ],
                    ['error' => 'Failed to get DocHdrKey from database'],
                    'error',
                    $request->input('userID')
                );
            
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to get DocHdrKey from database'
                ], 500);
            }
            
            // Create the final filename using DocHdrKey
            $newFileName = $docHdrKey . '.' . $extension;
            // $finalPath = $destinationPath . '\\' . $newFileName;
            
            // Move the file to the final destination
            $file->move($destinationPath, $newFileName);

            // Log success
            $this->apiLogger->logApiCall(
                'AgingDocumentUpload',
                [
                    'agingKey' => $request->input('agingKey'),
                    'clientKey' => $request->input('clientKey'),
                    'clientName' => $request->input('clientName'),
                    'debtorName' => $request->input('debtorName'),
                    'debtorKey' => $request->input('debtorKey'),
                    'category' => $category,
                    'description' => $description,
                    'fileName' => $originalFileName,
                    'extension' => $extension
                ],
                [
                    'success' => true,
                    'docHdrKey' => $docHdrKey,
                    'fileName' => $newFileName,
                    'path' => $destinationPath,
                    'folder' => $folder
                ],
                'success',
                $request->input('userID')
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Document uploaded successfully',
                'data' => [
                    'docHdrKey' => $docHdrKey,
                    'fileName' => $newFileName,
                    'path' => $destinationPath
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Document upload error: ' . $e->getMessage());

            // Log the exception
            $this->apiLogger->logApiCall(
                'AgingDocumentUpload',
                [
                    'agingKey' => $request->input('agingKey'),
                    'clientKey' => $request->input('clientKey'),
                    'clientName' => $request->input('clientName'),
                    'debtorName' => $request->input('debtorName'),
                    'debtorKey' => $request->input('debtorKey'),
                    'category' => $category,
                    'description' => $description,
                    'fileName' => $originalFileName,
                    'extension' => $extension
                ],
                [
                    'error' => 'Error uploading document: ' . $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ],
                'error',
                $request->input('userID')
            );
            
            return response()->json([
                'success' => false,
                'message' => 'Error uploading document: ' . $e->getMessage()
            ], 500);
        }
    }

    //endregion relationship documents


    //region dashboard reports
    // getting full debtor list for generating excel report
    public function getFullDebtorListForReport(Request $request)
    {
        $data = DB::select('web.SP_DebtorListAll');
        
        return response()->json([
            'data' => $data
        ]);
    }
    //endregion dashboard reports

}
