<?php
declare(strict_types=1);

namespace AICA;

/**
 * Funkcje pomocnicze dla wtyczki AI Chat Assistant
 *
 * @package AIChatAssistant
 */

if (!defined('ABSPATH')) {
    exit; // Bezpośredni dostęp zabroniony
}

// Load helper files
require_once plugin_dir_path(__FILE__) . 'Helpers/TableHelper.php';
require_once plugin_dir_path(__FILE__) . 'Helpers/SecurityHelper.php';
require_once plugin_dir_path(__FILE__) . 'Helpers/ValidationHelper.php';

/**
 * Klasa pomocnicza
 */
class Helpers {
    /**
     * Sprawdza, czy klucz API Claude jest skonfigurowany
     */
    public static function is_api_configured(): bool {
        $settings = get_option('aica_settings', []);
        return !empty($settings['claude_api_key']);
    }

    /**
     * Pobiera aktualnie wybrany model Claude
     */
    public static function get_current_model(): string {
        $settings = get_option('aica_settings', []);
        return $settings['model'] ?? 'claude-3-haiku-20240307';
    }

    /**
     * Pobiera listę dostępnych modeli Claude
     */
    public static function get_available_models(): array {
        return [
            'claude-3-haiku-20240307' => 'Claude 3 Haiku',
            'claude-3-sonnet-20240229' => 'Claude 3 Sonnet',
            'claude-3-opus-20240229' => 'Claude 3 Opus'
        ];
    }

    /**
     * Pobiera listę obsługiwanych języków programowania
     */
    public static function get_supported_languages(): array {
        return [
            'php' => 'PHP',
            'javascript' => 'JavaScript',
            'typescript' => 'TypeScript',
            'python' => 'Python',
            'java' => 'Java',
            'csharp' => 'C#',
            'cpp' => 'C++',
            'ruby' => 'Ruby',
            'go' => 'Go',
            'rust' => 'Rust',
            'swift' => 'Swift',
            'kotlin' => 'Kotlin',
            'scala' => 'Scala',
            'haskell' => 'Haskell',
            'elixir' => 'Elixir',
            'erlang' => 'Erlang',
            'clojure' => 'Clojure',
            'lisp' => 'Lisp',
            'perl' => 'Perl',
            'r' => 'R',
            'matlab' => 'MATLAB',
            'sql' => 'SQL',
            'html' => 'HTML',
            'css' => 'CSS',
            'scss' => 'SCSS',
            'sass' => 'Sass',
            'less' => 'Less',
            'stylus' => 'Stylus',
            'xml' => 'XML',
            'yaml' => 'YAML',
            'json' => 'JSON',
            'markdown' => 'Markdown',
            'shell' => 'Shell',
            'powershell' => 'PowerShell',
            'batch' => 'Batch',
            'dockerfile' => 'Dockerfile',
            'makefile' => 'Makefile',
            'cmake' => 'CMake',
            'gradle' => 'Gradle',
            'maven' => 'Maven',
            'ant' => 'Ant',
            'groovy' => 'Groovy',
            'julia' => 'Julia',
            'dart' => 'Dart',
            'lua' => 'Lua',
            'pascal' => 'Pascal',
            'fortran' => 'Fortran',
            'cobol' => 'COBOL',
            'basic' => 'BASIC',
            'assembly' => 'Assembly',
            'verilog' => 'Verilog',
            'vhdl' => 'VHDL',
            'mathematica' => 'Mathematica',
            'wolfram' => 'Wolfram',
            'r' => 'R',
            'sas' => 'SAS',
            'stata' => 'Stata',
            'spss' => 'SPSS',
            'latex' => 'LaTeX',
            'tex' => 'TeX',
            'bibtex' => 'BibTeX',
            'asciidoc' => 'AsciiDoc',
            'restructuredtext' => 'reStructuredText',
            'textile' => 'Textile',
            'mediawiki' => 'MediaWiki',
            'wiki' => 'Wiki',
            'twig' => 'Twig',
            'jinja' => 'Jinja',
            'handlebars' => 'Handlebars',
            'ejs' => 'EJS',
            'pug' => 'Pug',
            'haml' => 'Haml',
            'slim' => 'Slim',
            'erb' => 'ERB',
            'liquid' => 'Liquid',
            'nunjucks' => 'Nunjucks',
            'mustache' => 'Mustache',
            'eex' => 'EEx',
            'heex' => 'HEEx',
            'leex' => 'LEEx',
            'slim' => 'Slim',
            'haml' => 'Haml',
            'jade' => 'Jade',
            'stylus' => 'Stylus',
            'less' => 'Less',
            'scss' => 'SCSS',
            'sass' => 'Sass',
            'stylus' => 'Stylus',
            'postcss' => 'PostCSS',
            'graphql' => 'GraphQL',
            'protobuf' => 'Protocol Buffers',
            'thrift' => 'Thrift',
            'avro' => 'Avro',
            'json-schema' => 'JSON Schema',
            'openapi' => 'OpenAPI',
            'swagger' => 'Swagger',
            'raml' => 'RAML',
            'blueprint' => 'API Blueprint',
            'wsdl' => 'WSDL',
            'xsd' => 'XSD',
            'dtd' => 'DTD',
            'relaxng' => 'RELAX NG',
            'schematron' => 'Schematron',
            'xslt' => 'XSLT',
            'xpath' => 'XPath',
            'xquery' => 'XQuery',
            'xproc' => 'XProc',
            'xforms' => 'XForms',
            'xhtml' => 'XHTML',
            'svg' => 'SVG',
            'mathml' => 'MathML',
            'smil' => 'SMIL',
            'rss' => 'RSS',
            'atom' => 'Atom',
            'rdf' => 'RDF',
            'owl' => 'OWL',
            'sparql' => 'SPARQL',
            'turtle' => 'Turtle',
            'n3' => 'N3',
            'ntriples' => 'N-Triples',
            'nquads' => 'N-Quads',
            'trig' => 'TriG',
            'jsonld' => 'JSON-LD',
            'microdata' => 'Microdata',
            'microformats' => 'Microformats',
            'opengraph' => 'Open Graph',
            'twitter-cards' => 'Twitter Cards',
            'schema.org' => 'Schema.org',
            'dublin-core' => 'Dublin Core',
            'foaf' => 'FOAF',
            'sioc' => 'SIOC',
            'skos' => 'SKOS',
            'dcat' => 'DCAT',
            'void' => 'VoID',
            'prov' => 'PROV',
            'bpmn' => 'BPMN',
            'dmn' => 'DMN',
            'cmmn' => 'CMMN',
            'epc' => 'EPC',
            'petri-net' => 'Petri Net',
            'uml' => 'UML',
            'sysml' => 'SysML',
            'archimate' => 'ArchiMate',
            'bpel' => 'BPEL',
            'wsdl' => 'WSDL',
            'soap' => 'SOAP',
            'rest' => 'REST',
            'graphql' => 'GraphQL',
            'grpc' => 'gRPC',
            'thrift' => 'Thrift',
            'avro' => 'Avro',
            'protobuf' => 'Protocol Buffers',
            'json-rpc' => 'JSON-RPC',
            'xml-rpc' => 'XML-RPC',
            'corba' => 'CORBA',
            'dcom' => 'DCOM',
            'rmi' => 'RMI',
            'jms' => 'JMS',
            'amqp' => 'AMQP',
            'mqtt' => 'MQTT',
            'stomp' => 'STOMP',
            'xmpp' => 'XMPP',
            'sip' => 'SIP',
            'rtp' => 'RTP',
            'rtcp' => 'RTCP',
            'sdp' => 'SDP',
            'h.323' => 'H.323',
            'mgcp' => 'MGCP',
            'megaco' => 'Megaco',
            'diameter' => 'Diameter',
            'radius' => 'RADIUS',
            'ldap' => 'LDAP',
            'kerberos' => 'Kerberos',
            'ntlm' => 'NTLM',
            'oauth' => 'OAuth',
            'openid' => 'OpenID',
            'saml' => 'SAML',
            'ws-federation' => 'WS-Federation',
            'ws-trust' => 'WS-Trust',
            'ws-security' => 'WS-Security',
            'ws-policy' => 'WS-Policy',
            'ws-addressing' => 'WS-Addressing',
            'ws-coordination' => 'WS-Coordination',
            'ws-transaction' => 'WS-Transaction',
            'ws-reliablemessaging' => 'WS-ReliableMessaging',
            'ws-discovery' => 'WS-Discovery',
            'ws-eventing' => 'WS-Eventing',
            'ws-management' => 'WS-Management',
            'ws-makeconnection' => 'WS-MakeConnection',
            'ws-metadataexchange' => 'WS-MetadataExchange',
            'ws-notification' => 'WS-Notification',
            'ws-resource' => 'WS-Resource',
            'ws-resourceproperties' => 'WS-ResourceProperties',
            'ws-resourcelifetime' => 'WS-ResourceLifetime',
            'ws-servicegroup' => 'WS-ServiceGroup',
            'ws-topics' => 'WS-Topics',
            'ws-transfer' => 'WS-Transfer'
        ];
    }

    /**
     * Pobiera wartość opcji
     */
    public static function get_option(string $key, mixed $default = null): mixed {
        $settings = get_option('aica_settings', []);
        return $settings[$key] ?? $default;
    }

    /**
     * Pobiera tłumaczenia dla repozytoriów
     */
    public static function get_repository_translations(): array {
        return [
            'add' => __('Dodaj repozytorium', 'ai-chat-assistant'),
            'edit' => __('Edytuj repozytorium', 'ai-chat-assistant'),
            'delete' => __('Usuń repozytorium', 'ai-chat-assistant'),
            'refresh' => __('Odśwież repozytorium', 'ai-chat-assistant'),
            'name' => __('Nazwa', 'ai-chat-assistant'),
            'url' => __('URL', 'ai-chat-assistant'),
            'type' => __('Typ', 'ai-chat-assistant'),
            'credentials' => __('Dane uwierzytelniające', 'ai-chat-assistant'),
            'created_at' => __('Data utworzenia', 'ai-chat-assistant'),
            'updated_at' => __('Data aktualizacji', 'ai-chat-assistant'),
            'actions' => __('Akcje', 'ai-chat-assistant'),
            'confirm_delete' => __('Czy na pewno chcesz usunąć to repozytorium?', 'ai-chat-assistant'),
            'confirm_refresh' => __('Czy na pewno chcesz odświeżyć to repozytorium?', 'ai-chat-assistant'),
            'success_add' => __('Repozytorium zostało dodane.', 'ai-chat-assistant'),
            'success_edit' => __('Repozytorium zostało zaktualizowane.', 'ai-chat-assistant'),
            'success_delete' => __('Repozytorium zostało usunięte.', 'ai-chat-assistant'),
            'success_refresh' => __('Repozytorium zostało odświeżone.', 'ai-chat-assistant'),
            'error_add' => __('Nie udało się dodać repozytorium.', 'ai-chat-assistant'),
            'error_edit' => __('Nie udało się zaktualizować repozytorium.', 'ai-chat-assistant'),
            'error_delete' => __('Nie udało się usunąć repozytorium.', 'ai-chat-assistant'),
            'error_refresh' => __('Nie udało się odświeżyć repozytorium.', 'ai-chat-assistant')
        ];
    }

    /**
     * Weryfikuje żądanie AJAX
     */
    public static function verify_ajax_request(): void {
        if (!wp_verify_nonce($_REQUEST['nonce'] ?? '', 'aica-ajax-nonce')) {
            wp_send_json_error([
                'message' => __('Nieprawidłowy token bezpieczeństwa.', 'ai-chat-assistant')
            ]);
        }

        if (!current_user_can('edit_posts')) {
            wp_send_json_error([
                'message' => __('Nie masz uprawnień do wykonania tej operacji.', 'ai-chat-assistant')
            ]);
        }
    }

    /**
     * Pobiera tłumaczenia dla historii
     */
    public static function get_history_translations(): array {
        return [
            'title' => __('Historia', 'ai-chat-assistant'),
            'no_history' => __('Brak historii.', 'ai-chat-assistant'),
            'load_more' => __('Załaduj więcej', 'ai-chat-assistant'),
            'delete' => __('Usuń', 'ai-chat-assistant'),
            'export' => __('Eksportuj', 'ai-chat-assistant'),
            'confirm_delete' => __('Czy na pewno chcesz usunąć tę historię?', 'ai-chat-assistant'),
            'success_delete' => __('Historia została usunięta.', 'ai-chat-assistant'),
            'error_delete' => __('Nie udało się usunąć historii.', 'ai-chat-assistant'),
            'success_export' => __('Historia została wyeksportowana.', 'ai-chat-assistant'),
            'error_export' => __('Nie udało się wyeksportować historii.', 'ai-chat-assistant')
        ];
    }

    /**
     * Weryfikuje wymagane parametry
     */
    public static function verify_required_params(array $params, array $required): void {
        $missing = array_diff($required, array_keys($params));
        if (!empty($missing)) {
            wp_send_json_error([
                'message' => sprintf(
                    __('Brakujące wymagane parametry: %s', 'ai-chat-assistant'),
                    implode(', ', $missing)
                )
            ]);
        }
    }

    /**
     * Formatuje konwersację jako tekst
     */
    public static function format_conversation_as_text(array $session, array $messages): string {
        $output = sprintf(
            "Konwersacja: %s\nData: %s\n\n",
            $session['title'],
            $session['created_at']
        );

        foreach ($messages as $message) {
            $output .= sprintf(
                "%s: %s\n\n",
                ucfirst($message['role']),
                $message['content']
            );
        }

        return $output;
    }
}

/**
 * Pobiera wartość opcji z własnej tabeli opcji wtyczki
 *
 * @param string $option_name Nazwa opcji
 * @param mixed $default Domyślna wartość, jeśli opcja nie istnieje
 * @return mixed Wartość opcji lub wartość domyślna
 */
function aica_get_option($option_name, $default = false) {
    global $wpdb;
    $table = aica_get_table_name('options');
    
    if (!aica_table_exists($table)) {
        return $default;
    }
    
    $value = $wpdb->get_var($wpdb->prepare(
        "SELECT option_value FROM $table WHERE option_name = %s",
        $option_name
    ));
    
    if ($value === null) {
        return $default;
    }
    
    return maybe_unserialize($value);
}

/**
 * Aktualizuje lub dodaje opcję w tabeli opcji wtyczki
 *
 * @param string $option_name Nazwa opcji
 * @param mixed $option_value Wartość opcji
 * @return bool Czy operacja się powiodła
 */
function aica_update_option($option_name, $option_value) {
    global $wpdb;
    $table = aica_get_table_name('options');
    $now = current_time('mysql');
    
    if (!aica_table_exists($table)) {
        return false;
    }
    
    // Sprawdź czy opcja już istnieje
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE option_name = %s",
        $option_name
    ));
    
    $value = maybe_serialize($option_value);
    
    if ($exists) {
        // Aktualizuj istniejącą opcję
        $result = $wpdb->update(
            $table,
            [
                'option_value' => $value,
                'updated_at' => $now
            ],
            ['option_name' => $option_name],
            ['%s', '%s'],
            ['%s']
        );
    } else {
        // Dodaj nową opcję
        $result = $wpdb->insert(
            $table,
            [
                'option_name' => $option_name,
                'option_value' => $value,
                'autoload' => 'yes',
                'created_at' => $now,
                'updated_at' => $now
            ],
            ['%s', '%s', '%s', '%s', '%s']
        );
    }
    
    return $result !== false;
}

/**
 * Usuwa opcję z tabeli opcji wtyczki
 *
 * @param string $option_name Nazwa opcji
 * @return bool Czy operacja się powiodła
 */
function aica_delete_option($option_name) {
    global $wpdb;
    $table = aica_get_table_name('options');
    
    if (!aica_table_exists($table)) {
        return false;
    }
    
    $result = $wpdb->delete(
        $table,
        ['option_name' => $option_name],
        ['%s']
    );
    
    return $result !== false;
}

/**
 * Pobiera ID użytkownika wtyczki na podstawie ID użytkownika WordPressa
 *
 * @param int $wp_user_id ID użytkownika WordPressa
 * @return int|false ID użytkownika wtyczki lub false jeśli nie znaleziono
 */
function aica_get_user_id($wp_user_id = null) {
    global $wpdb;
    $table = aica_get_table_name('users');
    
    // Jeśli nie podano ID, użyj aktualnie zalogowanego użytkownika
    if ($wp_user_id === null) {
        $wp_user_id = get_current_user_id();
    }
    
    // Jeśli ID użytkownika to 0, oznacza to, że użytkownik nie jest zalogowany
    if ($wp_user_id === 0) {
        return false;
    }
    
    if (!aica_table_exists($table)) {
        return false;
    }
    
    $user_id = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table WHERE wp_user_id = %d",
        $wp_user_id
    ));
    
    if (!$user_id) {
        // Jeśli użytkownik nie istnieje w naszej tabeli, dodaj go
        $user_info = get_userdata($wp_user_id);
        if ($user_info) {
            $user_data = [
                'username' => $user_info->user_login,
                'email' => $user_info->user_email,
                'role' => !empty($user_info->roles) ? aica_get_highest_role($user_info->roles) : 'subscriber'
            ];
            
            $validated_data = aica_validate_user_data($user_data);
            if (is_wp_error($validated_data)) {
                return false;
            }
            
            $user_id = aica_add_user(
                $wp_user_id,
                $validated_data['username'],
                $validated_data['email'],
                $validated_data['role'],
                current_time('mysql')
            );
        }
    }
    
    return $user_id ? (int) $user_id : false;
}

/**
 * Dodaje nowego użytkownika do tabeli użytkowników wtyczki
 *
 * @param int $wp_user_id ID użytkownika WordPressa
 * @param string $username Nazwa użytkownika
 * @param string $email Adres email
 * @param string $role Rola użytkownika
 * @param string $created_at Data utworzenia
 * @return int|false ID dodanego użytkownika lub false w przypadku błędu
 */
function aica_add_user($wp_user_id, $username, $email, $role, $created_at = null) {
    global $wpdb;
    $table = aica_get_table_name('users');
    
    if (!aica_table_exists($table)) {
        return false;
    }
    
    // Sprawdź czy użytkownik już istnieje
    $user_exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE wp_user_id = %d",
        $wp_user_id
    ));
    
    if ($user_exists) {
        // Zwróć istniejący ID użytkownika
        return $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE wp_user_id = %d",
            $wp_user_id
        ));
    }
    
    // Użyj aktualnej daty, jeśli nie podano
    if ($created_at === null) {
        $created_at = current_time('mysql');
    }
    
    // Waliduj dane użytkownika
    $user_data = [
        'username' => $username,
        'email' => $email,
        'role' => $role
    ];
    
    $validated_data = aica_validate_user_data($user_data);
    if (is_wp_error($validated_data)) {
        return false;
    }
    
    // Dodaj nowego użytkownika
    $result = $wpdb->insert(
        $table,
        [
            'wp_user_id' => $wp_user_id,
            'username' => $validated_data['username'],
            'email' => $validated_data['email'],
            'role' => $validated_data['role'],
            'created_at' => $created_at
        ],
        ['%d', '%s', '%s', '%s', '%s']
    );
    
    if ($result === false) {
        return false;
    }
    
    return $wpdb->insert_id;
}

/**
 * Określa najwyższą rolę z tablicy ról
 *
 * @param array $roles Tablica ról użytkownika
 * @return string Najwyższa rola
 */
function aica_get_highest_role($roles) {
    $role_priority = [
        'administrator' => 5,
        'editor' => 4,
        'author' => 3,
        'contributor' => 2,
        'subscriber' => 1
    ];
    
    $highest_role = 'subscriber';
    $highest_priority = 0;
    
    foreach ($roles as $role) {
        if (isset($role_priority[$role]) && $role_priority[$role] > $highest_priority) {
            $highest_role = $role;
            $highest_priority = $role_priority[$role];
        }
    }
    
    return $highest_role;
}

/**
 * Aktualizuje datę ostatniego logowania użytkownika
 *
 * @param int $user_id ID użytkownika
 * @return bool Czy operacja się powiodła
 */
function aica_update_user_last_login($user_id) {
    global $wpdb;
    $table = aica_get_table_name('users');
    
    if (!aica_table_exists($table)) {
        return false;
    }
    
    $result = $wpdb->update(
        $table,
        ['last_login' => current_time('mysql')],
        ['id' => $user_id],
        ['%s'],
        ['%d']
    );
    
    return $result !== false;
}

/**
 * Pobiera listę wszystkich użytkowników
 *
 * @return array Lista użytkowników
 */
function aica_get_users() {
    global $wpdb;
    $table = aica_get_table_name('users');
    
    if (!aica_table_exists($table)) {
        return [];
    }
    
    $users = $wpdb->get_results(
        "SELECT * FROM $table ORDER BY created_at DESC",
        ARRAY_A
    );
    
    return $users ?: [];
}

/**
 * Pobiera dane użytkownika
 *
 * @param int $user_id ID użytkownika
 * @return array|false Dane użytkownika lub false jeśli nie znaleziono
 */
function aica_get_user($user_id) {
    global $wpdb;
    $table = aica_get_table_name('users');
    
    if (!aica_table_exists($table)) {
        return false;
    }
    
    $user = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table WHERE id = %d",
        $user_id
    ), ARRAY_A);
    
    return $user ?: false;
}

/**
 * Usuwa użytkownika
 *
 * @param int $user_id ID użytkownika
 * @return bool Czy operacja się powiodła
 */
function aica_delete_user($user_id) {
    global $wpdb;
    $table = aica_get_table_name('users');
    
    if (!aica_table_exists($table)) {
        return false;
    }
    
    $result = $wpdb->delete(
        $table,
        ['id' => $user_id],
        ['%d']
    );
    
    return $result !== false;
}

/**
 * Loguje wiadomość do pliku logów
 *
 * @param string $message Wiadomość do zalogowania
 * @param string $level Poziom logu (info, warning, error)
 * @return bool Czy operacja się powiodła
 */
function aica_log($message, $level = 'info') {
    $log_dir = WP_CONTENT_DIR . '/aica-logs';
    
    // Utwórz katalog logów, jeśli nie istnieje
    if (!file_exists($log_dir)) {
        wp_mkdir_p($log_dir);
    }
    
    // Utwórz plik .htaccess, aby zabezpieczyć logi
    $htaccess_file = $log_dir . '/.htaccess';
    if (!file_exists($htaccess_file)) {
        file_put_contents($htaccess_file, 'Deny from all');
    }
    
    // Utwórz plik index.php, aby zabezpieczyć przed listowaniem katalogów
    $index_file = $log_dir . '/index.php';
    if (!file_exists($index_file)) {
        file_put_contents($index_file, '<?php // Silence is golden');
    }
    
    // Formatuj wiadomość
    $timestamp = current_time('mysql');
    $formatted_message = sprintf(
        "[%s] [%s] %s\n",
        $timestamp,
        strtoupper($level),
        $message
    );
    
    // Zapisz do pliku
    $log_file = $log_dir . '/aica-' . date('Y-m-d') . '.log';
    return file_put_contents($log_file, $formatted_message, FILE_APPEND) !== false;
}

/**
 * Migruje ustawienia do nowej wersji
 *
 * @param string $current_version Aktualna wersja
 * @return bool Czy migracja się powiodła
 */
function aica_migrate_settings($current_version) {
    // Migracja z wersji 1.0.0 do 1.1.0
    if (version_compare($current_version, '1.1.0', '<')) {
        // Przenieś stare ustawienia do nowej struktury
        $old_settings = get_option('aica_settings');
        if ($old_settings) {
            $new_settings = [
                'api_key' => $old_settings['api_key'] ?? '',
                'model' => $old_settings['model'] ?? 'gpt-3.5-turbo',
                'temperature' => $old_settings['temperature'] ?? 0.7,
                'max_tokens' => $old_settings['max_tokens'] ?? 2000
            ];
            
            // Zapisz nowe ustawienia
            $validated_settings = aica_validate_settings($new_settings);
            if (!is_wp_error($validated_settings)) {
                foreach ($validated_settings as $key => $value) {
                    aica_update_option($key, $value);
                }
            }
            
            // Usuń stare ustawienia
            delete_option('aica_settings');
        }
    }
    
    return true;
}