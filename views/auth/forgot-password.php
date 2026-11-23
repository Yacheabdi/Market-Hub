<?php
/**
 * Campus Market Hub - Password Recovery View
 */
?>
<div class="auth-page-container">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-logo">
                <i class="fa-solid fa-key"></i>
            </div>
            <h2>Password Recovery</h2>
            <p>Enter your registered university email to receive account verification and reset instructions.</p>
        </div>

        <form action="/forgot-password" method="POST" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <div class="form-group">
                <label for="email">Institutional University Email</label>
                <div class="input-with-icon">
                    <i class="fa-solid fa-envelope input-icon"></i>
                    <input type="email" id="email" name="email" class="form-control" placeholder="e.g. lisa@student.campus.edu" required autofocus>
                </div>
            </div>

            <button type="submit" class="btn-auth-submit">
                <span>Send Reset Link</span>
                <i class="fa-solid fa-paper-plane"></i>
            </button>
        </form>

        <div class="auth-footer">
            <p>Remembered your credentials? <a href="/login">Return to login</a></p>
        </div>
    </div>
</div>
