/**
 * Skrypt dla strony ustawień wtyczki AI Chat Assistant
 */
(function($) {
    'use strict';
    
    // Inicjalizacja po załadowaniu dokumentu
    $(document).ready(function() {
        // Inicjalizacja strony ustawień
        initSettingsPage();
    });
    
    /**
     * Inicjalizacja funkcji strony ustawień
     */
    function initSettingsPage() {
        console.log('Inicjalizacja strony ustawień...');
        
        // Inicjalizacja zakładek
        initTabs();
        
        // Inicjalizacja pól zakresu
        initRangeFields();
        
        // Inicjalizacja przycisków pokazywania/ukrywania haseł
        initPasswordToggles();
        
        // Inicjalizacja pola tagów z rozszerzeniami plików
        initTagsField();
        
        // Inicjalizacja szablonów promptów
        initTemplates();
        
        // Inicjalizacja automatycznego czyszczenia historii
        initAutoPurge();
        
        // Inicjalizacja wyboru modelu AI
        initModelSelection();
        
        // Inicjalizacja testowania API
        initApiTesting();
        
        // Inicjalizacja zamykania powiadomień
        initNotices();
        
        // Inicjalizacja przycisku odświeżania modeli
        initRefreshModels();
        
        // Inicjalizacja walidacji formularza
        initFormValidation();
        
        // Inicjalizacja obsługi zapisu formularza
        initFormSubmit();
    }
    
    /**
     * Obsługa zakładek
     */
    function initTabs() {
        console.log('Inicjalizacja zakładek...');
        
        $('.aica-tab-item').on('click', function(e) {
            e.preventDefault();
            
            // Usunięcie aktywnej klasy z wszystkich zakładek
            $('.aica-tab-item').removeClass('active');
            // Dodanie aktywnej klasy do klikniętej zakładki
            $(this).addClass('active');
            
            // Ukrycie wszystkich zawartości zakładek
            $('.aica-tab-content').removeClass('active');
            
            // Pobranie id zawartości zakładki
            var tabId = $(this).data('tab');
            
            // Pokazanie zawartości aktywnej zakładki
            $('#' + tabId).addClass('active');
            
            // Zapisanie aktywnej zakładki w localStorage
            localStorage.setItem('aica_active_tab', tabId);
            
            console.log('Aktywna zakładka: ' + tabId);
        });
        
        // Przywrócenie aktywnej zakładki z localStorage
        var activeTab = localStorage.getItem('aica_active_tab');
        if (activeTab && $('#' + activeTab).length) {
            $('.aica-tab-item[data-tab="' + activeTab + '"]').trigger('click');
        } else {
            // Jeśli nie ma zapisanej zakładki, pokaż pierwszą
            $('.aica-tab-item:first').trigger('click');
        }
    }
    
    /**
     * Obsługa pól z zakresem (range inputs)
     */
    function initRangeFields() {
        console.log('Inicjalizacja pól zakresu...');
        
        // Obsługa pola z zakresem dla tokenów
        $('#aica_max_tokens_range').on('input', function() {
            $('#aica_max_tokens').val($(this).val());
        });
        
        $('#aica_max_tokens').on('change', function() {
            const value = parseInt($(this).val());
            const min = parseInt($(this).attr('min') || 1000);
            const max = parseInt($(this).attr('max') || 100000);
            
            let validValue = value;
            if (isNaN(value) || value < min) {
                validValue = min;
            } else if (value > max) {
                validValue = max;
            }
            
            $(this).val(validValue);
            $('#aica_max_tokens_range').val(validValue);
        });
        
        // Obsługa pola z zakresem dla temperatury
        if ($('#aica_temperature_range').length) {
            $('#aica_temperature_range').on('input', function() {
                $('#aica_temperature').val($(this).val());
            });
            
            $('#aica_temperature').on('change', function() {
                const value = parseFloat($(this).val());
                const min = parseFloat($(this).attr('min') || 0);
                const max = parseFloat($(this).attr('max') || 1);
                
                let validValue = value;
                if (isNaN(value) || value < min) {
                    validValue = min;
                } else if (value > max) {
                    validValue = max;
                }
                
                $(this).val(validValue);
                $('#aica_temperature_range').val(validValue);
            });
        }
        
        // Dodatkowy trigger dla poprawnego ustawienia wartości na starcie
        $('#aica_max_tokens').trigger('change');
        $('#aica_temperature').trigger('change');
    }
    
    /**
     * Obsługa przycisków pokaż/ukryj hasło
     */
    function initPasswordToggles() {
        console.log('Inicjalizacja przycisków pokazywania/ukrywania haseł...');
        
        $('.aica-toggle-password').on('click', function(e) {
            e.preventDefault();
            
            var input = $(this).siblings('input');
            var icon = $(this).find('.dashicons');
            
            console.log('Przełącznik hasła kliknięty, typ pola:', input.attr('type'));
            
            if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
                $(this).attr('aria-label', aica_data.i18n.hide_password);
            } else {
                input.attr('type', 'password');
                icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
                $(this).attr('aria-label', aica_data.i18n.show_password);
            }
        });
    }
    
    /**
     * Obsługa zamykania powiadomień
     */
    function initNotices() {
        console.log('Inicjalizacja obsługi powiadomień...');
        
        $('.aica-notice-dismiss').on('click', function() {
            $(this).closest('.aica-notice').slideUp(300, function() {
                $(this).remove();
            });
        });

        // Automatyczne zamknięcie powiadomień po 5 sekundach
        setTimeout(function() {
            $('.aica-notice').slideUp(300, function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    /**
     * Obsługa pola tagów z rozszerzeniami plików
     */
    function initTagsField() {
        console.log('Inicjalizacja pola tagów...');
        
        function updateExtensionsField() {
            var extensions = [];
            $('.aica-tag').each(function() {
                var text = $(this).contents().filter(function() {
                    return this.nodeType === 3; // node tekstowy
                }).text().trim();
                
                if (text) {
                    extensions.push(text);
                }
            });
            $('#aica_allowed_file_extensions').val(extensions.join(','));
            
            console.log('Zaktualizowano pole rozszerzeń:', extensions.join(','));
        }
        
        $('#add-extension').on('click', function(e) {
            e.preventDefault();
            var extension = $('#aica_file_extension_input').val().trim();
            
            if (extension !== '') {
                // Sprawdzenie czy rozszerzenie już istnieje
                var exists = false;
                $('.aica-tag').each(function() {
                    var text = $(this).contents().filter(function() {
                        return this.nodeType === 3; // node tekstowy
                    }).text().trim();
                    
                    if (text === extension) {
                        exists = true;
                        return false;
                    }
                });
                
                if (!exists) {
                    // Dodanie nowego tagu
                    var tag = $('<span class="aica-tag">' + extension + '<button type="button" class="aica-remove-tag" data-value="' + extension + '"><span class="dashicons dashicons-no-alt"></span></button></span>');
                    $('#extensions-container').append(tag);
                    $('#aica_file_extension_input').val('');
                    
                    // Aktualizacja pola ukrytego
                    updateExtensionsField();
                    
                    console.log('Dodano nowe rozszerzenie:', extension);
                }
            }
        });
        
        // Obsługa wciśnięcia Enter w polu dodawania rozszerzeń
        $('#aica_file_extension_input').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#add-extension').trigger('click');
            }
        });
        
        // Usuwanie tagów
        $(document).on('click', '.aica-remove-tag', function(e) {
            e.preventDefault();
            $(this).parent().remove();
            updateExtensionsField();
            
            console.log('Usunięto rozszerzenie');
        });
    }
    
    /**
     * Obsługa szablonów promptów
     */
    function initTemplates() {
        console.log('Inicjalizacja szablonów promptów...');
        
        if ($('#aica-templates-container').length) {
            let templateCount = $('.aica-template-item').length;
            
            $('#add-template').on('click', function(e) {
                e.preventDefault();
                const template = $('#template-item-template').html().replace(/__INDEX__/g, templateCount);
                
                $('#aica-templates-container').append(template);
                templateCount++;
                
                console.log('Dodano nowy szablon, liczba szablonów:', templateCount);
            });
            
            $(document).on('click', '.aica-template-delete', function(e) {
                e.preventDefault();
                $(this).closest('.aica-template-item').slideUp(300, function() {
                    $(this).remove();
                    console.log('Usunięto szablon');
                });
            });
        }
    }
    
    /**
     * Obsługa automatycznego czyszczenia historii
     */
    function initAutoPurge() {
        console.log('Inicjalizacja automatycznego czyszczenia historii...');
        
        if ($('#aica_auto_purge_enabled').length) {
            $('#aica_auto_purge_enabled').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#aica_auto_purge_days').prop('disabled', false);
                    console.log('Włączono automatyczne czyszczenie');
                } else {
                    $('#aica_auto_purge_days').prop('disabled', true);
                    console.log('Wyłączono automatyczne czyszczenie');
                }
            });
            
            // Wywołanie na starcie, aby poprawnie ustawić stan
            $('#aica_auto_purge_enabled').trigger('change');
        }
    }
    
    /**
     * Obsługa wyboru modelu AI
     */
    function initModelSelection() {
        console.log('Inicjalizacja wyboru modelu AI...');
        
        if ($('.aica-model-card').length) {
            $('.aica-model-card input[type="radio"]').on('change', function() {
                $('.aica-model-card').removeClass('selected');
                $(this).closest('.aica-model-card').addClass('selected');
                
                console.log('Wybrano model:', $(this).val());
            });
        }
    }
    
    /**
     * Obsługa przycisku odświeżania modeli
     */
    function initRefreshModels() {
        console.log('Inicjalizacja odświeżania modeli...');
        
        $('#refresh-models').on('click', function(e) {
            e.preventDefault();
            var button = $(this);
            var originalText = button.html();
            
            console.log('Rozpoczęto odświeżanie modeli...');
            
            button.html('<span class="aica-spinner"></span> ' + (aica_data && aica_data.i18n && aica_data.i18n.refreshing_models ? aica_data.i18n.refreshing_models : 'Odświeżanie...'));
            button.prop('disabled', true);
            
            $.ajax({
                url: aica_data && aica_data.ajax_url ? aica_data.ajax_url : ajaxurl,
                type: 'POST',
                data: {
                    action: 'aica_refresh_models',
                    nonce: aica_data && aica_data.settings_nonce ? aica_data.settings_nonce : $('#aica_settings_nonce').val()
                },
                success: function(response) {
                    if (response.success) {
                        console.log('Odświeżanie modeli zakończone sukcesem. Przeładowywanie strony...');
                        
                        // Odświeżenie strony, aby pokazać zaktualizowane modele
                        location.reload();
                    } else {
                        button.html(originalText);
                        button.prop('disabled', false);
                        alert(response.data && response.data.message ? response.data.message : 'Nie udało się odświeżyć modeli.');
                        
                        console.error('Błąd odświeżania modeli:', response.data);
                    }
                },
                error: function(xhr, status, error) {
                    button.html(originalText);
                    button.prop('disabled', false);
                    alert('Wystąpił błąd podczas odświeżania modeli.');
                    
                    console.error('Błąd AJAX podczas odświeżania modeli:', error);
                }
            });
        });
    }
    
    /**
     * Obsługa testowania połączenia z API
     */
    function initApiTesting() {
        console.log('Inicjalizacja testowania API...');
        
        // Testowanie połączenia z API Claude
        $('#test-claude-api').on('click', function(e) {
            e.preventDefault();
            testApiConnection('claude', '#aica_claude_api_key', '#api-test-result');
        });
        
        // Testowanie połączenia z API GitHub
        $('#test-github-api').on('click', function(e) {
            e.preventDefault();
            testApiConnection('github', '#aica_github_token', '#github-test-result');
        });
        
        // Testowanie połączenia z API GitLab
        $('#test-gitlab-api').on('click', function(e) {
            e.preventDefault();
            testApiConnection('gitlab', '#aica_gitlab_token', '#gitlab-test-result');
        });
        
        // Testowanie połączenia z API Bitbucket
        $('#test-bitbucket-api').on('click', function(e) {
            e.preventDefault();
            testBitbucketConnection();
        });
    }
    
    /**
     * Testowanie połączenia z API
     */
    function testApiConnection(type, inputSelector, resultSelector) {
        const input = $(inputSelector);
        const resultContainer = $(resultSelector);
        const token = input.val().trim();
        
        if (!token) {
            showApiTestResult(resultContainer, 'error', aica_data.i18n.no_token_provided);
            return;
        }
        
        resultContainer.removeClass('success error').addClass('loading');
        resultContainer.html('<span class="dashicons dashicons-update"></span> ' + aica_data.i18n.testing_connection);
        
        $.ajax({
            url: aica_data.ajax_url,
            type: 'POST',
            data: {
                action: 'aica_test_api_connection',
                nonce: aica_data.settings_nonce,
                api_type: type,
                api_key: token
            },
            success: function(response) {
                if (response.success) {
                    showApiTestResult(resultContainer, 'success', response.data.message);
                } else {
                    showApiTestResult(resultContainer, 'error', response.data.message);
                }
            },
            error: function() {
                showApiTestResult(resultContainer, 'error', aica_data.i18n.connection_error);
            }
        });
    }
    
    /**
     * Testowanie połączenia z Bitbucket
     */
    function testBitbucketConnection() {
        const username = $('#aica_bitbucket_username').val().trim();
        const password = $('#aica_bitbucket_app_password').val().trim();
        const resultContainer = $('#bitbucket-test-result');
        
        if (!username || !password) {
            showApiTestResult(resultContainer, 'error', aica_data.i18n.no_credentials_provided);
            return;
        }
        
        resultContainer.removeClass('success error').addClass('loading');
        resultContainer.html('<span class="dashicons dashicons-update"></span> ' + aica_data.i18n.testing_connection);
        
        $.ajax({
            url: aica_data.ajax_url,
            type: 'POST',
            data: {
                action: 'aica_test_api_connection',
                nonce: aica_data.settings_nonce,
                api_type: 'bitbucket',
                username: username,
                password: password
            },
            success: function(response) {
                if (response.success) {
                    showApiTestResult(resultContainer, 'success', response.data.message);
                } else {
                    showApiTestResult(resultContainer, 'error', response.data.message);
                }
            },
            error: function() {
                showApiTestResult(resultContainer, 'error', aica_data.i18n.connection_error);
            }
        });
    }
    
    /**
     * Wyświetlanie wyniku testu API
     */
    function showApiTestResult(container, type, message) {
        container.removeClass('loading success error').addClass(type);
        const icon = type === 'success' ? 'dashicons-yes-alt' : 'dashicons-no-alt';
        container.html(`<span class="dashicons ${icon}"></span> ${message}`);
    }
    
    /**
     * Walidacja formularza przed wysłaniem
     */
    function initFormValidation() {
        console.log('Inicjalizacja walidacji formularza...');
        
        $('#aica-settings-form').on('submit', function(e) {
            var valid = true;
            var firstError = null;
            
            // Walidacja pól wg aktywnej zakładki
            var activeTab = $('.aica-tab-content.active').attr('id');
            
            if (activeTab === 'claude-settings') {
                // Walidacja klucza API Claude
                if ($('#aica_claude_api_key').val().trim() === '') {
                    $('#aica_claude_api_key').addClass('error');
                    valid = false;
                    if (!firstError) firstError = $('#aica_claude_api_key');
                } else {
                    $('#aica_claude_api_key').removeClass('error');
                }
            }
            
            // W przypadku błędów, przerwij wysyłanie i przewiń do pierwszego błędu
            if (!valid) {
                e.preventDefault();
                if (firstError) {
                    $('html, body').animate({
                        scrollTop: firstError.offset().top - 100
                    }, 300);
                    firstError.focus();
                    
                    console.log('Walidacja formularza nieudana - znaleziono błędy');
                }
            } else {
                console.log('Walidacja formularza udana - wysyłanie formularza');
            }
        });
    }
    
    /**
     * Inicjalizacja zapisu formularza
     */
    function initFormSubmit() {
        console.log('Inicjalizacja obsługi zapisu formularza...');
        
        $('#aica-settings-form').on('submit', function(e) {
            e.preventDefault();
            console.log('Formularz został wysłany - przechwycono zdarzenie submit');
            
            var form = $(this);
            var submitButton = form.find('button[type="submit"]');
            var originalButtonText = submitButton.html();
            
            // Wyłącz przycisk i pokaż spinner
            submitButton.prop('disabled', true).html('<span class="aica-spinner"></span> ' + 
                (aica_data && aica_data.i18n && aica_data.i18n.saving ? aica_data.i18n.saving : 'Zapisywanie...'));
            
            // Zbierz dane formularza
            var formData = new FormData(form[0]);
            formData.append('action', 'aica_save_settings');
            formData.append('nonce', aica_data && aica_data.settings_nonce ? aica_data.settings_nonce : $('#aica_settings_nonce').val());
            
            // Wyślij dane przez AJAX
            $.ajax({
                url: aica_data && aica_data.ajax_url ? aica_data.ajax_url : ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    // Przywróć stan przycisku
                    submitButton.prop('disabled', false).html(originalButtonText);
                    
                    if (response.success) {
                        // Pokaż komunikat o sukcesie
                        showNotification('success', response.data.message || 'Ustawienia zostały zapisane pomyślnie.');
                        console.log('Ustawienia zapisane pomyślnie');
                    } else {
                        // Pokaż komunikat o błędzie
                        showNotification('error', response.data.message || 'Wystąpił błąd podczas zapisywania ustawień.');
                        console.error('Błąd zapisu ustawień:', response.data);
                    }
                },
                error: function(xhr, status, error) {
                    // Przywróć stan przycisku
                    submitButton.prop('disabled', false).html(originalButtonText);
                    
                    // Pokaż komunikat o błędzie
                    showNotification('error', 'Wystąpił błąd podczas zapisywania ustawień.');
                    console.error('Błąd AJAX podczas zapisu ustawień:', error);
                }
            });
        });
    }
    
    /**
     * Wyświetlanie powiadomień
     */
    function showNotification(type, message) {
        // Usuń istniejące powiadomienia
        $('.aica-notification').remove();
        
        // Stwórz nowe powiadomienie
        var icon = type === 'success' ? 'dashicons-yes-alt' : 'dashicons-no-alt';
        var notificationClass = 'aica-notification aica-notification-' + type;
        
        var notification = $('<div class="' + notificationClass + '"><span class="dashicons ' + icon + '"></span>' + message + '</div>');
        
        // Dodaj powiadomienie do DOM
        $('.aica-form-actions').prepend(notification);
        
        // Ukryj powiadomienie po 5 sekundach
        setTimeout(function() {
            notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }
    
})(jQuery);