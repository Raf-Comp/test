<?php
/**
 * Szablon czatu
 *
 * @package AI_Chat_Assistant
 */

// Bezpośredni dostęp do pliku jest zabroniony
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="aica-chat-wrapper">
    <div class="aica-sidebar">
        <div class="aica-sidebar-header">
            <div class="aica-branding">
                <div class="aica-logo">🤖</div>
                <h1>AI Chat</h1>
            </div>
            <button class="aica-sidebar-toggle">
                <span class="dashicons dashicons-menu"></span>
            </button>
        </div>
        
        <div class="aica-sidebar-actions">
            <button class="aica-action-button" id="aica-new-chat">
                <span class="dashicons dashicons-plus-alt"></span>
                <span class="aica-button-text">Nowa rozmowa</span>
            </button>
        </div>
        
        <div class="aica-tabs">
            <div class="aica-tab aica-tab-active" data-tab="history">
                <span class="dashicons dashicons-format-chat"></span>
                <span class="aica-tab-text">Historia</span>
            </div>
            <div class="aica-tab" data-tab="favorites">
                <span class="dashicons dashicons-star-filled"></span>
                <span class="aica-tab-text">Ulubione</span>
            </div>
        </div>
        
        <div class="aica-search-container">
            <input type="text" class="aica-search-input" placeholder="Szukaj rozmów...">
            <span class="aica-search-icon dashicons dashicons-search"></span>
        </div>
        
        <div class="aica-sessions-list">
            <!-- Lista sesji będzie generowana przez JavaScript -->
        </div>
        
        <div class="aica-sidebar-footer">
            <span class="aica-version">v<?php echo AICA_VERSION; ?></span>
            <a href="<?php echo admin_url('admin.php?page=ai-chat-assistant-settings'); ?>" class="aica-settings-link">
                <span class="dashicons dashicons-admin-settings"></span>
            </a>
        </div>
    </div>

    <div class="aica-main-panel">
        <div class="aica-main-header">
            <div class="aica-conversation-info">
                <h2 class="aica-conversation-title">Nowa rozmowa</h2>
                <span class="aica-conversation-date"></span>
            </div>
            <div class="aica-main-actions">
                <button class="aica-toolbar-button" id="aica-rename-chat" title="Zmień nazwę">
                    <span class="dashicons dashicons-edit"></span>
                </button>
                <button class="aica-toolbar-button" id="aica-delete-chat" title="Usuń rozmowę">
                    <span class="dashicons dashicons-trash"></span>
                </button>
            </div>
        </div>

        <div class="aica-messages-container">
            <div id="aica-welcome-screen" class="aica-welcome-screen">
                <div class="aica-welcome-icon">
                    <span class="dashicons dashicons-format-chat"></span>
                </div>
                <h2>Witaj w AI Chat!</h2>
                <p>Rozpocznij nową rozmowę lub wybierz istniejącą z historii.</p>
                <div class="aica-example-prompts">
                    <h3>Przykładowe pytania:</h3>
                    <div class="aica-examples">
                        <div class="aica-example-prompt">Jak mogę zoptymalizować wydajność mojej strony?</div>
                        <div class="aica-example-prompt">Pomóż mi napisać skrypt PHP do obsługi formularza.</div>
                        <div class="aica-example-prompt">Wyjaśnij mi różnicę między REST a GraphQL.</div>
                    </div>
                </div>
            </div>
            <div id="aica-messages" class="aica-messages"></div>
        </div>

        <div class="aica-input-container">
            <div class="aica-input-wrapper">
                <textarea id="aica-message-input" placeholder="Wpisz wiadomość..." rows="1"></textarea>
                <div class="aica-input-tools">
                    <button class="aica-tool-button" id="aica-upload-file" title="Dodaj plik">
                        <span class="dashicons dashicons-upload"></span>
                    </button>
                    <input type="file" id="aica-file-input" class="aica-file-input" multiple>
                </div>
            </div>
            <button class="aica-send-button" id="aica-send-message" title="Wyślij wiadomość">
                <span class="dashicons dashicons-arrow-right-alt"></span>
            </button>
            <div class="aica-input-footer">
                <div class="aica-model-info">
                    <span class="dashicons dashicons-info"></span>
                    <span class="aica-model-name"><?php echo esc_html(aica_get_option('claude_model', 'claude-3-haiku-20240307')); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal do zmiany nazwy rozmowy -->
<div id="aica-rename-modal" class="aica-modal" style="display: none;">
    <div class="aica-modal-content">
        <div class="aica-modal-header">
            <h3>Zmień nazwę rozmowy</h3>
            <button class="aica-modal-close">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="aica-modal-body">
            <div class="aica-form-field">
                <label for="aica-rename-input">Nazwa rozmowy</label>
                <input type="text" id="aica-rename-input" placeholder="Wprowadź nową nazwę">
            </div>
        </div>
        <div class="aica-modal-footer">
            <button class="aica-button aica-button-secondary aica-modal-cancel">Anuluj</button>
            <button class="aica-button aica-button-primary aica-modal-confirm">Zapisz</button>
        </div>
    </div>
</div>

<!-- Dialog potwierdzenia usunięcia -->
<div id="aica-delete-dialog" class="aica-dialog" style="display: none;">
    <div class="aica-dialog-content">
        <div class="aica-dialog-header">
            <h3>Potwierdź usunięcie</h3>
            <button class="aica-dialog-close">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="aica-dialog-body">
            <p>Czy na pewno chcesz usunąć tę rozmowę? Tej operacji nie można cofnąć.</p>
        </div>
        <div class="aica-dialog-footer">
            <button class="aica-button aica-button-secondary aica-dialog-cancel">Anuluj</button>
            <button class="aica-button aica-button-danger aica-dialog-confirm">Usuń</button>
        </div>
    </div>
</div>
