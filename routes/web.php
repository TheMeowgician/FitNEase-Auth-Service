<?php

use Illuminate\Support\Facades\Route;

// Health check endpoint for Docker and service monitoring
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'fitnease-auth',
        'timestamp' => now()
    ]);
});

Route::get('/', function () {
    return view('welcome');
});
