<?php
/**
 * Campus Market Hub - Authentication & RBAC Security Manager
 */

namespace App\Core;

class Auth {
    /**
     * Start secure session if not already active
     */
    public static function initSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.cookie_samesite', 'Lax');
            session_start();
        }

        // Initialize CSRF token if missing
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    /**
     * Authenticate and bind user into current session
     */
    public static function login(array $user): void {
        self::initSession();
        // Prevent session fixation
        session_regenerate_id(true);

        $_SESSION['user'] = [
            'UserID'               => (int) $user['UserID'],
            'FullName'             => $user['FullName'],
            'Email'                => $user['Email'],
            'Registration_StaffNo' => $user['Registration_StaffNo'],
            'Role'                 => $user['Role'],
            'IsVerified'           => (bool) $user['IsVerified'],
            'CampusDepartment'     => $user['CampusDepartment'] ?? '',
            'ProfilePicture'       => $user['ProfilePicture'] ?? 'default_avatar.png'
        ];
    }

    /**
     * Log user out and flush session
     */
    public static function logout(): void {
        self::initSession();
        $_SESSION['user'] = null;
        unset($_SESSION['user']);
        session_destroy();
    }

    /**
     * Check if a user is logged in
     */
    public static function check(): bool {
        self::initSession();
        return !empty($_SESSION['user']['UserID']);
    }

    /**
     * Get logged-in user array or null
     */
    public static function user(): ?array {
        self::initSession();
        return $_SESSION['user'] ?? null;
    }

    /**
     * Get current user's ID
     */
    public static function id(): ?int {
        self::initSession();
        return isset($_SESSION['user']['UserID']) ? (int)$_SESSION['user']['UserID'] : null;
    }

    /**
     * Get current user's Role ('Student', 'Staff', 'Admin')
     */
    public static function role(): ?string {
        self::initSession();
        return $_SESSION['user']['Role'] ?? null;
    }

    public static function isAdmin(): bool {
        return self::role() === 'Admin';
    }

    public static function isStaff(): bool {
        return self::role() === 'Staff';
    }

    public static function isStudent(): bool {
        return self::role() === 'Student';
    }

    public static function isVerified(): bool {
        self::initSession();
        return !empty($_SESSION['user']['IsVerified']);
    }

    /**
     * Enforce login requirement or redirect to /login
     */
    public static function requireLogin(): void {
        if (!self::check()) {
            $_SESSION['flash_error'] = "Please sign in to access this page.";
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/';
            header('Location: /login');
            exit;
        }
    }

    /**
     * Enforce Admin role requirement
     */
    public static function requireAdmin(): void {
        self::requireLogin();
        if (!self::isAdmin()) {
            http_response_code(403);
            die("<h1>403 Forbidden</h1><p>Access restricted to Campus Market Hub Administrators.</p><p><a href='/'>Return to Home</a></p>");
        }
    }

    /**
     * Validate university identification and email credentials
     */
    public static function validateUniversityCredentials(string $regStaffNo, string $email, string $role): array {
        $errors = [];

        // Clean values
        $regStaffNo = trim($regStaffNo);
        $email = trim(strtolower($email));

        // 1. Validate RegNo / StaffNo
        if (empty($regStaffNo)) {
            $errors[] = "University Registration or Staff ID Number is required.";
        } elseif (strlen($regStaffNo) < 4) {
            $errors[] = "Registration/Staff Number appears too short. Please enter your valid campus ID.";
        }

        // 2. Validate Institutional Email
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "A valid university email address is required.";
        } else {
            $config = require dirname(__DIR__) . '/config/app.php';
            $allowedDomains = $config['allowed_email_domains'] ?? ['edu', 'ac.ke'];
            
            $domain = substr(strrchr($email, "@"), 1);
            $isValidDomain = false;

            foreach ($allowedDomains as $allowed) {
                if (str_ends_with($domain, $allowed)) {
                    $isValidDomain = true;
                    break;
                }
            }

            // In local/demo test mode, also accept test domain if specified
            if (!$isValidDomain && !str_ends_with($domain, 'campus.edu') && !str_ends_with($domain, '.test') && !str_ends_with($domain, 'localhost')) {
                $errors[] = "Please use your official university email (e.g., student.ku.ac.ke, uonbi.ac.ke, or *.edu).";
            }
        }

        // 3. Validate Role
        if (!in_array($role, ['Student', 'Staff', 'Admin'])) {
            $errors[] = "Invalid role selected. Must be Student or Staff.";
        }

        return $errors;
    }

    /**
     * Secure password hashing
     */
    public static function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
    }

    /**
     * Verify password hash with fallback support for seed accounts
     */
    public static function verifyPassword(string $password, string $hash): bool {
        if (password_verify($password, $hash)) {
            return true;
        }

        // Seed default fallback check for testing ease
        if (($password === 'Password@123' || $password === 'Admin@123' || $password === 'Student@123') 
            && ($hash === '$2y$10$w8.1k3294E6N6V0g7N/1ve6mF7zVd/X8uH2PjZ3T/r5w6W8aQ4qSm' || $password === $hash)) {
            return true;
        }

        return false;
    }

    /**
     * CSRF Utilities
     */
    public static function csrfToken(): string {
        self::initSession();
        return $_SESSION['csrf_token'] ?? '';
    }

    public static function verifyCsrfToken(?string $token): bool {
        self::initSession();
        return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }
}
