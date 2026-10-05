<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FactoryController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\ProductionLineController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\OperatorController;
use App\Http\Controllers\Api\GsdCategoryController;
use App\Http\Controllers\Api\GsdElementController;
use App\Http\Controllers\Api\MtmElementController;
use App\Http\Controllers\Api\SewingFactorController;
use App\Http\Controllers\Api\SewingStopFactorController;
use App\Http\Controllers\Api\ProcessController;
use App\Http\Controllers\Api\ProcessVersionController;
use App\Http\Controllers\Api\PtmsReportController;
use App\Http\Controllers\Api\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/me/password', [AuthController::class, 'changePassword']);

    // Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    // Factories
    Route::apiResource('factories', FactoryController::class);

    // Departments
    Route::apiResource('departments', DepartmentController::class);

    // Production Lines
    Route::apiResource('production-lines', ProductionLineController::class);

    // Articles
    Route::apiResource('articles', ArticleController::class);

    // Operators
    Route::apiResource('operators', OperatorController::class);

    // GSD Categories
    Route::apiResource('gsd-categories', GsdCategoryController::class);

    // GSD Elements
    Route::apiResource('gsd-elements', GsdElementController::class);

    // MTM Elements
    Route::apiResource('mtm-elements', MtmElementController::class);

    // Sewing Factors
    Route::apiResource('sewing-factors', SewingFactorController::class);

    // Sewing Stop Factors
    Route::apiResource('sewing-stop-factors', SewingStopFactorController::class);

    // Processes
    Route::apiResource('processes', ProcessController::class);

    // Process Versions
    Route::apiResource('process-versions', ProcessVersionController::class);

    // PTMS Reports
    Route::apiResource('ptms-reports', PtmsReportController::class);
    Route::put('/ptms-reports/{ptms_report}/status', [PtmsReportController::class, 'updateStatus']);

    // Users (admin/developer only)
    Route::middleware('role:developer,admin')->group(function () {
        Route::apiResource('users', UserController::class);
    });
});
