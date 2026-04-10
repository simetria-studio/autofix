<?php

use App\Http\Controllers\Api\ErrorController;
use Illuminate\Support\Facades\Route;

Route::post('/errors', [ErrorController::class, 'store']);
