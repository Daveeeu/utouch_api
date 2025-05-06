<?php

// routes/api.php
use App\Http\Controllers\API\ActivityLogsController;
use App\Http\Controllers\API\Admin\AdminCardTypeController;
use App\Http\Controllers\API\Admin\AdminStatisticsController;
use App\Http\Controllers\API\CardController;
use App\Http\Controllers\API\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use \App\Http\Controllers\API\Admin\AdminCardController;
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

    Route::get('/permissions', [AuthController::class, 'permissions'])->middleware('auth:sanctum');
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
    Route::post('/check-url', [ProfileController::class, 'checkCustomUrl']);
    Route::get('/{id}/seo', [ProfileController::class, 'getSeoSettings'])->name('profiles.seo');
});

// Profil kezelő útvonalak az új ProfileManagerController-hez
Route::middleware('auth:sanctum')->prefix('profiles')->group(function () {
    // Profilok listázása
    Route::get('/', [\App\Http\Controllers\API\ProfileManagerController::class, 'index']);

    Route::post('/', [\App\Http\Controllers\API\ProfileManagerController::class, 'store']);

    Route::post('/{id}', [ProfileController::class, 'update']);

    // Profil kártyához kapcsolása
    Route::post('/{id}/link', [\App\Http\Controllers\API\ProfileManagerController::class, 'linkToCard']);

    // Profil törlése
    Route::delete('/{id}', [\App\Http\Controllers\API\ProfileManagerController::class, 'destroy']);
});

// Admin API útvonalak
Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {
    // Kártyák
    Route::get('cards/users', [AdminCardController::class, 'users']);
    Route::get('cards/card-types', [AdminCardController::class, 'cardTypes']);
    Route::post('cards/{card}/assign', [AdminCardController::class, 'assignToUser']);
    Route::post('cards/{card}/activate', [AdminCardController::class, 'activate']);
    Route::apiResource('cards', AdminCardController::class);

    // Kártyatípusok
    Route::apiResource('card-types', AdminCardTypeController::class);

    // Statisztikák
    Route::get('statistics/summary', [AdminStatisticsController::class, 'summary']);
    Route::get('statistics/cards-over-time', [AdminStatisticsController::class, 'cardsOverTime']);
    Route::get('statistics/profile-visits', [AdminStatisticsController::class, 'profileVisits']);
    Route::get('statistics/card-type-distribution', [AdminStatisticsController::class, 'cardTypeDistribution']);
    Route::get('statistics/user-growth', [AdminStatisticsController::class, 'userGrowth']);
});

Route::post('/activity-logs', [ActivityLogsController::class, 'store'])->middleware('auth:sanctum');
