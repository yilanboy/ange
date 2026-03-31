<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TelegramService
{
    private const string TELEGRAM_NEW_LINE = "\n";

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
        return $this->request('sendMessage', array_merge([
            'chat_id'    => $chatId,
            'text'       => $text,
            'parse_mode' => 'HTML',
        ], $params));
    }

    /**
     * Edit a message's text.
     */
    public function editMessageText(int|string $chatId, int $messageId, string $text, array $params = []): array
    {
        return $this->request('editMessageText', array_merge([
            'chat_id'    => $chatId,
            'message_id' => $messageId,
            'text'       => $text,
            'parse_mode' => 'HTML',
        ], $params));
    }

    /**
     * Convert markdown text to Telegram-compatible HTML.
     */
    public static function toTelegramHtml(string $markdown): string
    {
        $html = Str::markdown($markdown, ['html_input' => 'strip']);

        // Remove all newlines first
        $html = str_replace(['\r\n', '\r', '\n'], '', $html);

        // Convert headings to bold text with newlines.
        $html = preg_replace(
            '/<h[1-6]>(.*?)<\/h[1-6]>/s',
            self::TELEGRAM_NEW_LINE."<b>$1</b>".self::TELEGRAM_NEW_LINE,
            $html
        );

        // Convert list items to bullet points.
        $html = preg_replace('/<li>(.*?)<\/li>/s', "• $1", $html);

        // Convert paragraphs to text with a newline.
        $html = preg_replace('/<p>(.*?)<\/p>/s', "$1".self::TELEGRAM_NEW_LINE, $html);

        // Add a newline after code blocks
        $html = preg_replace('/<pre>(.*?)<\/pre>/s', "<pre>$1</pre>".self::TELEGRAM_NEW_LINE, $html);

        // Strip remaining unsupported tags, keeping only Telegram-supported ones.
        $html = strip_tags($html, [
            'b', 'strong', 'i', 'em', 'u', 'ins', 's', 'strike', 'del', 'span',
            'a', 'code', 'pre', 'blockquote',
        ]);

        return trim($html);
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
                'body'   => $e->response->body(),
            ]);

            return [];
        }
    }
}
