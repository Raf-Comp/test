<?php
declare(strict_types=1);

namespace AICA\Helpers;

/**
 * Klasa pomocnicza do zarządzania bezpieczeństwem wtyczki
 * 
 * Zapewnia metody do:
 * - Sprawdzania uprawnień użytkownika
 * - Weryfikacji nonce
 * - Bezpiecznego pobierania danych z formularzy
 * - Bezpiecznego wyświetlania danych
 * 
 * @package AIChatAssistant
 * @since 1.0.0
 */
class Security {
    /**
     * Generuje token bezpieczeństwa
     *
     * @param string $action Akcja
     * @return string Token bezpieczeństwa
     */
    public static function generate_nonce(string $action): string {
        return wp_create_nonce('aica-' . $action);
    }

    /**
     * Weryfikuje token bezpieczeństwa
     *
     * @param string $nonce Token bezpieczeństwa
     * @param string $action Akcja
     * @return bool Czy token jest prawidłowy
     */
    public static function verify_nonce(string $nonce, string $action): bool {
        return wp_verify_nonce($nonce, 'aica-' . $action);
    }

    /**
     * Sprawdza uprawnienia użytkownika
     *
     * @param string $capability Uprawnienie
     * @return bool Czy użytkownik ma uprawnienie
     */
    public static function check_capability(string $capability): bool {
        return current_user_can($capability);
    }

    /**
     * Sanityzuje dane wejściowe
     *
     * @param mixed $data Dane do sanityzacji
     * @return mixed Zsanityzowane dane
     */
    public static function sanitize_data(mixed $data): mixed {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::sanitize_data($value);
            }
            return $data;
        }

        if (is_string($data)) {
            return sanitize_text_field($data);
        }

        return $data;
    }

    /**
     * Waliduje dane wejściowe
     *
     * @param mixed $data Dane do walidacji
     * @param string $type Typ danych
     * @return bool Czy dane są prawidłowe
     */
    public static function validate_data(mixed $data, string $type): bool {
        return match($type) {
            'email' => is_email($data),
            'url' => filter_var($data, FILTER_VALIDATE_URL) !== false,
            'int' => filter_var($data, FILTER_VALIDATE_INT) !== false,
            'float' => filter_var($data, FILTER_VALIDATE_FLOAT) !== false,
            'boolean' => is_bool($data),
            'array' => is_array($data),
            'string' => is_string($data),
            default => false
        };
    }

    /**
     * Szyfruje dane
     *
     * @param string $data Dane do zaszyfrowania
     * @return string Zaszyfrowane dane
     */
    public static function encrypt_data(string $data): string {
        $key = wp_salt('auth');
        $method = 'aes-256-cbc';
        $ivlen = openssl_cipher_iv_length($method);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $encrypted = openssl_encrypt($data, $method, $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    /**
     * Deszyfruje dane
     *
     * @param string $data Dane do odszyfrowania
     * @return string|false Odszyfrowane dane lub false w przypadku błędu
     */
    public static function decrypt_data(string $data): string|false {
        $key = wp_salt('auth');
        $method = 'aes-256-cbc';
        $data = base64_decode($data);
        $ivlen = openssl_cipher_iv_length($method);
        $iv = substr($data, 0, $ivlen);
        $encrypted = substr($data, $ivlen);
        return openssl_decrypt($encrypted, $method, $key, 0, $iv);
    }

    /**
     * Generuje bezpieczne hasło
     *
     * @param int $length Długość hasła
     * @return string Wygenerowane hasło
     */
    public static function generate_password(int $length = 12): string {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+-=[]{}|;:,.<>?';
        $password = '';
        $max = strlen($chars) - 1;

        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, $max)];
        }

        return $password;
    }

    /**
     * Sprawdza czy hasło jest bezpieczne
     *
     * @param string $password Hasło do sprawdzenia
     * @return bool Czy hasło jest bezpieczne
     */
    public static function is_password_secure(string $password): bool {
        return strlen($password) >= 8
            && preg_match('/[A-Z]/', $password)
            && preg_match('/[a-z]/', $password)
            && preg_match('/[0-9]/', $password)
            && preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password);
    }

    /**
     * Sprawdza uprawnienia użytkownika
     * 
     * @param string $capability Nazwa uprawnienia do sprawdzenia
     * @return bool True jeśli użytkownik ma uprawnienie, false w przeciwnym razie
     */
    public static function check_user_capability(string $capability = 'manage_options'): bool {
        if (!current_user_can($capability)) {
            wp_die(
                esc_html__('Nie masz wystarczających uprawnień, aby uzyskać dostęp do tej strony.', 'ai-chat-assistant'),
                esc_html__('Błąd uprawnień', 'ai-chat-assistant'),
                ['response' => 403]
            );
        }
        return true;
    }

    /**
     * Pobiera wartość z tablicy POST
     *
     * @param string $key Klucz
     * @param mixed $default Wartość domyślna
     * @return mixed Wartość z tablicy POST lub wartość domyślna
     */
    public static function get_post_value(string $key, mixed $default = ''): mixed {
        return isset($_POST[$key]) ? self::sanitize_data($_POST[$key]) : $default;
    }

    /**
     * Pobiera wartość z tablicy GET
     *
     * @param string $key Klucz
     * @param mixed $default Wartość domyślna
     * @return mixed Wartość z tablicy GET lub wartość domyślna
     */
    public static function get_get_value(string $key, mixed $default = ''): mixed {
        return isset($_GET[$key]) ? self::sanitize_data($_GET[$key]) : $default;
    }

    /**
     * Pobiera wartość z tablicy REQUEST
     *
     * @param string $key Klucz
     * @param mixed $default Wartość domyślna
     * @return mixed Wartość z tablicy REQUEST lub wartość domyślna
     */
    public static function get_request_value(string $key, mixed $default = ''): mixed {
        return isset($_REQUEST[$key]) ? self::sanitize_data($_REQUEST[$key]) : $default;
    }

    /**
     * Pobiera tablicę z tablicy POST
     *
     * @param string $key Klucz
     * @param array $default Wartość domyślna
     * @return array Tablica z tablicy POST lub wartość domyślna
     */
    public static function get_post_array(string $key, array $default = []): array {
        if (!isset($_POST[$key]) || !is_array($_POST[$key])) {
            return $default;
        }
        return array_map([self::class, 'sanitize_data'], $_POST[$key]);
    }

    /**
     * Pobiera tablicę z tablicy GET
     *
     * @param string $key Klucz
     * @param array $default Wartość domyślna
     * @return array Tablica z tablicy GET lub wartość domyślna
     */
    public static function get_get_array(string $key, array $default = []): array {
        if (!isset($_GET[$key]) || !is_array($_GET[$key])) {
            return $default;
        }
        return array_map([self::class, 'sanitize_data'], $_GET[$key]);
    }

    /**
     * Pobiera tablicę z tablicy REQUEST
     *
     * @param string $key Klucz
     * @param array $default Wartość domyślna
     * @return array Tablica z tablicy REQUEST lub wartość domyślna
     */
    public static function get_request_array(string $key, array $default = []): array {
        if (!isset($_REQUEST[$key]) || !is_array($_REQUEST[$key])) {
            return $default;
        }
        return array_map([self::class, 'sanitize_data'], $_REQUEST[$key]);
    }

    /**
     * Wyświetla tekst
     *
     * @param string $text Tekst do wyświetlenia
     * @param bool $echo Czy wyświetlić tekst
     * @return string Wyświetlony tekst
     */
    public static function display_text(string $text, bool $echo = true): string {
        $output = esc_html($text);
        if ($echo) {
            echo $output;
        }
        return $output;
    }

    /**
     * Wyświetla atrybut
     *
     * @param string $text Tekst do wyświetlenia
     * @param bool $echo Czy wyświetlić tekst
     * @return string Wyświetlony tekst
     */
    public static function display_attribute(string $text, bool $echo = true): string {
        $output = esc_attr($text);
        if ($echo) {
            echo $output;
        }
        return $output;
    }

    /**
     * Wyświetla URL
     *
     * @param string $url URL do wyświetlenia
     * @param bool $echo Czy wyświetlić URL
     * @return string Wyświetlony URL
     */
    public static function display_url(string $url, bool $echo = true): string {
        $output = esc_url($url);
        if ($echo) {
            echo $output;
        }
        return $output;
    }

    /**
     * Wyświetla HTML
     *
     * @param string $html HTML do wyświetlenia
     * @param bool $echo Czy wyświetlić HTML
     * @return string Wyświetlony HTML
     */
    public static function display_html(string $html, bool $echo = true): string {
        $output = wp_kses_post($html);
        if ($echo) {
            echo $output;
        }
        return $output;
    }
} 