<?php

use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\ClientsInvoicesController;
use App\Http\Controllers\Api\DebtorsController;
use App\Http\Controllers\Api\MemberDebtorsController;
use App\Http\Controllers\Api\MasterClientsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::resource('debtors', DebtorsController::class)->except(['create', 'store', 'show', 'edit', 'update', 'destroy']);
Route::resource('memberDebtors', MemberDebtorsController::class)->except(['create', 'store', 'show', 'edit', 'update', 'destroy']);
Route::resource('clients', ClientController::class)->except(['create', 'store', 'show', 'edit', 'update', 'destroy']);
Route::resource('clientsinvoices', ClientsInvoicesController::class)->except(['create', 'store', 'show', 'edit', 'update', 'destroy']);
Route::resource('masterClients', MasterClientsController::class)->except(['create', 'store', 'show', 'edit', 'update', 'destroy']);