<?php

namespace AICA\Ajax;

use AICA\Services\ChatService;
use AICA\Services\ErrorService;

if (!defined('ABSPATH')) {
    exit;
}

class HistoryHandler {
    private $chat_service;
    private $error_service;

    /**
     * Inicjalizacja HistoryHandler
     */
    public function __construct(ChatService $chat_service, ErrorService $error_service) {
        $this->chat_service = $chat_service;
        $this->error_service = $error_service;
        add_action('wp_ajax_aica_get_history', array($this, 'get_history'));
        add_action('wp_ajax_aica_clear_history', array($this, 'clear_history'));
        add_action('wp_ajax_aica_get_sessions', array($this, 'get_sessions'));
        add_action('wp_ajax_aica_delete_session', array($this, 'delete_session'));
        add_action('wp_ajax_aica_rename_session', array($this, 'rename_session'));
    }

    /**
     * Pobiera historię czatu
     */
    public function get_history() {
        try {
            if (!current_user_can('manage_options')) {
                throw new \Exception(__('Brak uprawnień.', 'ai-chat-assistant'));
            }

            check_ajax_referer('aica_history_nonce', 'nonce');

            $user_id = get_current_user_id();
            if (!$user_id) {
                throw new \Exception(__('Użytkownik nie jest zalogowany.', 'ai-chat-assistant'));
            }

            $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
            if (empty($session_id)) {
                throw new \Exception(__('Brak identyfikatora sesji.', 'ai-chat-assistant'));
            }

            // Sprawdź uprawnienia
            global $wpdb;
            $session = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}aica_sessions WHERE session_id = %s AND user_id = %d",
                $session_id,
                $user_id
            ));

            if (!$session) {
                throw new \Exception(__('Sesja nie istnieje lub brak dostępu.', 'ai-chat-assistant'));
            }

            // Pobierz historię
            $history = $this->chat_service->get_conversation_history($session_id);

            wp_send_json_success([
                'history' => $history,
                'session' => $session
            ]);

        } catch (\Throwable $e) {
            $this->error_service->log_error($e);
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Usuwa wszystkie sesje czatu
     */
    public function clear_history() {
        try {
            if (!current_user_can('manage_options')) {
                throw new \Exception(__('Brak uprawnień.', 'ai-chat-assistant'));
            }

            check_ajax_referer('aica_history_nonce', 'nonce');

            $user_id = get_current_user_id();
            if (!$user_id) {
                throw new \Exception(__('Użytkownik nie jest zalogowany.', 'ai-chat-assistant'));
            }

            $result = $this->chat_service->clear_user_history($user_id);
            if (!$result) {
                throw new \Exception(__('Nie udało się wyczyścić historii.', 'ai-chat-assistant'));
            }

            wp_send_json_success([
                'message' => __('Historia została wyczyszczona.', 'ai-chat-assistant')
            ]);

        } catch (\Throwable $e) {
            $this->error_service->log_error($e);
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Pobiera listę sesji czatu
     */
    public function get_sessions() {
        try {
            if (!current_user_can('manage_options')) {
                throw new \Exception(__('Brak uprawnień.', 'ai-chat-assistant'));
            }

            check_ajax_referer('aica_history_nonce', 'nonce');

            $user_id = get_current_user_id();
            if (!$user_id) {
                throw new \Exception(__('Użytkownik nie jest zalogowany.', 'ai-chat-assistant'));
            }

            $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
            $per_page = isset($_POST['per_page']) ? max(1, intval($_POST['per_page'])) : 20;
            $search = sanitize_text_field($_POST['search'] ?? '');

            $sessions = $this->chat_service->get_user_sessions($user_id, $page, $per_page, $search);
            $total_sessions = $this->chat_service->count_user_sessions($user_id, $search);
            $total_pages = ceil($total_sessions / $per_page);

            wp_send_json_success([
                'sessions' => $sessions,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $per_page,
                    'total_pages' => $total_pages,
                    'total_sessions' => $total_sessions
                ]
            ]);

        } catch (\Throwable $e) {
            $this->error_service->log_error($e);
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Usuwa sesję czatu
     */
    public function delete_session() {
        try {
            if (!current_user_can('manage_options')) {
                throw new \Exception(__('Brak uprawnień.', 'ai-chat-assistant'));
            }

            check_ajax_referer('aica_history_nonce', 'nonce');

            $user_id = get_current_user_id();
            if (!$user_id) {
                throw new \Exception(__('Użytkownik nie jest zalogowany.', 'ai-chat-assistant'));
            }

            $session_id = sanitize_text_field($_POST['session_id'] ?? '');
            if (empty($session_id)) {
                throw new \Exception(__('Brak identyfikatora sesji.', 'ai-chat-assistant'));
            }

            $result = $this->chat_service->delete_session($session_id, $user_id);
            if (!$result) {
                throw new \Exception(__('Nie udało się usunąć sesji.', 'ai-chat-assistant'));
            }

            wp_send_json_success([
                'message' => __('Sesja została usunięta.', 'ai-chat-assistant')
            ]);

        } catch (\Throwable $e) {
            $this->error_service->log_error($e);
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Zmienia tytuł sesji
     */
    public function rename_session() {
        try {
            if (!current_user_can('manage_options')) {
                throw new \Exception(__('Brak uprawnień.', 'ai-chat-assistant'));
            }

            check_ajax_referer('aica_history_nonce', 'nonce');

            $user_id = get_current_user_id();
            if (!$user_id) {
                throw new \Exception(__('Użytkownik nie jest zalogowany.', 'ai-chat-assistant'));
            }

            $session_id = sanitize_text_field($_POST['session_id'] ?? '');
            $title = sanitize_text_field($_POST['title'] ?? '');

            if (empty($session_id)) {
                throw new \Exception(__('Brak identyfikatora sesji.', 'ai-chat-assistant'));
            }

            if (empty($title)) {
                throw new \Exception(__('Tytuł nie może być pusty.', 'ai-chat-assistant'));
            }

            $result = $this->chat_service->update_session_title($session_id, $title, $user_id);
            if (!$result) {
                throw new \Exception(__('Nie udało się zmienić tytułu sesji.', 'ai-chat-assistant'));
            }

            wp_send_json_success([
                'message' => __('Tytuł sesji został zmieniony.', 'ai-chat-assistant')
            ]);

        } catch (\Throwable $e) {
            $this->error_service->log_error($e);
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }
} 