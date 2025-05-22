<?php
declare(strict_types=1);

namespace AICA\Services;

use AICA\Helpers\SecurityHelper;
use AICA\Helpers\ValidationHelper;
use AICA\Helpers\TableHelper;

class ChatService {
    public function __construct(
        private readonly AnthropicService $anthropic,
        private readonly string $table_name = TableHelper::get_table_name('chat_history')
    ) {}

    public function send_message(string $message, int $user_id): array {
        if (!ValidationHelper::validate_model_id($this->anthropic->get_model())) {
            return [
                'success' => false,
                'error' => 'Invalid model selected'
            ];
        }

        $response = $this->anthropic->send_message($message);
        
        if ($response['success']) {
            $this->save_to_history($user_id, $message, $response['response']);
        }

        return $response;
    }

    public function get_history(int $user_id, int $limit = 10): array {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
            WHERE user_id = %d 
            ORDER BY created_at DESC 
            LIMIT %d",
            $user_id,
            $limit
        );

        return $wpdb->get_results($query, ARRAY_A) ?: [];
    }

    public function clear_history(int $user_id): bool {
        global $wpdb;
        
        $result = $wpdb->delete(
            $this->table_name,
            ['user_id' => $user_id],
            ['%d']
        );

        return $result !== false;
    }

    private function save_to_history(int $user_id, string $message, string $response): bool {
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->table_name,
            [
                'user_id' => $user_id,
                'message' => $message,
                'response' => $response,
                'created_at' => current_time('mysql')
            ],
            ['%d', '%s', '%s', '%s']
        );

        return $result !== false;
    }

    /**
     * Tworzy nową sesję czatu
     */
    public function create_session(string $title = ''): string|false {
        global $wpdb;
        $sessions_table = $wpdb->prefix . 'aica_sessions';
        
        $session_id = wp_generate_uuid4();
        $user_id = get_current_user_id();
        
        if (empty($title)) {
            $title = __('Nowa rozmowa', 'ai-chat-assistant');
        }
        
        $wpdb->insert(
            $sessions_table,
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
    
    /**
     * Aktualizuje tytuł sesji
     */
    public function update_session_title(string $session_id, string $title): bool {
        global $wpdb;
        $sessions_table = $wpdb->prefix . 'aica_sessions';
        
        // Sprawdź czy sesja należy do użytkownika
        $session = $this->get_session($session_id);
        if (!$session) {
            return false;
        }
        
        return $wpdb->update(
            $sessions_table,
            [
                'title' => $title,
                'updated_at' => current_time('mysql')
            ],
            ['session_id' => $session_id],
            ['%s', '%s'],
            ['%s']
        ) !== false;
    }
    
    /**
     * Pobiera historię konwersacji
     */
    public function get_conversation_history(string $session_id): array {
        // Sprawdź cache
        $cached_history = $this->cache_service->get_history($session_id);
        if ($cached_history !== false) {
            return $cached_history;
        }

        // Pobierz historię z bazy danych
        global $wpdb;
        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}aica_messages 
            WHERE session_id = %d 
            ORDER BY created_at ASC",
            $session_id
        ));

        $history = [];
        foreach ($messages as $message) {
            $history[] = [
                'role' => $message->role,
                'content' => $message->content
            ];
        }

        // Zapisz w cache
        $this->cache_service->set_history($session_id, $history);
        
        return $history;
    }
    
    /**
     * Pobiera sesje z filtrowaniem
     */
    public function get_sessions(array $args = []): array {
        $defaults = [
            'offset' => 0,
            'limit' => 10,
            'search' => '',
            'order' => 'DESC',
            'date_from' => '',
            'date_to' => ''
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        global $wpdb;
        $table = $wpdb->prefix . 'aica_sessions';
        
        // Sprawdź czy tabela istnieje przed zapytaniem
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            return [];
        }
        
        $query = "SELECT * FROM $table WHERE 1=1";
        $query_args = [];
        
        if (!empty($args['search'])) {
            $query .= " AND title LIKE %s";
            $query_args[] = '%' . $wpdb->esc_like($args['search']) . '%';
        }
        
        if (!empty($args['date_from'])) {
            $query .= " AND created_at >= %s";
            $query_args[] = $args['date_from'] . ' 00:00:00';
        }
        
        if (!empty($args['date_to'])) {
            $query .= " AND created_at <= %s";
            $query_args[] = $args['date_to'] . ' 23:59:59';
        }
        
        $query .= " ORDER BY created_at " . $args['order'];
        $query .= " LIMIT %d OFFSET %d";
        $query_args[] = $args['limit'];
        $query_args[] = $args['offset'];
        
        $prepared_query = empty($query_args) ? $query : $wpdb->prepare($query, $query_args);
        $results = $wpdb->get_results($prepared_query, ARRAY_A);
        
        if (!is_array($results)) {
            return [];
        }
        
        // Dodaj podglądy pierwszej wiadomości do każdej sesji
        foreach ($results as &$session) {
            $preview = $this->get_session_preview($session['session_id']);
            $session['preview'] = $preview ?: '';
        }
        
        return $results;
    }
    
    /**
     * Liczy łączną liczbę sesji z filtrowaniem
     */
    public function count_sessions(string $search = '', string $date_from = '', string $date_to = ''): int {
        global $wpdb;
        $table = $wpdb->prefix . 'aica_sessions';
        
        // Sprawdź czy tabela istnieje przed zapytaniem
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            return 0;
        }
        
        $query = "SELECT COUNT(*) FROM $table WHERE 1=1";
        $query_args = [];
        
        if (!empty($search)) {
            $query .= " AND title LIKE %s";
            $query_args[] = '%' . $wpdb->esc_like($search) . '%';
        }
        
        if (!empty($date_from)) {
            $query .= " AND created_at >= %s";
            $query_args[] = $date_from . ' 00:00:00';
        }
        
        if (!empty($date_to)) {
            $query .= " AND created_at <= %s";
            $query_args[] = $date_to . ' 23:59:59';
        }
        
        $prepared_query = empty($query_args) ? $query : $wpdb->prepare($query, $query_args);
        return (int) $wpdb->get_var($prepared_query);
    }

    /**
     * Pobiera podgląd sesji
     */
    public function get_session_preview(string $session_id): string {
        global $wpdb;
        
        $message = $wpdb->get_var($wpdb->prepare(
            "SELECT content FROM {$wpdb->prefix}aica_messages 
            WHERE session_id = %s 
            ORDER BY created_at ASC 
            LIMIT 1",
            $session_id
        ));
        
        if (!$message) {
            return '';
        }
        
        return wp_trim_words($message, 20);
    }
    
    /**
     * Pobiera sesję
     */
    public function get_session(string $session_id): ?array {
        global $wpdb;
        $table = $wpdb->prefix . 'aica_sessions';
        
        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE session_id = %s",
            $session_id
        ), ARRAY_A);
        
        return $session ?: null;
    }
    
    /**
     * Pobiera wiadomości sesji
     */
    public function get_session_messages(string $session_id, int $page = 1, int $per_page = 5): array {
        global $wpdb;
        
        $offset = ($page - 1) * $per_page;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}aica_messages 
            WHERE session_id = %s 
            ORDER BY created_at DESC 
            LIMIT %d OFFSET %d",
            $session_id,
            $per_page,
            $offset
        ), ARRAY_A) ?: [];
    }
    
    /**
     * Pobiera wszystkie wiadomości sesji
     */
    public function get_all_session_messages(string $session_id): array {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}aica_messages 
            WHERE session_id = %s 
            ORDER BY created_at ASC",
            $session_id
        ), ARRAY_A) ?: [];
    }
    
    /**
     * Liczy wiadomości sesji
     */
    public function count_session_messages(string $session_id): int {
        global $wpdb;
        
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}aica_messages 
            WHERE session_id = %s",
            $session_id
        ));
    }
    
    /**
     * Usuwa sesję
     */
    public function delete_session(string $session_id): bool {
        global $wpdb;
        
        // Sprawdź czy sesja należy do użytkownika
        $session = $this->get_session($session_id);
        if (!$session) {
            return false;
        }
        
        // Usuń wiadomości
        $wpdb->delete(
            $wpdb->prefix . 'aica_messages',
            ['session_id' => $session_id],
            ['%s']
        );
        
        // Usuń sesję
        $result = $wpdb->delete(
            $wpdb->prefix . 'aica_sessions',
            ['session_id' => $session_id],
            ['%s']
        );
        
        return $result !== false;
    }
    
    /**
     * Duplikuje sesję
     */
    public function duplicate_session(string $session_id): string|false {
        global $wpdb;
        
        // Pobierz oryginalną sesję
        $session = $this->get_session($session_id);
        if (!$session) {
            return false;
        }
        
        // Utwórz nową sesję
        $new_session_id = wp_generate_uuid4();
        $new_title = sprintf(
            __('Kopia: %s', 'ai-chat-assistant'),
            $session['title']
        );
        
        $wpdb->insert(
            $wpdb->prefix . 'aica_sessions',
            [
                'session_id' => $new_session_id,
                'user_id' => get_current_user_id(),
                'title' => $new_title,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ]
        );
        
        if ($wpdb->last_error) {
            return false;
        }
        
        // Pobierz wiadomości oryginalnej sesji
        $messages = $this->get_all_session_messages($session_id);
        
        // Skopiuj wiadomości
        foreach ($messages as $message) {
            $wpdb->insert(
                $wpdb->prefix . 'aica_messages',
                [
                    'session_id' => $new_session_id,
                    'role' => $message['role'],
                    'content' => $message['content'],
                    'created_at' => current_time('mysql')
                ]
            );
        }
        
        return $new_session_id;
    }
    
    /**
     * Pobiera konwersacje
     */
    public function get_conversations(array $args = []): array {
        return $this->get_sessions($args);
    }
    
    /**
     * Liczy konwersacje
     */
    public function count_conversations(string $search = '', string $date_from = '', string $date_to = ''): int {
        return $this->count_sessions($search, $date_from, $date_to);
    }
    
    /**
     * Pobiera konwersację
     */
    public function get_conversation(string $conversation_id): ?array {
        return $this->get_session($conversation_id);
    }
    
    /**
     * Pobiera wiadomości konwersacji
     */
    public function get_messages(string $conversation_id, int $page = 1, int $per_page = 5): array {
        return $this->get_session_messages($conversation_id, $page, $per_page);
    }
    
    /**
     * Pobiera wszystkie wiadomości konwersacji
     */
    public function get_all_messages(string $conversation_id): array {
        return $this->get_all_session_messages($conversation_id);
    }
    
    /**
     * Liczy wiadomości konwersacji
     */
    public function count_messages(string $conversation_id): int {
        return $this->count_session_messages($conversation_id);
    }
    
    /**
     * Usuwa konwersację
     */
    public function delete_conversation(string $conversation_id): bool {
        return $this->delete_session($conversation_id);
    }
    
    /**
     * Pobiera wszystkie sesje
     */
    public function get_all_sessions(): array {
        return $this->get_sessions(['limit' => -1]);
    }
    
    /**
     * Pobiera sesje użytkownika
     */
    public function get_user_sessions(int $page = 1, int $per_page = 20, string $search = ''): array {
        $args = [
            'offset' => ($page - 1) * $per_page,
            'limit' => $per_page,
            'search' => $search
        ];
        
        return $this->get_sessions($args);
    }
    
    /**
     * Liczy sesje użytkownika
     */
    public function count_user_sessions(string $search = ''): int {
        return $this->count_sessions($search);
    }
    
    /**
     * Czyści historię użytkownika
     */
    public function clear_user_history(): bool {
        global $wpdb;
        $user_id = get_current_user_id();
        
        // Usuń wiadomości
        $wpdb->delete(
            $wpdb->prefix . 'aica_messages',
            ['user_id' => $user_id],
            ['%d']
        );
        
        // Usuń sesje
        $result = $wpdb->delete(
            $wpdb->prefix . 'aica_sessions',
            ['user_id' => $user_id],
            ['%d']
        );
        
        return $result !== false;
    }
    
    /**
     * Zapisuje wiadomość
     */
    public function save_message(string $session_id, string $message, string $response): bool {
        global $wpdb;
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'aica_messages',
            [
                'session_id' => $session_id,
                'role' => 'user',
                'content' => $message,
                'created_at' => current_time('mysql')
            ]
        );
        
        if ($result === false) {
            return false;
        }
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'aica_messages',
            [
                'session_id' => $session_id,
                'role' => 'assistant',
                'content' => $response,
                'created_at' => current_time('mysql')
            ]
        );
        
        return $result !== false;
    }
}