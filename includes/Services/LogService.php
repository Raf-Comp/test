<?php
declare(strict_types=1);

namespace AICA\Services;

use AICA\Helpers\TableHelper;

class LogService {
    private readonly string $table_name;
    private readonly ErrorService $error_service;
    private const BATCH_SIZE = 50;
    private const MAX_LOG_AGE_DAYS = 30;
    private const MAX_MESSAGE_LENGTH = 500;
    private const MAX_CONTEXT_ITEMS = 5;
    private const LOG_BUFFER_SIZE = 10;
    private array $log_buffer = [];
    private static ?self $instance = null;
    private bool $db_initialized = false;

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->table_name = TableHelper::get_table_name('logs');
        $this->error_service = \AICA\Services\ErrorService::getInstance();
        
        // Rejestrujemy akcję do zapisywania bufora przy zamykaniu
        register_shutdown_function([$this, 'flush_log_buffer']);
    }

    /**
     * Inicjalizuje tabelę w bazie danych
     */
    private function init_database(): void {
        if ($this->db_initialized) {
            return;
        }

        try {
            global $wpdb;
            
            // Sprawdzamy czy tabela już istnieje
            $table_exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SHOW TABLES LIKE %s",
                    $this->table_name
                )
            );

            if (!$table_exists) {
                $charset_collate = $wpdb->get_charset_collate();
                
                $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
                    id bigint(20) NOT NULL AUTO_INCREMENT,
                    timestamp datetime NOT NULL,
                    level varchar(20) NOT NULL,
                    message text NOT NULL,
                    context longtext,
                    user_id bigint(20),
                    url varchar(255),
                    ip varchar(45),
                    user_agent text,
                    wp_version varchar(20),
                    php_version varchar(20),
                    plugin_version varchar(20),
                    memory_usage bigint(20),
                    peak_memory_usage bigint(20),
                    PRIMARY KEY  (id),
                    KEY level (level),
                    KEY timestamp (timestamp),
                    KEY user_id (user_id)
                ) $charset_collate;";

                require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
                dbDelta($sql);
            }

            $this->db_initialized = true;
        } catch (\Exception $e) {
            error_log('AICA Log Service: Database initialization error - ' . $e->getMessage());
        }
    }

    /**
     * Loguje błąd
     */
    public function log_error(string $message, array $context = [], string $level = 'error'): bool {
        try {
            // Inicjalizujemy bazę danych tylko jeśli jest to pierwszy wpis
            if (!$this->db_initialized) {
                $this->init_database();
            }

            // Ograniczamy rozmiar wiadomości
            $message = substr($message, 0, self::MAX_MESSAGE_LENGTH);
            
            // Ograniczamy kontekst
            $context = array_slice($context, 0, self::MAX_CONTEXT_ITEMS);
            
            $log_entry = [
                'timestamp' => current_time('mysql'),
                'level' => $level,
                'message' => $message,
                'context' => json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'user_id' => get_current_user_id(),
                'url' => isset($_SERVER['REQUEST_URI']) ? substr($_SERVER['REQUEST_URI'], 0, 255) : '',
                'ip' => isset($_SERVER['REMOTE_ADDR']) ? substr($_SERVER['REMOTE_ADDR'], 0, 45) : '',
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : '',
                'wp_version' => get_bloginfo('version'),
                'php_version' => phpversion(),
                'plugin_version' => AICA_VERSION,
                'memory_usage' => memory_get_usage(true),
                'peak_memory_usage' => memory_get_peak_usage(true)
            ];

            // Dodajemy wpis do bufora
            $this->log_buffer[] = $log_entry;

            // Jeśli bufor jest pełny, zapisujemy go do bazy
            if (count($this->log_buffer) >= self::LOG_BUFFER_SIZE) {
                $this->flush_log_buffer();
            }

            if ($level === 'critical') {
                $this->send_error_notification($log_entry);
            }

            return true;
        } catch (\Exception $e) {
            error_log('AICA Log Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Zapisuje bufor logów do bazy danych
     */
    public function flush_log_buffer(): void {
        if (empty($this->log_buffer)) {
            return;
        }

        try {
            global $wpdb;
            
            // Przygotowujemy wartości do wstawienia
            $values = [];
            $placeholders = [];
            
            foreach ($this->log_buffer as $entry) {
                $values = array_merge($values, array_values($entry));
                $placeholders[] = '(' . implode(',', array_fill(0, count($entry), '%s')) . ')';
            }
            
            // Wykonujemy zbiorcze wstawienie
            $query = "INSERT INTO {$this->table_name} (" . implode(',', array_keys($this->log_buffer[0])) . ") VALUES " . implode(',', $placeholders);
            $wpdb->query($wpdb->prepare($query, $values));
            
            // Czyścimy bufor
            $this->log_buffer = [];
            
            // Okresowo czyścimy stare logi
            if (random_int(1, 100) === 1) {
                $this->cleanup_old_logs();
            }
        } catch (\Exception $e) {
            error_log('AICA Log Buffer Flush Error: ' . $e->getMessage());
        }
    }

    /**
     * Czyści stare logi
     */
    private function cleanup_old_logs(): void {
        if (!$this->db_initialized) {
            return;
        }

        try {
            global $wpdb;
            
            // Usuwamy logi starsze niż MAX_LOG_AGE_DAYS
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$this->table_name} WHERE timestamp < DATE_SUB(NOW(), INTERVAL %d DAY)",
                    self::MAX_LOG_AGE_DAYS
                )
            );
        } catch (\Exception $e) {
            error_log('AICA Log Cleanup Error: ' . $e->getMessage());
        }
    }

    /**
     * Wysyła powiadomienie o błędzie krytycznym
     */
    private function send_error_notification(array $log_entry): void {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        
        $subject = sprintf(
            '[%s] Krytyczny błąd w AI Chat Assistant',
            $site_name
        );

        $message = sprintf(
            "Wystąpił krytyczny błąd w wtyczce AI Chat Assistant:\n\n" .
            "Czas: %s\n" .
            "Poziom: %s\n" .
            "Wiadomość: %s\n" .
            "Kontekst: %s\n" .
            "Użytkownik ID: %d\n" .
            "URL: %s\n" .
            "IP: %s\n" .
            "User Agent: %s\n\n" .
            "Proszę sprawdzić logi wtyczki w panelu administratora.",
            $log_entry['timestamp'],
            $log_entry['level'],
            $log_entry['message'],
            $log_entry['context'],
            $log_entry['user_id'],
            $log_entry['url'],
            $log_entry['ip'],
            $log_entry['user_agent']
        );

        wp_mail($admin_email, $subject, $message);
    }

    /**
     * Pobiera logi z określonego zakresu
     */
    public function get_logs(int $limit = 50, int $offset = 0, ?string $level = null): array {
        if (!$this->db_initialized) {
            return [];
        }

        try {
            global $wpdb;
            
            // Upewniamy się, że limit nie przekracza rozmiaru partii
            $limit = min($limit, self::BATCH_SIZE);
            
            $where = '';
            $params = [];
            
            if ($level !== null) {
                $where = 'WHERE level = %s';
                $params[] = $level;
            }
            
            $query = "SELECT * FROM {$this->table_name} {$where} ORDER BY timestamp DESC LIMIT %d OFFSET %d";
            $params[] = $limit;
            $params[] = $offset;
            
            $results = $wpdb->get_results(
                $wpdb->prepare($query, $params),
                ARRAY_A
            );
            
            return $results ?: [];
        } catch (\Exception $e) {
            error_log('AICA Log Query Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Czyści wszystkie logi
     */
    public function clear_logs(): bool {
        if (!$this->db_initialized) {
            return false;
        }

        try {
            global $wpdb;
            $result = $wpdb->query("TRUNCATE TABLE {$this->table_name}");
            return $result !== false;
        } catch (\Exception $e) {
            error_log('AICA Log Clear Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Pobiera statystyki logów
     */
    public function get_log_stats(): array {
        if (!$this->db_initialized) {
            return [];
        }

        try {
            global $wpdb;
            
            $stats = [
                'total' => 0,
                'by_level' => [],
                'by_day' => [],
                'errors_last_24h' => 0
            ];
            
            // Całkowita liczba logów
            $stats['total'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
            
            // Liczba logów według poziomu
            $level_stats = $wpdb->get_results(
                "SELECT level, COUNT(*) as count FROM {$this->table_name} GROUP BY level",
                ARRAY_A
            );
            
            foreach ($level_stats as $level) {
                $stats['by_level'][$level['level']] = (int) $level['count'];
            }
            
            // Liczba logów według dnia
            $day_stats = $wpdb->get_results(
                "SELECT DATE(timestamp) as day, COUNT(*) as count FROM {$this->table_name} GROUP BY DATE(timestamp) ORDER BY day DESC LIMIT 7",
                ARRAY_A
            );
            
            foreach ($day_stats as $day) {
                $stats['by_day'][$day['day']] = (int) $day['count'];
            }
            
            // Liczba błędów w ostatnich 24 godzinach
            $stats['errors_last_24h'] = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$this->table_name} WHERE level = 'error' AND timestamp > DATE_SUB(NOW(), INTERVAL 1 DAY)"
                )
            );
            
            return $stats;
        } catch (\Exception $e) {
            error_log('AICA Log Stats Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Loguje wiadomość
     */
    public function log(string $message, string $level = 'info', array $context = []): bool {
        return $this->log_error($message, $context, $level);
    }

    /**
     * Pobiera liczbę logów
     */
    public function get_log_count(?string $level = null): int {
        if (!$this->db_initialized) {
            return 0;
        }

        try {
            global $wpdb;
            
            if ($level !== null) {
                return (int) $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) FROM {$this->table_name} WHERE level = %s",
                        $level
                    )
                );
            }
            
            return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
        } catch (\Exception $e) {
            error_log('AICA Log Count Error: ' . $e->getMessage());
            return 0;
        }
    }
} 