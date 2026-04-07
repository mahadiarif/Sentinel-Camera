<?php $__env->startSection('content'); ?>
<?php
    $vendorOptions = collect($vendorProfiles)->map(fn ($profile, $key) => [
        'key' => $key,
        'label' => $profile['label'],
        'help' => $profile['help'] ?? '',
        'protocols' => $profile['protocols'] ?? ['rtsp'],
        'default_protocol' => $profile['default_protocol'] ?? 'rtsp',
        'default_port' => $profile['default_port'] ?? 554,
    ])->values();
?>

<style>
    .camera-page {
        max-width: 1440px;
        margin: 0 auto;
    }

    .camera-hero {
        border: 1px solid rgba(255,255,255,0.06);
        background:
            radial-gradient(circle at top right, rgba(0,229,255,0.16), transparent 28%),
            linear-gradient(135deg, rgba(17,28,35,0.96), rgba(11,19,24,0.98));
        border-radius: 22px;
        padding: 2rem;
        margin-bottom: 1.75rem;
        box-shadow: 0 18px 48px rgba(0,0,0,0.25);
    }

    .camera-hero__eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 0.55rem;
        padding: 0.45rem 0.75rem;
        border-radius: 999px;
        border: 1px solid rgba(0,229,255,0.18);
        background: rgba(0,229,255,0.08);
        color: #95f6ff;
        font-size: 0.74rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-weight: 700;
    }

    .camera-hero h1 {
        margin-top: 1rem;
        margin-bottom: 0.55rem;
        font-size: clamp(2rem, 4vw, 3rem);
        letter-spacing: -0.04em;
    }

    .camera-hero p {
        max-width: 720px;
        color: rgba(200,221,232,0.74);
        margin-bottom: 0;
    }

    .camera-hero__status {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 0.75rem;
    }

    .camera-summary-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 1rem;
        margin-bottom: 1.75rem;
    }

    .camera-summary-card {
        padding: 1.35rem 1.4rem;
        border-radius: 18px;
        border: 1px solid rgba(255,255,255,0.05);
        background: linear-gradient(180deg, rgba(17,28,35,0.92), rgba(11,19,24,0.98));
        box-shadow: 0 14px 34px rgba(0,0,0,0.18);
    }

    .camera-summary-card small {
        display: block;
        color: #7d98aa;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-size: 0.72rem;
        margin-bottom: 0.65rem;
    }

    .camera-summary-card strong {
        display: block;
        font-size: 2rem;
        line-height: 1;
        color: #f3fbff;
    }

    .camera-summary-card span {
        display: block;
        margin-top: 0.55rem;
        color: #8da5b4;
        font-size: 0.92rem;
    }

    .camera-panel {
        border: 1px solid rgba(255,255,255,0.06);
        border-radius: 22px;
        background: linear-gradient(180deg, rgba(11,19,24,0.98), rgba(11,19,24,0.92));
        overflow: hidden;
        box-shadow: 0 18px 40px rgba(0,0,0,0.2);
    }

    .camera-panel + .camera-panel {
        margin-top: 1.5rem;
    }

    .camera-panel__header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        padding: 1.4rem 1.5rem;
        border-bottom: 1px solid rgba(255,255,255,0.06);
        background: rgba(255,255,255,0.02);
    }

    .camera-panel__header h5 {
        margin: 0;
    }

    .camera-panel__header p {
        margin: 0.35rem 0 0;
        color: #87a0b0;
    }

    .camera-panel__body {
        padding: 1.5rem;
    }

    .camera-alert {
        border: 1px solid transparent;
        border-radius: 16px;
        padding: 1rem 1.1rem;
        margin-bottom: 1.5rem;
    }

    .camera-alert--success {
        background: rgba(184,255,87,0.1);
        border-color: rgba(184,255,87,0.2);
        color: #ddffb0;
    }

    .camera-alert--error {
        background: rgba(255,61,107,0.1);
        border-color: rgba(255,61,107,0.18);
        color: #ffd3de;
    }

    .camera-alert ul {
        margin: 0.65rem 0 0;
        padding-left: 1.15rem;
    }

    .camera-form-shell {
        display: grid;
        gap: 1rem;
    }

    .camera-form-section {
        border: 1px solid rgba(255,255,255,0.06);
        border-radius: 18px;
        background: rgba(255,255,255,0.02);
        padding: 1.2rem;
    }

    .camera-form-section--compact {
        padding-top: 1rem;
        padding-bottom: 1rem;
    }

    .camera-form-section__header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .camera-form-section__title {
        margin: 0;
        font-size: 0.95rem;
        letter-spacing: 0.04em;
    }

    .camera-form-section__meta {
        margin: 0.3rem 0 0;
        color: #87a0b0;
        font-size: 0.92rem;
    }

    .camera-label {
        display: block;
        margin-bottom: 0.48rem;
        color: #89a2b2;
        font-size: 0.75rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        font-family: 'Space Mono', monospace;
    }

    .camera-input,
    .camera-static-value {
        min-height: 48px;
        border-radius: 12px;
        border: 1px solid rgba(255,255,255,0.08);
        background: #101b22;
        color: #f1fbff;
        box-shadow: none;
    }

    .camera-input:focus {
        border-color: rgba(0,229,255,0.4);
        box-shadow: 0 0 0 4px rgba(0,229,255,0.08);
        background: #13212a;
        color: #fff;
    }

    .camera-static-value {
        display: flex;
        align-items: center;
        padding: 0 0.9rem;
        color: #9bb1be;
    }

    .camera-field-help {
        margin-top: 0.5rem;
        color: #7f99aa;
        font-size: 0.88rem;
    }

    .camera-inline-note {
        display: flex;
        align-items: flex-start;
        gap: 0.65rem;
        margin-top: 1rem;
        padding: 0.85rem 1rem;
        border-radius: 14px;
        background: rgba(0,229,255,0.08);
        color: #bdebf0;
        font-size: 0.92rem;
    }

    .camera-form-actions {
        display: flex;
        justify-content: flex-end;
        padding-top: 0.35rem;
    }

    .camera-primary-btn {
        min-height: 48px;
        padding: 0.8rem 1.3rem;
        border-radius: 12px;
        border: 0;
        font-weight: 700;
    }

    .camera-card-list {
        display: grid;
        gap: 1.25rem;
    }

    .camera-record {
        border: 1px solid rgba(255,255,255,0.06);
        border-radius: 20px;
        background: linear-gradient(180deg, rgba(14,22,29,0.98), rgba(11,19,24,0.96));
        overflow: hidden;
    }

    .camera-record__header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        padding: 1.3rem 1.4rem;
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }

    .camera-record__title {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.6rem;
        margin-bottom: 0.35rem;
    }

    .camera-record__meta {
        color: #88a1b1;
        font-size: 0.92rem;
    }

    .camera-record__body {
        padding: 1.35rem 1.4rem 1.1rem;
    }

    .camera-record__footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        padding: 1rem 1.4rem 1.3rem;
        border-top: 1px solid rgba(255,255,255,0.05);
    }

    .camera-record__footer p {
        margin: 0;
        color: #88a1b1;
        max-width: 680px;
    }

    .camera-chip-row {
        display: flex;
        flex-wrap: wrap;
        gap: 0.6rem;
    }

    .camera-chip {
        display: inline-flex;
        align-items: center;
        min-height: 34px;
        padding: 0.35rem 0.8rem;
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 999px;
        background: rgba(255,255,255,0.03);
        color: #a5bcc9;
        font-size: 0.79rem;
        font-weight: 700;
        letter-spacing: 0.06em;
    }

    .camera-empty {
        padding: 4rem 1.5rem;
        text-align: center;
        color: #7e98aa;
    }

    @media (max-width: 1199px) {
        .camera-summary-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 767px) {
        .camera-hero,
        .camera-panel__body,
        .camera-panel__header,
        .camera-record__header,
        .camera-record__body,
        .camera-record__footer {
            padding-left: 1rem;
            padding-right: 1rem;
        }

        .camera-summary-grid {
            grid-template-columns: 1fr;
        }

        .camera-hero__status,
        .camera-record__footer,
        .camera-panel__header,
        .camera-record__header {
            flex-direction: column;
            align-items: stretch;
        }

        .camera-form-actions {
            justify-content: stretch;
        }

        .camera-primary-btn {
            width: 100%;
        }
    }
</style>

<div class="camera-page">
    <section class="camera-hero">
        <div class="row g-4 align-items-end">
            <div class="col-xl-8">
                <span class="camera-hero__eyebrow">
                    <i class="fas fa-sliders"></i>
                    Camera Operations
                </span>
                <h1 class="text-white">Standardized Camera Configuration</h1>
                <p>Configure USB, RTSP, and vendor-specific cameras from a single screen with predictable settings, cleaner forms, and clearer operator guidance.</p>
            </div>
            <div class="col-xl-4">
                <div class="camera-hero__status">
                    <span class="badge-surv bg-accent px-3 py-2 text-dark">
                        <i class="fas fa-rotate me-2"></i> Auto Sync Enabled
                    </span>
                    <span class="badge-surv bg-accent3 px-3 py-2 text-dark">
                        <i class="fas fa-shield-halved me-2"></i> Centralized Setup
                    </span>
                </div>
            </div>
        </div>
    </section>

    <?php if(session('status')): ?>
    <div class="camera-alert camera-alert--success">
        <strong>Saved successfully.</strong>
        <div><?php echo e(session('status')); ?></div>
    </div>
    <?php endif; ?>

    <?php if($errors->any()): ?>
    <div class="camera-alert camera-alert--error">
        <strong>Configuration needs attention.</strong>
        <ul>
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <li><?php echo e($error); ?></li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
    </div>
    <?php endif; ?>

    <section class="camera-summary-grid">
        <article class="camera-summary-card">
            <small>Total Cameras</small>
            <strong><?php echo e($stats['total']); ?></strong>
            <span>All saved devices in this workspace.</span>
        </article>
        <article class="camera-summary-card">
            <small>Active Cameras</small>
            <strong style="color: var(--accent)"><?php echo e($stats['active']); ?></strong>
            <span>Currently included in worker sync.</span>
        </article>
        <article class="camera-summary-card">
            <small>Network Feeds</small>
            <strong style="color: var(--accent3)"><?php echo e($stats['network']); ?></strong>
            <span>RTSP, HTTP, and IP camera sources.</span>
        </article>
        <article class="camera-summary-card">
            <small>USB Cameras</small>
            <strong style="color: var(--warn)"><?php echo e($stats['usb']); ?></strong>
            <span>Local devices using numeric indexes.</span>
        </article>
    </section>

    <section class="camera-panel">
        <div class="camera-panel__header">
            <div>
                <h5>Add Camera</h5>
                <p>Create a new camera entry with either direct source input or a vendor preset.</p>
            </div>
        </div>
        <div class="camera-panel__body">
            <form method="POST" action="<?php echo e(route('cameras.store')); ?>" class="camera-config-form">
                <?php echo csrf_field(); ?>
                <?php echo $__env->make('cameras.partials.form-fields', [
                    'camera' => null,
                    'settings' => [
                        'config_mode' => old('config_mode', 'preset'),
                        'vendor' => old('vendor', 'generic'),
                        'network_protocol' => old('network_protocol', 'rtsp'),
                        'host' => old('host'),
                        'port' => old('port', 554),
                        'username' => old('username'),
                        'password' => old('password'),
                        'path' => old('path'),
                        'channel' => old('channel', 1),
                        'stream_type' => old('stream_type', 'main'),
                    ],
                    'sourceValue' => old('source'),
                    'nameValue' => old('name'),
                    'locationValue' => old('location'),
                    'typeValue' => old('type', 'rtsp'),
                    'statusValue' => old('status', 'active'),
                    'submitLabel' => 'Add Camera',
                ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </form>
        </div>
    </section>

    <section class="camera-panel">
        <div class="camera-panel__header">
            <div>
                <h5>Configured Cameras</h5>
                <p>Review and update existing cameras without leaving this screen.</p>
            </div>
            <div class="camera-chip-row">
                <span class="camera-chip"><?php echo e($stats['total']); ?> Total</span>
                <span class="camera-chip"><?php echo e($stats['active']); ?> Active</span>
            </div>
        </div>
        <div class="camera-panel__body">
            <?php if($cameras->isEmpty()): ?>
            <div class="camera-empty">
                <i class="fas fa-video-slash fa-2x mb-3 d-block opacity-50"></i>
                No cameras configured yet.
            </div>
            <?php else: ?>
            <div class="camera-card-list">
                <?php $__currentLoopData = $cameras; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $camera): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <article class="camera-record">
                    <div class="camera-record__header">
                        <div>
                            <div class="camera-record__title">
                                <h5 class="mb-0"><?php echo e($camera->name); ?></h5>
                                <span class="camera-chip">Camera #<?php echo e($camera->id); ?></span>
                                <span class="camera-chip"><?php echo e(ucfirst($camera->vendor ?: 'generic')); ?></span>
                            </div>
                            <div class="camera-record__meta">
                                Last seen: <?php echo e($camera->last_seen_at?->format('Y-m-d H:i:s') ?? 'Never'); ?>

                            </div>
                        </div>
                        <span class="badge-surv <?php echo e($camera->status === 'active' ? 'bg-accent3' : 'bg-secondary'); ?>">
                            <?php echo e(strtoupper($camera->status)); ?>

                        </span>
                    </div>

                    <div class="camera-record__body">
                        <form id="camera-update-<?php echo e($camera->id); ?>" method="POST" action="<?php echo e(route('cameras.update', $camera)); ?>" class="camera-config-form">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('PUT'); ?>
                            <?php echo $__env->make('cameras.partials.form-fields', [
                                'camera' => $camera,
                                'settings' => $camera->form_settings,
                                'sourceValue' => $camera->source,
                                'nameValue' => $camera->name,
                                'locationValue' => $camera->location,
                                'typeValue' => $camera->type,
                                'statusValue' => $camera->status,
                                'submitLabel' => 'Save',
                            ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </form>
                    </div>

                    <div class="camera-record__footer">
                        <p>Active cameras are synced to the AI worker automatically. Inactive cameras remain saved for later use.</p>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" form="camera-update-<?php echo e($camera->id); ?>" class="btn btn-sm btn-accent text-dark fw-bold px-3">
                                <i class="fas fa-floppy-disk me-2"></i> Save Changes
                            </button>
                            <form method="POST" action="<?php echo e(route('cameras.destroy', $camera)); ?>" onsubmit="return confirm('Remove this camera from MetroNet?');">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger px-3">
                                    <i class="fas fa-trash me-2"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </article>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<template id="vendor-profiles-json"><?php echo json_encode($vendorOptions, 15, 512) ?></template>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const vendorProfiles = JSON.parse(document.getElementById('vendor-profiles-json').innerHTML);
    const vendorMap = Object.fromEntries(vendorProfiles.map((profile) => [profile.key, profile]));

    function toggleSection(element, show) {
        if (!element) {
            return;
        }

        element.style.display = show ? '' : 'none';
        element.querySelectorAll('input, select').forEach((field) => {
            field.disabled = !show;
        });
    }

    function syncProtocolOptions(form, vendorKey) {
        const protocolSelect = form.querySelector('[name="network_protocol"]');
        if (!protocolSelect) {
            return;
        }

        const profile = vendorMap[vendorKey] || vendorMap.generic;
        const currentValue = protocolSelect.value;

        [...protocolSelect.options].forEach((option) => {
            option.hidden = !profile.protocols.includes(option.value);
        });

        if (!profile.protocols.includes(currentValue)) {
            protocolSelect.value = profile.default_protocol;
        }
    }

    function syncPresetHelp(form, vendorKey) {
        const helpTarget = form.querySelector('[data-role="vendor-help"]');
        const portInput = form.querySelector('[name="port"]');
        const profile = vendorMap[vendorKey] || vendorMap.generic;

        if (helpTarget) {
            helpTarget.textContent = profile.help || '';
        }

        if (portInput && !portInput.value) {
            portInput.value = profile.default_port;
        }
    }

    function applyFormState(form) {
        const type = form.querySelector('[name="type"]')?.value || 'rtsp';
        const mode = form.querySelector('[name="config_mode"]')?.value || 'direct';
        const vendor = form.querySelector('[name="vendor"]')?.value || 'generic';

        toggleSection(form.querySelector('[data-role="network-only"]'), type === 'rtsp');
        toggleSection(form.querySelector('[data-role="usb-only"]'), type === 'usb');
        toggleSection(form.querySelector('[data-role="direct-source"]'), type === 'usb' || mode === 'direct');
        toggleSection(form.querySelector('[data-role="preset-fields"]'), type === 'rtsp' && mode === 'preset');
        toggleSection(form.querySelector('[data-role="vendor-only"]'), type === 'rtsp' && mode === 'preset');

        syncProtocolOptions(form, vendor);
        syncPresetHelp(form, vendor);
    }

    document.querySelectorAll('.camera-config-form').forEach((form) => {
        form.addEventListener('change', function (event) {
            if (['type', 'config_mode', 'vendor'].includes(event.target.name)) {
                applyFormState(form);
            }
        });

        applyFormState(form);
    });
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Mahadi Hassan Arif\Desktop\Camera Tracking\sentinel\resources\views\cameras\index.blade.php ENDPATH**/ ?>