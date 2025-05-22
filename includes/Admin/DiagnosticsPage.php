<?php
namespace AICA\Admin;

use function aica_check_claude_api;
use function aica_check_github_api;
use function aica_check_gitlab_api;
use function aica_check_bitbucket_api;
use function aica_get_database_status;
use function aica_get_files_permissions;
use function aica_get_diagnostics_recommendations;
use function aica_get_option;

class DiagnosticsPage {
    public function render() {
        // Sprawdzenie uprawnień
        if (!current_user_can('manage_options')) {
            wp_die(__('Nie masz wystarczających uprawnień do dostępu do tej strony.', 'ai-chat-assistant'));
        }
        
        // Pobieranie danych diagnostycznych
        $claude_api_status = \aica_check_claude_api(aica_get_option('claude_api_key', ''));
        $github_api_status = \aica_check_github_api(aica_get_option('github_token', ''));
        $gitlab_api_status = \aica_check_gitlab_api(aica_get_option('gitlab_token', ''));
        $bitbucket_api_status = \aica_check_bitbucket_api([
            'username' => aica_get_option('bitbucket_username', ''),
            'app_password' => aica_get_option('bitbucket_app_password', '')
        ]);
        $database_status = \aica_get_database_status();
        $files_permissions = \aica_get_files_permissions();
        $recommendations = \aica_get_diagnostics_recommendations(
            $database_status,
            $files_permissions,
            $claude_api_status,
            $github_api_status,
            $gitlab_api_status,
            $bitbucket_api_status
        );

        // Ładowanie szablonu
        include AICA_PLUGIN_DIR . 'templates/admin/diagnostics.php';
    }

    private function check_claude_api() {
        $api_key = aica_get_option('claude_api_key', '');
        if (empty($api_key)) {
            return ['valid' => false, 'message' => __('Klucz API Claude nie jest skonfigurowany.', 'ai-chat-assistant')];
        }

        // Sprawdzenie klasy
        if (!class_exists('\AICA\API\ClaudeClient')) {
            return ['valid' => false, 'message' => __('Klasa ClaudeClient nie istnieje.', 'ai-chat-assistant')];
        }

        try {
            $claude_client = new \AICA\API\ClaudeClient($api_key);
            $test_result = $claude_client->test_connection();

            if (!$test_result) {
                return ['valid' => false, 'message' => __('Połączenie z API Claude nie działa.', 'ai-chat-assistant')];
            }

            $models = $claude_client->get_available_models();
            $current_model = aica_get_option('claude_model', 'claude-3-haiku-20240307');

            return [
                'valid' => true,
                'details' => [
                    'current_model' => $current_model,
                    'model_available' => in_array($current_model, array_keys($models)),
                    'models' => array_keys($models)
                ]
            ];
        } catch (\Exception $e) {
            return ['valid' => false, 'message' => __('Błąd podczas sprawdzania API Claude: ', 'ai-chat-assistant') . $e->getMessage()];
        }
    }

    private function check_github_api() {
        $token = aica_get_option('github_token', '');
        if (empty($token)) {
            return ['valid' => false, 'message' => __('Token GitHub nie jest skonfigurowany.', 'ai-chat-assistant')];
        }

        // Sprawdzenie klasy
        // Sprawdzenie klasy
if (!class_exists('\AICA\API\GitHubClient')) {
    // Próba załadowania pliku
    $git_client_path = AICA_PLUGIN_DIR . 'includes/API/GitHubClient.php';
    if (file_exists($git_client_path)) {
        require_once $git_client_path;
    } else {
        return ['valid' => false, 'message' => __('Klasa GitHubClient nie istnieje.', 'ai-chat-assistant')];
    }
}

        try {
            $client = new \AICA\API\GitHubClient($token);
            $result = $client->test_connection();

            return $result ? ['valid' => true] : ['valid' => false, 'message' => __('Połączenie z API GitHub nie działa.', 'ai-chat-assistant')];
        } catch (\Exception $e) {
            return ['valid' => false, 'message' => __('Błąd podczas sprawdzania API GitHub: ', 'ai-chat-assistant') . $e->getMessage()];
        }
    }

    private function check_gitlab_api() {
        $token = aica_get_option('gitlab_token', '');
        if (empty($token)) {
            return ['valid' => false, 'message' => __('Token GitLab nie jest skonfigurowany.', 'ai-chat-assistant')];
        }

        try {
            $response = wp_remote_get('https://gitlab.com/api/v4/user', [
                'headers' => ['PRIVATE-TOKEN' => $token],
                'timeout' => 10
            ]);

            $code = wp_remote_retrieve_response_code($response);
            return [
                'valid' => $code === 200,
                'message' => $code === 200 ? __('Połączenie z GitLab API działa.', 'ai-chat-assistant') : __('Błąd połączenia z GitLab API (kod: ', 'ai-chat-assistant') . $code . ')'
            ];
        } catch (\Exception $e) {
            return ['valid' => false, 'message' => __('Błąd podczas sprawdzania API GitLab: ', 'ai-chat-assistant') . $e->getMessage()];
        }
    }

    private function check_bitbucket_api() {
        $username = aica_get_option('bitbucket_username', '');
        $app_password = aica_get_option('bitbucket_app_password', '');
        
        if (empty($username) || empty($app_password)) {
            return ['valid' => false, 'message' => __('Dane dostępowe Bitbucket nie są skonfigurowane.', 'ai-chat-assistant')];
        }

        try {
            $auth = base64_encode($username . ':' . $app_password);
            $response = wp_remote_get('https://api.bitbucket.org/2.0/user', [
                'headers' => ['Authorization' => 'Basic ' . $auth],
                'timeout' => 10
            ]);

            $code = wp_remote_retrieve_response_code($response);
            return [
                'valid' => $code === 200,
                'message' => $code === 200 ? __('Połączenie z Bitbucket API działa.', 'ai-chat-assistant') : __('Błąd połączenia z Bitbucket API (kod: ', 'ai-chat-assistant') . $code . ')'
            ];
        } catch (\Exception $e) {
            return ['valid' => false, 'message' => __('Błąd podczas sprawdzania API Bitbucket: ', 'ai-chat-assistant') . $e->getMessage()];
        }
    }

    private function get_database_status() {
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

    private function get_files_permissions() {
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

    private function get_recommendations($db_status, $files_status, $claude_api, $github_api, $gitlab_api, $bitbucket_api) {
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
}