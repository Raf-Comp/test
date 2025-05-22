/**
 * Modern Chat JS 2025 - AI Chat Assistant
 * JavaScript dla interaktywnego czatu z Claude.ai
 */
(function($) {
    'use strict';
    
    // Zmienne globalne
    let currentSessionId = null;
    let currentFilePath = null;
    let currentRepositoryId = null;
    let currentRepositoryPath = '';
    let isProcessing = false;
    let currentPage = 1;
    let totalPages = 1;
    let messagesPerPage = 20;
    let isLoadingMoreMessages = false;
    let isDarkMode = localStorage.getItem('aica_dark_mode') === 'true';
    let isCompactMode = localStorage.getItem('aica_compact_mode') === 'true';
    
    // Cache service
    const CacheService = {
        storage: window.localStorage,
        prefix: 'aica_cache_',
        
        get(key) {
            try {
                const item = this.storage.getItem(this.prefix + key);
                if (!item) return null;
                
                const { value, expiry } = JSON.parse(item);
                if (expiry && expiry < Date.now()) {
                    this.delete(key);
                    return null;
                }
                
                return value;
            } catch (error) {
                console.error('Cache get error:', error);
                return null;
            }
        },
        
        set(key, value, ttl = 3600000) { // Default TTL: 1 hour
            try {
                const item = {
                    value,
                    expiry: ttl ? Date.now() + ttl : null
                };
                this.storage.setItem(this.prefix + key, JSON.stringify(item));
                return true;
            } catch (error) {
                console.error('Cache set error:', error);
                return false;
            }
        },
        
        delete(key) {
            try {
                this.storage.removeItem(this.prefix + key);
                return true;
            } catch (error) {
                console.error('Cache delete error:', error);
                return false;
            }
        },
        
        clear() {
            try {
                Object.keys(this.storage).forEach(key => {
                    if (key.startsWith(this.prefix)) {
                        this.storage.removeItem(key);
                    }
                });
                return true;
            } catch (error) {
                console.error('Cache clear error:', error);
                return false;
            }
        }
    };
    
    // Główna funkcja inicjalizująca
    function init() {
        // Inicjalizacja motywu
        updateTheme();
        
        // Inicjalizacja eventów
        setupEvents();
        
        // Wczytanie sesji z localStorage lub utworzenie nowej
        loadOrCreateSession();
        
        // Wczytanie listy repozytoriów
        loadRepositories();
        
        // Wyświetlenie aktualnie wybranego modelu (pobieranego z globalnych ustawień)
        $('#aica-model-name').text(aica_data.settings.claude_model || 'claude-3-haiku-20240307');
        
        // Inicjalizacja stanu przycisku wysyłania
        updateSendButtonState();
        
        console.log('AI Chat Assistant zainicjalizowany');
    }
    
    // Aktualizacja motywu strony
    function updateTheme() {
        if (isDarkMode) {
            $('body').addClass('dark-mode');
            $('#aica-theme-toggle').find('.dashicons')
                .removeClass('dashicons-admin-appearance')
                .addClass('dashicons-sun');
        } else {
            $('body').removeClass('dark-mode');
            $('#aica-theme-toggle').find('.dashicons')
                .removeClass('dashicons-sun')
                .addClass('dashicons-admin-appearance');
        }
        
        if (isCompactMode) {
            $('body').addClass('aica-compact-view');
        } else {
            $('body').removeClass('aica-compact-view');
        }
    }
    
    // Ustawienie obsługi eventów
    function setupEvents() {
        // Obsługa wysyłania wiadomości
        $('#aica-message-input').on('keydown', function(e) {
            // Wysłanie wiadomości na Enter (bez Shift)
            if (e.keyCode === 13 && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
            // Obsługa Ctrl+Enter dla nowej linii
            else if (e.ctrlKey && e.keyCode === 13) {
                const start = this.selectionStart;
                const end = this.selectionEnd;
                const value = this.value;
                this.value = value.substring(0, start) + '\n' + value.substring(end);
                this.selectionStart = this.selectionEnd = start + 1;
                e.preventDefault();
            }
        });

        // Obsługa kliknięcia przycisku wysyłania
        $('#aica-send-message').on('click', function() {
            sendMessage();
        });

        // Dynamiczne dostosowanie wysokości pola tekstowego
        $('#aica-message-input').on('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
            updateSendButtonState();
        });

        // Obsługa przycisków nawigacyjnych
        $('#aica-new-chat').on('click', createNewSession);
        
        // Obsługa przełącznika motywu
        $('#aica-theme-toggle').on('click', toggleDarkMode);
        
        // Obsługa przycisków zarządzania sesją
        $(document).on('click', '.aica-session-item', function() {
            const sessionId = $(this).data('session-id');
            loadSession(sessionId);
        });

        $(document).on('click', '.aica-delete-session', function(e) {
            e.stopPropagation();
            const sessionId = $(this).parent().data('session-id');
            showDeleteConfirmation(sessionId);
        });
        
        // Obsługa zakładek
        $('.aica-tab').on('click', function() {
            const tabId = $(this).data('tab');
            activateTab(tabId);
        });

        // Obsługa przycisku "załaduj więcej"
        $(document).on('click', '#aica-load-more', function() {
            if (!isLoadingMoreMessages) {
                loadMoreMessages();
            }
        });

        // Obsługa przycisków zarządzania repozytorium
        $(document).on('click', '.aica-repository-item', function() {
            const repoId = $(this).data('repo-id');
            loadRepository(repoId);
        });
        
        // Obsługa dropdown menu
        $('.aica-more-button').on('click', function(e) {
            e.stopPropagation();
            $('.aica-dropdown').toggleClass('open');
        });
        
        // Zamykanie dropdown menu po kliknięciu poza
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.aica-dropdown').length) {
                $('.aica-dropdown').removeClass('open');
            }
        });
        
        // Obsługa przykładowych promptów
        $('.aica-example-prompt').on('click', function() {
            $('#aica-message-input').val($(this).text().trim());
            $('#aica-message-input').trigger('input');
            $('#aica-welcome-screen').hide();
            $('#aica-messages').show();
        });
        
        // Obsługa akcji
        $('#aica-rename-chat').on('click', showRenameModal);
        $('#aica-delete-chat').on('click', function() {
            showDeleteConfirmation(currentSessionId);
        });
        
        // Obsługa modalu zmiany nazwy
        $('.aica-modal-confirm').on('click', renameConversation);
        $('.aica-modal-close, .aica-modal-cancel').on('click', hideModal);
        
        // Obsługa dialogu usuwania
        $('.aica-dialog-confirm').on('click', confirmDelete);
        $('.aica-dialog-close, .aica-dialog-cancel').on('click', hideDialog);
        
        // Obsługa przełącznika panelu bocznego
        $('.aica-sidebar-toggle').on('click', toggleSidebar);
        
        // Obsługa przesyłania plików
        $('#aica-upload-file').on('click', function() {
            $('#aica-file-input').click();
        });
        
        $('#aica-file-input').on('change', handleFileUpload);
        
        // Obsługa przewijania - wykrywanie, gdy użytkownik przewinie do góry
        $('#aica-messages-container').on('scroll', function() {
            if ($(this).scrollTop() < 50 && currentPage < totalPages && !isLoadingMoreMessages) {
                loadMoreMessages();
            }
        });
        
        // Obsługa responsywności na mobile
        setupMobileEvents();
    }
    
    // Obsługa wydarzeń mobilnych
    function setupMobileEvents() {
        // Dodajemy przycisk mobile toggle, jeśli jest potrzebny
        if (window.innerWidth <= 992 && !$('.aica-mobile-toggle').length) {
            $('body').append('<button class="aica-mobile-toggle"><span class="dashicons dashicons-menu-alt"></span></button>');
            $('body').append('<div class="aica-mobile-backdrop"></div>');
            
            $('.aica-mobile-toggle').on('click', function() {
                $('.aica-sidebar').toggleClass('open');
            });
            
            $('.aica-mobile-backdrop').on('click', function() {
                $('.aica-sidebar').removeClass('open');
            });
        }
        
        // Na resize okna
        $(window).on('resize', function() {
            if (window.innerWidth <= 992 && !$('.aica-mobile-toggle').length) {
                $('body').append('<button class="aica-mobile-toggle"><span class="dashicons dashicons-menu-alt"></span></button>');
                $('body').append('<div class="aica-mobile-backdrop"></div>');
                
                $('.aica-mobile-toggle').on('click', function() {
                    $('.aica-sidebar').toggleClass('open');
                });
                
                $('.aica-mobile-backdrop').on('click', function() {
                    $('.aica-sidebar').removeClass('open');
                });
            } else if (window.innerWidth > 992) {
                $('.aica-mobile-toggle').remove();
                $('.aica-mobile-backdrop').remove();
                $('.aica-sidebar').removeClass('open');
            }
        });
    }
    
    // Debouncing function
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    // Optimized updateSendButtonState with debouncing
    const updateSendButtonState = debounce(function() {
        const messageInput = $('#aica-message-input');
        const sendButton = $('#aica-send-message');
        
        if (messageInput.val().trim() === '' || isProcessing) {
            sendButton.prop('disabled', true);
        } else {
            sendButton.prop('disabled', false);
        }
    }, 100);
    
    // Optimized scrollToBottom with debouncing
    const scrollToBottom = debounce(function() {
        const container = $('#aica-messages-container');
        container.scrollTop(container.prop('scrollHeight'));
    }, 100);
    
    // Aktywacja zakładki
    function activateTab(tabId) {
        $('.aica-tab').removeClass('aica-tab-active');
        $(`.aica-tab[data-tab="${tabId}"]`).addClass('aica-tab-active');
        
        // Tutaj można dodać logikę ładowania zawartości zakładki
        if (tabId === 'history') {
            loadSessionsList();
        } else if (tabId === 'favorites') {
            loadFavoritesList();
        }
    }
    
    // Przełączanie sidebar
    function toggleSidebar() {
        $('.aica-sidebar').toggleClass('collapsed');
    }
    
    // Przełączanie trybu ciemnego
    function toggleDarkMode() {
        isDarkMode = !isDarkMode;
        localStorage.setItem('aica_dark_mode', isDarkMode);
        updateTheme();
    }
    
    // Funkcja do wysyłania wiadomości
    function sendMessage() {
        const messageInput = $('#aica-message-input');
        const message = messageInput.val().trim();
        
        if (message === '' || isProcessing) {
            return;
        }
        
        isProcessing = true;
        updateSendButtonState();
        
        // Dodanie wiadomości użytkownika do czatu
        appendMessage('user', message);
        
        // Wyczyszczenie pola input
        messageInput.val('');
        messageInput.css('height', 'auto');
        
        // Pokazanie wskaźnika pisania
        showTypingIndicator();
        
        // Wywołanie API Claude
        callClaudeAPI(message)
            .then(response => {
                hideTypingIndicator();
                appendMessage('assistant', response);
                saveMessage(message, response);
            })
            .catch(error => {
                hideTypingIndicator();
                showNotification('error', 'Wystąpił błąd podczas wysyłania wiadomości.');
                console.error('Błąd API:', error);
            })
            .finally(() => {
                isProcessing = false;
                updateSendButtonState();
            });
    }

    // Funkcja wywołująca API Claude
    function callClaudeAPI(message) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: aica_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'aica_send_message',
                    nonce: aica_data.nonce,
                    message: message,
                    session_id: currentSessionId
                },
                success: function(response) {
                    if (response.success) {
                        resolve(response.data.message);
                    } else {
                        reject(new Error(response.data.message));
                    }
                },
                error: function(xhr, status, error) {
                    reject(new Error(error));
                }
            });
        });
    }

    // Funkcja dodająca wiadomość do czatu
    function appendMessage(sender, content, timestamp = null) {
        const messageElement = createMessageElement(sender, content, timestamp || formatTimestamp(new Date()));
        $('#aica-messages').append(messageElement);
        scrollToBottom();
        
        // Inicjalizacja lazy loading dla nowych obrazów
        initLazyLoading();
    }

    // Funkcja do formatowania markdown w odpowiedzi
    function formatMarkdown(content) {
        try {
            // Obsługa bloków kodu
            content = content.replace(/```([a-z]*)\n([\s\S]*?)```/g, function(match, language, code) {
                return `<pre><code class="language-${language}">${escapeHTML(code)}</code></pre>`;
            });
            
            // Obsługa pojedynczych linii kodu
            content = content.replace(/`([^`]+)`/g, '<code>$1</code>');
            
            // Obsługa pogrubienia
            content = content.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
            
            // Obsługa kursywy
            content = content.replace(/\*([^*]+)\*/g, '<em>$1</em>');
            
            // Obsługa list nieuporządkowanych
            content = content.replace(/^\s*-\s+(.+)$/gm, '<li>$1</li>');
            content = content.replace(/(<li>.*<\/li>)/gs, '<ul>$1</ul>');
            
            // Obsługa list uporządkowanych
            content = content.replace(/^\s*(\d+)\.\s+(.+)$/gm, '<li>$2</li>');
            content = content.replace(/(<li>.*<\/li>)/gs, '<ul>$1</ul>');
            
            // Obsługa linków
            content = content.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank">$1</a>');
            
            // Obsługa obrazów z lazy loading
            content = content.replace(/!\[([^\]]*)\]\(([^)]+)\)/g, function(match, alt, src) {
                return `<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg'/%3E" 
                            data-src="${src}" 
                            alt="${alt || ''}" 
                            class="aica-lazy-image" 
                            loading="lazy" 
                            onerror="this.onerror=null; this.src='${aica_data.assets_url}/images/placeholder.png';" />`;
            });
            
            return content;
        } catch (e) {
            console.error('Błąd formatowania markdown:', e);
            return content;
        }
    }

    // Funkcja do escapowania HTML
    function escapeHTML(html) {
        return html
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Pokazanie wskaźnika pisania
    function showTypingIndicator() {
        const indicator = $(`
            <div class="aica-message aica-ai-message">
                <div class="aica-message-bubble">
                    <div class="aica-typing-indicator">
                        <div class="aica-typing-dot"></div>
                        <div class="aica-typing-dot"></div>
                        <div class="aica-typing-dot"></div>
                    </div>
                </div>
            </div>
        `);
        
        $('#aica-messages').append(indicator);
        scrollToBottom();
    }
    
    // Ukrycie wskaźnika pisania
    function hideTypingIndicator() {
        $('.aica-typing-indicator').parent().parent().remove();
    }

    // Funkcje zarządzania sesją
    function loadOrCreateSession() {
        const savedSessionId = localStorage.getItem('currentSessionId');
        
        if (savedSessionId) {
            loadSession(savedSessionId);
        } else {
            createNewSession();
        }
    }

    function createNewSession() {
        $.ajax({
            url: aica_data.ajax_url,
            type: 'POST',
            data: {
                action: 'aica_create_session',
                nonce: aica_data.nonce
            },
            success: function(response) {
                if (response.success) {
                    currentSessionId = response.data.session_id;
                    localStorage.setItem('currentSessionId', currentSessionId);
                    
                    // Wyczyszczenie czatu
                    $('#aica-messages').empty();
                    
                    // Ukrycie ekranu powitalnego, pokazanie czatu
                    $('#aica-welcome-screen').hide();
                    $('#aica-messages').show();
                    
                    // Aktualizacja tytułu
                    $('.aica-conversation-title').text('Nowa rozmowa');
                    $('.aica-conversation-date').text('');
                    
                    // Aktualizacja listy sesji
                    loadSessionsList();
                } else {
                    showNotification('error', 'Nie udało się utworzyć nowej sesji.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Błąd tworzenia sesji:', error);
                showNotification('error', 'Wystąpił błąd podczas tworzenia sesji.');
            }
        });
    }

    function loadSession(sessionId) {
        currentSessionId = sessionId;
        localStorage.setItem('currentSessionId', sessionId);
        
        // Wyczyszczenie czatu
        $('#aica-messages').empty();
        showLoadingSpinner();
        
        // Ukrycie ekranu powitalnego, pokazanie czatu
        $('#aica-welcome-screen').hide();
        $('#aica-messages').show();
        
        // Wywołanie AJAX do pobrania historii czatu
        $.ajax({
            url: aica_data.ajax_url,
            type: 'POST',
            data: {
                action: 'aica_get_chat_history',
                nonce: aica_data.nonce,
                session_id: sessionId,
                page: 1,
                per_page: messagesPerPage
            },
            success: function(response) {
                hideLoadingSpinner();
                
                if (response.success) {
                    const historyData = response.data;
                    
                    // Aktualizacja zmiennych paginacji
                    currentPage = historyData.pagination.current_page;
                    totalPages = historyData.pagination.total_pages;
                    
                    // Dodanie wiadomości do czatu
                    historyData.messages.forEach(msg => {
                        appendMessage(msg.type, msg.content, formatTimestamp(msg.time));
                    });
                    
                    // Aktualizacja tytułu i daty
                    $('.aica-conversation-title').text(historyData.title || 'Nowa rozmowa');
                    $('.aica-conversation-date').text(formatTimestamp(historyData.created_at));
                    
                    // Jeśli są dostępne wcześniejsze strony, dodaj przycisk "załaduj więcej"
                    if (currentPage < totalPages) {
                        showLoadMoreButton();
                    }
                    
                    // Inicjalizacja podświetlania składni, jeśli jest dostępne
                    if (window.Prism) {
                        try {
                            Prism.highlightAll();
                        } catch (e) {
                            console.error('Błąd podświetlania składni:', e);
                        }
                    }
                } else {
                    showNotification('error', 'Nie udało się załadować historii czatu.');
                }
            },
            error: function(xhr, status, error) {
                hideLoadingSpinner();
                console.error('Błąd ładowania historii czatu:', error);
                showNotification('error', 'Wystąpił błąd podczas ładowania historii.');
            }
        });
        
        // Schowanie panelu bocznego na mobilce po wybraniu sesji
        if (window.innerWidth <= 992) {
            $('.aica-sidebar').removeClass('open');
        }
    }
    
    // Formatowanie znacznika czasowego dla wyświetlania
    function formatTimestamp(timestamp) {
        const date = new Date(timestamp);
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
    
    // Funkcja ładująca więcej wiadomości (paginacja)
    function loadMoreMessages() {
        if (isLoadingMoreMessages || currentPage >= totalPages) {
            return;
        }
        
        isLoadingMoreMessages = true;
        
        // Zmiana tekstu przycisku "załaduj więcej"
        $('#aica-load-more').html('<div class="aica-spinner"></div>');
        
        // Wywołanie AJAX do pobrania kolejnej strony wiadomości
        $.ajax({
            url: aica_data.ajax_url,
            type: 'POST',
            data: {
                action: 'aica_get_chat_history',
                nonce: aica_data.nonce,
                session_id: currentSessionId,
                page: currentPage + 1,
                per_page: messagesPerPage
            },
            success: function(response) {
                if (response.success) {
                    const historyData = response.data;
                    
                    // Aktualizacja zmiennych paginacji
                    currentPage = historyData.pagination.current_page;
                    totalPages = historyData.pagination.total_pages;
                    
                    // Dodawanie starszych wiadomości na górze
                    const chatContainer = $('#aica-messages');
                    const scrollPos = $('#aica-messages-container').scrollTop();
                    const scrollHeight = $('#aica-messages-container').prop('scrollHeight');
                    
                    // Dodawanie starszych wiadomości na początku
                    historyData.messages.forEach(msg => {
                        const messageElement = createMessageElement(msg.type, msg.content, formatTimestamp(msg.time));
                        chatContainer.prepend(messageElement);
                    });
                    
                    // Inicjalizacja podświetlania składni i lazy loading
                    if (window.Prism) {
                        try {
                            Prism.highlightAll();
                        } catch (e) {
                            console.error('Błąd podświetlania składni:', e);
                        }
                    }
                    initLazyLoading();
                    
                    // Zachowanie pozycji przewijania
                    const newScrollHeight = $('#aica-messages-container').prop('scrollHeight');
                    $('#aica-messages-container').scrollTop(scrollPos + (newScrollHeight - scrollHeight));
                    
                    // Jeśli są dostępne wcześniejsze strony, zaktualizuj przycisk "załaduj więcej"
                    if (currentPage < totalPages) {
                        $('#aica-load-more').text('Załaduj wcześniejsze wiadomości');
                    } else {
                        $('#aica-load-more').remove();
                    }
                } else {
                    console.error('Błąd pobierania historii:', response.data.message);
                    showNotification('error', 'Nie udało się załadować wcześniejszych wiadomości.');
                }
                
                isLoadingMoreMessages = false;
            },
            error: function(xhr, status, error) {
                console.error('Błąd ładowania historii czatu:', error);
                $('#aica-load-more').text('Załaduj wcześniejsze wiadomości');
                isLoadingMoreMessages = false;
                showNotification('error', 'Wystąpił błąd podczas ładowania wiadomości.');
            }
        });
    }
    
    // Tworzenie elementu wiadomości
    function createMessageElement(sender, content, timestamp) {
        const messageClass = sender === 'user' ? 'aica-user-message' : 
                           (sender === 'assistant' ? 'aica-ai-message' : 'aica-system-message');
        
        // Formatowanie kodu markdown, jeśli treść zawiera kod
        if (sender === 'assistant' || sender === 'system') {
            content = formatMarkdown(content);
        }
        
        const messageElement = $(`<div class="aica-message ${messageClass}"></div>`);
        const messageBubble = $('<div class="aica-message-bubble"></div>');
        messageBubble.html(content);
        messageElement.append(messageBubble);
        
        // Dodanie znacznika czasu
        const timeElement = $('<div class="aica-message-info"></div>');
        timeElement.text(timestamp);
        messageElement.append(timeElement);
        
        return messageElement;
    }
    
    // Pokazanie przycisku "załaduj więcej"
    function showLoadMoreButton() {
        if (!$('#aica-load-more').length) {
            const loadMoreButton = $(`
                <div id="aica-load-more" class="aica-load-more">
                    Załaduj wcześniejsze wiadomości
                </div>
            `);
            
            $('#aica-messages').prepend(loadMoreButton);
        }
    }
    
    // Funkcja zapisująca konwersację
    function saveConversation(userMessage, assistantResponse) {
        $.ajax({
            url: aica_data.ajax_url,
            type: 'POST',
            data: {
                action: 'aica_save_conversation',
                nonce: aica_data.nonce,
                session_id: currentSessionId,
                user_message: userMessage,
                assistant_response: assistantResponse
            },
            success: function(response) {
                if (!response.success) {
                    console.error('Błąd zapisywania konwersacji:', response.data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Błąd zapisywania konwersacji:', error);
            }
        });
    }
    
    // Zapisanie wiadomości do bazy danych (dokończenie funkcji saveMessage)
    function saveMessage(userMessage, assistantResponse) {
        $.ajax({
            url: aica_data.ajax_url,
            type: 'POST',
            data: {
                action: 'aica_save_message',
                nonce: aica_data.nonce,
                session_id: currentSessionId,
                user_message: userMessage,
                assistant_response: assistantResponse
            },
            success: function(response) {
                if (response.success) {
                    // Aktualizacja listy sesji
                    loadSessionsList();
                } else {
                    console.error('Błąd zapisywania wiadomości:', response.data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Błąd zapisywania wiadomości:', error);
            }
        });
    }
    
    // Ładowanie listy sesji
    function loadSessionsList() {
        $.ajax({
            url: aica_data.ajax_url,
            type: 'POST',
            data: {
                action: 'aica_get_sessions',
                nonce: aica_data.nonce
            },
            success: function(response) {
                if (response.success) {
                    renderSessionsList(response.data.sessions);
                } else {
                    console.error('Błąd pobierania listy sesji:', response.data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Błąd pobierania listy sesji:', error);
            }
        });
    }
    
    // Virtualized list rendering for sessions
    function renderSessionsList(sessions) {
        const sessionsList = $('.aica-sessions-list');
        sessionsList.empty();
        
        if (sessions.length === 0) {
            sessionsList.append('<div class="aica-empty-state">Brak zapisanych rozmów</div>');
            return;
        }
        
        const itemHeight = 80; // Approximate height of each session item
        const containerHeight = sessionsList.height();
        const visibleItems = Math.ceil(containerHeight / itemHeight);
        const startIndex = Math.max(0, Math.floor(sessionsList.scrollTop() / itemHeight));
        const endIndex = Math.min(sessions.length, startIndex + visibleItems + 2);
        
        const fragment = document.createDocumentFragment();
        
        for (let i = startIndex; i < endIndex; i++) {
            const session = sessions[i];
            const sessionElement = $(`
                <div class="aica-session-item ${session.id === currentSessionId ? 'active' : ''}" 
                     data-session-id="${session.id}"
                     style="height: ${itemHeight}px;">
                    <div class="aica-session-header">
                        <div class="aica-session-title">${session.title || 'Nowa rozmowa'}</div>
                        <div class="aica-session-actions">
                            <button class="aica-session-action aica-delete-session" title="Usuń rozmowę">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </div>
                    </div>
                    <div class="aica-session-preview">${session.preview || ''}</div>
                    <div class="aica-session-date">${formatTimestamp(session.created_at)}</div>
                </div>
            `);
            
            fragment.appendChild(sessionElement[0]);
        }
        
        sessionsList.append(fragment);
        
        // Update scroll position
        sessionsList.on('scroll', debounce(function() {
            renderSessionsList(sessions);
        }, 100));
    }
    
    // Obsługa repozytoriów
    function loadRepositories() {
        $.ajax({
            url: aica_data.ajax_url,
            type: 'POST',
            data: {
                action: 'aica_get_repositories',
                nonce: aica_data.nonce
            },
            success: function(response) {
                const repositoriesList = $('#aica-repositories-list');
                
                if (!response.success || !response.data.repositories || response.data.repositories.length === 0) {
                    repositoriesList.html('<div class="aica-empty-message">Brak dostępnych repozytoriów</div>');
                    return;
                }
                
                repositoriesList.empty();
                
                response.data.repositories.forEach(repo => {
                    const repoItem = $(`
                        <div class="aica-repository-item" data-repo-id="${repo.id}">
                            <div class="aica-repo-icon">
                                <span class="dashicons dashicons-code-standards"></span>
                            </div>
                            <div class="aica-repo-info">
                                <div class="aica-repo-title">${repo.name}</div>
                                <div class="aica-repo-description">${repo.description || 'Brak opisu'}</div>
                            </div>
                        </div>
                    `);
                    
                    repositoriesList.append(repoItem);
                });
            },
            error: function(xhr, status, error) {
                console.error('Błąd ładowania repozytoriów:', error);
                $('#aica-repositories-list').html('<div class="aica-empty-message">Wystąpił błąd podczas ładowania repozytoriów</div>');
            }
        });
    }
    
    // Funkcja do ładowania repozytorium
    function loadRepository(repoId) {
        // Zapisanie bieżącego repozytorium
        currentRepositoryId = repoId;
        
        // Pokazanie wskaźnika ładowania
        $('#aica-repositories-list').html('<div class="aica-loading"><div class="aica-spinner"></div><span>Ładowanie plików repozytorium...</span></div>');
        
        // Wywołanie AJAX do pobrania plików repozytorium
        $.ajax({
            url: aica_data.ajax_url,
            type: 'POST',
            data: {
                action: 'aica_get_repository_files',
                nonce: aica_data.nonce,
                repository_id: repoId
            },
            success: function(response) {
                if (response.success) {
                    renderRepositoryFiles(response.data.files, response.data.repository);
                } else {
                    console.error('Błąd pobierania plików repozytorium:', response.data.message);
                    $('#aica-repositories-list').html('<div class="aica-empty-message">Nie udało się załadować plików.</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Błąd podczas pobierania plików repozytorium:', error);
                $('#aica-repositories-list').html('<div class="aica-empty-message">Wystąpił błąd podczas ładowania plików.</div>');
            }
        });
    }
    
    // Funkcja do renderowania plików repozytorium
    function renderRepositoryFiles(files, repository) {
        const repositoriesList = $('#aica-repositories-list');
        
        // Jeśli nie ma plików, pokaż komunikat
        if (files.length === 0) {
            repositoriesList.html('<div class="aica-empty-message">Brak plików w repozytorium.</div>');
            return;
        }
        
        repositoriesList.empty();
        
        // Dodanie nagłówka repozytorium
        repositoriesList.append(`
            <div class="aica-repository-header">
                <div class="aica-repository-title">
                    <h3>${repository.name}</h3>
                    <p>${repository.description || ''}</p>
                </div>
                <div class="aica-repository-path">
                    <span>${currentRepositoryPath || '/'}</span>
                </div>
            </div>
        `);
        
        // Dodanie przycisku powrotu, jeśli nie jesteśmy w katalogu głównym
        if (currentRepositoryPath !== '') {
            const parentPath = currentRepositoryPath.split('/').slice(0, -1).join('/');
            
            repositoriesList.append(`
                <div class="aica-file-item aica-directory" data-path="${parentPath}">
                    <div class="aica-file-icon">
                        <span class="dashicons dashicons-arrow-left-alt"></span>
                    </div>
                    <div class="aica-file-info">
                        <div class="aica-file-name">Powrót do katalogu nadrzędnego</div>
                    </div>
                </div>
            `);
        }
        
        // Sortowanie plików - katalogi na początku, potem pliki
        files.sort((a, b) => {
            if (a.type === 'dir' && b.type !== 'dir') return -1;
            if (a.type !== 'dir' && b.type === 'dir') return 1;
            return a.name.localeCompare(b.name);
        });
        
        // Dodanie plików i katalogów
        files.forEach(file => {
            const isDirectory = file.type === 'dir';
            const icon = isDirectory ? 'dashicons-category' : 'dashicons-media-text';
            
            const fileItem = $(`
                <div class="aica-file-item ${isDirectory ? 'aica-directory' : 'aica-file'}" data-path="${file.path}">
                    <div class="aica-file-icon">
                        <span class="dashicons ${icon}"></span>
                    </div>
                    <div class="aica-file-info">
                        <div class="aica-file-name">${file.name}</div>
                        ${!isDirectory ? `<div class="aica-file-size">${formatFileSize(file.size)}</div>` : ''}
                    </div>
                </div>
            `);
            
            // Dodanie obsługi kliknięcia
            fileItem.on('click', function() {
                const path = $(this).data('path');
                
                if (isDirectory) {
                    // Aktualizacja ścieżki i załadowanie plików
                    currentRepositoryPath = path;
                    loadRepository(currentRepositoryId);
                } else {
                    // Załadowanie zawartości pliku
                    loadFileContent(currentRepositoryId, path);
                }
            });
            
            repositoriesList.append(fileItem);
        });
    }
    
    // Funkcja do formatowania rozmiaru pliku
    function formatFileSize(size) {
        if (size < 1024) {
            return size + ' B';
        } else if (size < 1024 * 1024) {
            return (size / 1024).toFixed(1) + ' KB';
        } else if (size < 1024 * 1024 * 1024) {
            return (size / (1024 * 1024)).toFixed(1) + ' MB';
        } else {
            return (size / (1024 * 1024 * 1024)).toFixed(1) + ' GB';
        }
    }
    
    // Funkcja do ładowania zawartości pliku
    function loadFileContent(repoId, filePath) {
        // Zapisanie bieżącej ścieżki pliku
        currentFilePath = filePath;
        
        // Pokazanie wskaźnika ładowania
        $('#aica-repositories-list').html('<div class="aica-loading"><div class="aica-spinner"></div><span>Ładowanie zawartości pliku...</span></div>');
        
        // Wywołanie AJAX do pobrania zawartości pliku
        $.ajax({
            url: aica_data.ajax_url,
            type: 'POST',
            data: {
                action: 'aica_get_file_content',
                nonce: aica_data.nonce,
                repository_id: repoId,
                file_path: filePath
            },
            success: function(response) {
                if (response.success) {
                    renderFileContent(response.data.content, response.data.file_info);
                } else {
                    console.error('Błąd pobierania zawartości pliku:', response.data.message);
                    $('#aica-repositories-list').html('<div class="aica-empty-message">Nie udało się załadować zawartości pliku.</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Błąd podczas pobierania zawartości pliku:', error);
                $('#aica-repositories-list').html('<div class="aica-empty-message">Wystąpił błąd podczas ładowania zawartości pliku.</div>');
            }
        });
    }
    
    // Funkcja do renderowania zawartości pliku
    function renderFileContent(content, fileInfo) {
        const repositoriesList = $('#aica-repositories-list');
        repositoriesList.empty();
        
        // Dodanie nagłówka pliku
        repositoriesList.append(`
            <div class="aica-file-header">
                <div class="aica-file-title">
                    <h3>${fileInfo.name}</h3>
                    <p>${fileInfo.path}</p>
                </div>
                <div class="aica-file-actions">
                    <button type="button" class="aica-button aica-button-secondary aica-back-button">
                        <span class="dashicons dashicons-arrow-left-alt"></span>
                        Powrót
                    </button>
                    <button type="button" class="aica-button aica-button-primary aica-analyze-button">
                        <span class="dashicons dashicons-admin-tools"></span>
                        Analizuj plik
                    </button>
                </div>
            </div>
        `);
        
        // Dodanie zawartości pliku
        repositoriesList.append(`
            <div class="aica-file-content">
                <pre><code class="language-${getLanguageFromExtension(fileInfo.extension)}">${escapeHTML(content)}</code></pre>
            </div>
        `);
        
        // Inicjalizacja podświetlania składni, jeśli jest dostępne
        if (window.Prism) {
            try {
                Prism.highlightAll();
            } catch (e) {
                console.error('Błąd podświetlania składni:', e);
            }
        }
        
        // Obsługa przycisku powrotu
        $('.aica-back-button').on('click', function() {
            loadRepository(currentRepositoryId);
        });
        
        // Obsługa przycisku analizy
        $('.aica-analyze-button').on('click', function() {
            // Dodanie zawartości pliku do pola tekstowego
            const messageInput = $('#aica-message-input');
            const currentText = messageInput.val();
            const fileAnalysisPrompt = `Proszę przeanalizować ten kod:\n\n\`\`\`${fileInfo.extension}\n${content}\n\`\`\`\n\nCzy możesz wyjaśnić co robi, zidentyfikować potencjalne problemy lub zasugerować optymalizacje?`;
            
            messageInput.val(currentText ? `${currentText}\n\n${fileAnalysisPrompt}` : fileAnalysisPrompt);
            messageInput.trigger('input');
            
            // Przełączenie na zakładkę czatu
            activateTab('chats');
            
            // Przewinięcie do pola tekstowego
            $('html, body').animate({
                scrollTop: $('#aica-message-input').offset().top - 200
            }, 500);
        });
    }
    
    // Funkcja do określania języka na podstawie rozszerzenia pliku
    function getLanguageFromExtension(extension) {
        const languageMap = {
            'php': 'php',
            'js': 'javascript',
            'jsx': 'jsx',
            'ts': 'typescript',
            'tsx': 'tsx',
            'html': 'html',
            'css': 'css',
            'scss': 'scss',
            'sass': 'sass',
            'json': 'json',
            'xml': 'xml',
            'md': 'markdown',
            'py': 'python',
            'rb': 'ruby',
            'java': 'java',
            'c': 'c',
            'cpp': 'cpp',
            'cs': 'csharp',
            'go': 'go',
            'rs': 'rust',
            'swift': 'swift',
            'kt': 'kotlin',
            'sql': 'sql',
            'sh': 'bash',
            'yml': 'yaml',
            'yaml': 'yaml'
        };
        
        return languageMap[extension.toLowerCase()] || 'plaintext';
    }
    
    // Obsługa plików
    function handleFileUpload(event) {
        const file = event.target.files[0];
        
        if (!file) {
            return;
        }
        
        // Pokazanie wskaźnika ładowania
        showLoadingSpinner();
        
        // Utworzenie obiektu FormData do przesłania pliku
        const formData = new FormData();
        formData.append('action', 'aica_upload_file');
        formData.append('nonce', aica_data.nonce);
        formData.append('file', file);
        
        // Wysłanie pliku na serwer
        $.ajax({
            url: aica_data.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                hideLoadingSpinner();
                
                if (response.success) {
                    // Aktualizacja UI z informacją o wybranym pliku
                    $('#aica-selected-file-name').text(file.name);
                    $('#aica-selected-file-info').show();
                    
                    // Dodanie treści pliku do inputu
                    const currentText = $('#aica-message-input').val();
                    const fileQuestion = `Proszę przeanalizować ten kod:\n\n\`\`\`${getLanguageFromExtension(file.name.split('.').pop())}\n${response.data.file_content}\n\`\`\``;
                    
                    $('#aica-message-input').val(currentText ? `${currentText}\n\n${fileQuestion}` : fileQuestion);
                    $('#aica-message-input').trigger('input');
                } else {
                    showNotification('error', response.data.message || 'Nie udało się przesłać pliku.');
                }
            },
            error: function(xhr, status, error) {
                hideLoadingSpinner();
                console.error('Błąd podczas przesyłania pliku:', error);
                showNotification('error', 'Wystąpił błąd podczas przesyłania pliku.');
            }
        });
    }
    
    // Usuwanie wybranego pliku
    function removeSelectedFile() {
        $('#aica-selected-file-info').hide();
        $('#aica-file-upload').val('');
    }
    
    // Eksportowanie rozmowy
    function exportConversation() {
        if (!currentSessionId) {
            showNotification('error', 'Brak aktywnej rozmowy do eksportu.');
            return;
        }
        
        // Pobieranie wiadomości z czatu
        const messages = [];
        $('#aica-messages .aica-message').each(function() {
            const sender = $(this).hasClass('aica-user-message') ? 'Użytkownik' : 'Claude';
            const content = $(this).find('.aica-message-bubble').html();
            const time = $(this).find('.aica-message-info').text();
            
            messages.push(`[${time}] ${sender}:\n${$(this).find('.aica-message-bubble').text()}`);
        });
        
        // Tworzenie zawartości pliku
        const title = $('#aica-conversation-title').text();
        const date = $('#aica-conversation-date').text();
        const content = `# ${title}\n${date}\n\n${messages.join('\n\n')}`;
        
        // Tworzenie i pobieranie pliku
        const blob = new Blob([content], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `${title.replace(/[^a-z0-9]/gi, '_').toLowerCase()}_${Date.now()}.md`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
        
        showNotification('success', 'Rozmowa została wyeksportowana.');
    }
    
    // Kopiowanie całej rozmowy
    function copyConversation() {
        if (!currentSessionId) {
            showNotification('error', 'Brak aktywnej rozmowy do skopiowania.');
            return;
        }
        
        // Pobieranie wiadomości z czatu
        const messages = [];
        $('#aica-messages .aica-message').each(function() {
            const sender = $(this).hasClass('aica-user-message') ? 'Użytkownik' : 'Claude';
            
            messages.push(`${sender}: ${$(this).find('.aica-message-bubble').text()}`);
        });
        
        // Kopiowanie do schowka
        const content = messages.join('\n\n');
        navigator.clipboard.writeText(content).then(() => {
            showNotification('success', 'Rozmowa została skopiowana do schowka.');
        }).catch(err => {
            console.error('Nie udało się skopiować tekstu: ', err);
            showNotification('error', 'Nie udało się skopiować rozmowy.');
        });
    }
    
    // Pokazanie modalu zmiany nazwy
    function showRenameModal() {
        if (!currentSessionId) {
            showNotification('error', 'Brak aktywnej rozmowy do zmiany nazwy.');
            return;
        }
        
        $('#aica-conversation-new-title').val($('#aica-conversation-title').text());
        $('#aica-rename-modal').css('display', 'flex');
        $('#aica-conversation-new-title').focus();
    }
    
    // Zmiana nazwy rozmowy
    function renameConversation() {
        const newTitle = $('#aica-conversation-new-title').val().trim();
        
        if (!newTitle) {
            showNotification('error', 'Nazwa rozmowy nie może być pusta.');
            return;
        }
        
        $.ajax({
            url: aica_data.ajax_url,
            type: 'POST',
            data: {
                action: 'aica_rename_session',
                nonce: aica_data.nonce,
                session_id: currentSessionId,
                title: newTitle
            },
            success: function(response) {
                if (response.success) {
                    // Aktualizacja UI
                    $('#aica-conversation-title').text(newTitle);
                    
                    // Aktualizacja listy sesji
                    loadSessionsList();
                    
                    hideModal();
                    showNotification('success', 'Nazwa rozmowy została zmieniona.');
                } else {
                    console.error('Błąd zmiany nazwy:', response.data.message);
                    showNotification('error', 'Nie udało się zmienić nazwy rozmowy.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Błąd podczas zmiany nazwy rozmowy:', error);
                showNotification('error', 'Wystąpił błąd podczas zmiany nazwy rozmowy.');
            }
        });
    }
    
    // Ukrycie modalu
    function hideModal() {
        $('#aica-rename-modal').hide();
    }
    
    // Pokazanie dialogu potwierdzenia usunięcia
    function showDeleteConfirmation(sessionId = null) {
        // Jeśli podano ID sesji, zapisz je tymczasowo
        if (sessionId) {
            $('#aica-delete-dialog').data('session-id', sessionId);
        } else if (currentSessionId) {
            $('#aica-delete-dialog').data('session-id', currentSessionId);
        } else {
            showNotification('error', 'Brak rozmowy do usunięcia.');
            return;
        }
        
        $('#aica-delete-dialog').css('display', 'flex');
    }
    
    // Usunięcie rozmowy po potwierdzeniu
    function confirmDelete() {
        const sessionId = $('#aica-delete-dialog').data('session-id');
        
        if (!sessionId) {
            hideDialog();
            return;
        }
        
        $.ajax({
            url: aica_data.ajax_url,
            type: 'POST',
            data: {
                action: 'aica_delete_session',
                nonce: aica_data.nonce,
                session_id: sessionId
            },
            success: function(response) {
                if (response.success) {
                    // Jeśli usuwamy aktualną sesję, stwórz nową
                    if (sessionId === currentSessionId) {
                        createNewSession();
                    }
                    
                    // Aktualizacja listy sesji
                    loadSessionsList();
                    
                    hideDialog();
                    showNotification('success', 'Rozmowa została usunięta.');
                } else {
                    console.error('Błąd usuwania sesji:', response.data.message);
                    showNotification('error', 'Nie udało się usunąć rozmowy.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Błąd usuwania sesji:', error);
                showNotification('error', 'Wystąpił błąd podczas usuwania rozmowy.');
            }
        });
    }
    
    // Ukrycie dialogu
    function hideDialog() {
        $('#aica-delete-dialog').hide();
        $('#aica-delete-dialog').removeData('session-id');
    }
    
    // Pokazanie powiadomienia
    function showNotification(type, message) {
        // Usuń istniejące powiadomienia
        $('.aica-notification').remove();
        
        // Utwórz nowe powiadomienie
        const notification = $(`
            <div class="aica-notification aica-notification-${type}">
                <div class="aica-notification-content">
                    <span class="aica-notification-message">${message}</span>
                    <button class="aica-notification-close">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
            </div>
        `);
        
        // Dodaj powiadomienie do dokumentu
        $('body').append(notification);
        
        // Ustaw event na przycisk zamknięcia
        notification.find('.aica-notification-close').on('click', function() {
            notification.remove();
        });
        
        // Automatyczne zamknięcie po 5 sekundach
        setTimeout(function() {
            notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    // Pokazanie spinnera ładowania
    function showLoadingSpinner() {
        // Jeśli już istnieje, nie twórz nowego
        if ($('.aica-loading').length) {
            return;
        }
        
        const spinner = $(`
            <div class="aica-loading">
                <div class="aica-spinner"></div>
                <span>Ładowanie...</span>
            </div>
        `);
        
        $('#aica-messages').append(spinner);
    }
    
    // Ukrycie spinnera ładowania
    function hideLoadingSpinner() {
        $('.aica-loading').remove();
    }
    
    // Funkcja do inicjalizacji lazy loading dla obrazów
    function initLazyLoading() {
        // Sprawdź czy IntersectionObserver jest dostępny
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        const src = img.getAttribute('data-src');
                        
                        if (src) {
                            img.src = src;
                            img.classList.add('aica-image-loaded');
                            observer.unobserve(img);
                        }
                    }
                });
            }, {
                rootMargin: '50px 0px',
                threshold: 0.01
            });

            // Obserwuj wszystkie obrazy z klasą aica-lazy-image
            document.querySelectorAll('.aica-lazy-image').forEach(img => {
                imageObserver.observe(img);
            });
        } else {
            // Fallback dla przeglądarek bez wsparcia IntersectionObserver
            document.querySelectorAll('.aica-lazy-image').forEach(img => {
                const src = img.getAttribute('data-src');
                if (src) {
                    img.src = src;
                    img.classList.add('aica-image-loaded');
                }
            });
        }
    }
    
    // Inicjalizacja
    $(document).ready(function() {
        init();
    });

    // Initialize Prism
    if (typeof Prism !== 'undefined') {
        // Add line numbers plugin
        Prism.plugins.lineNumbers = {
            init: function() {
                this.addLineNumbers();
                this.addCopyButton();
            },

            addLineNumbers: function() {
                const codeBlocks = document.querySelectorAll('.aica-message pre code');
                
                codeBlocks.forEach(block => {
                    if (!block.parentElement.classList.contains('line-numbers')) {
                        block.parentElement.classList.add('line-numbers');
                        
                        // Create line numbers container
                        const lineNumbers = document.createElement('span');
                        lineNumbers.className = 'line-numbers-rows';
                        
                        // Count lines
                        const lines = block.textContent.split('\n');
                        lines.forEach(() => {
                            const span = document.createElement('span');
                            lineNumbers.appendChild(span);
                        });
                        
                        block.parentElement.appendChild(lineNumbers);
                    }
                });
            },

            addCopyButton: function() {
                const codeBlocks = document.querySelectorAll('.aica-message pre');
                
                codeBlocks.forEach(block => {
                    if (!block.querySelector('.aica-code-copy')) {
                        const copyButton = document.createElement('button');
                        copyButton.className = 'aica-code-copy';
                        copyButton.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>';
                        
                        copyButton.addEventListener('click', function() {
                            const code = block.querySelector('code').textContent;
                            navigator.clipboard.writeText(code).then(() => {
                                // Show success state
                                copyButton.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>';
                                copyButton.classList.add('copied');
                                
                                setTimeout(() => {
                                    copyButton.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>';
                                    copyButton.classList.remove('copied');
                                }, 2000);
                            });
                        });
                        
                        block.appendChild(copyButton);
                    }
                });
            }
        };

        // Initialize plugins
        Prism.plugins.lineNumbers.init();

        // Highlight all code blocks
        Prism.highlightAll();

        // Observe DOM changes for new messages
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1 && node.classList.contains('aica-message')) {
                            // Highlight new code blocks
                            const codeBlocks = node.querySelectorAll('pre code');
                            codeBlocks.forEach(block => {
                                Prism.highlightElement(block);
                            });
                            
                            // Add line numbers to new blocks
                            Prism.plugins.lineNumbers.addLineNumbers();
                            Prism.plugins.lineNumbers.addCopyButton();
                        }
                    });
                }
            });
        });

        // Start observing the chat container
        const chatContainer = document.querySelector('.aica-chat__messages');
        if (chatContainer) {
            observer.observe(chatContainer, {
                childList: true,
                subtree: true
            });
        }
    }

})(jQuery);