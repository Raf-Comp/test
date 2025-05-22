<?php
namespace AICA\Ajax;

class DiagnosticsHandler {
    public function __construct() {
        // Rejestracja akcji AJAX dla diagnostyki
        add_action('wp_ajax_aica_repair_database', [$this, 'repair_database']);
        add_action('wp_ajax_aica_test_api_connection_diagnostics', [$this, 'test_api_connection_diagnostics']);
        add_action('wp_ajax_aica_delete_session', [$this, 'delete_session']);
    }
    
    /**
     * Naprawa bazy danych
     */
    public function repair_database() {
        // Upewnij się, że funkcja aica_log jest dostępna
        if (!function_exists('aica_log') && file_exists(AICA_PLUGIN_DIR . 'includes/Helpers.php')) {
            require_once AICA_PLUGIN_DIR . 'includes/Helpers.php';
        }
        
        // Logowanie dla celów diagnostycznych
        if (function_exists('aica_log')) {
            aica_log('Rozpoczęto naprawę bazy danych', 'info');
        }
        
        // Sprawdzenie nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_diagnostics_nonce')) {
            if (function_exists('aica_log')) {
                aica_log('Błąd weryfikacji nonce podczas naprawy bazy danych', 'error');
            }
            wp_send_json_error([
                'message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')
            ]);
            return;
        }
        
        // Sprawdzenie uprawnień
        if (!current_user_can('manage_options')) {
            if (function_exists('aica_log')) {
                aica_log('Próba naprawy bazy danych przez użytkownika bez uprawnień', 'error');
            }
            wp_send_json_error([
                'message' => __('Nie masz wystarczających uprawnień.', 'ai-chat-assistant')
            ]);
            return;
        }
        
        try {
            // Ładujemy klasę Installer jeśli nie jest załadowana
            if (!class_exists('\AICA\Installer')) {
                // Sprawdzamy istnienie pliku Installer.php
                $installer_path = AICA_PLUGIN_DIR . 'includes/Installer.php';
                if (!file_exists($installer_path)) {
                    if (function_exists('aica_log')) {
                        aica_log('Nie znaleziono pliku Installer.php: ' . $installer_path, 'error');
                    }
                    wp_send_json_error([
                        'message' => __('Nie znaleziono pliku instalacyjnego.', 'ai-chat-assistant')
                    ]);
                    return;
                }
                
                // Ładujemy plik Installer.php
                require_once $installer_path;
            }
            
            // Tworzenie instancji klasy Installer
            $installer = new \AICA\Installer();
            
            // Sprawdzenie czy metoda repair_database istnieje
            if (!method_exists($installer, 'repair_database')) {
                if (function_exists('aica_log')) {
                    aica_log('Metoda repair_database nie istnieje w klasie Installer', 'error');
                }
                
                // Sprawdźmy czy istnieje metoda create_tables
                if (method_exists($installer, 'create_tables')) {
                    if (function_exists('aica_log')) {
                        aica_log('Używam metody create_tables jako alternatywy', 'info');
                    }
                    
                    // Użyj metody create_tables jako alternatywy
                    $result = $installer->create_tables();
                    
                    if ($result) {
                        if (function_exists('aica_log')) {
                            aica_log('Naprawa bazy danych zakończona powodzeniem (użyto create_tables)', 'info');
                        }
                        wp_send_json_success([
                            'message' => __('Pomyślnie naprawiono tabele bazy danych.', 'ai-chat-assistant')
                        ]);
                    } else {
                        if (function_exists('aica_log')) {
                            aica_log('Naprawa bazy danych nie powiodła się (użyto create_tables)', 'error');
                        }
                        wp_send_json_error([
                            'message' => __('Wystąpił błąd podczas naprawy bazy danych.', 'ai-chat-assistant')
                        ]);
                    }
                    return;
                }
                
                wp_send_json_error([
                    'message' => __('Błąd instalatora: brak metody naprawy bazy danych.', 'ai-chat-assistant')
                ]);
                return;
            }
            
            // Wywołanie metody naprawiającej tabele
            $result = $installer->repair_database();
            
            if ($result) {
                if (function_exists('aica_log')) {
                    aica_log('Naprawa bazy danych zakończona powodzeniem', 'info');
                }
                wp_send_json_success([
                    'message' => __('Pomyślnie naprawiono tabele bazy danych.', 'ai-chat-assistant')
                ]);
            } else {
                if (function_exists('aica_log')) {
                    aica_log('Naprawa bazy danych nie powiodła się', 'error');
                }
                wp_send_json_error([
                    'message' => __('Wystąpił błąd podczas naprawy bazy danych.', 'ai-chat-assistant')
                ]);
            }
            
        } catch (\Exception $e) {
            if (function_exists('aica_log')) {
                aica_log('Wyjątek podczas naprawy bazy danych: ' . $e->getMessage(), 'error');
                // Dodaj logowanie pełnej informacji o błędzie
                aica_log('Pełny błąd: ' . $e->__toString(), 'error');
            }
            wp_send_json_error([
                'message' => __('Wystąpił błąd: ', 'ai-chat-assistant') . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Usuwanie sesji czatu
     */
    public function delete_session() {
        // Sprawdzenie nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_diagnostics_nonce')) {
            wp_send_json_error([
                'message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')
            ]);
            return;
        }
        
        // Sprawdzenie ID sesji
        if (empty($_POST['session_id'])) {
            wp_send_json_error([
                'message' => __('Nie określono ID sesji.', 'ai-chat-assistant')
            ]);
            return;
        }
        
        $session_id = sanitize_text_field($_POST['session_id']);
        $user_id = get_current_user_id();
        
        global $wpdb;
        
        // Najpierw usuwamy wiadomości powiązane z sesją
        $wpdb->delete(
            $wpdb->prefix . 'aica_messages',
            ['session_id' => $session_id],
            ['%s']
        );
        
        // Następnie usuwamy sesję
        $result = $wpdb->delete(
            $wpdb->prefix . 'aica_sessions',
            [
                'session_id' => $session_id,
                'user_id' => $user_id
            ],
            ['%s', '%d']
        );
        
        if ($result !== false) {
            wp_send_json_success([
                'message' => __('Sesja została pomyślnie usunięta.', 'ai-chat-assistant')
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Wystąpił błąd podczas usuwania sesji.', 'ai-chat-assistant')
            ]);
        }
    }
    
    /**
     * Testowanie połączenia z API dla diagnostyki
     */
    public function test_api_connection_diagnostics() {
        // Sprawdzenie nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_diagnostics_nonce')) {
            wp_send_json_error([
                'message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdzenie typu API
        if (!isset($_POST['api_type'])) {
            wp_send_json_error([
                'message' => __('Nie określono typu API.', 'ai-chat-assistant')
            ]);
        }
        
        $api_type = sanitize_text_field($_POST['api_type']);
        
        switch ($api_type) {
            case 'claude':
                $this->test_claude_connection_diagnostics();
                break;
            case 'github':
                $this->test_github_connection_diagnostics();
                break;
            case 'gitlab':
                $this->test_gitlab_connection_diagnostics();
                break;
            case 'bitbucket':
                $this->test_bitbucket_connection_diagnostics();
                break;
            default:
                wp_send_json_error([
                    'message' => __('Nieznany typ API.', 'ai-chat-assistant')
                ]);
        }
    }
    
    /**
     * Testowanie połączenia z API Claude dla diagnostyki
     */
    private function test_claude_connection_diagnostics() {
        // Używamy klucza API z opcji wtyczki
        $api_key = aica_get_option('claude_api_key', '');
        
        if (empty($api_key)) {
            wp_send_json_error([
                'message' => __('Klucz API Claude nie jest ustawiony.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdzenie czy klasa istnieje
        if (!class_exists('\AICA\API\ClaudeClient')) {
            wp_send_json_error([
                'message' => __('Nie znaleziono klasy ClaudeClient.', 'ai-chat-assistant')
            ]);
            return;
        }
        
        try {
            $claude_client = new \AICA\API\ClaudeClient($api_key);
            $result = $claude_client->test_connection();
            
            if ($result) {
                // Pobierz modele Claude
                $models = $claude_client->get_available_models();
                
                wp_send_json_success([
                    'message' => __('Połączenie z API Claude działa poprawnie.', 'ai-chat-assistant'),
                    'models' => $models
                ]);
            } else {
                wp_send_json_error([
                    'message' => __('Nie udało się połączyć z API Claude. Sprawdź klucz API i spróbuj ponownie.', 'ai-chat-assistant')
                ]);
            }
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => __('Błąd podczas połączenia z API Claude: ', 'ai-chat-assistant') . $e->getMessage(),
                'error_details' => $e->__toString()
            ]);
        }
    }
    
    /**
     * Testowanie połączenia z API GitHub dla diagnostyki
     */
    private function test_github_connection_diagnostics() {
        // Używamy tokenu GitHub z opcji wtyczki
        $token = aica_get_option('github_token', '');
        
        if (empty($token)) {
            wp_send_json_error([
                'message' => __('Token GitHub nie jest ustawiony.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdzenie czy klasa istnieje
        if (!class_exists('\AICA\API\GitHubClient')) {
            // Próba załadowania pliku
            $git_client_path = AICA_PLUGIN_DIR . 'includes/API/GitHubClient.php';
            if (file_exists($git_client_path)) {
                require_once $git_client_path;
            } else {
                wp_send_json_error([
                    'message' => __('Nie znaleziono klasy GitHubClient.', 'ai-chat-assistant')
                ]);
                return;
            }
        }
        
        try {
            $github_client = new \AICA\API\GitHubClient($token);
            $result = $github_client->test_connection();
            
            if ($result) {
                wp_send_json_success([
                    'message' => __('Połączenie z API GitHub działa poprawnie.', 'ai-chat-assistant')
                ]);
            } else {
                wp_send_json_error([
                    'message' => __('Nie udało się połączyć z API GitHub. Sprawdź token i spróbuj ponownie.', 'ai-chat-assistant')
                ]);
            }
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => __('Błąd podczas połączenia z API GitHub: ', 'ai-chat-assistant') . $e->getMessage(),
                'error_details' => $e->__toString()
            ]);
        }
    }
    
    /**
     * Testowanie połączenia z API GitLab dla diagnostyki
     */
    private function test_gitlab_connection_diagnostics() {
        // Używamy tokenu GitLab z opcji wtyczki
        $token = aica_get_option('gitlab_token', '');
        
        if (empty($token)) {
            wp_send_json_error([
                'message' => __('Token GitLab nie jest ustawiony.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdzenie czy klasa istnieje
        if (!class_exists('\AICA\API\GitLabClient')) {
            // Próba załadowania pliku
            $git_client_path = AICA_PLUGIN_DIR . 'includes/API/GitLabClient.php';
            if (file_exists($git_client_path)) {
                require_once $git_client_path;
            } else {
                wp_send_json_error([
                    'message' => __('Nie znaleziono klasy GitLabClient.', 'ai-chat-assistant')
                ]);
                return;
            }
        }
        
        try {
            $gitlab_client = new \AICA\API\GitLabClient($token);
            $result = $gitlab_client->test_connection();
            
            if ($result) {
                wp_send_json_success([
                    'message' => __('Połączenie z API GitLab działa poprawnie.', 'ai-chat-assistant')
                ]);
            } else {
                wp_send_json_error([
                    'message' => __('Nie udało się połączyć z API GitLab. Sprawdź token i spróbuj ponownie.', 'ai-chat-assistant')
                ]);
            }
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => __('Błąd podczas połączenia z API GitLab: ', 'ai-chat-assistant') . $e->getMessage(),
                'error_details' => $e->__toString()
            ]);
        }
    }
    
    /**
     * Testowanie połączenia z API Bitbucket dla diagnostyki
     */
    private function test_bitbucket_connection_diagnostics() {
        // Używamy danych Bitbucket z opcji wtyczki
        $username = aica_get_option('bitbucket_username', '');
        $password = aica_get_option('bitbucket_app_password', '');
        
        if (empty($username) || empty($password)) {
            wp_send_json_error([
                'message' => __('Dane logowania Bitbucket nie są ustawione.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdzenie czy klasa istnieje
        if (!class_exists('\AICA\API\BitbucketClient')) {
            // Próba załadowania pliku
            $git_client_path = AICA_PLUGIN_DIR . 'includes/API/BitbucketClient.php';
            if (file_exists($git_client_path)) {
                require_once $git_client_path;
            } else {
                wp_send_json_error([
                    'message' => __('Nie znaleziono klasy BitbucketClient.', 'ai-chat-assistant')
                ]);
                return;
            }
        }
        
        try {
            $bitbucket_client = new \AICA\API\BitbucketClient($username, $password);
            $result = $bitbucket_client->test_connection();
            
            if ($result) {
                wp_send_json_success([
                    'message' => __('Połączenie z API Bitbucket działa poprawnie.', 'ai-chat-assistant')
                ]);
            } else {
                wp_send_json_error([
                    'message' => __('Nie udało się połączyć z API Bitbucket. Sprawdź dane logowania i spróbuj ponownie.', 'ai-chat-assistant')
                ]);
            }
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => __('Błąd podczas połączenia z API Bitbucket: ', 'ai-chat-assistant') . $e->getMessage(),
                'error_details' => $e->__toString()
            ]);
        }
    }
}