# Ange

Ange (🤖) is a Telegram bot powered by an AI agent built on Laravel 13 and the
[`laravel/ai`](https://github.com/laravel/ai) SDK. Telegram updates hit a
webhook, get queued as jobs, and are answered by a conversational agent
(`App\Ai\Agents\Ange`) with persistent per-chat history. The same agent is also
reachable from the CLI via an interactive `php artisan ange:chat` command, so
you can iterate on prompts and tools without going through Telegram.

> **Status:** in active development. APIs, env vars, and behavior may change.

## Features

- **Telegram webhook handler** — `POST /webhook` validates Telegram's
  `X-Telegram-Bot-Api-Secret-Token` header, then dispatches a job for async
  processing.
- **Private and group chats** — in groups, the bot only responds to messages
  starting with `/<bot_username>` (or `/<bot_username>@<bot_username>`) followed
  by a question; other group messages are ignored. Group replies use Telegram's
  `reply_parameters` to thread back to the user's message.
- **Per-chat conversation history** — messages are persisted to a `histories`
  table; the agent rehydrates the last 50 messages when generating a reply.
  Group-chat history prefixes user messages with `[Sender Name]:` so the model
  can address the right person.
- **Built-in agent tools** — Ange ships with `WebSearch` and `WebFetch` from
  `laravel/ai` so it can look things up online.
- **Markdown → Telegram HTML** — agent replies are written in Markdown and
  converted to the limited HTML subset Telegram's `parse_mode=HTML` supports
  (`b`, `i`, `u`, `s`, `a`, `code`, `pre`, `blockquote`, etc.).
- **"Thinking…" placeholders** — the job sends a randomized placeholder
  message immediately and edits it with the final answer once the agent
  finishes, so users get instant feedback.
- **Interactive CLI** — `php artisan ange:chat` opens a streaming REPL backed
  by the same agent and history table (under `chat_id = "cli-chat"`).

## Requirements

- PHP **8.5+**
- Composer
- Node.js + npm (for Vite / Tailwind v4 assets)
- SQLite (default; configurable via `DB_CONNECTION`)
- A Telegram bot — create one with [@BotFather](https://t.me/BotFather) to get
  a bot token, username, and a publicly reachable HTTPS URL for the webhook
- An AI provider key. The bundled `Ange` agent is configured to use
  **Gemini** (`#[Provider(Lab::Gemini)]`, model `gemini-3.8-flash`), so a
  `GEMINI_API_KEY` is required out of the box. Other providers (OpenAI,
  Anthropic, Azure, Groq, xAI, etc.) are
  pre-wired in `config/ai.php` if you change the agent's `#[Provider]`
  attribute.

## Installation

```bash
git clone <repo-url> ange
cd ange

composer install
npm install

cp .env.example .env
php artisan key:generate
php artisan migrate
```

Or, equivalently, run the bundled setup script:

```bash
composer run setup
```

## Configuration

All configuration is read from `.env`. The variables specific to Ange:

| Variable                    | Required     | Description                                                                                                                                                                   |
|-----------------------------|--------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `TELEGRAM_BOT_TOKEN`        | yes          | Bot token from BotFather; used to call the Telegram Bot API.                                                                                                                  |
| `TELEGRAM_BOT_SECRET_TOKEN` | yes          | Shared secret you set when registering the webhook. The `VerifyTelegramSecretToken` middleware rejects requests whose `X-Telegram-Bot-Api-Secret-Token` header doesn't match. |
| `TELEGRAM_BOT_USERNAME`     | yes (groups) | Bot's username **without** the `@`. Used to detect `/botname` commands in group chats. Defaults to `ange`.                                                                    |
| `GEMINI_API_KEY`            | yes          | API key for the Gemini provider used by the default agent.                                                                                                                    |

Other AI provider keys (`OPENAI_API_KEY`, `ANTHROPIC_API_KEY`,
`AZURE_OPENAI_*`, `GROQ_API_KEY`, `XAI_API_KEY`, etc.) are wired up in
`config/ai.php` and only need to be set if you switch `Ange`'s `#[Provider]`
attribute to a different lab.

The default queue connection is `database`, and the default DB is SQLite — so
no extra services are needed for local development.

## Running locally

The fastest way to run the full stack (HTTP server + queue worker + log
tailer + Vite) is the bundled `dev` script:

```bash
composer run dev
```

This runs, concurrently:

- `php artisan serve` — HTTP server
- `php artisan queue:listen --tries=1 --timeout=0` — required, since the
  webhook dispatches `ProcessTelegramWebhookJob`
- `php artisan pail --timeout=0` — tails application logs
- `npm run dev` — Vite

If you only want to run pieces individually, those are the four commands.

### Interactive CLI

To talk to the same agent from your terminal, with streaming output and
persistent history:

```bash
php artisan ange:chat
```

Type `exit` to quit. CLI history is stored under `chat_id = "cli-chat"` in
the `histories` table, separate from any Telegram chats.

## Telegram webhook setup

Telegram needs a publicly reachable HTTPS URL pointing at `POST /webhook`.
For local development you can tunnel through something like ngrok / Expose /
Cloudflare Tunnel.

Register the webhook with Telegram (replace the placeholders, and use the
same secret token you put in `TELEGRAM_BOT_SECRET_TOKEN`):

```bash
curl "https://api.telegram.org/bot<TELEGRAM_BOT_TOKEN>/deleteWebhook?drop_pending_updates=true"

curl "https://api.telegram.org/bot<TELEGRAM_BOT_TOKEN>/setWebhook?url=<PUBLIC_HOST>/webhook&secret_token=<TELEGRAM_BOT_SECRET_TOKEN>"
```

A minimal incoming webhook payload looks like this:

```json
{
  "update_id": 10000000,
  "message": {
    "message_id": 10,
    "from": { "id": 123456789, "first_name": "Allen", "username": "allen" },
    "chat": { "id": 123456789, "type": "private" },
    "date": 1774769180,
    "text": "Hello!"
  }
}
```

In group chats, only messages starting with `/<bot_username>` followed by question text (e.g.
`/ange What is Laravel?`) are processed; messages without text or not mentioning the bot return
`{"message":"ok"}` and are ignored.

## Testing

The project uses Pest 4 with `RefreshDatabase`:

```bash
composer test
# or
php artisan test --compact
```

Filter to a single file or test:

```bash
php artisan test --compact --filter=WebhookTest
```

## Project structure

```
app/
├── Ai/Agents/Ange.php             # The AI agent: instructions, history, tools
├── Console/Commands/AngeChat.php  # `php artisan ange:chat` interactive REPL
├── Http/
│   ├── Controllers/HandleWebhookController.php
│   └── Middleware/VerifyTelegramSecretToken.php
├── Jobs/ProcessTelegramWebhookJob.php  # Async pipeline: placeholder → agent → edit
├── Models/History.php             # Per-chat conversation log
└── Services/TelegramService.php   # Telegram Bot API wrapper + Markdown→HTML
config/
├── ai.php          # laravel/ai provider definitions
└── services.php    # Telegram bot_token / bot_secret_token / bot_username
routes/
└── web.php         # GET / health check, POST /webhook
tests/
├── Feature/{AgentHistoryTest,WebhookTest}.php
└── Unit/TelegramServiceTest.php
```

`AGENTS.md` and `CLAUDE.md` are instructions for AI coding assistants working
on this repo, not runtime configuration.
