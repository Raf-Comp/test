<?php
namespace AICA\Ajax;

use AICA\Services\SettingsService;
use AICA\Services\ErrorService;
use AICA\Services\ApiService;
use AICA\Services\ChatService;
use AICA\Services\CacheService;

class AjaxManager {
    private $settings_service;
    private $error_service;
    private $api_service;
    private $chat_service;
    private $cache_service;

    public function __construct() {
        // Inicjalizacja serwisów
        $this->error_service = ErrorService::getInstance();
        $this->cache_service = CacheService::getInstance();
        $this->settings_service = SettingsService::getInstance();
        $this->chat_service = new ChatService($this->error_service, $this->cache_service);
        $this->api_service = new ApiService($this->error_service, $this->settings_service);

        // Inicjalizacja wszystkich handlerów AJAX
        new ChatHandler();
        new RepositoryHandler();
        new SettingsHandler($this->settings_service, $this->error_service, $this->api_service);
        new DiagnosticsHandler();
        new HistoryHandler($this->chat_service, $this->error_service);
    }
}