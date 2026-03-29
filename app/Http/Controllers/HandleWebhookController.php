<?php

namespace App\Http\Controllers;

use App\Ai\Agents\Ange;
use App\Http\Requests\WebhookRequest;
use App\Services\TelegramService;
use Laravel\Ai\Enums\Lab;

class HandleWebhookController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(WebhookRequest $request, TelegramService $telegram)
    {
        $text = $request->input('message.text');

        $response = Ange::make()
            ->prompt($text, provider: Lab::Gemini);

        $chatId = $request->input('message.chat.id');

        if ($chatId) {
            $telegram->sendMessage($chatId, (string) $response);
        }

        return response()->json(['message' => (string) $response]);
    }
}
