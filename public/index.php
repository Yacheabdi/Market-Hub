<?php
/**
 * Campus Market Hub - Application Front Controller
 * Entry point for all HTTP requests.
 */

declare(strict_types=1);

// Report all errors in development mode
error_reporting(E_ALL);
ini_set('display_errors', '1');

define('BASE_PATH', dirname(__DIR__));

// Simple .env Loader
if (file_exists(BASE_PATH . '/.env')) {
    $lines = file(BASE_PATH . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (str_contains($line, '=')) {
            [$key, $val] = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val, " \t\n\r\0\x0B\"'");
            putenv("{$key}={$val}");
            $_ENV[$key] = $val;
            $_SERVER[$key] = $val;
        }
    }
}

// PSR-4 Style Class Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $parts = explode('\\', $relativeClass);

    // Map sub-namespaces to directories
    $dirMap = [
        'Core'        => 'core',
        'Controllers' => 'controllers',
        'Models'      => 'models',
        'Services'    => 'services'
    ];

    $subNamespace = array_shift($parts);
    $dir = $dirMap[$subNamespace] ?? strtolower($subNamespace);

    $file = BASE_PATH . '/' . $dir . '/' . implode('/', $parts) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Core\Router;
use App\Core\Auth;
use App\Controllers\AuthController;
use App\Controllers\HomeController;
use App\Controllers\ProductController;
use App\Controllers\MessageController;
use App\Controllers\TransactionController;
use App\Controllers\ProfileController;
use App\Controllers\AdminController;
use App\Controllers\RecommendationController;
use App\Controllers\MpesaController;

// Initialize Session
Auth::initSession();

// Initialize Router
$router = new Router();

// =====================================================================
// ROUTE DEFINITIONS
// =====================================================================

// Authentication & University Identity
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->get('/logout', [AuthController::class, 'logout']);
$router->get('/forgot-password', [AuthController::class, 'showForgotPassword']);
$router->post('/forgot-password', [AuthController::class, 'forgotPassword']);

// Marketplace Home & Discovery
$router->get('/', [HomeController::class, 'index']);

// Product Listings & Management
$router->get('/products/create', [ProductController::class, 'showCreate']);
$router->post('/products/create', [ProductController::class, 'store']);
$router->get('/products/manage', [ProductController::class, 'manage']);
$router->get('/products/edit/:id', [ProductController::class, 'showEdit']);
$router->post('/products/edit/:id', [ProductController::class, 'update']);
$router->post('/products/delete/:id', [ProductController::class, 'delete']);
$router->post('/products/toggle-status/:id', [ProductController::class, 'toggleStatus']);
$router->post('/products/report/:id', [ProductController::class, 'report']);
$router->get('/products/:id', [ProductController::class, 'view']);

// Real-Time Buyer-Seller Messaging
$router->get('/messages', [MessageController::class, 'index']);
$router->post('/api/messages/send', [MessageController::class, 'send']);
$router->get('/api/messages/poll', [MessageController::class, 'poll']);
$router->get('/api/messages/unread-count', [MessageController::class, 'unreadCount']);

// Transactions Lifecycle & Rating
$router->get('/transactions', [TransactionController::class, 'index']);
$router->post('/transactions/initiate', [TransactionController::class, 'initiate']);
$router->post('/transactions/status/:id', [TransactionController::class, 'updateStatus']);
$router->post('/transactions/review', [TransactionController::class, 'submitReview']);

// User Profile Dashboard
$router->get('/profile', [ProfileController::class, 'index']);
$router->post('/profile/update', [ProfileController::class, 'update']);
$router->post('/profile/change-password', [ProfileController::class, 'changePassword']);

// Intelligent Recommendation Engine API
$router->get('/api/recommendations', [RecommendationController::class, 'getRecommendations']);
$router->post('/api/recommendations/recompute', [RecommendationController::class, 'recompute']);

// Administrator & Fraud Control Panel
$router->get('/admin', [AdminController::class, 'index']);
$router->post('/admin/users/toggle-verification/:id', [AdminController::class, 'toggleVerification']);
$router->post('/admin/listings/delete/:id', [AdminController::class, 'deleteListing']);
$router->post('/admin/listings/toggle-featured/:id', [AdminController::class, 'toggleFeatured']);
$router->post('/admin/reports/resolve/:id', [AdminController::class, 'resolveComplaint']);

// M-Pesa Cashless Stub Integration
$router->post('/api/mpesa/initiate', [MpesaController::class, 'initiate']);
$router->post('/api/mpesa/callback', [MpesaController::class, 'callback']);

// Dispatch Request
$router->dispatch();
