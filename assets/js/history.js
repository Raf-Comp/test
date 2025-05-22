/**
 * JavaScript do obsługi strony historii rozmów
 */
jQuery(document).ready(function($) {
    // Zmienne globalne
    let currentSession = null;
    let currentPage = 1;
    let totalPages = 1;
    let itemsPerPage = 10;
    
    /**
     * Funkcje do obsługi historii
     */
    
    // Ładowanie listy rozmów
    function loadConversations(page = 1, search = '') {
        // Pokaż wskaźnik ładowania
        $('.aica-history-container').html('<div class="aica-loading"><div class="aica-loading-spinner"></div><p>' + aica_history.i18n.loading + '</p></div>');
        
        // Przygotuj dane
        const data = {
            action: 'aica_get_sessions_list',
            nonce: aica_history.nonce,
            page: page,
            per_page: itemsPerPage
        };
        
        // Dodaj wyszukiwanie, jeśli podano
        if (search) {
            data.search = search;
        }
        
        // Pobierz listę rozmów
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    // Ukryj wskaźnik ładowania i stwórz kontener
                    $('.aica-history-container').html('<div class="aica-conversations-list"></div><div class="aica-messages-container"></div>');
                    
                    // Pobierz dane
                    const conversations = response.data.sessions;
                    const totalItems = response.data.pagination.total_items;
                    totalPages = Math.ceil(totalItems / itemsPerPage);
                    currentPage = page;
                    
                    // Aktualizuj paginację
                    updatePagination(currentPage, totalPages, totalItems);
                    
                    // Generuj listę rozmów
                    renderConversationsList(conversations, search);
                    
                    // Jeśli jest aktywna rozmowa, zaznacz ją
                    if (currentSession) {
                        $('.aica-conversation-item[data-session-id="' + currentSession + '"]').addClass('active');
                    } else if (conversations && conversations.length > 0) {
                        // Jeśli nie ma aktywnej rozmowy, zaznacz pierwszą
                        currentSession = conversations[0].session_id;
                        $('.aica-conversation-item[data-session-id="' + currentSession + '"]').addClass('active');
                        loadConversationMessages(currentSession);
                    }
                } else {
                    // Pokaż komunikat o błędzie
                    $('.aica-history-container').html(`
                        <div class="aica-empty-state">
                            <div class="aica-empty-icon">
                                <span class="dashicons dashicons-warning"></span>
                            </div>
                            <p>${response.data ? response.data.message : aica_history.i18n.load_error}</p>
                        </div>
                    `);
                }
            },
            error: function() {
                // Pokaż komunikat o błędzie
                $('.aica-history-container').html(`
                    <div class="aica-empty-state">
                        <div class="aica-empty-icon">
                            <span class="dashicons dashicons-warning"></span>
                        </div>
                        <p>${aica_history.i18n.load_error}</p>
                    </div>
                `);
            }
        });
    }
    
    // Wyświetlanie listy rozmów
    function renderConversationsList(conversations, search = '') {
        const conversationsList = $('.aica-conversations-list');
        
        if (!conversations || conversations.length === 0) {
            // Wyświetl komunikat o braku rozmów
            conversationsList.html(`
                <div class="aica-empty-state">
                    <div class="aica-empty-icon">
                        <span class="dashicons dashicons-format-chat"></span>
                    </div>
                    <h2>${aica_history.i18n.no_conversations}</h2>
                    <p>${aica_history.i18n.no_conversations_desc}</p>
                    <a href="${aica_history.chat_url}" class="button button-primary">
                        <span class="dashicons dashicons-plus"></span>
                        ${aica_history.i18n.new_conversation}
                    </a>
                </div>
            `);
            
            // Wyczyść widok wiadomości
            $('.aica-messages-container').html('');
            return;
        }
        
        // Przygotuj HTML dla listy rozmów
        let html = '';
        
        for (let i = 0; i < conversations.length; i++) {
            const conversation = conversations[i];
            
            // Formatowanie daty
            const createdDate = new Date(conversation.created_at);
            const timeAgo = formatTimeAgo(createdDate);
            
            // Dodaj zaznaczenie, jeśli to aktywna rozmowa
            const activeClass = conversation.session_id === currentSession ? 'active' : '';
            
            // Podświetl wyszukiwany tekst w tytule, jeśli podano
            let title = conversation.title || 'Rozmowa ' + conversation.session_id;
            if (search && search.length > 0) {
                const regex = new RegExp('(' + search + ')', 'gi');
                title = title.replace(regex, '<span class="aica-search-highlight">$1</span>');
            }
            
            // Dodaj element do listy
            html += `
                <div class="aica-conversation-item ${activeClass}" data-session-id="${conversation.session_id}">
                    <div class="aica-conversation-title">${title}</div>
                    <div class="aica-conversation-meta">
                        <span class="aica-conversation-date">${timeAgo}</span>
                        <span class="aica-conversation-messages">${conversation.message_count || 0} wiadomości</span>
                    </div>
                </div>
            `;
        }
        
        // Aktualizuj listę rozmów
        conversationsList.html(html);
        
        // Dodaj obsługę kliknięcia rozmowy
        $('.aica-conversation-item').on('click', function() {
            // Usuń aktywną klasę z wszystkich rozmów
            $('.aica-conversation-item').removeClass('active');
            
            // Dodaj aktywną klasę do klikniętej rozmowy
            $(this).addClass('active');
            
            // Pobierz ID sesji
            currentSession = $(this).data('session-id');
            
            // Załaduj wiadomości dla wybranej rozmowy
            loadConversationMessages(currentSession);
        });
    }
    
    // Ładowanie wiadomości dla wybranej rozmowy
    function loadConversationMessages(sessionId, page = 1) {
        // Pokaż wskaźnik ładowania
        $('.aica-messages-container').html(`
            <div class="aica-loading-messages">
                <div class="aica-loading-spinner"></div>
                <p>${aica_history.i18n.loading_messages}</p>
            </div>
        `);
        
        // Pobierz wiadomości
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aica_get_chat_history',
                nonce: aica_history.nonce,
                session_id: sessionId,
                page: page
            },
            success: function(response) {
                if (response.success) {
                    // Pobierz dane
                    const messages = response.data.messages;
                    const title = response.data.title || 'Rozmowa ' + sessionId;
                    
                    // Generuj widok wiadomości
                    renderConversationMessages(messages, title, sessionId);
                } else {
                    // Pokaż komunikat o błędzie
                    $('.aica-messages-container').html(`
                        <div class="aica-empty-state">
                            <div class="aica-empty-icon">
                                <span class="dashicons dashicons-warning"></span>
                            </div>
                            <p>${response.data ? response.data.message : aica_history.i18n.load_error}</p>
                        </div>
                    `);
                }
            },
            error: function() {
                // Pokaż komunikat o błędzie
                $('.aica-messages-container').html(`
                    <div class="aica-empty-state">
                        <div class="aica-empty-icon">
                            <span class="dashicons dashicons-warning"></span>
                        </div>
                        <p>${aica_history.i18n.load_error}</p>
                    </div>
                `);
            }
        });
    }
    
    // Wyświetlanie wiadomości
    function renderConversationMessages(messages, title, sessionId) {
        if (!messages || messages.length === 0) {
            // Wyświetl komunikat o braku wiadomości
            $('.aica-messages-container').html(`
                <div class="aica-empty-state">
                    <div class="aica-empty-icon">
                        <span class="dashicons dashicons-format-chat"></span>
                    </div>
                    <p>${aica_history.i18n.no_messages}</p>
                </div>
            `);
            return;
        }
        
        // Przygotuj HTML dla wiadomości
        let html = '';
        
        // Dodaj tytuł rozmowy
        html += `
            <div class="aica-conversation-header">
                <h2>${title}</h2>
            </div>
        `;
        
        // Dodaj wiadomości
        for (let i = 0; i < messages.length; i++) {
            const message = messages[i];
            
            // Sprawdź czy treść jest zdefiniowana
            if (!message || !message.content) {
                continue; // Pomiń wiadomości bez treści
            }
            
            // Formatowanie daty
            let createdDate;
            try {
                createdDate = new Date(message.time);
            } catch (e) {
                createdDate = new Date();
            }
            const formattedDate = createdDate.toLocaleString();
            
            // Określenie typu wiadomości
            const senderClass = message.type === 'user' ? 'user' : 'assistant';
            const senderName = message.type === 'user' ? aica_history.i18n.user : 'Claude';
            
            // Konwersja zawartości (obsługa wiadomości w formacie HTML)
            let content = message.content || '';
            
            // Bezpieczna konwersja zawartości HTML
            content = content.toString().replace(/&/g, '&amp;')
                           .replace(/</g, '&lt;')
                           .replace(/>/g, '&gt;')
                           .replace(/"/g, '&quot;')
                           .replace(/'/g, '&#039;');
            
            // Zastąp znaki nowej linii znacznikami <br>
            content = content.replace(/\n/g, '<br>');
            
            // Dodaj wiadomość
            html += `
                <div class="aica-message-item ${senderClass}">
                    <div class="aica-message-header">
                        <span class="aica-message-sender">${senderName}</span>
                        <span class="aica-message-time">${formattedDate}</span>
                    </div>
                    <div class="aica-message-content">${content}</div>
                </div>
            `;
        }
        
        // Dodaj przyciski akcji
        html += `
            <div class="aica-conversation-actions">
                <a href="${aica_history.chat_url}&session_id=${sessionId}" class="button button-primary">
                    <span class="dashicons dashicons-format-chat"></span>
                    ${aica_history.i18n.continue_conversation}
                </a>
                <button type="button" class="button aica-duplicate-button" data-session-id="${sessionId}">
                    <span class="dashicons dashicons-admin-page"></span>
                    ${aica_history.i18n.duplicate}
                </button>
                <button type="button" class="button aica-export-button" data-session-id="${sessionId}">
                    <span class="dashicons dashicons-download"></span>
                    ${aica_history.i18n.export}
                </button>
                <button type="button" class="button button-link-delete aica-delete-button" data-session-id="${sessionId}">
                    <span class="dashicons dashicons-trash"></span>
                    ${aica_history.i18n.delete}
                </button>
            </div>
        `;
        
        // Aktualizuj kontener wiadomości
        $('.aica-messages-container').html(html);
        
        // Dodaj obsługę przycisków
        
        // Przycisk duplikowania
        $('.aica-duplicate-button').on('click', function() {
            const sessionId = $(this).data('session-id');
            duplicateConversation(sessionId);
        });
        
        // Przycisk eksportu
        $('.aica-export-button').on('click', function() {
            const sessionId = $(this).data('session-id');
            showExportOptions(sessionId);
        });
        
        // Przycisk usuwania
        $('.aica-delete-button').on('click', function() {
            const sessionId = $(this).data('session-id');
            if (confirm(aica_history.i18n.confirm_delete)) {
                deleteConversation(sessionId);
            }
        });
    }
    
    // Funkcja pokazująca opcje eksportu
    function showExportOptions(sessionId) {
        // Najpierw usuń istniejący dropdown, jeśli istnieje
        $('.aica-export-dropdown').remove();
        
        // Tworzymy dropdown z opcjami formatu
        const dropdown = $('<div class="aica-export-dropdown"></div>');
        
        // Dodajemy opcje formatu
        dropdown.append(`
            <h4>Wybierz format eksportu:</h4>
            <button class="button aica-export-format" data-format="json" data-session-id="${sessionId}">JSON</button>
            <button class="button aica-export-format" data-format="txt" data-session-id="${sessionId}">Tekst</button>
            <button class="button aica-export-format" data-format="html" data-session-id="${sessionId}">HTML</button>
        `);
        
        // Dodajemy dropdown po przycisku eksportu
        $('.aica-export-button').after(dropdown);
        
        // Obsługa kliknięcia opcji formatu
        $('.aica-export-format').on('click', function() {
            const format = $(this).data('format');
            const sessionId = $(this).data('session-id');
            exportConversation(sessionId, format);
            dropdown.remove();
        });
        
        // Ukrywamy dropdown po kliknięciu poza nim
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.aica-export-dropdown, .aica-export-button').length) {
                dropdown.remove();
            }
        });
    }
    
    // Aktualizacja paginacji
    function updatePagination(currentPage, totalPages, totalItems) {
        // Usuwamy istniejącą paginację
        $('.aica-pagination').remove();
        
        if (totalPages <= 1) {
            return;
        }
        
        // Oblicz zakres wyświetlanych elementów
        const startItem = (currentPage - 1) * itemsPerPage + 1;
        const endItem = Math.min(currentPage * itemsPerPage, totalItems);
        
        // Przygotuj HTML dla paginacji
        let html = '<div class="aica-pagination">';
        
        // Przycisk "Poprzednia strona"
        if (currentPage > 1) {
            html += `
                <button type="button" class="aica-pagination-button aica-prev-page">
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                </button>
            `;
        } else {
            html += `
                <button type="button" class="aica-pagination-button" disabled>
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                </button>
            `;
        }
        
        // Informacja o stronie
        html += `
            <div class="aica-pagination-info">
                ${aica_history.i18n.pagination_info.replace('%1$s', startItem).replace('%2$s', endItem).replace('%3$s', totalItems)}
            </div>
        `;
        
        // Przycisk "Następna strona"
        if (currentPage < totalPages) {
            html += `
                <button type="button" class="aica-pagination-button aica-next-page">
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </button>
            `;
        } else {
            html += `
                <button type="button" class="aica-pagination-button" disabled>
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </button>
            `;
        }
        
        html += '</div>';
        
        // Dodaj paginację na koniec listy rozmów
        $('.aica-conversations-list').after(html);
        
        // Dodaj obsługę przycisków paginacji
        $('.aica-prev-page').on('click', function() {
            if (currentPage > 1) {
                const search = $('#aica-search-input').val();
                loadConversations(currentPage - 1, search);
            }
        });
        
        $('.aica-next-page').on('click', function() {
            if (currentPage < totalPages) {
                const search = $('#aica-search-input').val();
                loadConversations(currentPage + 1, search);
            }
        });
    }
    
    /**
     * Funkcje akcji na rozmowach
     */
    
    // Eksportowanie konwersacji
    function exportConversation(sessionId, format = 'json') {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aica_export_conversation',
                nonce: aica_history.nonce,
                session_id: sessionId,
                format: format
            },
            success: function(response) {
                if (response.success) {
                    // Pobierz dane do pobrania
                    const content = response.data.content;
                    const filename = response.data.filename;
                    const mime_type = response.data.mime_type;
                    
                    // Stwórz link do pobrania
                    const blob = new Blob([content], { type: mime_type });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = filename;
                    a.click();
                    URL.revokeObjectURL(url);
                } else {
                    alert(response.data ? response.data.message : 'Wystąpił błąd podczas eksportu rozmowy.');
                }
            },
            error: function() {
                alert('Wystąpił błąd podczas eksportu rozmowy.');
            }
        });
    }
    
    // Duplikowanie rozmowy
    function duplicateConversation(sessionId) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aica_duplicate_conversation',
                nonce: aica_history.nonce,
                session_id: sessionId
            },
            success: function(response) {
                if (response.success) {
                    alert(aica_history.i18n.duplicate_success);
                    loadConversations(currentPage);
                } else {
                    alert(response.data ? response.data.message : aica_history.i18n.duplicate_error);
                }
            },
            error: function() {
                alert(aica_history.i18n.duplicate_error);
            }
        });
    }
    
    // Usuwanie rozmowy
    function deleteConversation(sessionId) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aica_delete_session',
                nonce: aica_history.nonce,
                session_id: sessionId
            },
            success: function(response) {
                if (response.success) {
                    // Odśwież listę rozmów
                    loadConversations(currentPage);
                    
                    // Wyczyść widok wiadomości
                    $('.aica-messages-container').html('');
                    
                    // Resetuj aktywną rozmowę
                    currentSession = null;
                } else {
                    alert(response.data ? response.data.message : aica_history.i18n.delete_error);
                }
            },
            error: function() {
                alert(aica_history.i18n.delete_error);
            }
        });
    }
    
    /**
     * Funkcje pomocnicze
     */
    
    // Formatowanie czasu "temu"
    function formatTimeAgo(date) {
        const now = new Date();
        const diff = Math.floor((now - date) / 1000); // różnica w sekundach
        
        // Funkcja do formatowania liczby i jednostki
        function formatUnit(value, singular, plural) {
            if (value === 1) {
                return value + ' ' + (aica_history.i18n[singular] || singular);
            } else {
                return value + ' ' + (aica_history.i18n[plural] || plural);
            }
        }
        
        if (diff < 60) {
            // Mniej niż minuta
            return diff < 5 ? (aica_history.i18n.just_now || 'Przed chwilą') : formatUnit(diff, 'second', 'seconds') + ' ' + (aica_history.i18n.ago || 'temu');
        } else if (diff < 3600) {
            // Mniej niż godzina
            return formatUnit(Math.floor(diff / 60), 'minute', 'minutes') + ' ' + (aica_history.i18n.ago || 'temu');
        } else if (diff < 86400) {
            // Mniej niż dzień
            return formatUnit(Math.floor(diff / 3600), 'hour', 'hours') + ' ' + (aica_history.i18n.ago || 'temu');
        } else if (diff < 2592000) {
            // Mniej niż miesiąc (30 dni)
            return formatUnit(Math.floor(diff / 86400), 'day', 'days') + ' ' + (aica_history.i18n.ago || 'temu');
        } else if (diff < 31536000) {
            // Mniej niż rok
            return formatUnit(Math.floor(diff / 2592000), 'month', 'months') + ' ' + (aica_history.i18n.ago || 'temu');
        } else {
            // Więcej niż rok
            return formatUnit(Math.floor(diff / 31536000), 'year', 'years') + ' ' + (aica_history.i18n.ago || 'temu');
        }
    }
    
    /**
     * Inicjalizacja strony
     */
    
    // Obsługa wyszukiwania
    $('#aica-search-input').on('keyup', function(e) {
        const searchTerm = $(this).val().trim();
        
        // Jeśli wciśnięto Enter
        if (e.which === 13) {
            if (searchTerm.length < 3 && searchTerm.length > 0) {
                alert(aica_history.i18n.min_search_length || 'Wprowadź co najmniej 3 znaki, aby wyszukać.');
                return;
            }
            
            loadConversations(1, searchTerm);
        }
    });
    
    // Obsługa przycisku wyszukiwania
    $('#aica-search-button').on('click', function() {
        const searchTerm = $('#aica-search-input').val().trim();
        
        if (searchTerm.length < 3 && searchTerm.length > 0) {
            alert(aica_history.i18n.min_search_length || 'Wprowadź co najmniej 3 znaki, aby wyszukać.');
            return;
        }
        
        loadConversations(1, searchTerm);
    });
    
    // Obsługa filtrów
    $('.aica-filter-button').on('click', function() {
        $('.aica-filter-dropdown').toggle();
    });
    
    // Obsługa przycisku zastosuj filtry
    $('.aica-apply-filters').on('click', function() {
        const sort = $('input[name="sort"]:checked').val();
        const dateFrom = $('.aica-date-from').val();
        const dateTo = $('.aica-date-to').val();
        const search = $('#aica-search-input').val().trim();
        
        // Ukryj dropdown
        $('.aica-filter-dropdown').hide();
        
        // Aktualizacja danych filtrów
        const data = {
            action: 'aica_get_sessions_list',
            nonce: aica_history.nonce,
            page: 1,
            per_page: itemsPerPage,
            sort: sort
        };
        
        if (search) {
            data.search = search;
        }
        
        if (dateFrom) {
            data.date_from = dateFrom;
        }
        
        if (dateTo) {
            data.date_to = dateTo;
        }
        
        // Pokaż wskaźnik ładowania
        $('.aica-history-container').html('<div class="aica-loading"><div class="aica-loading-spinner"></div><p>' + (aica_history.i18n.loading || 'Ładowanie...') + '</p></div>');
        
        // Wykonaj nowe zapytanie
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    // Ukryj wskaźnik ładowania
                    $('.aica-loading').remove();
                    
                    // Przy pierwszym ładowaniu, utwórz kontener dla historii
                    if ($('.aica-conversations-list').length === 0 && $('.aica-messages-container').length === 0) {
                        $('.aica-history-container').html('<div class="aica-conversations-list"></div><div class="aica-messages-container"></div>');
                    }
                    
                    // Pobierz dane
                    const conversations = response.data.sessions;
                    const totalItems = response.data.pagination.total_items;
                    totalPages = Math.ceil(totalItems / itemsPerPage);
                    currentPage = 1;
                    
                    // Aktualizuj paginację
                    updatePagination(currentPage, totalPages, totalItems);
                    
                    // Generuj listę rozmów
                    renderConversationsList(conversations, search);
                    
                    // Resetuj aktywną rozmowę
                    currentSession = null;
                    
                    // Wyczyść widok wiadomości
                    $('.aica-messages-container').html('');
                } else {
                    // Pokaż komunikat o błędzie
                    $('.aica-history-container').html(`
                        <div class="aica-empty-state">
                            <div class="aica-empty-icon">
                                <span class="dashicons dashicons-warning"></span>
                            </div>
                            <p>${response.data ? response.data.message : (aica_history.i18n.load_error || 'Wystąpił błąd podczas ładowania danych.')}</p>
                        </div>
                    `);
                }
            },
            error: function() {
                // Ukryj wskaźnik ładowania
                $('.aica-loading').remove();
                
                // Pokaż komunikat o błędzie
                $('.aica-history-container').html(`
                    <div class="aica-empty-state">
                        <div class="aica-empty-icon">
                            <span class="dashicons dashicons-warning"></span>
                        </div>
                        <p>${aica_history.i18n.load_error || 'Wystąpił błąd podczas ładowania danych.'}</p>
                    </div>
                `);
            }
        });
    });
    
    // Obsługa przycisku reset filtrów
    $('.aica-reset-filters').on('click', function() {
        // Reset pól formularza
        $('input[name="sort"][value="newest"]').prop('checked', true);
        $('.aica-date-from').val('');
        $('.aica-date-to').val('');
        $('#aica-search-input').val('');
        
        // Ukryj dropdown
        $('.aica-filter-dropdown').hide();
        
        // Załaduj rozmowy bez filtrów
        loadConversations(1);
    });
    
    // Obsługa dialogu usuwania
    $('.aica-dialog-confirm').on('click', function() {
        const sessionId = $('#aica-delete-dialog').data('session-id');
        $('#aica-delete-dialog').hide();
        deleteConversation(sessionId);
    });
    
    $('.aica-dialog-cancel, .aica-dialog-close').on('click', function() {
        $('#aica-delete-dialog').hide();
    });
    
    // Zamykanie dropdown po kliknięciu poza nim
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.aica-filter-container').length) {
            $('.aica-filter-dropdown').hide();
        }
    });
    
    // Ładowanie rozmów przy starcie
    loadConversations(1);
});

/**
 * Obsługa historii czatu
 */
class ChatHistory {
    constructor() {
        this.currentSession = null;
        this.historyContainer = document.getElementById('chat-history');
        this.messageContainer = document.getElementById('chat-messages');
        this.loadMoreButton = document.getElementById('load-more-history');
        this.clearHistoryButton = document.getElementById('clear-history');
        
        this.init();
    }

    init() {
        if (this.loadMoreButton) {
            this.loadMoreButton.addEventListener('click', () => this.loadMoreHistory());
        }

        if (this.clearHistoryButton) {
            this.clearHistoryButton.addEventListener('click', () => this.clearHistory());
        }

        // Obsługa kliknięcia w sesję
        if (this.historyContainer) {
            this.historyContainer.addEventListener('click', (e) => {
                const sessionElement = e.target.closest('.chat-session');
                if (sessionElement) {
                    const sessionId = sessionElement.dataset.sessionId;
                    this.loadSession(sessionId);
                }
            });
        }
    }

    /**
     * Ładuje sesję
     */
    async loadSession(sessionId) {
        try {
            const response = await fetch(aica_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'aica_get_history',
                    session_id: sessionId,
                    nonce: aica_ajax.nonce
                })
            });

            const data = await response.json();
            if (data.success) {
                this.currentSession = data.data.session;
                this.displayHistory(data.data.history);
                this.updateSessionInfo();
            } else {
                throw new Error(data.data);
            }
        } catch (error) {
            console.error('Error loading session:', error);
            alert('Failed to load session: ' + error.message);
        }
    }

    /**
     * Wyświetla historię
     */
    displayHistory(history) {
        if (!this.messageContainer) return;

        this.messageContainer.innerHTML = '';
        history.forEach(message => {
            const messageElement = document.createElement('div');
            messageElement.className = `chat-message ${message.role}`;
            messageElement.innerHTML = `
                <div class="message-content">${message.content}</div>
                <div class="message-time">${this.formatTime(message.created_at)}</div>
            `;
            this.messageContainer.appendChild(messageElement);
        });

        this.messageContainer.scrollTop = this.messageContainer.scrollHeight;
    }

    /**
     * Aktualizuje informacje o sesji
     */
    updateSessionInfo() {
        if (!this.currentSession) return;

        const sessionInfo = document.getElementById('session-info');
        if (sessionInfo) {
            sessionInfo.innerHTML = `
                <h3>${this.currentSession.title || 'Untitled Session'}</h3>
                <p>Created: ${this.formatTime(this.currentSession.created_at)}</p>
                <p>Last modified: ${this.formatTime(this.currentSession.updated_at)}</p>
            `;
        }
    }

    /**
     * Czyści historię
     */
    async clearHistory() {
        if (!this.currentSession) return;

        if (!confirm('Are you sure you want to clear this chat history?')) {
            return;
        }

        try {
            const response = await fetch(aica_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'aica_clear_history',
                    session_id: this.currentSession.id,
                    nonce: aica_ajax.nonce
                })
            });

            const data = await response.json();
            if (data.success) {
                this.messageContainer.innerHTML = '';
                this.currentSession = null;
                this.updateSessionInfo();
            } else {
                throw new Error(data.data);
            }
        } catch (error) {
            console.error('Error clearing history:', error);
            alert('Failed to clear history: ' + error.message);
        }
    }

    /**
     * Formatuje czas
     */
    formatTime(timestamp) {
        return new Date(timestamp).toLocaleString();
    }
}

// Inicjalizacja
document.addEventListener('DOMContentLoaded', () => {
    new ChatHistory();
});