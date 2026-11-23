<?php
/**
 * Campus Market Hub - University Login View
 */
?>
<div class="auth-page-container">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-logo">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>
            <h2>Campus Sign In</h2>
            <p>Access your university marketplace, trade with verified peers, and manage transactions.</p>
        </div>

        <form action="/login" method="POST" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <div class="form-group">
                <label for="identifier">University Email or Registration / Staff No</label>
                <div class="input-with-icon">
                    <i class="fa-solid fa-id-card input-icon"></i>
                    <input type="text" id="identifier" name="identifier" class="form-control" placeholder="e.g. SCM211-0024/2023 or lisa@student.campus.edu" required autofocus>
                </div>
                <small class="form-text">Enter your university ID or campus email address</small>
            </div>

            <div class="form-group">
                <div class="label-row">
                    <label for="password">Campus Password</label>
                    <a href="/forgot-password" class="forgot-link">Forgot Password?</a>
                </div>
                <div class="input-with-icon">
                    <i class="fa-solid fa-lock input-icon"></i>
                    <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                    <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('password', this)">
                        <i class="fa-regular fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-auth-submit">
                <span>Sign In to Campus Hub</span>
                <i class="fa-solid fa-arrow-right"></i>
            </button>
        </form>

        <div class="auth-footer">
            <p>New to the campus network? <a href="/register">Create a verified account</a></p>
        </div>

        <!-- Demo Accounts Quick Auto-fill Widget for Testing -->
        <div class="demo-login-widget">
            <div class="demo-widget-title"><i class="fa-solid fa-bolt"></i> Instant Demo Testing Logins:</div>
            <div class="demo-buttons">
                <button type="button" class="btn-demo-pill" onclick="fillLogin('lisa.doe@student.campus.edu', 'Password@123')">
                    <i class="fa-solid fa-user-graduate"></i> Student (lisa)
                </button>
                <button type="button" class="btn-demo-pill" onclick="fillLogin('dr.sarah@campus.edu', 'Password@123')">
                    <i class="fa-solid fa-user-tie"></i> Staff (Dr. Sarah)
                </button>
                <button type="button" class="btn-demo-pill" onclick="fillLogin('admin@campus.edu', 'Password@123')">
                    <i class="fa-solid fa-shield-halved"></i> Campus Admin
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function fillLogin(email, password) {
    document.getElementById('identifier').value = email;
    document.getElementById('password').value = password;
}

function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>
