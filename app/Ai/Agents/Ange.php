<?php

namespace App\Ai\Agents;

use App\Models\History;
use Illuminate\Support\Facades\Cache;
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
use Laravel\Mcp\Client\Primitives\Tool as McpToolPrimitive;
use Laravel\Mcp\Facades\Mcp;
use Stringable;

#[Provider(Lab::Gemini)]
#[Model('gemini-3.8-flash')]
#[MaxSteps(10)]
#[Temperature(0.7)]
class Ange implements Agent, Conversational, HasTools
{
    use Promptable;

    /**
     * Create a new agent instance.
     */
    public function __construct(
        public ?string $chatId = null,
        public ?string $senderName = null,
    ) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        $instructions = <<<'EOD'
        You are Ange, a chat assistant talking to the user on Telegram.
        Be concise. Match your length to the question — most answers are one to three sentences, and a single sentence is often enough.
        Skip filler openings like "Sure!" or "Great question!", and don't end with a summary of what you just said.
        Answer exactly what was asked. Don't volunteer extra detail, background, alternatives, caveats, or follow-up questions the user didn't request — if they want more, they'll ask.
        Only ask a clarifying question when the request is genuinely ambiguous.
        Respond in Markdown. Use Markdown formatting (like bold, italic, inline code, code blocks, and lists) where appropriate to make your response clear and well-structured.
        EOD;

        if ($this->senderName) {
            $instructions .= <<<EOD

            You are in a group chat. The current message is from "$this->senderName".
            In conversation history, user messages are prefixed with "[Name]: " to indicate who sent them.
            Address users by their name when appropriate.
            EOD;
        }

        return $instructions;
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
            ->map(fn ($history) => new Message($history->role, $history->content))
            ->all();
    }

    /**
     * Get the tools available to the agent.
     *
     * @return list<Tool|McpToolPrimitive>
     */
    public function tools(): array
    {
        $tools = [
            new WebSearch,
            new WebFetch,
        ];

        if (config('services.github.token')) {
            $client = Mcp::client('github');

            $cacheKey = 'mcp:github:tools:json:'.md5((string) config('services.github.token'));

            $json = Cache::remember($cacheKey, now()->addDay(), function () use ($client) {
                $payloads = $client->tools()->map(fn (McpToolPrimitive $tool) => [
                    'name' => $tool->name,
                    'title' => $tool->title,
                    'description' => $tool->description,
                    'inputSchema' => $tool->inputSchema,
                    'outputSchema' => $tool->outputSchema,
                    'annotations' => $tool->annotations,
                    '_meta' => $tool->meta,
                ])->values()->all();

                return json_encode($payloads, JSON_THROW_ON_ERROR);
            });

            /** @var list<array<string, mixed>> $toolPayloads */
            $toolPayloads = json_decode((string) $json, true, 512, JSON_THROW_ON_ERROR);

            $mcpTools = array_map(
                fn (array $payload) => McpToolPrimitive::from($client, $payload),
                $toolPayloads
            );

            $tools = [...$tools, ...$mcpTools];
        }

        return $tools;
    }
}
