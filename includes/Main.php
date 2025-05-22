<?php
declare(strict_types=1);

namespace AICA;

use AICA\Admin\PageManager;
use AICA\Services\SecurityService;
use AICA\Services\ApiService;
use AICA\Services\RepositoryService;
use AICA\Services\SessionService;
use AICA\Services\MessageService;
use AICA\Services\CleanupService;
use AICA\Services\UpdateService;

/**
 * Główna klasa wtyczki
 */
class Main {
    /**
     * Konstruktor
     */
    public function __construct(
        private readonly PageManager $page_manager,
        private readonly SecurityService $security_service,
        private readonly ApiService $api_service,
        private readonly RepositoryService $repository_service,
        private readonly SessionService $session_service,
        private readonly MessageService $message_service,
        private readonly CleanupService $cleanup_service,
        private readonly UpdateService $update_service
    ) {}

    /**
     * Inicjalizacja wtyczki
     */
    public function init(): void {
        // Inicjalizacja menedżera stron
        $this->page_manager->init();

        // Rejestracja hooków
        $this->register_hooks();

        // Inicjalizacja AJAX
        $this->init_ajax();

        // Inicjalizacja REST API
        $this->init_rest_api();

        // Inicjalizacja widgetów
        $this->init_widgets();

        // Inicjalizacja shortcode'ów
        $this->init_shortcodes();
    }

    /**
     * Rejestracja hooków
     */
    private function register_hooks(): void {
        // Akcje
        add_action('init', [$this, 'init_plugin']);
        add_action('admin_init', [$this, 'admin_init']);
        add_action('wp_loaded', [$this, 'frontend_init']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);

        // Filtry
        add_filter('plugin_action_links_' . AICA_PLUGIN_BASENAME, [$this, 'add_action_links']);
        add_filter('plugin_row_meta', [$this, 'add_meta_links'], 10, 2);

        // Cron jobs
        add_action('aica_daily_cleanup', [$this->cleanup_service, 'cleanup_old_data']);
        add_action('aica_check_updates', [$this->update_service, 'check_for_updates']);
    }

    /**
     * Inicjalizacja AJAX
     */
    private function init_ajax(): void {
        // Chat
        add_action('wp_ajax_aica_send_message', [$this, 'handle_send_message']);
        add_action('wp_ajax_aica_get_messages', [$this, 'handle_get_messages']);
        add_action('wp_ajax_aica_clear_chat', [$this, 'handle_clear_chat']);

        // Historia
        add_action('wp_ajax_aica_get_history', [$this, 'handle_get_history']);
        add_action('wp_ajax_aica_delete_history', [$this, 'handle_delete_history']);
        add_action('wp_ajax_aica_export_history', [$this, 'handle_export_history']);

        // Repozytoria
        add_action('wp_ajax_aica_add_repository', [$this, 'handle_add_repository']);
        add_action('wp_ajax_aica_delete_repository', [$this, 'handle_delete_repository']);
        add_action('wp_ajax_aica_refresh_repository', [$this, 'handle_refresh_repository']);
        add_action('wp_ajax_aica_get_repository_files', [$this, 'handle_get_repository_files']);
        add_action('wp_ajax_aica_get_file_content', [$this, 'handle_get_file_content']);

        // Ustawienia
        add_action('wp_ajax_aica_save_settings', [$this, 'handle_save_settings']);
        add_action('wp_ajax_aica_test_api', [$this, 'handle_test_api']);

        // Diagnostyka
        add_action('wp_ajax_aica_get_diagnostics', [$this, 'handle_get_diagnostics']);
    }

    /**
     * Inicjalizacja REST API
     */
    private function init_rest_api(): void {
        add_action('rest_api_init', function(): void {
            $api = new \AICA\API\RestApi();
            $api->register_routes();
        });
    }

    /**
     * Inicjalizacja widgetów
     */
    private function init_widgets(): void {
        add_action('widgets_init', function(): void {
            register_widget(\AICA\Widgets\ChatWidget::class);
        });
    }

    /**
     * Inicjalizacja shortcode'ów
     */
    private function init_shortcodes(): void {
        add_shortcode('aica_chat', [$this, 'render_chat_shortcode']);
    }

    /**
     * Inicjalizacja wtyczki
     */
    public function init_plugin(): void {
        // Załaduj pliki tłumaczeń
        load_plugin_textdomain('ai-chat-assistant', false, dirname(AICA_PLUGIN_BASENAME) . '/languages');

        // Inicjalizacja sesji
        if (!session_id()) {
            session_start();
        }
    }

    /**
     * Inicjalizacja panelu administracyjnego
     */
    public function admin_init(): void {
        // Sprawdź uprawnienia
        if (!current_user_can('manage_options')) {
            return;
        }

        // Rejestracja ustawień
        register_setting('aica_settings', 'aica_settings', [
            'sanitize_callback' => [$this->security_service, 'sanitize_settings']
        ]);

        // Dodaj sekcje ustawień
        add_settings_section(
            'aica_general_settings',
            __('Ustawienia ogólne', 'ai-chat-assistant'),
            [$this, 'render_general_settings_section'],
            'aica_settings'
        );

        add_settings_section(
            'aica_api_settings',
            __('Ustawienia API', 'ai-chat-assistant'),
            [$this, 'render_api_settings_section'],
            'aica_settings'
        );

        // Dodaj pola ustawień
        $this->add_settings_fields();
    }

    /**
     * Inicjalizacja frontendu
     */
    public function frontend_init(): void {
        // Sprawdź czy wtyczka jest aktywna
        if (!is_plugin_active(AICA_PLUGIN_BASENAME)) {
            return;
        }

        // Inicjalizacja sesji
        if (!session_id()) {
            session_start();
        }
    }

    /**
     * Dodanie pól ustawień
     */
    private function add_settings_fields(): void {
        // Ustawienia ogólne
        add_settings_field(
            'aica_model',
            __('Model AI', 'ai-chat-assistant'),
            [$this, 'render_model_field'],
            'aica_settings',
            'aica_general_settings'
        );

        add_settings_field(
            'aica_max_tokens',
            __('Maksymalna liczba tokenów', 'ai-chat-assistant'),
            [$this, 'render_max_tokens_field'],
            'aica_settings',
            'aica_general_settings'
        );

        add_settings_field(
            'aica_temperature',
            __('Temperatura', 'ai-chat-assistant'),
            [$this, 'render_temperature_field'],
            'aica_settings',
            'aica_general_settings'
        );

        // Ustawienia API
        add_settings_field(
            'aica_claude_api_key',
            __('Klucz API Claude', 'ai-chat-assistant'),
            [$this, 'render_claude_api_key_field'],
            'aica_settings',
            'aica_api_settings'
        );

        add_settings_field(
            'aica_github_token',
            __('Token GitHub', 'ai-chat-assistant'),
            [$this, 'render_github_token_field'],
            'aica_settings',
            'aica_api_settings'
        );

        add_settings_field(
            'aica_gitlab_token',
            __('Token GitLab', 'ai-chat-assistant'),
            [$this, 'render_gitlab_token_field'],
            'aica_settings',
            'aica_api_settings'
        );

        add_settings_field(
            'aica_bitbucket_username',
            __('Nazwa użytkownika Bitbucket', 'ai-chat-assistant'),
            [$this, 'render_bitbucket_username_field'],
            'aica_settings',
            'aica_api_settings'
        );

        add_settings_field(
            'aica_bitbucket_app_password',
            __('Hasło aplikacji Bitbucket', 'ai-chat-assistant'),
            [$this, 'render_bitbucket_app_password_field'],
            'aica_settings',
            'aica_api_settings'
        );
    }

    /**
     * Renderowanie sekcji ustawień ogólnych
     */
    public function render_general_settings_section(): void {
        echo '<p>' . esc_html__('Ustawienia ogólne wtyczki AI Chat Assistant.', 'ai-chat-assistant') . '</p>';
    }

    /**
     * Renderowanie sekcji ustawień API
     */
    public function render_api_settings_section(): void {
        echo '<p>' . esc_html__('Ustawienia API dla wtyczki AI Chat Assistant.', 'ai-chat-assistant') . '</p>';
    }

    /**
     * Renderowanie pola wyboru modelu
     */
    public function render_model_field(): void {
        $settings = get_option('aica_settings', []);
        $current_model = $settings['model'] ?? 'claude-3-haiku-20240307';
        $models = $this->api_service->get_available_models();

        echo '<select name="aica_settings[model]" id="aica_model">';
        foreach ($models as $model_id => $model_name) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($model_id),
                selected($current_model, $model_id, false),
                esc_html($model_name)
            );
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__('Wybierz model AI do użycia.', 'ai-chat-assistant') . '</p>';
    }

    /**
     * Renderowanie pola maksymalnej liczby tokenów
     */
    public function render_max_tokens_field(): void {
        $settings = get_option('aica_settings', []);
        $max_tokens = $settings['max_tokens'] ?? 4000;

        printf(
            '<input type="number" name="aica_settings[max_tokens]" id="aica_max_tokens" value="%s" min="1" max="100000" step="1" />',
            esc_attr($max_tokens)
        );
        echo '<p class="description">' . esc_html__('Maksymalna liczba tokenów w odpowiedzi.', 'ai-chat-assistant') . '</p>';
    }

    /**
     * Renderowanie pola temperatury
     */
    public function render_temperature_field(): void {
        $settings = get_option('aica_settings', []);
        $temperature = $settings['temperature'] ?? 0.7;

        printf(
            '<input type="number" name="aica_settings[temperature]" id="aica_temperature" value="%s" min="0" max="1" step="0.1" />',
            esc_attr($temperature)
        );
        echo '<p class="description">' . esc_html__('Temperatura generowania odpowiedzi (0-1).', 'ai-chat-assistant') . '</p>';
    }

    /**
     * Renderowanie pola klucza API Claude
     */
    public function render_claude_api_key_field(): void {
        $settings = get_option('aica_settings', []);
        $api_key = $settings['claude_api_key'] ?? '';

        printf(
            '<input type="password" name="aica_settings[claude_api_key]" id="aica_claude_api_key" value="%s" class="regular-text" />',
            esc_attr($api_key)
        );
        echo '<p class="description">' . esc_html__('Klucz API Claude od Anthropic.', 'ai-chat-assistant') . '</p>';
    }

    /**
     * Renderowanie pola tokenu GitHub
     */
    public function render_github_token_field(): void {
        $settings = get_option('aica_settings', []);
        $token = $settings['github_token'] ?? '';

        printf(
            '<input type="password" name="aica_settings[github_token]" id="aica_github_token" value="%s" class="regular-text" />',
            esc_attr($token)
        );
        echo '<p class="description">' . esc_html__('Token dostępu GitHub.', 'ai-chat-assistant') . '</p>';
    }

    /**
     * Renderowanie pola tokenu GitLab
     */
    public function render_gitlab_token_field(): void {
        $settings = get_option('aica_settings', []);
        $token = $settings['gitlab_token'] ?? '';

        printf(
            '<input type="password" name="aica_settings[gitlab_token]" id="aica_gitlab_token" value="%s" class="regular-text" />',
            esc_attr($token)
        );
        echo '<p class="description">' . esc_html__('Token dostępu GitLab.', 'ai-chat-assistant') . '</p>';
    }

    /**
     * Renderowanie pola nazwy użytkownika Bitbucket
     */
    public function render_bitbucket_username_field(): void {
        $settings = get_option('aica_settings', []);
        $username = $settings['bitbucket_username'] ?? '';

        printf(
            '<input type="text" name="aica_settings[bitbucket_username]" id="aica_bitbucket_username" value="%s" class="regular-text" />',
            esc_attr($username)
        );
        echo '<p class="description">' . esc_html__('Nazwa użytkownika Bitbucket.', 'ai-chat-assistant') . '</p>';
    }

    /**
     * Renderowanie pola hasła aplikacji Bitbucket
     */
    public function render_bitbucket_app_password_field(): void {
        $settings = get_option('aica_settings', []);
        $app_password = $settings['bitbucket_app_password'] ?? '';

        printf(
            '<input type="password" name="aica_settings[bitbucket_app_password]" id="aica_bitbucket_app_password" value="%s" class="regular-text" />',
            esc_attr($app_password)
        );
        echo '<p class="description">' . esc_html__('Hasło aplikacji Bitbucket.', 'ai-chat-assistant') . '</p>';
    }

    /**
     * Dodanie linków do ustawień
     */
    public function add_action_links(array $links): array {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url('admin.php?page=aica-settings'),
            __('Ustawienia', 'ai-chat-assistant')
        );
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Dodanie linków do dokumentacji
     */
    public function add_meta_links(array $links, string $file): array {
        if ($file === AICA_PLUGIN_BASENAME) {
            $links[] = sprintf(
                '<a href="%s" target="_blank">%s</a>',
                'https://github.com/yourusername/ai-chat-assistant/wiki',
                __('Dokumentacja', 'ai-chat-assistant')
            );
            $links[] = sprintf(
                '<a href="%s" target="_blank">%s</a>',
                'https://github.com/yourusername/ai-chat-assistant/issues',
                __('Wsparcie', 'ai-chat-assistant')
            );
        }
        return $links;
    }

    /**
     * Załadowanie zasobów panelu administracyjnego
     */
    public function enqueue_admin_assets(): void {
        // Style
        wp_enqueue_style(
            'aica-admin',
            AICA_PLUGIN_URL . 'assets/css/admin.css',
            [],
            AICA_VERSION
        );

        // Skrypty
        wp_enqueue_script(
            'aica-admin',
            AICA_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            AICA_VERSION,
            true
        );

        // Lokalizacja
        wp_localize_script('aica-admin', 'aicaAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aica-admin-nonce'),
            'i18n' => [
                'confirmDelete' => __('Czy na pewno chcesz usunąć ten element?', 'ai-chat-assistant'),
                'confirmClear' => __('Czy na pewno chcesz wyczyścić historię?', 'ai-chat-assistant'),
                'error' => __('Wystąpił błąd. Spróbuj ponownie.', 'ai-chat-assistant'),
                'success' => __('Operacja zakończona sukcesem.', 'ai-chat-assistant')
            ]
        ]);
    }

    /**
     * Załadowanie zasobów frontendu
     */
    public function enqueue_frontend_assets(): void {
        // Style
        wp_enqueue_style(
            'aica-frontend',
            AICA_PLUGIN_URL . 'assets/css/frontend.css',
            [],
            AICA_VERSION
        );

        // Skrypty
        wp_enqueue_script(
            'aica-frontend',
            AICA_PLUGIN_URL . 'assets/js/frontend.js',
            ['jquery'],
            AICA_VERSION,
            true
        );

        // Lokalizacja
        wp_localize_script('aica-frontend', 'aicaFrontend', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aica-frontend-nonce'),
            'i18n' => [
                'error' => __('Wystąpił błąd. Spróbuj ponownie.', 'ai-chat-assistant'),
                'success' => __('Operacja zakończona sukcesem.', 'ai-chat-assistant')
            ]
        ]);
    }

    /**
     * Renderowanie shortcode'u czatu
     */
    public function render_chat_shortcode(array $atts = []): string {
        $atts = shortcode_atts([
            'title' => __('Chat AI', 'ai-chat-assistant'),
            'width' => '100%',
            'height' => '500px'
        ], $atts, 'aica_chat');

        ob_start();
        require AICA_PLUGIN_DIR . 'templates/shortcode-chat.php';
        return ob_get_clean();
    }
}
