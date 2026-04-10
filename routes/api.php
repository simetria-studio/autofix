<?php

use App\Http\Controllers\Api\ErrorController;
use Illuminate\Support\Facades\Route;

Route::get('/errors', [ErrorController::class, 'index']);
Route::post('/errors', [ErrorController::class, 'store']);
