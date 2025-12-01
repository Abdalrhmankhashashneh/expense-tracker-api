<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\IncomeController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CurrencyController;
use App\Http\Controllers\Api\BalanceController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\SettingsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
| These routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

// Authentication Routes (Public)
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // Protected auth routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
    });
});

// Protected Routes (Require Authentication)
Route::middleware('auth:sanctum')->group(function () {

    // Income Management Routes
    Route::prefix('income')->group(function () {
        Route::get('/current', [IncomeController::class, 'current'])->middleware('permission:view income');
        Route::get('/history', [IncomeController::class, 'index'])->middleware('permission:view income');
    });
    Route::apiResource('income', IncomeController::class)->except(['index']);

    // Expense Management Routes
    Route::prefix('expenses')->group(function () {
        Route::get('/summary', [ExpenseController::class, 'summary'])->middleware('permission:view expenses');
    });
    Route::apiResource('expenses', ExpenseController::class);

    // Category Management Routes
    Route::apiResource('categories', CategoryController::class);

    // Currency Management Routes
    Route::prefix('currencies')->group(function () {
        Route::get('/', [CurrencyController::class, 'index']);
        Route::get('/default', [CurrencyController::class, 'default']);
        Route::get('/active', [CurrencyController::class, 'active']);
        Route::put('/set', [CurrencyController::class, 'set']);
        Route::get('/{currency}', [CurrencyController::class, 'show']);
    });

    // Balance Management Routes
    Route::prefix('balance')->group(function () {
        Route::get('/', [BalanceController::class, 'index']);
        Route::post('/add', [BalanceController::class, 'addMoney']);
        Route::get('/transactions', [BalanceController::class, 'transactions']);
        Route::get('/sources', [BalanceController::class, 'sources']);
    });

    // Dashboard Routes
    Route::prefix('dashboard')->middleware('permission:view dashboard')->group(function () {
        Route::get('/', [DashboardController::class, 'overview']);
        Route::get('/trends', [DashboardController::class, 'trends']);
        Route::get('/category-breakdown', [DashboardController::class, 'categoryBreakdown']);
    });

    // Export Routes
    Route::prefix('export')->middleware('permission:export data')->group(function () {
        Route::get('/csv', [ExportController::class, 'csv']);
        Route::get('/pdf', [ExportController::class, 'pdf']);
        Route::get('/excel', [ExportController::class, 'excel']);
        Route::get('/history', [ExportController::class, 'history'])->middleware('permission:view export history');
    });

    // Settings Routes
    Route::prefix('settings')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->middleware('permission:view settings');
        Route::put('/profile', [SettingsController::class, 'updateProfile'])->middleware('permission:update settings');
        Route::put('/password', [SettingsController::class, 'changePassword']);
    });
});
