<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserPermissionController;
use App\Http\Controllers\Api\WorkItemController;
use App\Http\Controllers\Api\WorkItemFileController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\TeamMemberController;
use App\Http\Controllers\Api\MilestoneController;
use App\Http\Controllers\Api\GovernanceController;
use App\Http\Controllers\Api\GovernanceFileController;
use App\Http\Controllers\Api\GovernanceMilestoneController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\SupplierContractController;
use App\Http\Controllers\Api\SupplierInvoiceController;
use App\Http\Controllers\Api\SupplierFileController;
use App\Http\Controllers\Api\SageCategoryController;
use App\Http\Controllers\Api\SettingListController;
use App\Http\Controllers\Api\SystemSettingController;
use App\Http\Controllers\Api\RiskThemeController;
use App\Http\Controllers\Api\RiskCategoryController;
use App\Http\Controllers\Api\RiskController;
use App\Http\Controllers\Api\ControlLibraryController;
use App\Http\Controllers\Api\RiskControlController;
use App\Http\Controllers\Api\RiskActionController;
use App\Http\Controllers\Api\RiskFileController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\ImportExportController;

/*
|--------------------------------------------------------------------------
| API Routes - Tavira BOW
|--------------------------------------------------------------------------
*/

// ============================================
// Authentication (public)
// ============================================
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    // Protected auth routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

// ============================================
// Protected Routes
// ============================================
Route::middleware('auth:sanctum')->group(function () {

    // ----------------------------------------
    // Users & Permissions
    // ----------------------------------------
    Route::apiResource('users', UserController::class);
    Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword']);
    Route::get('users/{user}/teams', [UserController::class, 'teams']);

    Route::prefix('users/{user}/permissions')->group(function () {
        Route::get('/', [UserPermissionController::class, 'index']);
        Route::post('/', [UserPermissionController::class, 'store']);
        Route::put('/{permission}', [UserPermissionController::class, 'update']);
        Route::delete('/{permission}', [UserPermissionController::class, 'destroy']);
    });

    // ----------------------------------------
    // Work Items (Tasks)
    // ----------------------------------------
    Route::apiResource('workitems', WorkItemController::class);

    Route::prefix('workitems/{workitem}')->group(function () {
        Route::get('/files', [WorkItemFileController::class, 'index']);
        Route::post('/files', [WorkItemFileController::class, 'store']);
        Route::get('/files/{filename}', [WorkItemFileController::class, 'show']);
        Route::delete('/files/{filename}', [WorkItemFileController::class, 'destroy']);

        Route::post('/assign/{user}', [WorkItemController::class, 'assign']);
        Route::delete('/assign/{user}', [WorkItemController::class, 'unassign']);

        Route::get('/milestones', [MilestoneController::class, 'forWorkItem']);
    });

    // ----------------------------------------
    // Teams
    // ----------------------------------------
    Route::apiResource('teams', TeamController::class);

    Route::prefix('teams/{team}/members')->group(function () {
        Route::get('/', [TeamMemberController::class, 'index']);
        Route::post('/', [TeamMemberController::class, 'store']);
        Route::delete('/{member}', [TeamMemberController::class, 'destroy']);
    });

    // ----------------------------------------
    // Milestones
    // ----------------------------------------
    Route::apiResource('milestones', MilestoneController::class)->except(['index']);
    Route::get('milestones', [MilestoneController::class, 'index']);

    // ----------------------------------------
    // Governance
    // ----------------------------------------
    Route::prefix('governance')->group(function () {
        // Dashboard stats (must be before resource routes)
        Route::get('/dashboard/stats', [GovernanceController::class, 'dashboard']);

        Route::apiResource('items', GovernanceController::class);

        Route::prefix('items/{item}')->group(function () {
            Route::get('/files', [GovernanceFileController::class, 'index']);
            Route::post('/files', [GovernanceFileController::class, 'store']);
            Route::get('/files/{filename}', [GovernanceFileController::class, 'show']);
            Route::delete('/files/{filename}', [GovernanceFileController::class, 'destroy']);

            Route::post('/milestones', [GovernanceMilestoneController::class, 'store']);
        });

        Route::apiResource('milestones', GovernanceMilestoneController::class)->except(['store']);
    });

    // ----------------------------------------
    // Suppliers
    // ----------------------------------------
    Route::apiResource('suppliers', SupplierController::class);
    Route::get('suppliers-dashboard', [SupplierController::class, 'dashboard']);

    Route::prefix('suppliers/{supplier}')->group(function () {
        // Contracts
        Route::get('/contracts', [SupplierContractController::class, 'index']);
        Route::post('/contracts', [SupplierContractController::class, 'store']);
        Route::put('/contracts/{contract}', [SupplierContractController::class, 'update']);
        Route::delete('/contracts/{contract}', [SupplierContractController::class, 'destroy']);

        // Invoices
        Route::get('/invoices', [SupplierInvoiceController::class, 'index']);
        Route::post('/invoices', [SupplierInvoiceController::class, 'store']);
        Route::post('/invoices/bulk', [SupplierInvoiceController::class, 'bulkStore']);
        Route::put('/invoices/{invoice}', [SupplierInvoiceController::class, 'update']);
        Route::delete('/invoices/{invoice}', [SupplierInvoiceController::class, 'destroy']);

        // Files
        Route::get('/files', [SupplierFileController::class, 'index']);
        Route::post('/files', [SupplierFileController::class, 'store']);
        Route::delete('/files/{file}', [SupplierFileController::class, 'destroy']);
    });

    // Sage Categories
    Route::apiResource('sage-categories', SageCategoryController::class);

    // ----------------------------------------
    // Settings
    // ----------------------------------------
    Route::prefix('settings')->group(function () {
        Route::get('/lists', [SettingListController::class, 'index']);
        Route::post('/lists', [SettingListController::class, 'store']);
        Route::post('/lists/bulk', [SettingListController::class, 'bulkStore']);
        Route::put('/lists/{list}', [SettingListController::class, 'update']);
        Route::delete('/lists/{list}', [SettingListController::class, 'destroy']);
        Route::get('/lists/type/{type}', [SettingListController::class, 'byType']);

        Route::get('/system', [SystemSettingController::class, 'index']);
        Route::put('/system/{key}', [SystemSettingController::class, 'update']);
    });

    // ----------------------------------------
    // Risk Management
    // ----------------------------------------
    Route::prefix('risks')->group(function () {
        // Themes (L1)
        Route::get('/themes', [RiskThemeController::class, 'index']);
        Route::post('/themes', [RiskThemeController::class, 'store']);
        Route::put('/themes/{theme}', [RiskThemeController::class, 'update']);
        Route::get('/themes/{theme}/permissions', [RiskThemeController::class, 'permissions']);
        Route::post('/themes/{theme}/permissions', [RiskThemeController::class, 'storePermission']);
        Route::put('/themes/{theme}/permissions/{permission}', [RiskThemeController::class, 'updatePermission']);
        Route::delete('/themes/{theme}/permissions/{permission}', [RiskThemeController::class, 'destroyPermission']);

        // Categories (L2)
        Route::get('/categories', [RiskCategoryController::class, 'index']);
        Route::post('/categories', [RiskCategoryController::class, 'store']);
        Route::put('/categories/{category}', [RiskCategoryController::class, 'update']);
        Route::delete('/categories/{category}', [RiskCategoryController::class, 'destroy']);

        // Control Library
        Route::get('/controls/library', [ControlLibraryController::class, 'index']);
        Route::post('/controls/library', [ControlLibraryController::class, 'store']);
        Route::put('/controls/library/{control}', [ControlLibraryController::class, 'update']);
        Route::delete('/controls/library/{control}', [ControlLibraryController::class, 'destroy']);

        // Dashboard & Heatmap
        Route::get('/dashboard', [RiskController::class, 'dashboard']);
        Route::get('/heatmap', [RiskController::class, 'heatmap']);
        Route::get('/alerts', [RiskController::class, 'alerts']);
    });

    // Risks (L3)
    Route::apiResource('risks', RiskController::class);

    Route::prefix('risks/{risk}')->group(function () {
        // Controls
        Route::get('/controls', [RiskControlController::class, 'index']);
        Route::post('/controls', [RiskControlController::class, 'store']);
        Route::put('/controls/{riskControl}', [RiskControlController::class, 'update']);
        Route::delete('/controls/{riskControl}', [RiskControlController::class, 'destroy']);

        // Actions
        Route::get('/actions', [RiskActionController::class, 'index']);
        Route::post('/actions', [RiskActionController::class, 'store']);
        Route::put('/actions/{action}', [RiskActionController::class, 'update']);
        Route::delete('/actions/{action}', [RiskActionController::class, 'destroy']);

        // Files
        Route::get('/files', [RiskFileController::class, 'index']);
        Route::post('/files', [RiskFileController::class, 'store']);
        Route::get('/files/{filename}', [RiskFileController::class, 'show']);
        Route::delete('/files/{filename}', [RiskFileController::class, 'destroy']);
    });

    // ----------------------------------------
    // Tasks Dashboard
    // ----------------------------------------
    Route::get('/tasks/dashboard/stats', [DashboardController::class, 'tasksDashboard']);

    // ----------------------------------------
    // Dashboard
    // ----------------------------------------
    Route::prefix('dashboard')->group(function () {
        Route::get('/stats', [DashboardController::class, 'stats']);
        Route::get('/by-area', [DashboardController::class, 'byArea']);
        Route::get('/by-activity', [DashboardController::class, 'byActivity']);
        Route::get('/by-rag', [DashboardController::class, 'byRag']);
        Route::get('/alerts', [DashboardController::class, 'alerts']);
        Route::get('/upcoming', [DashboardController::class, 'upcoming']);
        Route::get('/calendar', [DashboardController::class, 'calendar']);
    });

    // ----------------------------------------
    // Search
    // ----------------------------------------
    Route::get('/search', [SearchController::class, 'search']);
    Route::get('/tags', [SearchController::class, 'tags']);
    Route::get('/departments', [SearchController::class, 'departments']);
    Route::get('/activities', [SearchController::class, 'activities']);

    // ----------------------------------------
    // Import / Export
    // ----------------------------------------
    Route::prefix('import')->group(function () {
        Route::post('/preview', [ImportExportController::class, 'preview']);
        Route::post('/confirm', [ImportExportController::class, 'confirm']);
        Route::get('/templates/{type}', [ImportExportController::class, 'template']);
        Route::get('/status/{jobId}', [ImportExportController::class, 'status']);
    });

    Route::prefix('export')->group(function () {
        Route::get('/workitems', [ImportExportController::class, 'exportWorkItems']);
        Route::get('/governance', [ImportExportController::class, 'exportGovernance']);
        Route::get('/suppliers', [ImportExportController::class, 'exportSuppliers']);
        Route::get('/risks', [ImportExportController::class, 'exportRisks']);
    });

    // ----------------------------------------
    // Notifications (Admin)
    // ----------------------------------------
    Route::post('/notifications/send-reminders', [DashboardController::class, 'sendReminders'])
        ->middleware('ability:admin');
});
