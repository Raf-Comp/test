<?php
declare(strict_types=1);

namespace AICA\Helpers;

/**
 * Klasa pomocnicza do zarządzania cache wtyczki
 * 
 * Zapewnia metody do:
 * - Pobierania danych z cache
 * - Zapisówania danych w cache
 * - Usuwania danych z cache
 * - Generowania unikalnych kluczy cache
 * 
 * @package AIChatAssistant
 * @since 1.0.0
 */
class Cache {
    private static ?self $instance = null;
    private string $cache_group = 'aica_cache';
    private int $cache_time;
    private string $cache_dir;

    /**
     * Konstruktor
     *
     * @param string $cache_dir Katalog cache
     * @param int $cache_time Czas ważności cache w sekundach
     */
    public function __construct(string $cache_dir, int $cache_time = 3600) {
        $this->cache_dir = $cache_dir;
        $this->cache_time = $cache_time;

        if (!file_exists($this->cache_dir)) {
            wp_mkdir_p($this->cache_dir);
        }
    }

    /**
     * Pobiera instancję klasy Cache
     * 
     * @return self Instancja klasy Cache
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Pobiera dane z cache
     * 
     * @param string $key Klucz cache
     * @return mixed Dane z cache lub null jeśli nie znaleziono
     */
    public function get(string $key): mixed {
        $file = $this->get_cache_file($key);

        if (!file_exists($file)) {
            return null;
        }

        $data = file_get_contents($file);
        if ($data === false) {
            return null;
        }

        $cache = unserialize($data);
        if ($cache === false) {
            return null;
        }

        if (time() > $cache['expires']) {
            $this->delete($key);
            return null;
        }

        return $cache['data'];
    }

    /**
     * Zapisuje dane w cache
     * 
     * @param string $key Klucz cache
     * @param mixed $data Dane do zapisania
     * @param int|null $time Czas ważności w sekundach
     * @return bool Czy operacja się powiodła
     */
    public function set(string $key, mixed $data, ?int $time = null): bool {
        $file = $this->get_cache_file($key);
        $time = $time ?? $this->cache_time;

        $cache = [
            'data' => $data,
            'expires' => time() + $time
        ];

        return file_put_contents($file, serialize($cache)) !== false;
    }

    /**
     * Usuwa dane z cache
     * 
     * @param string $key Klucz cache
     * @return bool Czy operacja się powiodła
     */
    public function delete(string $key): bool {
        $file = $this->get_cache_file($key);

        if (file_exists($file)) {
            return unlink($file);
        }

        return true;
    }

    /**
     * Czyści cały cache
     * 
     * @return bool Czy operacja się powiodła
     */
    public function clear(): bool {
        $files = glob($this->cache_dir . '/*');

        if ($files === false) {
            return false;
        }

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        return true;
    }

    /**
     * Sprawdza czy cache istnieje
     *
     * @param string $key Klucz cache
     * @return bool Czy cache istnieje
     */
    public function exists(string $key): bool {
        $file = $this->get_cache_file($key);

        if (!file_exists($file)) {
            return false;
        }

        $data = file_get_contents($file);
        if ($data === false) {
            return false;
        }

        $cache = unserialize($data);
        if ($cache === false) {
            return false;
        }

        return time() <= $cache['expires'];
    }

    /**
     * Generuje ścieżkę do pliku cache
     *
     * @param string $key Klucz cache
     * @return string Ścieżka do pliku cache
     */
    private function get_cache_file(string $key): string {
        return $this->cache_dir . '/' . md5($key) . '.cache';
    }

    /**
     * Pobiera dane z cache lub wykonuje callback i zapisuje wynik
     *
     * @param string $key Klucz cache
     * @param callable $callback Funkcja do wykonania
     * @param int|null $expiration Czas ważności w sekundach
     * @return mixed Dane z cache lub wynik callback
     */
    public function remember(string $key, callable $callback, ?int $expiration = null): mixed {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->set($key, $value, $expiration);

        return $value;
    }

    /**
     * Generuje unikalny klucz cache
     *
     * @param string $prefix Prefiks klucza
     * @param array $params Parametry do hashowania
     * @return string Unikalny klucz cache
     */
    public function generateKey(string $prefix, array $params = []): string {
        return $prefix . '_' . md5(serialize($params));
    }
} 