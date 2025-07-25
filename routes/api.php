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
use App\Http\Controllers\Api\RiskMonitoringController;
use App\Http\Controllers\Api\TicketingController;
use App\Http\Controllers\Api\DocumentsReportsController;
use App\Http\Controllers\Api\InvoiceSearchController;
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
Route::post('updateDebtorDetails', [DebtorsController::class, 'updateDebtorDetails']);
Route::get('DebtorChecksSearch', [DebtorsController::class, 'DebtorChecksSearch']);
Route::get('debtorHistoryTrend', [DebtorsController::class, 'debtorHistoryTrend']);
Route::get('searchDuns', [DebtorsController::class, 'searchDuns']);
// Route::post('updateDebtorCreditLimit', [DebtorsController::class, 'updateCreditLimit']);
// Route::post('updateDebtorAccountStatus', [DebtorsController::class, 'updateAccountStatus']);
Route::resource('memberDebtors', MemberDebtorsController::class)->except(['create', 'store', 'show', 'edit', 'update', 'destroy']);
Route::resource('clients', ClientController::class)->except(['create', 'store', 'show', 'edit', 'update', 'destroy']);
Route::resource('clientsinvoices', ClientsInvoicesController::class)->except(['create', 'store', 'show', 'edit', 'update', 'destroy']);
Route::resource('masterClients', MasterClientsController::class)->except(['create', 'store', 'show', 'edit', 'update', 'destroy']);
Route::resource('memberClients', MemberClientsController::class)->except(['create', 'store', 'show', 'edit', 'update', 'destroy']);
Route::resource('ClientsDebtors', ClientsDebtorController ::class)->except(['create', 'store', 'show', 'edit', 'update', 'destroy']);
Route::get('documentsList', [DebtorDocumentsController::class, 'index']);
Route::post('debtorMasterAddDocument', [DebtorDocumentsController::class, 'uploadDebtorDocuments']);
Route::get('memberMasterDebtor', [MemberMasterDebtorController::class, 'index']);
Route::get('MiscDataList', [MiscDataListController::class, 'index']);
// Route::get('clientGroupLevelList', [MasterClientsController::class, 'clientGroupLevelList']);
Route::get('clientGroupList', [MasterClientsController::class, 'clientGroupList']);
Route::get('clientGroupValueList', [MasterClientsController::class, 'clientGroupValueList']);
Route::get('login', [LoginController::class, 'index']);
Route::get('exchangeRatesByMonth', [LoginController::class, 'exchangeRatesByMonth']); 
Route::get('riskMonitoring', [RiskMonitoringController::class, 'index']); 
Route::get('clientGroupLevelList', [RiskMonitoringController::class, 'clientGroupLevelList']); 
Route::get('CRMList', [RiskMonitoringController::class, 'CRMList']); 
Route::get('officeList', [RiskMonitoringController::class, 'officeList']); 
Route::get('DDCreatedBy', [RiskMonitoringController::class, 'DDCreatedBy']); 
Route::get('ClientDetails', [RiskMonitoringController::class, 'ClientDetails']); 
Route::get('ClientContactsDetails', [RiskMonitoringController::class, 'ClientContactsDetails']); 
Route::get('MonitoringCategories', [RiskMonitoringController::class, 'MonitoringCategories']); 
Route::get('MonitoringNotes', [RiskMonitoringController::class, 'MonitoringNotes']); 
Route::post('addNotesRisk', [RiskMonitoringController::class, 'addNotesRisk']);
Route::post('updateCRMRisk', [RiskMonitoringController::class, 'updateCRMRisk']);
Route::post('updateLevelRisk', [RiskMonitoringController::class, 'updateLevelRisk']);
Route::post('updateCompleteStatusRisk', [RiskMonitoringController::class, 'updateCompleteStatusRisk']);
Route::get('getClientSummaryNote', [RiskMonitoringController::class, 'getClientSummaryNote']);
Route::post('setClientSummaryNote', [RiskMonitoringController::class, 'setClientSummaryNote']);
Route::get('creditRequests', [TicketingController::class, 'index']); 
Route::get('getCreditRequestStatusList', [TicketingController::class, 'getCreditRequestStatusList']); 
Route::post('approveCreditRequest', [TicketingController::class, 'approveCreditRequest']); 
Route::post('approveCreditRequest2', [TicketingController::class, 'approveCreditRequest2']); 
Route::get('actionToCreditRequest', [TicketingController::class, 'actionToCreditRequest']); 
Route::get('invoiceDetailNotes', [ClientsInvoicesController::class, 'invoiceDetailNotes']); 
Route::post('ClientNotesHide', [RiskMonitoringController::class, 'ClientNotesHide']);
Route::get('getClientsList', [DocumentsReportsController::class, 'getClientsList']); 
Route::get('getDebtorsListByClientKey', [DocumentsReportsController::class, 'getDebtorsListByClientKey']); 
Route::get('getNOADebtorsListByClientKey', [DocumentsReportsController::class, 'getNOADebtorsListByClientKey']); 
Route::get('getDebtorsListByName', [DocumentsReportsController::class, 'getDebtorsListByName']); 
Route::get('callNOAIRISAPI', [DocumentsReportsController::class, 'callNOAIRISAPI']); 
Route::get('callAnsoniaAPI', [DocumentsReportsController::class, 'callAnsoniaAPI']); 
Route::get('callInvoiceImageAPI', [DocumentsReportsController::class, 'callInvoiceImageAPI']); 
Route::get('callLORCreatePDFAPI', [DocumentsReportsController::class, 'callLORCreatePDFAPI']); 
Route::get('callLORCreatePDFsAPI', [DocumentsReportsController::class, 'callLORCreatePDFsAPI']); 
Route::get('getInvoiceStatusList', [InvoiceSearchController::class, 'getInvoiceStatusList']); 
Route::get('getDisputeCodeList', [InvoiceSearchController::class, 'getDisputeCodeList']); 
Route::get('getInvoicesList', [InvoiceSearchController::class, 'getInvoicesList']); 
Route::get('/paymentsFiles/{filename}', function ($filename) {
    $path = public_path('payment_images/' . $filename);

    if (!File::exists($path)) {
        abort(404);
    }

    return response()->file($path); 
});