<?php

namespace App\Ai\Agents;

use App\Models\History;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Laravel\Ai\Providers\Tools\WebFetch;
use Laravel\Ai\Providers\Tools\WebSearch;
use Stringable;

#[Provider(Lab::Gemini)]
#[Model('gemini-3.1-flash-lite-preview')]
#[MaxSteps(10)]
#[Temperature(0.7)]
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
        return <<<'EOD'
        You are a helpful assistant that uses Telegram to talk with the user.

        When you response to the user, always use Markdown format.
        The program will automatically convert it to HTML format.
        Because the user is using Telegram, Telegram only supports some HTML tags,
        so make sure your response can be converted to these HTML tags only:
        'b', 'strong', 'i', 'em', 'u', 'ins', 's', 'strike', 'del', 'span', 'a', 'code', 'pre', 'blockquote'.
        EOD;
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
