<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
        ];

        return $this->request('sendMessage', array_merge($payload, $params));
    }

    /**
     * Edit a message's text.
     */
    public function editMessage(int|string $chatId, int $messageId, string $text, array $params = []): array
    {
        $payload = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
        ];

        return $this->request('editMessageText', array_merge($payload, $params));
    }

    /**
     * Make a request to the Telegram Bot API.
     */
    private function request(string $method, array $params): array
    {
        try {
            return Http::timeout(10)
                ->connectTimeout(5)
                ->retry(3, 100)
                ->post("https://api.telegram.org/bot{$this->token}/{$method}", $params)
                ->throw()
                ->json();
        } catch (ConnectionException $e) {
            Log::error("Telegram API connection failed [{$method}]", [
                'exception' => $e->getMessage(),
            ]);

            return [];
        } catch (RequestException $e) {
            Log::error("Telegram API request failed [{$method}]", [
                'status' => $e->response->status(),
                'body' => $e->response->body(),
            ]);

            return [];
        }
    }
}
