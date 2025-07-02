<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiDocumentationController;

// API Documentation Landing Page
Route::get('/', [ApiDocumentationController::class, 'index']);
