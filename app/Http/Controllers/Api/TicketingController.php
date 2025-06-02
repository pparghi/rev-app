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


    // #region Credit Request
    // get status list for Approve/Deny credit requests
    public function getCreditRequestStatusList()
    {
        $data = DB::select('web.SP_CredRequestStatus');
        
        return response()->json([
            'data' => $data
        ]);
    }

    // approve credit request 
    public function approveCreditRequest(Request $request)
    {
        try {
    
            $data = DB::statement('Web.SP_CredRequestApproval 
                @CredRequestKey = ?, 
                @ApproveUser = ?,
                @Status = ?,
                @Response = ?,
                @ApprovedLimit = ?,
                @NewLimitAmt = ?,
                @ExpMonths = ?', 
                [
                    $request->CredRequestKey,
                    $request->ApproveUser,
                    $request->Status,
                    $request->Response,
                    $request->ApprovedLimit ?? null,
                    $request->NewLimitAmt ?? null,
                    $request->ExpMonths
                ]
            );
    
            return response()->json([
                'message' => 'Credit request processed successfully',
                'data' => $data
            ]);
    
        } catch (\Exception $e) {
            \Log::error('Credit Request Approval Error:', [
                'error' => $e->getMessage(),
                'parameters' => $request->all()
            ]);
            
            return response()->json([
                'error' => 'Failed to process credit request',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
