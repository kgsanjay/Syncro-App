<?php
declare(strict_types=1);

if (!function_exists('base_url')) {
    /**
     * Prepend the base URL/path to a given URI.
     *
     * @param string $path
     * @return string
     */
    function base_url(string $path = ''): string
    {
        // Ensure path starts with a slash if not empty
        if ($path !== '' && $path[0] !== '/') {
            $path = '/' . $path;
        }

        // Use BASE_PATH if defined (from index.php), otherwise BASE_URL
        $base = defined('BASE_PATH') ? BASE_PATH : (defined('BASE_URL') ? BASE_URL : '');
        
        // Remove trailing slash from base
        $base = rtrim($base, '/');
        
        return $base . $path;
    }
}

if (!function_exists('e')) {
    /**
     * Escape HTML entities to prevent XSS.
     */
    function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        return \Syncro\Security\CsrfManager::generateToken();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string {
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrf_token()) . '">';
    }
}

if (!function_exists('csrf_meta')) {
    function csrf_meta(): string {
        return '<meta name="csrf-token" content="' . htmlspecialchars(csrf_token()) . '">';
    }
}

if (!function_exists('asset')) {
    /**
     * Resolve a frontend asset path.
     * In production, reads the Vite manifest to serve the hashed file.
     *
     * @param string $path e.g., 'assets/css/app.css'
     * @return string
     */
    function asset(string $path): string
    {
        $manifestPath = __DIR__ . '/../public/build/.vite/manifest.json';
        
        if (file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);
            if (isset($manifest[$path]['file'])) {
                return base_url('public/build/' . $manifest[$path]['file']);
            }
        }
        
        // Fallback if manifest isn't built yet
        return base_url($path);
    }
}

if (!function_exists('__')) {
    /**
     * Translate a string using the Translator service.
     *
     * @param string $key
     * @param array $replacements
     * @return string
     */
    function __(string $key, array $replacements = []): string
    {
        return \Syncro\Services\Translator::getInstance()->translate($key, $replacements);
    }
}
