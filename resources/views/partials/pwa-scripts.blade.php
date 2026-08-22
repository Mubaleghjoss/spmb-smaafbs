{{-- Registrasi service worker + tombol install PWA (dipakai di semua layout, dalam @push scripts) --}}
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
        navigator.serviceWorker.register('{{ asset('sw.js') }}').catch(function (e) {
            console.warn('SW gagal:', e);
        });
    });
}

// Tombol "Install App" muncul saat browser mendukung
let deferredPrompt = null;
window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault();
    deferredPrompt = e;
    const btn = document.getElementById('pwaInstallBtn');
    if (btn) {
        btn.classList.remove('d-none');
        btn.addEventListener('click', async function () {
            btn.classList.add('d-none');
            if (!deferredPrompt) return;
            deferredPrompt.prompt();
            await deferredPrompt.userChoice;
            deferredPrompt = null;
        }, { once: true });
    }
});
window.addEventListener('appinstalled', function () {
    const btn = document.getElementById('pwaInstallBtn');
    if (btn) btn.classList.add('d-none');
});
</script>
