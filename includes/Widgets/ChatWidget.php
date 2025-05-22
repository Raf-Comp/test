<?php
namespace AICA\Widgets;

use WP_Widget;

class ChatWidget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'aica_chat_widget',
            __('AI Chat Widget', 'ai-chat-assistant'),
            ['description' => __('Widget dla asystenta AI', 'ai-chat-assistant')]
        );
    }

    public function widget($args, $instance) {
        echo '<div>' . esc_html__('AI Chat Assistant', 'ai-chat-assistant') . '</div>';
    }

    public function form($instance) {
        // Form w panelu admina – na razie pusto
    }

    public function update($new_instance, $old_instance) {
        return $new_instance;
    }
}
