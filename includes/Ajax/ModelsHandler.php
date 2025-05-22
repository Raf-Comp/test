<?php
namespace AICA\Ajax;

class ModelsHandler {
    public function __construct() {
        // Rejestracja akcji AJAX dla odświeżania modeli
        add_action('wp_ajax_aica_refresh_models', [$this, 'refresh_models']);
    }
    
    /**
     * Obsługa żądania odświeżenia listy modeli
     */
    public function refresh_models() {
        // Sprawdzenie nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_settings_nonce')) {
            wp_send_json_error([
                'message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdzenie uprawnień
        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('Nie masz uprawnień do wykonania tej operacji.', 'ai-chat-assistant')
            ]);
        }
        
        // Pobranie klucza API
        $api_key = aica_get_option('claude_api_key', '');
        
        if (empty($api_key)) {
            wp_send_json_error([
                'message' => __('Klucz API Claude nie jest skonfigurowany. Najpierw zapisz klucz API.', 'ai-chat-assistant')
            ]);
        }
        
        // Pobranie dostępnych modeli
        $claude_client = new \AICA\API\ClaudeClient($api_key);
        $models = $claude_client->get_available_models();
        
        if (empty($models)) {
            wp_send_json_error([
                'message' => __('Nie udało się pobrać listy modeli. Sprawdź klucz API i spróbuj ponownie.', 'ai-chat-assistant')
            ]);
        }
        
        // Aktualizacja czasu ostatniego odświeżenia
        aica_update_option('claude_models_last_update', current_time('mysql'));
        
        // Zwróć powodzenie
        wp_send_json_success([
            'message' => __('Lista modeli została zaktualizowana pomyślnie.', 'ai-chat-assistant'),
            'models' => $models
        ]);
    }
}