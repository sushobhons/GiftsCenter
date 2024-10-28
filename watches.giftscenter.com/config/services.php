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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'sms_gateway' => [
        'url' => env('SMS_GATEWAY_URL', 'http://josmsservice.com/smsonline/smppinterform.cfm'),
        'sender_id' => env('SMS_SENDER_ID', 'GiftsCenter'),
        'account_name' => env('SMS_ACCOUNT_NAME', 'giftce'),
        'account_password' => env('SMS_ACCOUNT_PASSWORD', 'Tul7uA5KVC5nRo32'),
        'request_timeout' => env('SMS_REQUEST_TIMEOUT', 5000000),
    ],

    'mc_payment' => [
        'api_username' => env('MC_PAYMENT_API_USERNAME','merchant.9500002036EP'),
        'api_password' => env('MC_PAYMENT_API_PASSWORD','3bc688bef4b30a3f4a22ec604cfa300f'),
        'merchant_id' => env('MC_PAYMENT_MERCHANT_ID','9500002036EP'),
        'gateway_url' => env('MC_PAYMENT_GATEWAY_URL','https://ap-gateway.mastercard.com/api/nvp/version/61'),
        'checkout_url' => env('MC_PAYMENT_CHECKOUT_URL','https://ap-gateway.mastercard.com/checkout/version/61/checkout.js'),
    ],

    'mp_payment' => [
        'api_password' => env('MP_PAYMENT_API_PASSWORD', '00fec44536b5f7c7e2725bf82c5c1ca5'),
        'merchant_key' => env('MP_PAYMENT_MERCHANT_KEY', '08f9354c-af91-11ee-bfd6-7aa05a16e0e1'),
        'gateway_url' => env('MP_PAYMENT_GATEWAY_URL', 'https://checkout.montypay.com/api/v1/session'),
    ],

];
