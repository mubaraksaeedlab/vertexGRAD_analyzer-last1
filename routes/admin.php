<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Modules\Projects\Controllers\ProjectController;
use App\Modules\Reports\Controllers\ReportController;
 

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('projects')->name('projects.')->group(function () {
        Route::get('/', [ProjectController::class, 'index'])->name('index');
        Route::get('{project}', [ProjectController::class, 'show'])->name('show');
    });
   



    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('{report}', [ReportController::class, 'show'])->name('show');
        Route::get('{report}/api', [ReportController::class, 'api'])->name('api');
        Route::get('{report}/raw', [ReportController::class, 'raw'])->name('raw');
        Route::get('{report}/download', [ReportController::class, 'download'])->name('download');
        Route::get('{report}/download-pdf', [ReportController::class, 'downloadPdf'])->name('download-pdf');
    });
});