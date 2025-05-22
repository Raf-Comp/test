<?php
declare(strict_types=1);

namespace AICA\Services;

use AICA\API\GitHubClient;
use AICA\API\GitLabClient;
use AICA\API\BitbucketClient;
use AICA\Helpers\SecurityHelper;
use AICA\Helpers\ValidationHelper;
use AICA\Helpers\TableHelper;

class RepositoryService {
    private readonly string $table_name;
    private readonly \wpdb $db;

    public function __construct() {
        $this->table_name = TableHelper::get_table_name('repositories');
        global $wpdb;
        $this->db = $wpdb;
    }

    /**
     * Sprawdza czy tabela repozytoriów istnieje i tworzy ją jeśli potrzeba
     */
    private function maybe_create_table(): void {
        $table_name = $this->db->prefix . 'aica_repositories';
        
        // Sprawdź czy tabela istnieje
        if ($this->db->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            error_log("AICA - Tworzenie tabeli repozytoriów");
            
            $charset_collate = $this->db->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                user_id bigint(20) NOT NULL,
                repo_type varchar(20) NOT NULL,
                repo_name varchar(255) NOT NULL,
                repo_owner varchar(255) NOT NULL,
                repo_url varchar(255) NOT NULL,
                repo_external_id varchar(255) DEFAULT '',
                repo_description text DEFAULT '',
                languages varchar(255) DEFAULT '',
                default_branch varchar(50) DEFAULT 'main',
                avatar_url varchar(255) DEFAULT '',
                html_url varchar(255) DEFAULT '',
                created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                updated_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                PRIMARY KEY  (id),
                KEY user_id (user_id)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            error_log("AICA - Rezultat tworzenia tabeli: " . ($this->db->get_var("SHOW TABLES LIKE '$table_name'") == $table_name ? "Sukces" : "Niepowodzenie"));
        }
    }

    /**
     * Pobranie repozytoriów z wybranej platformy
     */
    public function get_repositories(string $type = 'github'): array {
        try {
            return match($type) {
                'github' => $this->get_github_repositories(),
                'gitlab' => $this->get_gitlab_repositories(),
                'bitbucket' => $this->get_bitbucket_repositories(),
                default => []
            };
        } catch (\Exception $e) {
            error_log('Repository Service Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Pobranie repozytoriów z GitHub
     */
    private function get_github_repositories(): array {
        $github_token = aica_get_option('github_token', '');
        if (empty($github_token)) {
            return [];
        }

        $github_client = new GitHubClient($github_token);
        return $github_client->get_repositories();
    }

    /**
     * Pobranie repozytoriów z GitLab
     */
    private function get_gitlab_repositories(): array {
        $gitlab_token = aica_get_option('gitlab_token', '');
        if (empty($gitlab_token)) {
            return [];
        }

        $gitlab_client = new GitLabClient($gitlab_token);
        return $gitlab_client->get_repositories();
    }

    /**
     * Pobranie repozytoriów z Bitbucket
     */
    private function get_bitbucket_repositories(): array {
        $bitbucket_username = aica_get_option('bitbucket_username', '');
        $bitbucket_app_password = aica_get_option('bitbucket_app_password', '');
        if (empty($bitbucket_username) || empty($bitbucket_app_password)) {
            return [];
        }

        $bitbucket_client = new BitbucketClient($bitbucket_username, $bitbucket_app_password);
        return $bitbucket_client->get_repositories();
    }

    /**
     * Zapisanie nowego repozytorium
     */
    public function save_repository(string $type, string $name, string $owner, string $url, string $repo_id = '', string $description = ''): int|false {
        try {
            // Walidacja danych
            if (empty($type) || empty($name) || empty($owner) || empty($url)) {
                return false;
            }
            
            $user_id = get_current_user_id();
            
            if (!$user_id) {
                return false;
            }
            
            // Twórz tabelę jeśli nie istnieje
            $this->maybe_create_table();
            
            $now = current_time('mysql');
            
            // Sprawdź czy repozytorium już istnieje
            $exists = false;
            if (!empty($repo_id)) {
                $exists = $this->db->get_var(
                    $this->db->prepare(
                        "SELECT id FROM {$this->db->prefix}aica_repositories WHERE user_id = %d AND repo_external_id = %s AND repo_type = %s",
                        $user_id, $repo_id, $type
                    )
                );
            }
            
            if (!$exists && !empty($owner) && !empty($name)) {
                // Sprawdź czy istnieje repozytorium o tej samej nazwie i właścicielu
                $exists = $this->db->get_var(
                    $this->db->prepare(
                        "SELECT id FROM {$this->db->prefix}aica_repositories WHERE user_id = %d AND repo_type = %s AND repo_owner = %s AND repo_name = %s",
                        $user_id, $type, $owner, $name
                    )
                );
            }
            
            if ($exists) {
                // Aktualizuj istniejące repozytorium
                $result = $this->db->update(
                    $this->db->prefix . 'aica_repositories',
                    [
                        'repo_name' => $name,
                        'repo_owner' => $owner,
                        'repo_url' => $url,
                        'repo_description' => $description,
                        'updated_at' => $now
                    ],
                    ['id' => $exists],
                    ['%s', '%s', '%s', '%s', '%s'],
                    ['%d']
                );
                
                return $exists;
            } else {
                // Dodaj nowe repozytorium
                $result = $this->db->insert(
                    $this->db->prefix . 'aica_repositories',
                    [
                        'user_id' => $user_id,
                        'repo_type' => $type,
                        'repo_name' => $name,
                        'repo_owner' => $owner,
                        'repo_url' => $url,
                        'repo_external_id' => $repo_id,
                        'repo_description' => $description,
                        'default_branch' => 'main', // Domyślna gałąź, będzie aktualizowana później
                        'created_at' => $now,
                        'updated_at' => $now
                    ],
                    ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
                );
                
                if ($result) {
                    return $this->db->insert_id;
                } else {
                    return false;
                }
            }
        } catch (\Exception $e) {
            error_log('Repository Service Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Usunięcie repozytorium
     */
    public function delete_repository(int $repo_id): bool {
        try {
            $user_id = get_current_user_id();
            
            if (!$user_id) {
                return false;
            }

            $result = $this->db->delete(
                $this->db->prefix . 'aica_repositories',
                [
                    'id' => $repo_id,
                    'user_id' => $user_id
                ],
                ['%d', '%d']
            );

            return $result !== false;
        } catch (\Exception $e) {
            error_log('Repository Service Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Pobranie zapisanych repozytoriów użytkownika
     */
    public function get_saved_repositories(?int $user_id = null): array {
        try {
            if ($user_id === null) {
                $user_id = get_current_user_id();
                if (!$user_id) {
                    return [];
                }
            }
            
            $table = $this->db->prefix . 'aica_repositories';
            
            // Sprawdź czy tabela istnieje
            $table_exists = $this->db->get_var($this->db->prepare(
                "SHOW TABLES LIKE %s",
                $table
            )) === $table;
            
            if (!$table_exists) {
                // Twórz tabelę jeśli nie istnieje
                $this->maybe_create_table();
                return [];
            }
            
            $results = $this->db->get_results(
                $this->db->prepare(
                    "SELECT * FROM $table WHERE user_id = %d ORDER BY repo_type, repo_name",
                    $user_id
                ),
                ARRAY_A
            );
            
            return $results ?: [];
        } catch (\Exception $e) {
            error_log('Repository Service Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Pobranie pojedynczego repozytorium
     */
    public function get_repository(int $repo_id): ?array {
        try {
            $user_id = get_current_user_id();
            
            if (!$user_id) {
                return null;
            }
            
            $result = $this->db->get_row(
                $this->db->prepare(
                    "SELECT * FROM {$this->db->prefix}aica_repositories WHERE id = %d AND user_id = %d",
                    $repo_id,
                    $user_id
                ),
                ARRAY_A
            );
            
            return $result ?: null;
        } catch (\Exception $e) {
            error_log('Repository Service Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Pobranie plików z repozytorium
     */
    public function get_repository_files(int $repo_id, string $path = '', string $branch = 'main'): array {
        try {
            $repo = $this->get_repository($repo_id);
            
            if (!$repo) {
                return [];
            }
            
            $user_id = get_current_user_id();
            
            if (!$user_id) {
                return [];
            }
            
            return match($repo['repo_type']) {
                'github' => $this->get_github_files($repo, $path, $branch),
                'gitlab' => $this->get_gitlab_files($repo, $path, $branch),
                'bitbucket' => $this->get_bitbucket_files($repo, $path, $branch),
                default => []
            };
        } catch (\Exception $e) {
            error_log('Repository Service Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Pobranie plików z GitHub
     */
    private function get_github_files(array $repo, string $path, string $branch): array {
        $github_token = aica_get_option('github_token', '');
        if (empty($github_token)) {
            return [];
        }

        $github_client = new GitHubClient($github_token);
        return $github_client->get_repository_files($repo['repo_owner'], $repo['repo_name'], $path, $branch);
    }

    /**
     * Pobranie plików z GitLab
     */
    private function get_gitlab_files(array $repo, string $path, string $branch): array {
        $gitlab_token = aica_get_option('gitlab_token', '');
        if (empty($gitlab_token)) {
            return [];
        }

        $gitlab_client = new GitLabClient($gitlab_token);
        return $gitlab_client->get_repository_files($repo['repo_owner'], $repo['repo_name'], $path, $branch);
    }

    /**
     * Pobranie plików z Bitbucket
     */
    private function get_bitbucket_files(array $repo, string $path, string $branch): array {
        $bitbucket_username = aica_get_option('bitbucket_username', '');
        $bitbucket_app_password = aica_get_option('bitbucket_app_password', '');
        if (empty($bitbucket_username) || empty($bitbucket_app_password)) {
            return [];
        }

        $bitbucket_client = new BitbucketClient($bitbucket_username, $bitbucket_app_password);
        return $bitbucket_client->get_repository_files($repo['repo_owner'], $repo['repo_name'], $path, $branch);
    }

    /**
     * Pobranie zawartości pliku
     */
    public function get_file_content(int $repo_id, string $path, string $branch = 'main'): ?string {
        try {
            $repo = $this->get_repository($repo_id);
            
            if (!$repo) {
                return null;
            }
            
            $user_id = get_current_user_id();
            
            if (!$user_id) {
                return null;
            }
            
            return match($repo['repo_type']) {
                'github' => $this->get_github_file_content($repo, $path, $branch),
                'gitlab' => $this->get_gitlab_file_content($repo, $path, $branch),
                'bitbucket' => $this->get_bitbucket_file_content($repo, $path, $branch),
                default => null
            };
        } catch (\Exception $e) {
            error_log('Repository Service Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Pobranie zawartości pliku z GitHub
     */
    private function get_github_file_content(array $repo, string $path, string $branch): ?string {
        $github_token = aica_get_option('github_token', '');
        if (empty($github_token)) {
            return null;
        }

        $github_client = new GitHubClient($github_token);
        return $github_client->get_file_content($repo['repo_owner'], $repo['repo_name'], $path, $branch);
    }

    /**
     * Pobranie zawartości pliku z GitLab
     */
    private function get_gitlab_file_content(array $repo, string $path, string $branch): ?string {
        $gitlab_token = aica_get_option('gitlab_token', '');
        if (empty($gitlab_token)) {
            return null;
        }

        $gitlab_client = new GitLabClient($gitlab_token);
        return $gitlab_client->get_file_content($repo['repo_owner'], $repo['repo_name'], $path, $branch);
    }

    /**
     * Pobranie zawartości pliku z Bitbucket
     */
    private function get_bitbucket_file_content(array $repo, string $path, string $branch): ?string {
        $bitbucket_username = aica_get_option('bitbucket_username', '');
        $bitbucket_app_password = aica_get_option('bitbucket_app_password', '');
        if (empty($bitbucket_username) || empty($bitbucket_app_password)) {
            return null;
        }

        $bitbucket_client = new BitbucketClient($bitbucket_username, $bitbucket_app_password);
        return $bitbucket_client->get_file_content($repo['repo_owner'], $repo['repo_name'], $path, $branch);
    }

    /**
     * Pobranie gałęzi repozytorium
     */
    public function get_repository_branches(int $repo_id): array {
        try {
            $repo = $this->get_repository($repo_id);
            
            if (!$repo) {
                return [];
            }
            
            $user_id = get_current_user_id();
            
            if (!$user_id) {
                return [];
            }
            
            return match($repo['repo_type']) {
                'github' => $this->get_github_branches($repo),
                'gitlab' => $this->get_gitlab_branches($repo),
                'bitbucket' => $this->get_bitbucket_branches($repo),
                default => []
            };
        } catch (\Exception $e) {
            error_log('Repository Service Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Pobranie gałęzi z GitHub
     */
    private function get_github_branches(array $repo): array {
        $github_token = aica_get_option('github_token', '');
        if (empty($github_token)) {
            return [];
        }

        $github_client = new GitHubClient($github_token);
        return $github_client->get_repository_branches($repo['repo_owner'], $repo['repo_name']);
    }

    /**
     * Pobranie gałęzi z GitLab
     */
    private function get_gitlab_branches(array $repo): array {
        $gitlab_token = aica_get_option('gitlab_token', '');
        if (empty($gitlab_token)) {
            return [];
        }

        $gitlab_client = new GitLabClient($gitlab_token);
        return $gitlab_client->get_repository_branches($repo['repo_owner'], $repo['repo_name']);
    }

    /**
     * Pobranie gałęzi z Bitbucket
     */
    private function get_bitbucket_branches(array $repo): array {
        $bitbucket_username = aica_get_option('bitbucket_username', '');
        $bitbucket_app_password = aica_get_option('bitbucket_app_password', '');
        if (empty($bitbucket_username) || empty($bitbucket_app_password)) {
            return [];
        }

        $bitbucket_client = new BitbucketClient($bitbucket_username, $bitbucket_app_password);
        return $bitbucket_client->get_repository_branches($repo['repo_owner'], $repo['repo_name']);
    }

    /**
     * Odświeżenie metadanych repozytorium
     */
    public function refresh_repository_metadata(int $repo_id): bool {
        try {
            $repo = $this->get_repository($repo_id);
            
            if (!$repo) {
                return false;
            }
            
            $user_id = get_current_user_id();
            
            if (!$user_id) {
                return false;
            }
            
            $metadata = match($repo['repo_type']) {
                'github' => $this->get_github_metadata($repo),
                'gitlab' => $this->get_gitlab_metadata($repo),
                'bitbucket' => $this->get_bitbucket_metadata($repo),
                default => null
            };
            
            if (!$metadata) {
                return false;
            }
            
            $result = $this->db->update(
                $this->db->prefix . 'aica_repositories',
                [
                    'repo_description' => $metadata['description'] ?? '',
                    'languages' => $metadata['languages'] ?? '',
                    'default_branch' => $metadata['default_branch'] ?? 'main',
                    'avatar_url' => $metadata['avatar_url'] ?? '',
                    'html_url' => $metadata['html_url'] ?? '',
                    'updated_at' => current_time('mysql')
                ],
                ['id' => $repo_id],
                ['%s', '%s', '%s', '%s', '%s', '%s'],
                ['%d']
            );
            
            return $result !== false;
        } catch (\Exception $e) {
            error_log('Repository Service Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Pobranie metadanych z GitHub
     */
    private function get_github_metadata(array $repo): ?array {
        $github_token = aica_get_option('github_token', '');
        if (empty($github_token)) {
            return null;
        }

        $github_client = new GitHubClient($github_token);
        return $github_client->get_repository_metadata($repo['repo_owner'], $repo['repo_name']);
    }

    /**
     * Pobranie metadanych z GitLab
     */
    private function get_gitlab_metadata(array $repo): ?array {
        $gitlab_token = aica_get_option('gitlab_token', '');
        if (empty($gitlab_token)) {
            return null;
        }

        $gitlab_client = new GitLabClient($gitlab_token);
        return $gitlab_client->get_repository_metadata($repo['repo_owner'], $repo['repo_name']);
    }

    /**
     * Pobranie metadanych z Bitbucket
     */
    private function get_bitbucket_metadata(array $repo): ?array {
        $bitbucket_username = aica_get_option('bitbucket_username', '');
        $bitbucket_app_password = aica_get_option('bitbucket_app_password', '');
        if (empty($bitbucket_username) || empty($bitbucket_app_password)) {
            return null;
        }

        $bitbucket_client = new BitbucketClient($bitbucket_username, $bitbucket_app_password);
        return $bitbucket_client->get_repository_metadata($repo['repo_owner'], $repo['repo_name']);
    }

    /**
     * Dodanie nowego repozytorium
     */
    public function add_repository(string $type, string $url, string $token): array {
        try {
            if (empty($type) || empty($url) || empty($token)) {
                return [
                    'success' => false,
                    'message' => __('Missing required parameters', 'ai-chat-assistant')
                ];
            }

            $client = match($type) {
                'github' => new GitHubClient($token),
                'gitlab' => new GitLabClient($token),
                'bitbucket' => new BitbucketClient($token),
                default => null
            };

            if (!$client) {
                return [
                    'success' => false,
                    'message' => __('Invalid repository type', 'ai-chat-assistant')
                ];
            }

            $metadata = $client->get_repository_metadata_from_url($url);
            if (!$metadata) {
                return [
                    'success' => false,
                    'message' => __('Could not fetch repository metadata', 'ai-chat-assistant')
                ];
            }

            $repo_id = $this->save_repository(
                $type,
                $metadata['name'],
                $metadata['owner'],
                $url,
                $metadata['id'] ?? '',
                $metadata['description'] ?? ''
            );

            if (!$repo_id) {
                return [
                    'success' => false,
                    'message' => __('Failed to save repository', 'ai-chat-assistant')
                ];
            }

            return [
                'success' => true,
                'message' => __('Repository added successfully', 'ai-chat-assistant'),
                'repo_id' => $repo_id
            ];
        } catch (\Exception $e) {
            error_log('Repository Service Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Pobranie tokenu repozytorium
     */
    public function get_repository_token(int $id): ?string {
        try {
            $repo = $this->get_repository($id);
            
            if (!$repo) {
                return null;
            }
            
            return match($repo['repo_type']) {
                'github' => aica_get_option('github_token', ''),
                'gitlab' => aica_get_option('gitlab_token', ''),
                'bitbucket' => aica_get_option('bitbucket_app_password', ''),
                default => null
            };
        } catch (\Exception $e) {
            error_log('Repository Service Error: ' . $e->getMessage());
            return null;
        }
    }
}