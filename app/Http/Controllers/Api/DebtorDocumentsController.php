<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

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
        $documentsFolder = DB::select('web.SP_DocumentsFolder');

        // foreach ($documentsList as $key => $value) {
        //     $fileName = $value->Path . "/" . $value->DocHdrKey . '.' . pathinfo($value->FileName, PATHINFO_EXTENSION);
        //     if (copy($fileName, storage_path('app/public/uploads'))) {
        //         echo "File copied successfully.";
        //     } else {
        //         echo "Error copying file.";
        //     }
            
        // }
        
        return response()->json([
            'documentsList' => $documentsList,
            'documentsCat' => $documentsCat,
            'documentsFolder' => $documentsFolder
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
            $request->validate([
                'file' => 'required|file|mimes:xls,xlsx,pdf,doc,docx,jpg,png,jpeg|max:2048', // Update mimes as needed
            ]);    
            $file = $request->file('file');
                        
            $destinationPath = $request->DocFolderPath; // Ensure this path exists and is writable
            $fileName = $file->getClientOriginalName();
            $cdt = date('Y-m-d H:i:s');

            DB::statement('web.SP_DebtorMasterAddDocument @DebtorKey = ?, @Descr = ?, @FileName = ?, @DocCatKey = ?, @cdt = ?', [$request->DebtorKey, $request->Descr, $fileName, $request->DocCatKey, $cdt]);

            $docHdrKey = DB::select('web.SP_GetDocHdrKey @cdt = ?', [$cdt]);

            foreach ($docHdrKey as $key => $value) {
                $new_file_name = $value->DocHdrKey;
            }

            $file->move($destinationPath, $new_file_name . '.' . $file->getClientOriginalExtension());
            
            return response()->json([
                'message' => 'File uploaded successfully!',
                'file_path' => $destinationPath . '\\' . $fileName,
            ]);
        } catch(ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch(Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
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
