<?php

namespace App\Ai\Agents;

use App\Models\History;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Laravel\Ai\Providers\Tools\WebFetch;
use Laravel\Ai\Providers\Tools\WebSearch;
use Stringable;

class Ange implements Agent, Conversational, HasTools
{
    use Promptable;

    /**
     * Create a new agent instance.
     */
    public function __construct(public ?string $chatId = null)
    {
    }

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return 'You are a helpful assistant that uses Telegram to talk with the user.';
    }

    /**
     * Get the list of messages comprising the conversation so far.
     *
     * @return Message[]
     */
    public function messages(): iterable
    {
        if (! $this->chatId) {
            return [];
        }

        return History::query()
            ->where('chat_id', $this->chatId)
            ->latest()
            ->limit(50)
            ->get()
            ->reverse()
            ->map(fn($history) => new Message($history->role, $history->content))
            ->all();
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [
            new WebSearch,
            new WebFetch,
        ];
    }
}
