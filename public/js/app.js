/**
 * Campus Market Hub - Main Frontend Application Logic
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. User Dropdown Menu Toggle
    const userMenuBtn = document.getElementById('userMenuBtn');
    const userMenuDropdown = document.getElementById('userMenuDropdown');

    if (userMenuBtn && userMenuDropdown) {
        userMenuBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            userMenuDropdown.classList.toggle('show');
        });

        document.addEventListener('click', (e) => {
            if (!userMenuDropdown.contains(e.target) && !userMenuBtn.contains(e.target)) {
                userMenuDropdown.classList.remove('show');
            }
        });
    }

    // 2. Mobile Nav Drawer Toggle
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const closeDrawerBtn = document.getElementById('closeDrawerBtn');
    const mobileDrawer = document.getElementById('mobileDrawer');

    if (mobileMenuBtn && mobileDrawer) {
        mobileMenuBtn.addEventListener('click', () => {
            mobileDrawer.classList.add('show');
        });
    }

    if (closeDrawerBtn && mobileDrawer) {
        closeDrawerBtn.addEventListener('click', () => {
            mobileDrawer.classList.remove('show');
        });
    }

    // 3. Periodic Unread Message Count Poller
    setInterval(checkUnreadNotifications, 15000);
});

// Modal Open / Close Utilities
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }
}

// Close modals when clicking outside modal card
window.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('show');
        document.body.style.overflow = '';
    }
});

// Check unread messages for navbar badge
function checkUnreadNotifications() {
    fetch('/api/messages/unread-count')
        .then(res => res.json())
        .then(data => {
            const badges = document.querySelectorAll('.unread-badge');
            badges.forEach(badge => {
                if (data.unread && data.unread > 0) {
                    badge.innerText = data.unread;
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }
            });
        })
        .catch(() => {});
}
