/**
 * JavaScript do obsługi strony repozytoriów
 */
jQuery(document).ready(function($) {
    
  /**
     * INICJALIZACJA I FUNKCJE POMOCNICZE
     */
    
    // Funkcja inicjalizująca stronę
    function inicjalizacja() {
        try {
            // Sprawdź czy tabela repozytoriów istnieje
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'aica_test_db'
                },
                success: function(response) {
                    if (response.success && !response.data.table_exists) {
                        // Utwórz tabelę, jeśli nie istnieje
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'aica_activate_plugin'
                            },
                            success: function() {
                                console.log('Tabela repozytoriów została utworzona');
                            }
                        });
                    }
                }
            });
            
            // Aktywacja domyślnej zakładki po załadowaniu strony
            if ($('.aica-source-item').length > 0) {
                $('.aica-source-item').first().addClass('active');
                const defaultSource = $('.aica-source-item').first().data('source');
                $('#' + defaultSource + '-repositories').addClass('active');
            }
            
            // Sprawdzenie parametrów URL
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('added') === 'true') {
                $('.aica-source-item[data-source="saved"]').trigger('click');
                pokazPowiadomienie('success', aica_repos.i18n.add_success);
            } else if (urlParams.get('deleted') === 'true') {
                $('.aica-source-item[data-source="saved"]').trigger('click');
                pokazPowiadomienie('success', aica_repos.i18n.delete_success);
            }
            
            // Pokaż filtry języka dla zakładki "saved"
            if ($('.aica-source-item[data-source="saved"]').hasClass('active')) {
                $('#aica-language-filter').show();
            }
            
            // Dodaj klasę wskazującą, że strona jest zainicjalizowana
            $('body').addClass('aica-initialized');
            
            // Utwórz kontener powiadomień, jeśli nie istnieje
            if ($('.aica-notifications-container').length === 0) {
                $('body').append('<div class="aica-notifications-container"></div>');
            }
            
            // Sprawdź preferencję trybu ciemnego
            if (localStorage.getItem('aica_dark_mode') === 'true') {
                $('body').addClass('aica-dark-mode');
                $('.aica-theme-toggle .dashicons').removeClass('dashicons-visibility').addClass('dashicons-hidden');
            }
            
            console.log('Menedżer repozytoriów zainicjalizowany pomyślnie');
        } catch (error) {
            console.error('Błąd podczas inicjalizacji:', error);
        }
    }
    
    // Funkcja pokazująca nowoczesne powiadomienie
    function pokazPowiadomienie(typ, wiadomosc) {
        const klasaPowiadomienia = typ === 'success' ? 'aica-notification-success' : 'aica-notification-error';
        const klasaIkony = typ === 'success' ? 'dashicons-yes-alt' : 'dashicons-warning';
        
        const powiadomienie = $(`
            <div class="aica-notification ${klasaPowiadomienia}">
                <div class="aica-notification-icon">
                    <span class="dashicons ${klasaIkony}"></span>
                </div>
                <div class="aica-notification-content">
                    <p>${wiadomosc}</p>
                </div>
            </div>
        `);
        
        // Dodaj powiadomienie do kontenera
        $('.aica-notifications-container').append(powiadomienie);
        
        // Animacja wejścia
        powiadomienie.css('transform', 'translateY(20px)');
        powiadomienie.css('opacity', '0');
        
        setTimeout(function() {
            powiadomienie.css('transform', 'translateY(0)');
            powiadomienie.css('opacity', '1');
        }, 10);
        
        // Automatyczne ukrycie po 4 sekundach
        setTimeout(function() {
            powiadomienie.css('transform', 'translateY(-20px)');
            powiadomienie.css('opacity', '0');
            
            setTimeout(function() {
                powiadomienie.remove();
            }, 300);
        }, 4000);
    }
    
    /**
     * OBSŁUGA INTERFEJSU UŻYTKOWNIKA
     */
    
    // Obsługa pokazywania/ukrywania menu rozwijanego
    $(document).on('click', '.aica-dropdown-toggle', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const dropdown = $(this).siblings('.aica-dropdown-menu');
        $('.aica-dropdown-menu').not(dropdown).hide();
        dropdown.toggle();
    });
    
    // Zamykanie menu rozwijanego po kliknięciu poza nim
    $(document).on('click', function() {
        $('.aica-dropdown-menu').hide();
    });
    
    // Przełączanie zakładek dla różnych źródeł repozytoriów
    $('.aica-source-item').on('click', function() {
        const source = $(this).data('source');
        
        // Animacja wyjścia
        $('.aica-repos-tab.active').fadeOut(200, function() {
            // Zmiana aktywnej zakładki
            $('.aica-repos-tab').removeClass('active');
            $('#' + source + '-repositories').addClass('active');
            
            // Animacja wejścia
            $('#' + source + '-repositories').fadeIn(200);
            
            // Pokaż/ukryj filtry języka dla zakładki "saved"
            if (source === 'saved') {
                $('#aica-language-filter').slideDown(300);
            } else {
                $('#aica-language-filter').slideUp(300);
            }
        });
        
        // Aktywacja przycisku źródła
        $('.aica-source-item').removeClass('active');
        $(this).addClass('active');
    });
    
    // Wyszukiwanie repozytoriów z opóźnieniem (debouncing)
    let czasWyszukiwania;
    $('#aica-search-repositories').on('keyup', function() {
        clearTimeout(czasWyszukiwania);
        
        const poleWyszukiwania = $(this);
        
        czasWyszukiwania = setTimeout(function() {
            const fraza = poleWyszukiwania.val().toLowerCase();
            const aktywnaZakladka = $('.aica-repos-tab.active');
            
            if (fraza === '') {
                aktywnaZakladka.find('.aica-repository-card').show();
                aktywnaZakladka.find('.aica-no-results').remove();
                return;
            }
            
            // Dodaj stan ładowania
            poleWyszukiwania.addClass('aica-searching');
            
            // Przeszukaj karty repozytoriów
            let znaleziono = false;
            aktywnaZakladka.find('.aica-repository-card').each(function() {
                const nazwaRepo = $(this).find('.aica-repo-title h3').text().toLowerCase();
                const wlascicielRepo = $(this).find('.aica-repo-owner').text().toLowerCase();
                const opisRepo = $(this).find('.aica-repo-description p').text().toLowerCase();
                
                if (nazwaRepo.includes(fraza) || wlascicielRepo.includes(fraza) || opisRepo.includes(fraza)) {
                    $(this).show();
                    znaleziono = true;
                } else {
                    $(this).hide();
                }
            });
            
            // Usuń stan ładowania
            poleWyszukiwania.removeClass('aica-searching');
            
            // Pokaż komunikat jeśli nie znaleziono wyników
            if (!znaleziono) {
                if (aktywnaZakladka.find('.aica-no-results').length === 0) {
                    aktywnaZakladka.append(`
                        <div class="aica-empty-state aica-no-results">
                            <div class="aica-empty-icon">
                                <span class="dashicons dashicons-search"></span>
                            </div>
                            <h2>${aica_repos.i18n.no_search_results}</h2>
                            <p>Spróbuj zmienić zapytanie wyszukiwania.</p>
                        </div>
                    `);
                }
            } else {
                aktywnaZakladka.find('.aica-no-results').remove();
            }
        }, 300); // 300ms opóźnienia
    });
    
    // Sortowanie repozytoriów
    $('.aica-sort-select').on('change', function() {
        const opcjaSortowania = $(this).val();
        const aktywnaZakladka = $('.aica-repos-tab.active');
        const siatkaRepo = aktywnaZakladka.find('.aica-repositories-grid');
        const kartyRepo = aktywnaZakladka.find('.aica-repository-card').toArray();
        
        // Sortowanie kart repozytoriów
        kartyRepo.sort(function(a, b) {
            const nazwaA = $(a).find('.aica-repo-title h3').text().toLowerCase();
            const nazwaB = $(b).find('.aica-repo-title h3').text().toLowerCase();
            
            // Pobieranie daty dla różnych typów zakładek
            let dataA, dataB;
            
            if (aktywnaZakladka.attr('id') === 'saved-repositories') {
                dataA = $(a).find('.aica-meta-item:contains("Dodano:")').find('.aica-meta-value').text();
                dataB = $(b).find('.aica-meta-item:contains("Dodano:")').find('.aica-meta-value').text();
            } else {
                dataA = $(a).find('.aica-meta-item:contains("Aktualizacja:")').find('.aica-meta-value').text();
                dataB = $(b).find('.aica-meta-item:contains("Aktualizacja:")').find('.aica-meta-value').text();
            }
            
            // Konwersja dat na format porównywalny
            const dataAObj = dataA ? new Date(dataA.split('.').reverse().join('-')) : new Date(0);
            const dataBObj = dataB ? new Date(dataB.split('.').reverse().join('-')) : new Date(0);
            
            switch (opcjaSortowania) {
                case 'name_asc':
                    return nazwaA.localeCompare(nazwaB);
                case 'name_desc':
                    return nazwaB.localeCompare(nazwaA);
                case 'date_asc':
                    return dataAObj - dataBObj;
                case 'date_desc':
                    return dataBObj - dataAObj;
                default:
                    return 0;
            }
        });
        
        // Aktualizacja widoku po sortowaniu
        siatkaRepo.empty();
        kartyRepo.forEach(function(karta) {
            siatkaRepo.append(karta);
        });
    });
    
    // Filtrowanie po języku
    $('#aica-language-filter').on('change', function() {
        const wybranyJezyk = $(this).val();
        const aktywnaZakladka = $('.aica-repos-tab.active');
        const kartyRepo = aktywnaZakladka.find('.aica-repository-card');

        if (wybranyJezyk === 'all') {
            kartyRepo.show();
            return;
        }

        kartyRepo.each(function() {
            const jezykRepo = $(this).find('.aica-repo-language').text().toLowerCase();
            if (jezykRepo === wybranyJezyk.toLowerCase()) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
    
    // Obsługa trybu ciemnego
    $('.aica-theme-toggle').on('click', function() {
        const przycisk = $(this);
        const ikona = przycisk.find('.dashicons');
        
        if ($('body').hasClass('aica-dark-mode')) {
            $('body').removeClass('aica-dark-mode');
            ikona.removeClass('dashicons-hidden').addClass('dashicons-visibility');
            localStorage.setItem('aica_dark_mode', 'false');
        } else {
            $('body').addClass('aica-dark-mode');
            ikona.removeClass('dashicons-visibility').addClass('dashicons-hidden');
            localStorage.setItem('aica_dark_mode', 'true');
        }
    });
    
    // Obsługa przycisku odświeżania
    $('.aica-refresh-button').on('click', function() {
        const przycisk = $(this);
        const aktywnaZakladka = $('.aica-repos-tab.active');
        
        // Dodaj animację ładowania
        przycisk.addClass('aica-loading');
        
        // Symulacja odświeżania (w rzeczywistej implementacji tutaj będzie AJAX)
        setTimeout(function() {
            przycisk.removeClass('aica-loading');
            pokazPowiadomienie('success', 'Lista repozytoriów została odświeżona');
        }, 1000);
    });
    
    // Obsługa przycisku dodawania repozytorium
    $('.aica-add-repository').on('click', function() {
		const przycisk = $(this);
        const url = przycisk.data('url');
        
        if (url) {
            window.location.href = url;
        }
    });
    
    // Obsługa przycisku usuwania repozytorium
    $(document).on('click', '.aica-delete-repository', function(e) {
        e.preventDefault();
        
        const przycisk = $(this);
        const repoId = przycisk.data('repo-id');
        
        if (confirm(aica_repos.i18n.confirm_delete)) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'aica_delete_repository',
                    repo_id: repoId,
                    nonce: aica_repos.nonce
                },
                success: function(response) {
                    if (response.success) {
                        przycisk.closest('.aica-repository-card').fadeOut(300, function() {
                            $(this).remove();
                            pokazPowiadomienie('success', aica_repos.i18n.delete_success);
                        });
                    } else {
                        pokazPowiadomienie('error', response.data.message || aica_repos.i18n.delete_error);
                    }
                },
                error: function() {
                    pokazPowiadomienie('error', aica_repos.i18n.delete_error);
                }
            });
        }
    });
    
    /**
     * OBSŁUGA PRZEGLĄDARKI PLIKÓW
     */
    
    // Otwieranie przeglądarki plików
    $(document).on('click', '.aica-browse-repo, .aica-browse-button', function(e) {
        e.preventDefault();
        const repoId = $(this).data('repo-id');
        
        // Pokaż modal przeglądarki plików z animacją
        $('#aica-file-browser-modal').css('display', 'flex').hide().fadeIn(300);
        
        // Załaduj repozytorium
        zaladujRepozytorium(repoId);
    });
    
    // Zamykanie modalnego okna przeglądarki plików
    $('.aica-modal-close').on('click', function() {
        $('#aica-file-browser-modal').fadeOut(300);
    });
    
    // Zamykanie modalnego okna po kliknięciu poza nim
    $('#aica-file-browser-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).fadeOut(300);
        }
    });
    
    // Funkcja ładująca repozytorium w przeglądarce plików
    function zaladujRepozytorium(repoId) {
        // Pokaż wskaźnik ładowania
        $('.aica-loading-files').show();
        $('#aica-file-tree').empty();
        $('#aica-file-content code').empty();
        $('.aica-file-path').text('');
        
        // Zapisz ID repozytorium w drzewie plików
        $('#aica-file-tree').data('repo-id', repoId);
        
        // Pobierz dane repozytorium
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aica_get_repository_details',
                nonce: aica_repos.nonce,
                repo_id: repoId
            },
            success: function(response) {
                if (response.success) {
                    const repo = response.data.repository;
                    
                    // Ustaw informacje o repozytorium
                    $('.aica-repo-name').text(repo.repo_name);
                    
                    // Ustaw ikonę repozytorium
                    let iconClass = 'dashicons-code-standards';
                    if (repo.repo_type === 'gitlab') {
                        iconClass = 'dashicons-editor-code';
                    } else if (repo.repo_type === 'bitbucket') {
                        iconClass = 'dashicons-cloud';
                    }
                    $('.aica-repo-icon .dashicons').attr('class', 'dashicons ' + iconClass);
                    
                    // Załaduj listę gałęzi z animacją
                    const listaGalezi = $('#aica-branch-select');
                    listaGalezi.empty();
                    
                    if (response.data.branches && response.data.branches.length > 0) {
                        $.each(response.data.branches, function(index, branch) {
                            listaGalezi.append($('<option>', {
                                value: branch,
                                text: branch
                            }));
                        });
                    } else {
                        // Dodaj domyślne gałęzie
                        const domyslneGalezie = ['main', 'master', 'develop'];
                        $.each(domyslneGalezie, function(index, branch) {
                            listaGalezi.append($('<option>', {
                                value: branch,
                                text: branch
                            }));
                        });
                    }
                    
                    // Załaduj strukturę plików
                    zaladujStrukturePlikow(repoId, '', listaGalezi.val());
                } else {
                    $('.aica-loading-files').hide();
                    pokazPowiadomienie('error', response.data.message || aica_repos.i18n.load_error);
                }
            },
            error: function(xhr, status, error) {
                $('.aica-loading-files').hide();
                pokazPowiadomienie('error', aica_repos.i18n.load_error);
                console.error('Błąd AJAX:', status, error);
            }
        });
    }
    
    // Funkcja ładująca strukturę plików
    function zaladujStrukturePlikow(repoId, path = '', branch = 'main', container = null) {
        // Pokaż wskaźnik ładowania
        if (!container) {
            $('.aica-loading-files').show();
        } else {
            container.parent().find('.aica-file-tree-folder').addClass('aica-loading');
        }
        
        // Pobierz strukturę plików
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aica_get_repository_files',
                nonce: aica_repos.nonce,
                repo_id: repoId,
                path: path,
                branch: branch
            },
            success: function(response) {
                if (response.success) {
                    // Ukryj wskaźnik ładowania
                    $('.aica-loading-files').hide();
                    if (container) {
                        container.parent().find('.aica-file-tree-folder').removeClass('aica-loading');
                    }
                    
                    // Budowanie drzewa plików
                    if (!container) {
                        // Czyszczenie drzewa, jeśli ładujemy korzeń
                        $('#aica-file-tree').empty();
                        zbudujDrzewoPlikow(response.data.files, $('#aica-file-tree'), repoId, branch);
                    } else {
                        // Budowanie poddrzewa
                        zbudujDrzewoPlikow(response.data.files, container, repoId, branch);
                        
                        // Rozwinięcie folderu z animacją
                        container.parent().find('.aica-file-tree-folder').addClass('expanded');
                        container.slideDown(200);
                    }
                } else {
                    $('.aica-loading-files').hide();
                    if (container) {
                        container.parent().find('.aica-file-tree-folder').removeClass('aica-loading');
                    }
                    pokazPowiadomienie('error', response.data.message || aica_repos.i18n.load_error);
                }
            },
            error: function(xhr, status, error) {
                $('.aica-loading-files').hide();
                if (container) {
                    container.parent().find('.aica-file-tree-folder').removeClass('aica-loading');
                }
                pokazPowiadomienie('error', aica_repos.i18n.load_error);
                console.error('Błąd AJAX:', status, error);
            }
        });
    }
    
    // Funkcja budująca drzewo plików
    function zbudujDrzewoPlikow(pliki, kontener, repoId, branch) {
        // Sortowanie: najpierw foldery, potem pliki, alfabetycznie
        pliki.sort(function(a, b) {
            if (a.type !== b.type) {
                return a.type === 'dir' || a.type === 'tree' || a.type === 'commit_directory' ? -1 : 1;
            }
            return a.name.localeCompare(b.name);
        });
        
        // Grupowanie plików według rozszerzenia
        const plikiWgRozszerzenia = {};
        const katalogi = [];
        
        pliki.forEach(function(plik) {
            if (plik.type === 'dir' || plik.type === 'tree' || plik.type === 'commit_directory') {
                katalogi.push(plik);
            } else {
                const rozszerzenie = plik.name.split('.').pop().toLowerCase() || 'inne';
                if (!plikiWgRozszerzenia[rozszerzenie]) {
                    plikiWgRozszerzenia[rozszerzenie] = [];
                }
                plikiWgRozszerzenia[rozszerzenie].push(plik);
            }
        });
        
        // Dodaj katalogi
        katalogi.forEach(function(katalog) {
            zbudujElementDrzewaKatalogu(katalog, kontener, repoId, branch);
        });
        
        // Dodaj pliki pogrupowane według rozszerzenia
        for (const rozszerzenie in plikiWgRozszerzenia) {
            plikiWgRozszerzenia[rozszerzenie].forEach(function(plik) {
                zbudujElementDrzewaPliku(plik, kontener, repoId, branch);
            });
        }
    }
    
    // Funkcja budująca element drzewa dla katalogu
    function zbudujElementDrzewaKatalogu(katalog, kontener, repoId, branch) {
        const elementKatalogu = $('<div class="aica-file-tree-item"></div>');
        const naglowekKatalogu = $('<div class="aica-file-tree-folder" data-path="' + katalog.path + '"></div>');
        naglowekKatalogu.append('<span class="dashicons dashicons-category"></span>');
        naglowekKatalogu.append('<span class="aica-file-name">' + katalog.name + '</span>');
        
        // Dodaj licznik jeśli katalog jest duży
        if (katalog.size && katalog.size > 20) {
            naglowekKatalogu.append('<span class="aica-file-count">' + katalog.size + '</span>');
        }
        
        // Dodanie kontenera dla dzieci
        const kontenerDzieci = $('<div class="aica-file-tree-children"></div>').hide();
        
        // Dodanie elementów do kontenera
        elementKatalogu.append(naglowekKatalogu);
        elementKatalogu.append(kontenerDzieci);
        kontener.append(elementKatalogu);
        
        // Dodaj efekt hover
        naglowekKatalogu.hover(
            function() { $(this).addClass('aica-hover'); },
            function() { $(this).removeClass('aica-hover'); }
        );
        
        // Obsługa kliknięcia folderu
        naglowekKatalogu.on('click', function() {
            const sciezka = $(this).data('path');
            $(this).toggleClass('expanded');
            
            // Jeśli folder ma już załadowane dzieci, po prostu rozwiń/zwiń
            if (kontenerDzieci.children().length > 0) {
                kontenerDzieci.slideToggle(200);
            } else {
                // Załaduj zawartość folderu
                zaladujStrukturePlikow(repoId, sciezka, branch, kontenerDzieci);
            }
        });
    }
    
    // Funkcja budująca element drzewa dla pliku
    function zbudujElementDrzewaPliku(plik, kontener, repoId, branch) {
        const elementPliku = $('<div class="aica-file-tree-item"></div>');
        const linkPliku = $('<div class="aica-file-tree-file" data-path="' + plik.path + '"></div>');
        
        // Wybierz ikonę na podstawie rozszerzenia pliku
        let ikonaPliku = 'dashicons-media-default';
        const rozszerzenie = plik.name.split('.').pop().toLowerCase();
        
        // Mapa rozszerzeń do ikon
        const mapaIkon = {
            'php': 'dashicons-editor-code',
            'js': 'dashicons-editor-code',
            'jsx': 'dashicons-editor-code',
            'ts': 'dashicons-editor-code',
            'tsx': 'dashicons-editor-code',
            'css': 'dashicons-admin-customizer',
            'scss': 'dashicons-admin-customizer',
            'less': 'dashicons-admin-customizer',
            'html': 'dashicons-editor-code',
            'htm': 'dashicons-editor-code',
            'md': 'dashicons-media-text',
            'txt': 'dashicons-media-text',
            'jpg': 'dashicons-format-image',
            'jpeg': 'dashicons-format-image',
            'png': 'dashicons-format-image',
            'gif': 'dashicons-format-image',
            'svg': 'dashicons-format-image',
            'json': 'dashicons-media-code',
            'xml': 'dashicons-media-code',
            'yml': 'dashicons-media-code',
            'yaml': 'dashicons-media-code'
        };
        
        ikonaPliku = mapaIkon[rozszerzenie] || 'dashicons-media-default';
        
        linkPliku.append('<span class="dashicons ' + ikonaPliku + '"></span>');
        linkPliku.append('<span class="aica-file-name">' + plik.name + '</span>');
        
        elementPliku.append(linkPliku);
        kontener.append(elementPliku);
        
        // Dodaj efekt hover
        linkPliku.hover(
            function() { $(this).addClass('aica-hover'); },
            function() { $(this).removeClass('aica-hover'); }
        );
        
        // Obsługa kliknięcia pliku
        linkPliku.on('click', function() {
            const sciezka = $(this).data('path');
            
            // Dodaj klasę ładowania
            $(this).addClass('aica-loading');
            
            // Zaznaczenie aktywnego pliku
            $('.aica-file-tree-file').removeClass('active');
            $(this).addClass('active');
            
            // Załaduj zawartość pliku
            zaladujZawartoscPliku(repoId, sciezka, branch, $(this));
        });
    }
    
    // Funkcja ładująca zawartość pliku
    function zaladujZawartoscPliku(repoId, path, branch = 'main', elementPliku = null) {
        // Pokaż wskaźnik ładowania
        $('.aica-loading-content').fadeIn(200);
        
        // Ustaw ścieżkę pliku
        $('.aica-file-path').text(path);
        
        // Wyczyść zawartość
        $('#aica-file-content code').empty();
        
        // Pobierz zawartość pliku
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aica_get_file_content',
                nonce: aica_repos.nonce,
                repo_id: repoId,
                path: path,
                branch: branch
            },
            success: function(response) {
                // Ukryj wskaźnik ładowania z animacją
                $('.aica-loading-content').fadeOut(200);
                if (elementPliku) {
                    elementPliku.removeClass('aica-loading');
                }
                
                if (response.success) {
                    // Wyświetl zawartość pliku z animacją
                    const zawartosc = response.data.content;
                    const jezyk = response.data.language || '';
                    
                    // Ukryj kod podczas aktualizacji
                    $('#aica-file-content').fadeOut(200, function() {
                        // Aktualizacja zawartości
                        $('#aica-file-content code').text(zawartosc);
                        
                        // Dodaj klasę języka do elementu code, jeśli istnieje
                        if (jezyk) {
                            $('#aica-file-content code').attr('class', 'language-' + jezyk);
                        }
                        
                        // Podświetlanie składni, jeśli dostępne
                        if (typeof Prism !== 'undefined') {
                            Prism.highlightElement($('#aica-file-content code')[0]);
                        }
                        
                        // Zapisz aktualną ścieżkę i zawartość dla późniejszego użycia
                        $('#aica-file-content').data('path', path);
                        $('#aica-file-content').data('content', zawartosc);
                        
                        // Pokaż kod po aktualizacji
                        $('#aica-file-content').fadeIn(200);
                    });
                } else {
                    pokazPowiadomienie('error', response.data.message || aica_repos.i18n.load_error);
                }
            },
            error: function() {
                pokazPowiadomienie('error', aica_repos.i18n.load_error);
            }
        });
    }
	
	
	// Dodawanie nowego repozytorium - przycisk "Dodaj repozytorium"
    $('.aica-add-repository-button').on('click', function() {
        // Przełącz na zakładkę GitHub, GitLab lub Bitbucket
        if ($('.aica-source-item[data-source="github"]').length > 0) {
            $('.aica-source-item[data-source="github"]').trigger('click');
        } else if ($('.aica-source-item[data-source="gitlab"]').length > 0) {
            $('.aica-source-item[data-source="gitlab"]').trigger('click');
        } else if ($('.aica-source-item[data-source="bitbucket"]').length > 0) {
            $('.aica-source-item[data-source="bitbucket"]').trigger('click');
        } else {
            // Jeśli nie ma żadnego źródła, przekieruj do ustawień
            pokazPowiadomienie('error', aica_repos.i18n.no_sources_configured);
            window.location.href = aica_repos.settings_url;
        }
    });
	
	
	/**
     * OBSŁUGA FORMULARZY I AJAX
     */
    
    // Obsługa formularzy dodawania repozytoriów - użyj AJAX zamiast zwykłego formularza
    $(document).on('submit', '.aica-add-repo-form', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitButton = form.find('button[name="aica_add_repository"]');
        const formError = form.find('.aica-form-error');
        
        // Usuń poprzednie błędy
        formError.empty().hide();
        
        // Sprawdź czy wszystkie wymagane pola są wypełnione
        const requiredFields = ['repo_type', 'repo_name', 'repo_owner', 'repo_url'];
        let hasErrors = false;
        
        requiredFields.forEach(function(field) {
            const fieldElement = form.find(`input[name="${field}"]`);
            const fieldValue = fieldElement.val().trim();
            
            if (fieldValue === '') {
                fieldElement.addClass('aica-field-error');
                hasErrors = true;
            } else {
                fieldElement.removeClass('aica-field-error');
            }
        });
        
        if (hasErrors) {
            formError.html('<p>' + aica_repos.i18n.fill_required_fields + '</p>').show();
            return;
        }
        
        // Dezaktywuj przycisk, aby uniknąć wielokrotnego kliknięcia
        submitButton.prop('disabled', true);
        submitButton.html('<span class="dashicons dashicons-update" style="animation: aica-spin 1s linear infinite;"></span> ' + aica_repos.i18n.adding);
        
        // Przygotuj dane formularza
        const formData = new FormData();
        formData.append('action', 'aica_add_repository');
        formData.append('nonce', aica_repos.nonce);
        formData.append('repo_type', form.find('input[name="repo_type"]').val().trim());
        formData.append('repo_name', form.find('input[name="repo_name"]').val().trim());
        formData.append('repo_owner', form.find('input[name="repo_owner"]').val().trim());
        formData.append('repo_url', form.find('input[name="repo_url"]').val().trim());
        formData.append('repo_external_id', form.find('input[name="repo_external_id"]').val().trim());
        formData.append('repo_description', form.find('input[name="repo_description"]').val().trim());
        
        console.log('Wysyłanie danych repozytorium:', {
            action: 'aica_add_repository',
            nonce: aica_repos.nonce,
            repo_type: form.find('input[name="repo_type"]').val().trim(),
            repo_name: form.find('input[name="repo_name"]').val().trim(),
            repo_owner: form.find('input[name="repo_owner"]').val().trim(),
            repo_url: form.find('input[name="repo_url"]').val().trim(),
            repo_external_id: form.find('input[name="repo_external_id"]').val().trim(),
            repo_description: form.find('input[name="repo_description"]').val().trim()
        });
        
        // Wyślij żądanie AJAX
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('Odpowiedź dodawania repozytorium:', response);
                
                // Przywróć przycisk do oryginalnego stanu
                submitButton.prop('disabled', false);
                submitButton.html('<span class="dashicons dashicons-plus"></span> ' + aica_repos.i18n.add);
                
                if (response.success) {
                    // Pokaż komunikat o sukcesie
                    pokazPowiadomienie('success', response.data.message || aica_repos.i18n.add_success);
                    
                    // Odśwież stronę, aby pokazać nowe repozytorium
                    window.location.href = window.location.href.split('?')[0] + '?page=ai-chat-assistant-repositories&added=true';
                } else {
                    // Pokaż komunikat o błędzie
                    const errorMessage = response.data && response.data.message 
                        ? response.data.message 
                        : aica_repos.i18n.add_error;
                    
                    formError.html('<p>' + errorMessage + '</p>').show();
                    pokazPowiadomienie('error', errorMessage);
                }
            },
            error: function(xhr, status, error) {
                console.error('Błąd AJAX:', xhr, status, error);
                
                // Przywróć przycisk do oryginalnego stanu
                submitButton.prop('disabled', false);
                submitButton.html('<span class="dashicons dashicons-plus"></span> ' + aica_repos.i18n.add);
                
                // Pokaż komunikat o błędzie
                formError.html('<p>' + aica_repos.i18n.add_error + '</p>').show();
                pokazPowiadomienie('error', aica_repos.i18n.add_error);
            }
        });
    });
	
    
    // Inicjalizacja strony po załadowaniu
    inicjalizacja();
}); // Koniec jQuery(document).ready
                      