<?php
declare(strict_types=1);

namespace AICA\Services;

use AICA\Helpers\TranslationHelper;

class TranslationService {
    public function get_chat_translations(): array {
        return TranslationHelper::get_chat_translations();
    }

    public function get_history_translations(): array {
        return TranslationHelper::get_history_translations();
    }

    public function get_repository_translations(): array {
        return TranslationHelper::get_repository_translations();
    }

    public function get_settings_translations(): array {
        return TranslationHelper::get_settings_translations();
    }

    public function get_diagnostics_translations(): array {
        return TranslationHelper::get_diagnostics_translations();
    }

    public function translate(string $key, string $section = 'chat'): string {
        $translations = match($section) {
            'chat' => $this->get_chat_translations(),
            'history' => $this->get_history_translations(),
            'repository' => $this->get_repository_translations(),
            'settings' => $this->get_settings_translations(),
            'diagnostics' => $this->get_diagnostics_translations(),
            default => []
        };

        return $translations[$key] ?? $key;
    }
} 