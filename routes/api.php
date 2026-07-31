<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmployeeController;
use Illuminate\Support\Facades\Route;

// Public Auth routes
Route::post('/login', [AuthController::class, 'login']);

// Authenticated Passport routes
Route::middleware('auth:api')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);

    // Employee CRUD routes
    Route::get('/employees', [EmployeeController::class, 'index']);
    Route::get('/employees/export', [EmployeeController::class, 'exportCsv']);
    Route::post('/employees/import', [EmployeeController::class, 'importCsv']);
    Route::post('/employees/generate-faker', [EmployeeController::class, 'generateFaker']);
    Route::get('/employees/{id}', [EmployeeController::class, 'show']);
    Route::post('/employees', [EmployeeController::class, 'store']);
    Route::put('/employees/{id}', [EmployeeController::class, 'update']);
    Route::delete('/employees/{id}', [EmployeeController::class, 'destroy']);
    Route::post('/employees/{id}/restore', [EmployeeController::class, 'restore']);
    Route::delete('/employees/{id}/force', [EmployeeController::class, 'forceDelete']);
});
