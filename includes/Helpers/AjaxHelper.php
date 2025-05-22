<?php
declare(strict_types=1);

namespace AICA\Helpers;

/**
 * Klasa pomocnicza do obsługi żądań AJAX
 */
class AjaxHelper {
    /**
     * Sprawdza czy żądanie jest typu AJAX
     */
    public static function is_ajax_request(): bool {
        return defined('DOING_AJAX') && DOING_AJAX;
    }

    /**
     * Sprawdza czy żądanie jest typu REST
     */
    public static function is_rest_request(): bool {
        return defined('REST_REQUEST') && REST_REQUEST;
    }

    /**
     * Sprawdza czy żądanie jest typu WP-CLI
     */
    public static function is_cli_request(): bool {
        return defined('WP_CLI') && WP_CLI;
    }

    /**
     * Sprawdza czy żądanie jest typu CRON
     */
    public static function is_cron_request(): bool {
        return defined('DOING_CRON') && DOING_CRON;
    }

    /**
     * Sprawdza czy żądanie jest typu XML-RPC
     */
    public static function is_xmlrpc_request(): bool {
        return defined('XMLRPC_REQUEST') && XMLRPC_REQUEST;
    }

    /**
     * Sprawdza czy żądanie jest typu WP-API
     */
    public static function is_api_request(): bool {
        return self::is_rest_request() || self::is_xmlrpc_request();
    }

    /**
     * Sprawdza czy żądanie jest typu WP-Admin
     */
    public static function is_admin_request(): bool {
        return is_admin();
    }

    /**
     * Sprawdza czy żądanie jest typu WP-Frontend
     */
    public static function is_frontend_request(): bool {
        return !self::is_admin_request() && !self::is_api_request();
    }

    /**
     * Sprawdza czy żądanie jest typu WP-Login
     */
    public static function is_login_request(): bool {
        return in_array($GLOBALS['pagenow'] ?? '', ['wp-login.php', 'wp-register.php'], true);
    }

    /**
     * Sprawdza czy żądanie jest typu WP-Install
     */
    public static function is_install_request(): bool {
        return defined('WP_INSTALLING') && WP_INSTALLING;
    }

    /**
     * Sprawdza czy żądanie jest typu WP-Activation
     */
    public static function is_activation_request(): bool {
        return defined('WP_PLUGIN_DIR') && file_exists(WP_PLUGIN_DIR . '/ai-chat-assistant/ai-chat-assistant.php');
    }

    /**
     * Sprawdza czy żądanie jest typu WP-Deactivation
     */
    public static function is_deactivation_request(): bool {
        return defined('WP_PLUGIN_DIR') && !file_exists(WP_PLUGIN_DIR . '/ai-chat-assistant/ai-chat-assistant.php');
    }

    /**
     * Sprawdza czy żądanie jest typu WP-Uninstall
     */
    public static function is_uninstall_request(): bool {
        return defined('WP_UNINSTALL_PLUGIN') && WP_UNINSTALL_PLUGIN === 'ai-chat-assistant/ai-chat-assistant.php';
    }

    /**
     * Sprawdza czy żądanie jest typu WP-Update
     */
    public static function is_update_request(): bool {
        return defined('WP_PLUGIN_DIR') && 
               file_exists(WP_PLUGIN_DIR . '/ai-chat-assistant/ai-chat-assistant.php') && 
               get_option('aica_version') !== AICA_VERSION;
    }

    /**
     * Sprawdza czy żądanie jest typu WP-Installation
     */
    public static function is_installation_request(): bool {
        return self::is_install_request() || self::is_activation_request() || self::is_update_request();
    }

    /**
     * Sprawdza czy żądanie jest typu WP-Deinstallation
     */
    public static function is_deinstallation_request(): bool {
        return self::is_deactivation_request() || self::is_uninstall_request();
    }

    /**
     * Sprawdza czy żądanie jest typu WP-System
     */
    public static function is_system_request(): bool {
        return self::is_installation_request() || self::is_deinstallation_request();
    }

    /**
     * Sprawdza czy żądanie jest typu WP-User
     */
    public static function is_user_request(): bool {
        return !self::is_system_request();
    }

    /**
     * Sprawdza czy żądanie jest typu WP-Admin-User
     */
    public static function is_admin_user_request(): bool {
        return self::is_admin_request() && self::is_user_request();
    }

    /**
     * Sprawdza czy żądanie jest typu WP-Frontend-User
     */
    public static function is_frontend_user_request(): bool {
        return self::is_frontend_request() && self::is_user_request();
    }

    /**
     * Sprawdza czy żądanie jest typu WP-API-User
     */
    public static function is_api_user_request(): bool {
        return self::is_api_request() && self::is_user_request();
    }

    /**
     * Sprawdza czy żądanie jest typu WP-AJAX-User
     */
    public static function is_ajax_user_request(): bool {
        return self::is_ajax_request() && self::is_user_request();
    }

    /**
     * Sprawdza czy żądanie jest typu WP-REST-User
     */
    public static function is_rest_user_request(): bool {
        return self::is_rest_request() && self::is_user_request();
    }

    /**
     * Sprawdza czy żądanie jest typu WP-CLI-User
     */
    public static function is_cli_user_request(): bool {
        return self::is_cli_request() && self::is_user_request();
    }

    /**
     * Sprawdza czy żądanie jest typu WP-CRON-User
     */
    public static function is_cron_user_request(): bool {
        return self::is_cron_request() && self::is_user_request();
    }

    /**
     * Sprawdza czy żądanie jest typu WP-XMLRPC-User
     */
    public static function is_xmlrpc_user_request(): bool {
        return self::is_xmlrpc_request() && self::is_user_request();
    }

    /**
     * Sprawdza czy żądanie jest typu WP-Login-User
     */
    public static function is_login_user_request(): bool {
        return self::is_login_request() && self::is_user_request();
    }

    /**
     * Sprawdza czy żądanie jest typu WP-Install-User
     */
    public static function is_install_user_request(): bool {
        return self::is_install_request() && self::is_user_request();
    }

    /**
     * Sprawdza czy żądanie jest typu WP-Activation-User
     */
    public static function is_activation_user_request(): bool {
        return self::is_activation_request() && self::is_user_request();
    }

    /**
     * Sprawdza czy żądanie jest typu WP-Deactivation-User
     */
    public static function is_deactivation_user_request(): bool {
        return self::is_deactivation_request() && self::is_user_request();
    }

    /**
     * Sprawdza czy żądanie jest typu WP-Uninstall-User
     */
    public static function is_uninstall_user_request(): bool {
        return self::is_uninstall_request() && self::is_user_request();
    }

    /**
     * Sprawdza czy żądanie jest typu WP-Update-User
     */
    public static function is_update_user_request(): bool {
        return self::is_update_request() && self::is_user_request();
    }

    /**
     * Sprawdza czy żądanie jest typu WP-Installation-User
     */
    public static function is_installation_user_request(): bool {
        return self::is_installation_request() && self::is_user_request();
    }

    /**
     * Sprawdza czy żądanie jest typu WP-Deinstallation-User
     */
    public static function is_deinstallation_user_request(): bool {
        return self::is_deinstallation_request() && self::is_user_request();
    }

    /**
     * Sprawdza czy żądanie jest typu WP-System-User
     */
    public static function is_system_user_request(): bool {
        return self::is_system_request() && self::is_user_request();
    }
}

/**
 * Verifies AJAX request nonce
 *
 * @param string $nonce_name Nonce name
 * @return bool True if nonce is valid
 */
function aica_verify_ajax_request(string $nonce_name = 'aica_nonce'): bool {
    if (!check_ajax_referer($nonce_name, 'nonce', false)) {
        wp_send_json_error(['message' => __('Invalid nonce', 'ai-chat-assistant')]);
        return false;
    }
    return true;
}

/**
 * Verifies required parameters in request
 *
 * @param array $params Required parameters
 * @return bool True if all parameters are present
 */
function aica_verify_required_params(array $params): bool {
    foreach ($params as $param) {
        if (!isset($_POST[$param])) {
            wp_send_json_error(['message' => sprintf(__('Missing required parameter: %s', 'ai-chat-assistant'), $param)]);
            return false;
        }
    }
    return true;
}

/**
 * Formats conversation as text
 *
 * @param array $session Session data
 * @param array $messages Messages data
 * @return string Formatted conversation
 */
function aica_format_conversation_as_text(array $session, array $messages): string {
    $output = sprintf("Conversation: %s\n\n", $session['title'] ?? 'Untitled');
    
    foreach ($messages as $message) {
        $role = $message['role'] ?? 'unknown';
        $content = $message['content'] ?? '';
        $output .= sprintf("%s: %s\n\n", ucfirst($role), $content);
    }
    
    return $output;
}

/**
 * Formats conversation as HTML
 *
 * @param array $session Session data
 * @param array $messages Messages data
 * @return string Formatted conversation
 */
function aica_format_conversation_as_html(array $session, array $messages): string {
    $output = sprintf('<div class="aica-conversation"><h2>%s</h2>', 
        esc_html($session['title'] ?? 'Untitled')
    );
    
    foreach ($messages as $message) {
        $role = $message['role'] ?? 'unknown';
        $content = $message['content'] ?? '';
        $output .= sprintf(
            '<div class="aica-message aica-message-%s"><strong>%s:</strong> %s</div>',
            esc_attr($role),
            esc_html(ucfirst($role)),
            wp_kses_post($content)
        );
    }
    
    $output .= '</div>';
    return $output;
}

/**
 * Gets history translations
 *
 * @return array Translations array
 */
function aica_get_history_translations(): array {
    return [
        'no_messages' => __('No messages in this conversation', 'ai-chat-assistant'),
        'loading' => __('Loading conversation...', 'ai-chat-assistant'),
        'error' => __('Error loading conversation', 'ai-chat-assistant'),
        'delete_confirm' => __('Are you sure you want to delete this conversation?', 'ai-chat-assistant'),
        'delete_success' => __('Conversation deleted successfully', 'ai-chat-assistant'),
        'delete_error' => __('Error deleting conversation', 'ai-chat-assistant'),
        'export_success' => __('Conversation exported successfully', 'ai-chat-assistant'),
        'export_error' => __('Error exporting conversation', 'ai-chat-assistant')
    ];
} 