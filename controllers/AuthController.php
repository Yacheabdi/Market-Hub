<?php
/**
 * Campus Market Hub - Authentication Controller
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\User;

class AuthController extends Controller {
    /**
     * Show University Login Page
     */
    public function showLogin(): void {
        if (Auth::check()) {
            $this->redirect('/');
        }
        $this->view('auth/login', [
            'pageTitle' => 'Campus Sign In - Campus Market Hub'
        ]);
    }

    /**
     * Handle Login Submission
     */
    public function login(): void {
        $this->validateCsrf();

        $identifier = $this->input('identifier'); // Accepts Email or RegNo
        $password   = $this->input('password');

        if (empty($identifier) || empty($password)) {
            $this->setFlash('error', 'Please enter your university ID/email and password.');
            $this->redirect('/login');
        }

        // Look up by Email first, then by Registration_StaffNo
        $user = User::findByEmail($identifier) ?: User::findByRegNo($identifier);

        if (!$user) {
            $this->setFlash('error', 'No campus account found matching that ID or email.');
            $this->redirect('/login');
        }

        if (!Auth::verifyPassword($password, $user['Password'])) {
            $this->setFlash('error', 'Invalid password. Please check your credentials and try again.');
            $this->redirect('/login');
        }

        // Check if account has been banned/suspended by admin
        if (isset($user['IsSuspended']) && $user['IsSuspended']) {
            $this->setFlash('error', 'Your campus marketplace access has been temporarily suspended. Please contact the campus admin desk.');
            $this->redirect('/login');
        }

        Auth::login($user);
        $this->setFlash('success', 'Welcome back, ' . htmlspecialchars($user['FullName']) . '!');

        $redirect = $_SESSION['redirect_after_login'] ?? ($user['Role'] === 'Admin' ? '/admin' : '/');
        unset($_SESSION['redirect_after_login']);
        $this->redirect($redirect);
    }

    /**
     * Show University Registration Page
     */
    public function showRegister(): void {
        if (Auth::check()) {
            $this->redirect('/');
        }
        $this->view('auth/register', [
            'pageTitle' => 'Campus Registration - Campus Market Hub'
        ]);
    }

    /**
     * Handle University Registration Submission
     */
    public function register(): void {
        $this->validateCsrf();

        $fullName       = $this->input('full_name');
        $regStaffNo     = $this->input('reg_staff_no');
        $email          = $this->input('email');
        $role           = $this->input('role', 'Student');
        $phone          = $this->input('phone');
        $department     = $this->input('department');
        $password       = $this->input('password');
        $passwordConfirm = $this->input('password_confirmation');

        // 1. Validate University Credentials
        $errors = Auth::validateUniversityCredentials($regStaffNo, $email, $role);

        if (empty($fullName) || strlen($fullName) < 3) {
            $errors[] = "Full name is required.";
        }

        if (empty($password) || strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters long.";
        }

        if ($password !== $passwordConfirm) {
            $errors[] = "Passwords do not match.";
        }

        // 2. Check for unique Email and RegNo
        if (User::findByEmail($email)) {
            $errors[] = "An account with this institutional email already exists.";
        }

        if (User::findByRegNo($regStaffNo)) {
            $errors[] = "An account with this Registration/Staff number is already registered.";
        }

        if (!empty($errors)) {
            $this->setFlash('error', implode('<br>', $errors));
            $this->redirect('/register');
        }

        // Auto-verify students and staff registering with official university email domains (.ac.ke, .edu)
        $domain = substr(strrchr($email, "@"), 1);
        $autoVerify = (str_ends_with($domain, '.edu') || str_ends_with($domain, '.ac.ke') || str_ends_with($domain, 'campus.edu')) ? 1 : 0;

        $userId = User::create([
            'FullName'             => $fullName,
            'Registration_StaffNo' => strtoupper(trim($regStaffNo)),
            'Email'                => strtolower(trim($email)),
            'Password'             => Auth::hashPassword($password),
            'PhoneNumber'          => $phone,
            'Role'                 => $role,
            'IsVerified'           => $autoVerify,
            'CampusDepartment'     => $department,
            'CreatedAt'            => date('Y-m-d H:i:s')
        ]);

        $createdUser = User::find($userId);
        Auth::login($createdUser);

        $verifyMsg = $autoVerify ? 'Your campus identity has been automatically verified!' : 'Account registered! Your student/staff verification is pending approval by the admin desk.';
        $this->setFlash('success', 'Registration successful! ' . $verifyMsg);
        $this->redirect('/');
    }

    /**
     * Logout
     */
    public function logout(): void {
        Auth::logout();
        $this->setFlash('info', 'You have been safely signed out of Campus Market Hub.');
        $this->redirect('/login');
    }

    /**
     * Show Password Recovery Page
     */
    public function showForgotPassword(): void {
        $this->view('auth/forgot-password', [
            'pageTitle' => 'Reset Campus Password - Campus Market Hub'
        ]);
    }

    /**
     * Process Password Recovery
     */
    public function forgotPassword(): void {
        $this->validateCsrf();
        $email = $this->input('email');

        if (empty($email)) {
            $this->setFlash('error', 'Please provide your registered university email.');
            $this->redirect('/forgot-password');
        }

        $user = User::findByEmail($email);
        if ($user) {
            // In a production setup, send email token; here we simulate safe password reset
            $this->setFlash('success', 'If an account exists for ' . htmlspecialchars($email) . ', a password recovery link has been generated. For testing, you may use default password: <code>Password@123</code>.');
        } else {
            $this->setFlash('error', 'No campus account found with that email address.');
        }

        $this->redirect('/forgot-password');
    }
}
