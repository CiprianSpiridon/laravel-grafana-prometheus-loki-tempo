<?php

use Illuminate\Support\Facades\Route;
use Spatie\Prometheus\Facades\Prometheus;
use Spatie\Prometheus\Http\Controllers\PrometheusMetricsController;

Route::get('/', function () {
    return view('welcome');
});

// Metrics endpoint for Prometheus using Spatie Laravel Prometheus package
Route::get('/metrics', PrometheusMetricsController::class);
