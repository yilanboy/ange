<?php

namespace App\Http\Controllers;

use App\Ai\Agents\Ange;
use App\Http\Requests\WebhookRequest;
use App\Models\History;
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
        $chatId = $request->input('message.chat.id');

        if (! $chatId) {
            return response()->json(['message' => 'No chat ID found']);
        }

        $response = Ange::make($chatId)
            ->prompt($text, provider: Lab::Gemini);

        History::create([
            'chat_id' => $chatId,
            'role' => 'user',
            'content' => $text,
        ]);

        $telegram->sendMessage($chatId, (string) $response);

        History::create([
            'chat_id' => $chatId,
            'role' => 'assistant',
            'content' => (string) $response,
        ]);

        return response()->json(['message' => (string) $response]);
    }
}
