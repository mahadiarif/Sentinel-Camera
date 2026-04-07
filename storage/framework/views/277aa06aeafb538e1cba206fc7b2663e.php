<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo e(config('app.name', 'MetroNet')); ?> — AI Surveillance</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&family=Space+Mono:ital,wght@0,400;0,700;1,400;1,700&family=Syne:wght@400..800&display=swap" rel="stylesheet">
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --bg: #050a0e;
            --surface: #0b1318;
            --surface2: #111c23;
            --border: #1a2e3a;
            --accent: #00e5ff;
            --accent2: #ff3d6b;
            --accent3: #b8ff57;
            --text: #c8dde8;
            --text-dim: #4a6478;
            --warn: #ffaa00;
        }

        body {
            background-color: var(--bg);
            color: var(--text);
            font-family: 'Outfit', sans-serif;
            overflow-x: hidden;
            font-weight: 400;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: -0.02em;
        }

        .font-mono {
            font-family: 'Space Mono', monospace;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 280px;
            height: 100vh;
            position: fixed;
            background: var(--surface);
            border-right: 1px solid var(--border);
            padding: 2rem 1.5rem;
            z-index: 1000;
        }

        .main-content {
            margin-left: 280px;
            padding: 2rem;
            min-height: 100vh;
        }

        .nav-link {
            color: var(--text-dim);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            font-weight: 500;
        }

        .nav-link i {
            margin-right: 1rem;
            font-size: 1.2rem;
        }

        .nav-link:hover, .nav-link.active {
            background: var(--surface2);
            color: var(--accent);
            box-shadow: inset 0 0 0 1px var(--border);
        }

        .nav-link.active {
            color: var(--accent);
            border-left: 4px solid var(--accent);
        }

        /* Card Styles */
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            color: var(--text);
            overflow: hidden;
        }

        .card-header {
            background: rgba(255,255,255,0.03);
            border-bottom: 1px solid var(--border);
            padding: 1.25rem;
        }

        /* Surveillance Status Badges */
        .badge-surv {
            text-transform: uppercase;
            font-size: 0.75rem;
            padding: 0.4rem 0.8rem;
            border-radius: 4px;
            font-weight: 800;
        }

        .bg-accent { background-color: var(--accent); color: #000; }
        .bg-accent2 { background-color: var(--accent2); color: #fff; }
        .bg-accent3 { background-color: var(--accent3); color: #000; }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: var(--bg); }
        ::-webkit-scrollbar-thumb { background: var(--surface2); border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--border); }

        /* Global Alert Banner placeholder */
        #alertBanner {
            position: fixed;
            top: -100px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 9999;
            width: 90%;
            max-width: 600px;
            transition: top 0.5s cubic-bezier(0.68, -0.55, 0.27, 1.55);
        }

        #alertBanner.show { top: 20px; }

        @media (max-width: 992px) {
            .sidebar { width: 80px; padding: 2rem 0.5rem; text-align: center; }
            .sidebar span { display: none; }
            .main-content { margin-left: 80px; }
            .nav-link i { margin-right: 0; font-size: 1.5rem; }
        }
    </style>
</head>
<body>

    <div id="alertBanner">
        <div class="alert bg-danger text-white border-0 shadow-lg d-flex align-items-center p-3 rounded-4">
            <i class="fas fa-triangle-exclamation fa-2x me-3"></i>
            <div>
                <h6 class="mb-0 fw-bold">SURVEILLANCE ALERT</h6>
                <small id="alertText">Object detected at Camera 1</small>
            </div>
        </div>
    </div>

    <aside class="sidebar d-flex flex-column">
        <div class="brand mb-5 px-3">
            <h4 class="text-white mb-0" style="letter-spacing: 2px;">
                <span style="color: var(--accent)">METRO</span>NET
            </h4>
            <small class="text-dim">V.1.0 SURVEILLANCE</small>
        </div>

        <nav class="flex-grow-1">
            <a href="<?php echo e(route('dashboard')); ?>" class="nav-link <?php echo e(request()->routeIs('dashboard') ? 'active' : ''); ?>">
                <i class="fas fa-th-large"></i> <span>Dashboard</span>
            </a>
            <a href="<?php echo e(route('gate.monitor')); ?>" class="nav-link <?php echo e(request()->routeIs('gate.*') ? 'active' : ''); ?>">
                <i class="fas fa-video"></i> <span>Gate Monitor</span>
            </a>
            <a href="<?php echo e(route('cameras.index')); ?>" class="nav-link <?php echo e(request()->routeIs('cameras.*') ? 'active' : ''); ?>">
                <i class="fas fa-sliders"></i> <span>Camera Config</span>
            </a>
            <a href="<?php echo e(route('training.index')); ?>" class="nav-link <?php echo e(request()->routeIs('training.*') ? 'active' : ''); ?>">
                <i class="fas fa-bullseye"></i> <span>Object Training</span>
            </a>
        </nav>

        <div class="mt-auto">
            <form action="<?php echo e(route('logout')); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <button type="submit" class="nav-link text-danger w-100 border-0 bg-transparent">
                    <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                </button>
            </form>
        </div>
    </aside>

    <main class="main-content">
        <?php echo $__env->yieldContent('content'); ?>
    </main>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Pusher/Echo Library (Commented out until configured) -->
    <!--
    <script src="https://js.pusher.com/8.0/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.15.3/dist/echo.iife.js"></script>
    <script>
        window.Pusher = Pusher;
        window.Echo = new Echo({
            broadcaster: 'pusher',
            key: 'your-app-key',
            cluster: 'your-cluster',
            forceTLS: true
        });
    </script>
    -->

    <script>
        // Global Alert Function
        function showAlert(message) {
            const banner = document.getElementById('alertBanner');
            const alertText = document.getElementById('alertText');
            alertText.innerText = message;
            banner.classList.add('show');
            
            // Beep Sound
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                if (ctx.state === 'suspended') ctx.resume();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sawtooth';
                osc.frequency.setValueAtTime(440, ctx.currentTime);
                osc.frequency.exponentialRampToValueAtTime(880, ctx.currentTime + 0.1);
                gain.gain.setValueAtTime(0.1, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.3);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start();
                osc.stop(ctx.currentTime + 0.3);
            } catch (e) {
                console.error('Audio alert failed:', e);
            }

            setTimeout(() => {
                banner.classList.remove('show');
            }, 6000);
        }
    </script>

    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH C:\Users\Mahadi Hassan Arif\Desktop\Camera Tracking\sentinel\resources\views\layouts\app.blade.php ENDPATH**/ ?>