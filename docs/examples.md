# AI Chat Assistant - Przykłady użycia

## Spis treści
1. [Podstawowe użycie](#podstawowe-użycie)
2. [Rozszerzanie funkcjonalności](#rozszerzanie-funkcjonalności)
3. [Integracja z innymi wtyczkami](#integracja-z-innymi-wtyczkami)
4. [Dostosowywanie interfejsu](#dostosowywanie-interfejsu)
5. [Zaawansowane przykłady](#zaawansowane-przykłady)

## Podstawowe użycie

### Inicjalizacja wtyczki

```php
// Sprawdzenie czy wtyczka jest aktywna
if (class_exists('AIChatAssistant\Main')) {
    // Wtyczka jest aktywna
}

// Pobranie instancji głównej klasy
$plugin = AIChatAssistant\Main::getInstance();
```

### Sprawdzanie uprawnień

```php
use AIChatAssistant\Helpers\Security;

// Sprawdzenie czy użytkownik jest administratorem
if (Security::check_user_capability('manage_options')) {
    // Użytkownik ma uprawnienia administratora
}

// Sprawdzenie czy użytkownik może edytować posty
if (Security::check_user_capability('edit_posts')) {
    // Użytkownik może edytować posty
}
```

### Praca z cache

```php
use AIChatAssistant\Helpers\Cache;

$cache = Cache::getInstance();

// Zapis danych w cache
$cache->set('my_data', $data, 3600); // Cache na 1 godzinę

// Pobieranie danych z cache
$data = $cache->get('my_data');

// Pobieranie z cache lub generowanie
$data = $cache->remember('my_data', function() {
    return expensive_operation();
}, 3600);

// Usuwanie danych z cache
$cache->delete('my_data');

// Czyszczenie całego cache
$cache->flush();
```

## Rozszerzanie funkcjonalności

### Dodawanie własnych typów wiadomości

```php
add_filter('aica_message_types', function($types) {
    $types['custom'] = [
        'label' => 'Własny typ',
        'icon' => 'dashicons-star-filled',
        'template' => 'custom-message.php'
    ];
    return $types;
});
```

### Dodawanie własnych akcji do wiadomości

```php
add_action('aica_message_actions', function($message) {
    if ($message->type === 'custom') {
        echo '<button class="aica-custom-action" data-message-id="' . esc_attr($message->id) . '">';
        echo 'Moja akcja';
        echo '</button>';
    }
});
```

### Obsługa własnych załączników

```php
add_filter('aica_allowed_file_types', function($types) {
    // Dodaj własne typy plików
    $types[] = 'application/pdf';
    $types[] = 'application/msword';
    return $types;
});

add_filter('aica_file_preview', function($preview, $file) {
    if ($file->type === 'application/pdf') {
        return '<iframe src="' . esc_url($file->url) . '" width="100%" height="400"></iframe>';
    }
    return $preview;
}, 10, 2);
```

## Integracja z innymi wtyczkami

### Integracja z WooCommerce

```php
add_action('woocommerce_order_status_changed', function($order_id, $old_status, $new_status) {
    $order = wc_get_order($order_id);
    $message = sprintf(
        'Zamówienie #%s zmieniło status z %s na %s',
        $order_id,
        $old_status,
        $new_status
    );
    
    // Wysłanie wiadomości do AI
    do_action('aica_send_message', $message);
}, 10, 3);
```

### Integracja z ACF

```php
add_action('acf/save_post', function($post_id) {
    if (get_post_type($post_id) === 'product') {
        $product = wc_get_product($post_id);
        $message = sprintf(
            'Zaktualizowano produkt: %s',
            $product->get_name()
        );
        
        // Wysłanie wiadomości do AI
        do_action('aica_send_message', $message);
    }
});
```

## Dostosowywanie interfejsu

### Dodawanie własnych stylów

```php
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style(
        'my-custom-chat-style',
        plugins_url('css/custom-chat.css', __FILE__),
        ['aica-chat']
    );
});
```

### Modyfikacja szablonu czatu

```php
add_filter('aica_chat_template', function($template) {
    if (is_page('custom-chat')) {
        return 'custom-chat-template.php';
    }
    return $template;
});
```

### Dodawanie własnych przycisków

```php
add_action('aica_chat_toolbar', function() {
    echo '<button class="aica-custom-button">';
    echo '<span class="dashicons dashicons-star-filled"></span>';
    echo 'Moja akcja';
    echo '</button>';
});
```

## Zaawansowane przykłady

### Własny system logowania

```php
add_action('aica_before_chat_message', function($message, $conversation_id) {
    // Logowanie wiadomości do własnego systemu
    $log_entry = [
        'message' => $message,
        'conversation_id' => $conversation_id,
        'user_id' => get_current_user_id(),
        'timestamp' => current_time('mysql')
    ];
    
    // Zapisz do własnej tabeli
    global $wpdb;
    $wpdb->insert(
        $wpdb->prefix . 'aica_message_logs',
        $log_entry
    );
}, 10, 2);
```

### Własny system analizy

```php
add_filter('aica_chat_response', function($response, $conversation_id) {
    // Analiza odpowiedzi AI
    $sentiment = analyze_sentiment($response);
    $keywords = extract_keywords($response);
    
    // Zapisz analizę
    update_post_meta($conversation_id, '_aica_sentiment', $sentiment);
    update_post_meta($conversation_id, '_aica_keywords', $keywords);
    
    return $response;
}, 10, 2);
```

### Własny system powiadomień

```php
add_action('aica_after_chat_message', function($response, $conversation_id) {
    // Sprawdź czy odpowiedź wymaga powiadomienia
    if (should_notify($response)) {
        $admin_email = get_option('admin_email');
        $subject = 'Nowa odpowiedź AI wymaga uwagi';
        $message = sprintf(
            'Odpowiedź AI w rozmowie #%s wymaga Twojej uwagi: %s',
            $conversation_id,
            $response
        );
        
        wp_mail($admin_email, $subject, $message);
    }
}, 10, 2);
```

### Własny system eksportu

```php
add_action('aica_export_conversation', function($conversation_id) {
    $conversation = get_post($conversation_id);
    $messages = get_post_meta($conversation_id, '_aica_messages', true);
    
    // Generuj PDF
    $pdf = new TCPDF();
    $pdf->AddPage();
    $pdf->SetFont('dejavusans', '', 12);
    
    // Dodaj nagłówek
    $pdf->Cell(0, 10, 'Rozmowa #' . $conversation_id, 0, 1);
    
    // Dodaj wiadomości
    foreach ($messages as $message) {
        $pdf->MultiCell(0, 10, $message->content);
    }
    
    // Zapisz PDF
    $pdf->Output('conversation-' . $conversation_id . '.pdf', 'D');
});
```

### Własny system raportowania

```php
add_action('aica_generate_report', function($start_date, $end_date) {
    global $wpdb;
    
    // Pobierz statystyki
    $stats = $wpdb->get_results($wpdb->prepare("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as message_count,
            AVG(LENGTH(content)) as avg_length
        FROM {$wpdb->prefix}aica_messages
        WHERE created_at BETWEEN %s AND %s
        GROUP BY DATE(created_at)
    ", $start_date, $end_date));
    
    // Generuj raport
    $report = [
        'period' => [
            'start' => $start_date,
            'end' => $end_date
        ],
        'total_messages' => array_sum(array_column($stats, 'message_count')),
        'avg_message_length' => array_sum(array_column($stats, 'avg_length')) / count($stats),
        'daily_stats' => $stats
    ];
    
    return $report;
});
``` 