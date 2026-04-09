<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessTelegramWebhookJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class HandleWebhookController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        try {
            return $this->process($request);
        } catch (Throwable $e) {
            Log::error('Webhook error: ', [get_class($e), $e->getMessage()]);

            return response()->json(['message' => 'ok']);
        }
    }

    private function process(Request $request)
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

            $text = $this->extractGroupMessageText($text, $botUsername);

            if ($text === null) {
                return response()->json(['message' => 'ok']);
            }

            $replyToMessageId = $request->integer('message.message_id');
        }

        ProcessTelegramWebhookJob::dispatch($chatId, $text, $replyToMessageId, $senderName);

        return response()->json(['message' => 'ok']);
    }

    /**
     * Extract the question text from a /botname command in a group message.
     *
     * Returns null if the message should be ignored.
     */
    private function extractGroupMessageText(string $text, string $botUsername): ?string
    {
        if (preg_match('/^\/'.preg_quote($botUsername, '/').'(?:@'.preg_quote($botUsername, '/').')?\s+(.*)/s', $text, $matches)) {
            $text = trim($matches[1]);

            return $text !== '' ? $text : null;
        }

        return null;
    }

    /**
     * Extract the sender's display name from the Telegram message.
     */
    private function extractSenderName(Request $request): ?string
    {
        $firstName = $request->input('message.from.first_name');

        if (! $firstName) {
            return null;
        }

        $lastName = $request->input('message.from.last_name');

        return $lastName ? "{$firstName} {$lastName}" : $firstName;
    }
}
