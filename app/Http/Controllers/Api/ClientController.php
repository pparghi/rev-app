<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Clients;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Clients::leftjoin('Debtors', 'Clients.ClientKey', '=', 'Debtors.ClientKey')
        ->select('Clients.ClientNo', 'Clients.NameKey', 'Clients.AcctExec', 'Clients.TotalCreditLimit', 'Clients.IndivCreditLimit', 'Clients.Phone1', 'Clients.Country', 'Clients.CurrencyType', 'Debtors.DebtorNo')
        ->get();

        // return Clients::select('ClientNo', 'NameKey', 'AcctExec', 'TotalCreditLimit', 'IndivCreditLimit', 'Phone1', 'Country', 'CurrencyType')
        // ->get();

        // return Clients::all();

        // return Clients::paginate(10);
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
