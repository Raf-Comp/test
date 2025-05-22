# Testy dla AI Chat Assistant

## Struktura testów

Testy są podzielone na trzy kategorie:

1. **Testy jednostkowe** (`tests/Unit/`)
   - Testy pojedynczych klas i metod
   - Sprawdzanie logiki biznesowej
   - Testy pomocniczych funkcji

2. **Testy integracyjne** (`tests/Integration/`)
   - Testy interakcji między komponentami
   - Testy API REST
   - Testy integracji z WordPress

3. **Testy bezpieczeństwa** (`tests/Security/`)
   - Testy zabezpieczeń przed atakami
   - Testy walidacji danych
   - Testy uprawnień

## Wymagania

- PHP 7.4 lub nowszy
- Composer
- MySQL
- WordPress (zostanie pobrany automatycznie)

## Instalacja

1. Zainstaluj zależności:
```bash
composer install
```

2. Skonfiguruj bazę danych testową:
```bash
bin/install-wp-tests.sh wordpress_test root root localhost latest
```

## Uruchamianie testów

### Wszystkie testy
```bash
./vendor/bin/phpunit
```

### Testy jednostkowe
```bash
./vendor/bin/phpunit --testsuite Unit
```

### Testy integracyjne
```bash
./vendor/bin/phpunit --testsuite Integration
```

### Testy bezpieczeństwa
```bash
./vendor/bin/phpunit --testsuite Security
```

### Pojedynczy test
```bash
./vendor/bin/phpunit tests/Unit/SecurityTest.php
```

## Dodawanie nowych testów

1. Utwórz nowy plik testowy w odpowiednim katalogu
2. Rozszerz odpowiednią klasę testową:
   - `WP_UnitTestCase` dla testów jednostkowych
   - `WP_UnitTestCase` dla testów integracyjnych
   - `WP_UnitTestCase` dla testów bezpieczeństwa
3. Dodaj metody testowe z prefixem `test_`
4. Uruchom testy

## Przykład testu

```php
<?php
namespace AIChatAssistant\Tests\Unit;

use WP_UnitTestCase;
use AIChatAssistant\Helpers\Security;

class SecurityTest extends WP_UnitTestCase {
    public function test_check_user_capability() {
        // Test dla administratora
        wp_set_current_user(1);
        $this->assertTrue(Security::check_user_capability('manage_options'));

        // Test dla użytkownika bez uprawnień
        wp_set_current_user(2);
        $this->expectException('WPDieException');
        Security::check_user_capability('manage_options');
    }
}
```

## Najlepsze praktyki

1. **Nazewnictwo**
   - Używaj opisowych nazw dla testów
   - Grupuj powiązane testy w klasy
   - Używaj prefixu `test_` dla metod testowych

2. **Struktura testu**
   - Arrange: przygotuj dane testowe
   - Act: wykonaj testowaną akcję
   - Assert: sprawdź wyniki

3. **Asercje**
   - Używaj precyzyjnych asercji
   - Sprawdzaj zarówno pozytywne jak i negatywne przypadki
   - Testuj graniczne przypadki

4. **Mockowanie**
   - Używaj mocków dla zewnętrznych zależności
   - Symuluj różne scenariusze
   - Testuj obsługę błędów

5. **Czyszczenie**
   - Czyść dane testowe po każdym teście
   - Używaj `setUp()` i `tearDown()`
   - Unikaj zależności między testami

## Debugowanie

1. Włącz tryb debugowania w `phpunit.xml`:
```xml
<phpunit
    colors="true"
    convertErrorsToExceptions="true"
    convertNoticesToExceptions="true"
    convertWarningsToExceptions="true"
    stopOnFailure="false"
>
```

2. Użyj `--debug` flagi:
```bash
./vendor/bin/phpunit --debug
```

3. Dodaj `var_dump()` lub `print_r()` w testach:
```php
public function test_something() {
    $result = some_function();
    var_dump($result);
    $this->assertTrue($result);
}
```

## CI/CD

Testy są automatycznie uruchamiane w procesie CI/CD:

1. Przy każdym pull requeście
2. Przy każdym merge do głównej gałęzi
3. Przy każdym tagu wersji

## Raporty

Po uruchomieniu testów generowane są raporty:

1. **HTML**: `tests/reports/html/`
2. **JUnit**: `tests/reports/junit/`
3. **Coverage**: `tests/reports/coverage/`

Aby wygenerować raporty:
```bash
./vendor/bin/phpunit --coverage-html tests/reports/coverage
``` 