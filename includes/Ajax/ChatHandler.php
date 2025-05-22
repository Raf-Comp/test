<?php
namespace AICA\Ajax;

class ChatHandler {
    public function __construct() {
        // Rejestracja wszystkich metod obsługi AJAX
        add_action('wp_ajax_aica_send_message', [$this, 'send_message']);
        add_action('wp_ajax_aica_create_session', [$this, 'create_session']);
        add_action('wp_ajax_aica_get_chat_history', [$this, 'get_chat_history']);
        add_action('wp_ajax_aica_get_sessions_list', [$this, 'get_sessions_list']);
        add_action('wp_ajax_aica_rename_session', [$this, 'rename_session']);
        add_action('wp_ajax_aica_delete_session', [$this, 'delete_session']);
        add_action('wp_ajax_aica_get_repository_files', [$this, 'get_repository_files']);
        add_action('wp_ajax_aica_get_file_content', [$this, 'get_file_content']);
        add_action('wp_ajax_aica_save_message', [$this, 'save_message']);
        add_action('wp_ajax_aica_upload_file', [$this, 'upload_file']);
    }

    public function send_message() {
        // Sprawdzenie nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_nonce')) {
            wp_send_json_error([
                'message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')
            ]);
            return;
        }
        
        // Sprawdzenie treści wiadomości
        if (!isset($_POST['message']) || empty($_POST['message'])) {
            wp_send_json_error([
                'message' => __('Wiadomość nie może być pusta.', 'ai-chat-assistant')
            ]);
            return;
        }
        
        $message = sanitize_textarea_field($_POST['message']);
        $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error([
                'message' => __('Nie znaleziono użytkownika.', 'ai-chat-assistant')
            ]);
            return;
        }
        
        // Sprawdź czy sesja istnieje i należy do użytkownika
        if (!empty($session_id)) {
            if (!$this->user_owns_session($user_id, $session_id)) {
                // Utwórz nową sesję, jeśli obecna nie należy do użytkownika
                $title = __('Nowa rozmowa', 'ai-chat-assistant');
                $session_id = $this->create_new_session($user_id, $title);
                
                if (!$session_id) {
                    wp_send_json_error([
                        'message' => __('Nie udało się utworzyć nowej sesji.', 'ai-chat-assistant')
                    ]);
                    return;
                }
            }
        } else {
            // Utwórz nową sesję
            $title = __('Nowa rozmowa', 'ai-chat-assistant');
            $session_id = $this->create_new_session($user_id, $title);
            
            if (!$session_id) {
                wp_send_json_error([
                    'message' => __('Nie udało się utworzyć nowej sesji.', 'ai-chat-assistant')
                ]);
                return;
            }
        }
        
        // Pobranie ustawień Claude
        $api_key = aica_get_option('claude_api_key', '');
        $model = aica_get_option('claude_model', 'claude-3-haiku-20240307');
        $max_tokens = intval(aica_get_option('max_tokens', 4000));
        $temperature = floatval(aica_get_option('temperature', 0.7));

        if (empty($api_key)) {
            wp_send_json_error([
                'message' => __('Klucz API Claude nie jest skonfigurowany.', 'ai-chat-assistant')
            ]);
            return;
        }

        // Dodanie logowania w trybie debugowania
        if (aica_get_option('debug_mode', false)) {
            aica_log('Wysyłanie zapytania do Claude API. Model: ' . $model);
        }

        try {
            // Pobierz historię rozmowy
            $history = $this->get_session_messages($session_id);
            $messages_for_api = [];
            
            foreach ($history as $msg) {
                if ($msg['type'] === 'user') {
                    $messages_for_api[] = [
                        'role' => 'user',
                        'content' => $msg['content']
                    ];
                } elseif ($msg['type'] === 'assistant') {
                    $messages_for_api[] = [
                        'role' => 'assistant',
                        'content' => $msg['content']
                    ];
                }
            }
            
            // Dodanie aktualnej wiadomości użytkownika
            $messages_for_api[] = [
                'role' => 'user',
                'content' => $message
            ];
            
            // Wysłanie wiadomości do Claude
            $claude_client = new \AICA\API\ClaudeClient($api_key);
            $response = $claude_client->send_message($messages_for_api, $model, $max_tokens, $temperature);
            
            if (!$response['success']) {
                wp_send_json_error([
                    'message' => $response['message'] ?? __('Wystąpił błąd podczas komunikacji z Claude API.', 'ai-chat-assistant')
                ]);
                return;
            }
            
            // Zapisanie wiadomości do bazy danych
            $this->add_message_to_session($session_id, 'user', $message);
            $this->add_message_to_session($session_id, 'assistant', $response['message']);
            
            // Jeżeli to nowa rozmowa, zaktualizuj tytuł na podstawie pierwszej wymiany wiadomości
            if (count($history) <= 2) {
                $new_title = $this->generate_session_title($message, $response['message']);
                $this->update_session_title($session_id, $new_title);
            }
            
            // Aktualizuj czas ostatniej modyfikacji sesji
            $this->update_session_time($session_id);
            
            // Zwróć odpowiedź
            wp_send_json_success([
                'content' => $response['message'],
                'session_id' => $session_id,
                'model' => $model,
                'tokens_used' => $response['tokens_used'] ?? 0
            ]);
        } catch (\Exception $e) {
            if (aica_get_option('debug_mode', false)) {
                aica_log('Błąd API: ' . $e->getMessage(), 'error');
            }
            
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }
    
    private function user_owns_session($user_id, $session_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'aica_sessions';
        
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE session_id = %s AND user_id = %d",
            $session_id,
            $user_id
        );
        
        return (int)$wpdb->get_var($query) > 0;
    }
    
    private function create_new_session($user_id, $title) {
        global $wpdb;
        $table = $wpdb->prefix . 'aica_sessions';
        
        $session_id = wp_generate_uuid4();
        
        $wpdb->insert(
            $table,
            [
                'session_id' => $session_id,
                'user_id' => $user_id,
                'title' => $title,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ]
        );
        
        if ($wpdb->last_error) {
            return false;
        }
        
        return $session_id;
    }
    
    private function get_session_messages($session_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'aica_messages';
        
        $query = $wpdb->prepare(
            "SELECT id, message as content, 'user' as type, created_at as time FROM $table 
            WHERE session_id = %s
            UNION ALL
            SELECT id, response as content, 'assistant' as type, created_at as time 
            FROM $table WHERE session_id = %s AND response IS NOT NULL
            ORDER BY time ASC",
            $session_id, $session_id
        );
        
        return $wpdb->get_results($query, ARRAY_A);
    }
    
    private function add_message_to_session($session_id, $type, $content) {
        global $wpdb;
        $table = $wpdb->prefix . 'aica_messages';
        
        if ($type === 'user') {
            // Dodaj wiadomość użytkownika
            $wpdb->insert(
                $table,
                [
                    'session_id' => $session_id,
                    'message' => $content,
                    'created_at' => current_time('mysql')
                ]
            );
            return $wpdb->insert_id;
        } else if ($type === 'assistant') {
            // Pobierz ostatnią wiadomość bez odpowiedzi
            $query = $wpdb->prepare(
                "SELECT id FROM $table WHERE session_id = %s AND message IS NOT NULL AND response IS NULL ORDER BY id DESC LIMIT 1",
                $session_id
            );
            $message_id = $wpdb->get_var($query);
            
            if ($message_id) {
                // Zaktualizuj odpowiedź asystenta
                $wpdb->update(
                    $table,
                    ['response' => $content],
                    ['id' => $message_id]
                );
                return $message_id;
            } else {
                // Jeśli nie ma pasującej wiadomości, dodaj nową
                $wpdb->insert(
                    $table,
                    [
                        'session_id' => $session_id,
                        'response' => $content,
                        'created_at' => current_time('mysql')
                    ]
                );
                return $wpdb->insert_id;
            }
        }
        
        return false;
    }
    
    private function update_session_time($session_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'aica_sessions';
        
        $wpdb->update(
            $table,
            ['updated_at' => current_time('mysql')],
            ['session_id' => $session_id]
        );
    }
    
    private function generate_session_title($message, $response) {
        // Ogranicz długość wiadomości do 50 znaków
        $message_preview = substr(strip_tags($message), 0, 50);
        return $message_preview . '...';
    }
    
    private function update_session_title($session_id, $title) {
        global $wpdb;
        $table = $wpdb->prefix . 'aica_sessions';
        
        $wpdb->update(
            $table,
            ['title' => $title],
            ['session_id' => $session_id]
        );
    }
    
    public function create_session() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_nonce')) {
            wp_send_json_error(['message' => __('Błąd bezpieczeństwa.', 'ai-chat-assistant')]);
            return;
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('Użytkownik nie zalogowany.', 'ai-chat-assistant')]);
            return;
        }
        
        $title = __('Nowa rozmowa', 'ai-chat-assistant');
        $session_id = $this->create_new_session($user_id, $title);
        
        if (!$session_id) {
            wp_send_json_error(['message' => __('Nie udało się utworzyć nowej sesji.', 'ai-chat-assistant')]);
            return;
        }
        
        wp_send_json_success(['session_id' => $session_id]);
    }
    
    public function get_chat_history() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_nonce')) {
            wp_send_json_error(['message' => __('Błąd bezpieczeństwa.', 'ai-chat-assistant')]);
            return;
        }
        
        if (!isset($_POST['session_id'])) {
            wp_send_json_error(['message' => __('Brak ID sesji.', 'ai-chat-assistant')]);
            return;
        }
        
        $session_id = sanitize_text_field($_POST['session_id']);
        $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
        $per_page = isset($_POST['per_page']) ? max(1, intval($_POST['per_page'])) : 20;
        
        $user_id = get_current_user_id();
        if (!$this->user_owns_session($user_id, $session_id)) {
            wp_send_json_error(['message' => __('Brak dostępu do tej sesji.', 'ai-chat-assistant')]);
            return;
        }
        
        global $wpdb;
        $sessions_table = $wpdb->prefix . 'aica_sessions';
        
        // Pobierz informacje o sesji
        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $sessions_table WHERE session_id = %s",
            $session_id
        ), ARRAY_A);
        
        if (!$session) {
            wp_send_json_error(['message' => __('Sesja nie istnieje.', 'ai-chat-assistant')]);
            return;
        }
        
        // Pobierz wiadomości z paginacją
        $messages = $this->get_session_messages_paginated($session_id, $page, $per_page);
        $total_messages = $this->count_session_messages($session_id);
        
        $total_pages = ceil($total_messages / $per_page);
        
        wp_send_json_success([
            'title' => $session['title'],
            'created_at' => $session['created_at'],
            'updated_at' => $session['updated_at'],
            'messages' => $messages,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $per_page,
                'total_pages' => $total_pages,
                'total_messages' => $total_messages
            ]
        ]);
    }
    
    private function get_session_messages_paginated($session_id, $page, $per_page) {
        global $wpdb;
        $table = $wpdb->prefix . 'aica_messages';
        
        $offset = ($page - 1) * $per_page;
        
        $query = $wpdb->prepare(
            "SELECT id, message as content, 'user' as type, created_at as time FROM $table 
            WHERE session_id = %s
            UNION ALL
            SELECT id, response as content, 'assistant' as type, created_at as time 
            FROM $table WHERE session_id = %s AND response IS NOT NULL
            ORDER BY time DESC, id DESC
            LIMIT %d OFFSET %d",
            $session_id, $session_id, $per_page, $offset
        );
        
        $results = $wpdb->get_results($query, ARRAY_A);
        
        // Odwróć wyniki, aby najstarsze były pierwsze
        return array_reverse($results);
    }
    
    private function count_session_messages($session_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'aica_messages';
        
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM (
                SELECT id FROM $table WHERE session_id = %s
                UNION ALL
                SELECT id FROM $table WHERE session_id = %s AND response IS NOT NULL
            ) as total",
            $session_id, $session_id
        );
        
        return (int)$wpdb->get_var($query);
    }
    
    public function get_sessions_list() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_nonce')) {
            wp_send_json_error(['message' => __('Błąd bezpieczeństwa.', 'ai-chat-assistant')]);
            return;
        }
        
        $user_id = get_current_user_id();
        $per_page = isset($_POST['per_page']) ? max(1, intval($_POST['per_page'])) : 10;
        
        global $wpdb;
        $sessions_table = $wpdb->prefix . 'aica_sessions';
        
        // Pobierz sesje użytkownika
        $query = $wpdb->prepare(
            "SELECT * FROM $sessions_table WHERE user_id = %d ORDER BY updated_at DESC LIMIT %d",
            $user_id, $per_page
        );
        
        $sessions = $wpdb->get_results($query, ARRAY_A);
        
        // Dodaj podgląd dla każdej sesji
        foreach ($sessions as &$session) {
            $session['preview'] = $this->get_session_preview($session['session_id']);
        }
        
        wp_send_json_success(['sessions' => $sessions]);
    }
    
    private function get_session_preview($session_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'aica_messages';
        
        $query = $wpdb->prepare(
            "SELECT message FROM $table WHERE session_id = %s ORDER BY id ASC LIMIT 1",
            $session_id
        );
        
        $message = $wpdb->get_var($query);
        
        if ($message) {
            return wp_trim_words($message, 10, '...');
        }
        
        return '';
    }
    
    public function rename_session() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_nonce')) {
            wp_send_json_error(['message' => __('Błąd bezpieczeństwa.', 'ai-chat-assistant')]);
            return;
        }
        
        if (!isset($_POST['session_id']) || !isset($_POST['title'])) {
            wp_send_json_error(['message' => __('Brak wymaganych danych.', 'ai-chat-assistant')]);
            return;
        }
        
        $session_id = sanitize_text_field($_POST['session_id']);
        $title = sanitize_text_field($_POST['title']);
        
        if (empty($title)) {
            wp_send_json_error(['message' => __('Tytuł nie może być pusty.', 'ai-chat-assistant')]);
            return;
        }
        
        $user_id = get_current_user_id();
        if (!$this->user_owns_session($user_id, $session_id)) {
            wp_send_json_error(['message' => __('Brak dostępu do tej sesji.', 'ai-chat-assistant')]);
            return;
        }
        
        $this->update_session_title($session_id, $title);
        
        wp_send_json_success(['title' => $title]);
    }
    
    public function delete_session() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_nonce')) {
            wp_send_json_error(['message' => __('Błąd bezpieczeństwa.', 'ai-chat-assistant')]);
            return;
        }
        
        if (!isset($_POST['session_id'])) {
            wp_send_json_error(['message' => __('Brak ID sesji.', 'ai-chat-assistant')]);
            return;
        }
        
        $session_id = sanitize_text_field($_POST['session_id']);
        
        $user_id = get_current_user_id();
        if (!$this->user_owns_session($user_id, $session_id)) {
            wp_send_json_error(['message' => __('Brak dostępu do tej sesji.', 'ai-chat-assistant')]);
            return;
        }
        
        global $wpdb;
        $sessions_table = $wpdb->prefix . 'aica_sessions';
        $messages_table = $wpdb->prefix . 'aica_messages';
        
        // Rozpocznij transakcję
        $wpdb->query('START TRANSACTION');
        
        // Usuń wiadomości
        $wpdb->delete($messages_table, ['session_id' => $session_id]);
        
        // Usuń sesję
        $wpdb->delete($sessions_table, ['session_id' => $session_id]);
        
        // Zatwierdź transakcję
        $wpdb->query('COMMIT');
        
        wp_send_json_success();
    }
    
    public function save_message() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_nonce')) {
            wp_send_json_error(['message' => __('Błąd bezpieczeństwa.', 'ai-chat-assistant')]);
            return;
        }
        
        if (!isset($_POST['session_id']) || !isset($_POST['user_message']) || !isset($_POST['assistant_response'])) {
            wp_send_json_error(['message' => __('Brak wymaganych danych.', 'ai-chat-assistant')]);
            return;
        }
        
        $session_id = sanitize_text_field($_POST['session_id']);
        $user_message = sanitize_textarea_field($_POST['user_message']);
        $assistant_response = $_POST['assistant_response']; // Nie używaj sanitize_textarea_field, aby zachować formatowanie HTML
        
        $user_id = get_current_user_id();
        if (!$this->user_owns_session($user_id, $session_id)) {
            wp_send_json_error(['message' => __('Brak dostępu do tej sesji.', 'ai-chat-assistant')]);
            return;
        }
        
        $message_id = $this->add_message_to_session($session_id, 'user', $user_message);
        if ($message_id) {
            $this->add_message_to_session($session_id, 'assistant', $assistant_response);
            $this->update_session_time($session_id);
            
            wp_send_json_success();
        } else {
            wp_send_json_error(['message' => __('Nie udało się zapisać wiadomości.', 'ai-chat-assistant')]);
        }
    }
    
    public function upload_file() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_nonce')) {
            wp_send_json_error(['message' => __('Błąd bezpieczeństwa.', 'ai-chat-assistant')]);
            return;
        }
        
        if (!isset($_FILES['file'])) {
            wp_send_json_error(['message' => __('Brak pliku.', 'ai-chat-assistant')]);
            return;
        }
        
        $file_service = new \AICA\Services\FileService();
        $result = $file_service->handle_upload($_FILES['file']);
        
        if ($result['success']) {
            $file_content = $file_service->read_file($result['file_path']);
            
            if ($file_content !== false) {
                $result['file_content'] = $file_content;
                wp_send_json_success($result);
            } else {
                wp_send_json_error(['message' => __('Nie udało się odczytać zawartości pliku.', 'ai-chat-assistant')]);
            }
        } else {
            wp_send_json_error($result);
        }
    }
    
    public function get_repository_files() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_nonce')) {
            wp_send_json_error(['message' => __('Błąd bezpieczeństwa.', 'ai-chat-assistant')]);
            return;
        }
        
        if (!isset($_POST['repository_id'])) {
            wp_send_json_error(['message' => __('Brak ID repozytorium.', 'ai-chat-assistant')]);
            return;
        }
        
        $repository_id = sanitize_text_field($_POST['repository_id']);
        $path = isset($_POST['path']) ? sanitize_text_field($_POST['path']) : '';
        
        $repo_service = new \AICA\Services\RepositoryService();
        $repository = $repo_service->get_repository($repository_id);
        
        if (!$repository) {
            wp_send_json_error(['message' => __('Repozytorium nie istnieje.', 'ai-chat-assistant')]);
            return;
        }
        
        $files = $repo_service->get_repository_files($repository_id, $path);
        
        wp_send_json_success([
            'repository' => $repository,
            'files' => $files
        ]);
    }
    
    public function get_file_content() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_nonce')) {
            wp_send_json_error(['message' => __('Błąd bezpieczeństwa.', 'ai-chat-assistant')]);
            return;
        }
        
        if (!isset($_POST['repository_id']) || !isset($_POST['file_path'])) {
            wp_send_json_error(['message' => __('Brak wymaganych danych.', 'ai-chat-assistant')]);
            return;
        }
        
        $repository_id = sanitize_text_field($_POST['repository_id']);
        $file_path = sanitize_text_field($_POST['file_path']);
        
        $repo_service = new \AICA\Services\RepositoryService();
        $file_content = $repo_service->get_file_content($repository_id, $file_path);
        
        if ($file_content === false) {
            wp_send_json_error(['message' => __('Nie udało się odczytać zawartości pliku.', 'ai-chat-assistant')]);
            return;
        }
        
        // Przygotuj informacje o pliku
        $path_parts = pathinfo($file_path);
        $file_info = [
            'name' => $path_parts['basename'],
            'path' => $file_path,
            'extension' => isset($path_parts['extension']) ? $path_parts['extension'] : ''
        ];
        
        wp_send_json_success([
            'content' => $file_content,
            'file_info' => $file_info
        ]);
    }
}