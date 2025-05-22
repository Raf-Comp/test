/**
 * Skrypt obsługujący stronę diagnostyki
 */
jQuery(document).ready(function($) {
    // Sprawdź czy dane zostały prawidłowo przekazane
    if (typeof aica_diagnostics_data === 'undefined' || !aica_diagnostics_data.nonce) {
        console.error('AICA: Brak danych diagnostyki lub nieprawidłowy nonce');
        // Próba naprawy danych z atrybutu
        const scriptTag = document.querySelector('script[data-diagnostics-nonce]');
        if (scriptTag) {
            window.aica_diagnostics_data = {
                nonce: scriptTag.getAttribute('data-diagnostics-nonce'),
                ajax_url: ajaxurl || '/wp-admin/admin-ajax.php',
                chat_url: '/wp-admin/admin.php?page=ai-chat-assistant'
            };
            console.log('AICA: Odzyskano dane diagnostyki z atrybutu');
        }
    }
    
    // Store configuration for dialog actions
    const dialogConfig = {
        action: '',
        params: {},
        callback: null
    };
    
    // Show notification function
    function showNotification(type, message, title = '', duration = 5000) {
        const notificationsContainer = $('#aica-notifications-container');
        const icons = {
            success: 'dashicons-yes-alt',
            error: 'dashicons-warning',
            warning: 'dashicons-info',
            info: 'dashicons-info'
        };
        
        // Utwórz tytuł, jeśli nie został podany
        if (!title) {
            switch(type) {
                case 'success': title = aica_diagnostics_data.i18n?.success || 'Sukces'; break;
                case 'error': title = aica_diagnostics_data.i18n?.error || 'Błąd'; break;
                case 'warning': title = aica_diagnostics_data.i18n?.warning || 'Ostrzeżenie'; break;
                case 'info': title = aica_diagnostics_data.i18n?.info || 'Informacja'; break;
            }
        }
        
        // Utwórz element powiadomienia
        const notification = $(`
            <div class="aica-notification ${type}">
                <div class="aica-notification-icon">
                    <span class="dashicons ${icons[type]}"></span>
                </div>
                <div class="aica-notification-content">
                    <div class="aica-notification-title">${title}</div>
                    <p class="aica-notification-message">${message}</p>
                </div>
                <button class="aica-notification-close">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
        `);
        
        // Dodaj powiadomienie do kontenera
        notificationsContainer.append(notification);
        
        // Skonfiguruj przycisk zamykania
        notification.find('.aica-notification-close').on('click', function() {
            closeNotification(notification);
        });
        
        // Auto-zamknięcie po określonym czasie
        if (duration > 0) {
            setTimeout(function() {
                closeNotification(notification);
            }, duration);
        }
        
        // Zwróć element powiadomienia, aby umożliwić dalszą manipulację
        return notification;
    }
    
    // Close notification function
    function closeNotification(notification) {
        notification.css('animation', 'slideOut 0.3s forwards');
        setTimeout(function() {
            notification.remove();
        }, 300);
    }
    
    // Show confirmation dialog
    function showConfirmDialog(title, message, action, params = {}, callback = null) {
        const dialog = $('#aica-confirm-dialog');
        
        // Set dialog content
        $('#aica-dialog-title').text(title);
        $('#aica-dialog-message').text(message);
        
        // Store action details
        dialogConfig.action = action;
        dialogConfig.params = params;
        dialogConfig.callback = callback;
        
        // Show dialog
        dialog.fadeIn(200);
    }
    
    // Hide confirmation dialog
    function hideConfirmDialog() {
        const dialog = $('#aica-confirm-dialog');
        dialog.fadeOut(200);
    }
    
    // Dialog close button
    $('.aica-dialog-close, .aica-dialog-cancel').on('click', function() {
        hideConfirmDialog();
    });
    
    // Dialog overlay click
    $(document).on('click', '.aica-dialog-overlay', function() {
        hideConfirmDialog();
    });
    
    // Dialog confirm button
    $('.aica-dialog-confirm').on('click', function() {
        // Execute the appropriate action based on dialogConfig
        switch(dialogConfig.action) {
            case 'delete_session':
                deleteSession(dialogConfig.params.sessionId);
                break;
            case 'repair_database':
                repairDatabase();
                break;
            default:
                // Execute callback if available
                if (typeof dialogConfig.callback === 'function') {
                    dialogConfig.callback();
                }
        }
        
        // Hide dialog after action
        hideConfirmDialog();
    });
    
    // API Tests
    
    // Claude API Test
    $('#test-claude-api').on('click', function() {
        const button = $(this);
        const originalHtml = button.html();
        
        // Show loading state
        button.html('<span class="aica-spinner"></span>').prop('disabled', true);
        
        // Send AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aica_test_api_connection_diagnostics',
                nonce: aica_diagnostics_data.nonce,
                api_type: 'claude'
            },
            success: function(response) {
                // Restore button state
                button.html(originalHtml).prop('disabled', false);
                
                if (response.success) {
                    showNotification('success', response.data.message || 'Połączenie z API Claude działa poprawnie.');
                    // Refresh section after short delay
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification('error', response.data.message || 'Wystąpił błąd podczas testowania połączenia z API Claude.');
                }
            },
            error: function(xhr, status, error) {
                // Restore button state
                button.html(originalHtml).prop('disabled', false);
                showNotification('error', 'Wystąpił błąd podczas wykonywania żądania: ' + error);
                console.error('AJAX Error:', xhr.responseText);
            }
        });
    });
    
    // GitHub API Test
    $('#test-github-api').on('click', function() {
        const button = $(this);
        const originalHtml = button.html();
        
        // Show loading state
        button.html('<span class="aica-spinner"></span>').prop('disabled', true);
        
        // Send AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aica_test_api_connection_diagnostics',
                nonce: aica_diagnostics_data.nonce,
                api_type: 'github'
            },
            success: function(response) {
                // Restore button state
                button.html(originalHtml).prop('disabled', false);
                
                if (response.success) {
                    showNotification('success', response.data.message || 'Połączenie z API GitHub działa poprawnie.');
                    // Refresh section after short delay
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification('error', response.data.message || 'Wystąpił błąd podczas testowania połączenia z API GitHub.');
                }
            },
            error: function(xhr, status, error) {
                // Restore button state
                button.html(originalHtml).prop('disabled', false);
                showNotification('error', 'Wystąpił błąd podczas wykonywania żądania: ' + error);
                console.error('AJAX Error:', xhr.responseText);
            }
        });
    });
    
    // GitLab API Test
    $('#test-gitlab-api').on('click', function() {
        const button = $(this);
        const originalHtml = button.html();
        
        // Show loading state
        button.html('<span class="aica-spinner"></span>').prop('disabled', true);
        
        // Send AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aica_test_api_connection_diagnostics',
                nonce: aica_diagnostics_data.nonce,
                api_type: 'gitlab'
            },
            success: function(response) {
                // Restore button state
                button.html(originalHtml).prop('disabled', false);
                
                if (response.success) {
                    showNotification('success', response.data.message || 'Połączenie z API GitLab działa poprawnie.');
                    // Refresh section after short delay
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification('error', response.data.message || 'Wystąpił błąd podczas testowania połączenia z API GitLab.');
                }
            },
            error: function(xhr, status, error) {
                // Restore button state
                button.html(originalHtml).prop('disabled', false);
                showNotification('error', 'Wystąpił błąd podczas wykonywania żądania: ' + error);
                console.error('AJAX Error:', xhr.responseText);
            }
        });
    });
    
    // Bitbucket API Test
    $('#test-bitbucket-api').on('click', function() {
        const button = $(this);
        const originalHtml = button.html();
        
        // Show loading state
        button.html('<span class="aica-spinner"></span>').prop('disabled', true);
        
        // Send AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aica_test_api_connection_diagnostics',
                nonce: aica_diagnostics_data.nonce,
                api_type: 'bitbucket'
            },
            success: function(response) {
                // Restore button state
                button.html(originalHtml).prop('disabled', false);
                
                if (response.success) {
                    showNotification('success', response.data.message || 'Połączenie z API Bitbucket działa poprawnie.');
                    // Refresh section after short delay
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification('error', response.data.message || 'Wystąpił błąd podczas testowania połączenia z API Bitbucket.');
                }
            },
            error: function(xhr, status, error) {
                // Restore button state
                button.html(originalHtml).prop('disabled', false);
                showNotification('error', 'Wystąpił błąd podczas wykonywania żądania: ' + error);
                console.error('AJAX Error:', xhr.responseText);
            }
        });
    });
    
    // Repair Database
    $('#repair-database').on('click', function() {
        // Pokaż okno dialogowe potwierdzenia
        showConfirmDialog(
            'Naprawa bazy danych', 
            'Czy na pewno chcesz naprawić tabele bazy danych? Ta operacja spróbuje utworzyć brakujące tabele.', 
            'repair_database'
        );
    });
    
    function repairDatabase() {
        const button = $('#repair-database');
        const originalHtml = button.html();
        
        // Dodajemy komunikaty debugujące
        console.log('Rozpoczynam naprawę bazy danych');
        console.log('Nonce:', aica_diagnostics_data.nonce);
        console.log('URL:', ajaxurl);
        
        // Show loading state
        button.html('<span class="aica-spinner"></span>').prop('disabled', true);
        
        // Send AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aica_repair_database',
                nonce: aica_diagnostics_data.nonce
            },
            success: function(response) {
                console.log('Odpowiedź serwera:', response);
                // Restore button state
                button.html(originalHtml).prop('disabled', false);
                
                if (response.success) {
                    showNotification('success', response.data.message || 'Pomyślnie naprawiono tabele bazy danych.');
                    // Refresh page after short delay
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification('error', response.data.message || 'Wystąpił błąd podczas naprawy bazy danych.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Błąd AJAX:', xhr.responseText);
                console.error('Status:', status);
                console.error('Error:', error);
                
                // Restore button state
                button.html(originalHtml).prop('disabled', false);
                showNotification('error', 'Wystąpił błąd podczas wykonywania żądania: ' + error);
                
                // Spróbujmy automatycznie odświeżyć stronę, być może tabele zostały naprawione pomimo błędu
                setTimeout(function() {
                    location.reload();
                }, 2000);
            }
        });
    }
    
    // Delete session
    $('.js-delete-session').on('click', function() {
        const sessionId = $(this).data('session-id');
        
        showConfirmDialog(
            'Usuń sesję', 
            'Czy na pewno chcesz usunąć tę sesję czatu? Tej operacji nie można cofnąć.', 
            'delete_session',
            { sessionId: sessionId }
        );
    });
    
    function deleteSession(sessionId) {
        const sessionItem = $('.js-delete-session[data-session-id="' + sessionId + '"]').closest('.aica-session-item');
        
        // Add loading state
        sessionItem.css('opacity', '0.5');
        
        // Send AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aica_delete_session',
                nonce: aica_diagnostics_data.nonce,
                nonce_key: 'aica_diagnostics_nonce',
                session_id: sessionId
            },
            success: function(response) {
                if (response.success) {
                    // Remove session item with animation
                    sessionItem.slideUp(300, function() {
                        $(this).remove();
                        
                        // Check if there are no more sessions
                        if ($('.aica-session-item').length === 0) {
                            // Show empty state
                            $('.aica-sessions-list').html(`
                                <div class="aica-empty-state">
                                    <div class="aica-empty-icon">
                                        <span class="dashicons dashicons-format-chat"></span>
                                    </div>
                                    <h3>Brak historii czatu</h3>
                                    <p>Nie przeprowadziłeś jeszcze żadnych rozmów z Claude. Rozpocznij rozmowę, aby zobaczyć ją tutaj.</p>
                                    <a href="${aica_diagnostics_data.chat_url}" class="button button-primary">
                                        <span class="dashicons dashicons-plus"></span>
                                        Rozpocznij rozmowę
                                    </a>
                                </div>
                            `);
                        }
                    });
                    
                    showNotification('success', 'Sesja została pomyślnie usunięta.');
                } else {
                    // Restore session item
                    sessionItem.css('opacity', '1');
                    showNotification('error', response.data.message || 'Wystąpił błąd podczas usuwania sesji.');
                }
            },
            error: function(xhr, status, error) {
                // Restore session item
                sessionItem.css('opacity', '1');
                showNotification('error', 'Wystąpił błąd podczas wykonywania żądania: ' + error);
                console.error('AJAX Error:', xhr.responseText);
            }
        });
    }
    
    // Refresh All Diagnostics
    $('#refresh-all-diagnostics').on('click', function() {
        const button = $(this);
        const originalHtml = button.html();
        
        // Show loading state
        button.html('<span class="aica-spinner"></span> Odświeżanie...').prop('disabled', true);
        
        // Wait a bit to show animation, then refresh page
        setTimeout(function() {
            location.reload();
        }, 800);
    });
    
    // Initial notification to help users
    setTimeout(function() {
        showNotification(
            'info', 
            'Możesz przetestować połączenia API za pomocą przycisków "Test" przy każdym z nich.', 
            'Witaj w diagnostyce',
            7000 // Dłuższy czas wyświetlania dla informacji powitalnej
        );
    }, 1000);
});