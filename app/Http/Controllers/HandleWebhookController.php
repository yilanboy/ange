<?php

namespace App\Http\Controllers;

use App\Ai\Agents\Ange;
use App\Http\Requests\WebhookRequest;
use App\Models\History;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Enums\Lab;

class HandleWebhookController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(WebhookRequest $request)
    {
        $text = $request->input('message.text');
        $chatId = $request->input('message.chat.id');

        if (! $chatId) {
            return response()->json(['message' => 'No chat ID found']);
        }

        dispatch(function () use ($chatId, $text) {
            $telegram = app()->make(TelegramService::class);

            $placeholder = $telegram->sendMessage($chatId, "I'm thinking... ⏳");

            Log::info('Check placeholder: ', $placeholder);

            $messageId = $placeholder['result']['message_id'] ?? null;

            $response = Ange::make($chatId)
                ->prompt($text, provider: Lab::Gemini);

            History::create([
                'chat_id' => $chatId,
                'role'    => 'user',
                'content' => $text,
            ]);

            if ($messageId) {
                $telegram->editMessageText($chatId, $messageId, (string) $response);
            } else {
                $telegram->sendMessage($chatId, (string) $response);
            }

            History::create([
                'chat_id' => $chatId,
                'role'    => 'assistant',
                'content' => (string) $response,
            ]);
        })->afterResponse();

        return response()->json(['message' => 'ok']);
    }
}
