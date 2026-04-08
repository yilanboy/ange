<?php

namespace App\Jobs;

use App\Ai\Agents\Ange;
use App\Models\History;
use App\Services\TelegramService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessTelegramWebhookJob implements ShouldQueue
{
    use Queueable;

    public const array THINKING_MESSAGES = [
        'Wait a moment... ⏳',
        'Thinking... 🤔',
        'Thinking about it... 😚',
        "I'm cooking... 🧑‍🍳",
    ];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string|int $chatId,
        public string $text,
        public ?int $replyToMessageId = null,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(TelegramService $telegram): void
    {
        $thinkingMessageKey = array_rand(self::THINKING_MESSAGES);
        $thinkingMessage = self::THINKING_MESSAGES[$thinkingMessageKey];

        $replyParams = $this->replyToMessageId
            ? ['reply_parameters' => ['message_id' => $this->replyToMessageId]]
            : [];

        $placeholder = $telegram->sendMessage(
            $this->chatId,
            TelegramService::toTelegramHtml($thinkingMessage),
            $replyParams,
        );

        $messageId = $placeholder['result']['message_id'] ?? null;

        try {
            $response = Ange::make($this->chatId)
                ->prompt($this->text);
        } catch (Exception $exception) {
            Log::error('AI model went wrong: ', [get_class($exception), $exception->getMessage()]);
            $response = "I'm sorry, I couldn't process your request at the moment.";
        }

        History::create([
            'chat_id' => $this->chatId,
            'role'    => 'user',
            'content' => $this->text,
        ]);

        $htmlResponse = TelegramService::toTelegramHtml((string) $response);

        if ($messageId) {
            $telegram->editMessageText($this->chatId, $messageId, $htmlResponse);
        } else {
            $telegram->sendMessage($this->chatId, $htmlResponse, $replyParams);
        }

        History::create([
            'chat_id' => $this->chatId,
            'role'    => 'assistant',
            'content' => (string) $response,
        ]);
    }
}
