<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Example API endpoints
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// Test endpoint for generating trace data
Route::get('/test-traces', function () {
    // Generate a log entry for Loki
    Log::info('Test trace endpoint accessed', ['timestamp' => now()->toIso8601String()]);

    // Simulate some processing time
    usleep(rand(100000, 500000));

    // Simulate a database query
    $dbQueryTime = rand(50, 200);
    usleep($dbQueryTime * 1000);

    // Simulate API call
    $apiCallTime = rand(200, 600);
    usleep($apiCallTime * 1000);

    return response()->json([
        'message' => 'Test trace generated',
        'timestamp' => now()->toIso8601String(),
        'simulated_times' => [
            'processing' => rand(100, 500),
            'database_query' => $dbQueryTime,
            'api_call' => $apiCallTime
        ]
    ]);
});
