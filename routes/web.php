<?php

use App\Http\Controllers\DashboardApiController;
use App\Http\Controllers\DashboardViewController;
use App\Http\Controllers\WebhookIngestionController;
use Illuminate\Support\Facades\Route;

// 1. Dashboard View
Route::get('/', [DashboardViewController::class, 'index'])->name('dashboard');

// 2. Webhook Ingestion Route (Any HTTP method, subpaths, dynamic matching)
Route::any('/hook/{slug}/{any?}', [WebhookIngestionController::class, 'ingest'])
    ->where('any', '.*')
    ->name('webhook.ingest');

// 3. Dashboard API Routes
Route::prefix('api')->group(function () {
    // Endpoints
    Route::get('/endpoints', [DashboardApiController::class, 'getEndpoints']);
    Route::post('/endpoints', [DashboardApiController::class, 'createEndpoint']);
    Route::put('/endpoints/{id}', [DashboardApiController::class, 'updateEndpoint']);
    Route::delete('/endpoints/{id}', [DashboardApiController::class, 'deleteEndpoint']);

    // Webhook Requests
    Route::get('/requests', [DashboardApiController::class, 'getRequests']);
    Route::get('/requests/{id}', [DashboardApiController::class, 'getRequest']);
    Route::delete('/requests/{id}', [DashboardApiController::class, 'deleteRequest']);
    Route::post('/requests/clear', [DashboardApiController::class, 'clearRequests']);
    Route::post('/requests/{id}/replay', [DashboardApiController::class, 'replayRequest']);

    // Callback Rules
    Route::get('/callback-rules', [DashboardApiController::class, 'getCallbackRules']);
    Route::post('/callback-rules', [DashboardApiController::class, 'createCallbackRule']);
    Route::put('/callback-rules/{id}', [DashboardApiController::class, 'updateCallbackRule']);
    Route::delete('/callback-rules/{id}', [DashboardApiController::class, 'deleteCallbackRule']);
    Route::post('/callback-rules/{id}/test', [DashboardApiController::class, 'testCallbackRule']);

    // Outbound Webhook Dispatcher
    Route::post('/dispatch', [DashboardApiController::class, 'dispatchOutbound']);
    Route::get('/dispatch/presets', [DashboardApiController::class, 'getDispatcherPresets']);
    Route::get('/dispatch/history', [DashboardApiController::class, 'getDispatchHistory']);

    // Real-Time SSE Stream
    Route::get('/stream', [DashboardApiController::class, 'stream']);
});
