<?php
namespace AICA\API;

class BitbucketClient {
    private $username;
    private $app_password;
    private $api_url = 'https://api.bitbucket.org/2.0';

    public function __construct($username, $app_password) {
        $this->username = $username;
        $this->app_password = $app_password;
    }

    /**
     * Testowanie połączenia z API
     */
    public function test_connection() {
        $args = [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->username . ':' . $this->app_password),
                'User-Agent' => 'AI-Chat-Assistant-WordPress-Plugin'
            ]
        ];

        $response = wp_remote_get($this->api_url . '/user', $args);

        if (is_wp_error($response)) {
            return false;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        return $status_code >= 200 && $status_code < 300;
    }

    /**
     * Pobranie listy repozytoriów
     */
    public function get_repositories() {
        $args = [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->username . ':' . $this->app_password),
                'User-Agent' => 'AI-Chat-Assistant-WordPress-Plugin'
            ]
        ];

        $response = wp_remote_get($this->api_url . '/repositories/' . $this->username, $args);

        if (is_wp_error($response)) {
            return [];
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code < 200 || $status_code >= 300) {
            return [];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (!isset($body['values']) || !is_array($body['values'])) {
            return [];
        }

        $repositories = [];
        foreach ($body['values'] as $repo) {
            $repositories[] = [
                'id' => $repo['uuid'],
                'name' => $repo['name'],
                'full_name' => $repo['full_name'],
                'owner' => $repo['owner']['username'],
                'url' => $repo['links']['html']['href'],
                'description' => $repo['description'] ?? '',
                'updated_at' => $repo['updated_on']
            ];
        }

        return $repositories;
    }

    /**
     * Pobranie zawartości pliku
     */
    public function get_file_content($repo_full_name, $path, $ref = 'master') {
        // W Bitbucket API musimy najpierw pobrać SHA pliku
        $url = $this->api_url . "/repositories/{$repo_full_name}/src/{$ref}/" . ltrim($path, '/');

        $args = [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->username . ':' . $this->app_password),
                'User-Agent' => 'AI-Chat-Assistant-WordPress-Plugin'
            ]
        ];

        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            return false;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code < 200 || $status_code >= 300) {
            return false;
        }

        // Bitbucket API zwraca bezpośrednią zawartość pliku
        $content = wp_remote_retrieve_body($response);
        
        // Pobierz metadane pliku, aby uzyskać SHA
        $meta_url = $this->api_url . "/repositories/{$repo_full_name}/src/{$ref}/?path=" . urlencode($path);
        $meta_response = wp_remote_get($meta_url, $args);
        
        $sha = '';
        if (!is_wp_error($meta_response)) {
            $meta_body = json_decode(wp_remote_retrieve_body($meta_response), true);
            if (isset($meta_body['values'])) {
                foreach ($meta_body['values'] as $file) {
                    if ($file['path'] === $path) {
                        $sha = $file['commit']['hash'];
                        break;
                    }
                }
            }
        }

        return [
            'content' => $content,
            'size' => strlen($content),
            'name' => basename($path),
            'path' => $path,
            'sha' => $sha,
            'url' => $url,
            'language' => $this->get_language_from_filename(basename($path))
        ];
    }

    /**
     * Pobranie zawartości katalogu
     */
    public function get_directory_contents($repo_full_name, $path = '', $ref = 'master') {
        $url = $this->api_url . "/repositories/{$repo_full_name}/src/{$ref}/";
        
        if (!empty($path)) {
            $url .= ltrim($path, '/');
        }

        $args = [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->username . ':' . $this->app_password),
                'User-Agent' => 'AI-Chat-Assistant-WordPress-Plugin'
            ]
        ];

        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            return [];
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code < 200 || $status_code >= 300) {
            return [];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (!isset($body['values']) || !is_array($body['values'])) {
            return [];
        }

        $contents = [];
        foreach ($body['values'] as $item) {
            $contents[] = [
                'name' => basename($item['path']),
                'path' => $item['path'],
                'type' => $item['type'], // 'commit_directory' for directory, 'commit_file' for file
                'size' => isset($item['size']) ? $item['size'] : 0,
                'url' => isset($item['links']['self']['href']) ? $item['links']['self']['href'] : ''
            ];
        }

        return $contents;
    }

    /**
     * Wyszukiwanie w repozytorium
     */
    public function search_repository($repo_full_name, $query, $ref = 'master') {
        // Bitbucket nie ma bezpośredniego API do wyszukiwania kodu, ale możemy użyć endpointu wyszukiwania 
        // zawartości w danej gałęzi i filtrować ręcznie
        $url = $this->api_url . "/repositories/{$repo_full_name}/src/{$ref}";

        $args = [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->username . ':' . $this->app_password),
                'User-Agent' => 'AI-Chat-Assistant-WordPress-Plugin'
            ]
        ];

        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            return [];
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code < 200 || $status_code >= 300) {
            return [];
        }

        // Pobierz listę plików w repozytorium
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (!isset($body['values']) || !is_array($body['values'])) {
            return [];
        }

        // Rekurencyjnie pobierz wszystkie pliki z repozytorium
        $all_files = $this->get_all_files_recursively($repo_full_name, $ref);
        
        // Przeszukaj każdy plik pod kątem zawartości
        $results = [];
        foreach ($all_files as $file) {
            if ($file['type'] !== 'commit_file') {
                continue;
            }
            
            $file_content = $this->get_file_content($repo_full_name, $file['path'], $ref);
            
            if ($file_content && strpos($file_content['content'], $query) !== false) {
                // Wygeneruj fragment z dopasowaniem
                $content = $file_content['content'];
                $pos = strpos($content, $query);
                $start = max(0, $pos - 50);
                $end = min(strlen($content), $pos + strlen($query) + 50);
                $snippet = substr($content, $start, $end - $start);
                
                if ($start > 0) {
                    $snippet = '...' . $snippet;
                }
                if ($end < strlen($content)) {
                    $snippet .= '...';
                }
                
                $results[] = [
                    'path' => $file['path'],
                    'filename' => basename($file['path']),
                    'language' => $this->get_language_from_filename(basename($file['path'])),
                    'basename' => basename($file['path']),
                    'snippet' => $snippet,
                ];
            }
        }

        return $results;
    }

    /**
     * Rekurencyjne pobieranie wszystkich plików z repozytorium
     */
    private function get_all_files_recursively($repo_full_name, $ref, $path = '') {
        $contents = $this->get_directory_contents($repo_full_name, $path, $ref);
        
        $all_files = [];
        foreach ($contents as $item) {
            if ($item['type'] === 'commit_directory') {
                $subfolder_files = $this->get_all_files_recursively($repo_full_name, $ref, $item['path']);
                $all_files = array_merge($all_files, $subfolder_files);
            } else {
                $all_files[] = $item;
            }
        }
        
        return $all_files;
    }

    /**
     * Określenie języka na podstawie nazwy pliku
     */
    private function get_language_from_filename($filename) {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $language_map = [
            'php' => 'php',
            'js' => 'javascript',
            'jsx' => 'jsx',
            'ts' => 'typescript',
            'tsx' => 'tsx',
            'html' => 'html',
            'css' => 'css',
            'scss' => 'scss',
            'less' => 'less',
            'json' => 'json',
            'py' => 'python',
            'rb' => 'ruby',
            'java' => 'java',
            'c' => 'c',
            'cpp' => 'cpp',
            'h' => 'c',
            'hpp' => 'cpp',
            'cs' => 'csharp',
            'go' => 'go',
            'rs' => 'rust',
            'swift' => 'swift',
            'kt' => 'kotlin',
            'md' => 'markdown',
            'sql' => 'sql',
            'sh' => 'bash',
            'bat' => 'batch',
            'ps1' => 'powershell',
            'yml' => 'yaml',
            'yaml' => 'yaml',
            'xml' => 'xml',
            'vue' => 'vue',
            'dart' => 'dart'
        ];

        return isset($language_map[$extension]) ? $language_map[$extension] : '';
    }
}