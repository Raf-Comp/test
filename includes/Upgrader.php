<?php
namespace AIChatAssistant;

class Upgrader {
    /**
     * Aktualizuje wtyczkę
     */
    public static function upgrade() {
        global $wpdb;
        
        // Pobierz aktualną wersję
        $current_version = get_option('aica_version', '1.0.0');
        
        // Aktualizuj do wersji 1.1.0
        if (version_compare($current_version, '1.1.0', '<')) {
            self::upgrade_to_1_1_0();
        }
        
        // Aktualizuj do wersji 1.2.0
        if (version_compare($current_version, '1.2.0', '<')) {
            self::upgrade_to_1_2_0();
        }
        
        // Aktualizuj wersję
        update_option('aica_version', AICA_VERSION);
    }
    
    /**
     * Aktualizuje do wersji 1.1.0
     */
    private static function upgrade_to_1_1_0() {
        global $wpdb;
        $sessions_table = $wpdb->prefix . 'aica_sessions';
        $messages_table = $wpdb->prefix . 'aica_messages';
        
        // Dodaj kolumny do tabeli sesji
        $wpdb->query("ALTER TABLE {$sessions_table} 
            ADD COLUMN user_id bigint(20) NOT NULL AFTER id,
            ADD COLUMN title varchar(255) NOT NULL AFTER user_id,
            ADD COLUMN created_at datetime NOT NULL AFTER title,
            ADD COLUMN updated_at datetime NOT NULL AFTER created_at");
        
        // Dodaj kolumny do tabeli wiadomości
        $wpdb->query("ALTER TABLE {$messages_table} 
            ADD COLUMN role varchar(20) NOT NULL AFTER session_id,
            ADD COLUMN content text NOT NULL AFTER role,
            ADD COLUMN created_at datetime NOT NULL AFTER content");
        
        // Migruj dane
        $compatibility = new CompatibilityService();
        $compatibility->migrate_history();
    }
    
    /**
     * Aktualizuje do wersji 1.2.0
     */
    private static function upgrade_to_1_2_0() {
        global $wpdb;
        $sessions_table = $wpdb->prefix . 'aica_sessions';
        $messages_table = $wpdb->prefix . 'aica_messages';
        
        // Dodaj kolumny do tabeli wiadomości
        $wpdb->query("ALTER TABLE {$messages_table} 
            ADD COLUMN tokens_used int(11) DEFAULT 0 AFTER content,
            ADD COLUMN model varchar(50) DEFAULT '' AFTER tokens_used");
        
        // Ustaw domyślny model
        $wpdb->query("UPDATE {$messages_table} SET model = 'claude-3-opus-20240229' WHERE model = ''");
    }
} 