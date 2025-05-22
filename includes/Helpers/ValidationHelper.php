<?php
/**
 * Helper functions for data validation
 *
 * @package AIChatAssistant
 */

if (!defined('ABSPATH')) {
    exit; // Direct access forbidden
}

declare(strict_types=1);

namespace AICA\Helpers;

use WP_Error;

class ValidationHelper {
    public static function validate_email(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function validate_url(string $url): bool {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    public static function validate_api_key(string $key): bool {
        return strlen($key) >= 32 && preg_match('/^[a-zA-Z0-9_-]+$/', $key);
    }

    public static function validate_model_id(string $model_id): bool {
        $valid_models = [
            'claude-3-haiku-20240307',
            'claude-3-sonnet-20240229',
            'claude-3-opus-20240229'
        ];
        return in_array($model_id, $valid_models, true);
    }
}

/**
 * Sanitizes input data based on type
 *
 * @param mixed $data Data to sanitize
 * @param string $type Type of data (text, email, int, float, bool, array)
 * @return mixed Sanitized data
 */
function aica_sanitize_input(mixed $data, string $type = 'text'): mixed {
    return match($type) {
        'email' => sanitize_email($data),
        'int' => intval($data),
        'float' => floatval($data),
        'bool' => (bool) $data,
        'array' => is_array($data) ? array_map('sanitize_text_field', $data) : [],
        default => sanitize_text_field($data)
    };
}

/**
 * Validates settings data
 *
 * @param array $settings Settings to validate
 * @return array|WP_Error Validated settings or WP_Error on failure
 */
function aica_validate_settings(array $settings): array|WP_Error {
    $validated = [];
    $errors = [];
    
    // Validate API settings
    if (isset($settings['api_key'])) {
        if (empty($settings['api_key'])) {
            $errors[] = 'API key is required';
        } else {
            $validated['api_key'] = aica_sanitize_input($settings['api_key']);
        }
    }
    
    // Validate model settings
    if (isset($settings['model'])) {
        $valid_models = ['gpt-3.5-turbo', 'gpt-4'];
        if (!in_array($settings['model'], $valid_models)) {
            $errors[] = 'Invalid model selected';
        } else {
            $validated['model'] = $settings['model'];
        }
    }
    
    // Validate temperature
    if (isset($settings['temperature'])) {
        $temp = floatval($settings['temperature']);
        if ($temp < 0 || $temp > 2) {
            $errors[] = 'Temperature must be between 0 and 2';
        } else {
            $validated['temperature'] = $temp;
        }
    }
    
    // Validate max tokens
    if (isset($settings['max_tokens'])) {
        $tokens = intval($settings['max_tokens']);
        if ($tokens < 1 || $tokens > 4000) {
            $errors[] = 'Max tokens must be between 1 and 4000';
        } else {
            $validated['max_tokens'] = $tokens;
        }
    }
    
    // Return errors if any
    if (!empty($errors)) {
        return new WP_Error('validation_error', implode(', ', $errors));
    }
    
    return $validated;
}

/**
 * Validates user data
 *
 * @param array $user_data User data to validate
 * @return array|WP_Error Validated user data or WP_Error on failure
 */
function aica_validate_user_data(array $user_data): array|WP_Error {
    $validated = [];
    $errors = [];
    
    // Validate username
    if (isset($user_data['username'])) {
        if (empty($user_data['username'])) {
            $errors[] = 'Username is required';
        } else {
            $validated['username'] = aica_sanitize_input($user_data['username']);
        }
    }
    
    // Validate email
    if (isset($user_data['email'])) {
        if (!is_email($user_data['email'])) {
            $errors[] = 'Invalid email address';
        } else {
            $validated['email'] = aica_sanitize_input($user_data['email'], 'email');
        }
    }
    
    // Validate role
    if (isset($user_data['role'])) {
        $valid_roles = ['administrator', 'editor', 'author', 'contributor', 'subscriber'];
        if (!in_array($user_data['role'], $valid_roles)) {
            $errors[] = 'Invalid role selected';
        } else {
            $validated['role'] = $user_data['role'];
        }
    }
    
    // Return errors if any
    if (!empty($errors)) {
        return new WP_Error('validation_error', implode(', ', $errors));
    }
    
    return $validated;
} 