/**
 * Skrypt administracyjny dla wtyczki AI Chat Assistant
 */
(function($) {
    'use strict';
    
    // Inicjalizacja po załadowaniu dokumentu
    $(document).ready(function() {
        // Inicjalizacja strony ustawień
        if ($('.aica-settings-container').length) {
            initSettingsPage();
        }
    });
    
    /**
     * Inicjalizacja funkcji strony ustawień
     */
    function initSettingsPage() {
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
    }
    
    /**
     * Obsługa zakładek
     */
    function initTabs() {
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
        $('.aica-toggle-password').on('click', function(e) {
            e.preventDefault();
            var input = $(this).siblings('input');
            var icon = $(this).find('.dashicons');
            
            if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
            } else {
                input.attr('type', 'password');
                icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
            }
        });
    }
    
    /**
     * Obsługa zamykania powiadomień
     */
    function initNotices() {
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
        });
    }
    
    /**
     * Obsługa szablonów promptów
     */
    function initTemplates() {
        if ($('#aica-templates-container').length) {
            let templateCount = $('.aica-template-item').length;
            
            $('#add-template').on('click', function(e) {
                e.preventDefault();
                const template = $('#template-item-template').html().replace(/__INDEX__/g, templateCount);
                
                $('#aica-templates-container').append(template);
                templateCount++;
            });
            
            $(document).on('click', '.aica-template-delete', function(e) {
                e.preventDefault();
                $(this).closest('.aica-template-item').slideUp(300, function() {
                    $(this).remove();
                });
            });
        }
    }
    
    /**
     * Obsługa automatycznego czyszczenia historii
     */
    function initAutoPurge() {
        if ($('#aica_auto_purge_enabled').length) {
            $('#aica_auto_purge_enabled').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#aica_auto_purge_days').prop('disabled', false);
                } else {
                    $('#aica_auto_purge_days').prop('disabled', true);
                }
            });
        }
    }
    
    /**
     * Obsługa wyboru modelu AI
     */
    function initModelSelection() {
        if ($('.aica-model-card').length) {
            $('.aica-model-card input[type="radio"]').on('change', function() {
                $('.aica-model-card').removeClass('selected');
                $(this).closest('.aica-model-card').addClass('selected');
            });
        }
    }
    
    /**
     * Obsługa przycisku odświeżania modeli
     */
    function initRefreshModels() {
        $('#refresh-models').on('click', function(e) {
            e.preventDefault();
            var button = $(this);
            var originalText = button.html();
            
            button.html('<span class="aica-spinner"></span> ' + (aica_data.i18n.refreshing_models || 'Odświeżanie...'));
            button.prop('disabled', true);
            
            $.ajax({
                url: aica_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'aica_refresh_models',
                    nonce: aica_data.settings_nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Odświeżenie strony, aby pokazać zaktualizowane modele
                        location.reload();
                    } else {
                        button.html(originalText);
                        button.prop('disabled', false);
                        alert(response.data.message || 'Nie udało się odświeżyć modeli.');
                    }
                },
                error: function() {
                    button.html(originalText);
                    button.prop('disabled', false);
                    alert('Wystąpił błąd podczas odświeżania modeli.');
                }
            });
        });
    }
    
    /**
     * Obsługa testowania połączenia z API
     */
    function initApiTesting() {
        // Testowanie połączenia z API Claude
        $('#test-claude-api').on('click', function(e) {
            e.preventDefault();
            var button = $(this);
            var originalText = button.html();
            var resultContainer = $('#api-test-result');
            
            // Ukrycie poprzedniego wyniku
            resultContainer.removeClass('success error').addClass('loading');
            resultContainer.html('<span class="aica-spinner"></span> ' + (aica_data && aica_data.i18n && aica_data.i18n.loading ? aica_data.i18n.loading : 'Testowanie...'));
            
            // Pobranie klucza API
            var apiKey = $('#aica_claude_api_key').val();
            
            if (apiKey === '') {
                resultContainer.removeClass('loading').addClass('error');
                resultContainer.html('<span class="dashicons dashicons-no-alt"></span> ' + 'Wprowadź klucz API.');
                return;
            }
            
            // Wywołanie AJAX do testowania połączenia
            $.ajax({
                url: aica_data.ajax_url || ajaxurl,
                type: 'POST',
                data: {
                    action: 'aica_test_api_connection',
                    nonce: aica_data.settings_nonce || $('#aica_settings_nonce').val(),
                    api_type: 'claude',
                    api_key: apiKey
                },
                success: function(response) {
                    if (response.success) {
                        resultContainer.removeClass('loading').addClass('success');
                        resultContainer.html('<span class="dashicons dashicons-yes-alt"></span> ' + response.data.message);
                    } else {
                        resultContainer.removeClass('loading').addClass('error');
                        resultContainer.html('<span class="dashicons dashicons-no-alt"></span> ' + (response.data.message || 'Nie udało się połączyć z API Claude.'));
                    }
                },
                error: function() {
                    resultContainer.removeClass('loading').addClass('error');
                    resultContainer.html('<span class="dashicons dashicons-no-alt"></span> ' + 'Wystąpił błąd podczas testowania połączenia.');
                }
            });
        });
        
        // Testowanie połączenia z API GitHub
        $('#test-github-api').on('click', function(e) {
            e.preventDefault();
            var button = $(this);
            var originalText = button.html();
            var resultContainer = $('#github-test-result');
            
            // Ukrycie poprzedniego wyniku
            resultContainer.removeClass('success error').addClass('loading');
            resultContainer.html('<span class="aica-spinner"></span> ' + (aica_data && aica_data.i18n && aica_data.i18n.loading ? aica_data.i18n.loading : 'Testowanie...'));
            
            // Pobranie tokenu
            var token = $('#aica_github_token').val();
            
            if (token === '') {
                resultContainer.removeClass('loading').addClass('error');
                resultContainer.html('<span class="dashicons dashicons-no-alt"></span> ' + 'Wprowadź token GitHub.');
                return;
            }
            
            // Wywołanie AJAX do testowania połączenia
            $.ajax({
                url: aica_data.ajax_url || ajaxurl,
                type: 'POST',
                data: {
                    action: 'aica_test_api_connection',
                    nonce: aica_data.settings_nonce || $('#aica_settings_nonce').val(),
                    api_type: 'github',
                    api_key: token
                },
                success: function(response) {
                    if (response.success) {
                        resultContainer.removeClass('loading').addClass('success');
                        resultContainer.html('<span class="dashicons dashicons-yes-alt"></span> ' + response.data.message);
                    } else {
                        resultContainer.removeClass('loading').addClass('error');
                        resultContainer.html('<span class="dashicons dashicons-no-alt"></span> ' + (response.data.message || 'Nie udało się połączyć z API GitHub.'));
                    }
                },
                error: function() {
                    resultContainer.removeClass('loading').addClass('error');
                    resultContainer.html('<span class="dashicons dashicons-no-alt"></span> ' + 'Wystąpił błąd podczas testowania połączenia.');
                }
            });
        });
        
        // Testowanie połączenia z API GitLab
        $('#test-gitlab-api').on('click', function(e) {
            e.preventDefault();
            var button = $(this);
            var originalText = button.html();
            var resultContainer = $('#gitlab-test-result');
            
            // Ukrycie poprzedniego wyniku
            resultContainer.removeClass('success error').addClass('loading');
            resultContainer.html('<span class="aica-spinner"></span> ' + (aica_data && aica_data.i18n && aica_data.i18n.loading ? aica_data.i18n.loading : 'Testowanie...'));
            
            // Pobranie tokenu
            var token = $('#aica_gitlab_token').val();
            
            if (token === '') {
                resultContainer.removeClass('loading').addClass('error');
                resultContainer.html('<span class="dashicons dashicons-no-alt"></span> ' + 'Wprowadź token GitLab.');
                return;
            }
            
            // Wywołanie AJAX do testowania połączenia
            $.ajax({
                url: aica_data.ajax_url || ajaxurl,
                type: 'POST',
                data: {
                    action: 'aica_test_api_connection',
                    nonce: aica_data.settings_nonce || $('#aica_settings_nonce').val(),
                    api_type: 'gitlab',
                    api_key: token
                },
                success: function(response) {
                    if (response.success) {
                        resultContainer.removeClass('loading').addClass('success');
                        resultContainer.html('<span class="dashicons dashicons-yes-alt"></span> ' + response.data.message);
                    } else {
                        resultContainer.removeClass('loading').addClass('error');
                        resultContainer.html('<span class="dashicons dashicons-no-alt"></span> ' + (response.data.message || 'Nie udało się połączyć z API GitLab.'));
                    }
                },
                error: function() {
                    resultContainer.removeClass('loading').addClass('error');
                    resultContainer.html('<span class="dashicons dashicons-no-alt"></span> ' + 'Wystąpił błąd podczas testowania połączenia.');
                }
            });
        });
        
        // Testowanie połączenia z API Bitbucket
        $('#test-bitbucket-api').on('click', function(e) {
            e.preventDefault();
            var button = $(this);
            var originalText = button.html();
            var resultContainer = $('#bitbucket-test-result');
            
            // Ukrycie poprzedniego wyniku
            resultContainer.removeClass('success error').addClass('loading');
            resultContainer.html('<span class="aica-spinner"></span> ' + (aica_data && aica_data.i18n && aica_data.i18n.loading ? aica_data.i18n.loading : 'Testowanie...'));
            
            // Pobranie danych dostępowych
            var username = $('#aica_bitbucket_username').val();
            var password = $('#aica_bitbucket_app_password').val();
            
            if (username === '' || password === '') {
                resultContainer.removeClass('loading').addClass('error');
                resultContainer.html('<span class="dashicons dashicons-no-alt"></span> ' + 'Wprowadź nazwę użytkownika i hasło aplikacji Bitbucket.');
                return;
            }
            
            // Wywołanie AJAX do testowania połączenia
            $.ajax({
                url: aica_data.ajax_url || ajaxurl,
                type: 'POST',
                data: {
                    action: 'aica_test_api_connection',
                    nonce: aica_data.settings_nonce || $('#aica_settings_nonce').val(),
                    api_type: 'bitbucket',
                    username: username,
                    password: password
                },
                success: function(response) {
                    if (response.success) {
                        resultContainer.removeClass('loading').addClass('success');
                        resultContainer.html('<span class="dashicons dashicons-yes-alt"></span> ' + response.data.message);
                    } else {
                        resultContainer.removeClass('loading').addClass('error');
                        resultContainer.html('<span class="dashicons dashicons-no-alt"></span> ' + (response.data.message || 'Nie udało się połączyć z API Bitbucket.'));
                    }
                },
                error: function() {
                    resultContainer.removeClass('loading').addClass('error');
                    resultContainer.html('<span class="dashicons dashicons-no-alt"></span> ' + 'Wystąpił błąd podczas testowania połączenia.');
                }
            });
        });
    }
    
    /**
     * Walidacja formularza przed wysłaniem
     */
    function initFormValidation() {
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
                }
            }
        });
    }

})(jQuery);