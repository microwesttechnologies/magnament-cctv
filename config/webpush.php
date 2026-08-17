<?php

return [
    'vapid_public' => env('VAPID_PUBLIC_KEY'),
    'vapid_private' => env('VAPID_PRIVATE_KEY'),
    'vapid_subject' => env('VAPID_SUBJECT', env('APP_URL', 'http://localhost')),
];
