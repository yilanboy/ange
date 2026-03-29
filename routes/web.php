<?php

use App\Http\Controllers\MessageController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response("Hi! I'm Ange")
        ->header('Content-Type', 'text/plain');
});

Route::post('/webhook', MessageController::class);
