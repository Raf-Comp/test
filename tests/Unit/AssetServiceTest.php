<?php
namespace AICA\Tests\Unit;

use AICA\Services\AssetService;
use WP_UnitTestCase;

/**
 * Testy jednostkowe dla klasy AssetService
 * 
 * @package AICA
 * @since 1.0.0
 */
class AssetServiceTest extends WP_UnitTestCase {
    private $asset_service;

    /**
     * Ustawienia przed każdym testem
     */
    public function setUp(): void {
        parent::setUp();
        $this->asset_service = new AssetService();
    }

    /**
     * Test ładowania zasobów dla strony czatu
     */
    public function test_load_chat_assets() {
        // Symulacja ładowania zasobów
        $this->asset_service->loadPageAssets('ai-chat-assistant');

        // Sprawdź czy style zostały zarejestrowane
        $this->assertTrue(wp_style_is('aica-chat', 'registered'));
        $this->assertTrue(wp_style_is('aica-modern-chat', 'registered'));
        $this->assertTrue(wp_style_is('dashicons', 'registered'));

        // Sprawdź czy skrypty zostały zarejestrowane
        $this->assertTrue(wp_script_is('aica-chat', 'registered'));
        $this->assertTrue(wp_script_is('aica-modern-chat', 'registered'));
    }

    /**
     * Test ładowania zasobów dla strony ustawień
     */
    public function test_load_settings_assets() {
        // Symulacja ładowania zasobów
        $this->asset_service->loadPageAssets('ai-chat-assistant-settings');

        // Sprawdź czy style zostały zarejestrowane
        $this->assertTrue(wp_style_is('aica-settings', 'registered'));

        // Sprawdź czy skrypty zostały zarejestrowane
        $this->assertTrue(wp_script_is('aica-settings', 'registered'));
    }

    /**
     * Test dodawania atrybutu lazy loading
     */
    public function test_add_lazy_loading() {
        $attr = ['src' => 'test.jpg'];
        $result = $this->asset_service->add_lazy_loading($attr);

        $this->assertArrayHasKey('loading', $result);
        $this->assertEquals('lazy', $result['loading']);
    }

    /**
     * Test dodawania stylów dla prefers-reduced-motion
     */
    public function test_add_reduced_motion_styles() {
        // Symulacja preferencji użytkownika
        $_COOKIE['aica_reduced_motion'] = 'true';

        // Wywołaj metodę
        ob_start();
        $this->asset_service->add_reduced_motion_styles();
        $output = ob_get_clean();

        // Sprawdź czy style zostały dodane
        $this->assertStringContainsString('@media (prefers-reduced-motion: reduce)', $output);
        $this->assertStringContainsString('transition: none !important', $output);
    }

    /**
     * Test generowania URL do zasobu
     */
    public function test_get_asset_url() {
        $url = $this->asset_service->getAssetUrl('css/test.css');
        $this->assertStringContainsString('assets/css/test.css', $url);
    }

    /**
     * Test pobierania wersji pliku
     */
    public function test_get_file_version() {
        // Test dla istniejącego pliku
        $version = $this->asset_service->getFileVersion('css/chat.css');
        $this->assertIsInt($version);

        // Test dla nieistniejącego pliku
        $version = $this->asset_service->getFileVersion('non_existent.css');
        $this->assertEquals('1.0.0', $version);
    }

    /**
     * Test lokalizacji skryptu
     */
    public function test_localize_script() {
        // Zarejestruj skrypt
        wp_register_script('test-script', 'test.js');

        // Lokalizuj skrypt
        $this->asset_service->localizeScript('test-script', 'testObject', [
            'test' => 'value'
        ]);

        // Sprawdź czy dane zostały zlokalizowane
        global $wp_scripts;
        $this->assertArrayHasKey('testObject', $wp_scripts->registered['test-script']->extra['data']);
    }

    /**
     * Test pobierania tłumaczeń
     */
    public function test_get_chat_translations() {
        $translations = $this->asset_service->getChatTranslations();

        // Sprawdź czy wszystkie klucze są obecne
        $this->assertArrayHasKey('new_chat', $translations);
        $this->assertArrayHasKey('send', $translations);
        $this->assertArrayHasKey('type_message', $translations);
    }

    /**
     * Test sprawdzania preferencji redukcji ruchu
     */
    public function test_should_reduce_motion() {
        // Test gdy użytkownik preferuje redukcję ruchu
        $_COOKIE['aica_reduced_motion'] = 'true';
        $this->assertTrue($this->asset_service->should_reduce_motion());

        // Test gdy użytkownik nie preferuje redukcji ruchu
        $_COOKIE['aica_reduced_motion'] = 'false';
        $this->assertFalse($this->asset_service->should_reduce_motion());
    }
} 