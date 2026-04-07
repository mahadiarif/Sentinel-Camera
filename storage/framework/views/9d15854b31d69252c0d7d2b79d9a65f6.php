<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — METRONET</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:ital,wght@0,400;0,700;1,400;1,700&family=Syne:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #050a0e;
            --surface: #0b1318;
            --accent: #00e5ff;
            --text: #c8dde8;
            --border: #1a2e3a;
        }
        body {
            background-color: var(--bg);
            color: var(--text);
            font-family: 'Space Mono', monospace;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: var(--surface);
            border: 1px solid var(--border);
            padding: 2.5rem;
            border-radius: 20px;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 0 50px rgba(0, 229, 255, 0.05);
        }
        .form-control {
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--border);
            color: white;
            padding: 0.8rem;
            border-radius: 8px;
        }
        .form-control:focus {
            background: rgba(255,255,255,0.05);
            border-color: var(--accent);
            box-shadow: 0 0 10px rgba(0, 229, 255, 0.2);
            color: white;
        }
        .btn-accent {
            background: var(--accent);
            color: black;
            font-weight: 800;
            text-transform: uppercase;
            padding: 1rem;
            border: none;
            width: 100%;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .btn-accent:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 229, 255, 0.3);
        }
        .brand-text {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            letter-spacing: 4px;
            text-align: center;
            margin-bottom: 2rem;
            font-size: 1.5rem;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="brand-text">
            <span style="color: var(--accent)">METRO</span>NET
        </div>
        <h5 class="text-center mb-4 opacity-75">SECURE ACCESS TERMINAL</h5>

        <form method="POST" action="<?php echo e(route('login')); ?>">
            <?php echo csrf_field(); ?>
            
            <div class="mb-3">
                <label class="form-label small text-dim uppercase">Credential / Email</label>
                <input type="email" name="email" class="form-control" placeholder="admin@metronet.ai" value="<?php echo e(old('email')); ?>" required autofocus>
                <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small class="text-danger mt-1"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="mb-4">
                <label class="form-label small text-dim uppercase">Security Code / Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small class="text-danger mt-1"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="mb-4 form-check">
                <input type="checkbox" name="remember" class="form-check-input" id="remember">
                <label class="form-check-label small" for="remember">Keep session active</label>
            </div>

            <button type="submit" class="btn btn-accent">Initialize System Login</button>
        </form>

        <div class="mt-4 text-center">
            <small class="text-dim opacity-50">V.1.0-STABLE | SECURE BROADCASTING</small>
        </div>
    </div>

</body>
</html>
<?php /**PATH C:\Users\Mahadi Hassan Arif\Desktop\Camera Tracking\sentinel\resources\views\auth\login.blade.php ENDPATH**/ ?>