<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {    
        $ClientNo = $request->input('ClientNo') ? $request->input('ClientNo') : '';    
        $StatusList = $request->StatusList ? $request->StatusList : '0';
        $RequestDate = $request->RequestDate ? $request->RequestDate : '';
        
        $data = DB::select('Web.SP_CredRequestList @StatusList = ?, @RequestDate = ?, @ClientNo = ?',  [$StatusList, $RequestDate, $ClientNo]);
        
        return response()->json([
            'data' => $data,
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
