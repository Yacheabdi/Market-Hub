<?php
/**
 * Campus Market Hub - University Registration View
 */
?>
<div class="auth-page-container">
    <div class="auth-card auth-card-wide">
        <div class="auth-header">
            <div class="auth-logo">
                <i class="fa-solid fa-id-card-clip"></i>
            </div>
            <h2>Register University Account</h2>
            <p>Join the verified campus marketplace. Open strictly to active students and university staff.</p>
        </div>

        <form action="/register" method="POST" class="auth-form" id="registerForm">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <div class="form-grid-2">
                <!-- Role Selector -->
                <div class="form-group full-width">
                    <label>Select Your Campus Affiliation</label>
                    <div class="role-selector-cards">
                        <label class="role-card">
                            <input type="radio" name="role" value="Student" checked onchange="updateIdLabel('Student')">
                            <div class="role-card-inner">
                                <i class="fa-solid fa-graduation-cap"></i>
                                <span class="role-title">University Student</span>
                                <small>Undergraduate & Postgraduate</small>
                            </div>
                        </label>
                        <label class="role-card">
                            <input type="radio" name="role" value="Staff" onchange="updateIdLabel('Staff')">
                            <div class="role-card-inner">
                                <i class="fa-solid fa-chalkboard-user"></i>
                                <span class="role-title">Faculty / Staff</span>
                                <small>Lecturers, Admin & Department Staff</small>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Full Name -->
                <div class="form-group">
                    <label for="full_name">Full Official Name</label>
                    <div class="input-with-icon">
                        <i class="fa-solid fa-user input-icon"></i>
                        <input type="text" id="full_name" name="full_name" class="form-control" placeholder="e.g. Mary Wangari" required>
                    </div>
                </div>

                <!-- RegNo / StaffNo -->
                <div class="form-group">
                    <label for="reg_staff_no" id="idLabel">Student Registration Number</label>
                    <div class="input-with-icon">
                        <i class="fa-solid fa-address-card input-icon"></i>
                        <input type="text" id="reg_staff_no" name="reg_staff_no" class="form-control" placeholder="e.g. SCM211-0024/2023 or STF/1042" required>
                    </div>
                    <small class="form-text">Used to verify your campus identity</small>
                </div>

                <!-- University Email -->
                <div class="form-group">
                    <label for="email">Institutional University Email</label>
                    <div class="input-with-icon">
                        <i class="fa-solid fa-envelope input-icon"></i>
                        <input type="email" id="email" name="email" class="form-control" placeholder="e.g. student@ku.ac.ke or name@campus.edu" required>
                    </div>
                    <small class="form-text">Must end in .edu, .ac.ke, or university domain</small>
                </div>

                <!-- Phone Number -->
                <div class="form-group">
                    <label for="phone">Mobile / WhatsApp Number</label>
                    <div class="input-with-icon">
                        <i class="fa-solid fa-phone input-icon"></i>
                        <input type="tel" id="phone" name="phone" class="form-control" placeholder="e.g. +254 712 345 678" required>
                    </div>
                    <small class="form-text">For buyer-seller meetup coordination</small>
                </div>

                <!-- Department / School -->
                <div class="form-group full-width">
                    <label for="department">School / Department / Faculty</label>
                    <div class="input-with-icon">
                        <i class="fa-solid fa-building-columns input-icon"></i>
                        <input type="text" id="department" name="department" class="form-control" placeholder="e.g. School of Pure & Applied Sciences, Computer Science" required>
                    </div>
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label for="password">Create Secure Password</label>
                    <div class="input-with-icon">
                        <i class="fa-solid fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Min. 6 characters" required>
                    </div>
                </div>

                <!-- Confirm Password -->
                <div class="form-group">
                    <label for="password_confirmation">Confirm Password</label>
                    <div class="input-with-icon">
                        <i class="fa-solid fa-shield-check input-icon"></i>
                        <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" placeholder="Repeat password" required>
                    </div>
                </div>
            </div>

            <!-- University Honor Pledge Checkbox -->
            <div class="campus-pledge-box">
                <label class="checkbox-label">
                    <input type="checkbox" required checked>
                    <span>I pledge to uphold the University Honor Code, maintain honesty in listings, and only arrange campus-area meetups.</span>
                </label>
            </div>

            <button type="submit" class="btn-auth-submit">
                <span>Complete Campus Registration</span>
                <i class="fa-solid fa-user-check"></i>
            </button>
        </form>

        <div class="auth-footer">
            <p>Already have a verified campus account? <a href="/login">Sign in here</a></p>
        </div>
    </div>
</div>

<script>
function updateIdLabel(role) {
    const label = document.getElementById('idLabel');
    const input = document.getElementById('reg_staff_no');
    if (role === 'Staff') {
        label.innerText = 'Staff Employment / Payroll Number';
        input.placeholder = 'e.g. STF/ENG/1042';
    } else {
        label.innerText = 'Student Registration Number';
        input.placeholder = 'e.g. SCM211-0024/2023';
    }
}
</script>
