<?php
namespace AICA\API;

class GitLabClient {
    private $token;
    private $api_url = 'https://gitlab.com/api/v4';

    public function __construct($token) {
        $this->token = $token;
    }

    /**
     * Testowanie połączenia z API
     */
    public function test_connection() {
        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
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
     * Pobranie listy repozytoriów (w GitLab nazywanych projektami)
     */
    public function get_repositories() {
        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
                'User-Agent' => 'AI-Chat-Assistant-WordPress-Plugin'
            ]
        ];

        $response = wp_remote_get($this->api_url . '/projects?membership=true&per_page=100&order_by=updated_at', $args);

        if (is_wp_error($response)) {
            return [];
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code < 200 || $status_code >= 300) {
            return [];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($body)) {
            return [];
        }

        $repositories = [];
        foreach ($body as $project) {
            $repositories[] = [
                'id' => $project['id'],
                'name' => $project['name'],
                'full_name' => $project['path_with_namespace'],
                'owner' => explode('/', $project['path_with_namespace'])[0],
                'url' => $project['web_url'],
                'description' => $project['description'],
                'updated_at' => $project['last_activity_at']
            ];
        }

        return $repositories;
    }

    /**
     * Pobranie zawartości pliku
     */
    public function get_file_content($project_id, $path, $ref = 'main') {
        $encoded_path = urlencode($path);
        $url = $this->api_url . "/projects/{$project_id}/repository/files/{$encoded_path}";
        if (!empty($ref)) {
            $url .= "?ref=" . urlencode($ref);
        }

        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
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

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (!isset($body['content']) || !isset($body['encoding'])) {
            return false;
        }

        // Dekodowanie zawartości (zazwyczaj base64)
        $content = '';
        if ($body['encoding'] === 'base64') {
            $content = base64_decode($body['content']);
        }

        return [
            'content' => $content,
            'size' => $body['size'],
            'name' => basename($path),
            'path' => $path,
            'sha' => $body['blob_id'],
            'url' => $body['web_url'] ?? '',
            'language' => $this->get_language_from_filename(basename($path))
        ];
    }

    /**
     * Pobranie zawartości katalogu
     */
    public function get_directory_contents($project_id, $path = '', $ref = 'main') {
        $url = $this->api_url . "/projects/{$project_id}/repository/tree";
        $params = ['path' => $path];
        
        if (!empty($ref)) {
            $params['ref'] = $ref;
        }
        
        $url .= '?' . http_build_query($params);

        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
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
        if (!is_array($body)) {
            return [];
        }

        $contents = [];
        foreach ($body as $item) {
            $contents[] = [
                'name' => $item['name'],
                'path' => $item['path'],
                'type' => $item['type'], // 'tree' for directory, 'blob' for file
                'size' => isset($item['size']) ? $item['size'] : 0,
                'url' => isset($item['web_url']) ? $item['web_url'] : ''
            ];
        }

        return $contents;
    }

    /**
     * Wyszukiwanie w repozytorium
     */
    public function search_repository($project_id, $query, $ref = 'main') {
        $url = $this->api_url . "/projects/{$project_id}/search";
        $params = [
            'scope' => 'blobs',
            'search' => $query,
            'ref' => $ref
        ];
        
        $url .= '?' . http_build_query($params);

        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
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
        if (!is_array($body)) {
            return [];
        }

        $results = [];
        foreach ($body as $item) {
            // Pobierz fragment tekstu z dopasowaniem
            $results[] = [
                'path' => $item['path'],
                'filename' => basename($item['path']),
                'language' => $this->get_language_from_filename(basename($item['path'])),
                'basename' => basename($item['path']),
                'snippet' => $item['data'] ?? '',
            ];
        }

        return $results;
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