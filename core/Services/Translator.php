<?php
declare(strict_types=1);

namespace Syncro\Services;

class Translator
{
    private static ?Translator $instance = null;
    private array $translations = [];
    private string $locale = 'en';

    private function __construct()
    {
        $this->detectLocale();
        $this->loadTranslations();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function detectLocale(): void
    {
        if (isset($_SESSION['locale'])) {
            $this->locale = $_SESSION['locale'];
        } elseif (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $langs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
            // Get the first two characters of the first preferred language
            $primary = strtolower(substr($langs[0], 0, 2));
            if (in_array($primary, ['en', 'fr', 'es'])) {
                $this->locale = $primary;
            }
        }
    }

    private function loadTranslations(): void
    {
        $path = __DIR__ . '/../../lang/' . $this->locale . '.json';
        if (!file_exists($path)) {
            // Fallback to English
            $path = __DIR__ . '/../../lang/en.json';
        }
        
        if (file_exists($path)) {
            $json = file_get_contents($path);
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                $this->translations = $decoded;
            }
        }
    }

    public function translate(string $key, array $replacements = []): string
    {
        $text = $this->translations[$key] ?? $key;

        if (!empty($replacements)) {
            foreach ($replacements as $placeholder => $value) {
                $text = str_replace(':' . $placeholder, (string)$value, $text);
            }
        }

        return $text;
    }
}
