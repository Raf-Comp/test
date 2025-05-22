<?php
declare(strict_types=1);

/**
 * Service for managing plugin caching
 */

namespace AICA\Services;

use AICA\Helpers\TableHelper;

class CacheService {
    private readonly string $table_name;
    private readonly int $default_expiration;
    private readonly ErrorService $error_service;
    private static ?self $instance = null;

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self(ErrorService::getInstance());
        }
        return self::$instance;
    }

    public function __construct(ErrorService $error_service) {
        $this->table_name = TableHelper::get_table_name('cache');
        $this->default_expiration = 3600; // 1 hour
        $this->error_service = $error_service;
    }

    /**
     * Get cached data
     */
    public function get(string $key): mixed {
        global $wpdb;
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT value, expires_at FROM {$this->table_name} WHERE cache_key = %s",
            $key
        ));

        if (!$result) {
            return null;
        }

        if (strtotime($result->expires_at) < time()) {
            $this->delete($key);
            return null;
        }

        return maybe_unserialize($result->value);
    }

    /**
     * Set cache data
     */
    public function set(string $key, mixed $value, ?int $expiration = null): bool {
        global $wpdb;
        
        $expiration = $expiration ?? $this->default_expiration;
        $expires_at = date('Y-m-d H:i:s', time() + $expiration);

        $result = $wpdb->replace(
            $this->table_name,
            [
                'cache_key' => $key,
                'value' => maybe_serialize($value),
                'expires_at' => $expires_at
            ],
            ['%s', '%s', '%s']
        );

        return $result !== false;
    }

    /**
     * Delete cached data
     */
    public function delete(string $key): bool {
        global $wpdb;
        
        $result = $wpdb->delete(
            $this->table_name,
            ['cache_key' => $key],
            ['%s']
        );

        return $result !== false;
    }

    /**
     * Clear all plugin cache
     */
    public function clear(): bool {
        global $wpdb;
        
        $result = $wpdb->query("TRUNCATE TABLE {$this->table_name}");
        return $result !== false;
    }

    /**
     * Cleanup expired cache entries
     */
    public function cleanup(): bool {
        global $wpdb;
        
        $result = $wpdb->query(
            "DELETE FROM {$this->table_name} WHERE expires_at < NOW()"
        );

        return $result !== false;
    }

    /**
     * Get or set cache with callback
     */
    public function remember(string $key, int $ttl, callable $callback): mixed {
        $cached = $this->get($key);
        
        if ($cached !== null) {
            return $cached;
        }
        
        $data = $callback();
        $this->set($key, $data, $ttl);
        
        return $data;
    }

    /**
     * Get cache stats
     */
    public function getStats(): ?array {
        try {
            global $wp_object_cache;
            $stats = [
                'hits' => 0,
                'misses' => 0,
                'size' => 0
            ];
            
            if (isset($wp_object_cache->cache)) {
                foreach ($wp_object_cache->cache as $key => $value) {
                    if (str_starts_with($key, $this->table_name)) {
                        $stats['size'] += strlen(serialize($value));
                        if (isset($wp_object_cache->cache_hits[$key])) {
                            $stats['hits']++;
                        } else {
                            $stats['misses']++;
                        }
                    }
                }
            }
            
            return $stats;
        } catch (\Exception $e) {
            $this->error_service->logError('Cache stats error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Pobiera historię czatu z cache
     */
    public function get_history(string $session_id): mixed {
        $cache_key = 'aica_history_' . $session_id;
        return get_transient($cache_key);
    }

    /**
     * Zapisuje historię czatu w cache
     */
    public function set_history(string $session_id, mixed $history, int $expiration = 3600): bool {
        $cache_key = 'aica_history_' . $session_id;
        return set_transient($cache_key, $history, $expiration);
    }

    /**
     * Usuwa historię czatu z cache
     */
    public function delete_history(string $session_id): bool {
        $cache_key = 'aica_history_' . $session_id;
        return delete_transient($cache_key);
    }

    /**
     * Czyści cache historii
     */
    public function clear_history_cache(): bool {
        global $wpdb;
        
        $result1 = $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_aica_history_%'");
        $result2 = $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_aica_history_%'");
        
        return $result1 !== false && $result2 !== false;
    }
} 