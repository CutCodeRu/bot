<?php

declare(strict_types=1);

return [
    'use_newsletter' => true,
    'test' => [
        'token' => env('TEST_BOT_TOKEN'),
        'username' => env('TEST_BOT_USERNAME'),
    ],
];
