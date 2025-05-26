<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class InvoiceSearchController extends Controller
{
    // getting the list of clients
    public function getInvoiceStatusList()
    {
        $data = DB::select('web.SP_InvoiceStatusList');
        
        return response()->json([
            'data' => $data
        ]);
    }

    // getting the Dispute Code List 
    public function getDisputeCodeList()
    {
        $data = DB::select('web.SP_DisputeCodeList');
        
        return response()->json([
            'data' => $data
        ]);
    }

    // getting the list of invoices
    public function getInvoicesList(Request $request)
    {
        try {
            $data = DB::select('Web.SP_InvoiceSearch 
                @ClientNameLike = ?, 
                @DebtorNameLike = ?,
                @InvDateInit = ?,
                @InvDateEnd = ?,
                @InvNo = ?,
                @ReferenceNo = ?,
                @Status = ?,
                @Office = ?,
                @AgeInit = ?,
                @AgeEnd = ?,
                @AmountInit = ?,
                @AmountEnd = ?,
                @PaidDateIni = ?,
                @PaidDateEnd = ?,
                @PurchDateIni = ?,
                @PurchDateEnd = ?,
                @CRM = ?,
                @DisputeCode = ?,
                @BatchNo = ?', 
                [
                    $request->input('clientNameLike', '%'),
                    $request->input('debtorNameLike', '%'),
                    $request->input('invDateInit', date('Y-m-d')),
                    $request->input('invDateEnd', date('Y-m-d')),
                    $request->input('invNo', '%'),
                    $request->input('referenceNo', '%'),
                    $request->input('status', '%'),
                    $request->input('office', '%'),
                    $request->input('ageInit', 0),
                    $request->input('ageEnd', 9999),
                    $request->input('amountInit', 0.00),
                    $request->input('amountEnd', 999999999.99),
                    $request->input('paidDateIni', '2000-01-01'),
                    $request->input('paidDateEnd', '2099-12-31'),
                    $request->input('purchDateIni', '2000-01-01'),
                    $request->input('purchDateEnd', '2099-12-31'),
                    $request->input('crm', '%'),
                    $request->input('disputeCode', '0'),
                    $request->input('batchNo', '%')
                ]
            );
            
            return response()->json([
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch invoice list',
                'message' => $e->getMessage()
            ], 500);
        }
    }


}
