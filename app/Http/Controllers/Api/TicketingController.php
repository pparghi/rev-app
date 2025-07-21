<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\ApiLoggingService;

class TicketingController extends Controller
{
    protected $apiLogger;

    public function __construct(ApiLoggingService $apiLogger)
    {
        $this->apiLogger = $apiLogger;
    }

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
                @ExpMonths = ?,
                @Email = ?', 
                [
                    $request->CredRequestKey,
                    $request->ApproveUser,
                    $request->Status,
                    $request->Response,
                    $request->ApprovedLimit ?? null,
                    $request->NewLimitAmt ?? null,
                    $request->ExpMonths,
                    $request->Email
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

    // approve credit request version 2 - New API, need put to API dictionary
    public function approveCreditRequest2(Request $request)
    {
        try {
    
            $response = DB::select('Web.SP_CredRequestApproval2 
                @CredRequestKey = ?, 
                @ApproveUser = ?,
                @Status = ?,
                @Response = ?,
                @NewTotalCreditLimit = ?,
                @NewIndivCreditLimit = ?,
                @ExpMonths = ?,
                @Email = ?, 
                @ChangeMaster = ?',
                [
                    $request->CredRequestKey,
                    $request->ApproveUser,
                    $request->Status,
                    $request->Response ?? '',
                    $request->NewTotalCreditLimit ?? null,
                    $request->NewIndivCreditLimit ?? null,
                    $request->ExpMonths,
                    $request->Email,
                    $request->ChangeMaster
                ]
            );

            // Log the API call
            $this->apiLogger->logApiCall(
                'CredRequestApproval2',                    // apiName
                $request->all(),                              // request (array)
                $response,                                    // response (can be array or object)
                'success',                                    // status
                $request->ApproveUser                 // userId (optional)
            );
    
            return response()->json([
                'response' => $response
            ]);
    
        } catch (\Exception $e) {
            // Log the error case
            $this->apiLogger->logApiCall(
                'SP_CredRequestApproval2',                    // apiName
                $request->all(),                              // request (array)
                ['error' => $e->getMessage()],                // response (error info)
                'error',                                      // status
                $request->ApproveUser                 // userId (optional)
            );

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

    // the API check and modify Credit Request Lock Ticket
    public function actionToCreditRequest(Request $request)
    {
        try {
            // Execute the stored procedure and capture the result directly
            $result = DB::select('EXEC Web.SP_CredRequestLockTicket 
            @CredRequestKey = ?, 
            @InUseUser = ?,
            @LockUnlock = ?',
                [
                    $request->CredRequestKey,
                    $request->InUseUser,
                    $request->LockUnlock
                ]
            );

            return response()->json([
                'status' => 'success',
                'result' => $result
            ]);

        } catch (\Exception $e) {
            \Log::error('Error to lock/unlock credit request:', [
                'error' => $e->getMessage(),
                'parameters' => $request->all()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to lock/unlock credit request',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
