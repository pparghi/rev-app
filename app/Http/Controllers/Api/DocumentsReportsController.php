<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


class DocumentsReportsController extends Controller
{
    
    // using IRIS NOA API for creating base64 encoded PDF
    public function callNOAIRISAPI(Request $request)
    {    
        $postfields = array(
            'clientkey' => $request->ClientKey ?? 11568, // e.g. 11568
            'debtorkey' => $request->DebtorKey ?? 72020, // e.g. 72020
            'factor_signature' => $request->factor_signature ?? 0,
            'acknowledge_signature' => $request->acknowledge_signature ?? 0,
            'bankingdetails' => $request->bankingdetails ?? 0,
            'bankingdetails_included' => $request->bankingdetails_included ?? 0,
            'araging' => $request->araging ?? 0,
            'email_debtor' => $request->email_debtor ?? 0,
            'email_client' => $request->email_client ?? 0,
            'email_crm' => $request->email_crm ?? 0,
            'email_address' => $request->email_address ?? ''
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
        
        return response()->json([
            'result' => $result,
            'resultType' => $resultType,
        ]);
    }
}
