// Bootstrap 5
import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

// Alpine.js
import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();

// Motion.dev — animation library
import { animate, scroll, stagger, spring } from "motion";
window.motionAnimate = animate;
window.motionScroll = scroll;
window.motionStagger = stagger;

// CSRF Token for AJAX requests
const token = document.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios = {
        defaults: {
            headers: {
                common: {
                    'X-CSRF-TOKEN': token.content,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }
        }
    };
}

// Dark mode toggle
document.addEventListener('DOMContentLoaded', function() {
    const darkModeToggle = document.getElementById('darkModeToggle');
    const htmlElement = document.documentElement;
    
    // Check saved preference
    const savedTheme = localStorage.getItem('theme') || 'light';
    htmlElement.setAttribute('data-bs-theme', savedTheme);
    
    if (darkModeToggle) {
        darkModeToggle.addEventListener('click', function() {
            const currentTheme = htmlElement.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            htmlElement.setAttribute('data-bs-theme', newTheme);
            localStorage.setItem('theme', newTheme);
        });
    }
});

// Toast notification helper
window.showToast = function(message, type = 'success') {
    const toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) return;
    
    const toastHtml = `
        <div class="toast align-items-center text-bg-${type} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    const toastElement = toastContainer.lastElementChild;
    const toast = new bootstrap.Toast(toastElement);
    toast.show();
    
    toastElement.addEventListener('hidden.bs.toast', () => toastElement.remove());
};

// ============================================================
// Motion.dev — Auto-animate UI elements on page load
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    // 1. Stat cards — staggered fade-in + slide-up
    const statCards = document.querySelectorAll('.stat-card, .card-stat');
    if (statCards.length) {
        animate(statCards,
            { opacity: [0, 1], y: [30, 0] },
            { duration: 0.5, delay: stagger(0.08), easing: [0.22, 1, 0.36, 1] }
        );
    }

    // 2. Flash alerts — spring bounce-in
    const alerts = document.querySelectorAll('.alert');
    if (alerts.length) {
        animate(alerts,
            { opacity: [0, 1], x: [-40, 0] },
            { duration: 0.6, easing: [0.22, 1, 0.36, 1] }
        );
    }

    // 3. Table rows — stagger fade-in
    const tableRows = document.querySelectorAll('.table tbody tr');
    if (tableRows.length) {
        animate(tableRows,
            { opacity: [0, 1], y: [10, 0] },
            { duration: 0.3, delay: stagger(0.03), easing: "ease-out" }
        );
    }

    // 4. Sidebar nav items — stagger slide-in
    const navItems = document.querySelectorAll('.sidebar .nav-item');
    if (navItems.length) {
        animate(navItems,
            { opacity: [0, 1], x: [-20, 0] },
            { duration: 0.4, delay: stagger(0.05), easing: [0.22, 1, 0.36, 1] }
        );
    }

    // 5. Main content — gentle fade
    const mainContent = document.getElementById('mainContent');
    if (mainContent) {
        animate(mainContent,
            { opacity: [0, 1] },
            { duration: 0.4, easing: "ease-out" }
        );
    }

    // 6. Cards general — stagger fade (dashboard, settings, etc.)
    const cards = document.querySelectorAll('.card:not(.stat-card):not(.card-stat)');
    if (cards.length) {
        animate(cards,
            { opacity: [0, 1], y: [20, 0] },
            { duration: 0.4, delay: stagger(0.06), easing: [0.22, 1, 0.36, 1] }
        );
    }
});

