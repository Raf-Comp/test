<?php
namespace AICA\Admin;

use AICA\Services\SecurityService;
use AICA\Services\ApiService;

class SettingsPage {
    private $nonce_action = 'aica_settings_nonce';
    private $nonce_name = 'aica_settings_nonce';
    private $security_service;
    private $api_service;

    public function __construct() {
        $this->security_service = new SecurityService();
        $this->api_service = new ApiService();
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Rejestracja ustawień
     */
    public function register_settings() {
        register_setting('aica_settings', 'aica_settings', array(
            'sanitize_callback' => array($this, 'sanitize_settings')
        ));
    }

    /**
     * Sanityzacja ustawień
     */
    public function sanitize_settings($input) {
        $this->security_service->verify_nonce($this->nonce_name, $this->nonce_action);
        $this->security_service->check_user_capability();

        $sanitized = array();
        
        if (isset($input['claude_api_key'])) {
            $sanitized['claude_api_key'] = sanitize_text_field($input['claude_api_key']);
        }
        
        if (isset($input['selected_model'])) {
            $sanitized['selected_model'] = sanitize_text_field($input['selected_model']);
        }
        
        if (isset($input['message_limit'])) {
            $sanitized['message_limit'] = absint($input['message_limit']);
        }
        
        if (isset($input['theme'])) {
            $sanitized['theme'] = sanitize_text_field($input['theme']);
        }

        return $sanitized;
    }
    
    /**
     * Renderowanie strony ustawień
     */
    public function render() {
        // Sprawdzenie uprawnień
        $this->security_service->check_user_capability();
        
        // Tworzenie instancji klienta Claude do pobrania dostępnych modeli
        $api_key = aica_get_option('claude_api_key', '');
        $available_models = aica_get_option('claude_available_models', []);
        
        // Jeśli nie ma zapisanych modeli w opcjach lub gdy API key jest ustawiony
        if (empty($available_models) && !empty($api_key)) {
            $models = $this->api_service->get_available_models();
            if (!empty($models)) {
                $available_models = $models;
            }
        } elseif (empty($available_models)) {
            // Domyślna lista modeli, jeśli nie ma ani zapisanych ani API key
            $available_models = [
                'claude-3.5-sonnet-20240620' => 'Claude 3.5 Sonnet (2024-06-20)',
                'claude-3-opus-20240229' => 'Claude 3 Opus (2024-02-29)',
                'claude-3-sonnet-20240229' => 'Claude 3 Sonnet (2024-02-29)',
                'claude-3-haiku-20240307' => 'Claude 3 Haiku (2024-03-07)',
                'claude-2.1' => 'Claude 2.1',
                'claude-2.0' => 'Claude 2.0',
                'claude-instant-1.2' => 'Claude Instant 1.2'
            ];
        }

        // Dodanie nonce do formularza
        wp_nonce_field($this->nonce_action, $this->nonce_name);
        
        // Renderowanie szablonu
        include AICA_PLUGIN_DIR . 'templates/admin/settings.php';
    }
    
    /**
     * Zwraca tekst etykiety modelu
     */
    public function get_model_badge_text($model_id) {
        return $this->security_service->display_text(aica_get_model_badge_text($model_id), false);
    }
    
    /**
     * Zwraca opis modelu
     */
    public function get_model_description($model_id) {
        return $this->security_service->display_text(aica_get_model_description($model_id), false);
    }
    
    /**
     * Zwraca ocenę mocy modelu w postaci kropek
     */
    public function get_model_power_rating($model_id) {
        $rating = aica_get_model_power_rating($model_id);
        return $this->security_service->display_html(aica_generate_rating_dots($rating), false);
    }
    
    /**
     * Zwraca ocenę szybkości modelu w postaci kropek
     */
    public function get_model_speed_rating($model_id) {
        $rating = aica_get_model_speed_rating($model_id);
        return $this->security_service->display_html(aica_generate_rating_dots($rating), false);
    }
}