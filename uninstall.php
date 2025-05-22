<?php
/**
 * Plik wywoływany podczas odinstalowywania wtyczki
 *
 * @package AIChatAssistant
 */

// Jeśli plik nie jest wywołany przez WordPress, przerwij
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Załaduj funkcje pomocnicze
require_once plugin_dir_path(__FILE__) . 'includes/Helpers.php';

/**
 * Usuwanie tabel utworzonych przez wtyczkę
 */
function aica_remove_tables() {
    global $wpdb;
    
    // Lista tabel do usunięcia
    $tables = [
        $wpdb->prefix . 'aica_sessions',
        $wpdb->prefix . 'aica_messages',
        $wpdb->prefix . 'aica_repositories',
        $wpdb->prefix . 'aica_users',
        $wpdb->prefix . 'aica_options'
    ];
    
    // Usunięcie tabel
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS $table");
    }
}

/**
 * Usuwanie katalogu plików
 */
function aica_remove_files() {
    $upload_dir = wp_upload_dir();
    $aica_dir = $upload_dir['basedir'] . '/aica-uploads';
    
    // Rekurencyjne usuwanie katalogu
    if (is_dir($aica_dir)) {
        aica_recursive_rmdir($aica_dir);
    }
    
    // Usuwanie plików logów
    $log_dir = WP_CONTENT_DIR . '/aica-logs';
    if (is_dir($log_dir)) {
        aica_recursive_rmdir($log_dir);
    }
}

/**
 * Rekurencyjne usuwanie katalogu i wszystkich plików
 */
function aica_recursive_rmdir($dir) {
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (is_dir($dir . "/" . $object)) {
                    aica_recursive_rmdir($dir . "/" . $object);
                } else {
                    unlink($dir . "/" . $object);
                }
            }
        }
        rmdir($dir);
    }
}

// Usunięcie danych wtyczki
aica_remove_tables();
aica_remove_files();