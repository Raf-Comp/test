<?php
/**
 * Service for handling API connections
 */

declare(strict_types=1);

namespace AICA\Services;

use WP_Error;

class ApiService {
    public function __construct(
        private readonly ErrorService $error_service,
        private readonly SettingsService $settings_service
    ) {}

    /**
     * Test Claude API connection
     */
    public function testClaudeConnection(string $token): array|WP_Error {
        try {
            if (empty($token)) {
                throw new \Exception(__('Claude API key is required.', 'ai-chat-assistant'));
            }

            $response = wp_remote_post('https://api.anthropic.com/v1/messages', [
                'headers' => [
                    'x-api-key' => $token,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json'
                ],
                'body' => json_encode([
                    'model' => 'claude-3-haiku-20240307',
                    'max_tokens' => 1,
                    'messages' => [
                        ['role' => 'user', 'content' => 'test']
                    ]
                ]),
                'timeout' => 15
            ]);

            if (is_wp_error($response)) {
                throw new \Exception($response->get_error_message());
            }

            $code = wp_remote_retrieve_response_code($response);
            if ($code !== 200) {
                $body = json_decode(wp_remote_retrieve_body($response), true);
                $message = $body['error']['message'] ?? __('Unknown error occurred.', 'ai-chat-assistant');
                throw new \Exception($message);
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);
            return [
                'model' => $body['model'] ?? 'unknown',
                'status' => 'connected'
            ];
        } catch (\Throwable $e) {
            $this->error_service->logError($e->getMessage());
            return new WP_Error(
                'claude_api_error',
                $e->getMessage()
            );
        }
    }

    /**
     * Test GitHub API connection
     */
    public function testGitHubConnection(string $token): array|WP_Error {
        try {
            if (empty($token)) {
                throw new \Exception(__('GitHub token is required.', 'ai-chat-assistant'));
            }

            $response = wp_remote_get('https://api.github.com/user', [
                'headers' => [
                    'Authorization' => 'token ' . $token,
                    'Accept' => 'application/vnd.github.v3+json'
                ],
                'timeout' => 15
            ]);

            if (is_wp_error($response)) {
                throw new \Exception($response->get_error_message());
            }

            $code = wp_remote_retrieve_response_code($response);
            if ($code !== 200) {
                $body = json_decode(wp_remote_retrieve_body($response), true);
                $message = $body['message'] ?? __('Unknown error occurred.', 'ai-chat-assistant');
                throw new \Exception($message);
            }

            // Get rate limit info
            $rate_limit = wp_remote_get('https://api.github.com/rate_limit', [
                'headers' => [
                    'Authorization' => 'token ' . $token,
                    'Accept' => 'application/vnd.github.v3+json'
                ],
                'timeout' => 15
            ]);

            $rate_limit_body = json_decode(wp_remote_retrieve_body($rate_limit), true);
            $rate_limit_info = $rate_limit_body['resources']['core'] ?? [];

            $body = json_decode(wp_remote_retrieve_body($response), true);
            return [
                'login' => $body['login'] ?? 'unknown',
                'rate_limit' => [
                    'limit' => $rate_limit_info['limit'] ?? 0,
                    'remaining' => $rate_limit_info['remaining'] ?? 0,
                    'reset' => $rate_limit_info['reset'] ?? 0
                ],
                'status' => 'connected'
            ];
        } catch (\Throwable $e) {
            $this->error_service->logError($e->getMessage());
            return new WP_Error(
                'github_api_error',
                $e->getMessage()
            );
        }
    }

    /**
     * Test GitLab API connection
     */
    public function testGitLabConnection(string $token): array|WP_Error {
        try {
            if (empty($token)) {
                throw new \Exception(__('GitLab token is required.', 'ai-chat-assistant'));
            }

            $response = wp_remote_get('https://gitlab.com/api/v4/user', [
                'headers' => [
                    'PRIVATE-TOKEN' => $token
                ],
                'timeout' => 15
            ]);

            if (is_wp_error($response)) {
                throw new \Exception($response->get_error_message());
            }

            $code = wp_remote_retrieve_response_code($response);
            if ($code !== 200) {
                $body = json_decode(wp_remote_retrieve_body($response), true);
                $message = $body['message'] ?? __('Unknown error occurred.', 'ai-chat-assistant');
                throw new \Exception($message);
            }

            // Get rate limit info
            $rate_limit = wp_remote_get('https://gitlab.com/api/v4/rate_limits', [
                'headers' => [
                    'PRIVATE-TOKEN' => $token
                ],
                'timeout' => 15
            ]);

            $rate_limit_body = json_decode(wp_remote_retrieve_body($rate_limit), true);
            $rate_limit_info = $rate_limit_body['rate_limits'] ?? [];

            $body = json_decode(wp_remote_retrieve_body($response), true);
            return [
                'username' => $body['username'] ?? 'unknown',
                'rate_limit' => [
                    'limit' => $rate_limit_info['limit'] ?? 0,
                    'remaining' => $rate_limit_info['remaining'] ?? 0,
                    'reset' => $rate_limit_info['reset'] ?? 0
                ],
                'status' => 'connected'
            ];
        } catch (\Throwable $e) {
            $this->error_service->logError($e->getMessage());
            return new WP_Error(
                'gitlab_api_error',
                $e->getMessage()
            );
        }
    }

    /**
     * Test Bitbucket API connection
     */
    public function testBitbucketConnection(string $token): array|WP_Error {
        try {
            if (empty($token)) {
                throw new \Exception(__('Bitbucket token is required.', 'ai-chat-assistant'));
            }

            $response = wp_remote_get('https://api.bitbucket.org/2.0/user', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token
                ],
                'timeout' => 15
            ]);

            if (is_wp_error($response)) {
                throw new \Exception($response->get_error_message());
            }

            $code = wp_remote_retrieve_response_code($response);
            if ($code !== 200) {
                $body = json_decode(wp_remote_retrieve_body($response), true);
                $message = $body['error']['message'] ?? __('Unknown error occurred.', 'ai-chat-assistant');
                throw new \Exception($message);
            }

            // Get rate limit info
            $rate_limit = wp_remote_get('https://api.bitbucket.org/2.0/rate-limits', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token
                ],
                'timeout' => 15
            ]);

            $rate_limit_body = json_decode(wp_remote_retrieve_body($rate_limit), true);
            $rate_limit_info = $rate_limit_body['rate_limits'] ?? [];

            $body = json_decode(wp_remote_retrieve_body($response), true);
            return [
                'username' => $body['username'] ?? 'unknown',
                'rate_limit' => [
                    'limit' => $rate_limit_info['limit'] ?? 0,
                    'remaining' => $rate_limit_info['remaining'] ?? 0,
                    'reset' => $rate_limit_info['reset'] ?? 0
                ],
                'status' => 'connected'
            ];
        } catch (\Throwable $e) {
            $this->error_service->logError($e->getMessage());
            return new WP_Error(
                'bitbucket_api_error',
                $e->getMessage()
            );
        }
    }

    /**
     * Get Claude API client
     */
    public function getClaudeClient(): \AICA\API\ClaudeClient {
        $api_settings = $this->settings_service->getApiSettings();
        $token = $api_settings['claude_api_key'] ?? '';
        return new \AICA\API\ClaudeClient($token);
    }

    /**
     * Get GitHub API client
     */
    public function getGitHubClient(): \AICA\API\GitHubClient {
        $api_settings = $this->settings_service->getApiSettings();
        $token = $api_settings['github_token'] ?? '';
        return new \AICA\API\GitHubClient($token);
    }

    /**
     * Get GitLab API client
     */
    public function getGitLabClient(): \AICA\API\GitLabClient {
        $api_settings = $this->settings_service->getApiSettings();
        $token = $api_settings['gitlab_token'] ?? '';
        return new \AICA\API\GitLabClient($token);
    }

    /**
     * Get Bitbucket API client
     */
    public function getBitbucketClient(): \AICA\API\BitbucketClient {
        $api_settings = $this->settings_service->getApiSettings();
        $token = $api_settings['bitbucket_token'] ?? '';
        return new \AICA\API\BitbucketClient($token);
    }
} 