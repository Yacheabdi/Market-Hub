<?php
/**
 * Campus Market Hub - User Profile & Account Settings Controller
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\User;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\Recommendation;

class ProfileController extends Controller {
    /**
     * Profile Dashboard View (/profile)
     */
    public function index(): void {
        Auth::requireLogin();
        $userId = Auth::id();

        $user = User::find($userId);
        $stats = User::getStats($userId);
        $myListings = Product::getUserListings($userId);
        $myTransactions = Transaction::getUserTransactions($userId);
        $recommendations = Recommendation::getForUser($userId, 4);

        $this->view('profile/index', [
            'pageTitle'       => 'My Campus Profile & Settings - Campus Market Hub',
            'user'            => $user,
            'stats'           => $stats,
            'myListings'      => $myListings,
            'myTransactions'  => $myTransactions,
            'recommendations' => $recommendations
        ]);
    }

    /**
     * Update Profile Information
     */
    public function update(): void {
        Auth::requireLogin();
        $this->validateCsrf();

        $userId     = Auth::id();
        $fullName   = $this->input('full_name');
        $phone      = $this->input('phone');
        $department = $this->input('department');
        $bio        = $this->input('bio');

        if (empty($fullName) || strlen($fullName) < 3) {
            $this->setFlash('error', 'Full name is required.');
            $this->redirect('/profile');
        }

        User::update($userId, [
            'FullName'         => $fullName,
            'PhoneNumber'      => $phone,
            'CampusDepartment' => $department,
            'Bio'              => $bio
        ]);

        // Refresh session
        $updatedUser = User::find($userId);
        Auth::login($updatedUser);

        $this->setFlash('success', 'Your campus profile details have been updated.');
        $this->redirect('/profile');
    }

    /**
     * Change Password
     */
    public function changePassword(): void {
        Auth::requireLogin();
        $this->validateCsrf();

        $userId          = Auth::id();
        $currentPassword = $this->input('current_password');
        $newPassword     = $this->input('new_password');
        $confirmPassword = $this->input('confirm_password');

        $user = User::find($userId);

        if (!Auth::verifyPassword($currentPassword, $user['Password'])) {
            $this->setFlash('error', 'Current password entered is incorrect.');
            $this->redirect('/profile');
        }

        if (strlen($newPassword) < 6) {
            $this->setFlash('error', 'New password must be at least 6 characters long.');
            $this->redirect('/profile');
        }

        if ($newPassword !== $confirmPassword) {
            $this->setFlash('error', 'New password and confirmation do not match.');
            $this->redirect('/profile');
        }

        User::update($userId, [
            'Password' => Auth::hashPassword($newPassword)
        ]);

        $this->setFlash('success', 'Your password has been securely updated.');
        $this->redirect('/profile');
    }
}
