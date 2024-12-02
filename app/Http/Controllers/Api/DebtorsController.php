<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Debtors;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Intervention\Image\ImageManagerStatic as Image;

class DebtorsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {          
        $mail = base64_decode($request->token);

        if ($mail != "") {
            $user = DB::select('web.SP_ValidateUserEmail @email = ?', [$mail]);
        } else {
            $user = [];
        }
        if(count($user) > 0){                     
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 25);
            $offset = ($page * $perPage)/$perPage;
            $search = $request->input('search') ? $request->input('search') : '';
            $sortBy = $request->input('sortBy', 'Debtor');
            $sortOrder = $request->input('sortOrder', 'ASC');
            $filterByBalance = $request->input('filterByBalance');

            $data = DB::select('web.SP_DebtorMasterDetails @OFFSET = ?, @LIMIT = ?, @SEARCH = ?, @sortColumn = ?, @sortDirection = ?,@filterByBalance = ?', [$offset, $perPage, $search, $sortBy, $sortOrder, $filterByBalance]);

            $DebtoNoBuyDisputeList = DB::select('Web.SP_DebtoNoBuyDisputeList');

            $total = DB::select('web.SP_CountDebtorMasterDetails');
            
            $response = response()->json([
                'DebtoNoBuyDisputeList' => $DebtoNoBuyDisputeList,
                'data' => $data,
                'total' => $total[0],
                'per_page' => $perPage,
                'current_page' => $page
            ]);

            return $response;   
        }else{
            return response()->json(['message' => 'User not found'], 404);
        }        
    }

    public function debtorContacts(Request $request)
    {      

        $debtorContactsData = DB::select('web.SP_InvoiceDebtorContactsDetails @Debtorkey = ?', [$request->DebtorKey]);
        
        return response()->json([
            'debtorContactsData' => $debtorContactsData,
        ]);
    }

    public function debtorPayments(Request $request)
    {      

        $debtorPaymentsData = DB::select('web.SP_RelationshipPaymentsList @ClientKey = ?, @Debtorkey = ?', [$request->ClientKey, $request->DebtorKey]);
        
        return response()->json([
            'debtorPaymentsData' => $debtorPaymentsData,
        ]);
    }

    public function debtorPaymentsImages(Request $request)
    {              
        try {
            $payment_images = [];           
            $debtorPaymentImages = DB::select('Web.SP_DebtorPaymentsImages @PmtChecksKey = ?', [$request->PmtChecksKey]);
            
            foreach ($debtorPaymentImages as $key => $value) {       
                $payment_images['fullname'] = $value->Path . "\\" . $value->FileName;
                $payment_images['basename'] = $value->FileName;
                
                $sourcePath = $value->Path . "\\" . $value->FileName;   
                
                if (!File::exists(public_path('payment_images'))) {
                    File::makeDirectory(public_path('payment_images'), 0755, true);
                }

                $destinationPath = public_path('payment_images/' . $value->FileName);

                $extension = File::extension($destinationPath);
                // if (!extension_loaded('imagick')) {
                //     phpinfo();
                //     throw new Exception('imagick not loaded');
                //     exit;
                // } else {
                //     echo 'Imagick Version: ' . phpversion('imagick') . "\n";
                //    // echo 'ImageMagick Version: ' . \Imagick::getVersion()['versionString'] . "\n";
                //     exit;
                // }
//C:\Program Files\ImageMagick-7.1.1-Q16-HDRI
                if ($extension == 'tif' || $extension == 'tiff') {

                    $tiff = $request->file('tiff'); 
                    $pdf = $tiff->storeAs('pdfs', 'converted.pdf'); 
                    $image = Image::make($tiff); 
                    $image->save($pdf);
                   // $image = new \Imagick();
                    
                    // Image::load($sourcePath)
                    // ->format('jpg')
                    // ->format($destinationPath);
                    // $destinationPath = public_path('payment_images/' . $value->FileName) . '.png';
                }
                
                if (File::exists($sourcePath)) {
                    File::copy($sourcePath, $destinationPath);                                                    
                } else {
                    echo 'Source file does not exist.';
                }                    
            }  
          
            return response()->json([
                'debtorPaymentImages' => $debtorPaymentImages,
            ]);            

        } catch(ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch(Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
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
