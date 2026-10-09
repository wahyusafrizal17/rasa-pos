<?php

return [
    'merchant_id' => env('FASPAY_MERCHANT_ID', ''),
    'merchant' => env('FASPAY_MERCHANT', env('APP_NAME', 'Rasa POS')),
    'user' => env('FASPAY_USER', ''),
    'password' => env('FASPAY_PASSWORD', ''),
    'qris_channel' => env('FASPAY_QRIS_CHANNEL', '702'),
    'sandbox' => filter_var(env('FASPAY_SANDBOX', true), FILTER_VALIDATE_BOOL),
    'post_url' => [
        true => 'https://debit-sandbox.faspay.co.id/cvr/300011/10',
        false => 'https://web.faspay.co.id/cvr/300011/10',
    ],
    'inquiry_url' => [
        true => 'https://debit-sandbox.faspay.co.id/cvr/100004/10',
        false => 'https://web.faspay.co.id/cvr/100004/10',
    ],
];
