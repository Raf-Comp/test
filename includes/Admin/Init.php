<?php
namespace AICA\Admin;

class Init {
    public function __construct() {
        // Dodawanie nowego użytkownika wtyczki po utworzeniu użytkownika WordPress
        add_action('user_register', [$this, 'add_new_user']);
        
        // Aktualizacja ostatniego logowania po zalogowaniu użytkownika
        add_action('wp_login', [$this, 'update_user_login_time'], 10, 2);
        
        // Usuwanie użytkownika wtyczki po usunięciu użytkownika WordPress
        add_action('delete_user', [$this, 'delete_user']);
        
        // Ładowanie skryptów i stylów administracyjnych
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }
    
    /**
     * Dodaje nowego użytkownika do tabeli użytkowników wtyczki
     */
    public function add_new_user($user_id) {
        $user_data = get_userdata($user_id);
        if ($user_data) {
            // Pobierz najwyższą rolę użytkownika
            $role = aica_get_highest_role($user_data->roles);
            
            // Dodaj użytkownika do tabeli wtyczki
            aica_add_user(
                $user_id,
                $user_data->user_login,
                $user_data->user_email,
                $role,
                current_time('mysql')
            );
            
            aica_log('Dodano nowego użytkownika: ' . $user_data->user_login . ' (ID: ' . $user_id . ')');
        }
    }
    
    /**
     * Aktualizuje czas ostatniego logowania użytkownika
     */
    public function update_user_login_time($user_login, $user) {
        // Pobierz ID użytkownika wtyczki
        $aica_user_id = aica_get_user_id($user->ID);
        
        if ($aica_user_id) {
            // Aktualizuj czas ostatniego logowania
            aica_update_user_last_login($aica_user_id);
            aica_log('Zaktualizowano czas logowania użytkownika: ' . $user_login);
        } else {
            // Jeśli użytkownik nie istnieje w tabeli wtyczki, dodaj go
            $this->add_new_user($user->ID);
        }
    }
    
    /**
     * Usuwa użytkownika z tabeli wtyczki po usunięciu z WordPressa
     */
    public function delete_user($user_id) {
        // Pobierz ID użytkownika wtyczki
        $aica_user_id = aica_get_user_id($user_id);
        
        if ($aica_user_id) {
            // Usuń użytkownika z tabeli wtyczki
            aica_delete_user($aica_user_id);
            aica_log('Usunięto użytkownika o ID WordPress: ' . $user_id);
        }
    }
    
    /**
     * Ładuje skrypty i style administracyjne
     */
    public function enqueue_admin_assets($hook) {
        // Ładuj assety tylko na stronach naszej wtyczki
        if (strpos($hook, 'ai-chat-assistant') === false) {
            return;
        }

        // Style CSS
        wp_enqueue_style(
            'aica-admin',
            AICA_PLUGIN_URL . 'assets/css/admin.css',
            [],
            AICA_VERSION
        );

        // Skrypty JavaScript
        wp_enqueue_script(
            'aica-admin',
            AICA_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            AICA_VERSION,
            true
        );

        // Przekaż dane do JS
        wp_localize_script('aica-admin', 'aica_data', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aica_nonce'),
            'settings_nonce' => wp_create_nonce('aica_settings_nonce'),
            'i18n' => [
                'error' => __('Błąd', 'ai-chat-assistant'),
                'loading' => __('Ładowanie...', 'ai-chat-assistant'),
                'sending' => __('Wysyłanie...', 'ai-chat-assistant'),
                'saving' => __('Zapisywanie...', 'ai-chat-assistant'),
                'saved' => __('Zapisano', 'ai-chat-assistant'),
                'save_error' => __('Błąd zapisywania', 'ai-chat-assistant')
            ]
        ]);
    }
}