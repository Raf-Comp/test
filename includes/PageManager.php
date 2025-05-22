/**
 * Rejestruje strony administracyjne
 */
public function register_pages() {
    // Strona główna
    add_menu_page(
        __('AI Chat Assistant', 'ai-chat-assistant'),
        __('AI Chat', 'ai-chat-assistant'),
        'manage_options',
        'ai-chat-assistant',
        [$this, 'render_chat_page'],
        'dashicons-format-chat',
        30
    );

    // Historia
    add_submenu_page(
        'ai-chat-assistant',
        __('Historia czatu', 'ai-chat-assistant'),
        __('Historia', 'ai-chat-assistant'),
        'manage_options',
        'ai-chat-assistant-history',
        [$this, 'render_history_page']
    );

    // Ustawienia
    add_submenu_page(
        'ai-chat-assistant',
        __('Ustawienia', 'ai-chat-assistant'),
        __('Ustawienia', 'ai-chat-assistant'),
        'manage_options',
        'ai-chat-assistant-settings',
        [$this, 'render_settings_page']
    );

    // Repozytoria
    add_submenu_page(
        'ai-chat-assistant',
        __('Repozytoria', 'ai-chat-assistant'),
        __('Repozytoria', 'ai-chat-assistant'),
        'manage_options',
        'ai-chat-assistant-repositories',
        [$this, 'render_repositories_page']
    );

    // Diagnostyka
    add_submenu_page(
        'ai-chat-assistant',
        __('Diagnostyka', 'ai-chat-assistant'),
        __('Diagnostyka', 'ai-chat-assistant'),
        'manage_options',
        'ai-chat-assistant-diagnostics',
        [$this, 'render_diagnostics_page']
    );
}

/**
 * Inicjalizacja historii
 */
private function init_history() {
    // Dodaj menu historii
    add_action('admin_menu', function() {
        add_submenu_page(
            'aica-chat',
            __('Chat History', 'aica'),
            __('History', 'aica'),
            'manage_options',
            'aica-history',
            [$this, 'render_history_page']
        );
    });

    // Dodaj obsługę AJAX
    add_action('wp_ajax_aica_get_history', [$this->history_handler, 'get_history']);
    add_action('wp_ajax_aica_clear_history', [$this->history_handler, 'clear_history']);

    // Dodaj skrypty i style
    add_action('admin_enqueue_scripts', function($hook) {
        if ($hook === 'aica-chat_page_aica-history') {
            wp_enqueue_style('aica-history', AICA_URL . 'assets/css/history.css', [], AICA_VERSION);
            wp_enqueue_script('aica-history', AICA_URL . 'assets/js/history.js', ['jquery'], AICA_VERSION, true);
            wp_localize_script('aica-history', 'aica_ajax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('aica_history_nonce')
            ]);
        }
    });
}

/**
 * Renderuje stronę historii
 */
public function render_history_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('Brak uprawnień.', 'ai-chat-assistant'));
    }

    // Załaduj style i skrypty
    wp_enqueue_style('aica-history', AICA_URL . 'assets/css/history.css', [], AICA_VERSION);
    wp_enqueue_script('aica-history', AICA_URL . 'assets/js/history.js', ['jquery'], AICA_VERSION, true);

    // Załaduj szablon
    include AICA_PATH . 'templates/admin/history.php';
}

// ... existing code ... 