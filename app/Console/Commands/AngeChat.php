<?php

namespace App\Console\Commands;

use App\Ai\Agents\Ange;
use App\Models\History;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Laravel\Ai\Streaming\Events\TextDelta;

use function Laravel\Prompts\info;
use function Laravel\Prompts\stream;
use function Laravel\Prompts\task;
use function Laravel\Prompts\text;
use function Laravel\Prompts\title;

#[Signature('ange:chat')]
#[Description('Command description')]
class AngeChat extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        title('Ange 🤖');

        info('Start chatting with Ange. Type "exit" to quit.');
        $this->newLine();

        $chatId = 'cli-chat';
        $ange = new Ange($chatId);

        while (true) {
            $input = text(
                label: 'You: ',
                placeholder: 'Type your message here...',
                required: true
            );

            if (strtolower(trim($input)) === 'exit') {
                info('Goodbye! 👋');

                break;
            }

            $this->newLine();

            $events = task('Thinking', function () use ($ange, $input) {
                $collected = [];

                $ange->stream($input)->each(function ($event) use (&$collected) {
                    $collected[] = $event;
                });

                return $collected;
            });

            $output = stream();

            foreach ($events as $event) {
                if ($event instanceof TextDelta) {
                    $output->append($event->delta);
                }
            }

            $output->close();

            History::create([
                'chat_id' => $chatId,
                'role'    => 'user',
                'content' => $input,
            ]);

            History::create([
                'chat_id' => $chatId,
                'role'    => 'assistant',
                'content' => $output->value(),
            ]);

            $this->newLine();
        }
    }
}
