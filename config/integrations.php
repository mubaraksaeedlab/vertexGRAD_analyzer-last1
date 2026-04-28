<?php

return [

   'github' => [
    'app_id' => env('GITHUB_APP_ID'),
    'client_id' => env('GITHUB_APP_CLIENT_ID'),
    'client_secret' => env('GITHUB_APP_CLIENT_SECRET'),
    'private_key' => env('GITHUB_APP_PRIVATE_KEY'),
    'private_key_path' => env('GITHUB_APP_PRIVATE_KEY_PATH'),
    'webhook_secret' => env('GITHUB_WEBHOOK_SECRET'),
    'api_base' => env('GITHUB_API_BASE', 'https://api.github.com'),
],

];