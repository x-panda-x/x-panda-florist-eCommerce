<?php

declare(strict_types=1);

return [
    'base_url' => 'https://example.com',
    'sftp' => [
        'host' => 'your-host.example.com',
        'port' => 22,
        'user' => 'your-user',
        'key_path' => '/path/to/authorized/deployment/key',
        'remote_notification_log' => '/home/your-user/public_html/storage/logs/notifications.log',
    ],
    'customer' => [
        'email_prefix' => 'emailtest',
        'initial_password' => 'EmailSmoke!ReplaceMe',
        'reset_password' => 'EmailReset!ReplaceMe',
        'full_name' => 'Email Smoke Customer',
        'phone' => '6155550102',
    ],
    'reminder' => [
        'occasion_label' => 'Smoke Test Occasion',
        'recipient_name' => 'Reminder Recipient',
        'note' => 'Email smoke reminder flow',
        'delivery_address' => '123 Review Lane',
        'delivery_zip' => '37211',
        'delivery_time_slot' => '12:00-15:00',
        'delivery_instructions' => 'Front desk',
        'card_message' => 'Email smoke order',
        'product_slug' => 'same-day-spring-bouquet',
    ],
];
