<?php

use App\Http\Controllers\HandleWebhookController;
use App\Http\Middleware\VerifyTelegramSecretToken;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response("Hi! I'm Ange")
        ->header('Content-Type', 'text/plain');
});

Route::post('/webhook', HandleWebhookController::class)
    ->middleware(VerifyTelegramSecretToken::class);
