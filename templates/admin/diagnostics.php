<?php
/**
 * Szablon strony diagnostyki
 *
 * @package AI_Chat_Assistant
 */

if (!defined('ABSPATH')) {
    exit; // Bezpośredni dostęp zabroniony
}

// Preload krytycznych zasobów
add_action('wp_head', function() {
    $diagnostics_css = AICA_PLUGIN_URL . 'assets/css/diagnostics.css';
    $diagnostics_js = AICA_PLUGIN_URL . 'assets/js/diagnostics.js';
    
    printf('<link rel="preload" href="%s" as="style">', esc_url($diagnostics_css));
    printf('<link rel="preload" href="%s" as="script">', esc_url($diagnostics_js));
});

// Ważne: Dodanie skryptu i danych nonce dla JavaScript
$nonce = wp_create_nonce('aica_diagnostics_nonce');

// Załaduj style z wersjonowaniem
$css_version = filemtime(AICA_PLUGIN_DIR . 'assets/css/diagnostics.css');
wp_enqueue_style(
    'aica-diagnostics-css',
    AICA_PLUGIN_URL . 'assets/css/diagnostics.css',
    [],
    $css_version ?: AICA_VERSION
);

// Załaduj skrypt z wersjonowaniem i defer
$js_version = filemtime(AICA_PLUGIN_DIR . 'assets/js/diagnostics.js');
wp_enqueue_script(
    'aica-diagnostics-script',
    AICA_PLUGIN_URL . 'assets/js/diagnostics.js',
    ['jquery'],
    $js_version ?: AICA_VERSION,
    true
);

// Dodaj atrybut defer do skryptu
add_filter('script_loader_tag', function($tag, $handle) {
    if ($handle === 'aica-diagnostics-script') {
        return str_replace(' src', ' defer src', $tag);
    }
    return $tag;
}, 10, 2);

// Przekaż dane do skryptu
wp_localize_script('aica-diagnostics-script', 'aica_diagnostics_data', [
    'nonce' => $nonce,
    'ajax_url' => admin_url('admin-ajax.php'),
    'chat_url' => admin_url('admin.php?page=ai-chat-assistant'),
    'i18n' => [
        'success' => __('Sukces', 'ai-chat-assistant'),
        'error' => __('Błąd', 'ai-chat-assistant'),
        'warning' => __('Ostrzeżenie', 'ai-chat-assistant'),
        'info' => __('Informacja', 'ai-chat-assistant')
    ]
]);
?>

<div class="wrap aica-diagnostics-container">
    <div class="aica-diagnostics-header">
        <div class="aica-header-title">
            <h1><?php _e('Diagnostyka AI Chat Assistant', 'ai-chat-assistant'); ?></h1>
            <p class="aica-header-description"><?php _e('Narzędzie do monitorowania i rozwiązywania problemów z wtyczką', 'ai-chat-assistant'); ?></p>
        </div>
        <div class="aica-header-actions">
            <button id="refresh-all-diagnostics" class="button button-primary aica-button-with-icon">
                <span class="dashicons dashicons-update"></span> <?php _e('Odśwież wszystko', 'ai-chat-assistant'); ?>
            </button>
        </div>
    </div>
    
    <?php if (!empty($recommendations)): ?>
    <div class="aica-recommendations-panel">
        <div class="aica-recommendations-header">
            <span class="aica-recommendations-icon"><span class="dashicons dashicons-lightbulb"></span></span>
            <h2><?php _e('Zalecane działania', 'ai-chat-assistant'); ?></h2>
        </div>
        <div class="aica-recommendations-content">
            <ul>
                <?php foreach ($recommendations as $recommendation): ?>
                    <li class="aica-recommendation-item">
                        <span class="aica-recommendation-bullet"></span>
                        <?php echo esc_html($recommendation); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    <div class="aica-dashboard-grid">
        <!-- Kolumna 1 - Status API -->
        <div class="aica-dashboard-column">
            <div class="aica-card aica-api-status-card">
                <div class="aica-card-header">
                    <h2><span class="dashicons dashicons-rest-api"></span><?php _e('Status API', 'ai-chat-assistant'); ?></h2>
                </div>
                <div class="aica-card-body">
                    <!-- Claude API status -->
                    <div class="aica-status-section">
                        <div class="aica-status-header">
                            <h3><?php _e('Claude API', 'ai-chat-assistant'); ?></h3>
                            <button id="test-claude-api" class="button aica-button-small aica-test-button">
                                <span class="dashicons dashicons-update"></span> <?php _e('Test', 'ai-chat-assistant'); ?>
                            </button>
                        </div>

                        <?php if ($claude_api_status['valid']): ?>
                            <div class="aica-status aica-status-card aica-status-success">
                                <div class="aica-status-icon">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                </div>
                                <div class="aica-status-content">
                                    <h4><?php _e('Połączono z API Claude', 'ai-chat-assistant'); ?></h4>
                                    <?php if (isset($claude_api_status['details'])): ?>
                                    <div class="aica-status-details">
                                        <div class="aica-detail-item">
                                            <span class="aica-detail-label"><?php _e('Wybrany model:', 'ai-chat-assistant'); ?></span>
                                            <span class="aica-detail-value">
                                                <?php echo esc_html($claude_api_status['details']['current_model']); ?>
                                                <?php if ($claude_api_status['details']['model_available']): ?>
                                                    <span class="aica-badge aica-badge-success"><?php _e('Dostępny', 'ai-chat-assistant'); ?></span>
                                                <?php else: ?>
                                                    <span class="aica-badge aica-badge-error"><?php _e('Niedostępny', 'ai-chat-assistant'); ?></span>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                        <div class="aica-detail-item">
                                            <span class="aica-detail-label"><?php _e('Dostępne modele:', 'ai-chat-assistant'); ?></span>
                                            <div class="aica-models-list">
                                                <?php foreach ($claude_api_status['details']['models'] as $model): ?>
                                                    <span class="aica-model-badge"><?php echo esc_html($model); ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="aica-status aica-status-card aica-status-error">
                                <div class="aica-status-icon">
                                    <span class="dashicons dashicons-warning"></span>
                                </div>
                                <div class="aica-status-content">
                                    <h4><?php _e('Problem z API Claude', 'ai-chat-assistant'); ?></h4>
                                    <p><?php echo esc_html($claude_api_status['message']); ?></p>
                                    <?php if (isset($claude_api_status['error_details'])): ?>
                                        <div class="aica-status-details">
                                            <div class="aica-detail-item">
                                                <span class="aica-detail-label"><?php _e('Szczegóły błędu:', 'ai-chat-assistant'); ?></span>
                                                <span class="aica-detail-value"><?php echo esc_html($claude_api_status['error_details']); ?></span>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- GitHub API status -->
                    <div class="aica-status-section">
                        <div class="aica-status-header">
                            <h3><?php _e('GitHub API', 'ai-chat-assistant'); ?></h3>
                            <button id="test-github-api" class="button aica-button-small aica-test-button">
                                <span class="dashicons dashicons-update"></span> <?php _e('Test', 'ai-chat-assistant'); ?>
                            </button>
                        </div>

                        <?php if ($github_api_status['valid']): ?>
                            <div class="aica-status aica-status-card aica-status-success">
                                <div class="aica-status-icon">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                </div>
                                <div class="aica-status-content">
                                    <h4><?php _e('Połączono z API GitHub', 'ai-chat-assistant'); ?></h4>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="aica-status aica-status-card aica-status-error">
                                <div class="aica-status-icon">
                                    <span class="dashicons dashicons-warning"></span>
                                </div>
                                <div class="aica-status-content">
                                    <h4><?php _e('Problem z API GitHub', 'ai-chat-assistant'); ?></h4>
                                    <p><?php echo esc_html($github_api_status['message']); ?></p>
                                    <?php if (isset($github_api_status['error_details'])): ?>
                                        <div class="aica-status-details">
                                            <div class="aica-detail-item">
                                                <span class="aica-detail-label"><?php _e('Szczegóły błędu:', 'ai-chat-assistant'); ?></span>
                                                <span class="aica-detail-value"><?php echo esc_html($github_api_status['error_details']); ?></span>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- GitLab API status -->
                    <div class="aica-status-section">
                        <div class="aica-status-header">
                            <h3><?php _e('GitLab API', 'ai-chat-assistant'); ?></h3>
                            <button id="test-gitlab-api" class="button aica-button-small aica-test-button">
                                <span class="dashicons dashicons-update"></span> <?php _e('Test', 'ai-chat-assistant'); ?>
                            </button>
                        </div>

                        <?php if ($gitlab_api_status['valid']): ?>
                            <div class="aica-status aica-status-card aica-status-success">
                                <div class="aica-status-icon">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                </div>
                                <div class="aica-status-content">
                                    <h4><?php _e('Połączono z API GitLab', 'ai-chat-assistant'); ?></h4>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="aica-status aica-status-card aica-status-error">
                                <div class="aica-status-icon">
                                    <span class="dashicons dashicons-warning"></span>
                                </div>
                                <div class="aica-status-content">
                                    <h4><?php _e('Problem z API GitLab', 'ai-chat-assistant'); ?></h4>
                                    <p><?php echo esc_html($gitlab_api_status['message']); ?></p>
                                    <?php if (isset($gitlab_api_status['error_details'])): ?>
                                        <div class="aica-status-details">
                                            <div class="aica-detail-item">
                                                <span class="aica-detail-label"><?php _e('Szczegóły błędu:', 'ai-chat-assistant'); ?></span>
                                                <span class="aica-detail-value"><?php echo esc_html($gitlab_api_status['error_details']); ?></span>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Bitbucket API status -->
                    <div class="aica-status-section">
                        <div class="aica-status-header">
                            <h3><?php _e('Bitbucket API', 'ai-chat-assistant'); ?></h3>
                            <button id="test-bitbucket-api" class="button aica-button-small aica-test-button">
                                <span class="dashicons dashicons-update"></span> <?php _e('Test', 'ai-chat-assistant'); ?>
                            </button>
                        </div>

                        <?php if ($bitbucket_api_status['valid']): ?>
                            <div class="aica-status aica-status-card aica-status-success">
                                <div class="aica-status-icon">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                </div>
                                <div class="aica-status-content">
                                    <h4><?php _e('Połączono z API Bitbucket', 'ai-chat-assistant'); ?></h4>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="aica-status aica-status-card aica-status-error">
                                <div class="aica-status-icon">
                                    <span class="dashicons dashicons-warning"></span>
                                </div>
                                <div class="aica-status-content">
                                    <h4><?php _e('Problem z API Bitbucket', 'ai-chat-assistant'); ?></h4>
                                    <p><?php echo esc_html($bitbucket_api_status['message']); ?></p>
                                    <?php if (isset($bitbucket_api_status['error_details'])): ?>
                                        <div class="aica-status-details">
                                            <div class="aica-detail-item">
                                                <span class="aica-detail-label"><?php _e('Szczegóły błędu:', 'ai-chat-assistant'); ?></span>
                                                <span class="aica-detail-value"><?php echo esc_html($bitbucket_api_status['error_details']); ?></span>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Karta informacji systemowych -->
            <div class="aica-card aica-system-info-card">
                <div class="aica-card-header">
                    <h2><span class="dashicons dashicons-info"></span> <?php _e('Informacje systemowe', 'ai-chat-assistant'); ?></h2>
                </div>
                <div class="aica-card-body">
                    <div class="aica-system-info-grid">
                        <div class="aica-info-item">
                            <div class="aica-info-icon">
                                <span class="dashicons dashicons-php"></span>
                            </div>
                            <div class="aica-info-content">
                                <h4><?php _e('Wersja PHP', 'ai-chat-assistant'); ?></h4>
                                <div class="aica-info-value"><?php echo esc_html(phpversion()); ?></div>
                            </div>
                        </div>
                        
                        <div class="aica-info-item">
                            <div class="aica-info-icon">
                                <span class="dashicons dashicons-wordpress"></span>
                            </div>
                            <div class="aica-info-content">
                                <h4><?php _e('Wersja WordPress', 'ai-chat-assistant'); ?></h4>
                                <div class="aica-info-value"><?php echo esc_html(get_bloginfo('version')); ?></div>
                            </div>
                        </div>
                        
                        <div class="aica-info-item">
                            <div class="aica-info-icon">
                                <span class="dashicons dashicons-admin-plugins"></span>
                            </div>
                            <div class="aica-info-content">
                                <h4><?php _e('Wersja wtyczki', 'ai-chat-assistant'); ?></h4>
                                <div class="aica-info-value"><?php echo defined('AICA_VERSION') ? esc_html(AICA_VERSION) : __('Nieznana', 'ai-chat-assistant'); ?></div>
                            </div>
                        </div>
                        
                        <div class="aica-info-item">
                            <div class="aica-info-icon">
                                <span class="dashicons dashicons-performance"></span>
                            </div>
                            <div class="aica-info-content">
                                <h4><?php _e('Pamięć PHP', 'ai-chat-assistant'); ?></h4>
                                <div class="aica-info-value"><?php echo esc_html(ini_get('memory_limit')); ?></div>
                            </div>
                        </div>
                        
                        <div class="aica-info-item">
                            <div class="aica-info-icon">
                                <span class="dashicons dashicons-clock"></span>
                            </div>
                            <div class="aica-info-content">
                                <h4><?php _e('Limit czasu wykonania', 'ai-chat-assistant'); ?></h4>
                                <div class="aica-info-value"><?php echo esc_html(ini_get('max_execution_time')) . ' ' . __('sekund', 'ai-chat-assistant'); ?></div>
                            </div>
                        </div>
                        
                        <div class="aica-info-item">
                            <div class="aica-info-icon">
                                <span class="dashicons dashicons-admin-site"></span>
                            </div>
                            <div class="aica-info-content">
                                <h4><?php _e('cURL', 'ai-chat-assistant'); ?></h4>
                                <div class="aica-info-value">
                                    <?php if (function_exists('curl_version')): ?>
                                        <span class="aica-badge aica-badge-success"><?php _e('Włączone', 'ai-chat-assistant'); ?></span>
                                    <?php else: ?>
                                        <span class="aica-badge aica-badge-error"><?php _e('Wyłączone', 'ai-chat-assistant'); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="aica-info-item">
                            <div class="aica-info-icon">
                                <span class="dashicons dashicons-shield"></span>
                            </div>
                            <div class="aica-info-content">
                                <h4><?php _e('OpenSSL', 'ai-chat-assistant'); ?></h4>
                                <div class="aica-info-value">
                                    <?php if (extension_loaded('openssl')): ?>
                                        <span class="aica-badge aica-badge-success"><?php _e('Włączone', 'ai-chat-assistant'); ?></span>
                                    <?php else: ?>
                                        <span class="aica-badge aica-badge-error"><?php _e('Wyłączone', 'ai-chat-assistant'); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="aica-info-item">
                            <div class="aica-info-icon">
                                <span class="dashicons dashicons-database"></span>
                            </div>
                            <div class="aica-info-content">
                                <h4><?php _e('Wersja MySQL', 'ai-chat-assistant'); ?></h4>
                                <div class="aica-info-value">
                                    <?php 
                                    global $wpdb;
                                    echo esc_html($wpdb->db_version());
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Kolumna 2 - Baza danych i pliki -->
        <div class="aica-dashboard-column">
            <!-- Karta statusu bazy danych -->
            <div class="aica-card aica-database-card">
                <div class="aica-card-header">
                    <h2><span class="dashicons dashicons-database"></span> <?php _e('Status bazy danych', 'ai-chat-assistant'); ?></h2>
                    <div class="aica-card-header-actions">
                        <button id="repair-database" class="button aica-button-small aica-repair-button">
                            <span class="dashicons dashicons-hammer"></span> <?php _e('Napraw', 'ai-chat-assistant'); ?>
                        </button>
                    </div>
                </div>
                <div class="aica-card-body">
                    <div class="aica-database-table">
                        <div class="aica-table-header">
                            <div class="aica-table-cell aica-table-cell-name"><?php _e('Tabela', 'ai-chat-assistant'); ?></div>
                            <div class="aica-table-cell aica-table-cell-status"><?php _e('Status', 'ai-chat-assistant'); ?></div>
                            <div class="aica-table-cell aica-table-cell-records"><?php _e('Rekordy', 'ai-chat-assistant'); ?></div>
                        </div>
                        <?php foreach ($database_status as $table => $status): ?>
                            <div class="aica-table-row <?php echo (!$status['exists']) ? 'aica-table-row-error' : ''; ?>">
                                <div class="aica-table-cell aica-table-cell-name"><?php echo esc_html($status['name']); ?> <span class="aica-table-cell-detail">(<?php echo esc_html($table); ?>)</span></div>
                                <div class="aica-table-cell aica-table-cell-status">
                                    <?php if ($status['exists']): ?>
                                        <span class="aica-badge aica-badge-success"><?php _e('Istnieje', 'ai-chat-assistant'); ?></span>
                                    <?php else: ?>
                                        <span class="aica-badge aica-badge-error"><?php _e('Brak', 'ai-chat-assistant'); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="aica-table-cell aica-table-cell-records">
                                    <?php if ($status['exists']): ?>
                                        <span class="aica-badge aica-badge-info"><?php echo esc_html($status['records']); ?></span>
                                    <?php else: ?>
                                        <span class="aica-badge aica-badge-warning">-</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Karta uprawnień plików -->
            <div class="aica-card aica-files-card">
                <div class="aica-card-header">
                    <h2><span class="dashicons dashicons-admin-page"></span> <?php _e('Uprawnienia plików', 'ai-chat-assistant'); ?></h2>
                </div>
                <div class="aica-card-body">
                    <div class="aica-files-list">
                        <?php foreach ($files_permissions as $file => $status): ?>
                            <div class="aica-file-item <?php echo (!$status['exists'] || !$status['readable']) ? 'aica-file-problem' : ''; ?>">
                                <div class="aica-file-icon">
                                    <?php if (!$status['exists']): ?>
                                        <span class="dashicons dashicons-warning"></span>
                                    <?php elseif (!$status['readable']): ?>
                                        <span class="dashicons dashicons-lock"></span>
                                    <?php else: ?>
                                        <span class="dashicons dashicons-media-text"></span>
                                    <?php endif; ?>
                                </div>
                                <div class="aica-file-details">
                                    <div class="aica-file-name"><?php echo esc_html($file); ?></div>
                                    <div class="aica-file-path"><?php echo esc_html($status['path']); ?></div>
                                    <div class="aica-file-info">
                                        <?php if ($status['exists']): ?>
                                            <span class="aica-file-perm"><?php echo esc_html($status['permissions']); ?></span>
                                            <div class="aica-file-badges">
                                                <?php if ($status['readable']): ?>
                                                    <span class="aica-badge aica-badge-success"><?php _e('Odczyt', 'ai-chat-assistant'); ?></span>
                                                <?php else: ?>
                                                    <span class="aica-badge aica-badge-error"><?php _e('Brak odczytu', 'ai-chat-assistant'); ?></span>
                                                <?php endif; ?>
                                                
                                                <?php if ($status['writable']): ?>
                                                    <span class="aica-badge aica-badge-success"><?php _e('Zapis', 'ai-chat-assistant'); ?></span>
                                                <?php else: ?>
                                                    <span class="aica-badge aica-badge-warning"><?php _e('Brak zapisu', 'ai-chat-assistant'); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="aica-badge aica-badge-error"><?php _e('Plik nie istnieje', 'ai-chat-assistant'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolumna 3 - Historia czatu -->
        <div class="aica-dashboard-column">
            <!-- Karta historii czatu -->
            <div class="aica-card aica-sessions-card">
                <div class="aica-card-header">
                    <h2><span class="dashicons dashicons-admin-comments"></span> <?php _e('Historia czatu', 'ai-chat-assistant'); ?></h2>
                </div>
                <div class="aica-card-body">
                    <?php
                    // Pobierz historię czatu
                    global $wpdb;
                    $user_id = get_current_user_id();
                    $table_name = $wpdb->prefix . 'aica_sessions';
                    
                    // Sprawdź, czy tabela istnieje
                    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
                    
                    if ($table_exists) {
                        $sessions = $wpdb->get_results(
                            $wpdb->prepare(
                                "SELECT * FROM {$wpdb->prefix}aica_sessions WHERE user_id = %d ORDER BY created_at DESC LIMIT 15",
                                $user_id
                            ),
                            ARRAY_A
                        );
                    } else {
                        $sessions = array();
                    }
                    
                    if (empty($sessions)): ?>
                        <div class="aica-empty-state">
                            <div class="aica-empty-icon">
                                <span class="dashicons dashicons-format-chat"></span>
                            </div>
                            <h3><?php _e('Brak historii czatu', 'ai-chat-assistant'); ?></h3>
                            <p><?php _e('Nie przeprowadziłeś jeszcze żadnych rozmów z Claude. Rozpocznij rozmowę, aby zobaczyć ją tutaj.', 'ai-chat-assistant'); ?></p>
                            <a href="<?php echo admin_url('admin.php?page=ai-chat-assistant'); ?>" class="button button-primary">
                                <span class="dashicons dashicons-plus"></span>
                                <?php _e('Rozpocznij rozmowę', 'ai-chat-assistant'); ?>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="aica-sessions-list">
                            <?php foreach ($sessions as $session): ?>
                                <div class="aica-session-item">
                                    <div class="aica-session-icon">
                                        <span class="dashicons dashicons-format-chat"></span>
                                    </div>
                                    <div class="aica-session-details">
                                        <div class="aica-session-title"><?php echo esc_html($session['title']); ?></div>
                                        <div class="aica-session-meta">
                                            <span class="aica-session-id"><?php echo esc_html(substr($session['session_id'], 0, 8) . '...'); ?></span>
                                            <span class="aica-session-date"><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($session['created_at']))); ?></span>
                                        </div>
                                    </div>
                                    <div class="aica-session-actions">
                                        <button class="aica-session-action js-delete-session" data-session-id="<?php echo esc_attr($session['session_id']); ?>">
                                            <span class="dashicons dashicons-trash"></span>
                                        </button>
                                        <a href="<?php echo admin_url('admin.php?page=ai-chat-assistant&session_id=' . esc_attr($session['session_id'])); ?>" class="aica-session-action">
                                            <span class="dashicons dashicons-arrow-right-alt"></span>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="aica-card-footer">
                            <a href="<?php echo admin_url('admin.php?page=ai-chat-assistant-history'); ?>" class="button aica-view-all-button">
                                <span class="dashicons dashicons-list-view"></span>
                                <?php _e('Zobacz wszystkie rozmowy', 'ai-chat-assistant'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Toast notifications -->
    <div id="aica-notifications-container" class="aica-notifications-container"></div>
    
    <!-- Confirmation dialog -->
    <div id="aica-confirm-dialog" class="aica-dialog" style="display: none;">
        <div class="aica-dialog-overlay"></div>
        <div class="aica-dialog-content">
            <div class="aica-dialog-header">
                <h3 id="aica-dialog-title"><?php _e('Potwierdź operację', 'ai-chat-assistant'); ?></h3>
                <button type="button" class="aica-dialog-close">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
            <div class="aica-dialog-body">
                <p id="aica-dialog-message"><?php _e('Czy na pewno chcesz wykonać tę operację?', 'ai-chat-assistant'); ?></p>
            </div>
            <div class="aica-dialog-footer">
                <button type="button" class="button aica-dialog-cancel"><?php _e('Anuluj', 'ai-chat-assistant'); ?></button>
                <button type="button" class="button button-primary aica-dialog-confirm"><?php _e('Potwierdź', 'ai-chat-assistant'); ?></button>
            </div>
        </div>
    </div>
</div>