<?php
namespace AICA\Admin;

use AICA\Services\RepositoryService;

class RepositoriesPage {
    private $repo_service;
    
    public function __construct() {
        $this->repo_service = new RepositoryService();
        
        // Obsługa akcji
        add_action('admin_init', [$this, 'handle_actions']);
        
        // Rejestracja akcji AJAX
        add_action('wp_ajax_aica_add_repository', [$this, 'ajax_add_repository']);
        add_action('wp_ajax_aica_delete_repository', [$this, 'ajax_delete_repository']);
        add_action('wp_ajax_aica_refresh_repository', [$this, 'ajax_refresh_repository']);
        add_action('wp_ajax_aica_get_repository_details', [$this, 'ajax_get_repository_details']);
        add_action('wp_ajax_aica_get_repository_files', [$this, 'ajax_get_repository_files']);
        add_action('wp_ajax_aica_get_file_content', [$this, 'ajax_get_file_content']);
        
        // Diagnostyczne punkty AJAX
        add_action('wp_ajax_aica_test_db', [$this, 'ajax_test_db']);
        add_action('wp_ajax_aica_activate_plugin', [$this, 'ajax_activate_plugin']);
    }
    
    /**
     * Testowanie tabeli bazy danych
     */
    public function ajax_test_db() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aica_repositories';
        $table_exists = aica_repositories_table_exists();
        
        wp_send_json_success([
            'table_exists' => $table_exists,
            'table_name' => $table_name,
            'current_user_id' => get_current_user_id(),
            'wp_version' => get_bloginfo('version')
        ]);
    }
    
    /**
     * Aktywacja pluginu - tworzenie tabel 
     */
    public function ajax_activate_plugin() {
        $success = aica_create_repositories_table();
        
        wp_send_json_success([
            'message' => 'Aktywacja zakończona',
            'table_created' => $success
        ]);
    }
    
    /**
     * Obsługa akcji na stronie repozytoriów
     */
    public function handle_actions() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'ai-chat-assistant-repositories') {
            return;
        }
        
        // Sprawdź czy tabela repozytoriów istnieje
        if (!aica_repositories_table_exists()) {
            $this->ajax_activate_plugin();
        }
        
        // Dodawanie repozytorium
        if (isset($_POST['aica_add_repository']) && check_admin_referer('aica_repository_nonce')) {
            $type = isset($_POST['repo_type']) ? sanitize_text_field($_POST['repo_type']) : '';
            $name = isset($_POST['repo_name']) ? sanitize_text_field($_POST['repo_name']) : '';
            $owner = isset($_POST['repo_owner']) ? sanitize_text_field($_POST['repo_owner']) : '';
            $url = isset($_POST['repo_url']) ? esc_url_raw($_POST['repo_url']) : '';
            $repo_id = isset($_POST['repo_external_id']) ? sanitize_text_field($_POST['repo_external_id']) : '';
            $description = isset($_POST['repo_description']) ? sanitize_text_field($_POST['repo_description']) : '';
            
            if (!empty($type) && !empty($name) && !empty($owner) && !empty($url)) {
                $result = $this->repo_service->save_repository($type, $name, $owner, $url, $repo_id, $description);
                
                if ($result) {
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-success is-dismissible"><p>' . aica_get_repository_translations()['add_success'] . '</p></div>';
                    });
                    
                    // Przekieruj, aby uniknąć ponownego wysłania formularza po odświeżeniu
                    wp_redirect(admin_url('admin.php?page=ai-chat-assistant-repositories&added=true'));
                    exit;
                } else {
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-error is-dismissible"><p>' . aica_get_repository_translations()['add_error'] . '</p></div>';
                    });
                }
            }
        }
        
        // Usuwanie repozytorium
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['repo_id']) && check_admin_referer('delete_repository')) {
            $repo_id = intval($_GET['repo_id']);
            $result = $this->repo_service->delete_repository($repo_id);
            
            if ($result) {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-success is-dismissible"><p>' . aica_get_repository_translations()['delete_success'] . '</p></div>';
                });
                // Przekieruj po usunięciu, aby odświeżyć listę
                wp_redirect(admin_url('admin.php?page=ai-chat-assistant-repositories&deleted=true'));
                exit;
            } else {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-error is-dismissible"><p>' . aica_get_repository_translations()['delete_error'] . '</p></div>';
                });
            }
        }
    }
    
    /**
     * Obsługa AJAX - dodawanie repozytorium
     */
    public function ajax_add_repository() {
        if (!\aica_verify_ajax_request('aica_repository_nonce')) {
            return;
        }
        
        // Pobierz dane z żądania
        $type = isset($_POST['repo_type']) ? sanitize_text_field($_POST['repo_type']) : '';
        $name = isset($_POST['repo_name']) ? sanitize_text_field($_POST['repo_name']) : '';
        $owner = isset($_POST['repo_owner']) ? sanitize_text_field($_POST['repo_owner']) : '';
        $url = isset($_POST['repo_url']) ? esc_url_raw($_POST['repo_url']) : '';
        $repo_id = isset($_POST['repo_external_id']) ? sanitize_text_field($_POST['repo_external_id']) : '';
        $description = isset($_POST['repo_description']) ? sanitize_text_field($_POST['repo_description']) : '';
        
        // Sprawdź wymagane dane
        if (empty($type)) {
            wp_send_json_error(['message' => \aica_get_repository_translations()['select_type']]);
            return;
        }
        
        if (empty($name)) {
            wp_send_json_error(['message' => \aica_get_repository_translations()['enter_name']]);
            return;
        }
        
        if (empty($owner)) {
            wp_send_json_error(['message' => \aica_get_repository_translations()['enter_owner']]);
            return;
        }
        
        if (empty($url)) {
            wp_send_json_error(['message' => \aica_get_repository_translations()['enter_url']]);
            return;
        }
        
        // Dodaj repozytorium
        $result = $this->repo_service->save_repository($type, $name, $owner, $url, $repo_id, $description);
        
        if ($result) {
            wp_send_json_success([
                'message' => \aica_get_repository_translations()['add_success'],
                'repo_id' => $result
            ]);
        } else {
            wp_send_json_error(['message' => \aica_get_repository_translations()['add_error']]);
        }
    }
    
    /**
     * Obsługa AJAX - usuwanie repozytorium
     */
    public function ajax_delete_repository() {
        if (!aica_verify_ajax_request('aica_repository_nonce')) {
            return;
        }
        
        // Pobierz dane
        $repo_id = isset($_POST['repo_id']) ? intval($_POST['repo_id']) : 0;
        
        if (empty($repo_id)) {
            wp_send_json_error(['message' => aica_get_repository_translations()['invalid_repo_id']]);
            return;
        }
        
        // Usuń repozytorium
        $result = $this->repo_service->delete_repository($repo_id);
        
        if ($result) {
            wp_send_json_success(['message' => aica_get_repository_translations()['delete_success']]);
        } else {
            wp_send_json_error(['message' => aica_get_repository_translations()['delete_error']]);
        }
    }
    
    /**
     * Obsługa AJAX - odświeżanie repozytorium
     */
    public function ajax_refresh_repository() {
        if (!aica_verify_ajax_request('aica_repository_nonce')) {
            return;
        }
        
        // Pobierz dane
        $repo_id = isset($_POST['repo_id']) ? intval($_POST['repo_id']) : 0;
        
        if (empty($repo_id)) {
            wp_send_json_error(['message' => aica_get_repository_translations()['invalid_repo_id']]);
            return;
        }
        
        // Odśwież repozytorium
        $result = $this->repo_service->refresh_repository($repo_id);
        
        if ($result) {
            wp_send_json_success(['message' => aica_get_repository_translations()['refresh_success']]);
        } else {
            wp_send_json_error(['message' => aica_get_repository_translations()['refresh_error']]);
        }
    }
    
    /**
     * Obsługa AJAX - pobieranie szczegółów repozytorium
     */
    public function ajax_get_repository_details() {
        if (!aica_verify_ajax_request('aica_repository_nonce')) {
            return;
        }
        
        // Pobierz dane
        $repo_id = isset($_POST['repo_id']) ? intval($_POST['repo_id']) : 0;
        
        if (empty($repo_id)) {
            wp_send_json_error(['message' => aica_get_repository_translations()['invalid_repo_id']]);
            return;
        }
        
        // Pobierz szczegóły repozytorium
        $repository = $this->repo_service->get_repository($repo_id);
        
        if ($repository) {
            wp_send_json_success(['repository' => $repository]);
        } else {
            wp_send_json_error(['message' => aica_get_repository_translations()['repository_not_found']]);
        }
    }
    
    /**
     * Obsługa AJAX - pobieranie plików repozytorium
     */
    public function ajax_get_repository_files() {
        if (!aica_verify_ajax_request('aica_repository_nonce')) {
            return;
        }
        
        // Pobierz dane
        $repo_id = isset($_POST['repo_id']) ? intval($_POST['repo_id']) : 0;
        
        if (empty($repo_id)) {
            wp_send_json_error(['message' => aica_get_repository_translations()['invalid_repo_id']]);
            return;
        }
        
        // Pobierz pliki repozytorium
        $files = $this->repo_service->get_repository_files($repo_id);
        
        if ($files !== false) {
            wp_send_json_success(['files' => $files]);
        } else {
            wp_send_json_error(['message' => aica_get_repository_translations()['repository_not_found']]);
        }
    }
    
    /**
     * Obsługa AJAX - pobieranie zawartości pliku
     */
    public function ajax_get_file_content() {
        if (!aica_verify_ajax_request('aica_repository_nonce')) {
            return;
        }
        
        // Pobierz dane
        $repo_id = isset($_POST['repo_id']) ? intval($_POST['repo_id']) : 0;
        $file_path = isset($_POST['file_path']) ? sanitize_text_field($_POST['file_path']) : '';
        
        if (empty($repo_id) || empty($file_path)) {
            wp_send_json_error(['message' => aica_get_repository_translations()['file_not_found']]);
            return;
        }
        
        // Pobierz zawartość pliku
        $content = $this->repo_service->get_file_content($repo_id, $file_path);
        
        if ($content !== false) {
            wp_send_json_success(['content' => $content]);
        } else {
            wp_send_json_error(['message' => aica_get_repository_translations()['file_content_error']]);
        }
    }
    
    /**
     * Renderowanie strony repozytoriów
     */
    public function render() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Nie masz wystarczających uprawnień, aby uzyskać dostęp do tej strony.', 'ai-chat-assistant'));
        }
        
        // Dodanie skryptów i stylów
        wp_enqueue_style('aica-repositories-css', AICA_PLUGIN_URL . 'assets/css/repositories.css', array(), AICA_VERSION);
        wp_enqueue_script('aica-repositories-js', AICA_PLUGIN_URL . 'assets/js/repositories.js', array('jquery'), AICA_VERSION, true);
        
        // Przekazanie danych do skryptu
        wp_localize_script('aica-repositories-js', 'aica_repositories', array(
            'nonce' => wp_create_nonce('aica_repository_nonce'),
            'i18n' => \aica_get_repository_translations()
        ));
        
        include_once AICA_PLUGIN_DIR . 'templates/admin/repositories.php';
    }
}