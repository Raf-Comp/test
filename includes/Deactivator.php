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
 * Klasa odpowiedzialna za deaktywację wtyczki
 */
class Deactivator {
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
     * Deaktywacja wtyczki
     */
    public function deactivate(): void {
        try {
            // Sprawdź uprawnienia
            if (!current_user_can('activate_plugins')) {
                throw new \RuntimeException(
                    __('Nie masz uprawnień do deaktywacji wtyczki.', 'ai-chat-assistant')
                );
            }

            // Usuń zadania cron
            $this->unschedule_cron_jobs();

            // Wyczyść cache
            $this->clear_cache();

            // Zapisz stan deaktywacji
            update_option('aica_deactivated', true);
            update_option('aica_deactivated_time', current_time('mysql'));

            // Przekieruj do strony ustawień
            wp_redirect(admin_url('admin.php?page=aica-settings&deactivated=true'));
            exit;
        } catch (\Throwable $e) {
            // Loguj błąd
            error_log(sprintf(
                'AICA Plugin Deactivation Error: %s in %s:%d',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            ));

            // Wyświetl komunikat o błędzie
            wp_die(
                sprintf(
                    __('Błąd deaktywacji wtyczki AI Chat Assistant: %s', 'ai-chat-assistant'),
                    $e->getMessage()
                ),
                __('Błąd deaktywacji', 'ai-chat-assistant'),
                ['back_link' => true]
            );
        }
    }

    /**
     * Usunięcie zadań cron
     */
    private function unschedule_cron_jobs(): void {
        $timestamp = wp_next_scheduled('aica_daily_cleanup');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'aica_daily_cleanup');
        }

        $timestamp = wp_next_scheduled('aica_check_updates');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'aica_check_updates');
        }
    }

    /**
     * Czyszczenie cache
     */
    private function clear_cache(): void {
        wp_cache_flush();
    }
} 