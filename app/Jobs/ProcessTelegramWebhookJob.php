<?php

namespace App\Jobs;

use App\Ai\Agents\Ange;
use App\Models\History;
use App\Services\TelegramService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Enums\Lab;

class ProcessTelegramWebhookJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string|int $chatId,
        public string $text,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(TelegramService $telegram): void
    {
        $placeholder = $telegram->sendMessage($this->chatId, "I'm thinking... ⏳");

        Log::info('Check placeholder: ', $placeholder);

        $messageId = $placeholder['result']['message_id'] ?? null;

        $response = Ange::make($this->chatId)
            ->prompt($this->text, provider: Lab::Gemini);

        History::create([
            'chat_id' => $this->chatId,
            'role' => 'user',
            'content' => $this->text,
        ]);

        if ($messageId) {
            $telegram->editMessageText($this->chatId, $messageId, (string) $response);
        } else {
            $telegram->sendMessage($this->chatId, (string) $response);
        }

        History::create([
            'chat_id' => $this->chatId,
            'role' => 'assistant',
            'content' => (string) $response,
        ]);
    }
}
