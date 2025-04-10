<?php

// routes/api.php
use App\Http\Controllers\API\CardController;
use App\Http\Controllers\API\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});

// Védett útvonalak példája
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (\Illuminate\Http\Request $request) {
        return $request->user();
    });
});

Route::middleware('auth:sanctum')->prefix('cards')->group(function () {
    Route::get('/', [CardController::class, 'index']);
    Route::post('/activate', [CardController::class, 'activate']);
    Route::get('/{id}', [CardController::class, 'show']);
    Route::delete('/{id}', [CardController::class, 'destroy']);
});

Route::prefix('profiles')->group(function () {
    Route::get('/{id}', [ProfileController::class, 'show']);
    Route::post('/{id}', [ProfileController::class, 'update']);
    Route::post('/check-url', [ProfileController::class, 'checkCustomUrl']);
});

// Profil kezelő útvonalak az új ProfileManagerController-hez
Route::middleware('auth:sanctum')->prefix('profiles')->group(function () {
    // Profilok listázása
    Route::get('/', [\App\Http\Controllers\API\ProfileManagerController::class, 'index']);

    // Új profil létrehozása
    Route::post('/', [\App\Http\Controllers\API\ProfileManagerController::class, 'store']);

    // Profil kártyához kapcsolása
    Route::post('/{id}/link', [\App\Http\Controllers\API\ProfileManagerController::class, 'linkToCard']);

    // Profil törlése
    Route::delete('/{id}', [\App\Http\Controllers\API\ProfileManagerController::class, 'destroy']);
});
