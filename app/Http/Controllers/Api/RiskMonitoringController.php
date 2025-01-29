<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RiskMonitoringController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 25);
        $offset = ($page * $perPage)/$perPage;
        $sortBy = $request->input('sortBy', '');
        $sortOrder = $request->input('sortOrder', '');
        $DueDateFrom = $request->input('dueDateFrom', '');
        $DueDateTo = $request->input('dueDateTo', '');
        $Inactive = $request->isActive;
        $search = $request->input('search') ? $request->input('search') : '';
        $Fuel = $request->isFuel;        
        $DDCreatedBy = $request->DDCreatedBy ? $request->DDCreatedBy : '';
        $level = $request->level ? $request->level : '';
        $office = $request->office ? $request->office : '';
        $crm = $request->crm ? $request->crm : '';        
        
        $data = DB::select('Web.ClientsCreditReviewFilters @OFFSET = ?, @LIMIT = ?, @sortColumn = ?, @sortDirection = ?, @DueDateFrom = ?,  @DueDateTo = ?, @Inactive = ?, @Name = ?, @Office = ?, @AcctExec = ?, @Level = ?, @Fuel = ?, @CreatedBy = ?',  [$offset, $perPage, $sortBy, $sortOrder, $DueDateFrom, $DueDateTo, $Inactive,  $search, $office, $crm, $level, $Fuel, $DDCreatedBy]);
        
        return response()->json([
            'data' => $data,
        ]); 
    }

    public function clientGroupLevelList(Request $request){
        $clientGroupLevelList = DB::select('web.SP_ClientGroupLevelList');
        
        return response()->json([
            'clientGroupLevelList' => $clientGroupLevelList
        ]);
    }

    public function CRMList(Request $request){
        $CRMList = DB::select('web.SP_CRMsList');
        
        return response()->json([
            'CRMList' => $CRMList
        ]);
    }
    public function officeList(Request $request){
        $officeList = DB::select('web.SP_officeList');
        
        return response()->json([
            'officeList' => $officeList
        ]);
    }

    public function DDCreatedBy(Request $request){
        $DDCreatedBy = DB::select('web.SP_DDCreatedBy');
        
        return response()->json([
            'DDCreatedBy' => $DDCreatedBy
        ]);
    }

    public function ClientDetails(Request $request){
        $ClientKey = $request->input('ClientKey');
        $ClientDetails = DB::select('Web.SP_ClientDetails @ClientKey = ?', [$ClientKey]);
        
        return response()->json([
            'ClientDetails' => $ClientDetails
        ]);
    }
    
    public function ClientContactsDetails(Request $request){
        $ClientKey = $request->input('ClientKey');
        $ClientContactsDetails = DB::select('Web.SP_ClientContactsDetails @ClientKey = ?', [$ClientKey]);
        $ClientFuelOrNot = DB::select('Web.SP_ClientFuelOrNot @ClientKey = ?', [$ClientKey]);
        
        return response()->json([
            'ClientContactsDetails' => $ClientContactsDetails,
            'ClientFuelOrNot' => $ClientFuelOrNot
        ]);
    }

    public function MonitoringCategories(Request $request){        
        $MonitoringCategories = DB::select('Web.SP_MonitoringCategories');
        
        foreach ($MonitoringCategories as $row) {
            $categories = explode(";",$row->parameter_value);
        }
        
        return response()->json([
            'MonitoringCategories' => $categories
        ]);
    }

    public function MonitoringNotes(Request $request){     
        $ClientKey = $request->input('ClientKey');   
        $Category = $request->input('Category') ? $request->input('Category') : '%';
        $MonitoringNotes = DB::select('Web.SP_MonitoringNotes @ClientKey = ?, @Category = ?', [$ClientKey, $Category]);        
        
        return response()->json([
            'MonitoringNotes' => $MonitoringNotes
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
