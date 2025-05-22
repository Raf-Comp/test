<?php
declare(strict_types=1);

namespace AICA;

use AICA\Services\SecurityService;
use AICA\Services\ApiService;
use AICA\Services\RepositoryService;
use AICA\Services\SessionService;
use AICA\Services\MessageService;
use AICA\Services\CleanupService;
use AICA\Services\UpdateService;

/**
 * Klasa odpowiedzialna za instalację wtyczki
 */
class Installer {
    public function __construct(
        private readonly SecurityService $security_service,
        private readonly ApiService $api_service,
        private readonly RepositoryService $repository_service,
        private readonly SessionService $session_service,
        private readonly MessageService $message_service,
        private readonly CleanupService $cleanup_service,
        private readonly UpdateService $update_service
    ) {}

    /**
     * Instalacja wtyczki
     */
    public function install(): void {
        try {
            // Sprawdź wymagania
            $this->check_requirements();

            // Utwórz tabele w bazie danych
            $this->create_tables();

            // Utwórz katalogi
            $this->create_directories();

            // Utwórz pliki
            $this->create_files();

            // Ustaw domyślne opcje
            $this->set_default_options();

            // Zaplanuj zadania cron
            $this->schedule_cron_jobs();

            // Wyczyść cache
            $this->clear_cache();

            // Zapisz wersję
            update_option('aica_version', AICA_VERSION);

            // Przekieruj do strony ustawień
            wp_redirect(admin_url('admin.php?page=aica-settings&installed=true'));
            exit;
        } catch (\Throwable $e) {
            // Loguj błąd
            error_log(sprintf(
                'AICA Plugin Installation Error: %s in %s:%d',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            ));

            // Wyświetl komunikat o błędzie
            wp_die(
                sprintf(
                    __('Błąd instalacji wtyczki AI Chat Assistant: %s', 'ai-chat-assistant'),
                    $e->getMessage()
                ),
                __('Błąd instalacji', 'ai-chat-assistant'),
                ['back_link' => true]
            );
        }
    }

    /**
     * Sprawdzenie wymagań
     */
    private function check_requirements(): void {
        // Sprawdź wersję PHP
        if (version_compare(PHP_VERSION, '8.0.0', '<')) {
            throw new \RuntimeException(
                sprintf(
                    __('Wtyczka AI Chat Assistant wymaga PHP w wersji 8.0 lub nowszej. Aktualna wersja: %s', 'ai-chat-assistant'),
                    PHP_VERSION
                )
            );
        }

        // Sprawdź wersję WordPress
        if (version_compare(get_bloginfo('version'), '5.8.0', '<')) {
            throw new \RuntimeException(
                sprintf(
                    __('Wtyczka AI Chat Assistant wymaga WordPress w wersji 5.8 lub nowszej. Aktualna wersja: %s', 'ai-chat-assistant'),
                    get_bloginfo('version')
                )
            );
        }

        // Sprawdź uprawnienia
        if (!current_user_can('activate_plugins')) {
            throw new \RuntimeException(
                __('Nie masz uprawnień do instalacji wtyczki.', 'ai-chat-assistant')
            );
        }
    }

    /**
     * Utworzenie tabel w bazie danych
     */
    private function create_tables(): void {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Tabela sesji
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}aica_sessions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            title varchar(255) NOT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id)
        ) $charset_collate;";

        // Tabela wiadomości
        $sql .= "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}aica_messages (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            session_id bigint(20) NOT NULL,
            role varchar(20) NOT NULL,
            content text NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY session_id (session_id)
        ) $charset_collate;";

        // Tabela repozytoriów
        $sql .= "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}aica_repositories (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            name varchar(255) NOT NULL,
            url varchar(255) NOT NULL,
            type varchar(20) NOT NULL,
            credentials text NOT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Utworzenie katalogów
     */
    private function create_directories(): void {
        $upload_dir = wp_upload_dir();
        $aica_dir = $upload_dir['basedir'] . '/ai-chat-assistant';

        if (!file_exists($aica_dir)) {
            wp_mkdir_p($aica_dir);
        }

        // Utwórz plik .htaccess
        $htaccess_file = $aica_dir . '/.htaccess';
        if (!file_exists($htaccess_file)) {
            $htaccess_content = "Order deny,allow\nDeny from all";
            file_put_contents($htaccess_file, $htaccess_content);
        }

        // Utwórz plik index.php
        $index_file = $aica_dir . '/index.php';
        if (!file_exists($index_file)) {
            $index_content = "<?php\n// Silence is golden.";
            file_put_contents($index_file, $index_content);
        }
    }

    /**
     * Utworzenie plików
     */
    private function create_files(): void {
        // Utwórz plik .htaccess w głównym katalogu wtyczki
        $htaccess_file = AICA_PLUGIN_DIR . '.htaccess';
        if (!file_exists($htaccess_file)) {
            $htaccess_content = "Order deny,allow\nDeny from all";
            file_put_contents($htaccess_file, $htaccess_content);
        }

        // Utwórz plik index.php w głównym katalogu wtyczki
        $index_file = AICA_PLUGIN_DIR . 'index.php';
        if (!file_exists($index_file)) {
            $index_content = "<?php\n// Silence is golden.";
            file_put_contents($index_file, $index_content);
        }
    }

    /**
     * Ustawienie domyślnych opcji
     */
    private function set_default_options(): void {
        $default_settings = [
            'model' => 'claude-3-haiku-20240307',
            'max_tokens' => 4096,
            'temperature' => 0.7,
            'enable_history' => true,
            'history_retention_days' => 30
        ];

        $current_settings = get_option('aica_settings', []);
        $settings = array_merge($default_settings, $current_settings);
        update_option('aica_settings', $settings);
    }

    /**
     * Zaplanowanie zadań cron
     */
    private function schedule_cron_jobs(): void {
        if (!wp_next_scheduled('aica_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'aica_daily_cleanup');
        }

        if (!wp_next_scheduled('aica_check_updates')) {
            wp_schedule_event(time(), 'daily', 'aica_check_updates');
        }
    }

    /**
     * Czyszczenie cache
     */
    private function clear_cache(): void {
        wp_cache_flush();
    }

    /**
     * Aktualizacja wtyczki – tymczasowa pusta implementacja
     */
    public function update(string $previous_version): void {
        // TODO: W przyszłości można dodać logikę migracji
        error_log("AICA Installer: update from version $previous_version");
    }
}
