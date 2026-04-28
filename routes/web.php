<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Analysis\Controllers\RunDiffController;

require __DIR__ . '/frontend.php';
require __DIR__ . '/admin.php';
require __DIR__ . '/api.php';

Route::get('/analysis/diffs/{id}', [RunDiffController::class, 'show'])
    ->name('analysis.diffs.show');