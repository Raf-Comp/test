<?php

declare(strict_types=1);

namespace AICA\Services;

class SecurityService {
    private readonly string $encryption_key;
    private readonly array $nonce_actions;

    public function __construct() {
        // Używamy AUTH_KEY z wp-config.php jako podstawy do generowania klucza szyfrowania
        if (!defined('AUTH_KEY')) {
            throw new \RuntimeException('AUTH_KEY is not defined in wp-config.php');
        }
        $this->encryption_key = hash('sha256', AUTH_KEY);
        
        // Inicjalizacja nonce_actions
        $this->nonce_actions = [
            'aica_chat' => 'aica_chat_nonce',
            'aica_settings' => 'aica_settings_nonce',
            'aica_repository' => 'aica_repository_nonce',
            'aica_interface' => 'aica_interface_settings'
        ];
    }

    /**
     * Weryfikuje nonce dla danej akcji
     */
    public function verifyNonce(string $nonce, string $action): bool {
        if (!isset($this->nonce_actions[$action])) {
            return false;
        }
        return wp_verify_nonce($nonce, $this->nonce_actions[$action]);
    }

    /**
     * Generuje nonce dla danej akcji
     */
    public function createNonce(string $action): string|false {
        if (!isset($this->nonce_actions[$action])) {
            return false;
        }
        return wp_create_nonce($this->nonce_actions[$action]);
    }

    /**
     * Szyfruje dane wrażliwe
     */
    public function encrypt(string $data): string {
        if (empty($data)) {
            return '';
        }

        // Generowanie losowego IV
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));

        // Szyfrowanie danych
        $encrypted = openssl_encrypt(
            $data,
            'aes-256-cbc',
            $this->encryption_key,
            0,
            $iv
        );

        if ($encrypted === false) {
            return '';
        }

        // Łączenie IV i zaszyfrowanych danych
        return base64_encode($iv . $encrypted);
    }

    /**
     * Deszyfruje dane wrażliwe
     */
    public function decrypt(string $encrypted_data): string {
        if (empty($encrypted_data)) {
            return '';
        }

        // Dekodowanie danych z base64
        $data = base64_decode($encrypted_data);
        if ($data === false) {
            return '';
        }

        // Pobranie rozmiaru IV
        $iv_length = openssl_cipher_iv_length('aes-256-cbc');
        
        // Ekstrakcja IV i zaszyfrowanych danych
        $iv = substr($data, 0, $iv_length);
        $encrypted = substr($data, $iv_length);

        // Deszyfrowanie danych
        $decrypted = openssl_decrypt(
            $encrypted,
            'aes-256-cbc',
            $this->encryption_key,
            0,
            $iv
        );

        if ($decrypted === false) {
            return '';
        }

        return $decrypted;
    }

    /**
     * Bezpiecznie pobiera klucz API
     */
    public function getApiKey(): string {
        $encrypted_key = aica_get_option('api_key');
        if (empty($encrypted_key)) {
            return '';
        }
        return $this->decrypt($encrypted_key);
    }

    /**
     * Bezpiecznie zapisuje klucz API
     */
    public function saveApiKey(string $api_key): bool {
        if (empty($api_key)) {
            return false;
        }
        $encrypted_key = $this->encrypt($api_key);
        return aica_update_option('api_key', $encrypted_key);
    }

    /**
     * Sanityzuje dane wejściowe
     */
    public function sanitizeInput(mixed $data, string $type = 'text'): mixed {
        return match($type) {
            'text' => sanitize_text_field($data),
            'textarea' => sanitize_textarea_field($data),
            'email' => sanitize_email($data),
            'int' => (int) $data,
            'float' => (float) $data,
            'url' => esc_url_raw($data),
            'html' => wp_kses_post($data),
            default => sanitize_text_field($data)
        };
    }

    /**
     * Sprawdza uprawnienia użytkownika
     */
    public function checkCapability(string $capability = 'manage_options'): bool {
        return current_user_can($capability);
    }

    /**
     * Tworzy nonce dla historii
     */
    public function create_history_nonce(): string {
        return wp_create_nonce('aica_history_nonce');
    }

    /**
     * Weryfikuje nonce historii
     */
    public function verify_history_nonce(string $nonce): bool {
        return wp_verify_nonce($nonce, 'aica_history_nonce');
    }
} 