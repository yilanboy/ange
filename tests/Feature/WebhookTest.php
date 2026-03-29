<?php

use App\Ai\Agents\Ange;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    Config::set('services.telegram.bot_secret_token', 'test-secret-token');
});

test('it returns 401 if secret token is missing', function () {
    $response = $this->postJson('/webhook', [
        'message' => [
            'text' => 'Hello',
            'chat' => ['id' => 123],
        ],
    ]);

    $response->assertUnauthorized();
    $response->assertJson(['message' => 'Unauthorized']);
});

test('it returns 401 if secret token is invalid', function () {
    $response = $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'wrong-token')
        ->postJson('/webhook', [
            'message' => [
                'text' => 'Hello',
                'chat' => ['id' => 123],
            ],
        ]);

    $response->assertUnauthorized();
    $response->assertJson(['message' => 'Unauthorized']);
});

test('it validates required fields', function () {
    $response = $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'test-secret-token')
        ->postJson('/webhook', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['message.text', 'message.chat.id']);
});

test('it handles successful webhook and calls telegram service', function () {
    Ange::fake();

    $this->mock(TelegramService::class, function ($mock) {
        $mock->shouldReceive('sendMessage')
            ->once()
            ->with(123, 'Fake response for prompt: Hello')
            ->andReturn([]);
    });

    $response = $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'test-secret-token')
        ->postJson('/webhook', [
            'message' => [
                'text' => 'Hello',
                'chat' => ['id' => 123],
            ],
        ]);

    $response->assertOk();
    $response->assertJson(['message' => 'Fake response for prompt: Hello']);

    Ange::assertPrompted('Hello');
});
