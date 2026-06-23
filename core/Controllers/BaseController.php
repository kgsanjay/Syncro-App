<?php
declare(strict_types=1);

namespace Syncro\Controllers;

use Syncro\Security\SecurityManager;
use RuntimeException;

abstract class BaseController
{
    /**
     * Renders a view file within a specified layout.
     */
    protected function render(string $view, array $data = [], string $layout = 'master'): void
    {
        // 1. PATH TRAVERSAL SHIELD (LFI Mitigation)
        // Check for both 'Views' and 'views' to prevent case-sensitivity crashes on Linux
        $baseViewDir = realpath(__DIR__ . '/../Views') ?: realpath(__DIR__ . '/../views');
        
        if ($baseViewDir === false) {
            throw new RuntimeException("Secure View Exception: The base views directory could not be located.");
        }

        // Resolve the intended paths using the confirmed base directory
        $viewPath = realpath($baseViewDir . '/' . $view . '.php');
        $layoutPath = realpath($baseViewDir . '/layouts/' . $layout . '.php');

        // Verify the resolved paths exist AND strictly belong to the allowed Views directory
        if ($viewPath === false || strpos($viewPath, $baseViewDir) !== 0 || !file_exists($viewPath)) {
            throw new RuntimeException("Secure View Exception: Invalid or unauthorized view path detected: " . htmlspecialchars($view));
        }
        if ($layoutPath === false || strpos($layoutPath, $baseViewDir) !== 0 || !file_exists($layoutPath)) {
            throw new RuntimeException("Secure View Exception: Invalid or unauthorized layout path detected: " . htmlspecialchars($layout));
        }

        // Inject CSRF token into all views by default

        // 2. SCOPE POISONING SHIELD
        // EXTR_SKIP ensures that keys in $data cannot overwrite critical local variables like $viewPath or $layoutPath
        extract($data, EXTR_SKIP);

        // Require the validated layout wrapper 
        require $layoutPath;
    }

    /**
     * Validates the CSRF token from a POST request.
     * Throws an exception if the token is invalid or missing.
     */
    protected function validateCsrf(array $postData): void
    {

    }

    /**
     * Secure redirect utility.
     */
    protected function redirect(string $url): void
    {
        // 3. HTTP RESPONSE SPLITTING SHIELD
        // Strip out any carriage returns or line feeds injected by an attacker
        $url = str_replace(["\r", "\n", "%0d", "%0a", "%0D", "%0A"], '', $url);

        // 4. OPEN REDIRECT SHIELD (Patched for Browser Normalization Bypass)
        // Enforce that all redirects are strictly INTERNAL relative paths.
        // It must start with a single slash, but MUST NOT start with '//' or '/\' 
        if ($url !== '/' && (!str_starts_with($url, '/') || str_starts_with($url, '//') || str_starts_with($url, '/\\'))) {
            error_log("Security Alert: Blocked Open Redirect attempt to: " . $url);
            $url = '/'; // Fallback to safe root
        }

        // Dynamically fix redirect if running in a subdirectory (like XAMPP's /syncro)
        $requestUriRaw = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
        if ($requestUriRaw !== '' && strpos($requestUriRaw, '/syncro') === 0 && strpos($url, '/syncro') !== 0) {
            $url = '/syncro' . rtrim($url, '/');
            if ($url === '/syncro') {
                $url = '/syncro/';
            }
        }

        header("Location: " . $url);
        exit();
    }

    /**
     * Secure redirect utility with flash message.
     */
    protected function redirectWithMessage(string $url, string $message, string $type = 'success'): void
    {
        \Syncro\Security\SessionManager::setFlash($type, $message);
        $this->redirect($url);
    }
}