<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiDocsController;

Route::get('/', [ApiDocsController::class, 'index']);
