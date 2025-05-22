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
 * Klasa odpowiedzialna za odinstalowanie wtyczki
 */
class Uninstaller {
    /**
     * @var SecurityService
     */
    private SecurityService $security_service;

    /**
     * @var ApiService
     */
    private ApiService $api_service;

    /**
     * @var RepositoryService
     */
    private RepositoryService $repository_service;

    /**
     * @var SessionService
     */
    private SessionService $session_service;

    /**
     * @var MessageService
     */
    private MessageService $message_service;

    /**
     * @var CleanupService
     */
    private CleanupService $cleanup_service;

    /**
     * @var UpdateService
     */
    private UpdateService $update_service;

    /**
     * Konstruktor
     */
    public function __construct() {
        $this->init_services();
    }

    /**
     * Inicjalizacja serwisów
     */
    private function init_services(): void {
        $this->security_service = new SecurityService();
        $this->api_service = new ApiService();
        $this->repository_service = new RepositoryService();
        $this->session_service = new SessionService();
        $this->message_service = new MessageService();
        $this->cleanup_service = new CleanupService();
        $this->update_service = new UpdateService();
    }

    /**
     * Odinstalowanie wtyczki
     */
    public function uninstall(): void {
        try {
            // Sprawdź uprawnienia
            if (!current_user_can('activate_plugins')) {
                throw new \RuntimeException(
                    __('Nie masz uprawnień do odinstalowania wtyczki.', 'ai-chat-assistant')
                );
            }

            // Usuń tabele
            $this->drop_tables();

            // Usuń opcje
            $this->delete_options();

            // Usuń katalogi
            $this->remove_directories();

            // Usuń pliki
            $this->remove_files();

            // Wyczyść cache
            $this->clear_cache();

            // Przekieruj do strony ustawień
            wp_redirect(admin_url('plugins.php?deleted=true'));
            exit;
        } catch (\Throwable $e) {
            // Loguj błąd
            error_log(sprintf(
                'AICA Plugin Uninstall Error: %s in %s:%d',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            ));

            // Wyświetl komunikat o błędzie
            wp_die(
                sprintf(
                    __('Błąd odinstalowania wtyczki AI Chat Assistant: %s', 'ai-chat-assistant'),
                    $e->getMessage()
                ),
                __('Błąd odinstalowania', 'ai-chat-assistant'),
                ['back_link' => true]
            );
        }
    }

    /**
     * Usunięcie tabel
     */
    private function drop_tables(): void {
        global $wpdb;

        $tables = [
            $wpdb->prefix . 'aica_sessions',
            $wpdb->prefix . 'aica_messages',
            $wpdb->prefix . 'aica_repositories'
        ];

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
    }

    /**
     * Usunięcie opcji
     */
    private function delete_options(): void {
        $options = [
            'aica_settings',
            'aica_version',
            'aica_deactivated',
            'aica_deactivated_time'
        ];

        foreach ($options as $option) {
            delete_option($option);
        }
    }

    /**
     * Usunięcie katalogów
     */
    private function remove_directories(): void {
        $upload_dir = wp_upload_dir();
        $aica_dir = $upload_dir['basedir'] . '/ai-chat-assistant';

        if (is_dir($aica_dir)) {
            $this->remove_directory($aica_dir);
        }
    }

    /**
     * Usunięcie plików
     */
    private function remove_files(): void {
        $files = [
            AICA_PLUGIN_DIR . '.htaccess',
            AICA_PLUGIN_DIR . 'index.php'
        ];

        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Usunięcie katalogu rekurencyjnie
     */
    private function remove_directory(string $dir): void {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $path = $dir . '/' . $file;

            if (is_dir($path)) {
                $this->remove_directory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }

    /**
     * Czyszczenie cache
     */
    private function clear_cache(): void {
        wp_cache_flush();
    }
} 