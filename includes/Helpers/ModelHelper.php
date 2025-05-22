<?php
/**
 * Helper functions for model-related operations
 *
 * @package AIChatAssistant
 */

if (!defined('ABSPATH')) {
    exit; // Direct access forbidden
}

declare(strict_types=1);

namespace AICA\Helpers;

class ModelHelper {
    public static function get_model_name(string $model_id): string {
        return match($model_id) {
            'claude-3-haiku-20240307' => 'Claude 3 Haiku',
            'claude-3-sonnet-20240229' => 'Claude 3 Sonnet',
            'claude-3-opus-20240229' => 'Claude 3 Opus',
            default => 'Unknown Model'
        };
    }

    public static function get_model_description(string $model_id): string {
        return match($model_id) {
            'claude-3-haiku-20240307' => 'Fastest and most compact model, ideal for simple tasks',
            'claude-3-sonnet-20240229' => 'Balanced model with good performance and capabilities',
            'claude-3-opus-20240229' => 'Most capable model, best for complex tasks',
            default => 'Unknown model description'
        };
    }

    public static function get_model_max_tokens(string $model_id): int {
        return match($model_id) {
            'claude-3-haiku-20240307',
            'claude-3-sonnet-20240229',
            'claude-3-opus-20240229' => 200000,
            default => 100000
        };
    }
}

/**
 * Gets model type based on model ID
 *
 * @param string $model_id Model ID
 * @return string Model type (opus, sonnet, haiku, instant, classic)
 */
function aica_get_model_type(string $model_id): string {
    return match(true) {
        str_contains($model_id, 'opus') => 'opus',
        str_contains($model_id, 'sonnet') => 'sonnet',
        str_contains($model_id, 'haiku') => 'haiku',
        str_contains($model_id, 'instant') => 'instant',
        default => 'classic'
    };
}

/**
 * Gets model badge text
 *
 * @param string $model_id Model ID
 * @return string Badge text
 */
function aica_get_model_badge_text(string $model_id): string {
    $type = aica_get_model_type($model_id);
    
    return match($type) {
        'opus' => __('Najpotężniejszy', 'ai-chat-assistant'),
        'sonnet' => str_contains($model_id, '3.5') 
            ? __('Najnowszy', 'ai-chat-assistant')
            : __('Zbalansowany', 'ai-chat-assistant'),
        'haiku' => __('Najszybszy', 'ai-chat-assistant'),
        'instant' => __('Ekonomiczny', 'ai-chat-assistant'),
        default => str_contains($model_id, '2.1')
            ? __('Klasyczny', 'ai-chat-assistant')
            : __('Standard', 'ai-chat-assistant')
    };
}

/**
 * Gets model description
 *
 * @param string $model_id Model ID
 * @return string Model description
 */
function aica_get_model_description(string $model_id): string {
    $type = aica_get_model_type($model_id);
    
    return match($type) {
        'opus' => __('Najwyższej klasy model Claude, oferujący najlepszą dostępną jakość dla najbardziej wymagających zadań.', 'ai-chat-assistant'),
        'sonnet' => str_contains($model_id, '3.5')
            ? __('Najnowszy model Claude z ulepszonymi zdolnościami rozumowania i wykonywania złożonych zadań.', 'ai-chat-assistant')
            : __('Zrównoważona kombinacja inteligencji i szybkości, idealna dla większości zastosowań.', 'ai-chat-assistant'),
        'haiku' => __('Szybki i wydajny model Claude, idealny do prostych zadań i zastosowań w czasie rzeczywistym.', 'ai-chat-assistant'),
        'instant' => __('Ekonomiczny model optymalizowany pod kątem interakcji w czasie rzeczywistym i prostych zadań.', 'ai-chat-assistant'),
        default => str_contains($model_id, '2.1') || str_contains($model_id, '2.0')
            ? __('Klasyczny model Claude drugiej generacji z solidnymi podstawowymi możliwościami.', 'ai-chat-assistant')
            : __('Standardowy model Claude z dobrymi ogólnymi zdolnościami.', 'ai-chat-assistant')
    };
}

/**
 * Gets model power rating
 *
 * @param string $model_id Model ID
 * @return int Rating from 1 to 5
 */
function aica_get_model_power_rating(string $model_id): int {
    $type = aica_get_model_type($model_id);
    
    return match($type) {
        'opus' => 5,
        'sonnet' => 4,
        'haiku' => 3,
        'instant' => 2,
        default => str_contains($model_id, '2.0') ? 2 : 3
    };
}

/**
 * Gets model speed rating
 *
 * @param string $model_id Model ID
 * @return int Rating from 1 to 5
 */
function aica_get_model_speed_rating(string $model_id): int {
    $type = aica_get_model_type($model_id);
    
    return match($type) {
        'opus' => 2,
        'sonnet' => 3,
        'haiku' => 5,
        'instant' => 5,
        default => str_contains($model_id, '2.0') ? 3 : 3
    };
}

/**
 * Generates rating dots HTML
 *
 * @param int $rating Rating from 1 to 5
 * @return string HTML with rating dots
 */
function aica_generate_rating_dots(int $rating): string {
    $dots = '';
    for ($i = 1; $i <= 5; $i++) {
        $class = $i <= $rating ? 'filled' : '';
        $dots .= '<span class="aica-rating-dot ' . $class . '"></span>';
    }
    return $dots;
} 