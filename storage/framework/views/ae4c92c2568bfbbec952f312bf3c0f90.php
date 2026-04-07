<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="row mb-5 align-items-center">
        <div class="col-md-6">
            <h1 class="text-white mb-0">OBJECT TRAINING CENTER</h1>
            <p class="text-dim opacity-75">MANAGE CUSTOM CLASSIFIERS & DATASETS</p>
        </div>
        <div class="col-md-6 text-md-end">
            <button class="btn btn-accent px-4 py-2" data-bs-toggle="modal" data-bs-target="#newClassModal">
                <i class="fas fa-plus me-2"></i> CONFIGURE NEW CLASS
            </button>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card p-3 border-0 bg-surface2 text-center">
                <small class="text-dim">TOTAL CLASSES</small>
                <h3 class="mb-0"><?php echo e($stats['total']); ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 border-0 bg-surface2 text-center border-bottom border-info">
                <small class="text-dim">READY TO TRAIN</small>
                <h3 class="mb-0 text-info"><?php echo e($stats['ready']); ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 border-0 bg-surface2 text-center border-bottom border-warning">
                <small class="text-dim">IN TRAINING</small>
                <h3 class="mb-0 text-warning"><?php echo e($stats['training']); ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 border-0 bg-surface2 text-center border-bottom border-success">
                <small class="text-dim">TRAINED / DEPLOYED</small>
                <h3 class="mb-0 text-success"><?php echo e($stats['trained']); ?></h3>
            </div>
        </div>
    </div>

    <!-- Classes Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-dark table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th class="ps-4">CLASS NAME / ID</th>
                        <th>SAMPLE IMAGES</th>
                        <th>LABELED DATA</th>
                        <th>STATUS</th>
                        <th>ACCURACY</th>
                        <th class="text-end pe-4">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $classes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $class): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td class="ps-4">
                            <h6 class="mb-0 text-white"><?php echo e($class->display_name); ?></h6>
                            <small class="text-dim font-monospace x-small"><?php echo e($class->name); ?> (#<?php echo e($class->id); ?>)</small>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <span class="badge bg-surface2 border border-border me-2 px-3"><?php echo e($class->training_images_count); ?></span>
                                <small class="text-dim">CAPTURES</small>
                            </div>
                        </td>
                        <td>
                            <div class="progress" style="height: 6px; width: 100px; background: rgba(255,255,255,0.05)">
                                <?php $pct = min(100, ($class->labeled_count / 30) * 100); ?>
                                <div class="progress-bar bg-info" style="width: <?php echo e($pct); ?>%"></div>
                            </div>
                            <small class="small opacity-50"><?php echo e($class->labeled_count); ?> / 30 required</small>
                        </td>
                        <td>
                            <?php
                                $statusColors = [
                                    'pending' => 'bg-secondary',
                                    'labeling' => 'bg-warning text-dark',
                                    'ready' => 'bg-info text-dark',
                                    'training' => 'bg-warning text-dark animated-pulse',
                                    'trained' => 'bg-success',
                                    'failed' => 'bg-danger'
                                ];
                            ?>
                            <span class="badge-surv <?php echo e($statusColors[$class->status]); ?>"><?php echo e($class->status); ?></span>
                        </td>
                        <td>
                            <?php if($class->training_accuracy): ?>
                                <span class="text-success fw-bold"><?php echo e($class->training_accuracy); ?>%</span>
                            <?php else: ?>
                                <span class="text-dim opacity-25">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end pe-4">
                            <div class="btn-group">
                                <a href="<?php echo e(route('training.label', $class->id)); ?>" class="btn btn-sm btn-outline-info">
                                    <i class="fas fa-tag me-1"></i> LABEL
                                </a>
                                <?php if($class->status == 'ready' || $class->status == 'failed'): ?>
                                <button onclick="startTraining(<?php echo e($class->id); ?>)" class="btn btn-sm btn-outline-warning">
                                    <i class="fas fa-play me-1"></i> TRAIN
                                </button>
                                <?php endif; ?>
                                <?php if($class->status == 'training'): ?>
                                <a href="<?php echo e(route('training.progress', $class->id)); ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-spinner fa-spin me-1"></i> STATUS
                                </a>
                                <?php endif; ?>
                                <button onclick="deleteClass(<?php echo e($class->id); ?>)" class="btn btn-sm btn-outline-danger border-0">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-dim">
                            NO CUSTOM CLASSES DEFINED. START BY CREATING ONE.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: New Class -->
<div class="modal fade" id="newClassModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-surface border-border shadow-lg">
            <div class="modal-header border-border">
                <h5 class="modal-title">CONFIGURE AI CLASSIFIER</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="createClassForm">
                    <div class="mb-3">
                        <label class="form-label small text-dim font-monospace">UNIQUE_KEY (alpha_dash)</label>
                        <input type="text" id="className" class="form-control bg-surface2 border-border text-white" placeholder="handgun_detected" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-dim font-monospace">DISPLAY_LABEL</label>
                        <input type="text" id="classDisplayName" class="form-control bg-surface2 border-border text-white" placeholder="Authorized Weapon" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small text-dim font-monospace">CONTEXT_DESCRIPTION</label>
                        <textarea id="classDescription" class="form-control bg-surface2 border-border text-white" rows="2" placeholder="Detecting unauthorized weapons at gate."></textarea>
                    </div>
                    <button type="button" onclick="submitNewClass()" class="btn btn-accent w-100 py-3 fw-bold">INITIALIZE CLASSIFIER ENTITY</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
    async function submitNewClass() {
        const payload = {
            name: document.getElementById('className').value,
            display_name: document.getElementById('classDisplayName').value,
            description: document.getElementById('classDescription').value,
        };
        
        try {
            const res = await fetch('/api/training/classes', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>'
                },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.success) {
                location.reload();
            } else {
                alert('ERROR: ' + data.error);
            }
        } catch (e) { alert('System communication error.'); }
    }

    async function startTraining(id) {
        if (!confirm('INITIATE YOLOv8 TRAINING PIPELINE? This process consumes high GPU/CPU resources.')) return;
        try {
            const res = await fetch(`/api/training/classes/${id}/train`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>' }
            });
            const data = await res.json();
            if (data.success) {
                window.location.href = `/training/${id}/progress`;
            } else {
                alert('PIPELINE FAILED: ' + data.error);
            }
        } catch (e) { alert('System communication error.'); }
    }

    async function deleteClass(id) {
        if (!confirm('WIPE ALL TRAINING DATA FOR THIS CLASS? This cannot be undone.')) return;
        try {
            const res = await fetch(`/api/v1/training/classes/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>' }
            });
            location.reload();
        } catch (e) { alert('System communication error.'); }
    }
</script>
<style>
    .animated-pulse { animation: pulse 1.5s infinite; }
    @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.4; } 100% { opacity: 1; } }
</style>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Mahadi Hassan Arif\Desktop\Camera Tracking\sentinel\resources\views\training\index.blade.php ENDPATH**/ ?>