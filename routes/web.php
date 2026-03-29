<?php

use App\Http\Controllers\MessageController;
use Illuminate\Support\Facades\Route;

Route::post('/webhook', MessageController::class);
