<?php
namespace AICA\Admin;
use AICA\Services\ChatService;
use function aica_verify_ajax_request;
use function aica_verify_required_params;
use function aica_get_history_translations;
use function aica_format_conversation_as_text;

class HistoryPage {
    private $chat_service;

    public function __construct() {
        $this->chat_service = new ChatService();
        $this->init_ajax_handlers();
    }

    // Alias dla render_page wywoływany przez PageManager
    public function render() {
        $this->render_page();
    }

    public function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Nie masz wystarczających uprawnień, aby uzyskać dostęp do tej strony.', 'ai-chat-assistant'));
        }

        // Przekazanie nonce do szablonu
        $history_nonce = wp_create_nonce('aica_history_nonce');
        
        // Dodanie skryptów i stylów
        wp_enqueue_style('aica-history-css', AICA_PLUGIN_URL . 'assets/css/history.css', array(), AICA_VERSION);
        wp_enqueue_script('aica-history-js', AICA_PLUGIN_URL . 'assets/js/history.js', array('jquery'), AICA_VERSION, true);
        
        // Przekazanie danych do skryptu
        wp_localize_script('aica-history-js', 'aica_history', array(
            'nonce' => $history_nonce,
            'chat_url' => admin_url('admin.php?page=ai-chat-assistant'),
            'i18n' => \aica_get_history_translations()
        ));
        
        include_once AICA_PLUGIN_DIR . 'templates/admin/history.php';
    }

    public function init_ajax_handlers() {
        add_action('wp_ajax_aica_get_sessions_list', [$this, 'ajax_get_sessions_list']);
        add_action('wp_ajax_aica_get_chat_history', [$this, 'ajax_get_chat_history']);
        add_action('wp_ajax_aica_delete_session', [$this, 'ajax_delete_session']);
        add_action('wp_ajax_aica_export_conversation', [$this, 'ajax_export_conversation']);
        add_action('wp_ajax_aica_duplicate_conversation', [$this, 'ajax_duplicate_conversation']);
    }
    
    // Pobieranie listy sesji
    public function ajax_get_sessions_list() {
        if (!aica_verify_ajax_request('aica_history_nonce')) {
            return;
        }
        
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 10;
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $sort = isset($_POST['sort']) ? sanitize_text_field($_POST['sort']) : 'newest';
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
        
        $offset = ($page - 1) * $per_page;
        
        $args = [
            'offset' => $offset,
            'limit' => $per_page,
            'search' => $search,
            'order' => $sort === 'oldest' ? 'ASC' : 'DESC',
        ];
        
        if (!empty($date_from)) {
            $args['date_from'] = $date_from;
        }
        
        if (!empty($date_to)) {
            $args['date_to'] = $date_to;
        }
        
        $sessions = $this->chat_service->get_sessions($args);
        $total_sessions = $this->chat_service->count_sessions($search, $date_from, $date_to);
        $total_pages = ceil($total_sessions / $per_page);
        
        wp_send_json_success([
            'sessions' => $sessions,
            'pagination' => [
                'total_items' => $total_sessions,
                'total_pages' => $total_pages,
                'current_page' => $page,
                'per_page' => $per_page
            ]
        ]);
    }
    
    // Pobieranie historii czatu dla konkretnej sesji
    public function ajax_get_chat_history() {
        if (!aica_verify_ajax_request('aica_history_nonce')) {
            return;
        }
        
        if (!aica_verify_required_params(['session_id'])) {
            return;
        }
        
        $session_id = sanitize_text_field($_POST['session_id']);
        $session = $this->chat_service->get_session($session_id);
        
        if (!$session) {
            wp_send_json_error(['message' => __('Nie znaleziono sesji.', 'ai-chat-assistant')]);
            return;
        }
        
        // Pobierz wszystkie wiadomości sesji
        $messages_objects = $this->chat_service->get_all_session_messages($session_id);
        
        // Konwersja obiektów na tablice asocjacyjne dla lepszej obsługi w JavaScript
        $messages = [];
        foreach ($messages_objects as $msg) {
            if (empty($msg->content)) {
                continue;
            }
            
            $messages[] = [
                'id' => $msg->id,
                'content' => $msg->content,
                'type' => $msg->type,
                'time' => $msg->time
            ];
        }
        
        wp_send_json_success([
            'messages' => $messages,
            'title' => $session->title,
            'session' => [
                'id' => $session->id,
                'session_id' => $session->session_id,
                'user_id' => $session->user_id,
                'title' => $session->title,
                'created_at' => $session->created_at,
                'updated_at' => $session->updated_at
            ],
            'pagination' => [
                'total_items' => count($messages),
                'current_page' => 1,
                'total_pages' => 1
            ]
        ]);
    }
    
    // Usuwanie sesji czatu
    public function ajax_delete_session() {
        if (!aica_verify_ajax_request('aica_history_nonce')) {
            return;
        }
        
        if (!aica_verify_required_params(['session_id'])) {
            return;
        }
        
        $session_id = sanitize_text_field($_POST['session_id']);
        $result = $this->chat_service->delete_session($session_id);
        
        if ($result) {
            wp_send_json_success(['message' => __('Sesja została pomyślnie usunięta.', 'ai-chat-assistant')]);
        } else {
            wp_send_json_error(['message' => __('Wystąpił błąd podczas usuwania sesji.', 'ai-chat-assistant')]);
        }
    }
    
    // Eksportowanie konwersacji
    public function ajax_export_conversation() {
        if (!aica_verify_ajax_request('aica_history_nonce')) {
            return;
        }
        
        if (!aica_verify_required_params(['session_id'])) {
            return;
        }
        
        $session_id = sanitize_text_field($_POST['session_id']);
        $format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : 'json';
        
        // Pobierz dane sesji
        $session = $this->chat_service->get_session($session_id);
        
        if (!$session) {
            wp_send_json_error(['message' => __('Nie znaleziono sesji.', 'ai-chat-assistant')]);
            return;
        }
        
        // Pobierz wiadomości
        $messages_objects = $this->chat_service->get_all_session_messages($session_id);
        
        // Formatuj konwersację
        $content = '';
        $filename = 'conversation-' . $session_id;
        
        switch ($format) {
            case 'text':
                $content = aica_format_conversation_as_text($session, $messages_objects);
                $filename .= '.txt';
                $content_type = 'text/plain';
                break;
                
            case 'html':
                $content = aica_format_conversation_as_html($session, $messages_objects);
                $filename .= '.html';
                $content_type = 'text/html';
                break;
                
            default:
                $content = json_encode([
                    'session' => $session,
                    'messages' => $messages_objects
                ], JSON_PRETTY_PRINT);
                $filename .= '.json';
                $content_type = 'application/json';
        }
        
        // Wyślij plik do pobrania
        header('Content-Type: ' . $content_type);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));
        echo $content;
        exit;
    }
    
    // Duplikowanie konwersacji
    public function ajax_duplicate_conversation() {
        if (!aica_verify_ajax_request('aica_history_nonce')) {
            return;
        }
        
        if (!aica_verify_required_params(['session_id'])) {
            return;
        }
        
        $session_id = sanitize_text_field($_POST['session_id']);
        $result = $this->chat_service->duplicate_session($session_id);
        
        if ($result) {
            wp_send_json_success(['message' => __('Rozmowa została pomyślnie zduplikowana.', 'ai-chat-assistant')]);
        } else {
            wp_send_json_error(['message' => __('Wystąpił błąd podczas duplikowania rozmowy.', 'ai-chat-assistant')]);
        }
    }
}