<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\IncomeController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CurrencyController;
use App\Http\Controllers\Api\BalanceController;
use App\Http\Controllers\Api\DebtController;
use App\Http\Controllers\LendingController;
use App\Http\Controllers\TargetController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\ShareController;
use App\Http\Controllers\Api\FriendController;
use App\Http\Controllers\Api\CommonExpenseController;

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

// Public Share View Route (no authentication required)
Route::get('/shared/{token}', [ShareController::class, 'viewShare']);

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

    // Common Expense (Template) Management Routes
    Route::post('common-expenses/{common_expense}/apply', [CommonExpenseController::class, 'apply']);
    Route::apiResource('common-expenses', CommonExpenseController::class);

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

    // Debt Management Routes
    Route::prefix('debts')->group(function () {
        Route::get('/statistics', [DebtController::class, 'statistics']);
        Route::get('/{debt}/payments', [DebtController::class, 'payments']);
        Route::post('/{debt}/payments', [DebtController::class, 'recordPayment']);
    });
    Route::apiResource('debts', DebtController::class);

    // Lending Management Routes (Money lent to others)
    Route::prefix('lendings')->group(function () {
        Route::get('/{lending}/payments', [LendingController::class, 'getPayments']);
        Route::post('/{lending}/payments', [LendingController::class, 'recordPayment']);
        Route::delete('/{lending}/payments/{payment}', [LendingController::class, 'deletePayment']);
        Route::post('/{lending}/forgive', [LendingController::class, 'forgive']);
    });
    Route::apiResource('lendings', LendingController::class);

    // Targets Management Routes (Wishlist)
    Route::post('targets/{target}/purchase', [TargetController::class, 'purchase']);
    Route::apiResource('targets', TargetController::class);

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

    // Share Management Routes
    Route::prefix('shares')->group(function () {
        Route::post('/', [ShareController::class, 'createShare']);
        Route::get('/', [ShareController::class, 'getUserShares']);
        Route::get('/received', [ShareController::class, 'getReceivedShares']);
        Route::post('/{id}/revoke', [ShareController::class, 'revokeShare']);
        Route::post('/{id}/resend-email', [ShareController::class, 'resendEmail']);
        Route::delete('/{id}', [ShareController::class, 'deleteShare']);
    });

    // Friends Routes
    Route::prefix('friends')->group(function () {
        Route::get('/', [FriendController::class, 'index']);
        Route::get('/search', [FriendController::class, 'search']);
        Route::post('/request', [FriendController::class, 'sendRequest']);
        Route::post('/requests/{id}/accept', [FriendController::class, 'accept']);
        Route::post('/requests/{id}/reject', [FriendController::class, 'reject']);
        Route::post('/requests/{id}/cancel', [FriendController::class, 'cancel']);
        Route::delete('/{id}', [FriendController::class, 'remove']);
    });
});
