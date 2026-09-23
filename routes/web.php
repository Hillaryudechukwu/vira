<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json([
    'name' => config('app.name'),
    'message' => 'VIRA API is running.',
    'docs' => '/api/v1/health',
]));
