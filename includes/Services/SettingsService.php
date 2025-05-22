<?php
declare(strict_types=1);

/**
 * Service for managing plugin settings
 */

namespace AICA\Services;

use AICA\Services\ErrorService;
use AICA\Services\CacheService;
use AICA\Helpers\SecurityHelper;
use AICA\Helpers\ValidationHelper;

class SettingsService {
    private const OPTION_PREFIX = 'aica_';
    private static ?self $instance = null;

    public function __construct(
        private readonly ErrorService $error_service,
        private readonly CacheService $cache_service,
        private string $encryption_key = ''
    ) {
        $this->encryption_key = $this->getEncryptionKey();
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self(
                ErrorService::getInstance(),
                CacheService::getInstance()
            );
        }
        return self::$instance;
    }

    /**
     * Get all settings
     */
    public function getAllSettings(): array {
        return $this->cache_service->remember('all_settings', 3600, function() {
            $defaults = [
                'theme' => 'light',
                'message_limit' => 50,
                'auto_save' => true,
                'notifications' => true,
                'sound_enabled' => true,
                'typing_indicator' => true,
                'markdown_support' => true,
                'code_highlighting' => true,
                'max_tokens' => 2000,
                'temperature' => 0.7,
                'model' => 'claude-3-opus-20240229'
            ];

            $settings = get_option('aica_settings', []);
            return wp_parse_args($settings, $defaults);
        });
    }

    /**
     * Alias for getAllSettings()
     */
    public function getSettings(): array {
        return $this->getAllSettings();
    }

    /**
     * Get API settings
     */
    public function getApiSettings(): array {
        return $this->cache_service->remember('api_settings', 3600, function() {
            $encrypted_settings = get_option('aica_api_settings', []);
            $settings = [];

            foreach ($encrypted_settings as $key => $value) {
                if (!empty($value)) {
                    $settings[$key] = $this->decrypt($value);
                }
            }

            return $settings;
        });
    }

    /**
     * Save settings
     */
    public function saveSettings(array $settings): bool {
        // Validate settings
        $validated_settings = $this->validateSettings($settings);
        if (is_wp_error($validated_settings)) {
            $this->error_service->logError($validated_settings->get_error_message());
            return false;
        }

        $result = update_option('aica_settings', $validated_settings);
        
        if ($result) {
            $this->cache_service->delete('all_settings');
        }

        return $result;
    }

    /**
     * Save API settings
     */
    public function saveApiSettings(array $settings): bool {
        $encrypted_settings = [];
        foreach ($settings as $key => $value) {
            if (!empty($value)) {
                // Validate token format
                if (!$this->validateToken($key, $value)) {
                    $this->error_service->logError("Invalid token format for {$key}");
                    continue;
                }
                $encrypted_settings[$key] = $this->encrypt($value);
            }
        }

        $result = update_option('aica_api_settings', $encrypted_settings);
        
        if ($result) {
            $this->cache_service->delete('api_settings');
        }

        return $result;
    }

    /**
     * Validate settings
     */
    private function validateSettings(array $settings): array|\WP_Error {
        $validated = [];

        // Theme
        if (isset($settings['theme'])) {
            $valid_themes = ['light', 'dark', 'system'];
            $validated['theme'] = in_array($settings['theme'], $valid_themes) 
                ? $settings['theme'] 
                : 'light';
        }

        // Message limit
        if (isset($settings['message_limit'])) {
            $limit = (int) $settings['message_limit'];
            $validated['message_limit'] = max(10, min(1000, $limit));
        }

        // Boolean settings
        $boolean_settings = [
            'auto_save',
            'notifications',
            'sound_enabled',
            'typing_indicator',
            'markdown_support',
            'code_highlighting'
        ];

        foreach ($boolean_settings as $setting) {
            if (isset($settings[$setting])) {
                $validated[$setting] = (bool) $settings[$setting];
            }
        }

        // Model settings
        if (isset($settings['max_tokens'])) {
            $tokens = (int) $settings['max_tokens'];
            $validated['max_tokens'] = max(100, min(4000, $tokens));
        }

        if (isset($settings['temperature'])) {
            $temp = (float) $settings['temperature'];
            $validated['temperature'] = max(0.1, min(1.0, $temp));
        }

        if (isset($settings['model'])) {
            $valid_models = [
                'claude-3-opus-20240229',
                'claude-3-sonnet-20240229',
                'claude-3-haiku-20240307'
            ];
            $validated['model'] = in_array($settings['model'], $valid_models) 
                ? $settings['model'] 
                : 'claude-3-opus-20240229';
        }

        return $validated;
    }

    /**
     * Validate token format
     */
    private function validateToken(string $type, string $token): bool {
        return match($type) {
            'claude_api_key' => preg_match('/^sk-[a-zA-Z0-9]{47}$/', $token),
            'github_token' => preg_match('/^[a-zA-Z0-9]{40}$/', $token),
            'gitlab_token' => preg_match('/^[a-zA-Z0-9]{20}$/', $token),
            'bitbucket_token' => preg_match('/^[a-zA-Z0-9]{32}$/', $token),
            default => false
        };
    }

    /**
     * Get encryption key
     */
    private function getEncryptionKey(): string {
        $key = get_option('aica_encryption_key');
        
        if (!$key) {
            $key = wp_generate_password(64, true, true);
            update_option('aica_encryption_key', $key);
        }

        return $key;
    }

    /**
     * Encrypt data
     */
    private function encrypt(string $data): string {
        if (empty($data)) {
            return '';
        }

        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt(
            $data,
            'aes-256-cbc',
            $this->encryption_key,
            0,
            $iv
        );

        if ($encrypted === false) {
            $this->error_service->logError('Encryption failed');
            return '';
        }

        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt data
     */
    private function decrypt(string $data): string {
        if (empty($data)) {
            return '';
        }

        $data = base64_decode($data);
        $iv_length = openssl_cipher_iv_length('aes-256-cbc');
        $iv = substr($data, 0, $iv_length);
        $encrypted = substr($data, $iv_length);

        $decrypted = openssl_decrypt(
            $encrypted,
            'aes-256-cbc',
            $this->encryption_key,
            0,
            $iv
        );

        if ($decrypted === false) {
            $this->error_service->logError('Decryption failed');
            return '';
        }

        return $decrypted;
    }

    /**
     * Rotate encryption key
     */
    public function rotateEncryptionKey(): bool {
        $old_key = $this->encryption_key;
        $new_key = wp_generate_password(64, true, true);

        // Get all encrypted settings
        $encrypted_settings = get_option('aica_api_settings', []);
        $decrypted_settings = [];

        // Decrypt with old key
        foreach ($encrypted_settings as $key => $value) {
            if (!empty($value)) {
                $decrypted_settings[$key] = $this->decrypt($value);
            }
        }

        // Update encryption key
        $this->encryption_key = $new_key;
        update_option('aica_encryption_key', $new_key);

        // Re-encrypt with new key
        $new_encrypted_settings = [];
        foreach ($decrypted_settings as $key => $value) {
            if (!empty($value)) {
                $new_encrypted_settings[$key] = $this->encrypt($value);
            }
        }

        // Save re-encrypted settings
        return update_option('aica_api_settings', $new_encrypted_settings);
    }

    /**
     * Get available Claude models
     */
    public function getAvailableClaudeModels(): array {
        return [
            'claude-3-opus-20240229' => [
                'name' => 'Claude 3 Opus',
                'description' => __('Most powerful model, best for complex tasks', 'ai-chat-assistant'),
                'max_tokens' => 4096,
                'price' => 0.015
            ],
            'claude-3-sonnet-20240229' => [
                'name' => 'Claude 3 Sonnet',
                'description' => __('Balanced model for most tasks', 'ai-chat-assistant'),
                'max_tokens' => 4096,
                'price' => 0.003
            ],
            'claude-3-haiku-20240307' => [
                'name' => 'Claude 3 Haiku',
                'description' => __('Fastest model, best for simple tasks', 'ai-chat-assistant'),
                'max_tokens' => 4096,
                'price' => 0.00025
            ]
        ];
    }

    /**
     * Get chat settings
     */
    public function getChatSettings(): array {
        return $this->cache_service->remember('chat_settings', 3600, function() {
            $defaults = [
                'theme' => 'light',
                'message_limit' => 50,
                'auto_save' => true,
                'notifications' => true,
                'sound_enabled' => true,
                'typing_indicator' => true,
                'markdown_support' => true,
                'code_highlighting' => true,
                'max_tokens' => 2000,
                'temperature' => 0.7,
                'model' => 'claude-3-opus-20240229'
            ];

            $settings = get_option('aica_chat_settings', []);
            return wp_parse_args($settings, $defaults);
        });
    }

    /**
     * Validate chat settings
     */
    private function validateChatSettings(array $settings): array|\WP_Error {
        $validated = [];

        // Theme
        if (isset($settings['theme'])) {
            $valid_themes = ['light', 'dark', 'system'];
            $validated['theme'] = in_array($settings['theme'], $valid_themes) 
                ? $settings['theme'] 
                : 'light';
        }

        // Message limit
        if (isset($settings['message_limit'])) {
            $limit = (int) $settings['message_limit'];
            $validated['message_limit'] = max(10, min(1000, $limit));
        }

        // Boolean settings
        $boolean_settings = [
            'auto_save',
            'notifications',
            'sound_enabled',
            'typing_indicator',
            'markdown_support',
            'code_highlighting'
        ];

        foreach ($boolean_settings as $setting) {
            if (isset($settings[$setting])) {
                $validated[$setting] = (bool) $settings[$setting];
            }
        }

        // Model settings
        if (isset($settings['max_tokens'])) {
            $tokens = (int) $settings['max_tokens'];
            $validated['max_tokens'] = max(100, min(4000, $tokens));
        }

        if (isset($settings['temperature'])) {
            $temp = (float) $settings['temperature'];
            $validated['temperature'] = max(0.1, min(1.0, $temp));
        }

        if (isset($settings['model'])) {
            $valid_models = [
                'claude-3-opus-20240229',
                'claude-3-sonnet-20240229',
                'claude-3-haiku-20240307'
            ];
            $validated['model'] = in_array($settings['model'], $valid_models) 
                ? $settings['model'] 
                : 'claude-3-opus-20240229';
        }

        return $validated;
    }

    /**
     * Save chat settings
     */
    public function saveChatSettings(array $settings): bool {
        // Validate settings
        $validated_settings = $this->validateChatSettings($settings);
        if (is_wp_error($validated_settings)) {
            $this->error_service->logError($validated_settings->get_error_message());
            return false;
        }

        $result = update_option('aica_chat_settings', $validated_settings);
        
        if ($result) {
            $this->cache_service->delete('chat_settings');
        }

        return $result;
    }

    /**
     * Check rate limit
     */
    private function checkRateLimit(int $user_id): bool {
        $limit = 100; // messages per hour
        $key = "rate_limit_{$user_id}";
        
        $count = (int) get_transient($key);
        if ($count === false) {
            set_transient($key, 1, HOUR_IN_SECONDS);
            return true;
        }
        
        if ($count >= $limit) {
            return false;
        }
        
        set_transient($key, $count + 1, HOUR_IN_SECONDS);
        return true;
    }

    /**
     * Get setting
     */
    public function get_setting(string $key, mixed $default = null): mixed {
        $settings = $this->getAllSettings();
        return $settings[$key] ?? $default;
    }

    /**
     * Update setting
     */
    public function update_setting(string $key, mixed $value): bool {
        $settings = $this->getAllSettings();
        $settings[$key] = $value;
        return $this->saveSettings($settings);
    }

    /**
     * Delete setting
     */
    public function delete_setting(string $key): bool {
        $settings = $this->getAllSettings();
        unset($settings[$key]);
        return $this->saveSettings($settings);
    }

    /**
     * Get API key
     */
    public function get_api_key(): ?string {
        $settings = $this->getApiSettings();
        return $settings['claude_api_key'] ?? null;
    }

    /**
     * Update API key
     */
    public function update_api_key(string $key): bool {
        $settings = $this->getApiSettings();
        $settings['claude_api_key'] = $key;
        return $this->saveApiSettings($settings);
    }

    /**
     * Get model
     */
    public function get_model(): string {
        return $this->get_setting('model', 'claude-3-opus-20240229');
    }

    /**
     * Update model
     */
    public function update_model(string $model): bool {
        return $this->update_setting('model', $model);
    }

    /**
     * Get temperature
     */
    public function get_temperature(): float {
        return (float) $this->get_setting('temperature', 0.7);
    }

    /**
     * Update temperature
     */
    public function update_temperature(float $temperature): bool {
        return $this->update_setting('temperature', $temperature);
    }

    /**
     * Get max tokens
     */
    public function get_max_tokens(): int {
        return (int) $this->get_setting('max_tokens', 2000);
    }

    /**
     * Update max tokens
     */
    public function update_max_tokens(int $tokens): bool {
        return $this->update_setting('max_tokens', $tokens);
    }
} 