<?php
if (!defined('PHELYZ_ACCESS')) { exit; }

/**
 * Paystack payment helpers.
 *
 * Keys are read from env vars first (Vercel), then data/settings.json (local
 * admin UI). Test keys (sk_test_… / pk_test_…) work identically — Paystack
 * routes them to its sandbox, use test card 4084 0840 8408 4081.
 */

function paystackSettings() {
    static $cache = null;
    if ($cache !== null) return $cache;
    $file = __DIR__ . '/../data/settings.json';
    $cache = file_exists($file) ? (json_decode(file_get_contents($file), true) ?? []) : [];
    return $cache;
}

function paystackSecretKey() {
    $env = getenv('PAYSTACK_SECRET_KEY') ?: ($_ENV['PAYSTACK_SECRET_KEY'] ?? '');
    if (!empty($env)) return $env;
    $s = paystackSettings();
    return $s['paystack_secret_key'] ?? '';
}

function paystackPublicKey() {
    $env = getenv('PAYSTACK_PUBLIC_KEY') ?: ($_ENV['PAYSTACK_PUBLIC_KEY'] ?? '');
    if (!empty($env)) return $env;
    $s = paystackSettings();
    return $s['paystack_public_key'] ?? '';
}

function paystackConfigured() {
    return !empty(paystackSecretKey());
}

/**
 * Initialize a transaction. Amount is in NGN (converted to kobo here).
 * Returns ['ok' => bool, 'authorization_url' => ?, 'reference' => ?, 'message' => ?]
 */
function paystackInitialize($email, $amountNgn, $callbackUrl, $metadata = []) {
    $secret = paystackSecretKey();
    if (empty($secret)) {
        return ['ok' => false, 'message' => 'Paystack is not configured.'];
    }

    $payload = [
        'email'        => $email,
        'amount'       => (int)round($amountNgn * 100), // kobo
        'currency'     => 'NGN',
        'callback_url' => $callbackUrl,
        'metadata'     => $metadata,
    ];

    $ch = curl_init('https://api.paystack.co/transaction/initialize');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $secret,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 30,
    ]);
    $response = curl_exec($ch);
    $err      = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return ['ok' => false, 'message' => 'Network error contacting Paystack: ' . $err];
    }

    $body = json_decode($response, true);
    if (empty($body['status']) || empty($body['data']['authorization_url'])) {
        return ['ok' => false, 'message' => $body['message'] ?? 'Paystack rejected the transaction.'];
    }

    return [
        'ok'                => true,
        'authorization_url' => $body['data']['authorization_url'],
        'reference'         => $body['data']['reference'],
    ];
}

/**
 * Verify a transaction by reference.
 * Returns ['ok' => bool, 'paid' => bool, 'amount_ngn' => float, 'message' => ?]
 */
function paystackVerify($reference) {
    $secret = paystackSecretKey();
    if (empty($secret)) {
        return ['ok' => false, 'paid' => false, 'message' => 'Paystack is not configured.'];
    }

    $ch = curl_init('https://api.paystack.co/transaction/verify/' . rawurlencode($reference));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $secret],
        CURLOPT_TIMEOUT        => 30,
    ]);
    $response = curl_exec($ch);
    $err      = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return ['ok' => false, 'paid' => false, 'message' => 'Network error contacting Paystack: ' . $err];
    }

    $body = json_decode($response, true);
    if (empty($body['status'])) {
        return ['ok' => false, 'paid' => false, 'message' => $body['message'] ?? 'Verification failed.'];
    }

    $data = $body['data'] ?? [];
    return [
        'ok'         => true,
        'paid'       => ($data['status'] ?? '') === 'success',
        'amount_ngn' => isset($data['amount']) ? ((float)$data['amount'] / 100) : 0.0,
        'message'    => $data['gateway_response'] ?? '',
    ];
}
