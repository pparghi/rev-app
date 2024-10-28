<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasterClientsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {        
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 25);
        $offset = ($page * $perPage)/$perPage;
        $search = $request->input('search') ? $request->input('search') : '';
        $sortBy = $request->input('sortBy') ? $request->input('sortBy') : '';
        $sortOrder = $request->input('sortOrder');
        $filterByBalance = $request->input('filterByBalance');
        $filterByGroup = $request->input('filterByGroup');

        $data = DB::select('web.SP_ClientMasterDetails @OFFSET = ?, @LIMIT = ?, @SEARCH = ?, @sortColumn = ?, @sortDirection = ?, @filterByBalance = ?, @GroupValue = ?', [$offset, $perPage, $search, $sortBy, $sortOrder, $filterByBalance, $filterByGroup]);        

        $total = DB::select('web.SP_CountClientMasterDetails');
        
        return response()->json([
            'data' => $data,
            'total' => $total[0],
            'per_page' => $perPage,
            'current_page' => $page            
        ]);
    }

    public function clientGroupLevelList(Request $request){
        $clientGroupLevelList = DB::select('web.SP_ClientGroupLevelList');
        
        return response()->json([
            'clientGroupLevelList' => $clientGroupLevelList
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
