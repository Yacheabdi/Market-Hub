<?php
/**
 * Campus Market Hub - URL Router & Dispatcher
 */

namespace App\Core;

class Router {
    private array $routes = [];
    private array $namedParams = [];

    /**
     * Register a GET route
     */
    public function get(string $path, array $handler): self {
        return $this->addRoute('GET', $path, $handler);
    }

    /**
     * Register a POST route
     */
    public function post(string $path, array $handler): self {
        return $this->addRoute('POST', $path, $handler);
    }

    /**
     * Internal route registration with regex pattern conversion
     */
    private function addRoute(string $method, string $path, array $handler): self {
        $pattern = preg_replace('/\/:([a-zA-Z0-9_]+)/', '/(?P<$1>[^/]+)', $path);
        $pattern = "#^" . $pattern . "$#";

        $this->routes[] = [
            'method'  => strtoupper($method),
            'path'    => $path,
            'pattern' => $pattern,
            'handler' => $handler
        ];

        return $this;
    }

    /**
     * Dispatch incoming HTTP request to registered handler
     */
    public function dispatch(): void {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        // Handle method override from hidden form fields (_method=DELETE/PUT)
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        
        // Strip trailing slash except root
        if ($uri !== '/' && str_ends_with($uri, '/')) {
            $uri = rtrim($uri, '/');
        }

        // Support subdirectory path if hosted inside subfolder like /campus-market-hub/public/
        $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        if ($scriptDir !== '/' && str_starts_with($uri, $scriptDir)) {
            $uri = substr($uri, strlen($scriptDir));
            if ($uri === '' || !str_starts_with($uri, '/')) {
                $uri = '/' . $uri;
            }
        }

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['pattern'], $uri, $matches)) {
                $params = [];
                foreach ($matches as $key => $value) {
                    if (!is_int($key)) {
                        $params[$key] = urldecode($value);
                    }
                }

                [$controllerClass, $action] = $route['handler'];

                if (!class_exists($controllerClass)) {
                    die("Controller <code>{$controllerClass}</code> not found.");
                }

                $controller = new $controllerClass();

                if (!method_exists($controller, $action)) {
                    die("Action <code>{$action}</code> not found in <code>{$controllerClass}</code>.");
                }

                // Call the controller action with extracted parameters
                call_user_func_array([$controller, $action], [$params]);
                return;
            }
        }

        // 404 Not Found
        http_response_code(404);
        if (str_starts_with($uri, '/api/')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'API endpoint not found: ' . $uri]);
            return;
        }

        // Render clean 404 HTML
        echo '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Page Not Found | Campus Market Hub</title>
            <link rel="stylesheet" href="/css/style.css">
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f8fafc; color: #1e293b; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
                .error-box { background: white; padding: 2.5rem; border-radius: 12px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); text-align: center; max-width: 480px; width: 90%; }
                .code { font-size: 5rem; font-weight: 800; color: #1e3a8a; margin: 0; line-height: 1; }
                h2 { margin: 1rem 0 0.5rem; font-size: 1.5rem; }
                p { color: #64748b; margin-bottom: 1.5rem; }
                a { display: inline-block; background: #1e3a8a; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 6px; font-weight: 500; }
                a:hover { background: #1e40af; }
            </style>
        </head>
        <body>
            <div class="error-box">
                <div class="code">404</div>
                <h2>Page Not Found</h2>
                <p>The marketplace page or item you are looking for does not exist or has been removed.</p>
                <a href="/">Back to Marketplace Home</a>
            </div>
        </body>
        </html>';
    }
}
