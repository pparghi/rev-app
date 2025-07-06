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
        $DueDateFrom = $request->input('dueDateFrom', '');
        $DueDateTo = $request->input('dueDateTo', '');
        $Inactive = $request->isActive;
        $search = $request->input('search') ? $request->input('search') : '';
        $Fuel = $request->isFuel;        
        $DDCreatedBy = $request->DDCreatedBy ? $request->DDCreatedBy : '';
        $level = $request->level ? $request->level : '';
        $office = $request->office ? $request->office : '';
        $crm = $request->crm ? $request->crm : '';        
        $sortBy = $request->input('sortBy', 'NoteDueDate');
        $sortOrder = $request->input('sortOrder', 'DESC');
        
        $data = DB::select('Web.ClientsCreditReviewFilters @OFFSET = ?, @LIMIT = ?, @sortColumn = ?, @sortDirection = ?, @DueDateFrom = ?,  @DueDateTo = ?, @Inactive = ?, @Name = ?, @Office = ?, @AcctExec = ?, @Level = ?, @Fuel = ?, @CreatedBy = ?',  [$offset, $perPage, $sortBy, $sortOrder, $DueDateFrom, $DueDateTo, $Inactive,  $search, $office, $crm, $level, $Fuel, $DDCreatedBy]);
        
        return response()->json([
            'data' => $data,
        ]); 
    }

    public function clientGroupLevelList(Request $request){
        $clientGroupLevelList = DB::select('web.SP_ClientLevels');
        
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
        $LevelHistory = DB::select('Web.SP_LevelHistory @ClientKey = ?', [$ClientKey]);
        $ClientLevelDetail = DB::select('Web.SP_ClientLevelDetail @ClientKey = ?', [$ClientKey]);
        
        return response()->json([
            'ClientDetails' => $ClientDetails,
            'LevelHistory' => $LevelHistory,
            'ClientLevelDetail' => $ClientLevelDetail
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
        $Hidden = $request->input('Hidden') ? $request->input('Hidden') : '%';
        $MonitoringNotes = DB::select('Web.SP_MonitoringNotes @ClientKey = ?, @Category = ?, @Hidden = ?', [$ClientKey, $Category, $Hidden]);        
        
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
    public function addNotesRisk(Request $request)
    {
        $ClientKey = $request->input('ClientKey');
        $Category = $request->input('Category');
        $Notes = $request->input('Notes');
        $Currency = $request->input('Currency');
        $Risk = $request->input('Risk');
        $CreatedBy = $request->input('CreatedBy');
        $DueDate = $request->input('DueDate');

        try {
            $result = DB::statement('web.SP_ClientNotesAdd @ClientKey = ?, @Category = ?, @Notes = ?, @Currency = ?, @Risk = ?, @CreatedBy = ?, @DueDate = ?', [$ClientKey, $Category, $Notes, $Currency, $Risk, $CreatedBy, $DueDate]);
            return response()->json(['result' => $result]);
        } catch(\Exception $e) {
            return response()->json(['error' => 'Failed to add note', 'message' => $e->getMessage()], 500);
        }
    }
    public function updateCRMRisk(Request $request)
    {
        $ClientKey = $request->input('ClientKey');
        $crm = $request->input('CRM');
        $Userkey = $request->input('UserKey');

        try {
            $result = DB::statement('web.SP_ClientCRMUpdate @ClientKey = ?, @AcctExec = ?, @Userkey = ?', [$ClientKey, $crm, $Userkey]);
            return response()->json(['result' => $result]);
        } catch(\Exception $e) {
            return response()->json(['error' => 'Failed to update crm', 'message' => $e->getMessage()], 500);
        }
    }
    public function updateLevelRisk(Request $request)
    {
        $ClientKey = $request->input('ClientKey');
        $GroupValue = $request->input('GroupValue');
        $Userkey = $request->input('UserKey');

        try {
            $result = DB::statement('web.SP_ClientLevelsChange @ClientKey = ?, @GroupValue = ?, @Userkey = ?', [$ClientKey, $GroupValue, $Userkey]);
            return response()->json(['result' => $result]);
        } catch(\Exception $e) {
            return response()->json(['error' => 'Failed to update Level', 'message' => $e->getMessage()], 500);
        }
    }
    public function updateCompleteStatusRisk(Request $request)
    {
        $ClientNoteKey = $request->input('ClientNoteKey');
        $Complete = $request->input('Complete');        

        try {
            DB::statement('web.SP_ClientNotesCompleteStatus @ClientNoteKey = ?, @Complete = ?', [$ClientNoteKey, $Complete]);
        } catch(\Exception $e) {
            return response()->json(['error' => 'Failed to update Complete Status', 'message' => $e->getMessage()], 500);
        }
    }
    public function ClientNotesHide(Request $request)
    {
        $ClientNoteKey = $request->input('ClientNoteKey');
        $Userkey = $request->input('UserKey');
        $Hide = $request->input('Hide');       

        try {
            DB::statement('web.SP_ClientNotesHide @ClientNoteKey = ?, @Userkey = ?, @Hide = ?', [$ClientNoteKey, $Userkey, $Hide]);
        } catch(\Exception $e) {
            return response()->json(['error' => 'Failed to hide note', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }


    // get client summary note
    public function getClientSummaryNote(Request $request)
    {
        $ClientKey = $request->input('ClientKey');
        $ClientSummaryNote = DB::select('Web.SP_ClientSummaryText @ClientKey = ?', [$ClientKey]);
        
        return response()->json([
            'ClientSummaryNote' => $ClientSummaryNote
        ]);
    }

    // set client summary note
    public function setClientSummaryNote(Request $request)
    {
        $ClientKey = $request->input('ClientKey');
        $SummaryText = $request->input('SummaryText');

        try {
            DB::statement('Web.SP_ClientSummaryTextMod @ClientKey = ?, @TermDesc = ?', [$ClientKey, $SummaryText]);
            return response()->json([
                'message' => 'Client summary text updated successfully',
            ]);
        } catch(\Exception $e) {
            return response()->json(['error' => 'Failed to set summary note', 'message' => $e->getMessage()], 500);
        }
    }
}
