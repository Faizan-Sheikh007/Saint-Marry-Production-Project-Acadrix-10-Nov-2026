<?php

// ─────────────────────────────────────────────────────────────────────────
// Field-level encryption configuration for sensitive HR identity fields.
//
// Sensitive fields (CNIC, bank account numbers) on Teacher and Staff are
// protected with hybrid RSA+AES "envelope encryption":
//   1. A fresh random AES-256 key is generated for every value.
//   2. The value is encrypted with AES-256-GCM (fast, handles any length).
//   3. The AES key itself is encrypted with the RSA public key.
//   4. Only the RSA private key (never the app's symmetric APP_KEY alone)
//      can unwrap the AES key and recover the data.
//
// This means even a full database leak is useless without the RSA
// private key, which should be stored outside the web root / repo
// (e.g. an env var, a secrets manager, or an HSM in production).
// ─────────────────────────────────────────────────────────────────────────
return [

    'rsa' => [
        // Where the keypair lives. Defaults to storage/app/private/keys,
        // which is OUTSIDE the public webroot and outside git (storage/
        // is gitignored). Override via .env for production secrets
        // management (mounted secret, vault path, etc).
        'private_key_path' => env('SALARY_RSA_PRIVATE_KEY', storage_path('app/private/keys/salary_private.pem')),
        'public_key_path'  => env('SALARY_RSA_PUBLIC_KEY', storage_path('app/private/keys/salary_public.pem')),

        // Optional passphrase protecting the private key file at rest.
        // Strongly recommended in production — set SALARY_RSA_PASSPHRASE
        // in .env (never commit it).
        'passphrase' => env('SALARY_RSA_PASSPHRASE'),

        // 2048-bit is the practical minimum for RSA today; 3072/4096 are
        // stronger but slower to generate. This only wraps a 32-byte AES
        // key, so the extra key-generation cost is a one-time thing.
        'key_bits' => (int) env('SALARY_RSA_KEY_BITS', 2048),
    ],

    'aes' => [
        'cipher' => 'aes-256-gcm',
    ],
];
