<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientsInvoicesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $ClientKey = $request->get('ClientKey');
        $DebtorKey = $request->get('DebtorKey');
        
        $invoices = DB::select('web.SP_DebtorMasterMemberClientsInvoicesDebtor @ClientKey = ?, @DebtorKey = ?', [$ClientKey, $DebtorKey]);
        
        return response()->json([
            'invoices' => $invoices,
        ]);
    }

    public function invoiceDetailNotes(Request $request)
    {
        $InvoiceKey = $request->get('InvoiceKey');
        
        $invoiceDetailNotes = DB::select('web.SP_InvoiceDetailNotes @InvoiceKey = ?', [$InvoiceKey]);
        
        return response()->json([
            'invoiceDetailNotes' => $invoiceDetailNotes,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
