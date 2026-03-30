<?php

use App\Ai\Agents\Ange;
use App\Models\History;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Messages\Message;

uses(RefreshDatabase::class);

it('can retrieve messages from the history model using chat_id', function () {
    $chatId = '123456789';

    History::create([
        'chat_id' => $chatId,
        'role' => 'user',
        'content' => 'Hello, I need help.',
    ]);

    History::create([
        'chat_id' => $chatId,
        'role' => 'assistant',
        'content' => 'Sure, how can I assist you?',
    ]);

    $agent = new Ange($chatId);
    $messages = $agent->messages();

    expect($messages)->toBeArray()
        ->toHaveCount(2)
        ->and($messages[0])->toBeInstanceOf(Message::class)
        ->and($messages[0]->role->value)->toBe('user')
        ->and($messages[0]->content)->toBe('Hello, I need help.')
        ->and($messages[1]->role->value)->toBe('assistant')
        ->and($messages[1]->content)->toBe('Sure, how can I assist you?');
});

it('returns empty array when chat has no history', function () {
    $agent = new Ange('non-existent-chat-id');

    expect($agent->messages())->toBeArray()->toBeEmpty();
});

it('returns empty array when no chat_id is set', function () {
    $agent = new Ange;

    expect($agent->messages())->toBeArray()->toBeEmpty();
});
