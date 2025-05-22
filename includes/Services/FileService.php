<?php
namespace AICA\Services;

class FileService {
    /**
     * Obsługa przesyłanych plików
     */
    public function handle_upload($file) {
        // Sprawdzenie błędów
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'message' => $this->get_upload_error_message($file['error'])
            ];
        }

        // Weryfikacja typu pliku
        $file_type = wp_check_filetype($file['name']);
        if (!$this->is_allowed_file_type($file_type['ext'])) {
            return [
                'success' => false,
                'message' => __('Niedozwolony typ pliku.', 'ai-chat-assistant')
            ];
        }

        // Przygotowanie katalogu do zapisu
        $upload_dir = wp_upload_dir();
        $target_dir = $upload_dir['basedir'] . '/aica-uploads/' . date('Y/m');
        
        if (!file_exists($target_dir)) {
            wp_mkdir_p($target_dir);
        }

        // Generowanie bezpiecznej nazwy pliku
        $filename = wp_unique_filename($target_dir, $file['name']);
        $target_file = $target_dir . '/' . $filename;

        // Przeniesienie pliku tymczasowego
        if (!move_uploaded_file($file['tmp_name'], $target_file)) {
            return [
                'success' => false,
                'message' => __('Nie udało się zapisać pliku.', 'ai-chat-assistant')
            ];
        }

        // Określenie typu MIME
        $filetype = wp_check_filetype($filename, null);
        
        // Zwrócenie ścieżki do pliku i dodatkowych informacji
        return [
            'success' => true,
            'file_path' => $target_file,
            'file_url' => $upload_dir['baseurl'] . '/aica-uploads/' . date('Y/m') . '/' . $filename,
            'file_name' => $file['name'],
            'file_type' => $filetype['type'],
            'file_size' => $file['size']
        ];
    }

    /**
     * Sprawdzenie, czy typ pliku jest dozwolony
     */
    private function is_allowed_file_type($extension) {
        $allowed_extensions = get_option('aica_allowed_file_extensions', 'txt,pdf,php,js,css,html,json,md');
        $allowed_extensions = explode(',', $allowed_extensions);
        $allowed_extensions = array_map('trim', $allowed_extensions);

        return in_array(strtolower($extension), $allowed_extensions);
    }

    /**
     * Pobranie komunikatu błędu na podstawie kodu
     */
    private function get_upload_error_message($error_code) {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
                return __('Przesłany plik przekracza limit określony w php.ini.', 'ai-chat-assistant');
            case UPLOAD_ERR_FORM_SIZE:
                return __('Przesłany plik przekracza limit określony w formularzu.', 'ai-chat-assistant');
            case UPLOAD_ERR_PARTIAL:
                return __('Plik został przesłany tylko częściowo.', 'ai-chat-assistant');
            case UPLOAD_ERR_NO_FILE:
                return __('Nie przesłano pliku.', 'ai-chat-assistant');
            case UPLOAD_ERR_NO_TMP_DIR:
                return __('Brak folderu tymczasowego.', 'ai-chat-assistant');
            case UPLOAD_ERR_CANT_WRITE:
                return __('Nie udało się zapisać pliku na dysku.', 'ai-chat-assistant');
            case UPLOAD_ERR_EXTENSION:
                return __('Przesyłanie pliku zostało zatrzymane przez rozszerzenie PHP.', 'ai-chat-assistant');
            default:
                return __('Wystąpił nieznany błąd podczas przesyłania pliku.', 'ai-chat-assistant');
        }
    }

    /**
     * Odczytanie zawartości pliku
     */
    public function read_file($file_path) {
        if (!file_exists($file_path)) {
            return false;
        }

        return file_get_contents($file_path);
    }
}