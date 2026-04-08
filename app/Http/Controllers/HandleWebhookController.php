<?php

namespace App\Http\Controllers;

use App\Http\Requests\WebhookRequest;
use App\Jobs\ProcessTelegramWebhookJob;

class HandleWebhookController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(WebhookRequest $request)
    {
        $text = $request->input('message.text');
        $chatId = $request->input('message.chat.id');

        if (! $chatId || ! $text) {
            return response()->json(['message' => 'No chat ID or text found']);
        }

        $chatType = $request->input('message.chat.type', 'private');
        $replyToMessageId = null;
        $senderName = $this->extractSenderName($request);

        if (in_array($chatType, ['group', 'supergroup'])) {
            $botUsername = config('services.telegram.bot_username');

            if (! str_starts_with($text, "@{$botUsername}")) {
                return response()->json(['message' => 'ok']);
            }

            $text = trim(str_replace("@{$botUsername}", '', $text));

            if ($text === '') {
                return response()->json(['message' => 'ok']);
            }

            $replyToMessageId = $request->integer('message.message_id');
        }

        ProcessTelegramWebhookJob::dispatch($chatId, $text, $replyToMessageId, $senderName);

        return response()->json(['message' => 'ok']);
    }

    /**
     * Extract the sender's display name from the Telegram message.
     */
    private function extractSenderName(WebhookRequest $request): ?string
    {
        $firstName = $request->input('message.from.first_name');

        if (! $firstName) {
            return null;
        }

        $lastName = $request->input('message.from.last_name');

        return $lastName ? "{$firstName} {$lastName}" : $firstName;
    }
}
