<?php

use App\Ai\Agents\Ange;
use App\Services\TelegramService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

uses(RefreshDatabase::class);

beforeEach(function () {
    Config::set('services.telegram.bot_secret_token', 'test-secret-token');
    Config::set('services.telegram.bot_username', 'test_bot');
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

test('it returns ok for empty payload', function () {
    $response = $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'test-secret-token')
        ->postJson('/webhook', []);

    $response->assertOk();
    $response->assertJson(['message' => 'No chat ID or text found']);
});

test('it returns ok for non-message updates', function () {
    $response = $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'test-secret-token')
        ->postJson('/webhook', [
            'edited_message' => [
                'text' => 'Edited text',
                'chat' => ['id' => 123],
            ],
        ]);

    $response->assertOk();
});

test('it handles successful webhook and calls telegram service', function () {
    Ange::fake();

    $this->mock(TelegramService::class, function ($mock) {
        $mock->shouldReceive('sendMessage')
            ->once()
            ->andReturn(['result' => ['message_id' => 456]]);

        $mock->shouldReceive('editMessageText')
            ->once()
            ->with(123, 456, 'Fake response for prompt: Hello')
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
    $response->assertJson(['message' => 'ok']);

    $this->assertDatabaseHas('histories', [
        'chat_id' => '123',
        'role' => 'user',
        'content' => 'Hello',
    ]);

    $this->assertDatabaseHas('histories', [
        'chat_id' => '123',
        'role' => 'assistant',
        'content' => 'Fake response for prompt: Hello',
    ]);

    Ange::assertPrompted('Hello');
});

test('it handles successful webhook and falls back to sendMessage if thinking message fails', function () {
    Ange::fake();

    $this->mock(TelegramService::class, function ($mock) {
        $mock->shouldReceive('sendMessage')
            ->twice()
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
    $response->assertJson(['message' => 'ok']);

    Ange::assertPrompted('Hello');
});

test('it returns ok when message has no text', function () {
    $response = $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'test-secret-token')
        ->postJson('/webhook', [
            'message' => [
                'chat' => ['id' => 123],
            ],
        ]);

    $response->assertOk();
    $response->assertJson(['message' => 'No chat ID or text found']);
});

test('group chat message with bot mention triggers the job', function () {
    Ange::fake();

    $this->mock(TelegramService::class, function ($mock) {
        $mock->shouldReceive('sendMessage')
            ->once()
            ->andReturn(['result' => ['message_id' => 456]]);

        $mock->shouldReceive('editMessageText')
            ->once()
            ->with(-100123, 456, 'Fake response for prompt: Hello there')
            ->andReturn([]);
    });

    $response = $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'test-secret-token')
        ->postJson('/webhook', [
            'message' => [
                'message_id' => 789,
                'text' => '@test_bot Hello there',
                'chat' => ['id' => -100123, 'type' => 'supergroup'],
                'from' => ['first_name' => 'John', 'last_name' => 'Doe'],
            ],
        ]);

    $response->assertOk();
    $response->assertJson(['message' => 'ok']);

    $this->assertDatabaseHas('histories', [
        'chat_id' => '-100123',
        'role' => 'user',
        'content' => '[John Doe]: Hello there',
    ]);

    Ange::assertPrompted('Hello there');
});

test('group chat message without bot mention is ignored', function () {
    Ange::fake();

    $response = $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'test-secret-token')
        ->postJson('/webhook', [
            'message' => [
                'message_id' => 789,
                'text' => 'Hello everyone',
                'chat' => ['id' => -100123, 'type' => 'group'],
            ],
        ]);

    $response->assertOk();
    $response->assertJson(['message' => 'ok']);

    $this->assertDatabaseMissing('histories', [
        'chat_id' => '-100123',
    ]);
});

test('group chat message with bot mention not at start is ignored', function () {
    Ange::fake();

    $response = $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'test-secret-token')
        ->postJson('/webhook', [
            'message' => [
                'message_id' => 789,
                'text' => 'Hey @test_bot what do you think?',
                'chat' => ['id' => -100123, 'type' => 'supergroup'],
            ],
        ]);

    $response->assertOk();
    $response->assertJson(['message' => 'ok']);

    $this->assertDatabaseMissing('histories', [
        'chat_id' => '-100123',
    ]);
});

test('group chat message with only bot mention and no text is ignored', function () {
    $response = $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'test-secret-token')
        ->postJson('/webhook', [
            'message' => [
                'message_id' => 789,
                'text' => '@test_bot',
                'chat' => ['id' => -100123, 'type' => 'supergroup'],
            ],
        ]);

    $response->assertOk();
    $response->assertJson(['message' => 'ok']);

    $this->assertDatabaseMissing('histories', [
        'chat_id' => '-100123',
    ]);
});

test('group chat message stores sender first name only when last name is missing', function () {
    Ange::fake();

    $this->mock(TelegramService::class, function ($mock) {
        $mock->shouldReceive('sendMessage')
            ->once()
            ->andReturn(['result' => ['message_id' => 456]]);

        $mock->shouldReceive('editMessageText')
            ->once()
            ->andReturn([]);
    });

    $response = $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'test-secret-token')
        ->postJson('/webhook', [
            'message' => [
                'message_id' => 789,
                'text' => '@test_bot Hi',
                'chat' => ['id' => -100123, 'type' => 'group'],
                'from' => ['first_name' => 'Alice'],
            ],
        ]);

    $response->assertOk();

    $this->assertDatabaseHas('histories', [
        'chat_id' => '-100123',
        'role' => 'user',
        'content' => '[Alice]: Hi',
    ]);
});

test('private chat does not prefix sender name in history', function () {
    Ange::fake();

    $this->mock(TelegramService::class, function ($mock) {
        $mock->shouldReceive('sendMessage')
            ->once()
            ->andReturn(['result' => ['message_id' => 456]]);

        $mock->shouldReceive('editMessageText')
            ->once()
            ->andReturn([]);
    });

    $response = $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'test-secret-token')
        ->postJson('/webhook', [
            'message' => [
                'text' => 'Hello',
                'chat' => ['id' => 123, 'type' => 'private'],
                'from' => ['first_name' => 'Bob'],
            ],
        ]);

    $response->assertOk();

    $this->assertDatabaseHas('histories', [
        'chat_id' => '123',
        'role' => 'user',
        'content' => 'Hello',
    ]);
});

test('private chat does not require bot mention', function () {
    Ange::fake();

    $this->mock(TelegramService::class, function ($mock) {
        $mock->shouldReceive('sendMessage')
            ->once()
            ->andReturn(['result' => ['message_id' => 456]]);

        $mock->shouldReceive('editMessageText')
            ->once()
            ->andReturn([]);
    });

    $response = $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'test-secret-token')
        ->postJson('/webhook', [
            'message' => [
                'text' => 'Hello',
                'chat' => ['id' => 123, 'type' => 'private'],
            ],
        ]);

    $response->assertOk();
    $response->assertJson(['message' => 'ok']);

    Ange::assertPrompted('Hello');
});
