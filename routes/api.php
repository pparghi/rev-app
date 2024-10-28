<?php

use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\ClientsDebtorController;
use App\Http\Controllers\Api\ClientsInvoicesController;
use App\Http\Controllers\Api\DebtorDocumentsController;
use App\Http\Controllers\Api\DebtorsController;
use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\MemberDebtorsController;
use App\Http\Controllers\Api\MasterClientsController;
use App\Http\Controllers\Api\MemberClientsController;
use App\Http\Controllers\Api\MemberMasterDebtorController;
use App\Http\Controllers\Api\MiscDataListController;
use App\Http\Middleware\CorsMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::get('debtors', [DebtorsController::class, 'index']);
Route::get('debtorContactsData', [DebtorsController::class, 'debtorContacts']);
Route::get('debtorPaymentsData', [DebtorsController::class, 'debtorPayments']);
Route::get('debtorPaymentsImages', [DebtorsController::class, 'debtorPaymentsImages']);
Route::post('updateDebtorCreditLimit', [DebtorsController::class, 'updateCreditLimit']);
Route::post('updateDebtorAccountStatus', [DebtorsController::class, 'updateAccountStatus']);
Route::resource('memberDebtors', MemberDebtorsController::class)->except(['create', 'store', 'show', 'edit', 'update', 'destroy']);
Route::resource('clients', ClientController::class)->except(['create', 'store', 'show', 'edit', 'update', 'destroy']);
Route::resource('clientsinvoices', ClientsInvoicesController::class)->except(['create', 'store', 'show', 'edit', 'update', 'destroy']);
Route::resource('masterClients', MasterClientsController::class)->except(['create', 'store', 'show', 'edit', 'update', 'destroy']);
Route::resource('memberClients', MemberClientsController::class)->except(['create', 'store', 'show', 'edit', 'update', 'destroy']);
Route::resource('ClientsDebtors', ClientsDebtorController ::class)->except(['create', 'store', 'show', 'edit', 'update', 'destroy']);
Route::get('documentsList', [DebtorDocumentsController ::class, 'index']);
Route::post('debtorMasterAddDocument', [DebtorDocumentsController ::class, 'uploadDebtorDocuments']);
Route::get('memberMasterDebtor', [MemberMasterDebtorController ::class, 'index']);
Route::get('MiscDataList', [MiscDataListController ::class, 'index']);
Route::get('clientGroupLevelList', [MasterClientsController ::class, 'clientGroupLevelList']);
Route::get('login', [LoginController ::class, 'index']);
Route::get('/paymentsFiles/{filename}', function ($filename) {
    $path = public_path('payment_images/' . $filename);

    if (!File::exists($path)) {
        abort(404);
    }

    return response()->file($path); 
});