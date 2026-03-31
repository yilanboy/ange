<?php

use App\Services\TelegramService;

test('it converts plain text to telegram html', function () {
    $result = TelegramService::toTelegramHtml('Hello world');

    expect($result)->toBe('Hello world');
});

test('it converts bold and italic markdown to html', function () {
    $result = TelegramService::toTelegramHtml('**bold** and *italic*');

    expect($result)->toContain('<strong>bold</strong>')
        ->toContain('<em>italic</em>');
});

test('it converts headings to bold text', function () {
    $result = TelegramService::toTelegramHtml('# Heading');

    expect($result)->toContain('<b>Heading</b>');
});

test('it converts code blocks to pre tags', function () {
    $result = TelegramService::toTelegramHtml("```php\necho 'hello';\n```");

    expect($result)->toContain('<pre>')
        ->toContain('<code class="language-php">');
});

test('it converts inline code to code tags', function () {
    $result = TelegramService::toTelegramHtml('Use `artisan` command');

    expect($result)->toContain('<code>artisan</code>');
});

test('it converts list items to bullet points', function () {
    $result = TelegramService::toTelegramHtml("- first\n- second");

    expect($result)->toContain('• first')
        ->toContain('• second');
});

test('it strips unsupported html tags', function () {
    $result = TelegramService::toTelegramHtml('Hello world');

    expect($result)->not->toContain('<p>')
        ->not->toContain('</p>');
});
