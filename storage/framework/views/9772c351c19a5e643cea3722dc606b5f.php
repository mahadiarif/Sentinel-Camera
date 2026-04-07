<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="row mb-4 align-items-end">
        <div class="col-md-6">
            <h1 class="text-white mb-0 mt-2">SYSTEM OVERVIEW</h1>
            <p class="text-dim opacity-75">LIVE METRICS & RECENT MONITORING LOGS</p>
        </div>
        <div class="col-md-6 text-md-end">
            <span class="badge-surv bg-accent3 px-3 py-2">
                <i class="fas fa-circle-check me-2"></i> SYSTEM OPERATIONAL
            </span>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card p-4">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-dim small mb-2">ACTIVE CAMERAS</h6>
                        <h2 class="mb-0"><?php echo e($stats['total_cameras']); ?></h2>
                    </div>
                    <div class="text-accent fs-1 opacity-25">
                        <i class="fas fa-video"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-4">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-dim small mb-2">DETECTIONS (24H)</h6>
                        <h2 class="mb-0 text-accent"><?php echo e($stats['detections_today']); ?></h2>
                    </div>
                    <div class="text-accent fs-1 opacity-25">
                        <i class="fas fa-eye"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-4">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-dim small mb-2">CRITICAL ALERTS</h6>
                        <h2 class="mb-0 text-danger"><?php echo e($stats['alerts_today']); ?></h2>
                    </div>
                    <div class="text-danger fs-1 opacity-25">
                        <i class="fas fa-triangle-exclamation"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-4">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-dim small mb-2">TRAINED MODELS</h6>
                        <h2 class="mb-0" style="color: var(--accent3)"><?php echo e($stats['trained_models']); ?></h2>
                    </div>
                    <div class="fs-1 opacity-25" style="color: var(--accent3)">
                        <i class="fas fa-brain"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Middle Content Row -->
    <div class="row g-4 mb-5">
        <!-- Breakdown by Class -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header border-bottom border-border">
                    <h5 class="mb-0 small fw-bold text-accent">BREAKDOWN BY CLASS</h5>
                </div>
                <div class="card-body p-4">
                    <?php $__empty_1 = true; $__currentLoopData = $breakdown; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            $color = in_array(strtolower($b['name']), ['person']) ? 'var(--accent2)' : 
                                    (in_array(strtolower($b['name']), ['car','truck','bus']) ? 'var(--accent)' : 'var(--accent3)');
                            $pct = ($b['count'] / max($stats['detections_today'], 1)) * 100;
                        ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="small font-mono text-uppercase"><?php echo e($b['name']); ?></span>
                                <span class="small fw-bold"><?php echo e($b['count']); ?></span>
                            </div>
                            <div class="progress" style="height: 6px; background: rgba(255,255,255,0.05); border-radius: 3px;">
                                <div class="progress-bar" style="width: <?php echo e($pct); ?>%; background: <?php echo e($color); ?>; box-shadow: 0 0 10px <?php echo e($color); ?>; border-radius: 3px;"></div>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="text-center py-5 opacity-25">
                            <i class="fas fa-chart-pie fa-3x mb-3"></i>
                            <p class="small mb-0">NO ANALYTICS FOR TODAY</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Logs Table (Modified to span 8 columns) -->
        <div class="col-md-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 small fw-bold">RECENT DETECTION LOGS</h5>
                    <a href="<?php echo e(route('gate.monitor')); ?>" class="btn btn-sm btn-outline-info border-0">
                        OPEN MONITOR <i class="fas fa-chevron-right ms-2"></i>
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0" style="background: transparent;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--border);">
                                <th class="ps-4">TIME</th>
                                <th>CAMERA</th>
                                <th>OBJECTS</th>
                                <th>LEVEL</th>
                            </tr>
                        </thead>
                        <tbody id="recentLogsTable">
                            <?php $__empty_1 = true; $__currentLoopData = $recent_detections; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.03);">
                                <td class="ps-4 text-dim font-mono small"><?php echo e($d->created_at->format('H:i:s')); ?></td>
                                <td class="small fw-bold"><?php echo e($d->camera_name); ?></td>
                                <td>
                                    <?php $__currentLoopData = $d->detected_objects; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $obj): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php
                                            $color = in_array($obj, ['person']) ? 'bg-accent2' : 
                                                    (in_array($obj, ['car','truck','bus']) ? 'bg-accent' : 'bg-warning text-dark');
                                        ?>
                                        <span class="badge <?php echo e($color); ?> x-small me-1"><?php echo e($obj); ?></span>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </td>
                                <td>
                                    <?php if($d->person_detected): ?>
                                        <span class="badge-surv bg-accent2">CRITICAL</span>
                                    <?php else: ?>
                                        <span class="badge-surv bg-accent text-dark">SECURE</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="4" class="text-center py-5 text-dim">
                                    <i class="fas fa-inbox fa-3x mb-3 d-block opacity-25"></i>
                                    NO RECENT DETECTIONS
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .x-small { font-size: 0.65rem; padding: 0.2rem 0.5rem; }
    .card { background: rgba(11, 19, 24, 0.8); backdrop-filter: blur(10px); }
</style>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Mahadi Hassan Arif\Desktop\Camera Tracking\sentinel\resources\views/dashboard/index.blade.php ENDPATH**/ ?>