<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Ujian') - {{ config('app.name') }}</title>
    @if(!empty($branding['favicon']))
    <link rel="icon" href="{{ asset('storage/' . $branding['favicon']) }}" type="image/x-icon">
    @endif
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        /* Alpine.js cloak - hide elements until Alpine loads */
        [x-cloak] { display: none !important; }
        
        body {
            background-color: #f8f9fa;
        }
        .ujian-header {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .timer {
            font-size: 1.25rem;
            font-weight: bold;
            font-family: monospace;
        }
        .pertanyaan-text {
            font-size: 1.1rem;
            line-height: 1.8;
        }
        .pertanyaan-text img {
            max-width: 100%;
            height: auto;
        }
        .form-check-label {
            cursor: pointer;
        }
        .soal-jawaban .form-check:hover {
            background-color: #f8f9fa;
        }
        /* Disable text selection untuk anti-cheat */
        .ujian-container {
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
        }
        .soal-jawaban textarea {
            user-select: text;
            -webkit-user-select: text;
        }
    </style>
    
    @stack('styles')
</head>
<body>
    @yield('content')

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <script>
        // Anti-cheat configuration
        const antiCheat = {
            isNavigating: false,
            isInteracting: false,
            lastWarningTime: 0,
            warningCooldown: 5000, // 5 detik cooldown antara peringatan
            interactionCooldown: 1500, // 1.5 detik setelah interaksi
            lastInteractionTime: 0,
            sesiId: null,
            
            init(sesiId) {
                this.sesiId = sesiId;
                this.setupEventListeners();
            },
            
            markInteraction() {
                this.isInteracting = true;
                this.lastInteractionTime = Date.now();
                // Reset setelah cooldown
                setTimeout(() => { 
                    this.isInteracting = false; 
                }, this.interactionCooldown);
            },
            
            shouldShowWarning() {
                const now = Date.now();
                
                // Jangan tampilkan jika sedang navigasi
                if (this.isNavigating) return false;
                
                // Jangan tampilkan jika baru saja ada interaksi (klik, dll)
                if (this.isInteracting) return false;
                if (now - this.lastInteractionTime < this.interactionCooldown) return false;
                
                // Jangan tampilkan jika masih dalam cooldown
                if (now - this.lastWarningTime < this.warningCooldown) return false;
                
                return true;
            },
            
            setupEventListeners() {
                // Mark interaction saat klik apapun di halaman
                document.addEventListener('mousedown', () => {
                    this.markInteraction();
                });
                
                // Mark interaction saat touch (mobile)
                document.addEventListener('touchstart', () => {
                    this.markInteraction();
                });
                
                // Mark interaction saat focus pada input/button
                document.addEventListener('focusin', () => {
                    this.markInteraction();
                });
                
                // Mark navigation when clicking links
                document.addEventListener('click', (e) => {
                    this.markInteraction();
                    
                    const link = e.target.closest('a');
                    const button = e.target.closest('button');
                    const input = e.target.closest('input');
                    const label = e.target.closest('label');
                    
                    if (link || button || input || label) {
                        this.isNavigating = true;
                        // Reset after longer delay
                        setTimeout(() => { this.isNavigating = false; }, 2000);
                    }
                });
                
                // Mark navigation when submitting forms
                document.addEventListener('submit', () => {
                    this.isNavigating = true;
                    this.markInteraction();
                });
                
                // Anti-cheat: Disable right-click
                document.addEventListener('contextmenu', (e) => {
                    e.preventDefault();
                });

                // Anti-cheat: Warn on tab/window change (with better detection)
                document.addEventListener('visibilitychange', () => {
                    // Hanya proses jika halaman benar-benar hidden
                    if (document.visibilityState === 'hidden') {
                        // Delay sedikit untuk memastikan bukan false positive
                        setTimeout(() => {
                            // Cek lagi apakah masih hidden dan tidak ada interaksi
                            if (document.visibilityState === 'hidden' && this.shouldShowWarning()) {
                                this.lastWarningTime = Date.now();
                                this.recordWarning();
                                // Tampilkan alert hanya jika halaman masih hidden
                                if (document.visibilityState === 'hidden') {
                                    alert('Perhatian! Anda meninggalkan halaman ujian. Aktivitas ini tercatat.');
                                }
                            }
                        }, 300); // Delay 300ms untuk menghindari false positive
                    }
                });

                // Prevent copy
                document.addEventListener('copy', (e) => {
                    e.preventDefault();
                });

                // Prevent keyboard shortcuts
                document.addEventListener('keydown', (e) => {
                    this.markInteraction();
                    
                    // Prevent Ctrl+C, Ctrl+V, Ctrl+U, F12
                    if ((e.ctrlKey && (e.key === 'c' || e.key === 'v' || e.key === 'u')) || e.key === 'F12') {
                        e.preventDefault();
                        return false;
                    }
                });
            },
            
            async recordWarning() {
                if (!this.sesiId) return;
                
                try {
                    await fetch('/ujian/sesi/' + this.sesiId + '/peringatan', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });
                } catch (error) {
                    console.error('Failed to record warning:', error);
                }
            }
        };
        
        // Initialize anti-cheat when sesi ID is available
        @if(isset($sesi))
        document.addEventListener('DOMContentLoaded', () => {
            antiCheat.init({{ $sesi->id }});
        });
        @endif
    </script>
    
    @stack('scripts')
</body>
</html>
