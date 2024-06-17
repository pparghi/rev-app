<?php

use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\DebtorsController;
use App\Http\Controllers\Api\MemberDebtorsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::resource('clients', ClientController::class)->except(['create', 'store', 'show', 'edit', 'update', 'destroy']);
Route::resource('debtors', DebtorsController::class)->except(['create', 'store', 'show', 'edit', 'update', 'destroy']);
Route::resource('memberDebtors', MemberDebtorsController::class)->except(['create', 'store', 'show', 'edit', 'update', 'destroy']);