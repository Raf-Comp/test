# AI Chat Assistant - Dokumentacja API

## Spis treści
1. [Wprowadzenie](#wprowadzenie)
2. [Konfiguracja](#konfiguracja)
3. [API REST](#api-rest)
4. [Hooks i Filtry](#hooks-i-filtry)
5. [Klasy i Metody](#klasy-i-metody)
6. [Przykłady użycia](#przykłady-użycia)

## Wprowadzenie

AI Chat Assistant to wtyczka WordPress, która dodaje asystenta AI opartego na Claude.ai. Wtyczka zapewnia interfejs do komunikacji z AI oraz zarządzania rozmowami.

## Konfiguracja

### Wymagania
- WordPress 5.8 lub nowszy
- PHP 7.4 lub nowszy
- Konto Claude.ai z kluczem API

### Instalacja
1. Pobierz i zainstaluj wtyczkę przez panel WordPress
2. Aktywuj wtyczkę
3. Przejdź do ustawień wtyczki i wprowadź klucz API Claude.ai

## API REST

### Endpointy

#### Pobieranie historii rozmów
```http
GET /wp-json/aica/v1/history
```

Parametry:
- `page` (opcjonalny) - numer strony (domyślnie 1)
- `per_page` (opcjonalny) - liczba rozmów na stronę (domyślnie 20)
- `search` (opcjonalny) - fraza do wyszukania

Odpowiedź:
```json
{
    "success": true,
    "data": {
        "conversations": [
            {
                "id": 1,
                "title": "Rozmowa 1",
                "created_at": "2024-01-01 12:00:00",
                "updated_at": "2024-01-01 12:30:00",
                "message_count": 10
            }
        ],
        "total": 100,
        "pages": 5
    }
}
```

#### Wysyłanie wiadomości
```http
POST /wp-json/aica/v1/chat
```

Parametry:
- `message` (wymagany) - treść wiadomości
- `conversation_id` (opcjonalny) - ID rozmowy
- `attachments` (opcjonalny) - załączniki

Odpowiedź:
```json
{
    "success": true,
    "data": {
        "message": {
            "id": 1,
            "content": "Odpowiedź AI",
            "role": "assistant",
            "created_at": "2024-01-01 12:00:00"
        }
    }
}
```

#### Pobieranie ustawień
```http
GET /wp-json/aica/v1/settings
```

Odpowiedź:
```json
{
    "success": true,
    "data": {
        "api_key": "***",
        "model": "claude-3-opus-20240229",
        "temperature": 0.7,
        "max_tokens": 1000
    }
}
```

## Hooks i Filtry

### Akcje

#### `aica_before_chat_message`
Wywoływana przed wysłaniem wiadomości do AI.
```php
do_action('aica_before_chat_message', $message, $conversation_id);
```

#### `aica_after_chat_message`
Wywoływana po otrzymaniu odpowiedzi od AI.
```php
do_action('aica_after_chat_message', $response, $conversation_id);
```

#### `aica_before_save_conversation`
Wywoływana przed zapisaniem rozmowy.
```php
do_action('aica_before_save_conversation', $conversation_data);
```

### Filtry

#### `aica_chat_message`
Filtruje wiadomość przed wysłaniem do AI.
```php
$message = apply_filters('aica_chat_message', $message, $conversation_id);
```

#### `aica_chat_response`
Filtruje odpowiedź od AI przed wyświetleniem.
```php
$response = apply_filters('aica_chat_response', $response, $conversation_id);
```

#### `aica_conversation_data`
Filtruje dane rozmowy przed zapisaniem.
```php
$conversation_data = apply_filters('aica_conversation_data', $conversation_data);
```

## Klasy i Metody

### Security

Klasa pomocnicza do zarządzania bezpieczeństwem.

```php
use AIChatAssistant\Helpers\Security;

// Sprawdzanie uprawnień
if (Security::check_user_capability('manage_options')) {
    // ...
}

// Weryfikacja nonce
if (Security::verify_nonce('aica_settings_nonce', 'aica_settings_action')) {
    // ...
}

// Bezpieczne pobieranie danych
$api_key = Security::get_post_value('api_key', '');
```

### Cache

Klasa pomocnicza do zarządzania cache.

```php
use AIChatAssistant\Helpers\Cache;

$cache = Cache::getInstance();

// Pobieranie danych z cache
$data = $cache->get('my_cache_key');

// Zapis danych w cache
$cache->set('my_cache_key', $data, 3600);

// Pobieranie z cache lub generowanie
$data = $cache->remember('my_cache_key', function() {
    return expensive_operation();
}, 3600);
```

### AssetService

Klasa serwisu do zarządzania zasobami.

```php
use AICA\Services\AssetService;

$assets = new AssetService();

// Ładowanie zasobów dla strony
$assets->loadPageAssets('ai-chat-assistant');
```

## Przykłady użycia

### Dodawanie własnego stylu do czatu

```php
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('my-custom-chat-style', 'path/to/style.css', ['aica-chat']);
});
```

### Modyfikacja odpowiedzi AI

```php
add_filter('aica_chat_response', function($response, $conversation_id) {
    // Dodaj własną logikę
    return $response;
}, 10, 2);
```

### Dodawanie własnych ustawień

```php
add_filter('aica_settings_fields', function($fields) {
    $fields['my_custom_setting'] = [
        'type' => 'text',
        'label' => 'Moje ustawienie',
        'default' => ''
    ];
    return $fields;
});
```

### Obsługa własnych typów załączników

```php
add_filter('aica_allowed_file_types', function($types) {
    $types[] = 'application/pdf';
    return $types;
});
```

### Dodawanie własnych akcji do interfejsu

```php
add_action('aica_chat_actions', function() {
    echo '<button class="aica-custom-action">Moja akcja</button>';
});
``` 