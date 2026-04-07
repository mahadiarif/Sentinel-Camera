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

    <!-- Recent Logs -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">RECENT DETECTION LOGS</h5>
                    <a href="<?php echo e(route('gate.monitor')); ?>" class="btn btn-sm btn-outline-info border-0">
                        OPEN MONITOR <i class="fas fa-chevron-right ms-2"></i>
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">TIME</th>
                                <th>CAMERA</th>
                                <th>OBJECTS DETECTED</th>
                                <th>COUNT</th>
                                <th>CONFIDENCE</th>
                                <th>SECURITY LEVEL</th>
                            </tr>
                        </thead>
                        <tbody id="recentLogsTable">
                            <?php $__empty_1 = true; $__currentLoopData = $recent_detections; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td class="ps-4 text-dim font-monospace"><?php echo e($d->created_at->format('H:i:s')); ?></td>
                                <td><?php echo e($d->camera_name); ?></td>
                                <td>
                                    <?php $__currentLoopData = $d->detected_objects; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $obj): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php
                                            $color = in_array($obj, ['person']) ? 'bg-accent2' : 
                                                    (in_array($obj, ['car','truck','bus']) ? 'bg-accent' : 'bg-warning text-dark');
                                        ?>
                                        <span class="badge <?php echo e($color); ?> small me-1"><?php echo e($obj); ?></span>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </td>
                                <td><?php echo e($d->object_count); ?></td>
                                <td>
                                    <div class="progress" style="height: 4px; width: 60px; background: rgba(255,255,255,0.05)">
                                        <div class="progress-bar bg-accent" style="width: <?php echo e($d->confidence); ?>%"></div>
                                    </div>
                                    <small class="small opacity-50"><?php echo e($d->confidence); ?>%</small>
                                </td>
                                <td>
                                    <?php if($d->person_detected): ?>
                                        <span class="badge-surv bg-accent2">LEVEL 5 / CRITICAL</span>
                                    <?php else: ?>
                                        <span class="badge-surv bg-accent text-dark">LEVEL 1 / INFORMATIONAL</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-dim">
                                    <i class="fas fa-inbox fa-3x mb-3 d-block opacity-25"></i>
                                    NO RECENT DETECTIONS RECORDED IN SYSTEM
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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Mahadi Hassan Arif\Desktop\Camera Tracking\sentinel\resources\views\dashboard\index.blade.php ENDPATH**/ ?>