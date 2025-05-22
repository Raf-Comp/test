<?php
declare(strict_types=1);

namespace AICA\Services;

use AICA\Helpers\TableHelper;

class ErrorService {
    private readonly string $table_name;

    public function __construct() {
        $this->table_name = TableHelper::get_table_name('errors');
    }

    public function log_error(string $message, string $type = 'error'): bool {
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->table_name,
            [
                'message' => $message,
                'type' => $type,
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s']
        );

        return $result !== false;
    }

    public function get_errors(int $limit = 100): array {
        global $wpdb;
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} ORDER BY created_at DESC LIMIT %d",
                $limit
            ),
            ARRAY_A
        );

        return $results ?: [];
    }

    public function clear_errors(): bool {
        global $wpdb;
        
        $result = $wpdb->query("TRUNCATE TABLE {$this->table_name}");
        return $result !== false;
    }

    public function get_error_count(): int {
        global $wpdb;
        
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
    }
} 