<?php
/**
 * Szablon strony repozytoriów
 */
?>
<div class="wrap">
    <h1 class="aica-page-title"><?php _e('Zarządzanie repozytoriami', 'ai-chat-assistant'); ?></h1>
    
    <div class="aica-notifications-container"></div>
    
    <div class="aica-dashboard">
        <!-- Panel boczny -->
        <div class="aica-sidebar">
            <div class="aica-sidebar-section">
                <h3 class="aica-sidebar-title"><?php _e('Źródła', 'ai-chat-assistant'); ?></h3>
                <ul class="aica-sources-list">
                    <li class="aica-source-item active" data-source="saved">
                        <span class="aica-source-icon">
                            <span class="dashicons dashicons-star-filled"></span>
                        </span>
                        <span class="aica-source-name"><?php _e('Zapisane repozytoria', 'ai-chat-assistant'); ?></span>
                        <span class="aica-source-count"><?php echo count($saved_repositories); ?></span>
                    </li>
                    
                    <?php if (!empty($github_token)): ?>
                    <li class="aica-source-item" data-source="github">
                        <span class="aica-source-icon">
                            <span class="dashicons dashicons-code-standards"></span>
                        </span>
                        <span class="aica-source-name">GitHub</span>
                        <span class="aica-source-count"><?php echo count($github_repos); ?></span>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (!empty($gitlab_token)): ?>
                    <li class="aica-source-item" data-source="gitlab">
                        <span class="aica-source-icon">
                            <span class="dashicons dashicons-editor-code"></span>
                        </span>
                        <span class="aica-source-name">GitLab</span>
                        <span class="aica-source-count"><?php echo count($gitlab_repos); ?></span>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (!empty($bitbucket_username) && !empty($bitbucket_app_password)): ?>
                    <li class="aica-source-item" data-source="bitbucket">
                        <span class="aica-source-icon">
                            <span class="dashicons dashicons-cloud"></span>
                        </span>
                        <span class="aica-source-name">Bitbucket</span>
                        <span class="aica-source-count"><?php echo count($bitbucket_repos); ?></span>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <div class="aica-sidebar-section" id="aica-language-filter" style="display: none;">
                <h3 class="aica-sidebar-title"><?php _e('Filtry języka', 'ai-chat-assistant'); ?></h3>
                <div class="aica-filters-container">
                    <?php
                    // Pobierz wszystkie języki z repozytoriów
                    $languages = [];
                    foreach ($saved_repositories as $repo) {
                        if (!empty($repo['languages'])) {
                            $repo_languages = explode(',', $repo['languages']);
                            foreach ($repo_languages as $lang) {
                                $lang = trim($lang);
                                if (!empty($lang)) {
                                    $languages[$lang] = isset($languages[$lang]) ? $languages[$lang] + 1 : 1;
                                }
                            }
                        }
                    }
                    
                    // Sortuj języki alfabetycznie
                    ksort($languages);
                    
                    // Wyświetl filtry języków
                    foreach ($languages as $lang => $count) {
                        ?>
                        <div class="aica-filter-item">
                            <label class="aica-filter-label">
                                <input type="checkbox" class="aica-language-checkbox" value="<?php echo esc_attr($lang); ?>">
                                <?php echo esc_html($lang); ?>
                                <span class="aica-filter-count"><?php echo $count; ?></span>
                            </label>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <!-- Zawartość główna -->
        <div class="aica-main-content">
            <div class="aica-toolbar">
                <div class="aica-search-container">
                    <input type="text" id="aica-search-repositories" placeholder="<?php _e('Szukaj repozytoriów...', 'ai-chat-assistant'); ?>" class="aica-search-input">
                    <span class="aica-search-icon">
                        <span class="dashicons dashicons-search"></span>
                    </span>
                </div>
                
                <div class="aica-toolbar-actions">
                    <select class="aica-sort-select">
                        <option value="name_asc"><?php _e('Nazwa (A-Z)', 'ai-chat-assistant'); ?></option>
                        <option value="name_desc"><?php _e('Nazwa (Z-A)', 'ai-chat-assistant'); ?></option>
                        <option value="date_desc"><?php _e('Najnowsze', 'ai-chat-assistant'); ?></option>
                        <option value="date_asc"><?php _e('Najstarsze', 'ai-chat-assistant'); ?></option>
                    </select>
                    
                    <button class="aica-theme-toggle" title="<?php _e('Przełącz tryb ciemny/jasny', 'ai-chat-assistant'); ?>">
                        <span class="dashicons dashicons-visibility"></span>
                    </button>
                    
                    <button class="aica-add-repo-button aica-add-repository-button">
                        <span class="dashicons dashicons-plus"></span>
                        <?php _e('Dodaj repozytorium', 'ai-chat-assistant'); ?>
                    </button>
                </div>
            </div>
            
            <!-- Panel z repozytoriami -->
            <div class="aica-repositories-content">
                <div id="saved-repositories" class="aica-repos-tab active">
                    <?php if (empty($saved_repositories)): ?>
                    <!-- Stan pusty -->
                    <div class="aica-empty-state">
                        <div class="aica-empty-icon">
                            <span class="dashicons dashicons-code-standards"></span>
                        </div>
                        <h2><?php _e('Brak zapisanych repozytoriów', 'ai-chat-assistant'); ?></h2>
                        <p><?php _e('Nie masz jeszcze żadnych zapisanych repozytoriów. Dodaj swoje pierwsze repozytorium.', 'ai-chat-assistant'); ?></p>
                        <button type="button" class="aica-button aica-button-primary aica-add-repository-button">
                            <span class="dashicons dashicons-plus"></span>
                            <?php _e('Dodaj repozytorium', 'ai-chat-assistant'); ?>
                        </button>
                    </div>
                    <?php else: ?>
                    <!-- Siatka repozytoriów -->
                    <div class="aica-repositories-grid">
                        <?php foreach ($saved_repositories as $repo): ?>
                        <!-- Karta repozytorium -->
                        <div class="aica-repository-card" data-languages="<?php echo esc_attr(!empty($repo['languages']) ? $repo['languages'] : ''); ?>">
                            <!-- Nagłówek karty -->
                            <div class="aica-repo-header">
                                <div class="aica-repo-icon">
                                    <?php
                                    $icon_class = 'dashicons-code-standards';
                                    if ($repo['repo_type'] === 'gitlab') {
                                        $icon_class = 'dashicons-editor-code';
                                    } elseif ($repo['repo_type'] === 'bitbucket') {
                                        $icon_class = 'dashicons-cloud';
                                    }
                                    ?>
                                    <span class="dashicons <?php echo esc_attr($icon_class); ?>"></span>
                                </div>
                                <div class="aica-repo-title">
                                    <h3><?php echo esc_html($repo['repo_name']); ?></h3>
                                    <div class="aica-repo-owner"><?php echo esc_html($repo['repo_owner']); ?></div>
                                </div>
                                <div class="aica-repo-actions">
                                    <div class="aica-dropdown">
                                        <button type="button" class="aica-dropdown-toggle">
                                            <span class="dashicons dashicons-ellipsis"></span>
                                        </button>
                                        <div class="aica-dropdown-menu">
                                            <a href="#" class="aica-dropdown-item aica-browse-repo" data-repo-id="<?php echo esc_attr($repo['id']); ?>">
                                                <span class="dashicons dashicons-visibility"></span>
                                                <?php _e('Przeglądaj pliki', 'ai-chat-assistant'); ?>
                                            </a>
                                            <a href="#" class="aica-dropdown-item aica-refresh-repo" data-repo-id="<?php echo esc_attr($repo['id']); ?>">
                                                <span class="dashicons dashicons-update"></span>
                                                <?php _e('Odśwież metadane', 'ai-chat-assistant'); ?>
                                            </a>
                                            <div class="aica-dropdown-divider"></div>
                                            <a href="#" class="aica-dropdown-item aica-delete-repo" data-repo-id="<?php echo esc_attr($repo['id']); ?>">
                                                <span class="dashicons dashicons-trash"></span>
                                                <?php _e('Usuń repozytorium', 'ai-chat-assistant'); ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Treść karty -->
                            <div class="aica-repo-body">
                                <?php if (!empty($repo['repo_description'])): ?>
                                <div class="aica-repo-description">
                                    <p><?php echo esc_html($repo['repo_description']); ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <div class="aica-repo-metadata">
                                    <div class="aica-meta-list">
                                        <div class="aica-meta-item">
                                            <span class="aica-meta-label"><?php _e('Typ:', 'ai-chat-assistant'); ?></span>
                                            <span class="aica-meta-value">
                                                <?php
                                                $type_name = 'GitHub';
                                                if ($repo['repo_type'] === 'gitlab') {
                                                    $type_name = 'GitLab';
                                                } elseif ($repo['repo_type'] === 'bitbucket') {
                                                    $type_name = 'Bitbucket';
                                                }
                                                echo esc_html($type_name);
                                                ?>
                                            </span>
                                        </div>
                                        
                                        <div class="aica-meta-item">
                                            <span class="aica-meta-label"><?php _e('Dodano:', 'ai-chat-assistant'); ?></span>
                                            <span class="aica-meta-value">
                                                <?php echo date_i18n(get_option('date_format'), strtotime($repo['created_at'])); ?>
                                            </span>
                                        </div>
                                        
                                        <?php if (!empty($repo['languages'])): ?>
                                        <div class="aica-meta-item">
                                            <span class="aica-meta-label"><?php _e('Języki:', 'ai-chat-assistant'); ?></span>
                                            <span class="aica-meta-value">
                                                <?php
                                                $languages = explode(',', $repo['languages']);
                                                echo esc_html(implode(', ', $languages));
                                                ?>
                                            </span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Stopka karty -->
                            <div class="aica-repo-footer">
                                <div class="aica-repo-buttons">
                                    <a href="#" class="aica-button aica-button-secondary aica-browse-button" data-repo-id="<?php echo esc_attr($repo['id']); ?>">
                                        <span class="dashicons dashicons-visibility"></span>
                                        <?php _e('Przeglądaj pliki', 'ai-chat-assistant'); ?>
                                    </a>
                                    <a href="<?php echo esc_url($repo['repo_url']); ?>" target="_blank" class="aica-button aica-button-secondary">
                                        <span class="dashicons dashicons-external"></span>
                                        <?php _e('Otwórz w', 'ai-chat-assistant'); ?> <?php echo esc_html($type_name); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- GitHub repozytoria -->
                <?php if (!empty($github_token)): ?>
                <div id="github-repositories" class="aica-repos-tab">
                    <?php if (empty($github_repos)): ?>
                    <div class="aica-empty-state">
                        <div class="aica-empty-icon">
                            <span class="dashicons dashicons-code-standards"></span>
                        </div>
                        <h2><?php _e('Brak repozytoriów GitHub', 'ai-chat-assistant'); ?></h2>
                        <p><?php _e('Nie znaleziono żadnych repozytoriów w Twoim koncie GitHub.', 'ai-chat-assistant'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=ai-chat-assistant-settings'); ?>" class="aica-button aica-button-secondary">
                            <span class="dashicons dashicons-admin-settings"></span>
                            <?php _e('Sprawdź ustawienia', 'ai-chat-assistant'); ?>
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="aica-repositories-grid">
                        <?php foreach ($github_repos as $repo): ?>
                        <div class="aica-repository-card">
                            <div class="aica-repo-header">
                                <div class="aica-repo-icon">
                                    <span class="dashicons dashicons-code-standards"></span>
                                </div>
                                <div class="aica-repo-title">
                                    <h3><?php echo esc_html($repo['name']); ?></h3>
                                    <div class="aica-repo-owner"><?php echo esc_html($repo['owner']); ?></div>
                                </div>
                            </div>
                            
                            <div class="aica-repo-body">
                                <?php if (!empty($repo['description'])): ?>
                                <div class="aica-repo-description">
                                    <p><?php echo esc_html($repo['description']); ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <div class="aica-repo-metadata">
                                    <div class="aica-meta-list">
                                        <div class="aica-meta-item">
                                            <span class="aica-meta-label"><?php _e('Aktualizacja:', 'ai-chat-assistant'); ?></span>
                                            <span class="aica-meta-value">
                                                <?php echo date_i18n(get_option('date_format'), strtotime($repo['updated_at'])); ?>
                                            </span>
                                        </div>
                                        
                                        <?php if (!empty($repo['language'])): ?>
                                        <div class="aica-meta-item">
                                            <span class="aica-meta-label"><?php _e('Główny język:', 'ai-chat-assistant'); ?></span>
                                            <span class="aica-meta-value">
                                                <?php echo esc_html($repo['language']); ?>
                                            </span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="aica-repo-footer">
                                <form class="aica-add-repo-form" method="post" action="">
                                    <?php wp_nonce_field('aica_repository_nonce'); ?>
                                    <input type="hidden" name="repo_type" value="github">
                                    <input type="hidden" name="repo_name" value="<?php echo esc_attr($repo['name']); ?>">
                                    <input type="hidden" name="repo_owner" value="<?php echo esc_attr($repo['owner']); ?>">
                                    <input type="hidden" name="repo_url" value="<?php echo esc_attr($repo['url']); ?>">
                                    <input type="hidden" name="repo_external_id" value="<?php echo esc_attr($repo['id']); ?>">
                                    <input type="hidden" name="repo_description" value="<?php echo esc_attr($repo['description']); ?>">
                                    
                                    <div class="aica-form-error"></div>
                                    
                                    <div class="aica-repo-buttons">
                                        <a href="<?php echo esc_url($repo['url']); ?>" target="_blank" class="aica-button aica-button-secondary">
                                            <span class="dashicons dashicons-external"></span>
                                            <?php _e('Otwórz w GitHub', 'ai-chat-assistant'); ?>
                                        </a>
                                        <button type="submit" name="aica_add_repository" class="aica-button aica-button-primary">
                                            <span class="dashicons dashicons-plus"></span>
                                            <?php _e('Dodaj', 'ai-chat-assistant'); ?>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- GitLab repozytoria -->
                <?php if (!empty($gitlab_token)): ?>
                <div id="gitlab-repositories" class="aica-repos-tab">
                    <?php if (empty($gitlab_repos)): ?>
                    <div class="aica-empty-state">
                        <div class="aica-empty-icon">
                            <span class="dashicons dashicons-editor-code"></span>
                        </div>
                        <h2><?php _e('Brak repozytoriów GitLab', 'ai-chat-assistant'); ?></h2>
                        <p><?php _e('Nie znaleziono żadnych repozytoriów w Twoim koncie GitLab.', 'ai-chat-assistant'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=ai-chat-assistant-settings'); ?>" class="aica-button aica-button-secondary">
                            <span class="dashicons dashicons-admin-settings"></span>
                            <?php _e('Sprawdź ustawienia', 'ai-chat-assistant'); ?>
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="aica-repositories-grid">
                        <?php foreach ($gitlab_repos as $repo): ?>
                        <div class="aica-repository-card">
                            <div class="aica-repo-header">
                                <div class="aica-repo-icon">
                                    <span class="dashicons dashicons-editor-code"></span>
                                </div>
                                <div class="aica-repo-title">
                                    <h3><?php echo esc_html($repo['name']); ?></h3>
                                    <div class="aica-repo-owner"><?php echo esc_html($repo['owner']); ?></div>
                                </div>
                            </div>
                            
                            <div class="aica-repo-body">
                                <?php if (!empty($repo['description'])): ?>
                                <div class="aica-repo-description">
                                    <p><?php echo esc_html($repo['description']); ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <div class="aica-repo-metadata">
                                    <div class="aica-meta-list">
                                        <div class="aica-meta-item">
                                            <span class="aica-meta-label"><?php _e('Aktualizacja:', 'ai-chat-assistant'); ?></span>
                                            <span class="aica-meta-value">
                                                <?php echo date_i18n(get_option('date_format'), strtotime($repo['updated_at'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="aica-repo-footer">
                                <form class="aica-add-repo-form" method="post" action="">
                                    <?php wp_nonce_field('aica_repository_nonce'); ?>
                                    <input type="hidden" name="repo_type" value="gitlab">
                                    <input type="hidden" name="repo_name" value="<?php echo esc_attr($repo['name']); ?>">
                                    <input type="hidden" name="repo_owner" value="<?php echo esc_attr($repo['owner']); ?>">
                                    <input type="hidden" name="repo_url" value="<?php echo esc_attr($repo['url']); ?>">
                                    <input type="hidden" name="repo_external_id" value="<?php echo esc_attr($repo['id']); ?>">
                                    <input type="hidden" name="repo_description" value="<?php echo esc_attr($repo['description']); ?>">
                                    
                                    <div class="aica-form-error"></div>
                                    
                                    <div class="aica-repo-buttons">
                                        <a href="<?php echo esc_url($repo['url']); ?>" target="_blank" class="aica-button aica-button-secondary">
                                            <span class="dashicons dashicons-external"></span>
                                            <?php _e('Otwórz w GitLab', 'ai-chat-assistant'); ?>
                                        </a>
                                        <button type="submit" name="aica_add_repository" class="aica-button aica-button-primary">
                                            <span class="dashicons dashicons-plus"></span>
                                            <?php _e('Dodaj', 'ai-chat-assistant'); ?>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- Bitbucket repozytoria -->
                <?php if (!empty($bitbucket_username) && !empty($bitbucket_app_password)): ?>
                <div id="bitbucket-repositories" class="aica-repos-tab">
                    <?php if (empty($bitbucket_repos)): ?>
                    <div class="aica-empty-state">
                        <div class="aica-empty-icon">
                            <span class="dashicons dashicons-cloud"></span>
                        </div>
                        <h2><?php _e('Brak repozytoriów Bitbucket', 'ai-chat-assistant'); ?></h2>
                        <p><?php _e('Nie znaleziono żadnych repozytoriów w Twoim koncie Bitbucket.', 'ai-chat-assistant'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=ai-chat-assistant-settings'); ?>" class="aica-button aica-button-secondary">
                            <span class="dashicons dashicons-admin-settings"></span>
                            <?php _e('Sprawdź ustawienia', 'ai-chat-assistant'); ?>
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="aica-repositories-grid">
                        <?php foreach ($bitbucket_repos as $repo): ?>
                        <div class="aica-repository-card">
                            <div class="aica-repo-header">
                                <div class="aica-repo-icon">
                                    <span class="dashicons dashicons-cloud"></span>
                                </div>
                                <div class="aica-repo-title">
                                    <h3><?php echo esc_html($repo['name']); ?></h3>
                                    <div class="aica-repo-owner"><?php echo esc_html($repo['owner']); ?></div>
                                </div>
                            </div>
                            
                            <div class="aica-repo-body">
                                <?php if (!empty($repo['description'])): ?>
                                <div class="aica-repo-description">
                                    <p><?php echo esc_html($repo['description']); ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <div class="aica-repo-metadata">
                                    <div class="aica-meta-list">
                                        <div class="aica-meta-item">
                                            <span class="aica-meta-label"><?php _e('Aktualizacja:', 'ai-chat-assistant'); ?></span>
                                            <span class="aica-meta-value">
                                                <?php echo date_i18n(get_option('date_format'), strtotime($repo['updated_at'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="aica-repo-footer">
                                <form class="aica-add-repo-form" method="post" action="">
                                    <?php wp_nonce_field('aica_repository_nonce'); ?>
                                    <input type="hidden" name="repo_type" value="bitbucket">
                                    <input type="hidden" name="repo_name" value="<?php echo esc_attr($repo['name']); ?>">
                                    <input type="hidden" name="repo_owner" value="<?php echo esc_attr($repo['owner']); ?>">
                                    <input type="hidden" name="repo_url" value="<?php echo esc_attr($repo['url']); ?>">
                                    <input type="hidden" name="repo_external_id" value="<?php echo esc_attr($repo['id']); ?>">
                                    <input type="hidden" name="repo_description" value="<?php echo esc_attr($repo['description']); ?>">
                                    
                                    <div class="aica-form-error"></div>
                                    
                                    <div class="aica-repo-buttons">
                                        <a href="<?php echo esc_url($repo['url']); ?>" target="_blank" class="aica-button aica-button-secondary">
                                            <span class="dashicons dashicons-external"></span>
                                            <?php _e('Otwórz w Bitbucket', 'ai-chat-assistant'); ?>
                                        </a>
                                        <button type="submit" name="aica_add_repository" class="aica-button aica-button-primary">
                                            <span class="dashicons dashicons-plus"></span>
                                            <?php _e('Dodaj', 'ai-chat-assistant'); ?>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Modal przeglądarki plików -->
    <div id="aica-file-browser-modal" class="aica-file-browser-modal">
        <div class="aica-modal-content">
            <div class="aica-modal-header">
                <h2><?php _e('Przeglądarka plików', 'ai-chat-assistant'); ?></h2>
                <button type="button" class="aica-modal-close">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
            
            <div class="aica-file-browser-container">
                <div class="aica-file-browser-sidebar">
                    <div class="aica-file-browser-header">
                        <div class="aica-repo-info">
                            <div class="aica-repo-icon">
                                <span class="dashicons dashicons-code-standards"></span>
                            </div>
                            <div class="aica-repo-name"></div>
                        </div>
                        
                        <div class="aica-branch-selector">
                            <select id="aica-branch-select">
                                <option value="main">main</option>
                                <option value="master">master</option>
                            </select>
                        </div>
                        
                        <div class="aica-file-search">
                            <input type="text" id="aica-file-search" placeholder="<?php _e('Szukaj plików...', 'ai-chat-assistant'); ?>">
                        </div>
                    </div>
                    
                    <div class="aica-file-tree-container">
                        <div class="aica-loading-files">
                            <span class="dashicons dashicons-update aica-loading"></span>
                            <?php _e('Ładowanie plików...', 'ai-chat-assistant'); ?>
                        </div>
                        <div id="aica-file-tree"></div>
                    </div>
                </div>
                
                <div class="aica-file-content-container">
                    <div class="aica-file-content-header">
                        <div class="aica-file-path"></div>
                        <div class="aica-file-actions">
                            <button type="button" class="aica-button aica-button-secondary aica-copy-file-button">
                                <span class="dashicons dashicons-clipboard"></span>
                                <?php _e('Kopiuj', 'ai-chat-assistant'); ?>
                            </button>
                            <button type="button" class="aica-button aica-button-primary aica-use-in-chat-button">
                                <span class="dashicons dashicons-format-chat"></span>
                                <?php _e('Użyj w czacie', 'ai-chat-assistant'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <div class="aica-file-content-body">
                        <div class="aica-loading-content" style="display: none;">
                            <span class="dashicons dashicons-update aica-loading"></span>
                            <?php _e('Ładowanie zawartości...', 'ai-chat-assistant'); ?>
                        </div>
                        <pre id="aica-file-content"><code></code></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Dialog potwierdzający usunięcie -->
    <div id="aica-delete-dialog" class="aica-file-browser-modal" style="display: none;">
        <div class="aica-modal-content aica-modal-sm">
            <div class="aica-modal-header">
                <h2><?php _e('Potwierdzenie usunięcia', 'ai-chat-assistant'); ?></h2>
                <button type="button" class="aica-modal-close aica-dialog-close">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
            
            <div class="aica-modal-body">
                <p><?php _e('Czy na pewno chcesz usunąć repozytorium', 'ai-chat-assistant'); ?> <strong id="aica-repo-name-confirm"></strong>?</p>
                <p><?php _e('Ta operacja jest nieodwracalna.', 'ai-chat-assistant'); ?></p>
            </div>
            
            <div class="aica-modal-footer">
                <button type="button" class="aica-button aica-button-secondary aica-dialog-cancel">
                    <?php _e('Anuluj', 'ai-chat-assistant'); ?>
                </button>
                <button type="button" class="aica-button aica-button-primary aica-delete-confirm">
                    <span class="dashicons dashicons-trash"></span>
                    <?php _e('Usuń', 'ai-chat-assistant'); ?>
                </button>
            </div>
        </div>
    </div>
</div>  