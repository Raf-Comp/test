<?php
declare(strict_types=1);

namespace AICA\Helpers;

/**
 * Helper functions for table operations
 *
 * @package AIChatAssistant
 */

if (!defined('ABSPATH')) {
    exit; // Direct access forbidden
}

class TableHelper {
    public static function get_table_name(string $table): string {
        global $wpdb;
        return $wpdb->prefix . 'aica_' . $table;
    }

    public static function table_exists(string $table): bool {
        global $wpdb;
        $table_name = self::get_table_name($table);
        return $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    }

    public static function get_table_columns(string $table): array {
        global $wpdb;
        $table_name = self::get_table_name($table);
        return $wpdb->get_col("DESCRIBE $table_name");
    }

    /**
     * Safely executes a database query with error handling
     *
     * @param string $query SQL query
     * @param array $args Query arguments
     * @return mixed Query result or false on error
     */
    public static function aica_db_query(string $query, array $args = []): mixed {
        global $wpdb;
        
        if (empty($args)) {
            $result = $wpdb->query($query);
        } else {
            $result = $wpdb->query($wpdb->prepare($query, $args));
        }
        
        if ($result === false) {
            aica_log("Database error: " . $wpdb->last_error, 'error');
            return false;
        }
        
        return $result;
    }
} 