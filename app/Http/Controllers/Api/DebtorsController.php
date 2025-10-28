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
use App\Services\ApiLoggingService;

class DebtorsController extends Controller
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
        $mail = base64_decode($request->token);

        if ($mail != "") {
            $user = DB::select('web.SP_ValidateUserEmail @email = ?', [$mail]);
        } else {
            $user = [];
        }
        // if(count($user) > 0){                     
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 25);
        $offset = ($page * $perPage) / $perPage;
        $search = $request->input('search') ? $request->input('search') : '';
        $sortBy = $request->input('sortBy', 'Balance');
        $sortOrder = $request->input('sortOrder', 'DESC');
        $filterByBalance = $request->input('filterByBalance');

        try {
            $data = DB::select('web.SP_DebtorMasterDetails @OFFSET = ?, @LIMIT = ?, @SEARCH = ?, @sortColumn = ?, @sortDirection = ?,@filterByBalance = ?', [$offset, $perPage, $search, $sortBy, $sortOrder, $filterByBalance]);
        } catch (Exception $e) {
            $isTimeout = str_contains($e->getMessage(), 'timeout') || 
            str_contains($e->getMessage(), 'Lock request time out') ||
            str_contains($e->getMessage(), 'query timeout');

            \Log::error("web.SP_DebtorMasterDetails request failed", [
                'error' => $e->getMessage(),
                'is_deadlock' => str_contains($e->getMessage(), 'deadlock')
            ]);

            // Return timeout response immediately on first attempt
            if ($isTimeout) {
                return response()->json([
                    'error' => 'Request timed out',
                    'code' => 'TIMEOUT',
                    'message' => 'The query took too long to execute. This is a test timeout scenario.',
                    'params' => compact('offset', 'perPage', 'search', 'sortBy', 'sortOrder', 'filterByBalance')
                ], 408);
            }

            throw $e;
        } finally {
            // Reset lock timeout to default
            DB::statement('SET LOCK_TIMEOUT -1');
        }

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
        // }else{
        //     return response()->json(['message' => 'User not found'], 404);
        // }        
    }

    public function debtorContacts(Request $request)
    {      

        $debtorContactsData = DB::select('web.SP_InvoiceDebtorContactsDetails @Debtorkey = ?', [$request->DebtorKey]);
        $debtorAudit = DB::select('web.SP_DebtorAudit @Debtorkey = ?', [$request->DebtorKey]);
        $debtorStatementsDetails = DB::select('web.SP_DebtorStatementDetails @Debtorkey = ?', [$request->DebtorKey]);
        
        return response()->json([
            'debtorContactsData' => $debtorContactsData,
            'debtorAudit' => $debtorAudit,
            'debtorStatementsDetails' => $debtorStatementsDetails
        ]);
    }

    public function debtorPayments(Request $request)
    {      

        $debtorPaymentsData = DB::select('web.SP_RelationshipPaymentsList @ClientKey = ?, @Debtorkey = ?', [$request->ClientKey, $request->DebtorKey]);
        
        return response()->json([
            'debtorPaymentsData' => $debtorPaymentsData,
        ]);
    }
    public function DebtorChecksSearch(Request $request)
    {      

        $payments = DB::select('web.SP_DebtorChecksSearch @Debtorkey = ?, @CheckNo = ?, @Amt = ?, @PostDateStart = ?, @PostDateEnd = ?, @LastPayments = ?', [$request->DebtorKey, $request->CheckNo, $request->Amt, $request->PostDateStart, $request->PostDateEnd, $request->LastPayments]);
        
        return response()->json([
            'payments' => $payments
        ]);
    }

    public function debtorPaymentsImages(Request $request)
    {              
        try {
            $data = DB::select('web.SP_DebtorPaymentsImages @PmtChecksKey = ?', [$request->PmtChecksKey]);

            $images = [];

            foreach ($data as $imageInfo) {
                $fullPath = $imageInfo->Path . "\\" . $imageInfo->FileName;

                if (File::exists($fullPath)) {
                    // Read the image file
                    $imageData = File::get($fullPath);

                    // Convert to base64 for JSON response
                    $base64Image = base64_encode($imageData);

                    // Determine content type based on file extension
                    $extension = strtolower(pathinfo($imageInfo->FileName, PATHINFO_EXTENSION));
                    $contentType = match ($extension) {
                        'jpg', 'jpeg' => 'image/jpeg',
                        'png' => 'image/png',
                        'gif' => 'image/gif',
                        'bmp' => 'image/bmp',
                        'tif', 'tiff' => 'image/tiff',
                        default => 'application/octet-stream'
                    };

                    $images[] = [
                        'FileName' => $imageInfo->FileName,
                        'Base64Data' => $base64Image,
                        'ContentType' => $contentType,
                        'FileSize' => strlen($imageData)
                    ];
                } else {
                    // Log missing file but continue processing others
                    \Log::warning("Image file not found: {$fullPath}");

                    $images[] = [
                        'FileName' => $imageInfo->FileName,
                        'Base64Data' => null,
                        'ContentType' => null,
                        'FileSize' => 0,
                        'Error' => 'File not found'
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'images' => $images,
                'count' => count($images)
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
    // public function updateCreditLimit(Request $request)
    // {
    //     try {
    //         DB::statement('web.SP_DebtorChangeTotalCreditLimit @DebtorKey = ?, @TotalCreditLimit = ?, @CredAppBy = ?', [$request->DebtorKey, $request->TotalCreditLimit, $request->CredAppBy]);
    //     } catch(\Exception $e) {
    //         return response()->json(['error' => 'Failed to update creditLimit', 'message' => $e->getMessage()], 500);
    //     }
    // }

    // public function updateAccountStatus(Request $request)
    // {
    //     try {
    //         DB::statement('web.SP_DebtorChangeNoBuyDispute @DebtorKey = ?, @NoBuyDisputeKey = ?, @CredAppBy = ?', [$request->DebtorKey, $request->NoBuyDisputeKey, $request->CredAppBy]);
    //     } catch(\Exception $e) {
    //         return response()->json(['error' => 'Failed to update creditLimit', 'message' => $e->getMessage()], 500);
    //     }
    // }
    public function updateDebtorDetails(Request $request)
    {               
        try {
            $DebtorKey = $request->DebtorKey ? $request->DebtorKey : '';
            $Debtor = $request->Debtor ? $request->Debtor : '';
            $Duns = $request->Duns ? $request->Duns : '';
            $Addr1 = $request->Addr1 ? $request->Addr1 : '';
            $Addr2 = $request->Addr2 ? $request->Addr2 : '';
            $Phone1 = $request->Phone1 ? $request->Phone1 : '';
            $Phone2 = $request->Phone2 ? $request->Phone2 : '';
            $City = $request->City ? $request->City : '';
            $State = $request->State ? $request->State : '';
            $ZipCode = $request->ZipCode ? $request->ZipCode : NULL;
            $Country = $request->Country ? $request->Country : NULL;
            $TotalCreditLimit = $request->TotalCreditLimit ? $request->TotalCreditLimit : '';
            $IndivCreditLimit = $request->IndivCreditLimit ? $request->IndivCreditLimit : '';
            $AIGLimit = $this->cleanNumericValue($request->AIGLimit);
            $Terms = $request->Terms ? $request->Terms : '';
            $MotorCarrNo = $request->MotorCarrNo ? $request->MotorCarrNo : '';
            $CredAppBy = $request->CredAppBy ? $request->CredAppBy : '';
            $Email = $request->Email ? $request->Email : '';
            $RateDate = $request->RateDate ? $request->RateDate : '';
            $CredExpireMos = $request->CredExpireMos ? $request->CredExpireMos : '';
            $Notes = $request->Notes ? $request->Notes : '';
            $CredNote = $request->CredNote ? $request->CredNote : '';
            $Warning = $request->Warning ? $request->Warning : '';
            $DotNo = $request->DotNo ? $request->DotNo : '';

            $result = DB::select('web.SP_DebtorChangeDetails @DebtorKey = ?, @Name = ?, @DbDunsNo = ?, @Addr1 = ?, @Addr2 = ?, @Phone1 = ?, @Phone2 = ?, @City = ?, @State = ?, @Country = ?, @ZipCode = ?, @TotalCreditLimit = ?, @IndivCreditLimit = ?, @AIGLimit = ?, @Terms = ?, @MotorCarrNo = ?, @CredAppBy = ?, @Email = ?, @RateDate = ?, @CredExpireMos = ?, @Notes = ?, @CredNote = ?, @Warning = ?, @DotNo = ?', [$DebtorKey, $Debtor, $Duns, $Addr1, $Addr2, $Phone1, $Phone2, $City, $State, $Country, $ZipCode, $TotalCreditLimit, $IndivCreditLimit, $AIGLimit, $Terms, $MotorCarrNo, $CredAppBy, $Email, $RateDate, $CredExpireMos, $Notes, $CredNote, $Warning, $DotNo]);            
            
            // Log the API call
            $this->apiLogger->logApiCall(
                'update_Debtor_Details',
                $request->all(),
                $result,
                'success',
                $request->CredAppBy ? $request->CredAppBy : 'UNKNOWN'
            );
            
            return response()->json(
                $result,
            );
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


    /**
     * Display list of debtor history/trend by month/year/quater and clients.
     */
    public function debtorHistoryTrend(Request $request)
    {    
        $DebtorKey = $request->DebtorKey ? $request->DebtorKey : '';    
        $ClientNo = $request->ClientNo ? $request->ClientNo : '';
        $Type = $request->Type ? $request->Type : 'M';
        
        $data = DB::select('Web.SP_DebtorHistoryTrend @DebtorKey = ?, @ClientNo = ?, @Type = ?', [$DebtorKey, $ClientNo, $Type]);
        
        return response()->json([
            'data' => $data,
        ]);
    }


    /**
     * Search for debtor's Duns number by using DnB API
     */
    public function searchDuns(Request $request)
    {
        $postfields = array(
            'name' => $request->Name, 
            'address' => $request->Address ?? '', 
            'addressLine2' => $request->AddressLine2 ?? '', 
            'city' => $request->City ?? '', 
            'state' => $request->State ?? '', 
            'zipCode' => $request->ZipCode ?? '', 
            'country' => in_array($request->Country, ['CA', 'US']) ? $request->Country : 'CA',
            'environment' => 'production'
        );
        $postfields = json_encode($postfields);

        // $url = "https://login.baron.finance/iris/public/api/dnb/match.php";
        $url = "https://iris.revinc.com/iris/public/api/dnb/match.php";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
        curl_setopt($ch, CURLOPT_HEADER, false);           // return response header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);    // return response
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_NOBODY, false);           // suppress result
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 30000);       // connection timeout (1 second = 1000)
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
        $response = curl_exec($ch);
        // $response = '{"test": "test"}'; // For testing purposes, replace with actual API call

        // Log the API call
        $this->apiLogger->logApiCall(
            'search_Duns_API',
            json_decode($postfields, true),
            json_decode($response, true),
            curl_errno($ch) ? 'error' : 'success',
            $request->header('X-User-Id')
        );

        curl_close($ch);

        $results = json_decode($response, true);
        
        return response()->json([
            'results' => $results,
        ]);
    }

    // getting the Debtor No Buy Code List 
    public function getDebtorNoBuyCodeList()
    {
        $data = DB::select('web.SP_DebtoNoBuyDisputeList');
        
        return response()->json([
            'data' => $data
        ]);
    }

    // method to update debtor's No Buy Code
    public function updateDebtorNoBuyCode(Request $request){
        try {
            $DebtorKey = $request->DebtorKey ? $request->DebtorKey : '';
            $NoBuyDisputeKey = $request->NoBuyDisputeKey ? $request->NoBuyDisputeKey : '';
            $CredAppBy = $request->CredAppBy ? $request->CredAppBy : '';

            DB::statement('web.SP_DebtorChangeNoBuyDispute @DebtorKey = ?, @NoBuyDisputeKey = ?, @CredAppBy = ?', [$DebtorKey, $NoBuyDisputeKey, $CredAppBy]);
            
            return response()->json(['message' => 'Debtor No Buy Code updated successfully']);
        } catch(ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch(Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function cleanNumericValue($value)
    {
        if (empty($value) || $value === '' || $value === null) {
            return null;
        }

        // Remove commas, spaces, and other formatting
        $cleaned = preg_replace('/[^\d.-]/', '', $value);

        // Validate it's a proper number
        if (is_numeric($cleaned)) {
            // Convert to appropriate type
            if (strpos($cleaned, '.') !== false) {
                return (float) $cleaned;
            } else {
                return (int) $cleaned;
            }
        }

        return null;
        
    }

    // getting the country & area List 
    public function getCountryAreaList()
    {
        $data = DB::select('web.SP_CountryList');

        return response()->json([
            'data' => $data
        ]);
    }

    //region alternate addresses
    // getting the Debtor No Buy Code List 
    public function getDebtorAlternateAddresses(Request $request)
    {
        $data = DB::select('web.SP_DebtorAltAddressList @DebtorKey = ?' , [$request->DebtorKey]);

        return response()->json([
            'data' => $data
        ]);
    }

    // method to delete debtor's alternate address
    public function deleteDebtorAlternateAddress(Request $request){
        try {
            $DebtorKey = $request->DebtorKey ? $request->DebtorKey : '';
            $AltAddressKey = $request->AltAddressKey ? $request->AltAddressKey : '';
            $UpdateBy = $request->UpdateBy ? $request->UpdateBy : '';
            DB::statement('web.SP_DebtorAltAddressDel @DebtorKey = ?, @AltAddressKey = ?, @UpdateBy = ?', [$DebtorKey, $AltAddressKey, $UpdateBy]);

            return response()->json(['message' => 'Debtor alternate address deleted successfully']);
        } catch(Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // method to add debtor's alternate address
    public function addDebtorAlternateAddress(Request $request){
        try {
            $DebtorKey = $request->DebtorKey ? $request->DebtorKey : '';
            $Name = $request->Name ? $request->Name : '';
            $Addr1 = $request->Addr1 ? $request->Addr1 : '';
            $Addr2 = $request->Addr2 ? $request->Addr2 : '';
            $City = $request->City ? $request->City : '';
            $State = $request->State ? $request->State : '';
            $ZipCode = $request->ZipCode ? $request->ZipCode : '';
            $Country = $request->Country ? $request->Country : '';
            $UpdateBy = $request->UpdateBy ? $request->UpdateBy : '';
            DB::statement('web.SP_DebtorAltAddressAdd @DebtorKey = ?, @Name = ?, @Addr1 = ?, @Addr2 = ?, @City = ?, @State = ?,  @ZipCode= ?, @Country = ?, @UpdateBy= ?', [$DebtorKey, $Name, $Addr1, $Addr2, $City, $State, $ZipCode, $Country, $UpdateBy]);

            return response()->json(['message' => 'Debtor alternate address added successfully']);
        } catch(Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    //endregion alternate addresses

    //region debtor alerts
    // getting the Debtor alert List 
    public function getDebtorAlertsList(Request $request)
    {
        $data = DB::select('web.SP_AlertsList');

        return response()->json([
            'data' => $data
        ]);
    }

    //endregion debtor alerts



}
