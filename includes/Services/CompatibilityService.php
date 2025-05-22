<?php
namespace AICA\Services;

class CompatibilityService {
    private $error_service;
    private $settings_service;
    private $min_php_version = '7.4.0';
    private $min_wp_version = '5.6.0';
    private $required_extensions = ['curl', 'json', 'mbstring'];
    private $migration_versions = [
        '1.0.0' => 'initial',
        '1.1.0' => 'addInterfaceSettings',
        '1.2.0' => 'addApiSettings'
    ];

    public function __construct() {
        $this->error_service = ErrorService::getInstance();
        $this->settings_service = SettingsService::getInstance();
    }

    /**
     * Sprawdza wymagania systemowe
     */
    public function checkSystemRequirements() {
        try {
            $requirements = [
                'php_version' => $this->checkPhpVersion(),
                'wp_version' => $this->checkWpVersion(),
                'extensions' => $this->checkExtensions(),
                'permissions' => $this->checkPermissions()
            ];

            return [
                'success' => !in_array(false, $requirements),
                'requirements' => $requirements
            ];
        } catch (\Throwable $e) {
            $this->error_service->handleException($e, ['action' => 'check_system_requirements']);
            return [
                'success' => false,
                'requirements' => []
            ];
        }
    }

    /**
     * Sprawdza wersję PHP
     */
    private function checkPhpVersion() {
        $current_version = PHP_VERSION;
        $is_compatible = version_compare($current_version, $this->min_php_version, '>=');
        
        if (!$is_compatible) {
            $this->error_service->logError(
                sprintf('Niekompatybilna wersja PHP: %s (wymagana: %s)', $current_version, $this->min_php_version),
                ['type' => 'compatibility'],
                'warning'
            );
        }
        
        return $is_compatible;
    }

    /**
     * Sprawdza wersję WordPress
     */
    private function checkWpVersion() {
        global $wp_version;
        $is_compatible = version_compare($wp_version, $this->min_wp_version, '>=');
        
        if (!$is_compatible) {
            $this->error_service->logError(
                sprintf('Niekompatybilna wersja WordPress: %s (wymagana: %s)', $wp_version, $this->min_wp_version),
                ['type' => 'compatibility'],
                'warning'
            );
        }
        
        return $is_compatible;
    }

    /**
     * Sprawdza wymagane rozszerzenia PHP
     */
    private function checkExtensions() {
        $missing_extensions = [];
        
        foreach ($this->required_extensions as $extension) {
            if (!extension_loaded($extension)) {
                $missing_extensions[] = $extension;
            }
        }
        
        if (!empty($missing_extensions)) {
            $this->error_service->logError(
                'Brakujące rozszerzenia PHP: ' . implode(', ', $missing_extensions),
                ['type' => 'compatibility', 'extensions' => $missing_extensions],
                'warning'
            );
        }
        
        return empty($missing_extensions);
    }

    /**
     * Sprawdza uprawnienia do katalogów
     */
    private function checkPermissions() {
        $directories = [
            WP_CONTENT_DIR . '/aica-logs',
            WP_CONTENT_DIR . '/uploads/aica'
        ];
        
        $permission_issues = [];
        
        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                wp_mkdir_p($dir);
            }
            
            if (!is_writable($dir)) {
                $permission_issues[] = $dir;
            }
        }
        
        if (!empty($permission_issues)) {
            $this->error_service->logError(
                'Brak uprawnień do zapisu w katalogach: ' . implode(', ', $permission_issues),
                ['type' => 'compatibility', 'directories' => $permission_issues],
                'warning'
            );
        }
        
        return empty($permission_issues);
    }

    /**
     * Wykonuje migrację ustawień
     */
    public function migrateSettings($current_version) {
        try {
            $migrations_to_run = $this->getMigrationsToRun($current_version);
            
            if (empty($migrations_to_run)) {
                return true;
            }

            foreach ($migrations_to_run as $version => $migration) {
                $method = 'migrate' . ucfirst($migration);
                if (method_exists($this, $method)) {
                    if (!$this->$method()) {
                        throw new \Exception("Błąd migracji do wersji {$version}");
                    }
                }
            }

            return true;
        } catch (\Throwable $e) {
            $this->error_service->handleException($e, [
                'action' => 'migrate_settings',
                'current_version' => $current_version
            ]);
            return false;
        }
    }

    /**
     * Pobiera listę migracji do wykonania
     */
    private function getMigrationsToRun($current_version) {
        $migrations = [];
        
        foreach ($this->migration_versions as $version => $migration) {
            if (version_compare($current_version, $version, '<')) {
                $migrations[$version] = $migration;
            }
        }
        
        return $migrations;
    }

    /**
     * Migracja początkowa
     */
    private function migrateInitial() {
        try {
            $default_settings = [
                'api' => [
                    'key' => '',
                    'model' => 'claude-3-opus-20240229',
                    'max_tokens' => 4000,
                    'temperature' => 0.7
                ],
                'interface' => [
                    'dark_mode' => false,
                    'compact_view' => false
                ]
            ];

            return $this->settings_service->saveSettings($default_settings);
        } catch (\Throwable $e) {
            $this->error_service->handleException($e, ['action' => 'migrate_initial']);
            return false;
        }
    }

    /**
     * Migracja ustawień interfejsu
     */
    private function migrateAddInterfaceSettings() {
        try {
            $settings = $this->settings_service->getSettings();
            
            if (!isset($settings['interface'])) {
                $settings['interface'] = [
                    'dark_mode' => false,
                    'compact_view' => false
                ];
                
                return $this->settings_service->saveSettings($settings);
            }
            
            return true;
        } catch (\Throwable $e) {
            $this->error_service->handleException($e, ['action' => 'migrate_add_interface_settings']);
            return false;
        }
    }

    /**
     * Migracja ustawień API
     */
    private function migrateAddApiSettings() {
        try {
            $settings = $this->settings_service->getSettings();
            
            if (!isset($settings['api'])) {
                $settings['api'] = [
                    'key' => '',
                    'model' => 'claude-3-opus-20240229',
                    'max_tokens' => 4000,
                    'temperature' => 0.7
                ];
                
                return $this->settings_service->saveSettings($settings);
            }
            
            return true;
        } catch (\Throwable $e) {
            $this->error_service->handleException($e, ['action' => 'migrate_add_api_settings']);
            return false;
        }
    }

    /**
     * Pobiera informacje o kompatybilności
     */
    public function getCompatibilityInfo() {
        $requirements = $this->checkSystemRequirements();
        
        return [
            'php_version' => [
                'current' => PHP_VERSION,
                'required' => $this->min_php_version,
                'status' => $requirements['requirements']['php_version']
            ],
            'wp_version' => [
                'current' => get_bloginfo('version'),
                'required' => $this->min_wp_version,
                'status' => $requirements['requirements']['wp_version']
            ],
            'extensions' => [
                'required' => $this->required_extensions,
                'status' => $requirements['requirements']['extensions']
            ],
            'permissions' => [
                'status' => $requirements['requirements']['permissions']
            ]
        ];
    }

    /**
     * Migruje historię czatu
     */
    public function migrate_history() {
        global $wpdb;

        // Sprawdź czy tabele istnieją
        if (!$this->tables_exist()) {
            return;
        }

        // Rozpocznij transakcję
        $wpdb->query('START TRANSACTION');

        try {
            // Pobierz wszystkie sesje
            $sessions = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}aica_sessions");

            foreach ($sessions as $session) {
                // Pobierz wiadomości dla sesji
                $messages = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}aica_messages WHERE session_id = %s",
                    $session->session_id
                ));

                foreach ($messages as $message) {
                    // Aktualizuj format wiadomości
                    $wpdb->update(
                        $wpdb->prefix . 'aica_messages',
                        [
                            'role' => 'user',
                            'content' => $message->message
                        ],
                        ['id' => $message->id],
                        ['%s', '%s'],
                        ['%d']
                    );

                    // Dodaj odpowiedź asystenta
                    if (!empty($message->response)) {
                        $wpdb->insert(
                            $wpdb->prefix . 'aica_messages',
                            [
                                'session_id' => $session->id,
                                'role' => 'assistant',
                                'content' => $message->response,
                                'created_at' => $message->created_at
                            ],
                            ['%d', '%s', '%s', '%s']
                        );
                    }
                }
            }
            
            // Zatwierdź transakcję
            $wpdb->query('COMMIT');

        } catch (\Exception $e) {
            // Wycofaj transakcję w przypadku błędu
            $wpdb->query('ROLLBACK');
            throw $e;
        }
    }

    /**
     * Sprawdza czy tabele istnieją
     */
    private function tables_exist() {
        global $wpdb;
        $sessions_table = $wpdb->prefix . 'aica_sessions';
        $messages_table = $wpdb->prefix . 'aica_messages';

        return $wpdb->get_var("SHOW TABLES LIKE '$sessions_table'") === $sessions_table
            && $wpdb->get_var("SHOW TABLES LIKE '$messages_table'") === $messages_table;
    }
} 