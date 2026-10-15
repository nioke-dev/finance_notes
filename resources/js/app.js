import './bootstrap';

// Register Service Worker for PWA installability
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then((registration) => {
                console.log('Driver Finance SW active:', registration.scope);
            })
            .catch((error) => {
                console.error('Driver Finance SW registration failed:', error);
            });
    });
}

// Global deferred prompt for PWA installation
window.deferredPWAInstallPrompt = null;
window.addEventListener('beforeinstallprompt', (e) => {
    // Prevent the mini-infobar from appearing on mobile
    e.preventDefault();
    // Stash the event so it can be triggered later
    window.deferredPWAInstallPrompt = e;
    // Dispatch custom event for UI components if needed
    window.dispatchEvent(new CustomEvent('pwa-install-ready'));
    console.log('PWA installation prompt is ready.');
});

window.installDriverPWA = async () => {
    if (window.deferredPWAInstallPrompt) {
        window.deferredPWAInstallPrompt.prompt();
        const { outcome } = await window.deferredPWAInstallPrompt.userChoice;
        console.log(`User response to install prompt: ${outcome}`);
        window.deferredPWAInstallPrompt = null;
    } else {
        alert('Untuk menginstal di Chrome: Ketuk menu titik tiga (⋮) di kanan atas, lalu pilih "Instal aplikasi".');
    }
};
