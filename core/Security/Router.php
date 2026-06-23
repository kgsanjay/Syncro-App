<?php
namespace Syncro\Security;

class Router {
    private array $routes = [];

    /**
     * Register a GET route
     */
    public function get(string $path, string $controller, string $method, array $middleware = []): void {
        $this->addRoute('GET', $path, $controller, $method, $middleware);
    }

    /**
     * Register a POST route
     */
    public function post(string $path, string $controller, string $method, array $middleware = []): void {
        $this->addRoute('POST', $path, $controller, $method, $middleware);
    }

    /**
     * Add route to internal collection
     */
    private function addRoute(string $httpMethod, string $path, string $controller, string $method, array $middleware): void {
        // Convert parameterized routes like /book/{slug} to regex
        // e.g. /book/{slug} -> ^/book/(?P<slug>[a-zA-Z0-9\-]+)$
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<\1>[a-zA-Z0-9\-]+)', $path);
        $pattern = '#^' . $pattern . '$#';
        
        $this->routes[] = [
            'method' => $httpMethod,
            'path' => $path,
            'pattern' => $pattern,
            'controller' => $controller,
            'action' => $method,
            'middleware' => $middleware
        ];
    }

    /**
     * Dispatch the request to the matched route
     */
    public function dispatch(string $method, string $uri): void {
        // CSRF Protection for state-mutating requests
        if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if (!\Syncro\Security\CsrfManager::validateToken($token)) {
                http_response_code(403);
                echo '<div style="font-family: sans-serif; text-align: center; padding: 50px; color: #002244;"><h1>403 Forbidden</h1><p>CSRF Token Validation Failed</p></div>';
                return;
            }
        }

        $allowedMethods = [];
        
        foreach ($this->routes as $route) {
            if (preg_match($route['pattern'], $uri, $matches)) {
                if ($route['method'] === $method) {
                    
                    // Execute Middleware Pipeline
                    foreach ($route['middleware'] as $key => $value) {
                        if (is_string($key)) {
                            $middlewareClass = $key;
                            $params = $value;
                            $mw = new $middlewareClass(...array_values($params));
                        } else {
                            $middlewareClass = $value;
                            $mw = new $middlewareClass();
                        }
                        $mw->handle();
                    }

                    // Extract named parameters or fallback to positional ones
                    $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                    if (empty($params)) {
                        // Remove the full match at index 0
                        $params = array_slice($matches, 1);
                    }
                    
                    // Automatically append $_POST and $_FILES if applicable, matching previous closure injection logic
                    if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
                        $params[] = $_POST;
                        if (!empty($_FILES)) {
                            $params[] = $_FILES;
                        }
                    }

                    // Instantiate controller and call action
                    global $db;
                    if (!$db) {
                        $db = new \Syncro\Models\Database();
                    }
                    $controllerInstance = new $route['controller']($db);
                    call_user_func_array([$controllerInstance, $route['action']], $params);
                    return;
                }
                $allowedMethods[] = $route['method'];
            }
        }

        // If the path matched but not the HTTP method
        if (!empty($allowedMethods)) {
            http_response_code(405);
            echo "Method Not Allowed.";
            return;
        }

        // Standard 404 Fallback
        http_response_code(404);
        echo '<div style="font-family: sans-serif; text-align: center; padding: 50px; color: #002244;"><h1>404</h1><p>Resource Not Found</p></div>';
    }
}
