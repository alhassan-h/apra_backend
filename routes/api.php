<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DrugController;
use App\Http\Controllers\Api\ExtractionController;
use App\Http\Controllers\Api\PrescriptionController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ReviewController;
use Illuminate\Support\Facades\Route;

// ── Auth ─────────────────────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::put('profile', [AuthController::class, 'updateProfile']);
    });
});

// ── Protected ─────────────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Prescriptions
    Route::get('prescriptions', [PrescriptionController::class, 'index']);
    Route::post('prescriptions', [PrescriptionController::class, 'store']);
    Route::get('prescriptions/{prescription}', [PrescriptionController::class, 'show']);
    Route::delete('prescriptions/{prescription}', [PrescriptionController::class, 'destroy']);

    // AI Extraction (read-only — written by the queue job)
    Route::get('prescriptions/{prescription}/extraction', [ExtractionController::class, 'show']);

    // Review
    Route::post('prescriptions/{prescription}/review', [ReviewController::class, 'store']);
    Route::get('prescriptions/{prescription}/review', [ReviewController::class, 'show']);
    Route::put('prescriptions/{prescription}/review', [ReviewController::class, 'update']);

    // Drug Dictionary
    Route::get('drugs', [DrugController::class, 'index']);
    Route::post('drugs', [DrugController::class, 'store']);
    Route::put('drugs/{drug}', [DrugController::class, 'update']);
    Route::delete('drugs/{drug}', [DrugController::class, 'destroy']);

    // Reports / Analytics
    Route::prefix('reports')->group(function () {
        Route::get('dashboard', [ReportController::class, 'dashboard']);
        Route::get('corrections', [ReportController::class, 'corrections']);
        Route::get('usage', [ReportController::class, 'usage']);
    });
});
