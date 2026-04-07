<div class="camera-form-shell">
    <div class="camera-form-section">
        <div class="camera-form-section__header">
            <div>
                <h6 class="camera-form-section__title">Camera Identity</h6>
                <p class="camera-form-section__meta">Name the camera clearly so operators can identify it fast.</p>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-lg-4">
                <label class="camera-label">Display Name</label>
                <input type="text" name="name" class="form-control camera-input" value="<?php echo e($nameValue); ?>" placeholder="Main Gate Entrance" required>
            </div>
            <div class="col-lg-4">
                <label class="camera-label">Location</label>
                <input type="text" name="location" class="form-control camera-input" value="<?php echo e($locationValue); ?>" placeholder="Gate 1 / Lobby / Parking" required>
            </div>
            <div class="col-lg-2">
                <label class="camera-label">Camera Type</label>
                <select name="type" class="form-select camera-input" required>
                    <option value="rtsp" <?php if($typeValue === 'rtsp'): echo 'selected'; endif; ?>>Network / IP</option>
                    <option value="usb" <?php if($typeValue === 'usb'): echo 'selected'; endif; ?>>USB</option>
                </select>
            </div>
            <div class="col-lg-2">
                <label class="camera-label">Status</label>
                <select name="status" class="form-select camera-input" required>
                    <option value="active" <?php if($statusValue === 'active'): echo 'selected'; endif; ?>>Active</option>
                    <option value="inactive" <?php if($statusValue === 'inactive'): echo 'selected'; endif; ?>>Inactive</option>
                </select>
            </div>
        </div>
    </div>

    <div class="camera-form-section" data-role="network-only">
        <div class="camera-form-section__header">
            <div>
                <h6 class="camera-form-section__title">Connection Setup</h6>
                <p class="camera-form-section__meta">Choose a standard vendor preset or enter the full stream URL yourself.</p>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-lg-3">
                <label class="camera-label">Setup Mode</label>
                <select name="config_mode" class="form-select camera-input">
                    <option value="preset" <?php if(($settings['config_mode'] ?? 'preset') === 'preset'): echo 'selected'; endif; ?>>Vendor Preset</option>
                    <option value="direct" <?php if(($settings['config_mode'] ?? 'preset') === 'direct'): echo 'selected'; endif; ?>>Direct URL</option>
                </select>
            </div>
            <div class="col-lg-5" data-role="vendor-only">
                <label class="camera-label">Vendor Profile</label>
                <select name="vendor" class="form-select camera-input">
                    <?php $__currentLoopData = $vendorProfiles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vendorKey => $vendorProfile): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($vendorKey); ?>" <?php if(($settings['vendor'] ?? 'generic') === $vendorKey): echo 'selected'; endif; ?>><?php echo e($vendorProfile['label']); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-lg-4" data-role="preset-fields">
                <label class="camera-label">Protocol</label>
                <select name="network_protocol" class="form-select camera-input">
                    <option value="rtsp" <?php if(($settings['network_protocol'] ?? 'rtsp') === 'rtsp'): echo 'selected'; endif; ?>>RTSP</option>
                    <option value="http" <?php if(($settings['network_protocol'] ?? 'rtsp') === 'http'): echo 'selected'; endif; ?>>HTTP</option>
                    <option value="https" <?php if(($settings['network_protocol'] ?? 'rtsp') === 'https'): echo 'selected'; endif; ?>>HTTPS</option>
                </select>
            </div>
        </div>
        <div class="camera-inline-note" data-role="preset-fields">
            <i class="fas fa-circle-info me-2"></i>
            <span data-role="vendor-help"></span>
        </div>
    </div>

    <div class="camera-form-section" data-role="preset-fields">
        <div class="camera-form-section__header">
            <div>
                <h6 class="camera-form-section__title">Network Details</h6>
                <p class="camera-form-section__meta">These fields are used to build the final stream source automatically.</p>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-lg-4">
                <label class="camera-label">Host / IP Address</label>
                <input type="text" name="host" class="form-control camera-input" value="<?php echo e($settings['host'] ?? ''); ?>" placeholder="192.168.1.100">
            </div>
            <div class="col-lg-2">
                <label class="camera-label">Port</label>
                <input type="number" name="port" class="form-control camera-input" value="<?php echo e($settings['port'] ?? 554); ?>" min="1" max="65535">
            </div>
            <div class="col-lg-2">
                <label class="camera-label">Channel</label>
                <input type="number" name="channel" class="form-control camera-input" value="<?php echo e($settings['channel'] ?? 1); ?>" min="1" max="64">
            </div>
            <div class="col-lg-4">
                <label class="camera-label">Stream Profile</label>
                <select name="stream_type" class="form-select camera-input">
                    <option value="main" <?php if(($settings['stream_type'] ?? 'main') === 'main'): echo 'selected'; endif; ?>>Main Stream</option>
                    <option value="sub" <?php if(($settings['stream_type'] ?? 'main') === 'sub'): echo 'selected'; endif; ?>>Sub Stream</option>
                </select>
            </div>
            <div class="col-lg-3">
                <label class="camera-label">Username</label>
                <input type="text" name="username" class="form-control camera-input" value="<?php echo e($settings['username'] ?? ''); ?>" placeholder="admin">
            </div>
            <div class="col-lg-3">
                <label class="camera-label">Password</label>
                <input type="text" name="password" class="form-control camera-input" value="<?php echo e($settings['password'] ?? ''); ?>" placeholder="camera password">
            </div>
            <div class="col-lg-6">
                <label class="camera-label">Custom Path Override</label>
                <input type="text" name="path" class="form-control camera-input font-mono" value="<?php echo e($settings['path'] ?? ''); ?>" placeholder="/Streaming/Channels/101 or /stream1">
                <div class="camera-field-help">Leave blank to use the vendor default path.</div>
            </div>
        </div>
    </div>

    <div class="camera-form-section" data-role="direct-source">
        <div class="camera-form-section__header">
            <div>
                <h6 class="camera-form-section__title">Source Input</h6>
                <p class="camera-form-section__meta">Use this for full manual URLs or USB index input.</p>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-12">
                <label class="camera-label">Source</label>
                <input type="text" name="source" class="form-control camera-input font-mono" value="<?php echo e($sourceValue); ?>" placeholder="rtsp://... or http://... or USB index like 0">
                <div class="camera-field-help" data-role="network-only">
                    Direct URL mode keeps full manual control. Base HTTP URLs like <span class="font-mono">http://192.168.137.21:8080/</span> are normalized automatically.
                </div>
                <div class="camera-field-help" data-role="usb-only">
                    USB cameras must use a numeric device index such as <span class="font-mono">0</span> or <span class="font-mono">1</span>.
                </div>
            </div>
        </div>
    </div>

    <?php if($camera): ?>
    <div class="camera-form-section camera-form-section--compact">
        <div class="row g-3 align-items-end">
            <div class="col-lg-4">
                <label class="camera-label">Frame Target</label>
                <div class="camera-static-value font-mono">cam_<?php echo e($camera->id); ?>.jpg</div>
            </div>
            <div class="col-lg-8">
                <div class="camera-chip-row">
                    <span class="camera-chip">ID #<?php echo e($camera->id); ?></span>
                    <span class="camera-chip"><?php echo e(strtoupper($camera->status)); ?></span>
                    <span class="camera-chip"><?php echo e(strtoupper($camera->type)); ?></span>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if(!$camera): ?>
    <div class="camera-form-actions">
        <button type="submit" class="btn btn-accent camera-primary-btn">
            <i class="fas fa-plus me-2"></i> <?php echo e($submitLabel); ?>

        </button>
    </div>
    <?php endif; ?>
</div>
<?php /**PATH C:\Users\Mahadi Hassan Arif\Desktop\Camera Tracking\sentinel\resources\views\cameras\partials\form-fields.blade.php ENDPATH**/ ?>