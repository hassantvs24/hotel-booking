<?php

// SSLCommerz configuration

$apiDomain = env('SSLCZ_TESTMODE', true) ? "https://sandbox.sslcommerz.com" : "https://securepay.sslcommerz.com";
return [
    'apiCredentials' => [
        'store_id' => env("SSLCOMM_STORE_ID"),
        'store_password' => env("SSLCOMM_STORE_PASSWORD"),
    ],
    'apiUrl' => [
        'make_payment' => "/gwprocess/v4/api.php",
        'transaction_status' => "/validator/api/merchantTransIDvalidationAPI.php",
        'order_validate' => "/validator/api/validationserverAPI.php",
        'refund_payment' => "/validator/api/merchantTransIDvalidationAPI.php",
        'refund_status' => "/validator/api/merchantTransIDvalidationAPI.php",
    ],
    'apiDomain' => $apiDomain,
    'refund' => [
        'timeout' => (int) env('SSLCZ_REFUND_TIMEOUT', 20),
        'retry_times' => (int) env('SSLCZ_REFUND_RETRY_TIMES', 2),
        'retry_delay_ms' => (int) env('SSLCZ_REFUND_RETRY_DELAY_MS', 300),
        'remarks_max_length' => 255,
        'fake' => filter_var(
            env('SSLCZ_FAKE_REFUNDS', false),
            FILTER_VALIDATE_BOOL
        ),
    ],
    'connect_from_localhost' => env("IS_LOCALHOST", false), // For Sandbox, use "true", For Live, use "false"
    'success_url' => '/success',
    'failed_url' => '/fail',
    'cancel_url' => '/cancel',
    'ipn_url' => '/ipn',
];
