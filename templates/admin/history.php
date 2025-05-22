<?php
/**
 * Szablon historii czatu
 */

// Bezpośredni dostęp do pliku jest zabroniony
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html__('Chat History', 'aica'); ?></h1>

    <div class="chat-history-container">
        <div class="chat-sessions" id="chat-history">
            <!-- Sesje będą ładowane dynamicznie -->
        </div>

        <div class="chat-content">
            <div class="session-info" id="session-info">
                <h3><?php echo esc_html__('Select a session to view history', 'aica'); ?></h3>
            </div>

            <div class="chat-messages" id="chat-messages">
                <!-- Wiadomości będą ładowane dynamicznie -->
            </div>

            <div class="chat-actions">
                <button id="load-more-history" class="button">
                    <?php echo esc_html__('Load More', 'aica'); ?>
                </button>
                <button id="clear-history" class="button">
                    <?php echo esc_html__('Clear History', 'aica'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Lokalizacja dla JavaScript
    var aica_history = {
        loadMore: '<?php echo esc_js(__('Load More', 'aica')); ?>',
        clearHistory: '<?php echo esc_js(__('Clear History', 'aica')); ?>',
        confirmClear: '<?php echo esc_js(__('Are you sure you want to clear this chat history?', 'aica')); ?>',
        selectSession: '<?php echo esc_js(__('Select a session to view history', 'aica')); ?>',
        errorLoading: '<?php echo esc_js(__('Error loading session:', 'aica')); ?>',
        errorClearing: '<?php echo esc_js(__('Error clearing history:', 'aica')); ?>'
    };
</script>