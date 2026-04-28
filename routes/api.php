
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\IntegrationProjectController;
use App\Modules\Reports\Controllers\ReportController;

Route::post('/integrations/projects', [IntegrationProjectController::class, 'store']);
Route::post('reports/{report}/ask-ai', [ReportController::class, 'askAi'])->name('api.reports.ask-ai');
use App\Modules\Integrations\Controllers\GitHubWebhookController;

Route::post("/webhooks/github", [GitHubWebhookController::class, "handle"])
    ->name("webhooks.github");