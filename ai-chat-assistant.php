<?php
declare(strict_types=1);

/**
 * Plugin Name: AI Chat Assistant
 * Plugin URI: https://github.com/yourusername/ai-chat-assistant
 * Description: Asystent AI do analizy kodu i repozytoriów
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * Text Domain: ai-chat-assistant
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 8.0
 */

// Zabezpieczenie przed bezpośrednim dostępem
if (!defined('ABSPATH')) {
    exit;
}

// Definicje stałych
define('AICA_VERSION', '1.0.0');
define('AICA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AICA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AICA_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Debugowanie ścieżek
error_log("AICA Plugin Dir: " . AICA_PLUGIN_DIR);
error_log("AICA Security Service Path: " . AICA_PLUGIN_DIR . 'includes/Services/SecurityService.php');
error_log("AICA Security Service Exists: " . (file_exists(AICA_PLUGIN_DIR . 'includes/Services/SecurityService.php') ? 'yes' : 'no'));

// Autoloader
spl_autoload_register(function (string $class): void {
    // Prefix namespace
    $prefix = 'AICA\\';
    $base_dir = AICA_PLUGIN_DIR . 'includes/';

    // Sprawdź czy klasa używa prefixu
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // Pobierz względną nazwę klasy
    $relative_class = substr($class, $len);

    // Zamień namespace na ścieżkę do pliku
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // Debugowanie
    error_log("Autoloader: Attempting to load class: " . $class);
    error_log("Autoloader: Looking for file: " . $file);
    error_log("Autoloader: File exists: " . (file_exists($file) ? 'yes' : 'no'));

    // Jeśli plik istnieje, załaduj go
    if (file_exists($file)) {
        require $file;
        error_log("Autoloader: File loaded successfully");
    } else {
        error_log("Autoloader: File not found");
    }
});

// Inicjalizacja wtyczki
function aica_init(): void {
    // Załaduj pliki tłumaczeń

    // Utwórz instancje serwisów w odpowiedniej kolejności
    $error_service = new \AICA\Services\ErrorService();
    $cache_service = new \AICA\Services\CacheService($error_service);
    $settings_service = new \AICA\Services\SettingsService($error_service, $cache_service);
    $page_manager = new \AICA\Admin\PageManager();
    $security_service = new \AICA\Services\SecurityService();
    $api_service = new \AICA\Services\ApiService($error_service, $settings_service);
    $repository_service = new \AICA\Services\RepositoryService();
    $session_service = new \AICA\Services\SessionService();
    $message_service = new \AICA\Services\MessageService();
    $cleanup_service = new \AICA\Services\CleanupService();
    $update_service = new \AICA\Services\UpdateService();

    // Inicjalizacja głównej klasy
    $main = new \AICA\Main(
        $page_manager,
        $security_service,
        $api_service,
        $repository_service,
        $session_service,
        $message_service,
        $cleanup_service,
        $update_service
    );
    $main->init();
}
add_action('plugins_loaded', 'aica_init');

// Aktywacja wtyczki
function aica_activate(): void {
    // Sprawdź wymagania
    if (version_compare(PHP_VERSION, '8.0', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            __('AI Chat Assistant wymaga PHP w wersji 8.0 lub nowszej.', 'ai-chat-assistant'),
            __('Błąd aktywacji', 'ai-chat-assistant'),
            ['back_link' => true]
        );
    }

    if (version_compare($GLOBALS['wp_version'], '5.8', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            __('AI Chat Assistant wymaga WordPress w wersji 5.8 lub nowszej.', 'ai-chat-assistant'),
            __('Błąd aktywacji', 'ai-chat-assistant'),
            ['back_link' => true]
        );
    }

    // Utwórz tabele w bazie danych

    if (version_compare($current_version, AICA_VERSION, '<')) {
        // Zbuduj zależności
        $error_service = new \AICA\Services\ErrorService();
        $cache_service = new \AICA\Services\CacheService($error_service);
        $settings_service = new \AICA\Services\SettingsService($error_service, $cache_service);
        $security_service = new \AICA\Services\SecurityService();
        $api_service = new \AICA\Services\ApiService($error_service, $settings_service);
        $repository_service = new \AICA\Services\RepositoryService();
        $session_service = new \AICA\Services\SessionService();
        $message_service = new \AICA\Services\MessageService();
        $cleanup_service = new \AICA\Services\CleanupService();
        $update_service = new \AICA\Services\UpdateService();

        $installer = new \AICA\Installer(
            $security_service,
            $api_service,
            $repository_service,
            $session_service,
            $message_service,
            $cleanup_service,
            $update_service
        );
        $installer->update($current_version);
    $installer->install();

    // Utwórz katalogi
    $upload_dir = wp_upload_dir();
    $plugin_upload_dir = $upload_dir['basedir'] . '/ai-chat-assistant';

    if (!file_exists($plugin_upload_dir)) {
        wp_mkdir_p($plugin_upload_dir);
    }

    // Utwórz plik .htaccess dla zabezpieczenia
    $htaccess_file = $plugin_upload_dir . '/.htaccess';
    if (!file_exists($htaccess_file)) {
        $htaccess_content = "Order deny,allow\nDeny from all";
        file_put_contents($htaccess_file, $htaccess_content);
    }

    // Utwórz plik index.php dla zabezpieczenia
    $index_file = $plugin_upload_dir . '/index.php';
    if (!file_exists($index_file)) {
        $index_content = "<?php\n// Silence is golden.";
        file_put_contents($index_file, $index_content);
    }

    // Zapisz wersję wtyczki
    update_option('aica_version', AICA_VERSION);

    // Wyczyść cache
    wp_cache_flush();
}
}
register_activation_hook(__FILE__, 'aica_activate');

// Deaktywacja wtyczki
function aica_deactivate(): void {
    // Wyczyść zaplanowane zadania
    wp_clear_scheduled_hook('aica_daily_cleanup');
    wp_clear_scheduled_hook('aica_check_updates');

    // Wyczyść cache
    wp_cache_flush();
}
register_deactivation_hook(__FILE__, 'aica_deactivate');

// Odinstalowanie wtyczki
function aica_uninstall(): void {
    // Usuń tabele z bazy danych
    $error_service = new \AICA\Services\ErrorService();
    $cache_service = new \AICA\Services\CacheService($error_service);
    $settings_service = new \AICA\Services\SettingsService($error_service, $cache_service);
    $security_service = new \AICA\Services\SecurityService();
    $api_service = new \AICA\Services\ApiService($error_service, $settings_service);
    $repository_service = new \AICA\Services\RepositoryService();
    $session_service = new \AICA\Services\SessionService();
    $message_service = new \AICA\Services\MessageService();
    $cleanup_service = new \AICA\Services\CleanupService();
    $update_service = new \AICA\Services\UpdateService();
    $installer = new \AICA\Installer(
        $security_service,
        $api_service,
        $repository_service,
        $session_service,
        $message_service,
        $cleanup_service,
        $update_service
    );
    $installer->uninstall();
    $installer->uninstall();

    // Usuń opcje
    delete_option('aica_version');
    delete_option('aica_settings');
    delete_option('aica_api_settings');

    // Usuń katalogi
    $upload_dir = wp_upload_dir();
    $plugin_upload_dir = $upload_dir['basedir'] . '/ai-chat-assistant';

    if (file_exists($plugin_upload_dir)) {
        // Usuń pliki
        array_map('unlink', glob("$plugin_upload_dir/*.*"));
        // Usuń katalog
        rmdir($plugin_upload_dir);
    }

    // Wyczyść cache
    wp_cache_flush();
}
register_uninstall_hook(__FILE__, 'aica_uninstall');

// Aktualizacja wtyczki
function aica_update(): void {
    $current_version = get_option('aica_version', '0.0.0');

    if (version_compare($current_version, AICA_VERSION, '<')) {
        // Wykonaj aktualizację
    $error_service = new \AICA\Services\ErrorService();
    $cache_service = new \AICA\Services\CacheService($error_service);
    $settings_service = new \AICA\Services\SettingsService($error_service, $cache_service);
    $security_service = new \AICA\Services\SecurityService();
    $api_service = new \AICA\Services\ApiService($error_service, $settings_service);
    $repository_service = new \AICA\Services\RepositoryService();
    $session_service = new \AICA\Services\SessionService();
    $message_service = new \AICA\Services\MessageService();
    $cleanup_service = new \AICA\Services\CleanupService();
    $update_service = new \AICA\Services\UpdateService();
    $installer = new \AICA\Installer(
        $security_service,
        $api_service,
        $repository_service,
        $session_service,
        $message_service,
        $cleanup_service,
        $update_service
    );
        $installer->update($current_version);

        // Zapisz nową wersję
        update_option('aica_version', AICA_VERSION);

        // Wyczyść cache
        wp_cache_flush();
    }
}
add_action('plugins_loaded', 'aica_update');

// Dodaj linki do ustawień
function aica_add_action_links(array $links): array {
    $settings_link = sprintf(
        '<a href="%s">%s</a>',
        admin_url('admin.php?page=aica-settings'),
        __('Ustawienia', 'ai-chat-assistant')
    );
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . AICA_PLUGIN_BASENAME, 'aica_add_action_links');

// Dodaj linki do dokumentacji
function aica_add_meta_links(array $links, string $file): array {
    if ($file === AICA_PLUGIN_BASENAME) {
        $links[] = sprintf(
            '<a href="%s" target="_blank">%s</a>',
            'https://github.com/yourusername/ai-chat-assistant/wiki',
            __('Dokumentacja', 'ai-chat-assistant')
        );
        $links[] = sprintf(
            '<a href="%s" target="_blank">%s</a>',
            'https://github.com/yourusername/ai-chat-assistant/issues',
            __('Wsparcie', 'ai-chat-assistant')
        );
    }
    return $links;
}
add_filter('plugin_row_meta', 'aica_add_meta_links', 10, 2);

// Dodaj menu w panelu administracyjnym
function aica_admin_menu(): void {
    add_menu_page(
        __('AI Chat Assistant', 'ai-chat-assistant'),
        __('AI Chat', 'ai-chat-assistant'),
        'manage_options',
        'aica-chat',
        function(): void {
            require_once AICA_PLUGIN_DIR . 'templates/chat.php';
        },
        'dashicons-format-chat',
        30
    );

    add_submenu_page(
        'aica-chat',
        __('Historia', 'ai-chat-assistant'),
        __('Historia', 'ai-chat-assistant'),
        'manage_options',
        'aica-history',
        function(): void {
            require_once AICA_PLUGIN_DIR . 'templates/history.php';
        }
    );

    add_submenu_page(
        'aica-chat',
        __('Repozytoria', 'ai-chat-assistant'),
        __('Repozytoria', 'ai-chat-assistant'),
        'manage_options',
        'aica-repositories',
        function(): void {
            require_once AICA_PLUGIN_DIR . 'templates/repositories.php';
        }
    );

    add_submenu_page(
        'aica-chat',
        __('Ustawienia', 'ai-chat-assistant'),
        __('Ustawienia', 'ai-chat-assistant'),
        'manage_options',
        'aica-settings',
        function(): void {
            require_once AICA_PLUGIN_DIR . 'templates/settings.php';
        }
    );

    add_submenu_page(
        'aica-chat',
        __('Diagnostyka', 'ai-chat-assistant'),
        __('Diagnostyka', 'ai-chat-assistant'),
        'manage_options',
        'aica-diagnostics',
        function(): void {
            require_once AICA_PLUGIN_DIR . 'templates/diagnostics.php';
        }
    );
}
add_action('admin_menu', 'aica_admin_menu');

// Załaduj style i skrypty
function aica_enqueue_scripts(): void {
    // Style
    wp_enqueue_style(
        'aica-admin',
        AICA_PLUGIN_URL . 'assets/css/admin.css',
        [],
        AICA_VERSION
    );

    // Skrypty
    wp_enqueue_script(
        'aica-admin',
        AICA_PLUGIN_URL . 'assets/js/admin.js',
        ['jquery'],
        AICA_VERSION,
        true
    );

    // Lokalizacja
    wp_localize_script('aica-admin', 'aicaAdmin', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('aica-admin-nonce'),
        'i18n' => [
            'confirmDelete' => __('Czy na pewno chcesz usunąć ten element?', 'ai-chat-assistant'),
            'confirmClear' => __('Czy na pewno chcesz wyczyścić historię?', 'ai-chat-assistant'),
            'error' => __('Wystąpił błąd. Spróbuj ponownie.', 'ai-chat-assistant'),
            'success' => __('Operacja zakończona sukcesem.', 'ai-chat-assistant')
        ]
    ]);
}
add_action('admin_enqueue_scripts', 'aica_enqueue_scripts');

// Dodaj widget

// Dodaj shortcode
    $atts = shortcode_atts([
        'title' => __('Chat AI', 'ai-chat-assistant'),
        'width' => '100%',
        'height' => '500px'
    ], $atts, 'aica_chat');

    ob_start();
    return ob_get_clean();
}
        wp_schedule_event(time(), 'twicedaily', 'aica_check_updates');
    }
}
add_action('wp', 'aica_schedule_events');

// Obsługa cron jobs
function aica_daily_cleanup(): void {
    $cleanup = new \AICA\Services\CleanupService();
    $cleanup->cleanup_old_data();
}
add_action('aica_daily_cleanup', 'aica_daily_cleanup');

function aica_check_updates(): void {
    $updater = new \AICA\Services\UpdateService();
    $updater->check_for_updates();
}
add_action('aica_check_updates', 'aica_check_updates');
// Załaduj tłumaczenia przy init
add_action('init', function () {
    load_plugin_textdomain('ai-chat-assistant', false, dirname(AICA_PLUGIN_BASENAME) . '/languages');
});
