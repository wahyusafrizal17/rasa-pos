<?php

return [
    'client_id' => env('ZOHO_CLIENT_ID', ''),
    'client_secret' => env('ZOHO_CLIENT_SECRET', ''),
    'refresh_token' => env('ZOHO_REFRESH_TOKEN', ''),
    'organization_id' => env('ZOHO_ORGANIZATION_ID', ''),
    'token_url' => env('ZOHO_TOKEN_URL', 'https://accounts.zoho.com/oauth/v2/token'),
    'base_url' => env('ZOHO_BASE_URL', 'https://www.zohoapis.com'),
    'customer_id' => env('ZOHO_CUSTOMER_ID', ''),
    'cf_source' => env('ZOHO_CF_SOURCE', 'POS'),
];
