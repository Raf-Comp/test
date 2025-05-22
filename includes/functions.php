<?php
declare(strict_types=1);

/**
 * Sprawdza, czy klucz API Claude jest skonfigurowany
 * 
 * @return bool True jeśli klucz API jest skonfigurowany
 */
function aica_is_api_configured(): bool {
    $api_settings = get_option('aica_api_settings', []);
    return !empty($api_settings['claude_api_key']);
}

/**
 * Pobiera aktualnie wybrany model Claude
 * 
 * @return string Nazwa modelu
 */
function aica_get_current_model(): string {
    return get_option('aica_model', 'claude-3-opus-20240229');
}

/**
 * Pobiera dostępne modele Claude
 * 
 * @return array<string> Lista dostępnych modeli
 */
function aica_get_available_models(): array {
    return [
        'claude-3-opus-20240229',
        'claude-3-sonnet-20240229',
        'claude-3-haiku-20240307'
    ];
}

/**
 * Pobiera wspierane języki programowania
 * 
 * @return array<string> Lista wspieranych języków
 */
function aica_get_supported_languages(): array {
    return [
        'javascript',
        'php',
        'python',
        'java',
        'csharp',
        'cpp',
        'ruby',
        'swift',
        'go',
        'rust',
        'typescript',
        'html',
        'css',
        'sql',
        'bash',
        'powershell',
        'yaml',
        'json',
        'xml',
        'markdown'
    ];
}

/**
 * Pobiera wartość opcji
 * 
 * @param string $key Klucz opcji
 * @param mixed $default Domyślna wartość
 * @return mixed Wartość opcji
 */
function aica_get_option(string $key, mixed $default = false): mixed {
    $settings = get_option('aica_settings', []);
    return $settings[$key] ?? $default;
}

/**
 * Pobiera tłumaczenia dla repozytoriów
 * 
 * @return array<string, string> Tłumaczenia
 */
function aica_get_repository_translations(): array {
    return [
        'select_type' => __('Wybierz typ repozytorium', 'ai-chat-assistant'),
        'enter_name' => __('Wprowadź nazwę repozytorium', 'ai-chat-assistant'),
        'enter_owner' => __('Wprowadź właściciela repozytorium', 'ai-chat-assistant'),
        'enter_url' => __('Wprowadź URL repozytorium', 'ai-chat-assistant'),
        'add_success' => __('Repozytorium zostało dodane', 'ai-chat-assistant'),
        'add_error' => __('Nie udało się dodać repozytorium', 'ai-chat-assistant'),
        'delete_success' => __('Repozytorium zostało usunięte', 'ai-chat-assistant'),
        'delete_error' => __('Nie udało się usunąć repozytorium', 'ai-chat-assistant'),
        'refresh_success' => __('Repozytorium zostało odświeżone', 'ai-chat-assistant'),
        'refresh_error' => __('Nie udało się odświeżyć repozytorium', 'ai-chat-assistant'),
        'invalid_repo_id' => __('Nieprawidłowy identyfikator repozytorium', 'ai-chat-assistant'),
        'repository_not_found' => __('Nie znaleziono repozytorium', 'ai-chat-assistant'),
        'file_not_found' => __('Nie znaleziono pliku', 'ai-chat-assistant'),
        'file_content_error' => __('Nie udało się pobrać zawartości pliku', 'ai-chat-assistant')
    ];
}

/**
 * Weryfikuje żądanie AJAX
 * 
 * @param string $nonce_name Nazwa nonce
 * @return bool True jeśli żądanie jest prawidłowe
 */
function aica_verify_ajax_request(string $nonce_name): bool {
    if (!check_ajax_referer($nonce_name, 'nonce', false)) {
        wp_send_json_error(['message' => __('Nieprawidłowy token bezpieczeństwa', 'ai-chat-assistant')]);
        return false;
    }
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Nie masz wystarczających uprawnień', 'ai-chat-assistant')]);
        return false;
    }
    
    return true;
}

/**
 * Pobiera tłumaczenia dla historii
 * 
 * @return array<string, mixed> Tłumaczenia
 */
function aica_get_history_translations(): array {
    return [
        'title' => __('Historia rozmów', 'ai-chat-assistant'),
        'no_history' => __('Brak historii rozmów', 'ai-chat-assistant'),
        'delete_confirm' => __('Czy na pewno chcesz usunąć tę rozmowę?', 'ai-chat-assistant'),
        'delete_success' => __('Rozmowa została usunięta', 'ai-chat-assistant'),
        'delete_error' => __('Nie udało się usunąć rozmowy', 'ai-chat-assistant'),
        'export_success' => __('Rozmowa została wyeksportowana', 'ai-chat-assistant'),
        'export_error' => __('Nie udało się wyeksportować rozmowy', 'ai-chat-assistant'),
        'clear_confirm' => __('Czy na pewno chcesz wyczyścić całą historię?', 'ai-chat-assistant'),
        'clear_success' => __('Historia została wyczyszczona', 'ai-chat-assistant'),
        'clear_error' => __('Nie udało się wyczyścić historii', 'ai-chat-assistant'),
        'search_placeholder' => __('Szukaj w historii...', 'ai-chat-assistant'),
        'date' => __('Data', 'ai-chat-assistant'),
        'title' => __('Tytuł', 'ai-chat-assistant'),
        'messages' => __('Wiadomości', 'ai-chat-assistant'),
        'actions' => __('Akcje', 'ai-chat-assistant'),
        'view' => __('Zobacz', 'ai-chat-assistant'),
        'delete' => __('Usuń', 'ai-chat-assistant'),
        'export' => __('Eksportuj', 'ai-chat-assistant'),
        'clear_all' => __('Wyczyść wszystko', 'ai-chat-assistant'),
        'no_results' => __('Nie znaleziono rozmów', 'ai-chat-assistant'),
        'loading' => __('Ładowanie...', 'ai-chat-assistant'),
        'error' => __('Wystąpił błąd', 'ai-chat-assistant'),
        'retry' => __('Spróbuj ponownie', 'ai-chat-assistant'),
        'cancel' => __('Anuluj', 'ai-chat-assistant'),
        'confirm' => __('Potwierdź', 'ai-chat-assistant'),
        'close' => __('Zamknij', 'ai-chat-assistant'),
        'export_formats' => [
            'txt' => __('Plik tekstowy', 'ai-chat-assistant'),
            'json' => __('JSON', 'ai-chat-assistant'),
            'html' => __('HTML', 'ai-chat-assistant'),
            'pdf' => __('PDF', 'ai-chat-assistant')
        ]
    ];
}

/**
 * Weryfikuje wymagane parametry w żądaniu AJAX
 * 
 * @param array<string> $required_params Lista wymaganych parametrów
 * @return bool True jeśli wszystkie parametry są obecne
 */
function aica_verify_required_params(array $required_params): bool {
    foreach ($required_params as $param) {
        if (!isset($_POST[$param]) || empty($_POST[$param])) {
            wp_send_json_error([
                'message' => sprintf(
                    __('Brak wymaganego parametru: %s', 'ai-chat-assistant'),
                    $param
                )
            ]);
            return false;
        }
    }
    return true;
}

/**
 * Formatuje konwersację jako tekst
 * 
 * @param object $session Obiekt sesji
 * @param array<object> $messages Lista wiadomości
 * @return string Sformatowany tekst konwersacji
 */
function aica_format_conversation_as_text(object $session, array $messages): string {
    $output = sprintf(
        "Konwersacja: %s\nData: %s\n\n",
        $session->title,
        date('Y-m-d H:i:s', strtotime($session->created_at))
    );
    
    foreach ($messages as $message) {
        $role = $message->type === 'user' ? 'Użytkownik' : 'Asystent';
        $time = date('H:i:s', strtotime($message->time));
        $output .= sprintf("[%s] %s:\n%s\n\n", $time, $role, $message->content);
    }
    
    return $output;
}

/**
 * Sprawdza połączenie z API Claude
 * 
 * @param string $api_key Klucz API Claude
 * @return array{valid: bool, message?: string, details?: array} Status połączenia
 */
function aica_check_claude_api(string $api_key): array {
    if (empty($api_key)) {
        return ['valid' => false, 'message' => __('Klucz API Claude nie jest skonfigurowany.', 'ai-chat-assistant')];
    }

    try {
        $api_service = new \AICA\Services\ApiService();
        $test_result = $api_service->test_connection();

        if (!$test_result) {
            return ['valid' => false, 'message' => __('Połączenie z API Claude nie działa.', 'ai-chat-assistant')];
        }

        $models = $api_service->get_available_models();
        $current_model = aica_get_option('claude_model', 'claude-3-haiku-20240307');

        return [
            'valid' => true,
            'details' => [
                'current_model' => $current_model,
                'model_available' => in_array($current_model, array_keys($models)),
                'models' => array_keys($models)
            ]
        ];
    } catch (\Throwable $e) {
        return ['valid' => false, 'message' => __('Błąd podczas sprawdzania API Claude: ', 'ai-chat-assistant') . $e->getMessage()];
    }
}

/**
 * Sprawdza połączenie z API GitHub
 * 
 * @param string $token Token GitHub
 * @return array{valid: bool, message: string} Status połączenia
 */
function aica_check_github_api(string $token): array {
    if (empty($token)) {
        return ['valid' => false, 'message' => __('Token GitHub nie jest skonfigurowany.', 'ai-chat-assistant')];
    }

    try {
        $response = wp_remote_get('https://api.github.com/user', [
            'headers' => [
                'Authorization' => 'token ' . $token,
                'Accept' => 'application/vnd.github.v3+json'
            ],
            'timeout' => 10
        ]);

        if (is_wp_error($response)) {
            throw new \Exception($response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        return [
            'valid' => $code === 200,
            'message' => $code === 200 ? __('Połączenie z GitHub API działa.', 'ai-chat-assistant') : __('Błąd połączenia z GitHub API (kod: ', 'ai-chat-assistant') . $code . ')'
        ];
    } catch (\Throwable $e) {
        return ['valid' => false, 'message' => __('Błąd podczas sprawdzania API GitHub: ', 'ai-chat-assistant') . $e->getMessage()];
    }
}

/**
 * Sprawdza połączenie z API GitLab
 * 
 * @param string $token Token GitLab
 * @return array{valid: bool, message: string} Status połączenia
 */
function aica_check_gitlab_api(string $token): array {
    if (empty($token)) {
        return ['valid' => false, 'message' => __('Token GitLab nie jest skonfigurowany.', 'ai-chat-assistant')];
    }

    try {
        $response = wp_remote_get('https://gitlab.com/api/v4/user', [
            'headers' => ['PRIVATE-TOKEN' => $token],
            'timeout' => 10
        ]);

        if (is_wp_error($response)) {
            throw new \Exception($response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        return [
            'valid' => $code === 200,
            'message' => $code === 200 ? __('Połączenie z GitLab API działa.', 'ai-chat-assistant') : __('Błąd połączenia z GitLab API (kod: ', 'ai-chat-assistant') . $code . ')'
        ];
    } catch (\Throwable $e) {
        return ['valid' => false, 'message' => __('Błąd podczas sprawdzania API GitLab: ', 'ai-chat-assistant') . $e->getMessage()];
    }
}

/**
 * Sprawdza połączenie z API Bitbucket
 * 
 * @param array{username: string, app_password: string} $credentials Dane dostępowe Bitbucket
 * @return array{valid: bool, message: string} Status połączenia
 */
function aica_check_bitbucket_api(array $credentials): array {
    if (empty($credentials['username']) || empty($credentials['app_password'])) {
        return ['valid' => false, 'message' => __('Dane dostępowe Bitbucket nie są skonfigurowane.', 'ai-chat-assistant')];
    }

    try {
        $auth = base64_encode($credentials['username'] . ':' . $credentials['app_password']);
        $response = wp_remote_get('https://api.bitbucket.org/2.0/user', [
            'headers' => ['Authorization' => 'Basic ' . $auth],
            'timeout' => 10
        ]);

        if (is_wp_error($response)) {
            throw new \Exception($response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        return [
            'valid' => $code === 200,
            'message' => $code === 200 ? __('Połączenie z Bitbucket API działa.', 'ai-chat-assistant') : __('Błąd połączenia z Bitbucket API (kod: ', 'ai-chat-assistant') . $code . ')'
        ];
    } catch (\Throwable $e) {
        return ['valid' => false, 'message' => __('Błąd podczas sprawdzania API Bitbucket: ', 'ai-chat-assistant') . $e->getMessage()];
    }
}

/**
 * Pobiera status bazy danych
 * 
 * @return array<string, array{name: string, exists: bool, records: int}> Status bazy danych
 */
function aica_get_database_status(): array {
    global $wpdb;
    $tables = $wpdb->get_col("SHOW TABLES");
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
        $exists = in_array($table_name, $tables, true);
        $records = 0;
        
        if ($exists) {
            $records = (int) $wpdb->get_var("SELECT COUNT(*) FROM `$table_name`");
        }
        
        $status[$table_name] = [
            'name' => $description,
            'exists' => $exists,
            'records' => $records
        ];
    }

    return $status;
}

/**
 * Pobiera uprawnienia plików
 * 
 * @return array<string, array{path: string, exists: bool, readable: bool, writable: bool, permissions: string}> Status uprawnień plików
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
        $readable = $exists && is_readable($file);
        $writable = $exists && is_writable($file);
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
 * Pobiera rekomendacje diagnostyczne
 * 
 * @param array<string, array{name: string, exists: bool, records: int}> $db_status Status bazy danych
 * @param array<string, array{path: string, exists: bool, readable: bool, writable: bool, permissions: string}> $files_status Status plików
 * @param array{valid: bool, message?: string, details?: array} $claude_api Status API Claude
 * @param array{valid: bool, message: string} $github_api Status API GitHub
 * @param array{valid: bool, message: string} $gitlab_api Status API GitLab
 * @param array{valid: bool, message: string} $bitbucket_api Status API Bitbucket
 * @return array<string> Lista rekomendacji
 */
function aica_get_diagnostics_recommendations(
    array $db_status,
    array $files_status,
    array $claude_api,
    array $github_api,
    array $gitlab_api,
    array $bitbucket_api
): array {
    $recommendations = [];

    // Sprawdzenie bazy danych
    foreach ($db_status as $table => $status) {
        if (!$status['exists']) {
            $recommendations[] = sprintf(
                __('Tabela %s nie istnieje. Uruchom instalator wtyczki.', 'ai-chat-assistant'),
                $status['name']
            );
        }
    }

    // Sprawdzenie plików
    foreach ($files_status as $name => $status) {
        if (!$status['exists']) {
            $recommendations[] = sprintf(
                __('Plik %s nie istnieje. Zainstaluj wtyczkę ponownie.', 'ai-chat-assistant'),
                $name
            );
        } elseif (!$status['readable']) {
            $recommendations[] = sprintf(
                __('Plik %s nie jest czytelny. Sprawdź uprawnienia.', 'ai-chat-assistant'),
                $name
            );
        }
    }

    // Sprawdzenie API Claude
    if (!$claude_api['valid']) {
        $recommendations[] = $claude_api['message'] ?? __('Nieznany błąd API Claude', 'ai-chat-assistant');
    }

    // Sprawdzenie API GitHub
    if (!$github_api['valid']) {
        $recommendations[] = $github_api['message'];
    }

    // Sprawdzenie API GitLab
    if (!$gitlab_api['valid']) {
        $recommendations[] = $gitlab_api['message'];
    }

    // Sprawdzenie API Bitbucket
    if (!$bitbucket_api['valid']) {
        $recommendations[] = $bitbucket_api['message'];
    }

    return $recommendations;
} 