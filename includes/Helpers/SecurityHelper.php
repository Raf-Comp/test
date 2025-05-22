<?php
/**
 * Helper functions for security and encryption
 *
 * @package AIChatAssistant
 */

if (!defined('ABSPATH')) {
    exit; // Direct access forbidden
}

declare(strict_types=1);

namespace AICA\Helpers;

class SecurityHelper {
    public static function generate_nonce(string $action): string {
        return wp_create_nonce('aica_' . $action);
    }

    public static function verify_nonce(string $nonce, string $action): bool {
        return wp_verify_nonce($nonce, 'aica_' . $action);
    }

    public static function check_capability(string $capability): bool {
        return current_user_can($capability);
    }

    public static function sanitize_input(string $input): string {
        return sanitize_text_field($input);
    }
}

/**
 * Generates encryption key based on AUTH_KEY
 *
 * @return string|false Encryption key or false on error
 */
function aica_get_encryption_key(): string|false {
    if (!defined('AUTH_KEY')) {
        return false;
    }
    
    // Use AUTH_KEY as base for encryption key
    $key = AUTH_KEY;
    
    // Add some entropy
    $key .= wp_salt('auth');
    
    // Generate a consistent key
    return hash('sha256', $key, true);
}

/**
 * Encrypts data using AES-256-CBC
 *
 * @param mixed $data Data to encrypt
 * @return string|false Encrypted data or false on error
 */
function aica_encrypt(mixed $data): string|false {
    $key = aica_get_encryption_key();
    if (!$key) {
        return false;
    }
    
    // Generate IV
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    
    // Encrypt data
    $encrypted = openssl_encrypt(
        maybe_serialize($data),
        'aes-256-cbc',
        $key,
        0,
        $iv
    );
    
    if ($encrypted === false) {
        return false;
    }
    
    // Combine IV and encrypted data
    return base64_encode($iv . $encrypted);
}

/**
 * Decrypts data encrypted with aica_encrypt()
 *
 * @param string $encrypted_data Encrypted data
 * @return mixed|false Decrypted data or false on error
 */
function aica_decrypt(string $encrypted_data): mixed {
    $key = aica_get_encryption_key();
    if (!$key) {
        return false;
    }
    
    // Decode base64
    $data = base64_decode($encrypted_data);
    if ($data === false) {
        return false;
    }
    
    // Extract IV
    $iv_length = openssl_cipher_iv_length('aes-256-cbc');
    $iv = substr($data, 0, $iv_length);
    $encrypted = substr($data, $iv_length);
    
    // Decrypt data
    $decrypted = openssl_decrypt(
        $encrypted,
        'aes-256-cbc',
        $key,
        0,
        $iv
    );
    
    if ($decrypted === false) {
        return false;
    }
    
    return maybe_unserialize($decrypted);
} 