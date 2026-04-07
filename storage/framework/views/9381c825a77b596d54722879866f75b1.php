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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;700&family=Rajdhani:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Icons & CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

    <style>
        :root {
            /* Core palette */
            --bg:          #060b12;
            --bg2:         #080e17;
            --surface:     #0d1621;
            --surface2:    #111d2c;
            --surface3:    #162236;
            --border:      #1e3247;
            --border2:     #243d55;

            /* Accents */
            --cyan:        #00d4ff;
            --cyan-dim:    #00d4ff22;
            --cyan-glow:   0 0 20px rgba(0,212,255,0.25);
            --green:       #00e57a;
            --green-dim:   #00e57a18;
            --red:         #ff4466;
            --red-dim:     #ff446618;
            --amber:       #ffa726;
            --amber-dim:   #ffa72618;
            --purple:      #a855f7;

            /* Text */
            --text:        #c6d8eb;
            --text-muted:  #4e6880;
            --text-bright: #e8f4ff;

            /* Fonts */
            --font-body:   'Inter', sans-serif;
            --font-mono:   'JetBrains Mono', monospace;
            --font-head:   'Rajdhani', sans-serif;

            /* Sidebar */
            --sidebar-w:   256px;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background-color: var(--bg);
            background-image:
                radial-gradient(ellipse 80% 50% at 20% -10%, rgba(0,212,255,0.04) 0%, transparent 70%),
                radial-gradient(ellipse 60% 40% at 80% 110%, rgba(0,229,122,0.03) 0%, transparent 70%);
            color: var(--text);
            font-family: var(--font-body);
            font-size: 14px;
            line-height: 1.6;
            overflow-x: hidden;
            min-height: 100vh;
        }

        /* ── Sidebar ── */
        .sidebar {
            width: var(--sidebar-w);
            height: 100vh;
            position: fixed;
            top: 0; left: 0;
            background: var(--surface);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            z-index: 200;
            overflow: hidden;
        }

        .sidebar::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--cyan), transparent);
            opacity: 0.5;
        }

        /* Brand */
        .sidebar-brand {
            padding: 22px 20px 18px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-icon {
            width: 38px; height: 38px;
            background: linear-gradient(135deg, var(--cyan), #0099cc);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 16px rgba(0,212,255,0.3);
            flex-shrink: 0;
        }

        .brand-icon i { color: #000; font-size: 16px; }

        .brand-name {
            font-family: var(--font-head);
            font-weight: 700;
            font-size: 20px;
            letter-spacing: 2px;
            color: var(--text-bright);
            line-height: 1.1;
        }

        .brand-name span { color: var(--cyan); }

        .brand-sub {
            font-family: var(--font-mono);
            font-size: 9px;
            letter-spacing: 2px;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        /* Live indicator */
        .live-badge {
            display: flex;
            align-items: center;
            gap: 6px;
            margin: 14px 20px;
            padding: 7px 12px;
            background: var(--green-dim);
            border: 1px solid rgba(0,229,122,0.2);
            border-radius: 6px;
            font-family: var(--font-mono);
            font-size: 10px;
            letter-spacing: 1.5px;
            color: var(--green);
        }

        .live-dot {
            width: 7px; height: 7px;
            background: var(--green);
            border-radius: 50%;
            animation: livePulse 1.4s ease-in-out infinite;
        }

        @keyframes livePulse {
            0%, 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(0,229,122,0.4); }
            50% { opacity: 0.7; box-shadow: 0 0 0 4px rgba(0,229,122,0); }
        }

        /* Nav */
        .sidebar-nav {
            flex: 1;
            overflow-y: auto;
            padding: 8px 12px;
        }

        .nav-section-label {
            font-family: var(--font-mono);
            font-size: 9px;
            letter-spacing: 2px;
            color: var(--text-muted);
            padding: 10px 8px 6px;
            text-transform: uppercase;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border-radius: 8px;
            margin-bottom: 2px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
            position: relative;
            cursor: pointer;
        }

        .nav-item i, .nav-item .nav-icon {
            width: 18px;
            text-align: center;
            font-size: 15px;
            flex-shrink: 0;
        }

        .nav-item:hover {
            background: var(--surface2);
            color: var(--text);
        }

        .nav-item.active {
            background: var(--cyan-dim);
            color: var(--cyan);
            border: 1px solid rgba(0,212,255,0.15);
        }

        .nav-item.active::before {
            content: '';
            position: absolute;
            left: 0; top: 8px; bottom: 8px;
            width: 3px;
            background: var(--cyan);
            border-radius: 0 3px 3px 0;
        }

        /* Sidebar footer */
        .sidebar-footer {
            border-top: 1px solid var(--border);
            padding: 14px 12px;
        }

        .sidebar-footer .nav-item {
            color: var(--red);
            margin-bottom: 0;
        }

        .sidebar-footer .nav-item:hover {
            background: var(--red-dim);
            color: var(--red);
        }

        /* Main content */
        .main-content {
            margin-left: var(--sidebar-w);
            min-height: 100vh;
        }

        /* Top header bar */
        .topbar {
            height: 56px;
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .topbar-title {
            font-family: var(--font-head);
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 1px;
            color: var(--text-bright);
            text-transform: uppercase;
        }

        .topbar-meta {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .topbar-time {
            font-family: var(--font-mono);
            font-size: 12px;
            color: var(--text-muted);
            letter-spacing: 1px;
        }

        .topbar-user {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text);
        }

        .user-avatar {
            width: 30px; height: 30px;
            background: linear-gradient(135deg, var(--cyan), var(--purple));
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 12px;
            font-weight: 700;
            color: #fff;
        }

        /* Page content wrapper */
        .page-body {
            padding: 24px;
        }

        /* ── Cards ── */
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
            color: var(--text);
        }

        .card-header {
            background: rgba(255,255,255,0.02);
            border-bottom: 1px solid var(--border);
            padding: 12px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-family: var(--font-head);
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--text);
        }

        .card-header .card-icon {
            width: 28px; height: 28px;
            border-radius: 7px;
            display: flex; align-items: center; justify-content: center;
            font-size: 12px;
            margin-right: 10px;
        }

        .card-body { padding: 18px; }

        /* ── Stat cards ── */
        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 18px;
            position: relative;
            overflow: hidden;
            transition: border-color 0.3s;
        }

        .stat-card:hover { border-color: var(--border2); }

        .stat-card::after {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            border-radius: 2px 2px 0 0;
        }

        .stat-card.cyan::after  { background: linear-gradient(90deg, var(--cyan), transparent); }
        .stat-card.green::after { background: linear-gradient(90deg, var(--green), transparent); }
        .stat-card.red::after   { background: linear-gradient(90deg, var(--red), transparent); }
        .stat-card.amber::after { background: linear-gradient(90deg, var(--amber), transparent); }

        .stat-value {
            font-family: var(--font-mono);
            font-size: 32px;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 4px;
        }

        .stat-label {
            font-family: var(--font-mono);
            font-size: 10px;
            letter-spacing: 1.5px;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        .stat-icon {
            position: absolute;
            top: 16px; right: 16px;
            font-size: 22px;
            opacity: 0.15;
        }

        /* ── Badges ── */
        .badge-status {
            font-family: var(--font-mono);
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 1.5px;
            padding: 3px 8px;
            border-radius: 4px;
            text-transform: uppercase;
        }

        .badge-live   { background: var(--green-dim); color: var(--green); border: 1px solid rgba(0,229,122,0.25); }
        .badge-alert  { background: var(--red-dim);   color: var(--red);   border: 1px solid rgba(255,68,102,0.25); }
        .badge-warn   { background: var(--amber-dim); color: var(--amber); border: 1px solid rgba(255,167,38,0.25); }
        .badge-info   { background: var(--cyan-dim);  color: var(--cyan);  border: 1px solid rgba(0,212,255,0.25); }

        /* ── Scrollbar ── */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--border2); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--text-muted); }

        /* ── Global Alert Banner ── */
        #alertBanner {
            position: fixed;
            top: -120px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 9999;
            width: 90%;
            max-width: 520px;
            transition: top 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        #alertBanner.show { top: 16px; }

        .alert-banner-inner {
            background: var(--surface2);
            border: 1px solid var(--red);
            border-radius: 12px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: 0 8px 40px rgba(0,0,0,0.6), 0 0 30px rgba(255,68,102,0.2);
        }

        .alert-icon-wrap {
            width: 40px; height: 40px;
            background: var(--red-dim);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }

        /* ── Forms ── */
        .form-control, .form-select {
            background: var(--surface2);
            border: 1px solid var(--border2);
            color: var(--text);
            border-radius: 8px;
            font-size: 14px;
            padding: 9px 13px;
        }

        .form-control:focus, .form-select:focus {
            background: var(--surface3);
            border-color: var(--cyan);
            color: var(--text-bright);
            box-shadow: 0 0 0 3px rgba(0,212,255,0.1);
        }

        .form-control::placeholder { color: var(--text-muted); }

        /* ── Buttons ── */
        .btn-primary-custom {
            background: var(--cyan);
            color: #000;
            font-weight: 700;
            border: none;
            border-radius: 8px;
            padding: 9px 20px;
            font-family: var(--font-head);
            letter-spacing: 0.5px;
            transition: all 0.2s ease;
            font-size: 14px;
        }

        .btn-primary-custom:hover {
            background: #00bfea;
            transform: translateY(-1px);
            box-shadow: var(--cyan-glow);
        }

        .btn-ghost {
            background: transparent;
            border: 1px solid var(--border2);
            color: var(--text-muted);
            border-radius: 8px;
            padding: 7px 16px;
            font-size: 13px;
            transition: all 0.2s ease;
        }

        .btn-ghost:hover {
            background: var(--surface2);
            border-color: var(--border);
            color: var(--text);
        }

        .btn-danger-custom {
            background: transparent;
            border: 1px solid rgba(255,68,102,0.3);
            color: var(--red);
            border-radius: 8px;
            padding: 7px 16px;
            font-size: 13px;
            transition: all 0.2s ease;
        }

        .btn-danger-custom:hover {
            background: var(--red-dim);
            border-color: var(--red);
        }

        /* ── Colors ── */
        .text-cyan   { color: var(--cyan)  !important; }
        .text-green  { color: var(--green) !important; }
        .text-red    { color: var(--red)   !important; }
        .text-amber  { color: var(--amber) !important; }
        .text-muted-custom { color: var(--text-muted) !important; }
        .text-mono   { font-family: var(--font-mono) !important; }
        .text-head   { font-family: var(--font-head) !important; }

        /* ── Responsive ── */
        @media (max-width: 991px) {
            :root { --sidebar-w: 64px; }
            .sidebar-brand { padding: 16px; justify-content: center; }
            .brand-name, .brand-sub, .live-badge span,
            .nav-section-label, .nav-item span { display: none; }
            .live-badge { justify-content: center; margin: 10px; padding: 8px; }
            .nav-item { justify-content: center; padding: 12px; gap: 0; }
            .sidebar-footer .nav-item { justify-content: center; }
            .nav-item i, .nav-item .nav-icon { width: auto; font-size: 18px; }
        }
    </style>
</head>
<body>

    <!-- Alert Banner -->
    <div id="alertBanner">
        <div class="alert-banner-inner">
            <div class="alert-icon-wrap">
                <i class="fas fa-triangle-exclamation" style="color:var(--red); font-size:18px;"></i>
            </div>
            <div>
                <div style="font-family:var(--font-head);font-size:13px;font-weight:700;letter-spacing:1px;color:var(--red);text-transform:uppercase;">Surveillance Alert</div>
                <div id="alertText" style="font-size:13px;color:var(--text);margin-top:2px;">Object detected</div>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon">
                <i class="fas fa-shield-halved"></i>
            </div>
            <div>
                <div class="brand-name"><span>METRO</span>NET</div>
                <div class="brand-sub">AI Surveillance</div>
            </div>
        </div>

        <div class="live-badge">
            <div class="live-dot"></div>
            <span>SYSTEM LIVE</span>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section-label">Main</div>

            <a href="<?php echo e(route('dashboard')); ?>"
               class="nav-item <?php echo e(request()->routeIs('dashboard') ? 'active' : ''); ?>">
                <i class="fas fa-chart-pie"></i>
                <span>Dashboard</span>
            </a>

            <a href="<?php echo e(route('gate.monitor')); ?>"
               class="nav-item <?php echo e(request()->routeIs('gate.*') ? 'active' : ''); ?>">
                <i class="fas fa-video"></i>
                <span>Gate Monitor</span>
            </a>

            <div class="nav-section-label" style="margin-top:8px;">Config</div>

            <a href="<?php echo e(route('cameras.index')); ?>"
               class="nav-item <?php echo e(request()->routeIs('cameras.*') ? 'active' : ''); ?>">
                <i class="fas fa-camera"></i>
                <span>Camera Config</span>
            </a>

            <a href="<?php echo e(route('training.index')); ?>"
               class="nav-item <?php echo e(request()->routeIs('training.*') ? 'active' : ''); ?>">
                <i class="fas fa-brain"></i>
                <span>Object Training</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <form action="<?php echo e(route('logout')); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <button type="submit" class="nav-item w-100 border-0 bg-transparent">
                    <i class="fas fa-arrow-right-from-bracket"></i>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <div class="topbar-title">
                <?php echo e(request()->routeIs('dashboard') ? 'Dashboard' :
                   (request()->routeIs('gate.*') ? 'Gate Monitor' :
                   (request()->routeIs('cameras.*') ? 'Camera Config' :
                   (request()->routeIs('training.*') ? 'Object Training' : 'MetroNet')))); ?>

            </div>
            <div class="topbar-meta">
                <div class="topbar-time" id="topbarClock">--:--:--</div>
                <div class="topbar-user">
                    <div class="user-avatar">
                        <?php echo e(strtoupper(substr(auth()->user()->name ?? 'A', 0, 1))); ?>

                    </div>
                    <span><?php echo e(auth()->user()->name ?? 'Admin'); ?></span>
                </div>
            </div>
        </div>

        <!-- Page body -->
        <div class="page-body">
            <?php echo $__env->yieldContent('content'); ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Live clock
        function updateClock() {
            const el = document.getElementById('topbarClock');
            if (el) {
                el.textContent = new Date().toLocaleTimeString('en-GB', {hour12: false});
            }
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Global Alert
        function showAlert(message) {
            const banner = document.getElementById('alertBanner');
            const alertText = document.getElementById('alertText');
            if (alertText) alertText.innerText = message;
            banner.classList.add('show');

            // Beep sound
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                if (ctx.state === 'suspended') ctx.resume();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sawtooth';
                osc.frequency.setValueAtTime(660, ctx.currentTime);
                osc.frequency.exponentialRampToValueAtTime(880, ctx.currentTime + 0.08);
                gain.gain.setValueAtTime(0.08, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.25);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start();
                osc.stop(ctx.currentTime + 0.25);
            } catch(e) {}

            setTimeout(() => banner.classList.remove('show'), 6000);
        }
    </script>

    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH C:\Users\Mahadi Hassan Arif\Desktop\Camera Tracking\sentinel\resources\views/layouts/app.blade.php ENDPATH**/ ?>