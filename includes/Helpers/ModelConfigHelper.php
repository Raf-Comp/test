<?php
declare(strict_types=1);

namespace AIChatAssistant\Helpers;

/**
 * Klasa pomocnicza do konfiguracji modeli
 * 
 * @package AIChatAssistant
 * @since 1.0.0
 */
class ModelConfigHelper {
    /**
     * Pobiera konfigurację modelu
     *
     * @param string $model_name Nazwa modelu
     * @return array{
     *     name: string,
     *     max_tokens: int,
     *     temperature: float,
     *     top_p: float,
     *     frequency_penalty: float,
     *     presence_penalty: float
     * }
     */
    public static function get_model_config(string $model_name): array {
        return match($model_name) {
            'claude-3-haiku-20240307' => [
                'name' => 'Claude 3 Haiku',
                'max_tokens' => 200_000,
                'temperature' => 0.7,
                'top_p' => 1.0,
                'frequency_penalty' => 0.0,
                'presence_penalty' => 0.0
            ],
            'claude-3-sonnet-20240229' => [
                'name' => 'Claude 3 Sonnet',
                'max_tokens' => 200_000,
                'temperature' => 0.7,
                'top_p' => 1.0,
                'frequency_penalty' => 0.0,
                'presence_penalty' => 0.0
            ],
            'claude-3-opus-20240229' => [
                'name' => 'Claude 3 Opus',
                'max_tokens' => 200_000,
                'temperature' => 0.7,
                'top_p' => 1.0,
                'frequency_penalty' => 0.0,
                'presence_penalty' => 0.0
            ],
            default => [
                'name' => 'Claude 3 Haiku',
                'max_tokens' => 200_000,
                'temperature' => 0.7,
                'top_p' => 1.0,
                'frequency_penalty' => 0.0,
                'presence_penalty' => 0.0
            ]
        };
    }

    /**
     * Pobiera listę dostępnych modeli
     *
     * @return array<string, string> Tablica modeli w formacie [id => nazwa]
     */
    public static function get_available_models(): array {
        return [
            'claude-3-haiku-20240307' => 'Claude 3 Haiku',
            'claude-3-sonnet-20240229' => 'Claude 3 Sonnet',
            'claude-3-opus-20240229' => 'Claude 3 Opus'
        ];
    }

    /**
     * Sprawdza czy model jest dostępny
     *
     * @param string $model_name Nazwa modelu
     * @return bool True jeśli model jest dostępny, false w przeciwnym razie
     */
    public static function is_model_available(string $model_name): bool {
        return array_key_exists($model_name, self::get_available_models());
    }
} 