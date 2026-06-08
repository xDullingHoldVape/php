<?php
namespace App\Services;

// Encryption - Handles data encryption and decryption
class Encryption
{
    // AES-256-CBC encryption method
    private const CIPHER = 'AES-256-CBC';
    private const IV_LEN = 16;

    public function generateKey(): string
    {
        return random_bytes(32);
    }

    // Encrypt the generated key using the user's password, the password is converted into an encryption key using SHA-256
    public function wrapKey(string $rawKey, string $plainPassword): string
    {
        $cipherKey  = hash('sha256', $plainPassword, true);
        $iv         = random_bytes(self::IV_LEN);
        $ciphertext = openssl_encrypt($rawKey, self::CIPHER, $cipherKey, OPENSSL_RAW_DATA, $iv);

        if ($ciphertext === false) {
            throw new \RuntimeException('Key wrapping failed.');
        }
    
        // Store IV together with encrypted data
        return base64_encode($iv . $ciphertext);
    }


    // Decrypt the stored key using the user's password
    public function unwrapKey(string $wrappedKey, string $plainPassword): string
    {

        // Decode Base64 string
        $cipherKey = hash('sha256', $plainPassword, true);
        $raw = base64_decode($wrappedKey, strict: true);

        if ($raw === false || strlen($raw) <= self::IV_LEN) {
            throw new \RuntimeException('Malformed wrapped key.');
        }

        // Extract IV and encrypted key
        $iv = substr($raw, 0, self::IV_LEN);
        $ciphertext = substr($raw, self::IV_LEN);
        $key = openssl_decrypt($ciphertext, self::CIPHER, $cipherKey, OPENSSL_RAW_DATA, $iv);

        if ($key === false) {
            throw new \RuntimeException('Key unwrapping failed — wrong password?');
        }

        return $key;
    }

    // Re-encrypt the same key with a new password
    // Used when a user changes their account password
    public function reWrapKey(string $wrappedKey, string $oldPassword, string $newPassword): string
    {
        $rawKey = $this->unwrapKey($wrappedKey, $oldPassword);
        return $this->wrapKey($rawKey, $newPassword);
    }

    // Encrypt text using the generated encryption key
    public function encrypt(string $plaintext, string $rawKey): string
    {
        $iv = random_bytes(self::IV_LEN);
        $ciphertext = openssl_encrypt($plaintext, self::CIPHER, $rawKey, OPENSSL_RAW_DATA, $iv);

        if ($ciphertext === false) {
            throw new \RuntimeException('Encryption failed.');
        }

        return base64_encode($iv . $ciphertext);
    }

    // Decrypt encrypted text back into readable text
    public function decrypt(string $encoded, string $rawKey): string
    {
        $raw = base64_decode($encoded, strict: true);

        if ($raw === false || strlen($raw) <= self::IV_LEN) {
            throw new \RuntimeException('Malformed ciphertext.');
        }

        // // Split IV and encrypted text
        $iv = substr($raw, 0, self::IV_LEN);
        $ciphertext = substr($raw, self::IV_LEN);
        $plain = openssl_decrypt($ciphertext, self::CIPHER, $rawKey, OPENSSL_RAW_DATA, $iv);

        if ($plain === false) {
            throw new \RuntimeException('Decryption failed.');
        }

        return $plain;
    }
}