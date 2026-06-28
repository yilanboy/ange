<?php

use App\Services\TelegramService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

test('it sends message successfully', function () {
    Http::fake([
        'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200),
    ]);

    $service = new TelegramService;
    $service->sendMessage(123, 'Hello');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'sendMessage') &&
            $request['chat_id'] === 123 &&
            $request['text'] === 'Hello' &&
            ! isset($request['parse_mode']);
    });
});

test('it sends message with optional params', function () {
    Http::fake([
        'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200),
    ]);

    $service = new TelegramService;
    $service->sendMessage(123, 'Hello', ['reply_parameters' => ['message_id' => 456]]);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'sendMessage') &&
            $request['chat_id'] === 123 &&
            $request['text'] === 'Hello' &&
            ! isset($request['parse_mode']) &&
            $request['reply_parameters'] === ['message_id' => 456];
    });
});

test('it edits message successfully', function () {
    Http::fake([
        'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200),
    ]);

    $service = new TelegramService;
    $service->editMessage(123, 456, 'Edited');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'editMessageText') &&
            $request['chat_id'] === 123 &&
            $request['message_id'] === 456 &&
            $request['text'] === 'Edited' &&
            ! isset($request['parse_mode']);
    });
});

test('it edits message with optional params', function () {
    Http::fake([
        'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200),
    ]);

    $service = new TelegramService;
    $service->editMessage(123, 456, 'Edited', ['reply_markup' => []]);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'editMessageText') &&
            $request['chat_id'] === 123 &&
            $request['message_id'] === 456 &&
            $request['text'] === 'Edited' &&
            ! isset($request['parse_mode']) &&
            $request['reply_markup'] === [];
    });
});
