<?php
namespace AICA\Tests\Integration;

use WP_UnitTestCase;
use AICA\Main;

/**
 * Testy integracyjne dla funkcjonalności czatu
 * 
 * @package AICA
 * @since 1.0.0
 */
class ChatIntegrationTest extends WP_UnitTestCase {
    private $plugin;

    /**
     * Ustawienia przed każdym testem
     */
    public function setUp(): void {
        parent::setUp();
        $this->plugin = Main::getInstance();
    }

    /**
     * Test tworzenia nowej rozmowy
     */
    public function test_create_conversation() {
        // Symulacja żądania AJAX
        $_POST['action'] = 'aica_create_conversation';
        $_POST['nonce'] = wp_create_nonce('aica_chat');

        // Wywołaj akcję
        ob_start();
        do_action('wp_ajax_aica_create_conversation');
        $response = json_decode(ob_get_clean(), true);

        // Sprawdź odpowiedź
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('conversation_id', $response['data']);
    }

    /**
     * Test wysyłania wiadomości
     */
    public function test_send_message() {
        // Utwórz rozmowę
        $conversation_id = $this->create_test_conversation();

        // Symulacja żądania AJAX
        $_POST['action'] = 'aica_send_message';
        $_POST['nonce'] = wp_create_nonce('aica_chat');
        $_POST['conversation_id'] = $conversation_id;
        $_POST['message'] = 'Test message';

        // Wywołaj akcję
        ob_start();
        do_action('wp_ajax_aica_send_message');
        $response = json_decode(ob_get_clean(), true);

        // Sprawdź odpowiedź
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('message', $response['data']);
    }

    /**
     * Test pobierania historii rozmowy
     */
    public function test_get_conversation_history() {
        // Utwórz rozmowę i dodaj wiadomości
        $conversation_id = $this->create_test_conversation();
        $this->add_test_messages($conversation_id);

        // Symulacja żądania AJAX
        $_GET['action'] = 'aica_get_history';
        $_GET['nonce'] = wp_create_nonce('aica_chat');
        $_GET['conversation_id'] = $conversation_id;

        // Wywołaj akcję
        ob_start();
        do_action('wp_ajax_aica_get_history');
        $response = json_decode(ob_get_clean(), true);

        // Sprawdź odpowiedź
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('messages', $response['data']);
        $this->assertCount(2, $response['data']['messages']);
    }

    /**
     * Test usuwania rozmowy
     */
    public function test_delete_conversation() {
        // Utwórz rozmowę
        $conversation_id = $this->create_test_conversation();

        // Symulacja żądania AJAX
        $_POST['action'] = 'aica_delete_conversation';
        $_POST['nonce'] = wp_create_nonce('aica_chat');
        $_POST['conversation_id'] = $conversation_id;

        // Wywołaj akcję
        ob_start();
        do_action('wp_ajax_aica_delete_conversation');
        $response = json_decode(ob_get_clean(), true);

        // Sprawdź odpowiedź
        $this->assertTrue($response['success']);

        // Sprawdź czy rozmowa została usunięta
        $conversation = get_post($conversation_id);
        $this->assertNull($conversation);
    }

    /**
     * Test wyszukiwania rozmów
     */
    public function test_search_conversations() {
        // Utwórz kilka rozmów
        $conversation1 = $this->create_test_conversation('Test conversation 1');
        $conversation2 = $this->create_test_conversation('Test conversation 2');
        $conversation3 = $this->create_test_conversation('Other conversation');

        // Symulacja żądania AJAX
        $_GET['action'] = 'aica_search_conversations';
        $_GET['nonce'] = wp_create_nonce('aica_chat');
        $_GET['search'] = 'Test';

        // Wywołaj akcję
        ob_start();
        do_action('wp_ajax_aica_search_conversations');
        $response = json_decode(ob_get_clean(), true);

        // Sprawdź odpowiedź
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('conversations', $response['data']);
        $this->assertCount(2, $response['data']['conversations']);
    }

    /**
     * Test obsługi załączników
     */
    public function test_handle_attachments() {
        // Utwórz rozmowę
        $conversation_id = $this->create_test_conversation();

        // Symulacja żądania AJAX z załącznikiem
        $_POST['action'] = 'aica_upload_attachment';
        $_POST['nonce'] = wp_create_nonce('aica_chat');
        $_POST['conversation_id'] = $conversation_id;

        // Symulacja pliku
        $_FILES['attachment'] = [
            'name' => 'test.txt',
            'type' => 'text/plain',
            'tmp_name' => tempnam(sys_get_temp_dir(), 'test'),
            'error' => 0,
            'size' => 1024
        ];

        // Zapisz testowy plik
        file_put_contents($_FILES['attachment']['tmp_name'], 'Test content');

        // Wywołaj akcję
        ob_start();
        do_action('wp_ajax_aica_upload_attachment');
        $response = json_decode(ob_get_clean(), true);

        // Sprawdź odpowiedź
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('attachment_id', $response['data']);

        // Wyczyść plik tymczasowy
        unlink($_FILES['attachment']['tmp_name']);
    }

    /**
     * Pomocnicza metoda do tworzenia testowej rozmowy
     */
    private function create_test_conversation($title = 'Test Conversation') {
        $conversation = wp_insert_post([
            'post_title' => $title,
            'post_type' => 'aica_conversation',
            'post_status' => 'publish'
        ]);

        return $conversation;
    }

    /**
     * Pomocnicza metoda do dodawania testowych wiadomości
     */
    private function add_test_messages($conversation_id) {
        $messages = [
            [
                'role' => 'user',
                'content' => 'Test message 1'
            ],
            [
                'role' => 'assistant',
                'content' => 'Test response 1'
            ]
        ];

        update_post_meta($conversation_id, '_aica_messages', $messages);
    }
} 