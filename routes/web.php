<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UploadController;

Route::get('/upload', function () {
    return view('upload');
});

Route::post('/', [HomeController::class, 'list']);

Route::get('/upload', function () {
    return view('upload');
});

Route::post('/upload', [UploadController::class, 'store'])->name('upload');
