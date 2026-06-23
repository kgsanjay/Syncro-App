<?php
declare(strict_types=1);

namespace Syncro\Views;

use RuntimeException;

class View
{
    /**
     * Render a view file with extracted data.
     */
    public static function render(string $template, array $data = []): void
    {
        $baseViewDir = realpath(__DIR__) ?: realpath(__DIR__ . '/../views');
        
        if ($baseViewDir === false) {
            throw new RuntimeException("Secure View Exception: The base views directory could not be located.");
        }

        $viewPath = realpath($baseViewDir . '/' . $template . '.php');

        if ($viewPath === false || strpos($viewPath, $baseViewDir) !== 0 || !file_exists($viewPath)) {
            throw new RuntimeException("Secure View Exception: Invalid or unauthorized view path detected: " . htmlspecialchars($template));
        }

        // Prevent data array from overriding local variables
        extract($data, EXTR_SKIP);
        
        require $viewPath;
    }
}
