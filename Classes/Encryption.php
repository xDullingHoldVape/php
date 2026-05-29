<?php
namespace App\Services;

class Encryption
{
    private const CIPHER = 'AES-256-CBC';
    private const IV_LEN = 16;  // AES block size

    /**
     * Generate a fresh random 32-byte (256-bit) key.
     * Returns the key as a raw binary string.
     */
    public function generateKey(): string
    {
        return random_bytes(32);
    }

    /**
     * Wrap (encrypt) the raw key with the user's plain password.
     */
    public function wrapKey(string $rawKey, string $plainPassword): string
    {
        $cipherKey = hash('sha256', $plainPassword, true);   // 32 bytes
        $iv        = random_bytes(self::IV_LEN);
        $ciphertext = openssl_encrypt($rawKey, self::CIPHER, $cipherKey, OPENSSL_RAW_DATA, $iv);

        if ($ciphertext === false) {
            throw new \RuntimeException('Key wrapping failed.');
        }

        return base64_encode($iv . $ciphertext);
    }

    /**
     * Decrypt the stored key using the user's plain password.
     * Returns the raw binary key, or throws on wrong password.
     */
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

    /**
     * Decrypt the key when the user changes their login password.
     */
    public function reWrapKey(string $wrappedKey, string $oldPassword, string $newPassword): string
    {
        $rawKey = $this->unwrapKey($wrappedKey, $oldPassword);
        return $this->wrapKey($rawKey, $newPassword);
    }

    //Data encryption

    /**
     * Encrypt a plain-text string with the raw binary key.
     * Returns a base64-encoded string.
     */
    public function encrypt(string $plaintext, string $rawKey): string
    {
        $iv         = random_bytes(self::IV_LEN);
        $ciphertext = openssl_encrypt($plaintext, self::CIPHER, $rawKey, OPENSSL_RAW_DATA, $iv);

        if ($ciphertext === false) {
            throw new \RuntimeException('Encryption failed.');
        }

        return base64_encode($iv . $ciphertext);
    }

    /**
     * Decrypt a base64-encoded encrypted blob back to plain text.
     */
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
