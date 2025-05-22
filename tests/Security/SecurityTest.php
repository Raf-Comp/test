<?php
namespace AICA\Tests\Security;

use WP_UnitTestCase;
use AICA\Helpers\Security;

/**
 * Testy bezpieczeństwa dla wtyczki
 * 
 * @package AICA
 * @since 1.0.0
 */
class SecurityTest extends WP_UnitTestCase {
    /**
     * Test zabezpieczenia przed XSS w wiadomościach
     */
    public function test_xss_protection_in_messages() {
        // Symulacja żądania AJAX z potencjalnie niebezpiecznym kodem
        $_POST['action'] = 'aica_send_message';
        $_POST['nonce'] = wp_create_nonce('aica_chat');
        $_POST['message'] = '<script>alert("xss")</script>';

        // Wywołaj akcję
        ob_start();
        do_action('wp_ajax_aica_send_message');
        $response = json_decode(ob_get_clean(), true);

        // Sprawdź czy kod został wyczyszczony
        $this->assertStringNotContainsString('<script>', $response['data']['message']);
    }

    /**
     * Test zabezpieczenia przed CSRF
     */
    public function test_csrf_protection() {
        // Symulacja żądania AJAX bez nonce
        $_POST['action'] = 'aica_send_message';
        $_POST['message'] = 'Test message';

        // Wywołaj akcję
        ob_start();
        do_action('wp_ajax_aica_send_message');
        $response = json_decode(ob_get_clean(), true);

        // Sprawdź czy żądanie zostało odrzucone
        $this->assertFalse($response['success']);
        $this->assertEquals('Invalid nonce', $response['data']['message']);
    }

    /**
     * Test zabezpieczenia przed SQL Injection
     */
    public function test_sql_injection_protection() {
        // Symulacja żądania AJAX z potencjalnie niebezpiecznym kodem SQL
        $_POST['action'] = 'aica_search_conversations';
        $_POST['nonce'] = wp_create_nonce('aica_chat');
        $_POST['search'] = "' OR '1'='1";

        // Wywołaj akcję
        ob_start();
        do_action('wp_ajax_aica_search_conversations');
        $response = json_decode(ob_get_clean(), true);

        // Sprawdź czy kod został wyczyszczony
        $this->assertStringNotContainsString("' OR '1'='1", $response['data']['search']);
    }

    /**
     * Test zabezpieczenia przed nieautoryzowanym dostępem
     */
    public function test_unauthorized_access_protection() {
        // Symulacja żądania AJAX bez zalogowanego użytkownika
        wp_set_current_user(0);

        $_POST['action'] = 'aica_send_message';
        $_POST['nonce'] = wp_create_nonce('aica_chat');
        $_POST['message'] = 'Test message';

        // Wywołaj akcję
        ob_start();
        do_action('wp_ajax_aica_send_message');
        $response = json_decode(ob_get_clean(), true);

        // Sprawdź czy żądanie zostało odrzucone
        $this->assertFalse($response['success']);
        $this->assertEquals('Unauthorized access', $response['data']['message']);
    }

    /**
     * Test zabezpieczenia przed niebezpiecznymi typami plików
     */
    public function test_dangerous_file_types_protection() {
        // Symulacja żądania AJAX z niebezpiecznym typem pliku
        $_POST['action'] = 'aica_upload_attachment';
        $_POST['nonce'] = wp_create_nonce('aica_chat');

        $_FILES['attachment'] = [
            'name' => 'test.php',
            'type' => 'application/x-httpd-php',
            'tmp_name' => tempnam(sys_get_temp_dir(), 'test'),
            'error' => 0,
            'size' => 1024
        ];

        // Wywołaj akcję
        ob_start();
        do_action('wp_ajax_aica_upload_attachment');
        $response = json_decode(ob_get_clean(), true);

        // Sprawdź czy żądanie zostało odrzucone
        $this->assertFalse($response['success']);
        $this->assertEquals('Invalid file type', $response['data']['message']);

        // Wyczyść plik tymczasowy
        unlink($_FILES['attachment']['tmp_name']);
    }

    /**
     * Test zabezpieczenia przed atakiem na wielkość pliku
     */
    public function test_file_size_limit_protection() {
        // Symulacja żądania AJAX z za dużym plikiem
        $_POST['action'] = 'aica_upload_attachment';
        $_POST['nonce'] = wp_create_nonce('aica_chat');

        $_FILES['attachment'] = [
            'name' => 'test.txt',
            'type' => 'text/plain',
            'tmp_name' => tempnam(sys_get_temp_dir(), 'test'),
            'error' => 0,
            'size' => 10 * 1024 * 1024 // 10MB
        ];

        // Wywołaj akcję
        ob_start();
        do_action('wp_ajax_aica_upload_attachment');
        $response = json_decode(ob_get_clean(), true);

        // Sprawdź czy żądanie zostało odrzucone
        $this->assertFalse($response['success']);
        $this->assertEquals('File too large', $response['data']['message']);

        // Wyczyść plik tymczasowy
        unlink($_FILES['attachment']['tmp_name']);
    }

    /**
     * Test zabezpieczenia przed atakiem na długość wiadomości
     */
    public function test_message_length_limit_protection() {
        // Symulacja żądania AJAX z za długą wiadomością
        $_POST['action'] = 'aica_send_message';
        $_POST['nonce'] = wp_create_nonce('aica_chat');
        $_POST['message'] = str_repeat('a', 10001); // 10001 znaków

        // Wywołaj akcję
        ob_start();
        do_action('wp_ajax_aica_send_message');
        $response = json_decode(ob_get_clean(), true);

        // Sprawdź czy żądanie zostało odrzucone
        $this->assertFalse($response['success']);
        $this->assertEquals('Message too long', $response['data']['message']);
    }

    /**
     * Test zabezpieczenia przed atakiem na częstotliwość żądań
     */
    public function test_rate_limiting_protection() {
        // Symulacja wielu żądań w krótkim czasie
        for ($i = 0; $i < 11; $i++) {
            $_POST['action'] = 'aica_send_message';
            $_POST['nonce'] = wp_create_nonce('aica_chat');
            $_POST['message'] = 'Test message ' . $i;

            ob_start();
            do_action('wp_ajax_aica_send_message');
            $response = json_decode(ob_get_clean(), true);

            if ($i < 10) {
                $this->assertTrue($response['success']);
            } else {
                // 11-te żądanie powinno zostać odrzucone
                $this->assertFalse($response['success']);
                $this->assertEquals('Rate limit exceeded', $response['data']['message']);
            }
        }
    }

    /**
     * Test zabezpieczenia przed atakiem na format danych
     */
    public function test_data_format_protection() {
        // Symulacja żądania AJAX z nieprawidłowym formatem danych
        $_POST['action'] = 'aica_send_message';
        $_POST['nonce'] = wp_create_nonce('aica_chat');
        $_POST['message'] = ['not_a_string'];

        // Wywołaj akcję
        ob_start();
        do_action('wp_ajax_aica_send_message');
        $response = json_decode(ob_get_clean(), true);

        // Sprawdź czy żądanie zostało odrzucone
        $this->assertFalse($response['success']);
        $this->assertEquals('Invalid data format', $response['data']['message']);
    }
} 