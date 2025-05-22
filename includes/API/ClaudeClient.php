<?php
namespace AICA\API;

class ClaudeClient {
    private $api_key;
    private $api_url = 'https://api.anthropic.com/v1/messages';
    private $api_version = '2023-06-01';

    public function __construct($api_key) {
        $this->api_key = $api_key;
    }

    /**
     * Wysyła wiadomość do API Claude
     * 
     * @param array $messages Lista wiadomości w formacie: [['role' => 'user|assistant', 'content' => 'treść']]
     * @param string $model Model Claude, np. 'claude-3-haiku-20240307'
     * @param int $max_tokens Maksymalna liczba tokenów do wygenerowania
     * @param float $temperature Temperatura generowania (0-1)
     * @return array ['success' => bool, 'message' => string, 'tokens_used' => int]
     */
    public function send_message($messages, $model = 'claude-3-haiku-20240307', $max_tokens = 4000, $temperature = 0.7) {
        if (empty($this->api_key)) {
            return [
                'success' => false,
                'message' => __('Klucz API Claude nie jest skonfigurowany.', 'ai-chat-assistant')
            ];
        }

        // Formatowanie wiadomości dla API
        $data = [
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => $max_tokens,
            'temperature' => $temperature
        ];

        // Logowanie w trybie debugowania
        if (function_exists('aica_log') && aica_get_option('debug_mode', false)) {
            aica_log('Wysyłanie zapytania do API Claude: ' . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        $response = wp_remote_post(
            $this->api_url,
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'x-api-key' => $this->api_key,
                    'anthropic-version' => $this->api_version
                ],
                'body' => json_encode($data),
                'timeout' => 60
            ]
        );

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => $response->get_error_message()
            ];
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        // Logowanie w trybie debugowania
        if (function_exists('aica_log') && aica_get_option('debug_mode', false)) {
            aica_log('Odpowiedź z API Claude: ' . json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        if ($response_code !== 200) {
            $error_message = isset($body['error']['message']) 
                ? $body['error']['message'] 
                : __('Wystąpił błąd podczas komunikacji z API Claude.', 'ai-chat-assistant');
            
            return [
                'success' => false,
                'message' => $error_message
            ];
        }

        // Poprawna odpowiedź
        return [
            'success' => true,
            'message' => $body['content'][0]['text'],
            'tokens_used' => $body['usage']['output_tokens'] ?? 0
        ];
    }

    /**
     * Testuje połączenie z API Claude
     * 
     * @return bool Czy połączenie działa
     */
    public function test_connection() {
        $test_messages = [
            [
                'role' => 'user',
                'content' => 'Odpowiedz krótko: test połączenia'
            ]
        ];

        $response = $this->send_message($test_messages, 'claude-3-haiku-20240307', 20, 0.7);

        return $response['success'];
    }

    /**
     * Pobiera dostępne modele Claude
     * 
     * @return array Lista dostępnych modeli
     */
    public function get_available_models() {
        $models = [
            'claude-3.5-sonnet-20240620' => 'Claude 3.5 Sonnet (2024-06-20)',
            'claude-3-opus-20240229' => 'Claude 3 Opus (2024-02-29)',
            'claude-3-sonnet-20240229' => 'Claude 3 Sonnet (2024-02-29)',
            'claude-3-haiku-20240307' => 'Claude 3 Haiku (2024-03-07)',
        ];

        return $models;
    }
}