<?php
declare(strict_types=1);

/**
 * Pomocnicze funkcje dla tłumaczeń
 *
 * @package AI_Chat_Assistant
 */

namespace AICA\Helpers;

if (!defined('ABSPATH')) {
    exit; // Bezpośredni dostęp zabroniony
}

/**
 * Klasa pomocnicza do obsługi tłumaczeń
 */
class TranslationHelper {
    /**
     * Pobiera tłumaczenia dla czatu
     *
     * @return array<string, string>
     */
    public static function get_chat_translations(): array {
        return [
            'title' => __('Chat AI', 'ai-chat-assistant'),
            'placeholder' => __('Wpisz wiadomość...', 'ai-chat-assistant'),
            'send' => __('Wyślij', 'ai-chat-assistant'),
            'clear' => __('Wyczyść', 'ai-chat-assistant'),
            'thinking' => __('Myślę...', 'ai-chat-assistant'),
            'error' => __('Wystąpił błąd. Spróbuj ponownie.', 'ai-chat-assistant'),
            'no_messages' => __('Brak wiadomości.', 'ai-chat-assistant'),
            'confirm_clear' => __('Czy na pewno chcesz wyczyścić historię?', 'ai-chat-assistant')
        ];
    }

    /**
     * Pobiera tłumaczenia dla historii
     *
     * @return array<string, string>
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
     * Pobiera tłumaczenia dla repozytoriów
     *
     * @return array<string, string>
     */
    public static function get_repository_translations(): array {
        return [
            'title' => __('Repozytoria', 'ai-chat-assistant'),
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
     * Pobiera tłumaczenia dla ustawień
     *
     * @return array<string, string>
     */
    public static function get_settings_translations(): array {
        return [
            'title' => __('Ustawienia', 'ai-chat-assistant'),
            'save' => __('Zapisz', 'ai-chat-assistant'),
            'reset' => __('Resetuj', 'ai-chat-assistant'),
            'success_save' => __('Ustawienia zostały zapisane.', 'ai-chat-assistant'),
            'error_save' => __('Nie udało się zapisać ustawień.', 'ai-chat-assistant'),
            'confirm_reset' => __('Czy na pewno chcesz zresetować ustawienia?', 'ai-chat-assistant'),
            'success_reset' => __('Ustawienia zostały zresetowane.', 'ai-chat-assistant'),
            'error_reset' => __('Nie udało się zresetować ustawień.', 'ai-chat-assistant')
        ];
    }

    /**
     * Pobiera tłumaczenia dla diagnostyki
     *
     * @return array<string, string>
     */
    public static function get_diagnostics_translations(): array {
        return [
            'title' => __('Diagnostyka', 'ai-chat-assistant'),
            'refresh' => __('Odśwież', 'ai-chat-assistant'),
            'export' => __('Eksportuj', 'ai-chat-assistant'),
            'success_export' => __('Diagnostyka została wyeksportowana.', 'ai-chat-assistant'),
            'error_export' => __('Nie udało się wyeksportować diagnostyki.', 'ai-chat-assistant')
        ];
    }
}

/**
 * Pobiera tłumaczenia dla interfejsu historii
 *
 * @return array<string, string>
 */
function aica_get_history_translations(): array {
    return [
        'load_more' => __('Load More', 'ai-chat-assistant'),
        'clear_history' => __('Clear History', 'ai-chat-assistant'),
        'confirm_clear' => __('Are you sure you want to clear this chat history?', 'ai-chat-assistant'),
        'select_session' => __('Select a session to view history', 'ai-chat-assistant'),
        'error_loading' => __('Error loading session:', 'ai-chat-assistant'),
        'error_clearing' => __('Error clearing history:', 'ai-chat-assistant'),
        'chat_history' => __('Chat History', 'ai-chat-assistant'),
        'no_sessions' => __('No chat sessions found.', 'ai-chat-assistant'),
        'loading' => __('Loading...', 'ai-chat-assistant'),
        'error' => __('Error:', 'ai-chat-assistant'),
        'success' => __('Success:', 'ai-chat-assistant'),
        'delete_confirm' => __('Are you sure you want to delete this session?', 'ai-chat-assistant'),
        'delete_success' => __('Session deleted successfully.', 'ai-chat-assistant'),
        'delete_error' => __('Error deleting session:', 'ai-chat-assistant'),
        'export_success' => __('Conversation exported successfully.', 'ai-chat-assistant'),
        'export_error' => __('Error exporting conversation:', 'ai-chat-assistant'),
        'duplicate_success' => __('Conversation duplicated successfully.', 'ai-chat-assistant'),
        'duplicate_error' => __('Error duplicating conversation:', 'ai-chat-assistant')
    ];
}

/**
 * Pobiera tłumaczenia dla interfejsu ustawień
 *
 * @return array<string, string|array<string, string>>
 */
function aica_get_settings_translations(): array {
    return [
        'settings_title' => __('AI Chat Assistant Settings', 'ai-chat-assistant'),
        'settings_saved' => __('Settings saved successfully.', 'ai-chat-assistant'),
        'api_settings' => __('API Settings', 'ai-chat-assistant'),
        'claude_api_key' => __('Claude API Key', 'ai-chat-assistant'),
        'toggle_password' => __('Toggle password visibility', 'ai-chat-assistant'),
        'test_connection' => __('Test Connection', 'ai-chat-assistant'),
        'github_token' => __('GitHub Token', 'ai-chat-assistant'),
        'gitlab_token' => __('GitLab Token', 'ai-chat-assistant'),
        'bitbucket_token' => __('Bitbucket Token', 'ai-chat-assistant'),
        'interface_settings' => __('Interface Settings', 'ai-chat-assistant'),
        'theme' => __('Theme', 'ai-chat-assistant'),
        'light' => __('Light', 'ai-chat-assistant'),
        'dark' => __('Dark', 'ai-chat-assistant'),
        'system' => __('System', 'ai-chat-assistant'),
        'message_history_limit' => __('Message History Limit', 'ai-chat-assistant'),
        'message_history_help' => __('Maximum number of messages to keep in history.', 'ai-chat-assistant')
    ];
}

/**
 * Pobiera tłumaczenia dla interfejsu repozytoriów
 *
 * @return array<string, string|array<string, string>>
 */
function aica_get_repository_translations(): array {
    return [
        'manage_repositories' => __('Manage Repositories', 'ai-chat-assistant'),
        'sources' => __('Sources', 'ai-chat-assistant'),
        'saved_repositories' => __('Saved Repositories', 'ai-chat-assistant'),
        'language_filters' => __('Language Filters', 'ai-chat-assistant'),
        'search_repositories' => __('Search repositories...', 'ai-chat-assistant'),
        'sort_by' => [
            'name_asc' => __('Name (A-Z)', 'ai-chat-assistant'),
            'name_desc' => __('Name (Z-A)', 'ai-chat-assistant'),
            'date_desc' => __('Newest', 'ai-chat-assistant'),
            'date_asc' => __('Oldest', 'ai-chat-assistant')
        ],
        'toggle_theme' => __('Toggle dark/light mode', 'ai-chat-assistant'),
        'add_repository' => __('Add Repository', 'ai-chat-assistant'),
        'no_repositories' => __('No saved repositories', 'ai-chat-assistant'),
        'no_repositories_desc' => __('You don\'t have any saved repositories yet. Add your first repository.', 'ai-chat-assistant'),
        'browse_files' => __('Browse Files', 'ai-chat-assistant')
    ];
}

/**
 * Pobiera tłumaczenia dla interfejsu diagnostyki
 *
 * @return array<string, string>
 */
function aica_get_diagnostics_translations(): array {
    return [
        'title' => __('Diagnostics', 'ai-chat-assistant'),
        'refresh' => __('Refresh', 'ai-chat-assistant'),
        'export' => __('Export', 'ai-chat-assistant'),
        'success_export' => __('Diagnostics exported successfully.', 'ai-chat-assistant'),
        'error_export' => __('Error exporting diagnostics:', 'ai-chat-assistant')
    ];
}

/**
 * Inicjalizuje tłumaczenia
 */
function aica_init_translations(): void {
    load_plugin_textdomain('ai-chat-assistant', false, dirname(plugin_basename(__FILE__)) . '/languages');
} 