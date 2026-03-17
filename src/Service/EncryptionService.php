<?php

declare(strict_types=1);

namespace Photobooth\Service;

use Photobooth\Utility\PathUtility;

class EncryptionService
{
    protected string $key;

    public function __construct()
    {
        $this->key = $this->loadOrCreateKey();
    }

    public function encrypt(string $plaintext): string
    {
        if ($plaintext === '' || $this->isEncrypted($plaintext)) {
            return $plaintext;
        }

        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = sodium_crypto_secretbox($plaintext, $nonce, $this->key);

        return 'enc:' . base64_encode($nonce . $ciphertext);
    }

    public function decrypt(string $value): string
    {
        if (!$this->isEncrypted($value)) {
            return $value;
        }

        $decoded = base64_decode(substr($value, 4), true);
        if ($decoded === false || strlen($decoded) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            return $value;
        }

        $nonce = substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        $plaintext = sodium_crypto_secretbox_open($ciphertext, $nonce, $this->key);
        if ($plaintext === false) {
            return $value;
        }

        return $plaintext;
    }

    public function isEncrypted(string $value): bool
    {
        return str_starts_with($value, 'enc:');
    }

    protected function loadOrCreateKey(): string
    {
        $keyPath = PathUtility::getAbsolutePath('var/run/config_encryption_key');

        if (file_exists($keyPath)) {
            $key = file_get_contents($keyPath);
            if ($key !== false && strlen($key) === SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
                return $key;
            }
        }

        $key = sodium_crypto_secretbox_keygen();
        $dir = dirname($keyPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($keyPath, $key);
        chmod($keyPath, 0600);

        return $key;
    }

    public static function getInstance(): self
    {
        if (!isset($GLOBALS[self::class])) {
            $GLOBALS[self::class] = new self();
        }

        return $GLOBALS[self::class];
    }
}
