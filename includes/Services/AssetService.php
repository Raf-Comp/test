<?php
namespace AICA\Services;

use AICA\Helpers\AssetOptimizer;
use AICA\Helpers\Cache;

/**
 * Klasa serwisu do zarządzania zasobami wtyczki
 * 
 * Zapewnia metody do:
 * - Ładowania skryptów i stylów
 * - Optymalizacji zasobów
 * - Zarządzania cache
 * - Obsługi preferencji użytkownika
 * 
 * @package AIChatAssistant
 * @since 1.0.0
 */
class AssetService {
    private $loaded_assets = [];
    private $version;
    private $optimizer;
    private $cache;

    /**
     * Konstruktor klasy AssetService
     * 
     * Inicjalizuje serwis i rejestruje hooki WordPress
     */
    public function __construct() {
        $this->version = defined('AICA_VERSION') ? AICA_VERSION : '1.0.0';
        $this->optimizer = AssetOptimizer::getInstance();
        $this->cache = Cache::getInstance();
        
        add_filter('wp_get_attachment_image_attributes', [$this, 'add_lazy_loading'], 10, 1);
        add_action('wp_head', [$this, 'add_reduced_motion_styles']);
        
        // Rejestracja zminifikowanych zasobów
        $this->registerMinifiedAssets();
    }

    /**
     * Rejestruje zminifikowane zasoby
     * 
     * Rejestruje wszystkie zminifikowane pliki CSS i JavaScript
     * oraz konfiguruje ich odroczone ładowanie
     */
    private function registerMinifiedAssets() {
        // Style
        $this->optimizer->registerMinifiedAsset('aica-chat', 'style', AICA_PLUGIN_DIR . 'assets/css/chat.min.css');
        $this->optimizer->registerMinifiedAsset('aica-modern-chat', 'style', AICA_PLUGIN_DIR . 'assets/css/modern-chat.min.css');
        $this->optimizer->registerMinifiedAsset('aica-settings', 'style', AICA_PLUGIN_DIR . 'assets/css/settings.min.css');
        $this->optimizer->registerMinifiedAsset('aica-repositories', 'style', AICA_PLUGIN_DIR . 'assets/css/repositories.min.css');
        $this->optimizer->registerMinifiedAsset('aica-history', 'style', AICA_PLUGIN_DIR . 'assets/css/history.min.css');
        $this->optimizer->registerMinifiedAsset('aica-diagnostics', 'style', AICA_PLUGIN_DIR . 'assets/css/diagnostics.min.css');

        // Skrypty
        $this->optimizer->registerMinifiedAsset('aica-chat', 'script', AICA_PLUGIN_DIR . 'assets/js/chat.min.js');
        $this->optimizer->registerMinifiedAsset('aica-modern-chat', 'script', AICA_PLUGIN_DIR . 'assets/js/modern-chat.min.js');
        $this->optimizer->registerMinifiedAsset('aica-settings', 'script', AICA_PLUGIN_DIR . 'assets/js/settings.min.js');
        $this->optimizer->registerMinifiedAsset('aica-repositories', 'script', AICA_PLUGIN_DIR . 'assets/js/repositories.min.js');
        $this->optimizer->registerMinifiedAsset('aica-history', 'script', AICA_PLUGIN_DIR . 'assets/js/history.min.js');
        $this->optimizer->registerMinifiedAsset('aica-diagnostics', 'script', AICA_PLUGIN_DIR . 'assets/js/diagnostics.min.js');

        // Rejestracja skryptów do odroczonego ładowania
        $this->optimizer->registerDeferredScript('aica-chat');
        $this->optimizer->registerDeferredScript('aica-modern-chat');
        $this->optimizer->registerDeferredScript('aica-settings');
        $this->optimizer->registerDeferredScript('aica-repositories');
        $this->optimizer->registerDeferredScript('aica-history');
        $this->optimizer->registerDeferredScript('aica-diagnostics');
    }

    /**
     * Dodaje style dla prefers-reduced-motion
     * 
     * Dodaje style CSS dla użytkowników preferujących zredukowane animacje
     * Style są cachowane dla lepszej wydajności
     */
    public function add_reduced_motion_styles() {
        if (!is_admin()) {
            return;
        }

        $cache_key = 'aica_reduced_motion_styles';
        $styles = $this->cache->remember($cache_key, function() {
            return $this->optimizer->minifyCSS('
                @media (prefers-reduced-motion: reduce) {
                    .aica-chat-message,
                    .aica-chat-input,
                    .aica-button,
                    .aica-status,
                    .aica-notification,
                    .aica-dialog,
                    .aica-session-item,
                    .aica-file-item,
                    .aica-info-item,
                    .aica-recommendation-item {
                        transition: none !important;
                        animation: none !important;
                    }

                    .aica-chat-message {
                        opacity: 1 !important;
                    }

                    .aica-button:hover,
                    .aica-button:focus {
                        transform: none !important;
                    }

                    .aica-notification {
                        transform: none !important;
                    }

                    .aica-dialog {
                        transform: none !important;
                    }

                    .aica-session-item:hover,
                    .aica-file-item:hover,
                    .aica-info-item:hover {
                        transform: none !important;
                    }

                    .aica-recommendation-item {
                        animation: none !important;
                    }

                    .aica-button:hover {
                        background-color: var(--aica-primary-dark);
                    }

                    .aica-session-item:hover,
                    .aica-file-item:hover,
                    .aica-info-item:hover {
                        background-color: var(--aica-bg-light);
                    }
                }
            ');
        });

        echo '<style>' . $styles . '</style>';
    }

    /**
     * Dodaje atrybut lazy loading do obrazów
     * 
     * @param array $attr Atrybuty obrazu
     * @return array Zmodyfikowane atrybuty obrazu
     */
    public function add_lazy_loading($attr) {
        if (is_admin()) {
            $attr['loading'] = 'lazy';
        }
        return $attr;
    }

    /**
     * Ładuje zasoby dla konkretnej strony
     * 
     * @param string $page Nazwa strony
     */
    public function loadPageAssets($page) {
        $cache_key = 'aica_page_assets_' . $page;
        
        $this->cache->remember($cache_key, function() use ($page) {
            switch ($page) {
                case 'ai-chat-assistant':
                    $this->loadChatAssets();
                    break;
                case 'ai-chat-assistant-settings':
                    $this->loadSettingsAssets();
                    break;
                case 'ai-chat-assistant-repositories':
                    $this->loadRepositoriesAssets();
                    break;
                case 'ai-chat-assistant-history':
                    $this->loadHistoryAssets();
                    break;
                case 'ai-chat-assistant-diagnostics':
                    $this->loadDiagnosticsAssets();
                    break;
            }
        });
    }

    /**
     * Ładuje zasoby dla czatu
     * 
     * Ładuje style i skrypty potrzebne do działania czatu
     */
    private function loadChatAssets() {
        // Preload krytycznych zasobów
        $this->preloadCriticalAssets();

        // Style
        $this->enqueueStyle('aica-chat', 'css/chat.css', []);
        $this->enqueueStyle('aica-modern-chat', 'css/modern-chat.css', []);
        $this->enqueueStyle('dashicons', '', []);

        // Skrypty
        $this->enqueueScript('aica-chat', 'js/chat.js', ['jquery']);
        $this->enqueueScript('aica-modern-chat', 'js/modern-chat.js', ['jquery', 'aica-chat']);

        // Dane dla skryptów
        $this->localizeScript('aica-chat', 'aica_chat', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aica_chat'),
            'i18n' => $this->getChatTranslations(),
            'prefers_reduced_motion' => $this->should_reduce_motion()
        ]);
    }

    /**
     * Preload krytycznych zasobów
     * 
     * Rejestruje krytyczne zasoby do wcześniejszego załadowania
     */
    private function preloadCriticalAssets() {
        $critical_assets = [
            'css/chat.css',
            'css/modern-chat.css',
            'js/chat.js',
            'js/modern-chat.js'
        ];

        foreach ($critical_assets as $asset) {
            $url = $this->getAssetUrl($asset);
            $type = pathinfo($asset, PATHINFO_EXTENSION) === 'css' ? 'style' : 'script';
            $this->optimizer->registerPreloadedAsset($url, $type);
        }
    }

    /**
     * Ładuje zasoby dla ustawień
     */
    private function loadSettingsAssets() {
        $this->enqueueStyle('aica-settings', 'css/settings.css', []);
        $this->enqueueScript('aica-settings', 'js/settings.js', ['jquery']);
    }

    /**
     * Ładuje zasoby dla repozytoriów
     */
    private function loadRepositoriesAssets() {
        $this->enqueueStyle('aica-repositories', 'css/repositories.css', []);
        $this->enqueueScript('aica-repositories', 'js/repositories.js', ['jquery']);
    }

    /**
     * Ładuje zasoby dla historii
     */
    private function loadHistoryAssets() {
        $this->enqueueStyle('aica-history', 'css/history.css', []);
        $this->enqueueScript('aica-history', 'js/history.js', ['jquery']);
    }

    /**
     * Ładuje zasoby dla diagnostyki
     */
    private function loadDiagnosticsAssets() {
        $this->enqueueStyle('aica-diagnostics', 'css/diagnostics.css', []);
        $this->enqueueScript('aica-diagnostics', 'js/diagnostics.js', ['jquery']);
    }

    /**
     * Ładuje style CSS
     * 
     * @param string $handle Nazwa zasobu
     * @param string $file Ścieżka do pliku
     * @param array $deps Zależności
     */
    private function enqueueStyle($handle, $file, $deps = []) {
        if (isset($this->loaded_assets[$handle])) {
            return;
        }

        $url = $this->getAssetUrl($file);
        $version = $this->getFileVersion($file);
        
        wp_enqueue_style($handle, $url, $deps, $version);
        $this->loaded_assets[$handle] = true;
    }

    /**
     * Ładuje skrypty JavaScript
     * 
     * @param string $handle Nazwa zasobu
     * @param string $file Ścieżka do pliku
     * @param array $deps Zależności
     * @param bool $in_footer Czy załadować w stopce
     */
    private function enqueueScript($handle, $file, $deps = [], $in_footer = true) {
        if (isset($this->loaded_assets[$handle])) {
            return;
        }

        $url = $this->getAssetUrl($file);
        $version = $this->getFileVersion($file);
        
        wp_enqueue_script($handle, $url, $deps, $version, $in_footer);
        $this->loaded_assets[$handle] = true;
    }

    /**
     * Lokalizuje skrypt
     * 
     * @param string $handle Nazwa zasobu
     * @param string $object_name Nazwa obiektu JavaScript
     * @param array $data Dane do lokalizacji
     */
    private function localizeScript($handle, $object_name, $data) {
        wp_localize_script($handle, $object_name, $data);
    }

    /**
     * Generuje URL do zasobu
     * 
     * @param string $file Ścieżka do pliku
     * @return string URL do zasobu
     */
    private function getAssetUrl($file) {
        return AICA_PLUGIN_URL . 'assets/' . $file;
    }

    /**
     * Pobiera wersję pliku
     * 
     * @param string $file Ścieżka do pliku
     * @return string Wersja pliku
     */
    private function getFileVersion($file) {
        $file_path = AICA_PLUGIN_DIR . 'assets/' . $file;
        return file_exists($file_path) ? filemtime($file_path) : $this->version;
    }

    /**
     * Pobiera tłumaczenia dla czatu
     * 
     * @return array Tłumaczenia
     */
    private function getChatTranslations() {
        return [
            'new_chat' => __('Nowa rozmowa', 'ai-chat-assistant'),
            'rename_chat' => __('Zmień nazwę', 'ai-chat-assistant'),
            'delete_chat' => __('Usuń rozmowę', 'ai-chat-assistant'),
            'confirm_delete' => __('Czy na pewno chcesz usunąć tę rozmowę?', 'ai-chat-assistant'),
            'cancel' => __('Anuluj', 'ai-chat-assistant'),
            'confirm' => __('Potwierdź', 'ai-chat-assistant'),
            'delete' => __('Usuń', 'ai-chat-assistant'),
            'send' => __('Wyślij', 'ai-chat-assistant'),
            'type_message' => __('Wpisz wiadomość...', 'ai-chat-assistant'),
            'upload_file' => __('Dodaj plik', 'ai-chat-assistant'),
            'search' => __('Szukaj rozmów...', 'ai-chat-assistant'),
            'history' => __('Historia', 'ai-chat-assistant'),
            'favorites' => __('Ulubione', 'ai-chat-assistant'),
            'settings' => __('Ustawienia', 'ai-chat-assistant'),
            'welcome_title' => __('Witaj w AI Chat!', 'ai-chat-assistant'),
            'welcome_message' => __('Rozpocznij nową rozmowę lub wybierz istniejącą z historii.', 'ai-chat-assistant'),
            'example_prompts' => __('Przykładowe pytania:', 'ai-chat-assistant'),
            'error_sending' => __('Nie udało się wysłać wiadomości. Spróbuj ponownie.', 'ai-chat-assistant'),
            'error_loading' => __('Nie udało się załadować historii. Odśwież stronę.', 'ai-chat-assistant'),
            'error_uploading' => __('Nie udało się przesłać pliku. Spróbuj ponownie.', 'ai-chat-assistant')
        ];
    }

    /**
     * Sprawdza czy użytkownik preferuje zredukowane animacje
     * 
     * @return bool True jeśli użytkownik preferuje zredukowane animacje
     */
    private function should_reduce_motion() {
        $cache_key = 'aica_reduced_motion_' . get_current_user_id();
        return $this->cache->remember($cache_key, function() {
            return isset($_COOKIE['aica_reduced_motion']) && $_COOKIE['aica_reduced_motion'] === 'true';
        });
    }
} 