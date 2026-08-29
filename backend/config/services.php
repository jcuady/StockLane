<?php

return [

    'paymongo' => [
        'secret_key' => env('PAYMONGO_SECRET_KEY', ''),
        'webhook_secret' => env('PAYMONGO_WEBHOOK_SECRET', ''),
    ],

    'busybee' => [
        'api_key' => env('BUSYBEE_API_KEY', ''),
        'sender' => env('BUSYBEE_SENDER', 'STOCKLANE'),
        'to' => env('LOW_STOCK_SMS_TO', ''),
    ],

];
