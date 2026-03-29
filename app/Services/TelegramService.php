<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TelegramService
{
    /**
     * The Telegram Bot API token.
     */
    protected string $token;

    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        $this->token = config('services.telegram.bot_token');
    }

    /**
     * Send a message to a chat.
     */
    public function sendMessage(int|string $chatId, string $text, array $params = []): array
    {
        return Http::post("https://api.telegram.org/bot{$this->token}/sendMessage", array_merge([
            'chat_id' => $chatId,
            'text' => $text,
        ], $params))->json();
    }

    /**
     * Edit a message's text.
     */
    public function editMessageText(int|string $chatId, int $messageId, string $text, array $params = []): array
    {
        return Http::post("https://api.telegram.org/bot{$this->token}/editMessageText", array_merge([
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
        ], $params))->json();
    }
}
