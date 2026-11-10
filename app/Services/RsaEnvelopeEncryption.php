<?php

namespace App\Services;

use RuntimeException;

/**
 * Hybrid RSA + AES-256-GCM "envelope encryption" for salary-module
 * sensitive fields (CNIC numbers, bank account numbers, etc).
 *
 * Why hybrid and not "just RSA": RSA can only encrypt data smaller than
 * its key size (~245 bytes for a 2048-bit key minus OAEP padding), so it
 * cannot safely encrypt arbitrary-length strings on its own. The standard,
 * correct way to use RSA for data-at-rest encryption is to use it to
 * protect a per-value AES key ("envelope"/"digital envelope" encryption),
 * while AES-256-GCM does the actual bulk encryption + integrity check.
 *
 * Stored payload format (base64 JSON):
 *   {
 *     "k": "<RSA-encrypted AES-256 key, base64>",
 *     "iv": "<AES-GCM IV, base64>",
 *     "tag": "<AES-GCM auth tag, base64>",
 *     "c": "<ciphertext, base64>",
 *     "v": 1
 *   }
 *
 * Decryption REQUIRES the RSA private key, so a database-only leak
 * (e.g. a SQL dump) cannot be decrypted.
 */
class RsaEnvelopeEncryption
{
    private const VERSION = 1;

    public function __construct(
        private readonly ?string $publicKeyPem = null,
        private readonly ?string $privateKeyPem = null,
        private readonly ?string $passphrase = null,
        private readonly string $cipher = 'aes-256-gcm',
    ) {}

    public static function fromConfig(): self
    {
        $publicPath  = config('security.rsa.public_key_path');
        $privatePath = config('security.rsa.private_key_path');

        return new self(
            publicKeyPem: is_file($publicPath) ? file_get_contents($publicPath) : null,
            privateKeyPem: is_file($privatePath) ? file_get_contents($privatePath) : null,
            passphrase: config('security.rsa.passphrase'),
            cipher: config('security.aes.cipher', 'aes-256-gcm'),
        );
    }

    public function encrypt(?string $plaintext): ?string
    {
        if ($plaintext === null || $plaintext === '') {
            return null;
        }

        if (!$this->publicKeyPem) {
            throw new RuntimeException(
                'Salary RSA public key not found. Run `php artisan salary:generate-rsa-keys` first.'
            );
        }

        $publicKey = openssl_pkey_get_public($this->publicKeyPem);
        if ($publicKey === false) {
            throw new RuntimeException('Invalid RSA public key for salary encryption.');
        }

        // 1. Fresh random AES-256 key + IV for this value.
        $aesKey = random_bytes(32);
        $iv     = random_bytes(openssl_cipher_iv_length($this->cipher));

        // 2. Bulk-encrypt the plaintext with AES-256-GCM (authenticated).
        $tag        = '';
        $ciphertext = openssl_encrypt($plaintext, $this->cipher, $aesKey, OPENSSL_RAW_DATA, $iv, $tag);
        if ($ciphertext === false) {
            throw new RuntimeException('AES encryption failed for salary field.');
        }

        // 3. Wrap the AES key with RSA-OAEP (asymmetric — only the private
        //    key holder can unwrap it).
        $wrapped = '';
        $ok = openssl_public_encrypt($aesKey, $wrapped, $publicKey, OPENSSL_PKCS1_OAEP_PADDING);
        if (!$ok) {
            throw new RuntimeException('RSA key-wrapping failed for salary field.');
        }

        $payload = [
            'v'   => self::VERSION,
            'k'   => base64_encode($wrapped),
            'iv'  => base64_encode($iv),
            'tag' => base64_encode($tag),
            'c'   => base64_encode($ciphertext),
        ];

        return base64_encode(json_encode($payload));
    }

    public function decrypt(?string $payload): ?string
    {
        if ($payload === null || $payload === '') {
            return null;
        }

        if (!$this->privateKeyPem) {
            throw new RuntimeException(
                'Salary RSA private key not found. It must be present to decrypt protected fields.'
            );
        }

        $decoded = json_decode(base64_decode($payload), true);
        if (!is_array($decoded) || !isset($decoded['k'], $decoded['iv'], $decoded['tag'], $decoded['c'])) {
            // Not a payload we recognise (e.g. legacy plaintext) — fail closed.
            return null;
        }

        $privateKey = $this->passphrase
            ? openssl_pkey_get_private($this->privateKeyPem, $this->passphrase)
            : openssl_pkey_get_private($this->privateKeyPem);

        if ($privateKey === false) {
            throw new RuntimeException('Invalid RSA private key (or wrong passphrase) for salary decryption.');
        }

        $aesKey = '';
        $ok = openssl_private_decrypt(base64_decode($decoded['k']), $aesKey, $privateKey, OPENSSL_PKCS1_OAEP_PADDING);
        if (!$ok) {
            throw new RuntimeException('RSA key-unwrapping failed — data may be corrupted or tampered with.');
        }

        $plaintext = openssl_decrypt(
            base64_decode($decoded['c']),
            $this->cipher,
            $aesKey,
            OPENSSL_RAW_DATA,
            base64_decode($decoded['iv']),
            base64_decode($decoded['tag'])
        );

        if ($plaintext === false) {
            // GCM auth tag mismatch = tampering or corruption. Fail closed,
            // never return partial/garbage data.
            throw new RuntimeException('Salary field integrity check failed (possible tampering).');
        }

        return $plaintext;
    }

    public function keysConfigured(): bool
    {
        return (bool) $this->publicKeyPem;
    }
}
