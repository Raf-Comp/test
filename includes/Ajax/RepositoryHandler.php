<?php
namespace AICA\Ajax;

class RepositoryHandler {
    public function __construct() {
        // Rejestracja akcji AJAX dla repozytoriów
		add_action('wp_ajax_aica_test_db', [$this, 'aica_test_db_handler']);
        add_action('wp_ajax_aica_get_repositories', [$this, 'get_repositories']);
        add_action('wp_ajax_aica_get_repository_files', [$this, 'get_repository_files']);
        add_action('wp_ajax_aica_get_file_content', [$this, 'get_file_content']);
        add_action('wp_ajax_aica_add_repository', [$this, 'add_repository']);
        add_action('wp_ajax_aica_delete_repository', [$this, 'delete_repository']);
        add_action('wp_ajax_aica_refresh_repository', [$this, 'refresh_repository']);
    }
    
	
	
	public function aica_test_db_handler() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'aica_repositories';

    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;

    wp_send_json_success(['table_exists' => $table_exists]);
}
	
	
	
	
    /**
     * Pobieranie listy repozytoriów
     */
    public function get_repositories() {
        // Sprawdzenie nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_repository_nonce')) {
            wp_send_json_error([
                'message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')
            ]);
        }
        
        $user_id = aica_get_user_id();
        
        if (!$user_id) {
            wp_send_json_error([
                'message' => __('Nie znaleziono użytkownika.', 'ai-chat-assistant')
            ]);
        }
        
        // Pobieranie repozytoriów
        $repositories = $this->get_user_repositories($user_id);
        
        wp_send_json_success([
            'repositories' => $repositories
        ]);
    }
    
    /**
     * Pobieranie plików repozytorium
     */
    public function get_repository_files() {
        // Sprawdzenie nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_repository_nonce')) {
            wp_send_json_error([
                'message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdzenie ID repozytorium
        if (!isset($_POST['repo_id']) || empty($_POST['repo_id'])) {
            wp_send_json_error([
                'message' => __('Nie podano ID repozytorium.', 'ai-chat-assistant')
            ]);
        }
        
        $repository_id = intval($_POST['repo_id']);
        $user_id = aica_get_user_id();
        
        if (!$user_id) {
            wp_send_json_error([
                'message' => __('Nie znaleziono użytkownika.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdź czy użytkownik ma dostęp do tego repozytorium
        if (!$this->user_owns_repository($user_id, $repository_id)) {
            wp_send_json_error([
                'message' => __('Nie masz dostępu do tego repozytorium.', 'ai-chat-assistant')
            ]);
        }
        
        // Pobierz dane repozytorium
        $repository = $this->get_repository_by_id($repository_id);
        
        if (!$repository) {
            wp_send_json_error([
                'message' => __('Nie znaleziono repozytorium.', 'ai-chat-assistant')
            ]);
        }
        
        // Pobierz pliki repozytorium
        $path = isset($_POST['path']) ? sanitize_text_field($_POST['path']) : '';
        $branch = isset($_POST['branch']) ? sanitize_text_field($_POST['branch']) : $repository['default_branch'];
        
        // Wybierz odpowiedniego klienta w zależności od typu repozytorium
        $client = $this->get_repository_client($repository['type']);
        
        if (!$client) {
            wp_send_json_error([
                'message' => __('Nie udało się uzyskać dostępu do API.', 'ai-chat-assistant')
            ]);
        }
        
        $files = $client->get_directory_contents($repository['full_name'], $path, $branch);
        
        if ($files === false) {
            wp_send_json_error([
                'message' => __('Nie udało się pobrać plików repozytorium.', 'ai-chat-assistant')
            ]);
        }
        
        wp_send_json_success([
            'files' => $files,
            'repository' => $repository
        ]);
    }
    
    /**
     * Pobieranie zawartości pliku
     */
    public function get_file_content() {
        // Sprawdzenie nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_repository_nonce')) {
            wp_send_json_error([
                'message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdzenie danych
        if (!isset($_POST['repo_id']) || empty($_POST['repo_id'])) {
            wp_send_json_error([
                'message' => __('Nie podano ID repozytorium.', 'ai-chat-assistant')
            ]);
        }
        
        if (!isset($_POST['path']) || empty($_POST['path'])) {
            wp_send_json_error([
                'message' => __('Nie podano ścieżki pliku.', 'ai-chat-assistant')
            ]);
        }
        
        $repository_id = intval($_POST['repo_id']);
        $path = sanitize_text_field($_POST['path']);
        $branch = isset($_POST['branch']) ? sanitize_text_field($_POST['branch']) : '';
        $user_id = aica_get_user_id();
        
        if (!$user_id) {
            wp_send_json_error([
                'message' => __('Nie znaleziono użytkownika.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdź czy użytkownik ma dostęp do tego repozytorium
        if (!$this->user_owns_repository($user_id, $repository_id)) {
            wp_send_json_error([
                'message' => __('Nie masz dostępu do tego repozytorium.', 'ai-chat-assistant')
            ]);
        }
        
        // Pobierz dane repozytorium
        $repository = $this->get_repository_by_id($repository_id);
        
        if (!$repository) {
            wp_send_json_error([
                'message' => __('Nie znaleziono repozytorium.', 'ai-chat-assistant')
            ]);
        }
        
        // Wybierz odpowiedniego klienta w zależności od typu repozytorium
        $client = $this->get_repository_client($repository['type']);
        
        if (!$client) {
            wp_send_json_error([
                'message' => __('Nie udało się uzyskać dostępu do API.', 'ai-chat-assistant')
            ]);
        }
        
        // Użyj domyślnej gałęzi, jeśli nie została określona
        if (empty($branch)) {
            $branch = $repository['default_branch'];
        }
        
        $content = $client->get_file_content($repository['full_name'], $path, $branch);
        
        if ($content === false) {
            wp_send_json_error([
                'message' => __('Nie udało się pobrać zawartości pliku.', 'ai-chat-assistant')
            ]);
        }
        
        wp_send_json_success([
            'content' => $content,
            'path' => $path,
            'repository' => $repository
        ]);
    }
    
    /**
     * Dodawanie repozytorium
     */
    public function add_repository() {
        // Sprawdzenie nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_repository_nonce')) {
            wp_send_json_error([
                'message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdzenie danych
        if (!isset($_POST['repo_url']) || empty($_POST['repo_url'])) {
            wp_send_json_error([
                'message' => __('Nie podano URL repozytorium.', 'ai-chat-assistant')
            ]);
        }
        
        $repo_url = esc_url_raw($_POST['repo_url']);
        $repo_type = sanitize_text_field($_POST['repo_type']);
        $repo_name = sanitize_text_field($_POST['repo_name']);
        $repo_owner = sanitize_text_field($_POST['repo_owner']);
        $repo_description = sanitize_text_field($_POST['repo_description']);
        $user_id = aica_get_user_id();
        
        if (!$user_id) {
            wp_send_json_error([
                'message' => __('Nie znaleziono użytkownika.', 'ai-chat-assistant')
            ]);
        }
        
        // Walidacja typu repozytorium
        if (!in_array($repo_type, ['github', 'gitlab', 'bitbucket'])) {
            wp_send_json_error([
                'message' => __('Nieobsługiwany typ repozytorium.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdź czy repozytorium już istnieje
        if ($this->repository_exists($user_id, $repo_type, $repo_owner . '/' . $repo_name)) {
            wp_send_json_error([
                'message' => __('To repozytorium zostało już dodane.', 'ai-chat-assistant')
            ]);
        }
        
        // Dodaj repozytorium
        $repository_id = $this->add_repository_to_user(
            $user_id,
            $repo_type,
            $repo_name,
            $repo_owner . '/' . $repo_name,
            $repo_description,
            'main',  // Domyślna gałąź, będzie zaktualizowana przy pierwszym odświeżeniu
            '',  // Brak URL awatara
            $repo_url
        );
        
        if (!$repository_id) {
            wp_send_json_error([
                'message' => __('Nie udało się dodać repozytorium.', 'ai-chat-assistant')
            ]);
        }
        
        // Odśwież dane repozytorium
        $client = $this->get_repository_client($repo_type);
        
        if ($client) {
            $repo_info = $client->get_repository_info($repo_owner . '/' . $repo_name);
            
            if ($repo_info) {
                $this->update_repository(
                    $repository_id,
                    $repo_info['name'],
                    $repo_info['description'] ?? $repo_description,
                    $repo_info['default_branch'] ?? 'main',
                    $repo_info['avatar_url'] ?? '',
                    $repo_info['html_url'] ?? $repo_url
                );
            }
        }
        
        wp_send_json_success([
            'message' => __('Repozytorium zostało dodane pomyślnie.', 'ai-chat-assistant'),
            'repository_id' => $repository_id,
            'repository' => $this->get_repository_by_id($repository_id)
        ]);
    }
    
    /**
     * Usuwanie repozytorium
     */
    public function delete_repository() {
        // Sprawdzenie nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_repository_nonce')) {
            wp_send_json_error([
                'message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdzenie ID repozytorium
        if (!isset($_POST['repo_id']) || empty($_POST['repo_id'])) {
            wp_send_json_error([
                'message' => __('Nie podano ID repozytorium.', 'ai-chat-assistant')
            ]);
        }
        
        $repository_id = intval($_POST['repo_id']);
        $user_id = aica_get_user_id();
        
        if (!$user_id) {
            wp_send_json_error([
                'message' => __('Nie znaleziono użytkownika.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdź czy użytkownik ma dostęp do tego repozytorium
        if (!$this->user_owns_repository($user_id, $repository_id)) {
            wp_send_json_error([
                'message' => __('Nie masz dostępu do tego repozytorium.', 'ai-chat-assistant')
            ]);
        }
        
        // Usuń repozytorium
        $result = $this->delete_repository_by_id($repository_id);
        
        if ($result) {
            wp_send_json_success([
                'message' => __('Repozytorium zostało usunięte.', 'ai-chat-assistant')
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Nie udało się usunąć repozytorium.', 'ai-chat-assistant')
            ]);
        }
    }
    
    /**
     * Odświeżanie repozytorium
     */
    public function refresh_repository() {
        // Sprawdzenie nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_repository_nonce')) {
            wp_send_json_error([
                'message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdzenie ID repozytorium
        if (!isset($_POST['repo_id']) || empty($_POST['repo_id'])) {
            wp_send_json_error([
                'message' => __('Nie podano ID repozytorium.', 'ai-chat-assistant')
            ]);
        }
        
        $repository_id = intval($_POST['repo_id']);
        $user_id = aica_get_user_id();
        
        if (!$user_id) {
            wp_send_json_error([
                'message' => __('Nie znaleziono użytkownika.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdź czy użytkownik ma dostęp do tego repozytorium
        if (!$this->user_owns_repository($user_id, $repository_id)) {
            wp_send_json_error([
                'message' => __('Nie masz dostępu do tego repozytorium.', 'ai-chat-assistant')
            ]);
        }
        
        // Pobierz dane repozytorium
        $repository = $this->get_repository_by_id($repository_id);
        
        if (!$repository) {
            wp_send_json_error([
                'message' => __('Nie znaleziono repozytorium.', 'ai-chat-assistant')
            ]);
        }
        
        // Wybierz odpowiedniego klienta w zależności od typu repozytorium
        $client = $this->get_repository_client($repository['type']);
        
        if (!$client) {
            wp_send_json_error([
                'message' => __('Nie udało się uzyskać dostępu do API.', 'ai-chat-assistant')
            ]);
        }
        
        // Pobierz aktualne informacje o repozytorium
        $repo_info = $client->get_repository_info($repository['full_name']);
        
        if (!$repo_info) {
            wp_send_json_error([
                'message' => __('Nie udało się pobrać informacji o repozytorium. Sprawdź czy masz dostęp do tego repozytorium.', 'ai-chat-assistant')
            ]);
        }
        
        // Aktualizuj dane repozytorium
        $result = $this->update_repository(
            $repository_id,
            $repo_info['name'],
            $repo_info['description'] ?? $repository['description'],
            $repo_info['default_branch'] ?? $repository['default_branch'],
            $repo_info['avatar_url'] ?? $repository['avatar_url'],
            $repo_info['html_url'] ?? $repository['html_url']
        );
        
        if (!$result) {
            wp_send_json_error([
                'message' => __('Nie udało się zaktualizować danych repozytorium.', 'ai-chat-assistant')
            ]);
        }
        
        wp_send_json_success([
            'message' => __('Dane repozytorium zostały zaktualizowane.', 'ai-chat-assistant'),
            'repository' => $this->get_repository_by_id($repository_id)
        ]);
    }
    
    // Metody pomocnicze
    
    /**
     * Pobiera klienta API dla danego typu repozytorium
     */
    private function get_repository_client($type) {
        switch ($type) {
            case 'github':
                $token = aica_get_option('github_token', '');
                if (empty($token)) {
                    return false;
                }
                return new \AICA\API\GitHubClient($token);
                
            case 'gitlab':
                $token = aica_get_option('gitlab_token', '');
                if (empty($token)) {
                    return false;
                }
                return new \AICA\API\GitLabClient($token);
                
            case 'bitbucket':
                $username = aica_get_option('bitbucket_username', '');
                $password = aica_get_option('bitbucket_app_password', '');
                if (empty($username) || empty($password)) {
                    return false;
                }
                return new \AICA\API\BitbucketClient($username, $password);
                
            default:
                return false;
        }
    }
    
    /**
     * Sprawdza czy użytkownik ma dostęp do danego repozytorium
     */
    private function user_owns_repository($user_id, $repository_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'aica_repositories';
        
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE id = %d AND user_id = %d",
            $repository_id, $user_id
        ));
        
        return $result > 0;
    }
    
    /**
     * Pobiera repozytoria użytkownika
     */
    private function get_user_repositories($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'aica_repositories';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d ORDER BY name ASC",
            $user_id
        ), ARRAY_A);
    }
    
    /**
     * Pobiera dane repozytorium na podstawie ID
     */
    private function get_repository_by_id($repository_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'aica_repositories';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $repository_id
        ), ARRAY_A);
    }
    
    /**
     * Sprawdza czy repozytorium już istnieje
     */
    private function repository_exists($user_id, $type, $full_name) {
        global $wpdb;
        $table = $wpdb->prefix . 'aica_repositories';
        
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE user_id = %d AND type = %s AND full_name = %s",
            $user_id, $type, $full_name
        ));
        
        return $result > 0;
    }
    
    /**
     * Dodaje repozytorium do użytkownika
     */
    private function add_repository_to_user($user_id, $type, $name, $full_name, $description, $default_branch, $avatar_url, $html_url) {
        global $wpdb;
        $table = $wpdb->prefix . 'aica_repositories';
        $now = current_time('mysql');
        
        $result = $wpdb->insert(
            $table,
            [
                'user_id' => $user_id,
                'type' => $type,
                'name' => $name,
                'full_name' => $full_name,
                'description' => $description,
                'default_branch' => $default_branch,
                'avatar_url' => $avatar_url,
                'html_url' => $html_url,
                'created_at' => $now,
                'updated_at' => $now
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );
        
        if (!$result) {
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Aktualizuje dane repozytorium
     */
    private function update_repository($repository_id, $name, $description, $default_branch, $avatar_url, $html_url) {
        global $wpdb;
        $table = $wpdb->prefix . 'aica_repositories';
        $now = current_time('mysql');
        
        $result = $wpdb->update(
            $table,
            [
                'name' => $name,
                'description' => $description,
                'default_branch' => $default_branch,
                'avatar_url' => $avatar_url,
                'html_url' => $html_url,
                'updated_at' => $now
            ],
            ['id' => $repository_id],
            ['%s', '%s', '%s', '%s', '%s', '%s'],
            ['%d']
        );
        
        return $result !== false;
    }
    
    /**
     * Usuwa repozytorium
     */
    private function delete_repository_by_id($repository_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'aica_repositories';
        
        $result = $wpdb->delete(
            $table,
            ['id' => $repository_id],
            ['%d']
        );
        
        return $result !== false;
    }
}