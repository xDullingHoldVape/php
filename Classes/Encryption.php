<?php
namespace App\Services;

class Encryption
{
    private const CIPHER = 'AES-256-CBC';
    private const IV_LEN = 16;

    public function generateKey(): string
    {
        return random_bytes(32);
    }

    public function wrapKey(string $rawKey, string $plainPassword): string
    {
        $cipherKey  = hash('sha256', $plainPassword, true);
        $iv         = random_bytes(self::IV_LEN);
        $ciphertext = openssl_encrypt($rawKey, self::CIPHER, $cipherKey, OPENSSL_RAW_DATA, $iv);

        if ($ciphertext === false) {
            throw new \RuntimeException('Key wrapping failed.');
        }

        return base64_encode($iv . $ciphertext);
    }

    public function unwrapKey(string $wrappedKey, string $plainPassword): string
    {
        $cipherKey = hash('sha256', $plainPassword, true);
        $raw       = base64_decode($wrappedKey, strict: true);

        if ($raw === false || strlen($raw) <= self::IV_LEN) {
            throw new \RuntimeException('Malformed wrapped key.');
        }

        $iv         = substr($raw, 0, self::IV_LEN);
        $ciphertext = substr($raw, self::IV_LEN);
        $key        = openssl_decrypt($ciphertext, self::CIPHER, $cipherKey, OPENSSL_RAW_DATA, $iv);

        if ($key === false) {
            throw new \RuntimeException('Key unwrapping failed — wrong password?');
        }

        return $key;
    }

    public function reWrapKey(string $wrappedKey, string $oldPassword, string $newPassword): string
    {
        $rawKey = $this->unwrapKey($wrappedKey, $oldPassword);
        return $this->wrapKey($rawKey, $newPassword);
    }

    public function encrypt(string $plaintext, string $rawKey): string
    {
        $iv         = random_bytes(self::IV_LEN);
        $ciphertext = openssl_encrypt($plaintext, self::CIPHER, $rawKey, OPENSSL_RAW_DATA, $iv);

        if ($ciphertext === false) {
            throw new \RuntimeException('Encryption failed.');
        }

        return base64_encode($iv . $ciphertext);
    }

    public function decrypt(string $encoded, string $rawKey): string
    {
        $raw = base64_decode($encoded, strict: true);

        if ($raw === false || strlen($raw) <= self::IV_LEN) {
            throw new \RuntimeException('Malformed ciphertext.');
        }

        $iv         = substr($raw, 0, self::IV_LEN);
        $ciphertext = substr($raw, self::IV_LEN);
        $plain      = openssl_decrypt($ciphertext, self::CIPHER, $rawKey, OPENSSL_RAW_DATA, $iv);

        if ($plain === false) {
            throw new \RuntimeException('Decryption failed.');
        }

        return $plain;
    }
}