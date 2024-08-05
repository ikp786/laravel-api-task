<?php

use App\Http\Controllers\Api\AuthenticationController;
use App\Http\Controllers\Api\TransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');


Route::controller(AuthenticationController::class)->group(function () {
    Route::post('register', 'register');
    Route::post('login', 'login');
});


Route::middleware('auth:api')->group(function () {
    Route::controller(TransactionController::class)->group(function () {
        Route::post('encrypt', 'encrypt');
        Route::post('decrypt', 'decrypt');

        // Route::post('transactions', 'store');
        // Route::get('transactions', 'index');
        // Route::get('transactions/{id}', 'show');
    });
    Route::apiResource('transactions',TransactionController::class);
});
