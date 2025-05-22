<?php
namespace AICA\API;

class GitHubClient {
    private $token;
    private $api_url = 'https://api.github.com';

    public function __construct($token) {
        $this->token = $token;
    }

    /**
     * Testowanie połączenia z API
     */
    public function test_connection() {
        $args = [
            'headers' => [
                'Authorization' => 'token ' . $this->token,
                'User-Agent' => 'AI-Chat-Assistant-WordPress-Plugin'
            ],
            'timeout' => 15
        ];

        $response = wp_remote_get($this->api_url . '/user', $args);

        if (is_wp_error($response)) {
            error_log('GitHub API Error: ' . $response->get_error_message());
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
                'Authorization' => 'token ' . $this->token,
                'User-Agent' => 'AI-Chat-Assistant-WordPress-Plugin'
            ],
            'timeout' => 15
        ];

        $response = wp_remote_get($this->api_url . '/user/repos?per_page=100&sort=updated', $args);

        if (is_wp_error($response)) {
            error_log('GitHub API Error: ' . $response->get_error_message());
            return [];
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code < 200 || $status_code >= 300) {
            error_log('GitHub API Error: HTTP status ' . $status_code);
            return [];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($body)) {
            error_log('GitHub API Error: Niepoprawna odpowiedź');
            return [];
        }

        $repositories = [];
        foreach ($body as $repo) {
            $repositories[] = [
                'id' => $repo['id'],
                'name' => $repo['name'],
                'full_name' => $repo['full_name'],
                'owner' => $repo['owner']['login'],
                'url' => $repo['html_url'],
                'description' => $repo['description'] ?? '',
                'updated_at' => $repo['updated_at'],
                'default_branch' => $repo['default_branch'],
                'language' => $repo['language'] ?? ''
            ];
        }

        return $repositories;
    }

    /**
     * Pobranie informacji o repozytorium
     */
    public function get_repository_info($full_name) {
        $args = [
            'headers' => [
                'Authorization' => 'token ' . $this->token,
                'User-Agent' => 'AI-Chat-Assistant-WordPress-Plugin'
            ],
            'timeout' => 15
        ];

        $response = wp_remote_get($this->api_url . '/repos/' . $full_name, $args);

        if (is_wp_error($response)) {
            error_log('GitHub API Error: ' . $response->get_error_message());
            return false;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code < 200 || $status_code >= 300) {
            error_log('GitHub API Error: HTTP status ' . $status_code);
            return false;
        }

        $repo = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($repo)) {
            error_log('GitHub API Error: Niepoprawna odpowiedź');
            return false;
        }

        return [
            'name' => $repo['name'],
            'full_name' => $repo['full_name'],
            'owner' => $repo['owner']['login'],
            'html_url' => $repo['html_url'],
            'description' => $repo['description'] ?? '',
            'default_branch' => $repo['default_branch'],
            'language' => $repo['language'] ?? '',
            'avatar_url' => $repo['owner']['avatar_url'] ?? ''
        ];
    }

    /**
     * Pobranie zawartości pliku
     */
    public function get_file_content($owner, $repo, $path, $ref = 'main') {
        $url = $this->api_url . "/repos/{$owner}/{$repo}/contents/" . ltrim($path, '/');
        if (!empty($ref)) {
            $url .= "?ref=" . urlencode($ref);
        }

        $args = [
            'headers' => [
                'Authorization' => 'token ' . $this->token,
                'User-Agent' => 'AI-Chat-Assistant-WordPress-Plugin',
                'Accept' => 'application/vnd.github.v3.raw'
            ],
            'timeout' => 15 // Zwiększony timeout dla większych plików
        ];

        try {
            $response = wp_remote_get($url, $args);

            if (is_wp_error($response)) {
                error_log('GitHub API Error: ' . $response->get_error_message());
                return false;
            }

            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code < 200 || $status_code >= 300) {
                error_log('GitHub API Error: HTTP status ' . $status_code);
                return false;
            }

            $content = wp_remote_retrieve_body($response);
            
            // Określ język na podstawie rozszerzenia pliku
            $language = $this->get_language_from_filename(basename($path));

            return [
                'content' => $content,
                'size' => strlen($content),
                'name' => basename($path),
                'path' => $path,
                'language' => $language
            ];
        } catch (\Exception $e) {
            error_log('GitHub API Exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Pobranie zawartości katalogu
     */
    public function get_directory_contents($owner, $repo, $path = '', $ref = 'main') {
        $url = $this->api_url . "/repos/{$owner}/{$repo}/contents/" . ltrim($path, '/');
        if (!empty($ref)) {
            $url .= "?ref=" . urlencode($ref);
        }

        $args = [
            'headers' => [
                'Authorization' => 'token ' . $this->token,
                'User-Agent' => 'AI-Chat-Assistant-WordPress-Plugin'
            ],
            'timeout' => 15
        ];

        try {
            $response = wp_remote_get($url, $args);

            if (is_wp_error($response)) {
                error_log('GitHub API Error: ' . $response->get_error_message());
                return [];
            }

            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code < 200 || $status_code >= 300) {
                error_log('GitHub API Error: HTTP status ' . $status_code);
                return [];
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (!is_array($body)) {
                error_log('GitHub API Error: Niepoprawna odpowiedź');
                return [];
            }

            $contents = [];
            foreach ($body as $item) {
                $contents[] = [
                    'name' => $item['name'],
                    'path' => $item['path'],
                    'type' => $item['type'],
                    'size' => isset($item['size']) ? $item['size'] : 0,
                    'url' => $item['html_url']
                ];
            }

            return $contents;
        } catch (\Exception $e) {
            error_log('GitHub API Exception: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Pobieranie dostępnych gałęzi repozytorium
     */
    public function get_repository_branches($owner, $repo) {
        $url = $this->api_url . "/repos/{$owner}/{$repo}/branches";

        $args = [
            'headers' => [
                'Authorization' => 'token ' . $this->token,
                'User-Agent' => 'AI-Chat-Assistant-WordPress-Plugin'
            ],
            'timeout' => 15
        ];

        try {
            $response = wp_remote_get($url, $args);

            if (is_wp_error($response)) {
                error_log('GitHub API Error: ' . $response->get_error_message());
                return [];
            }

            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code < 200 || $status_code >= 300) {
                error_log('GitHub API Error: HTTP status ' . $status_code);
                return [];
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (!is_array($body)) {
                error_log('GitHub API Error: Niepoprawna odpowiedź');
                return [];
            }

            $branches = [];
            foreach ($body as $branch) {
                $branches[] = $branch['name'];
            }

            return $branches;
        } catch (\Exception $e) {
            error_log('GitHub API Exception: ' . $e->getMessage());
            return [];
        }
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

    /**
     * Wyszukiwanie w repozytorium
     */
    public function search_repository($owner, $repo, $query, $ref = 'main') {
        $url = $this->api_url . "/search/code?q=" . urlencode($query) . "+repo:" . urlencode($owner . '/' . $repo);
        
        // Dodaj informację o gałęzi, jeśli została podana
        if (!empty($ref) && $ref !== 'main') {
            $url .= "+ref:" . urlencode($ref);
        }

        $args = [
            'headers' => [
                'Authorization' => 'token ' . $this->token,
                'User-Agent' => 'AI-Chat-Assistant-WordPress-Plugin',
                'Accept' => 'application/vnd.github.v3+json'
            ],
            'timeout' => 15
        ];

        try {
            $response = wp_remote_get($url, $args);

            if (is_wp_error($response)) {
                error_log('GitHub API Error: ' . $response->get_error_message());
                return [];
            }

            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code < 200 || $status_code >= 300) {
                error_log('GitHub API Error: HTTP status ' . $status_code);
                return [];
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (!isset($body['items']) || !is_array($body['items'])) {
                error_log('GitHub API Error: Brak wyników wyszukiwania');
                return [];
            }

            $results = [];
            foreach ($body['items'] as $item) {
                // Pobierz fragment kodu
                $file_content = $this->get_file_content($owner, $repo, $item['path'], $ref);
                $snippet = '';
                
                if ($file_content) {
                    // Wygeneruj fragment z dopasowaniem
                    $content = $file_content['content'];
                    $pos = stripos($content, $query);
                    if ($pos !== false) {
                        $start = max(0, $pos - 50);
                        $end = min(strlen($content), $pos + strlen($query) + 50);
                        $snippet = substr($content, $start, $end - $start);
                        
                        if ($start > 0) {
                            $snippet = '...' . $snippet;
                        }
                        if ($end < strlen($content)) {
                            $snippet .= '...';
                        }
                    } else {
                        // Jeśli dokładne dopasowanie nie zostało znalezione, weź początek pliku
                        $snippet = substr($content, 0, 100) . '...';
                    }
                }
                
                $results[] = [
                    'path' => $item['path'],
                    'filename' => basename($item['path']),
                    'language' => $this->get_language_from_filename(basename($item['path'])),
                    'basename' => basename($item['path']),
                    'snippet' => $snippet,
                    'url' => $item['html_url']
                ];
            }

            return $results;
        } catch (\Exception $e) {
            error_log('GitHub API Exception: ' . $e->getMessage());
            return [];
        }
    }
}