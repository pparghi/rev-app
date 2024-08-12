<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DebtorDocumentsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $DebtorKey = $request->get('DebtorKey');
        
        $documentsList = DB::select('web.SP_DebtorDocuments @Debtorkey  = ?', [$DebtorKey]);
        $documentsCat = DB::select('web.SP_DocumentCategory');
        
        return response()->json([
            'documentsList' => $documentsList,
            'documentsCat' => $documentsCat,
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

    public function uploadDebtorDocuments(Request $request)
    {
        try {
            DB::statement('web.SP_DebtorMasterAddDocument @DebtorKey = ?, @Descr = ?, @FileName = ?, @DocCatKey = ?', [$request->DebtorKey, $request->Descr, $request->FileName, $request->DocCatKey]);
        } catch(\Exception $e) {
            return response()->json(['error' => 'Failed to upload documents', 'message' => $e->getMessage()], 500);
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
