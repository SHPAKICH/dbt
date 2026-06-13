<?php

return [
    'adminEmail' => 'admin@example.com',
    'senderEmail' => getenv('MAILER_FROM') ?: 'noreply@dbthub.local',
    'senderName' => getenv('MAILER_FROM_NAME') ?: 'DBT Hub',
    'telegramBotToken' => getenv('TELEGRAM_BOT_TOKEN') ?: '',
    'telegramBotUsername' => getenv('TELEGRAM_BOT_USERNAME') ?: 'dbthub_bot',
    'telegramWebhookSecret' => getenv('TELEGRAM_WEBHOOK_SECRET') ?: '',
    'bsVersion' => '5.x',
    // URL фронтенда (Vue) для CORS и редиректов
    'frontendUrl' => getenv('FRONTEND_URL') ?: 'http://localhost:5173',
    // Web Push (VAPID) for browser notifications
    'vapidPublicKey' => getenv('VAPID_PUBLIC_KEY') ?: '',
    'vapidPrivateKey' => getenv('VAPID_PRIVATE_KEY') ?: '',
    'vapidSubject' => getenv('VAPID_SUBJECT') ?: 'mailto:admin@example.com',
    'dbBackup' => [
        'path' => '@runtime/backups/db',
        'retentionDays' => 14,
        'scheduleLabel' => 'Каждые 12 часов',
    ],
];
