<?php

namespace App\Http\Controllers;

use App\Ai\Agents\Ange;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Enums\Lab;

class MessageController extends Controller
{
    public function __invoke(Request $request)
    {
        $secretToken = $request->header('X-Telegram-Bot-Api-Secret-Token');

        if ($secretToken !== config('services.telegram.bot_secret_token')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        Log::info('Message received', $request->all());

        $text = $request->input('message.text') ?? $request->input('callback_query.data');

        if (! $text) {
            return response()->json(['message' => 'No message found'], 200);
        }

        $response = Ange::make()
            ->prompt($text, provider: Lab::Gemini);

        Log::info((string) $response);

        $chatId = $request->input('message.chat.id') ?? $request->input('callback_query.message.chat.id') ?? config('services.telegram.chat_id');

        if ($chatId) {
            Http::post('https://api.telegram.org/bot'.config('services.telegram.bot_token').'/sendMessage', [
                'chat_id' => $chatId,
                'text' => (string) $response,
            ]);
        }

        return response()->json(['message' => (string) $response]);
    }
}
