<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Exception;
use Illuminate\Support\Facades\DB;

class LoginController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $email = $request->input('mail');

        $data = DB::select('web.SP_UserInfo @email = ?', [$email]);
        
        return response()->json([
            'data' => $data
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

    public function login(Request $request)
   {
       $encryptedEmail = $request->input('email');
       try {
           $secretKey = env('ENCRYPTION_SECRET_KEY'); // Get the secret key from .env
           $decryptedEmail = openssl_decrypt(base64_decode($encryptedEmail), 'aes-256-cbc', $secretKey, 0, str_repeat("\0", 16));
           /* 

           Pre-requistics - Create DB table 'user_tokens' with 2 columns "email id" and "token", "created_at"
        
        if (email id exists in DB Table "User And Groups") {

            if (email id exists in DB table "user_tokens") {

                if (access token is not expired) {
                    return json response with access token
                } else {
                    generate new access token using -- $token = Str::random(60);
                    and Apply Hash for security -- hash('sha256', $token)
                    insert or update into DB table "user_tokens" new record/data for generated token
                    return json response with access token
                }
            } else {
                    generate new access token using -- $token = Str::random(60);
                    and Apply Hash for security -- hash('sha256', $token)
                    insert or update into DB table "user_tokens" new record/data for generated token
                    return json response with access token
            }
        } else {
            return Json response with error message "email doesn't exists."
        }

            */
       } catch (Exception $e) {
           return response()->json(['error' => 'Decryption failed'], 500);
       }
   }

   public function exchangeRatesByMonth(){
        $exchangeRatesByMonth = DB::select('Web.SP_ExchangeRatesByMonth');
        
        return response()->json([
            'exchangeRatesByMonth' => $exchangeRatesByMonth
        ]);
   }
}
