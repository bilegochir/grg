<?php

use App\Http\Controllers\AnalyticsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('analytics')->group(function () {
        Route::get('cases', [AnalyticsController::class, 'getCaseAnalytics']);
        Route::get('finance', [AnalyticsController::class, 'getFinanceAnalytics']);
        Route::get('staff', [AnalyticsController::class, 'getStaffAnalytics']);
        Route::get('leads', [AnalyticsController::class, 'getLeadAnalytics']);

        Route::post('build-report', [AnalyticsController::class, 'buildReport']);
        Route::post('export-report', [AnalyticsController::class, 'exportReport']);

        Route::prefix('reports')->group(function () {
            Route::get('/', [AnalyticsController::class, 'listCustomReports']);
            Route::post('/', [AnalyticsController::class, 'storeCustomReport']);
            Route::patch('{report}', [AnalyticsController::class, 'updateCustomReport']);
            Route::delete('{report}', [AnalyticsController::class, 'deleteCustomReport']);
            Route::get('{report}/run', [AnalyticsController::class, 'runCustomReport']);
        });
    });
});
