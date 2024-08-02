<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Debtors;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class DebtorsController extends Controller
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
        $sortBy = $request->input('sortBy', 'Debtor');
        $sortOrder = $request->input('sortOrder', 'ASC');

        $data = DB::select('web.SP_DebtorMasterDetails @OFFSET = ?, @LIMIT = ?, @SEARCH = ?, @sortColumn = ?, @sortDirection = ?', [$offset, $perPage, $search, $sortBy, $sortOrder]);

        $DebtoNoBuyDisputeList = DB::select('Web.SP_DebtoNoBuyDisputeList');

        $total = DB::select('web.SP_CountDebtorMasterDetails');
        
        return response()->json([
            'DebtoNoBuyDisputeList' => $DebtoNoBuyDisputeList,
            'data' => $data,
            'total' => $total[0],
            'per_page' => $perPage,
            'current_page' => $page
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
    public function updateCreditLimit(Request $request)
    {
        try {
            DB::statement('web.SP_DebtorChangeTotalCreditLimit @DebtorKey = ?, @TotalCreditLimit = ?, @CredAppBy = ?', [$request->DebtorKey, $request->TotalCreditLimit, $request->CredAppBy]);
        } catch(\Exception $e) {
            return response()->json(['error' => 'Failed to update creditLimit', 'message' => $e->getMessage()], 500);
        }
    }

    public function updateAccountStatus(Request $request)
    {
        try {
            DB::statement('web.SP_DebtorChangeNoBuyDispute @DebtorKey = ?, @NoBuyDisputeKey = ?, @CredAppBy = ?', [$request->DebtorKey, $request->NoBuyDisputeKey, $request->CredAppBy]);
        } catch(\Exception $e) {
            return response()->json(['error' => 'Failed to update creditLimit', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
