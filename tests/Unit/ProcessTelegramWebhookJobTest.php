<?php

use App\Jobs\ProcessTelegramWebhookJob;

test('it converts paragraphs and trims output', function () {
    $job = new ProcessTelegramWebhookJob(123, 'text');

    $markdown = "Hello world!\n\nThis is a paragraph.";
    $result = $job->convertToTelegramHtml($markdown);

    expect($result)->toBe("Hello world!\n\nThis is a paragraph.");
});

test('it supports bold and italic', function () {
    $job = new ProcessTelegramWebhookJob(123, 'text');

    $markdown = 'This is **bold** and *italic* text.';
    $result = $job->convertToTelegramHtml($markdown);

    expect($result)->toBe('This is <strong>bold</strong> and <em>italic</em> text.');
});

test('it converts headings to bold and adds spacing', function () {
    $job = new ProcessTelegramWebhookJob(123, 'text');

    $markdown = "# Heading 1\n## Heading 2\n\nSome text.";
    $result = $job->convertToTelegramHtml($markdown);

    expect($result)->toContain('<b>Heading 1</b>')
        ->and($result)->toContain('<b>Heading 2</b>');
});

test('it converts list items to bullet points', function () {
    $job = new ProcessTelegramWebhookJob(123, 'text');

    $markdown = "- Item A\n- Item B";
    $result = $job->convertToTelegramHtml($markdown);

    expect($result)->toBe("• Item A\n\n• Item B");
});

test('it strips unsupported HTML tags but keeps Telegram ones', function () {
    $job = new ProcessTelegramWebhookJob(123, 'text');

    // Passing raw HTML since Markdown is compiled by Str::markdown
    $htmlInput = '<div>Some text inside div</div> <span>with <strong>bold</strong> span</span>';
    $result = $job->convertToTelegramHtml($htmlInput);

    expect($result)->toBe('Some text inside div <span>with <strong>bold</strong> span</span>');
});

test('it handles pre and code blocks', function () {
    $job = new ProcessTelegramWebhookJob(123, 'text');

    $markdown = "```php\necho 'hello';\n```";
    $result = $job->convertToTelegramHtml($markdown);

    expect($result)->toContain("<pre><code class=\"language-php\">echo 'hello';");
});

test('it encodes special characters safely', function () {
    $job = new ProcessTelegramWebhookJob(123, 'text');

    $markdown = 'If x < 5 && y > 10';
    $result = $job->convertToTelegramHtml($markdown);

    expect($result)->toBe('If x &lt; 5 &amp;&amp; y &gt; 10');
});
