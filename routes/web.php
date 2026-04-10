<?php

use App\Http\Controllers\ErrorsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/errors', [ErrorsController::class, 'index'])->name('errors.index');
Route::get('/errors/{error}', [ErrorsController::class, 'show'])->name('errors.show');
