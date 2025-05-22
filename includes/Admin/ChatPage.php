<?php
namespace AICA\Admin;

use AICA\Services\ChatService;
use AICA\Services\RepositoryService;

// Dodanie użycia globalnych funkcji
use function aica_is_api_configured;
use function aica_get_current_model;
use function aica_get_available_models;
use function aica_get_supported_languages;
use function aica_get_option;

class ChatPage {
    private $chat_service;
    private $repo_service;

    public function __construct() {
        $this->chat_service = new ChatService();
        $this->repo_service = new RepositoryService();
        
        // Dodanie stylów i skryptów specyficznych dla strony czatu
        add_action('admin_enqueue_scripts', [$this, 'enqueue_chat_assets']);
    }

    public function enqueue_chat_assets($hook) {
        if ($hook != 'toplevel_page_ai-chat-assistant') {
            return;
        }

        // Załadowanie Prism.js do podświetlania składni
        wp_enqueue_style(
            'prism',
            AICA_PLUGIN_URL . 'assets/css/vendor/prism.min.css',
            [],
            AICA_VERSION
        );

        wp_enqueue_script(
            'prism',
            AICA_PLUGIN_URL . 'assets/js/vendor/prism.min.js',
            [],
            AICA_VERSION,
            true
        );

        // Podstawowe styles admina
        wp_enqueue_style(
            'aica-admin',
            AICA_PLUGIN_URL . 'assets/css/admin.css',
            [],
            AICA_VERSION
        );

        // Nowoczesny styl czatu
        wp_enqueue_style(
            'aica-modern-chat',
            AICA_PLUGIN_URL . 'assets/css/modern-chat.css',
            ['aica-admin', 'prism'],
            AICA_VERSION
        );

        // Dodatkowe styles czatu
        wp_enqueue_style(
            'aica-chat',
            AICA_PLUGIN_URL . 'assets/css/chat.css',
            ['aica-modern-chat'],
            AICA_VERSION
        );

        // Nowoczesny skrypt czatu
        wp_enqueue_script(
            'aica-modern-chat',
            AICA_PLUGIN_URL . 'assets/js/modern-chat.js',
            ['jquery', 'prism'],
            AICA_VERSION,
            true
        );

        // Załadowanie dashicons z WP
        wp_enqueue_style('dashicons');

        // Pobierz dostępne modele
        $available_models = \aica_get_available_models();

        // Dane dla skryptu
        $current_user_id = get_current_user_id();
        $chat_sessions = $this->chat_service->get_user_sessions($current_user_id);
        $repositories = $this->repo_service->get_saved_repositories($current_user_id);

        wp_localize_script('aica-modern-chat', 'aica_data', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aica_nonce'),
            'sessions' => $chat_sessions,
            'repositories' => $repositories,
            'settings' => [
                'claude_model' => \aica_get_current_model(),
                'claude_api_key' => \aica_is_api_configured(),
                'dark_mode' => \aica_get_option('dark_mode', false),
                'compact_mode' => \aica_get_option('compact_mode', false),
                'max_tokens' => get_option('aica_max_tokens', 4000),
                'available_models' => $available_models
            ]
        ]);
    }

    public function render() {
        // Sprawdzenie, czy klucz API Claude jest skonfigurowany
        $api_configured = \aica_is_api_configured();
        
        // Pobierz aktualnie wybrany model i dostępne modele
        $current_model = \aica_get_current_model();
        $available_models = \aica_get_available_models();
        
        // Języki programowania wspierane przez podświetlanie składni
        $supported_languages = \aica_get_supported_languages();

        include AICA_PLUGIN_DIR . 'templates/admin/chat.php';
    }
}