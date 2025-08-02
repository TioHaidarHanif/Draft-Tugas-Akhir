<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiDocumentationController;


Route::get('/api/doc', [ApiDocumentationController::class, 'index']);

// Fallback hanya untuk SPA route, bukan asset
Route::get('/{any}', function () {
    return file_get_contents(public_path('index.html'));
})->where('any', '^(?!assets|favicon\\.ico|robots\\.txt|vite\\.svg).*$');

// Route::get('/', function () {
//     return view('welcome');
// });

