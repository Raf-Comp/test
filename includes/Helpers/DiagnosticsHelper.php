<?php
declare(strict_types=1);

/**
 * Pomocnicze funkcje dla diagnostyki
 *
 * @package AI_Chat_Assistant
 */

namespace AICA\Helpers;

if (!defined('ABSPATH')) {
    exit; // Bezpośredni dostęp zabroniony
}

/**
 * Pobiera wartość opcji wtyczki
 *
 * @param string $option_name Nazwa opcji
 * @param mixed $default Domyślna wartość zwracana, jeśli opcja nie istnieje
 * @return mixed Wartość opcji lub wartość domyślna
 */
function aica_get_option(string $option_name, mixed $default = null): mixed {
    // Najpierw sprawdź, czy opcja jest w cache
    static $options_cache = array();
    
    if (isset($options_cache[$option_name])) {
        return $options_cache[$option_name];
    }
    
    // Jeśli nie ma w cache, pobierz z bazy danych
    global $wpdb;
    $table_name = $wpdb->prefix . 'aica_options';
    
    // Sprawdź, czy tabela istnieje
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    
    if ($table_exists) {
        // Pobierz wartość z tabeli wtyczki
        $value = $wpdb->get_var($wpdb->prepare(
            "SELECT option_value FROM $table_name WHERE option_name = %s LIMIT 1",
            $option_name
        ));
        
        if ($value !== null) {
            // Zdekoduj wartość JSON
            $decoded_value = json_decode($value, true);
            // Jeśli dekodowanie się powiedzie, zwróć zdekodowaną wartość, w przeciwnym razie zwróć oryginalną
            $result = (json_last_error() === JSON_ERROR_NONE) ? $decoded_value : $value;
            $options_cache[$option_name] = $result;
            return $result;
        }
    }
    
    // Jeśli nie znaleziono w tabeli wtyczki, spróbuj pobrać z opcji WordPress
    $wp_option_name = 'aica_' . $option_name;
    $value = get_option($wp_option_name, $default);
    
    // Zapisz w cache i zwróć
    $options_cache[$option_name] = $value;
    return $value;
}

/**
 * Aktualizuje wartość opcji wtyczki
 *
 * @param string $option_name Nazwa opcji
 * @param mixed $option_value Wartość opcji
 * @return bool Czy operacja się powiodła
 */
function aica_update_option(string $option_name, mixed $option_value): bool {
    global $wpdb;
    $table_name = $wpdb->prefix . 'aica_options';
    
    // Sprawdź, czy tabela istnieje
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    
    if (!$table_exists) {
        // Jeśli tabela nie istnieje, użyj standardowej funkcji WordPress
        $wp_option_name = 'aica_' . $option_name;
        return update_option($wp_option_name, $option_value);
    }
    
    // Przygotuj wartość do zapisania w bazie danych
    $value_to_save = is_array($option_value) || is_object($option_value) 
        ? json_encode($option_value) 
        : $option_value;
    
    $now = current_time('mysql');
    
    // Sprawdź, czy opcja już istnieje
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE option_name = %s",
        $option_name
    ));
    
    if ($exists) {
        // Aktualizuj istniejącą opcję
        $result = $wpdb->update(
            $table_name,
            array(
                'option_value' => $value_to_save,
                'updated_at' => $now
            ),
            array('option_name' => $option_name),
            array('%s', '%s'),
            array('%s')
        );
    } else {
        // Dodaj nową opcję
        $result = $wpdb->insert(
            $table_name,
            array(
                'option_name' => $option_name,
                'option_value' => $value_to_save,
                'autoload' => 'yes',
                'created_at' => $now,
                'updated_at' => $now
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );
    }
    
    // Aktualizuj cache
    if ($result !== false) {
        static $options_cache = array();
        $options_cache[$option_name] = $option_value;
    }
    
    return $result !== false;
}

/**
 * Usuwa opcję wtyczki
 *
 * @param string $option_name Nazwa opcji
 * @return bool Czy operacja się powiodła
 */
function aica_delete_option(string $option_name): bool {
    global $wpdb;
    $table_name = $wpdb->prefix . 'aica_options';
    
    // Sprawdź, czy tabela istnieje
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    
    if (!$table_exists) {
        // Jeśli tabela nie istnieje, użyj standardowej funkcji WordPress
        $wp_option_name = 'aica_' . $option_name;
        return delete_option($wp_option_name);
    }
    
    // Usuń opcję z tabeli
    $result = $wpdb->delete(
        $table_name,
        array('option_name' => $option_name),
        array('%s')
    );
    
    // Aktualizuj cache
    if ($result !== false) {
        static $options_cache = array();
        unset($options_cache[$option_name]);
    }
    
    return $result !== false;
}

/**
 * Dodaje wpis do dziennika wtyczki
 *
 * @param string $message Wiadomość do zapisania
 * @param string $level Poziom logowania (info, warning, error, debug)
 * @return bool Czy operacja się powiodła
 */
function aica_log(string $message, string $level = 'info'): bool {
    // Sprawdź, czy logowanie jest włączone
    $logging_enabled = aica_get_option('enable_logging', false);
    
    if (!$logging_enabled && $level !== 'error') {
        return false;
    }
    
    // Określ plik dziennika
    $upload_dir = wp_upload_dir();
    $log_dir = $upload_dir['basedir'] . '/aica-logs';
    
    // Utwórz katalog logów, jeśli nie istnieje
    if (!file_exists($log_dir)) {
        wp_mkdir_p($log_dir);
        
        // Dodaj plik .htaccess dla bezpieczeństwa
        $htaccess_file = $log_dir . '/.htaccess';
        $htaccess_content = "Options -Indexes\nDeny from all";
        file_put_contents($htaccess_file, $htaccess_content);
    }
    
    // Utwórz nazwę pliku dziennika bazując na bieżącej dacie
    $date = date('Y-m-d');
    $log_file = $log_dir . '/aica-' . $date . '.log';
    
    // Formatuj wpis dziennika
    $time = date('Y-m-d H:i:s');
    $level_uppercase = strtoupper($level);
    $log_entry = "[{$time}] [{$level_uppercase}] {$message}" . PHP_EOL;
    
    // Zapisz wpis do pliku
    $result = file_put_contents($log_file, $log_entry, FILE_APPEND);
    
    return $result !== false;
}

/**
 * Pobiera identyfikator użytkownika wtyczki na podstawie ID użytkownika WordPress
 *
 * @param int $wp_user_id ID użytkownika WordPress
 * @return int|bool ID użytkownika wtyczki lub false w przypadku niepowodzenia
 */
function aica_get_user_id(int $wp_user_id): ?int {
    global $wpdb;
    $table_name = $wpdb->prefix . 'aica_users';
    
    // Sprawdź, czy tabela istnieje
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    
    if (!$table_exists) {
        return null;
    }
    
    // Pobierz ID użytkownika wtyczki
    $user_id = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table_name WHERE wp_user_id = %d",
        $wp_user_id
    ));
    
    return $user_id ? (int) $user_id : null;
}

/**
 * Dodaje nowego użytkownika do tabeli użytkowników wtyczki
 *
 * @param int $wp_user_id ID użytkownika WordPress
 * @param string $username Nazwa użytkownika
 * @param string $email Adres e-mail użytkownika
 * @param string $role Rola użytkownika
 * @param string $created_at Data utworzenia użytkownika (format MySQL)
 * @return int|bool ID dodanego użytkownika lub false w przypadku niepowodzenia
 */
function aica_add_user(int $wp_user_id, string $username, string $email, string $role, string $created_at): int|false {
    global $wpdb;
    $table_name = $wpdb->prefix . 'aica_users';
    
    // Sprawdź, czy tabela istnieje
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    
    if (!$table_exists) {
        return false;
    }
    
    // Sprawdź, czy użytkownik już istnieje
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE wp_user_id = %d",
        $wp_user_id
    ));
    
    if ($exists) {
        // Użytkownik już istnieje, zwróć jego ID
        $user_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE wp_user_id = %d",
            $wp_user_id
        ));
        
        return (int) $user_id;
    }
    
    // Dodaj nowego użytkownika
    $result = $wpdb->insert(
        $table_name,
        array(
            'wp_user_id' => $wp_user_id,
            'username' => $username,
            'email' => $email,
            'role' => $role,
            'created_at' => $created_at,
            'updated_at' => $created_at
        ),
        array('%d', '%s', '%s', '%s', '%s', '%s')
    );
    
    if (!$result) {
        return false;
    }
    
    // Zwróć ID dodanego użytkownika
    return (int) $wpdb->insert_id;
}

/**
 * Sprawdza czy klasa API istnieje i próbuje ją załadować
 */
function aica_check_api_class(string $class_name, string $file_path): bool {
    if (!class_exists($class_name)) {
        if (file_exists($file_path)) {
            require_once $file_path;
            return class_exists($class_name);
        }
        return false;
    }
    return true;
}

/**
 * Sprawdza połączenie z API
 */
function aica_check_api_connection(string $api_type, array $credentials): bool {
    return match($api_type) {
        'claude' => aica_check_claude_api($credentials['api_key'] ?? ''),
        'github' => aica_check_github_api($credentials['token'] ?? ''),
        'gitlab' => aica_check_gitlab_api($credentials['token'] ?? ''),
        'bitbucket' => aica_check_bitbucket_api($credentials),
        default => false
    };
}

/**
 * Sprawdza połączenie z API Claude
 */
function aica_check_claude_api(string $api_key): bool {
    if (empty($api_key)) {
        return false;
    }

    if (!aica_check_api_class('\AICA\API\ClaudeClient', AICA_PLUGIN_DIR . 'includes/API/ClaudeClient.php')) {
        return false;
    }

    try {
        $claude_client = new \AICA\API\ClaudeClient($api_key);
        $test_result = $claude_client->test_connection();

        return $test_result;
    } catch (\Exception $e) {
        return false;
    }
}

/**
 * Sprawdza połączenie z API GitHub
 */
function aica_check_github_api(string $token): bool {
    if (empty($token)) {
        return false;
    }

    if (!aica_check_api_class('\AICA\API\GitHubClient', AICA_PLUGIN_DIR . 'includes/API/GitHubClient.php')) {
        return false;
    }

    try {
        $client = new \AICA\API\GitHubClient($token);
        $result = $client->test_connection();

        return $result;
    } catch (\Exception $e) {
        return false;
    }
}

/**
 * Sprawdza połączenie z API GitLab
 */
function aica_check_gitlab_api(string $token): bool {
    if (empty($token)) {
        return false;
    }

    try {
        $response = wp_remote_get('https://gitlab.com/api/v4/user', [
            'headers' => ['PRIVATE-TOKEN' => $token],
            'timeout' => 10
        ]);

        $code = wp_remote_retrieve_response_code($response);
        return $code === 200;
    } catch (\Exception $e) {
        return false;
    }
}

/**
 * Sprawdza połączenie z API Bitbucket
 */
function aica_check_bitbucket_api(array $credentials): bool {
    if (empty($credentials['username']) || empty($credentials['password'])) {
        return false;
    }

    try {
        $auth = base64_encode($credentials['username'] . ':' . $credentials['password']);
        $response = wp_remote_get('https://api.bitbucket.org/2.0/user', [
            'headers' => ['Authorization' => 'Basic ' . $auth],
            'timeout' => 10
        ]);

        $code = wp_remote_retrieve_response_code($response);
        return $code === 200;
    } catch (\Exception $e) {
        return false;
    }
}

/**
 * Pobiera status bazy danych
 */
function aica_get_database_status(): array {
    global $wpdb;
    $tables = $wpdb->get_col("SHOW TABLES");
    $prefix = $wpdb->prefix . 'aica_';
    $status = [];

    // Lista oczekiwanych tabel
    $expected_tables = [
        $wpdb->prefix . 'aica_repositories' => 'Repozytoria',
        $wpdb->prefix . 'aica_sessions' => 'Sesje',
        $wpdb->prefix . 'aica_messages' => 'Wiadomości',
        $wpdb->prefix . 'aica_users' => 'Użytkownicy',
        $wpdb->prefix . 'aica_options' => 'Opcje'
    ];

    // Sprawdź każdą oczekiwaną tabelę
    foreach ($expected_tables as $table_name => $description) {
        $exists = in_array($table_name, $tables);
        $records = 0;
        
        if ($exists) {
            $records = $wpdb->get_var("SELECT COUNT(*) FROM `$table_name`");
        }
        
        $status[$table_name] = [
            'name' => $description,
            'exists' => $exists,
            'records' => (int) $records
        ];
    }

    return $status;
}

/**
 * Pobiera uprawnienia plików
 */
function aica_get_files_permissions(): array {
    $files = [
        AICA_PLUGIN_DIR . 'ai-chat-assistant.php' => __('Plik główny wtyczki', 'ai-chat-assistant'),
        AICA_PLUGIN_DIR . 'includes/Main.php' => __('Klasa główna', 'ai-chat-assistant'),
        AICA_PLUGIN_DIR . 'includes/Installer.php' => __('Instalator', 'ai-chat-assistant'),
        AICA_PLUGIN_DIR . 'includes/API/ClaudeClient.php' => __('Klient Claude API', 'ai-chat-assistant'),
        AICA_PLUGIN_DIR . 'assets/js/repositories.js' => __('Skrypt repozytoriów', 'ai-chat-assistant'),
        AICA_PLUGIN_DIR . 'assets/css/repositories.css' => __('Style repozytoriów', 'ai-chat-assistant')
    ];

    $status = [];
    foreach ($files as $file => $name) {
        $exists = file_exists($file);
        $readable = $exists ? is_readable($file) : false;
        $writable = $exists ? is_writable($file) : false;
        $permissions = $exists ? substr(sprintf('%o', fileperms($file)), -4) : '';

        $status[$name] = [
            'path' => $file,
            'exists' => $exists,
            'readable' => $readable,
            'writable' => $writable,
            'permissions' => $permissions
        ];
    }

    return $status;
}

/**
 * Generuje rekomendacje na podstawie statusu systemu
 */
function aica_get_diagnostics_recommendations(
    array $db_status,
    array $files_status,
    bool $claude_api,
    bool $github_api,
    bool $gitlab_api,
    bool $bitbucket_api
): array {
    $recommendations = [];

    // Sprawdzanie stanu bazy danych
    $missing_tables = 0;
    foreach ($db_status as $table) {
        if (!$table['exists']) {
            $missing_tables++;
        }
    }
    
    if ($missing_tables > 0) {
        $recommendations[] = sprintf(
            __('Brakuje %d z %d wymaganych tabel w bazie danych. Użyj przycisku "Napraw" w sekcji "Status bazy danych".', 'ai-chat-assistant'),
            $missing_tables,
            count($db_status)
        );
    }

    // Sprawdzanie plików
    foreach ($files_status as $name => $status) {
        if (!$status['exists']) {
            $recommendations[] = sprintf(
                __('Brakuje pliku: %s (%s)', 'ai-chat-assistant'),
                $name,
                $status['path']
            );
        } elseif (!$status['readable']) {
            $recommendations[] = sprintf(
                __('Brak uprawnień do odczytu pliku: %s (%s)', 'ai-chat-assistant'),
                $name,
                $status['path']
            );
        }
    }

    // Sprawdzanie API
    if (!$claude_api['valid']) {
        $recommendations[] = __('Skonfiguruj poprawnie klucz API Claude w ustawieniach wtyczki.', 'ai-chat-assistant');
    } elseif (isset($claude_api['details']) && isset($claude_api['details']['model_available']) && !$claude_api['details']['model_available']) {
        $recommendations[] = __('Wybrany model Claude nie jest dostępny. Zmień model w ustawieniach wtyczki.', 'ai-chat-assistant');
    }

    if (!$github_api['valid']) {
        $recommendations[] = __('Skonfiguruj token GitHub w ustawieniach wtyczki.', 'ai-chat-assistant');
    }

    if (!$gitlab_api['valid']) {
        $recommendations[] = __('Skonfiguruj token GitLab w ustawieniach wtyczki.', 'ai-chat-assistant');
    }

    if (!$bitbucket_api['valid']) {
        $recommendations[] = __('Skonfiguruj dane dostępowe Bitbucket w ustawieniach wtyczki.', 'ai-chat-assistant');
    }

    return $recommendations;
}

/**
 * Klasa pomocnicza do diagnostyki
 */
class DiagnosticsHelper {
    /**
     * Pobiera informacje o systemie
     *
     * @return array<string, mixed>
     */
    public static function get_system_info(): array {
        return [
            'php_version' => PHP_VERSION,
            'wp_version' => get_bloginfo('version'),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'mysql_version' => self::get_mysql_version(),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_input_vars' => ini_get('max_input_vars'),
            'display_errors' => ini_get('display_errors'),
            'error_reporting' => ini_get('error_reporting'),
            'curl_version' => self::get_curl_version(),
            'ssl_version' => self::get_ssl_version(),
            'timezone' => date_default_timezone_get(),
            'locale' => get_locale(),
            'multisite' => is_multisite(),
            'debug_mode' => defined('WP_DEBUG') && WP_DEBUG,
            'active_plugins' => self::get_active_plugins(),
            'theme' => self::get_theme_info(),
            'server_os' => PHP_OS,
            'server_architecture' => PHP_INT_SIZE * 8 . ' bit',
            'server_protocol' => $_SERVER['SERVER_PROTOCOL'] ?? 'Unknown',
            'server_name' => $_SERVER['SERVER_NAME'] ?? 'Unknown',
            'server_port' => $_SERVER['SERVER_PORT'] ?? 'Unknown',
            'server_addr' => $_SERVER['SERVER_ADDR'] ?? 'Unknown',
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
            'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'Unknown',
            'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'Unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
            'http_user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'http_accept' => $_SERVER['HTTP_ACCEPT'] ?? 'Unknown',
            'http_accept_language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'Unknown',
            'http_accept_encoding' => $_SERVER['HTTP_ACCEPT_ENCODING'] ?? 'Unknown',
            'http_connection' => $_SERVER['HTTP_CONNECTION'] ?? 'Unknown',
            'http_host' => $_SERVER['HTTP_HOST'] ?? 'Unknown',
            'http_referer' => $_SERVER['HTTP_REFERER'] ?? 'Unknown',
            'http_cookie' => $_SERVER['HTTP_COOKIE'] ?? 'Unknown',
            'http_sec_fetch_dest' => $_SERVER['HTTP_SEC_FETCH_DEST'] ?? 'Unknown',
            'http_sec_fetch_mode' => $_SERVER['HTTP_SEC_FETCH_MODE'] ?? 'Unknown',
            'http_sec_fetch_site' => $_SERVER['HTTP_SEC_FETCH_SITE'] ?? 'Unknown',
            'http_sec_fetch_user' => $_SERVER['HTTP_SEC_FETCH_USER'] ?? 'Unknown',
            'http_upgrade_insecure_requests' => $_SERVER['HTTP_UPGRADE_INSECURE_REQUESTS'] ?? 'Unknown',
            'http_sec_ch_ua' => $_SERVER['HTTP_SEC_CH_UA'] ?? 'Unknown',
            'http_sec_ch_ua_mobile' => $_SERVER['HTTP_SEC_CH_UA_MOBILE'] ?? 'Unknown',
            'http_sec_ch_ua_platform' => $_SERVER['HTTP_SEC_CH_UA_PLATFORM'] ?? 'Unknown',
            'http_sec_ch_ua_platform_version' => $_SERVER['HTTP_SEC_CH_UA_PLATFORM_VERSION'] ?? 'Unknown',
            'http_sec_ch_ua_full_version' => $_SERVER['HTTP_SEC_CH_UA_FULL_VERSION'] ?? 'Unknown',
            'http_sec_ch_ua_full_version_list' => $_SERVER['HTTP_SEC_CH_UA_FULL_VERSION_LIST'] ?? 'Unknown',
            'http_sec_ch_ua_bitness' => $_SERVER['HTTP_SEC_CH_UA_BITNESS'] ?? 'Unknown',
            'http_sec_ch_ua_model' => $_SERVER['HTTP_SEC_CH_UA_MODEL'] ?? 'Unknown',
            'http_sec_ch_ua_arch' => $_SERVER['HTTP_SEC_CH_UA_ARCH'] ?? 'Unknown',
            'http_sec_ch_ua_full_version_list' => $_SERVER['HTTP_SEC_CH_UA_FULL_VERSION_LIST'] ?? 'Unknown',
            'http_sec_ch_ua_mobile' => $_SERVER['HTTP_SEC_CH_UA_MOBILE'] ?? 'Unknown',
            'http_sec_ch_ua_platform' => $_SERVER['HTTP_SEC_CH_UA_PLATFORM'] ?? 'Unknown',
            'http_sec_ch_ua_platform_version' => $_SERVER['HTTP_SEC_CH_UA_PLATFORM_VERSION'] ?? 'Unknown',
            'http_sec_ch_ua_full_version' => $_SERVER['HTTP_SEC_CH_UA_FULL_VERSION'] ?? 'Unknown',
            'http_sec_ch_ua_full_version_list' => $_SERVER['HTTP_SEC_CH_UA_FULL_VERSION_LIST'] ?? 'Unknown',
            'http_sec_ch_ua_bitness' => $_SERVER['HTTP_SEC_CH_UA_BITNESS'] ?? 'Unknown',
            'http_sec_ch_ua_model' => $_SERVER['HTTP_SEC_CH_UA_MODEL'] ?? 'Unknown',
            'http_sec_ch_ua_arch' => $_SERVER['HTTP_SEC_CH_UA_ARCH'] ?? 'Unknown'
        ];
    }

    /**
     * Pobiera wersję MySQL
     *
     * @return string
     */
    private static function get_mysql_version(): string {
        global $wpdb;
        return $wpdb->get_var('SELECT VERSION()') ?: 'Unknown';
    }

    /**
     * Pobiera wersję cURL
     *
     * @return string
     */
    private static function get_curl_version(): string {
        if (function_exists('curl_version')) {
            $curl_info = curl_version();
            return $curl_info['version'] ?? 'Unknown';
        }
        return 'Not available';
    }

    /**
     * Pobiera wersję SSL
     *
     * @return string
     */
    private static function get_ssl_version(): string {
        if (function_exists('curl_version')) {
            $curl_info = curl_version();
            return $curl_info['ssl_version'] ?? 'Unknown';
        }
        return 'Not available';
    }

    /**
     * Pobiera listę aktywnych wtyczek
     *
     * @return array<string, string>
     */
    private static function get_active_plugins(): array {
        $active_plugins = get_option('active_plugins');
        $plugins = [];
        
        if (is_array($active_plugins)) {
            foreach ($active_plugins as $plugin) {
                $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
                $plugins[$plugin] = $plugin_data['Version'] ?? 'Unknown';
            }
        }
        
        return $plugins;
    }

    /**
     * Pobiera informacje o motywie
     *
     * @return array<string, string>
     */
    private static function get_theme_info(): array {
        $theme = wp_get_theme();
        return [
            'name' => $theme->get('Name'),
            'version' => $theme->get('Version'),
            'author' => $theme->get('Author'),
            'author_uri' => $theme->get('AuthorURI'),
            'theme_uri' => $theme->get('ThemeURI'),
            'description' => $theme->get('Description'),
            'text_domain' => $theme->get('TextDomain'),
            'template' => $theme->get('Template'),
            'status' => $theme->get('Status'),
            'tags' => implode(', ', $theme->get('Tags')),
            'parent' => $theme->get('Parent Theme')
        ];
    }

    /**
     * Sprawdza wymagania systemowe
     *
     * @return array<string, array{status: bool, message: string}>
     */
    public static function check_requirements(): array {
        return [
            'php_version' => [
                'status' => version_compare(PHP_VERSION, '8.0.0', '>='),
                'message' => sprintf(
                    'PHP version %s is required. Current version is %s.',
                    '8.0.0',
                    PHP_VERSION
                )
            ],
            'wp_version' => [
                'status' => version_compare(get_bloginfo('version'), '5.8.0', '>='),
                'message' => sprintf(
                    'WordPress version %s is required. Current version is %s.',
                    '5.8.0',
                    get_bloginfo('version')
                )
            ],
            'memory_limit' => [
                'status' => self::check_memory_limit(),
                'message' => sprintf(
                    'Memory limit of %s is required. Current limit is %s.',
                    '128M',
                    ini_get('memory_limit')
                )
            ],
            'max_execution_time' => [
                'status' => self::check_max_execution_time(),
                'message' => sprintf(
                    'Max execution time of %s seconds is required. Current time is %s seconds.',
                    '30',
                    ini_get('max_execution_time')
                )
            ],
            'upload_max_filesize' => [
                'status' => self::check_upload_max_filesize(),
                'message' => sprintf(
                    'Upload max filesize of %s is required. Current size is %s.',
                    '2M',
                    ini_get('upload_max_filesize')
                )
            ],
            'post_max_size' => [
                'status' => self::check_post_max_size(),
                'message' => sprintf(
                    'Post max size of %s is required. Current size is %s.',
                    '8M',
                    ini_get('post_max_size')
                )
            ],
            'max_input_vars' => [
                'status' => self::check_max_input_vars(),
                'message' => sprintf(
                    'Max input vars of %s is required. Current vars is %s.',
                    '1000',
                    ini_get('max_input_vars')
                )
            ],
            'curl' => [
                'status' => function_exists('curl_version'),
                'message' => 'cURL extension is required.'
            ],
            'ssl' => [
                'status' => self::check_ssl(),
                'message' => 'SSL support is required.'
            ],
            'json' => [
                'status' => function_exists('json_encode'),
                'message' => 'JSON extension is required.'
            ],
            'mbstring' => [
                'status' => function_exists('mb_strlen'),
                'message' => 'Multibyte String extension is required.'
            ],
            'xml' => [
                'status' => function_exists('simplexml_load_string'),
                'message' => 'XML extension is required.'
            ],
            'zip' => [
                'status' => class_exists('ZipArchive'),
                'message' => 'ZIP extension is required.'
            ],
            'gd' => [
                'status' => function_exists('gd_info'),
                'message' => 'GD extension is required.'
            ],
            'fileinfo' => [
                'status' => function_exists('finfo_open'),
                'message' => 'Fileinfo extension is required.'
            ],
            'iconv' => [
                'status' => function_exists('iconv'),
                'message' => 'Iconv extension is required.'
            ],
            'intl' => [
                'status' => function_exists('intl_get_error_code'),
                'message' => 'Intl extension is required.'
            ],
            'pdo' => [
                'status' => class_exists('PDO'),
                'message' => 'PDO extension is required.'
            ],
            'pdo_mysql' => [
                'status' => in_array('mysql', PDO::getAvailableDrivers()),
                'message' => 'PDO MySQL driver is required.'
            ],
            'openssl' => [
                'status' => function_exists('openssl_encrypt'),
                'message' => 'OpenSSL extension is required.'
            ],
            'sodium' => [
                'status' => function_exists('sodium_crypto_secretbox'),
                'message' => 'Sodium extension is required.'
            ],
            'hash' => [
                'status' => function_exists('hash'),
                'message' => 'Hash extension is required.'
            ],
            'random_bytes' => [
                'status' => function_exists('random_bytes'),
                'message' => 'Random bytes function is required.'
            ],
            'random_int' => [
                'status' => function_exists('random_int'),
                'message' => 'Random int function is required.'
            ],
            'password_hash' => [
                'status' => function_exists('password_hash'),
                'message' => 'Password hash function is required.'
            ],
            'password_verify' => [
                'status' => function_exists('password_verify'),
                'message' => 'Password verify function is required.'
            ],
            'password_needs_rehash' => [
                'status' => function_exists('password_needs_rehash'),
                'message' => 'Password needs rehash function is required.'
            ],
            'password_get_info' => [
                'status' => function_exists('password_get_info'),
                'message' => 'Password get info function is required.'
            ],
            'password_algos' => [
                'status' => function_exists('password_algos'),
                'message' => 'Password algos function is required.'
            ],
            'password_hash' => [
                'status' => function_exists('password_hash'),
                'message' => 'Password hash function is required.'
            ],
            'password_verify' => [
                'status' => function_exists('password_verify'),
                'message' => 'Password verify function is required.'
            ],
            'password_needs_rehash' => [
                'status' => function_exists('password_needs_rehash'),
                'message' => 'Password needs rehash function is required.'
            ],
            'password_get_info' => [
                'status' => function_exists('password_get_info'),
                'message' => 'Password get info function is required.'
            ],
            'password_algos' => [
                'status' => function_exists('password_algos'),
                'message' => 'Password algos function is required.'
            ]
        ];
    }

    /**
     * Sprawdza limit pamięci
     *
     * @return bool
     */
    private static function check_memory_limit(): bool {
        $memory_limit = ini_get('memory_limit');
        if ($memory_limit === '-1') {
            return true;
        }
        $memory_limit_bytes = self::return_bytes($memory_limit);
        return $memory_limit_bytes >= 134217728; // 128M
    }

    /**
     * Sprawdza maksymalny czas wykonania
     *
     * @return bool
     */
    private static function check_max_execution_time(): bool {
        $max_execution_time = ini_get('max_execution_time');
        if ($max_execution_time === '0') {
            return true;
        }
        return (int) $max_execution_time >= 30;
    }

    /**
     * Sprawdza maksymalny rozmiar pliku
     *
     * @return bool
     */
    private static function check_upload_max_filesize(): bool {
        $upload_max_filesize = ini_get('upload_max_filesize');
        $upload_max_filesize_bytes = self::return_bytes($upload_max_filesize);
        return $upload_max_filesize_bytes >= 2097152; // 2M
    }

    /**
     * Sprawdza maksymalny rozmiar POST
     *
     * @return bool
     */
    private static function check_post_max_size(): bool {
        $post_max_size = ini_get('post_max_size');
        $post_max_size_bytes = self::return_bytes($post_max_size);
        return $post_max_size_bytes >= 8388608; // 8M
    }

    /**
     * Sprawdza maksymalną liczbę zmiennych wejściowych
     *
     * @return bool
     */
    private static function check_max_input_vars(): bool {
        $max_input_vars = ini_get('max_input_vars');
        return (int) $max_input_vars >= 1000;
    }

    /**
     * Sprawdza obsługę SSL
     *
     * @return bool
     */
    private static function check_ssl(): bool {
        if (function_exists('curl_version')) {
            $curl_info = curl_version();
            return isset($curl_info['ssl_version']) && !empty($curl_info['ssl_version']);
        }
        return false;
    }

    /**
     * Konwertuje wartość z jednostkami na bajty
     *
     * @param string $val
     * @return int
     */
    private static function return_bytes(string $val): int {
        $val = trim($val);
        $last = strtolower($val[strlen($val) - 1]);
        $val = (int) $val;
        
        switch ($last) {
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }
        
        return $val;
    }
}