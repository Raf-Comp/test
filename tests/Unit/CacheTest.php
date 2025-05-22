<?php
namespace AICA\Tests\Unit;

use AICA\Helpers\Cache;
use WP_UnitTestCase;

/**
 * Testy jednostkowe dla klasy Cache
 * 
 * @package AICA
 * @since 1.0.0
 */
class CacheTest extends WP_UnitTestCase {
    private $cache;

    /**
     * Ustawienia przed każdym testem
     */
    public function setUp(): void {
        parent::setUp();
        $this->cache = Cache::getInstance();
        wp_cache_flush();
    }

    /**
     * Test pobierania instancji
     */
    public function test_get_instance() {
        $instance1 = Cache::getInstance();
        $instance2 = Cache::getInstance();
        $this->assertSame($instance1, $instance2);
    }

    /**
     * Test zapisywania i pobierania danych
     */
    public function test_set_and_get() {
        $key = 'test_key';
        $data = ['test' => 'data'];

        // Test zapisywania
        $this->assertTrue($this->cache->set($key, $data));

        // Test pobierania
        $cached_data = $this->cache->get($key);
        $this->assertEquals($data, $cached_data);

        // Test pobierania nieistniejących danych
        $this->assertFalse($this->cache->get('non_existent_key'));
    }

    /**
     * Test usuwania danych
     */
    public function test_delete() {
        $key = 'test_key';
        $data = ['test' => 'data'];

        // Zapisz dane
        $this->cache->set($key, $data);

        // Usuń dane
        $this->assertTrue($this->cache->delete($key));

        // Sprawdź czy dane zostały usunięte
        $this->assertFalse($this->cache->get($key));
    }

    /**
     * Test czyszczenia cache
     */
    public function test_flush() {
        // Zapisz kilka kluczy
        $this->cache->set('key1', 'value1');
        $this->cache->set('key2', 'value2');

        // Wyczyść cache
        $this->assertTrue($this->cache->flush());

        // Sprawdź czy wszystkie dane zostały usunięte
        $this->assertFalse($this->cache->get('key1'));
        $this->assertFalse($this->cache->get('key2'));
    }

    /**
     * Test metody remember
     */
    public function test_remember() {
        $key = 'test_key';
        $callback_called = false;

        // Test generowania danych
        $data = $this->cache->remember($key, function() use (&$callback_called) {
            $callback_called = true;
            return ['test' => 'data'];
        });

        $this->assertTrue($callback_called);
        $this->assertEquals(['test' => 'data'], $data);

        // Reset flagi
        $callback_called = false;

        // Test pobierania z cache
        $data = $this->cache->remember($key, function() use (&$callback_called) {
            $callback_called = true;
            return ['test' => 'new_data'];
        });

        $this->assertFalse($callback_called);
        $this->assertEquals(['test' => 'data'], $data);
    }

    /**
     * Test generowania klucza cache
     */
    public function test_generate_key() {
        $prefix = 'test_prefix';
        $params = ['param1' => 'value1', 'param2' => 'value2'];

        $key = $this->cache->generateKey($prefix, $params);
        
        // Sprawdź czy klucz zaczyna się od prefixu
        $this->assertStringStartsWith($prefix, $key);
        
        // Sprawdź czy klucz jest unikalny dla różnych parametrów
        $key2 = $this->cache->generateKey($prefix, ['param1' => 'value2']);
        $this->assertNotEquals($key, $key2);
    }

    /**
     * Test wygaśnięcia cache
     */
    public function test_cache_expiration() {
        $key = 'test_key';
        $data = ['test' => 'data'];

        // Zapisz dane z krótkim czasem wygaśnięcia
        $this->cache->set($key, $data, 1);

        // Poczekaj aż cache wygaśnie
        sleep(2);

        // Sprawdź czy dane wygasły
        $this->assertFalse($this->cache->get($key));
    }
} 