<?php

namespace App\Casts;

use App\Services\RsaEnvelopeEncryption;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent attribute cast that transparently protects a sensitive field
 * (CNIC, bank account number, ...) with RSA+AES envelope encryption
 * (see App\Services\RsaEnvelopeEncryption).
 *
 * Usage on a model:
 *   protected $casts = ['cnic' => \App\Casts\RsaEncrypted::class];
 *
 * The column stores only the encrypted envelope. Reading the attribute
 * decrypts on the fly (requires the RSA private key); writing it
 * re-encrypts with a fresh AES key + IV every time.
 */
class RsaEncrypted implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Backward-compat: a field encrypted for the first time after
        // some plaintext rows already existed shouldn't turn old records
        // into blanks. If the stored value doesn't look like one of our
        // RSA+AES envelopes, treat it as legacy plaintext and return it
        // as-is — it gets transparently re-encrypted the next time the
        // record is saved.
        if (!$this->looksLikeEnvelope($value)) {
            return $value;
        }

        try {
            return RsaEnvelopeEncryption::fromConfig()->decrypt($value);
        } catch (\Throwable $e) {
            // Fail closed — never leak ciphertext or throw a raw stack
            // trace into a Blade view. Log for an admin to investigate
            // (missing/rotated key, tampering, etc).
            report($e);
            return null;
        }
    }

    private function looksLikeEnvelope(string $value): bool
    {
        $decoded = base64_decode($value, true);
        if ($decoded === false) {
            return false;
        }

        $json = json_decode($decoded, true);
        return is_array($json) && isset($json['v'], $json['k'], $json['iv'], $json['tag'], $json['c']);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return RsaEnvelopeEncryption::fromConfig()->encrypt((string) $value);
    }
}
