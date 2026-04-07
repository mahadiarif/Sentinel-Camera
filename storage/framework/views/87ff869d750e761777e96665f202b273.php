<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — METRONET</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&family=Rajdhani:wght@500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --bg:       #060b12;
            --surface:  #0d1621;
            --surface2: #111d2c;
            --border:   #1e3247;
            --border2:  #243d55;
            --cyan:     #00d4ff;
            --green:    #00e57a;
            --red:      #ff4466;
            --text:     #c6d8eb;
            --text-dim: #4e6880;
            --text-bright: #e8f4ff;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background-color: var(--bg);
            background-image:
                radial-gradient(ellipse 70% 60% at 15% 5%,  rgba(0,212,255,0.06) 0%, transparent 60%),
                radial-gradient(ellipse 50% 40% at 85% 95%, rgba(0,229,122,0.04) 0%, transparent 60%),
                radial-gradient(ellipse 40% 40% at 50% 50%, rgba(168,85,247,0.03) 0%, transparent 70%);
            color: var(--text);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        /* Grid background */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(30,50,71,0.3) 1px, transparent 1px),
                linear-gradient(90deg, rgba(30,50,71,0.3) 1px, transparent 1px);
            background-size: 40px 40px;
            mask-image: radial-gradient(ellipse 80% 80% at 50% 50%, black 30%, transparent 100%);
            pointer-events: none;
        }

        .login-wrap {
            width: 100%;
            max-width: 420px;
            position: relative;
        }

        /* Glow effect behind card */
        .login-wrap::before {
            content: '';
            position: absolute;
            inset: -40px;
            background: radial-gradient(ellipse 70% 50% at 50% 50%, rgba(0,212,255,0.08), transparent);
            pointer-events: none;
            z-index: 0;
        }

        .login-card {
            position: relative;
            z-index: 1;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            overflow: hidden;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent 5%, var(--cyan) 50%, transparent 95%);
            opacity: 0.6;
        }

        /* Header */
        .login-header {
            padding: 32px 32px 24px;
            text-align: center;
            border-bottom: 1px solid var(--border);
        }

        .logo-icon {
            width: 56px; height: 56px;
            background: linear-gradient(135deg, #00d4ff 0%, #0077aa 100%);
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            box-shadow: 0 8px 32px rgba(0,212,255,0.25);
        }

        .logo-icon i {
            font-size: 24px;
            color: #000;
        }

        .logo-name {
            font-family: 'Rajdhani', sans-serif;
            font-weight: 700;
            font-size: 26px;
            letter-spacing: 3px;
            color: var(--text-bright);
        }

        .logo-name span { color: var(--cyan); }

        .logo-sub {
            font-family: 'JetBrains Mono', monospace;
            font-size: 10px;
            letter-spacing: 2.5px;
            color: var(--text-dim);
            text-transform: uppercase;
            margin-top: 4px;
        }

        /* Body */
        .login-body {
            padding: 28px 32px 32px;
        }

        .field-label {
            font-family: 'JetBrains Mono', monospace;
            font-size: 10px;
            letter-spacing: 1.5px;
            color: var(--text-dim);
            text-transform: uppercase;
            margin-bottom: 7px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .field-label i { font-size: 10px; }

        .field-group {
            position: relative;
            margin-bottom: 18px;
        }

        .form-input {
            width: 100%;
            background: var(--surface2);
            border: 1px solid var(--border2);
            color: var(--text-bright);
            border-radius: 10px;
            padding: 11px 14px 11px 40px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            outline: none;
            transition: all 0.2s ease;
        }

        .form-input:focus {
            border-color: var(--cyan);
            background: rgba(0,212,255,0.03);
            box-shadow: 0 0 0 3px rgba(0,212,255,0.08);
        }

        .form-input::placeholder {
            color: var(--text-dim);
            font-size: 13px;
        }

        .field-icon {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-dim);
            font-size: 13px;
            pointer-events: none;
        }

        .error-text {
            font-size: 12px;
            color: var(--red);
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Remember */
        .remember-row {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 22px;
        }

        .remember-row input[type="checkbox"] {
            width: 15px; height: 15px;
            accent-color: var(--cyan);
            cursor: pointer;
        }

        .remember-row label {
            font-size: 13px;
            color: var(--text-dim);
            cursor: pointer;
        }

        /* Submit button */
        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, var(--cyan) 0%, #0099cc 100%);
            color: #000;
            font-family: 'Rajdhani', sans-serif;
            font-weight: 700;
            font-size: 15px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            border: none;
            border-radius: 10px;
            padding: 13px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,212,255,0.3);
        }

        .btn-login:active { transform: translateY(0); }

        /* Footer */
        .login-footer {
            padding: 14px 32px 18px;
            text-align: center;
            border-top: 1px solid var(--border);
        }

        .footer-badges {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
        }

        .footer-badge {
            display: flex;
            align-items: center;
            gap: 5px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 9px;
            letter-spacing: 1px;
            color: var(--text-dim);
            text-transform: uppercase;
        }

        .footer-badge i { font-size: 9px; color: var(--green); }

        /* Scan line animation */
        @keyframes scanline {
            0%   { top: -10%; }
            100% { top: 110%; }
        }

        .card-scanline {
            position: absolute;
            left: 0; right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(0,212,255,0.3), transparent);
            pointer-events: none;
            animation: scanline 4s linear infinite;
            opacity: 0;
        }

        .login-card:hover .card-scanline { opacity: 1; }
    </style>
</head>
<body>

<div class="login-wrap">
    <div class="login-card">
        <div class="card-scanline"></div>

        <!-- Header -->
        <div class="login-header">
            <div class="logo-icon">
                <i class="fas fa-shield-halved"></i>
            </div>
            <div class="logo-name"><span>METRO</span>NET</div>
            <div class="logo-sub">Secure Access Terminal · AI Surveillance</div>
        </div>

        <!-- Form -->
        <div class="login-body">
            <form method="POST" action="<?php echo e(route('login')); ?>">
                <?php echo csrf_field(); ?>

                <!-- Email -->
                <div class="field-group">
                    <div class="field-label">
                        <i class="fas fa-at"></i> Operator Credential
                    </div>
                    <i class="fas fa-at field-icon"></i>
                    <input
                        type="email"
                        name="email"
                        class="form-input"
                        placeholder="admin@metronet.ai"
                        value="<?php echo e(old('email')); ?>"
                        required
                        autofocus
                    >
                    <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="error-text">
                            <i class="fas fa-circle-exclamation"></i> <?php echo e($message); ?>

                        </div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <!-- Password -->
                <div class="field-group">
                    <div class="field-label">
                        <i class="fas fa-key"></i> Security Passphrase
                    </div>
                    <i class="fas fa-lock field-icon"></i>
                    <input
                        type="password"
                        name="password"
                        class="form-input"
                        placeholder="••••••••••••"
                        required
                    >
                    <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="error-text">
                            <i class="fas fa-circle-exclamation"></i> <?php echo e($message); ?>

                        </div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <!-- Remember -->
                <div class="remember-row">
                    <input type="checkbox" name="remember" id="remember">
                    <label for="remember">Keep session active</label>
                </div>

                <!-- Submit -->
                <button type="submit" class="btn-login">
                    <i class="fas fa-arrow-right-to-bracket"></i>
                    Initialize System Login
                </button>
            </form>
        </div>

        <!-- Footer -->
        <div class="login-footer">
            <div class="footer-badges">
                <div class="footer-badge">
                    <i class="fas fa-circle-check"></i>
                    <span>Encrypted</span>
                </div>
                <div class="footer-badge" style="color: rgba(78,104,128,0.5);">·</div>
                <div class="footer-badge">
                    <i class="fas fa-circle-check"></i>
                    <span>v1.0-stable</span>
                </div>
                <div class="footer-badge" style="color: rgba(78,104,128,0.5);">·</div>
                <div class="footer-badge">
                    <i class="fas fa-circle-check"></i>
                    <span>MetroNet AI</span>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
<?php /**PATH C:\Users\Mahadi Hassan Arif\Desktop\Camera Tracking\sentinel\resources\views/auth/login.blade.php ENDPATH**/ ?>