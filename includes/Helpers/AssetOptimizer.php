<?php
declare(strict_types=1);

namespace AICA\Helpers;

/**
 * Klasa pomocnicza do optymalizacji zasobów
 * 
 * @package AICA
 * @since 1.0.0
 */
class AssetOptimizer {
    private static ?self $instance = null;
    private array $minified_assets = [];
    private array $preloaded_assets = [];
    private array $deferred_scripts = [];

    /**
     * Pobiera instancję klasy
     * 
     * @return self Instancja klasy
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Rejestruje zminifikowany zasób
     * 
     * @param string $handle Nazwa zasobu
     * @param string $type Typ zasobu (css/js)
     * @param string $file Ścieżka do pliku
     * @return void
     */
    public function registerMinifiedAsset(string $handle, string $type, string $file): void {
        $this->minified_assets[$handle] = [
            'type' => $type,
            'file' => $file
        ];
    }

    /**
     * Rejestruje zasób do preload
     * 
     * @param string $url URL zasobu
     * @param string $type Typ zasobu (css/js/image)
     * @return void
     */
    public function registerPreloadedAsset(string $url, string $type): void {
        $this->preloaded_assets[] = [
            'url' => $url,
            'type' => $type
        ];
    }

    /**
     * Rejestruje skrypt do odroczonego ładowania
     * 
     * @param string $handle Nazwa skryptu
     * @return void
     */
    public function registerDeferredScript(string $handle): void {
        $this->deferred_scripts[] = $handle;
    }

    /**
     * Optymalizuje CSS
     *
     * @param string $css Kod CSS
     * @return string Zoptymalizowany kod CSS
     */
    public static function optimize_css(string $css): string {
        // Usuń komentarze
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Usuń białe znaki
        $css = str_replace(["\r\n", "\r", "\n", "\t"], '', $css);
        $css = preg_replace('/\s+/', ' ', $css);
        
        // Usuń spacje przed i po znakach specjalnych
        $css = str_replace(
            [' { ', ' } ', ' {', '} ', ' : ', ' :', ': ', ' ; ', ' ;', '; '],
            ['{', '}', '{', '}', ':', ':', ':', ';', ';', ';'],
            $css
        );
        
        return trim($css);
    }

    /**
     * Optymalizuje JavaScript
     *
     * @param string $js Kod JavaScript
     * @return string Zoptymalizowany kod JavaScript
     */
    public static function optimize_js(string $js): string {
        // Usuń komentarze jednoliniowe
        $js = preg_replace('!//.*!', '', $js);
        
        // Usuń komentarze wieloliniowe
        $js = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $js);
        
        // Usuń białe znaki
        $js = str_replace(["\r\n", "\r", "\n", "\t"], '', $js);
        $js = preg_replace('/\s+/', ' ', $js);
        
        return trim($js);
    }

    /**
     * Optymalizuje HTML
     *
     * @param string $html Kod HTML
     * @return string Zoptymalizowany kod HTML
     */
    public static function optimize_html(string $html): string {
        // Usuń komentarze HTML
        $html = preg_replace('/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/s', '', $html);
        
        // Usuń białe znaki
        $html = preg_replace('/\s+/', ' ', $html);
        
        return trim($html);
    }
} 