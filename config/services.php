<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'aisensy' => [
        'api_key' => env('AISENSY_API_KEY'),
        'url' => env('AISENSY_URL', 'https://apis.aisensy.com/sendCampaign'),
        'template' => env('AISENSY_TEMPLATE', 'ATTENDANCE_ALERT'),
        'template_in' => env('AISENSY_TEMPLATE_IN', env('AISENSY_TEMPLATE', 'ATTENDANCE_ALERT')),
        'template_out' => env('AISENSY_TEMPLATE_OUT', env('AISENSY_TEMPLATE', 'ATTENDANCE_ALERT')),
        'template_manual_in' => env('AISENSY_TEMPLATE_MANUAL_IN', env('AISENSY_TEMPLATE_IN', env('AISENSY_TEMPLATE', 'ATTENDANCE_ALERT'))),
        'template_manual_out' => env('AISENSY_TEMPLATE_MANUAL_OUT', env('AISENSY_TEMPLATE_OUT', env('AISENSY_TEMPLATE', 'ATTENDANCE_ALERT'))),
    ],

];
