<?php
/**
 * Campus Market Hub - Base Controller
 */

namespace App\Core;

class Controller {
    public function __construct() {
        Auth::initSession();
    }

    /**
     * Render a view file embedded in a master layout
     */
    protected function view(string $viewPath, array $data = [], string $layout = 'main'): void {
        // Expose helper variables to all views
        $data['currentUser'] = Auth::user();
        $data['isLoggedIn']  = Auth::check();
        $data['isAdmin']     = Auth::isAdmin();
        $data['csrfToken']   = Auth::csrfToken();
        $data['flashes']     = $this->getFlashes();

        // Extract variables into local scope for view
        extract($data);

        $viewFile = dirname(__DIR__) . '/views/' . ltrim($viewPath, '/') . '.php';
        if (!file_exists($viewFile)) {
            die("View file not found: <code>{$viewFile}</code>");
        }

        // Buffer the view content
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        // If no layout is specified, output view directly
        if (!$layout) {
            echo $content;
            return;
        }

        // Include master layout template
        $layoutFile = dirname(__DIR__) . '/views/layouts/' . $layout . '.php';
        if (file_exists($layoutFile)) {
            require $layoutFile;
        } else {
            echo $content;
        }
    }

    /**
     * Send JSON HTTP response
     */
    protected function json(array $data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    /**
     * Redirect to another URL
     */
    protected function redirect(string $url): void {
        header('Location: ' . $url);
        exit;
    }

    /**
     * Set a session flash message
     */
    protected function setFlash(string $type, string $message): void {
        $_SESSION['flash_' . $type] = $message;
    }

    /**
     * Retrieve and clear all session flash messages
     */
    protected function getFlashes(): array {
        $flashes = [];
        foreach (['success', 'error', 'warning', 'info'] as $type) {
            $key = 'flash_' . $type;
            if (isset($_SESSION[$key])) {
                $flashes[$type] = $_SESSION[$key];
                unset($_SESSION[$key]);
            }
        }
        return $flashes;
    }

    /**
     * Safely read input from POST or GET
     */
    protected function input(string $key, $default = null) {
        if (isset($_POST[$key])) {
            return is_string($_POST[$key]) ? trim($_POST[$key]) : $_POST[$key];
        }
        if (isset($_GET[$key])) {
            return is_string($_GET[$key]) ? trim($_GET[$key]) : $_GET[$key];
        }
        return $default;
    }

    /**
     * Sanitize input strings against XSS
     */
    protected function sanitize($input) {
        if (is_array($input)) {
            return array_map([$this, 'sanitize'], $input);
        }
        return htmlspecialchars(trim((string)$input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Verify CSRF token from POST
     */
    protected function validateCsrf(): void {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!Auth::verifyCsrfToken($token)) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'error' => 'Invalid or expired CSRF security token.'], 403);
            }
            $this->setFlash('error', 'Session expired or invalid form token. Please try again.');
            $this->redirect($_SERVER['HTTP_REFERER'] ?? '/');
        }
    }

    /**
     * Check if request is AJAX / JSON
     */
    protected function isAjax(): bool {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
    }
}
