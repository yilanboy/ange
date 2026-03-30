<?php

namespace App\Http\Controllers;

use App\Http\Requests\WebhookRequest;
use App\Jobs\ProcessTelegramWebhookJob;
use Illuminate\Support\Facades\Log;

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

        ProcessTelegramWebhookJob::dispatch($chatId, $text);

        Log::info('Send response first');

        return response()->json(['message' => 'ok']);
    }
}
