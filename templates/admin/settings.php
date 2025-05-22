<?php
/**
 * Template for the settings page
 */

if (!defined('ABSPATH')) {
    exit;
}

use AIChatAssistant\Helpers\Security;

// Sprawdzenie uprawnień
Security::check_user_capability();

$settings = $this->settings_service->getAllSettings();
$api_settings = $this->settings_service->getApiSettings();
?>

<div class="wrap aica-settings">
    <h1><?php Security::display_text(__('AI Chat Assistant Settings', 'ai-chat-assistant')); ?></h1>

    <?php if (isset($_GET['settings-updated'])) : ?>
        <div class="aica-notice aica-notice-success">
            <span><?php Security::display_text(__('Settings saved successfully.', 'ai-chat-assistant')); ?></span>
            <button type="button" class="aica-notice-close">&times;</button>
        </div>
    <?php endif; ?>

    <form method="post" action="options.php" id="aica-settings-form">
        <?php 
        settings_fields('aica_settings');
        wp_nonce_field('aica_settings_nonce', 'aica_settings_nonce');
        ?>

        <div class="aica-settings-grid">
            <!-- API Settings -->
            <div class="aica-settings-section">
                <h2><?php Security::display_text(__('API Settings', 'ai-chat-assistant')); ?></h2>
                
                <!-- Claude API -->
                <div class="aica-form-group">
                    <label for="aica_claude_api_key">
                        <?php Security::display_text(__('Claude API Key', 'ai-chat-assistant')); ?>
                    </label>
                    <div class="aica-password-field">
                        <input type="password" 
                               id="aica_claude_api_key" 
                               name="aica_api_settings[claude_api_key]" 
                               value="<?php Security::display_attribute($api_settings['claude_api_key'] ?? ''); ?>"
                               class="regular-text"
                               autocomplete="off">
                        <button type="button" 
                                class="aica-toggle-password" 
                                aria-label="<?php Security::display_attribute(__('Toggle password visibility', 'ai-chat-assistant')); ?>">
                            <span class="dashicons dashicons-visibility"></span>
                        </button>
                    </div>
                    <button type="button" 
                            class="button aica-test-api" 
                            data-api="claude"
                            data-nonce="<?php echo wp_create_nonce('aica_test_api'); ?>">
                        <?php Security::display_text(__('Test Connection', 'ai-chat-assistant')); ?>
                    </button>
                    <div class="aica-api-test-result" style="display: none;"></div>
                </div>

                <!-- GitHub Token -->
                <div class="aica-form-group">
                    <label for="aica_github_token">
                        <?php Security::display_text(__('GitHub Token', 'ai-chat-assistant')); ?>
                    </label>
                    <div class="aica-password-field">
                        <input type="password" 
                               id="aica_github_token" 
                               name="aica_api_settings[github_token]" 
                               value="<?php Security::display_attribute($api_settings['github_token'] ?? ''); ?>"
                               class="regular-text"
                               autocomplete="off">
                        <button type="button" 
                                class="aica-toggle-password" 
                                aria-label="<?php Security::display_attribute(__('Toggle password visibility', 'ai-chat-assistant')); ?>">
                            <span class="dashicons dashicons-visibility"></span>
                        </button>
                    </div>
                    <button type="button" 
                            class="button aica-test-api" 
                            data-api="github"
                            data-nonce="<?php echo wp_create_nonce('aica_test_api'); ?>">
                        <?php Security::display_text(__('Test Connection', 'ai-chat-assistant')); ?>
                    </button>
                    <div class="aica-api-test-result" style="display: none;"></div>
                </div>

                <!-- GitLab Token -->
                <div class="aica-form-group">
                    <label for="aica_gitlab_token">
                        <?php Security::display_text(__('GitLab Token', 'ai-chat-assistant')); ?>
                    </label>
                    <div class="aica-password-field">
                        <input type="password" 
                               id="aica_gitlab_token" 
                               name="aica_api_settings[gitlab_token]" 
                               value="<?php Security::display_attribute($api_settings['gitlab_token'] ?? ''); ?>"
                               class="regular-text"
                               autocomplete="off">
                        <button type="button" 
                                class="aica-toggle-password" 
                                aria-label="<?php Security::display_attribute(__('Toggle password visibility', 'ai-chat-assistant')); ?>">
                            <span class="dashicons dashicons-visibility"></span>
                        </button>
                    </div>
                    <button type="button" 
                            class="button aica-test-api" 
                            data-api="gitlab"
                            data-nonce="<?php echo wp_create_nonce('aica_test_api'); ?>">
                        <?php Security::display_text(__('Test Connection', 'ai-chat-assistant')); ?>
                    </button>
                    <div class="aica-api-test-result" style="display: none;"></div>
                </div>

                <!-- Bitbucket Token -->
                <div class="aica-form-group">
                    <label for="aica_bitbucket_token">
                        <?php Security::display_text(__('Bitbucket Token', 'ai-chat-assistant')); ?>
                    </label>
                    <div class="aica-password-field">
                        <input type="password" 
                               id="aica_bitbucket_token" 
                               name="aica_api_settings[bitbucket_token]" 
                               value="<?php Security::display_attribute($api_settings['bitbucket_token'] ?? ''); ?>"
                               class="regular-text"
                               autocomplete="off">
                        <button type="button" 
                                class="aica-toggle-password" 
                                aria-label="<?php Security::display_attribute(__('Toggle password visibility', 'ai-chat-assistant')); ?>">
                            <span class="dashicons dashicons-visibility"></span>
                        </button>
                    </div>
                    <button type="button" 
                            class="button aica-test-api" 
                            data-api="bitbucket"
                            data-nonce="<?php echo wp_create_nonce('aica_test_api'); ?>">
                        <?php Security::display_text(__('Test Connection', 'ai-chat-assistant')); ?>
                    </button>
                    <div class="aica-api-test-result" style="display: none;"></div>
                </div>
            </div>

            <!-- Interface Settings -->
            <div class="aica-settings-section">
                <h2><?php Security::display_text(__('Interface Settings', 'ai-chat-assistant')); ?></h2>
                
                <div class="aica-form-group">
                    <label for="aica_theme">
                        <?php Security::display_text(__('Theme', 'ai-chat-assistant')); ?>
                    </label>
                    <select id="aica_theme" name="aica_settings[theme]">
                        <option value="light" <?php selected($settings['theme'] ?? 'light', 'light'); ?>>
                            <?php Security::display_text(__('Light', 'ai-chat-assistant')); ?>
                        </option>
                        <option value="dark" <?php selected($settings['theme'] ?? 'light', 'dark'); ?>>
                            <?php Security::display_text(__('Dark', 'ai-chat-assistant')); ?>
                        </option>
                        <option value="system" <?php selected($settings['theme'] ?? 'light', 'system'); ?>>
                            <?php Security::display_text(__('System', 'ai-chat-assistant')); ?>
                        </option>
                    </select>
                </div>

                <div class="aica-form-group">
                    <label for="aica_message_limit">
                        <?php Security::display_text(__('Message History Limit', 'ai-chat-assistant')); ?>
                    </label>
                    <input type="number" 
                           id="aica_message_limit" 
                           name="aica_settings[message_limit]" 
                           value="<?php Security::display_attribute($settings['message_limit'] ?? 50); ?>"
                           min="10"
                           max="1000"
                           step="10">
                    <p class="description">
                        <?php Security::display_text(__('Maximum number of messages to keep in history.', 'ai-chat-assistant')); ?>
                    </p>
                </div>
            </div>
        </div>

        <?php submit_button(); ?>
    </form>
</div>