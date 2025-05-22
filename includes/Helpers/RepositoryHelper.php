<?php
declare(strict_types=1);

namespace AICA\Helpers;

/**
 * Klasa pomocnicza do zarządzania repozytoriami
 * 
 * @package AICA
 * @since 1.0.0
 */
class RepositoryHelper {
    /**
     * Pobiera listę obsługiwanych typów repozytoriów
     *
     * @return array<string, string>
     */
    public static function get_supported_repository_types(): array {
        return [
            'github' => 'GitHub',
            'gitlab' => 'GitLab',
            'bitbucket' => 'Bitbucket'
        ];
    }

    /**
     * Sprawdza czy podany typ repozytorium jest obsługiwany
     *
     * @param string $type Typ repozytorium
     * @return bool
     */
    public static function is_supported_repository_type(string $type): bool {
        return in_array($type, array_keys(self::get_supported_repository_types()), true);
    }

    /**
     * Pobiera dane uwierzytelniające dla repozytorium
     *
     * @param string $type Typ repozytorium
     * @return array<string, string>
     */
    public static function get_repository_credentials(string $type): array {
        $settings = get_option('aica_settings', []);
        
        return match($type) {
            'github' => [
                'token' => $settings['github_token'] ?? ''
            ],
            'gitlab' => [
                'token' => $settings['gitlab_token'] ?? ''
            ],
            'bitbucket' => [
                'username' => $settings['bitbucket_username'] ?? '',
                'app_password' => $settings['bitbucket_app_password'] ?? ''
            ],
            default => []
        };
    }

    /**
     * Sprawdza czy dane uwierzytelniające są skonfigurowane
     *
     * @param string $type Typ repozytorium
     * @return bool
     */
    public static function are_credentials_configured(string $type): bool {
        $credentials = self::get_repository_credentials($type);
        
        return match($type) {
            'github', 'gitlab' => !empty($credentials['token']),
            'bitbucket' => !empty($credentials['username']) && !empty($credentials['app_password']),
            default => false
        };
    }

    /**
     * Sprawdza czy repozytorium jest dostępne
     *
     * @param string $url URL repozytorium
     * @return bool
     */
    public static function isRepositoryAccessible(string $url): bool {
        $response = wp_remote_get($url);
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }

    /**
     * Pobiera metadane repozytorium
     *
     * @param string $url URL repozytorium
     * @return array<string, mixed>|false
     */
    public static function getRepositoryMetadata(string $url): array|false {
        $response = wp_remote_get($url);
        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        return is_array($data) ? $data : false;
    }

    /**
     * Synchronizuje repozytorium
     *
     * @param string $url URL repozytorium
     * @return array<string, mixed>|false
     */
    public static function syncRepository(string $url): array|false {
        $metadata = self::getRepositoryMetadata($url);
        if (!$metadata) {
            return false;
        }

        // Aktualizuj cache
        wp_cache_set('repo_' . md5($url), $metadata, 'aica_repositories', 3600);
        return $metadata;
    }

    /**
     * Pobiera listę plików z repozytorium
     *
     * @param string $url URL repozytorium
     * @return array<string>
     */
    public static function getRepositoryFiles(string $url): array {
        $metadata = self::getRepositoryMetadata($url);
        if (!$metadata || !isset($metadata['files']) || !is_array($metadata['files'])) {
            return [];
        }

        return $metadata['files'];
    }

    /**
     * Sprawdza czy plik istnieje w repozytorium
     *
     * @param string $url URL repozytorium
     * @param string $file Nazwa pliku
     * @return bool
     */
    public static function fileExists(string $url, string $file): bool {
        $files = self::getRepositoryFiles($url);
        return in_array($file, $files, true);
    }
} 