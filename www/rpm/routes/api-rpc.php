<?php

use App\Http\Controllers\Rpc\AuthController;
use App\Http\Controllers\Rpc\ParticipantController;
use App\Http\Controllers\Rpc\PrintController;
use App\Http\Controllers\Rpc\TicketController;
use App\Http\Controllers\Rpc\ValidationController;
use Illuminate\Support\Facades\Route;

// Public endpoint - tidak perlu auth
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected endpoints - require Sanctum auth
Route::middleware('auth:sanctum')->group(function () {
    // Ticket scanning
    Route::post('/tickets/scan', [TicketController::class, 'scan']);
    
    // Print payload
    Route::post('/prints/payload', [PrintController::class, 'getPayload']);
    
    // Participant search
    Route::get('/participants/search', [ParticipantController::class, 'search']);
    
    // Validation
    Route::post('/validate', [ValidationController::class, 'validate']);
});

