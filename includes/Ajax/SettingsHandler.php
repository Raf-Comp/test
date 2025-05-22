<?php
/**
 * Handler for settings AJAX requests
 */

namespace AICA\Ajax;

use AICA\Services\SettingsService;
use AICA\Services\ErrorService;
use AICA\Services\ApiService;

class SettingsHandler {
    private $settings_service;
    private $error_service;
    private $api_service;

    public function __construct(
        SettingsService $settings_service,
        ErrorService $error_service,
        ApiService $api_service
    ) {
        $this->settings_service = $settings_service;
        $this->error_service = $error_service;
        $this->api_service = $api_service;
    }

    /**
     * Initialize AJAX handlers
     */
    public function init() {
        add_action('wp_ajax_aica_save_settings', [$this, 'handleSaveSettings']);
        add_action('wp_ajax_aica_test_api', [$this, 'handleTestApi']);
    }

    /**
     * Handle settings save
     */
    public function handleSaveSettings() {
        try {
            if (!current_user_can('manage_options')) {
                throw new \Exception('Permission denied');
            }

            check_ajax_referer('aica_settings_nonce', 'nonce');

            $settings = $this->sanitizeSettings($_POST['settings'] ?? []);
            $api_settings = $this->sanitizeApiSettings($_POST['api_settings'] ?? []);

            // Save general settings
            if (!$this->settings_service->saveSettings($settings)) {
                throw new \Exception('Failed to save settings');
            }

            // Save API settings
            if (!$this->settings_service->saveApiSettings($api_settings)) {
                throw new \Exception('Failed to save API settings');
            }

            wp_send_json_success([
                'message' => __('Settings saved successfully.', 'ai-chat-assistant')
            ]);
        } catch (\Throwable $e) {
            $this->error_service->logError($e->getMessage());
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle API test
     */
    public function handleTestApi() {
        try {
            if (!current_user_can('manage_options')) {
                throw new \Exception('Permission denied');
            }

            check_ajax_referer('aica_settings_nonce', 'nonce');

            $api_type = sanitize_text_field($_POST['api_type'] ?? '');
            $token = sanitize_text_field($_POST['token'] ?? '');

            if (empty($api_type) || empty($token)) {
                throw new \Exception(__('Missing required parameters.', 'ai-chat-assistant'));
            }

            // Test API connection
            $result = $this->testApiConnection($api_type, $token);

            if (is_wp_error($result)) {
                throw new \Exception($result->get_error_message());
            }

            wp_send_json_success([
                'message' => __('API connection successful.', 'ai-chat-assistant'),
                'details' => $result
            ]);
        } catch (\Throwable $e) {
            $this->error_service->logError($e->getMessage());
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Test API connection
     */
    private function testApiConnection($api_type, $token) {
        switch ($api_type) {
            case 'claude':
                return $this->testClaudeApi($token);

            case 'github':
                return $this->testGitHubApi($token);

            case 'gitlab':
                return $this->testGitLabApi($token);

            case 'bitbucket':
                return $this->testBitbucketApi($token);

            default:
                return new \WP_Error(
                    'invalid_api_type',
                    __('Invalid API type.', 'ai-chat-assistant')
                );
        }
    }

    /**
     * Test Claude API
     */
    private function testClaudeApi($token) {
        try {
            $response = $this->api_service->testClaudeConnection($token);
            
            if (is_wp_error($response)) {
                throw new \Exception($response->get_error_message());
            }

            return [
                'model' => $response['model'] ?? 'unknown',
                'status' => 'connected'
            ];
        } catch (\Throwable $e) {
            return new \WP_Error(
                'claude_api_error',
                $e->getMessage()
            );
        }
    }

    /**
     * Test GitHub API
     */
    private function testGitHubApi($token) {
        try {
            $response = $this->api_service->testGitHubConnection($token);
            
            if (is_wp_error($response)) {
                throw new \Exception($response->get_error_message());
            }

            return [
                'user' => $response['login'] ?? 'unknown',
                'rate_limit' => $response['rate_limit'] ?? 'unknown',
                'status' => 'connected'
            ];
        } catch (\Throwable $e) {
            return new \WP_Error(
                'github_api_error',
                $e->getMessage()
            );
        }
    }

    /**
     * Test GitLab API
     */
    private function testGitLabApi($token) {
        try {
            $response = $this->api_service->testGitLabConnection($token);
            
            if (is_wp_error($response)) {
                throw new \Exception($response->get_error_message());
            }

            return [
                'user' => $response['username'] ?? 'unknown',
                'rate_limit' => $response['rate_limit'] ?? 'unknown',
                'status' => 'connected'
            ];
        } catch (\Throwable $e) {
            return new \WP_Error(
                'gitlab_api_error',
                $e->getMessage()
            );
        }
    }

    /**
     * Test Bitbucket API
     */
    private function testBitbucketApi($token) {
        try {
            $response = $this->api_service->testBitbucketConnection($token);
            
            if (is_wp_error($response)) {
                throw new \Exception($response->get_error_message());
            }

            return [
                'user' => $response['username'] ?? 'unknown',
                'rate_limit' => $response['rate_limit'] ?? 'unknown',
                'status' => 'connected'
            ];
        } catch (\Throwable $e) {
            return new \WP_Error(
                'bitbucket_api_error',
                $e->getMessage()
            );
        }
    }

    /**
     * Sanitize settings
     */
    private function sanitizeSettings($settings) {
        if (!is_array($settings)) {
            return [];
        }

        $sanitized = [];

        // Theme
        if (isset($settings['theme'])) {
            $valid_themes = ['light', 'dark', 'system'];
            $sanitized['theme'] = in_array($settings['theme'], $valid_themes) 
                ? $settings['theme'] 
                : 'light';
        }

        // Message limit
        if (isset($settings['message_limit'])) {
            $sanitized['message_limit'] = absint($settings['message_limit']);
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
                $sanitized[$setting] = (bool) $settings[$setting];
            }
        }

        // Model settings
        if (isset($settings['max_tokens'])) {
            $sanitized['max_tokens'] = absint($settings['max_tokens']);
        }

        if (isset($settings['temperature'])) {
            $sanitized['temperature'] = floatval($settings['temperature']);
        }

        if (isset($settings['model'])) {
            $valid_models = [
                'claude-3-opus-20240229',
                'claude-3-sonnet-20240229',
                'claude-3-haiku-20240307'
            ];
            $sanitized['model'] = in_array($settings['model'], $valid_models) 
                ? $settings['model'] 
                : 'claude-3-opus-20240229';
        }

        return $sanitized;
    }

    /**
     * Sanitize API settings
     */
    private function sanitizeApiSettings($settings) {
        if (!is_array($settings)) {
            return [];
        }

        $sanitized = [];

        // Claude API key
        if (isset($settings['claude_api_key'])) {
            $sanitized['claude_api_key'] = sanitize_text_field($settings['claude_api_key']);
        }

        // GitHub token
        if (isset($settings['github_token'])) {
            $sanitized['github_token'] = sanitize_text_field($settings['github_token']);
        }

        // GitLab token
        if (isset($settings['gitlab_token'])) {
            $sanitized['gitlab_token'] = sanitize_text_field($settings['gitlab_token']);
        }

        // Bitbucket token
        if (isset($settings['bitbucket_token'])) {
            $sanitized['bitbucket_token'] = sanitize_text_field($settings['bitbucket_token']);
        }

        return $sanitized;
    }
}