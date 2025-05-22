<?php
namespace AICA\Admin;


class PageManager {
    public function register_menu() {
        add_menu_page(
            __('AI Chat Assistant', 'ai-chat-assistant'),
            __('AI Chat', 'ai-chat-assistant'),
            'manage_options',
            'ai-chat-assistant',
            [$this, 'render_chat_page'],
            'dashicons-format-chat',
            30
        );

        add_submenu_page(
            'ai-chat-assistant',
            __('Repozytoria', 'ai-chat-assistant'),
            __('Repozytoria', 'ai-chat-assistant'),
            'manage_options',
            'ai-chat-assistant-repositories',
            [$this, 'render_repositories_page']
        );

        add_submenu_page(
            'ai-chat-assistant',
            __('Historia', 'ai-chat-assistant'),
            __('Historia', 'ai-chat-assistant'),
            'manage_options',
            'ai-chat-assistant-history',
            [$this, 'render_history_page']
        );

        add_submenu_page(
            'ai-chat-assistant',
            __('Ustawienia', 'ai-chat-assistant'),
            __('Ustawienia', 'ai-chat-assistant'),
            'manage_options',
            'ai-chat-assistant-settings',
            [$this, 'render_settings_page']
        );

        add_submenu_page(
            'ai-chat-assistant',
            __('Diagnostyka', 'ai-code-companion'),
            __('Diagnostyka', 'ai-code-companion'),
            'manage_options',
            'ai-chat-assistant-diagnostics',
            [$this, 'render_diagnostics_page']
        );
    }

    public function render_chat_page() {
        $chat = new ChatPage();
        $chat->render();
    }

    public function render_repositories_page() {
        $repositories = new RepositoriesPage();
        $repositories->render();
    }

    public function render_history_page() {
        $history = new HistoryPage();
        $history->render();
    }

    public function render_settings_page() {
        $settings = new SettingsPage();
        $settings->render();
    }

    public function render_diagnostics_page() {
        // Dodaj załadowanie pliku CSS bezpośrednio przed renderowaniem strony
        wp_enqueue_style(
            'aica-diagnostics-css',
            AICA_PLUGIN_URL . 'assets/css/diagnostics.css',
            [],
            AICA_VERSION
        );
        
        $diagnostics = new DiagnosticsPage();
        $diagnostics->render();
    

    }

    public function init(): void {
        add_action('admin_menu', [$this, 'register_menu']);
    }
}
