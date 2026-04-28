<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Frontend\AnalysisStatusController;
use App\Modules\Uploads\Controllers\UploadController;
use App\Modules\Projects\Controllers\ProjectController;
use App\Modules\Reports\Controllers\ReportController;

/*
|--------------------------------------------------------------------------
| Locale Switch
|--------------------------------------------------------------------------
*/
Route::post('/locale/switch', function (Request $request) {
    $request->validate([
        'locale' => 'required|in:en,ar',
    ]);

    session(['locale' => $request->locale]);

    return back();
})->name('locale.switch');

/*
|--------------------------------------------------------------------------
| Frontend Home
|--------------------------------------------------------------------------
*/
Route::view('/', 'frontend.dashboard-page.home')->name('frontend.home');
Route::view('/vertex', 'vertex.home');

/*
|--------------------------------------------------------------------------
| Submit Project
|--------------------------------------------------------------------------
*/
Route::prefix('submit')->name('frontend.submit.')->group(function () {
    Route::get('/', [UploadController::class, 'index'])->name('index');
    Route::post('/', [UploadController::class, 'store'])->name('store');
});

/*
|--------------------------------------------------------------------------
| Projects
|--------------------------------------------------------------------------
*/
Route::prefix('projects')->name('frontend.projects.')->group(function () {
    Route::get('{project}', [ProjectController::class, 'frontendShow'])->name('show');
    Route::get('{project}/preparation-status', [ProjectController::class, 'preparationStatus'])->name('preparation-status');
    Route::post('{project}/run-analysis', [ProjectController::class, 'runAnalysis'])->name('run-analysis');
});

/*
|--------------------------------------------------------------------------
| Reports
|--------------------------------------------------------------------------
*/
Route::prefix('reports')->name('frontend.reports.')->group(function () {
    Route::get('{report}', [ReportController::class, 'frontendShow'])->name('show');
    Route::get('{report}/details', [ReportController::class, 'frontendDetails'])->name('details');
    Route::get('{report}/download', [ReportController::class, 'downloadPdf'])->name('download');
});

/*
|--------------------------------------------------------------------------
| Analysis Runs
|--------------------------------------------------------------------------
*/
Route::prefix('analysis-runs')->name('frontend.analysis-runs.')->group(function () {
    Route::get('{analysisRun}/status', [AnalysisStatusController::class, 'show'])->name('status');
});