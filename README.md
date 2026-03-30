# Ange

WIP...

## Telegram Webhook API Call

Webhook API format:

```json
{
    "update_id": 10000000,
    "message": {
        "message_id": 10,
        "from": {
            "id": 123456789,
            "is_bot": false,
            "first_name": "Allen",
            "username": "allen",
            "language_code": "zh-hans",
            "is_premium": true
        },
        "chat": {
            "id": 123456789,
            "first_name": "Allen",
            "username": "allen",
            "type": "private"
        },
        "date": 1774769180,
        "text": "Hello!"
    }
}
```

Send message response:

```json
{
    "ok": true,
    "result": {
        "message_id": 10,
        "from": {
            "id": 1234567890,
            "is_bot": true,
            "first_name": "Ange 🤖",
            "username": "laravel_ai_agent_ange_bot"
        },
        "chat": {
            "id": 123456789,
            "first_name": "Allen",
            "username": "allen",
            "type": "private"
        },
        "date": 1774855246,
        "text": "Ange is thinking... ⏳"
    }
}
```
