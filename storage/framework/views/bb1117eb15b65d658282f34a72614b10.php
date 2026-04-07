

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="row g-4">
        <!-- LEFT COLUMN: LIVE VIEW -->
        <div class="col-xl-8 col-lg-7">
            <div class="card h-100 border-accent">
                <div class="card-header bg-dark d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-video text-accent me-2"></i>
                        <span class="fw-bold">LIVE FEED TERMINAL / GATE MONITOR</span>
                    </div>
                    <div>
                        <select class="form-select form-select-sm bg-surface2 border-border text-white" id="cameraSelect">
                            <?php $__currentLoopData = $cameras; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $camera): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($camera->id); ?>"><?php echo e($camera->name); ?> (<?php echo e($camera->location); ?>)</option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php if($cameras->isEmpty()): ?>
                            <option value="1">NO CAMERAS DETECTED</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                <div class="card-body p-0 position-relative bg-black" style="min-height: 480px; display: flex; align-items: center; justify-content: center;">
                    <img id="gateFeed" src="<?php echo e(asset('frames/cam_' . ($cameras->first()->id ?? 1) . '.jpg')); ?>?t=<?php echo e(now()->timestamp); ?>"
                         class="img-fluid w-100" style="object-fit: contain; max-height: 70vh;">
                    <div id="noSignalOverlay" style="display:none; position:absolute;
                        inset:0; background:#050a0e; align-items:center;
                        justify-content:center; flex-direction:column; gap:8px;
                        color:#4a6478; font-family:'Space Mono',monospace;">
                        <i class="fa-solid fa-video-slash" style="font-size:32px"></i>
                        <span style="font-size:10px;letter-spacing:3px">NO SIGNAL</span>
                        <span style="font-size:9px;letter-spacing:1px;color:#1a2e3a">
                            Waiting for camera feed...
                        </span>
                    </div>

                    <!-- Current Detection Overlay -->
                    <div id="detectionOverlay" class="position-absolute bottom-0 start-0 w-100 p-4" style="background: linear-gradient(transparent, rgba(0,0,0,0.8))">
                        <div class="d-flex justify-content-between align-items-end">
                            <div id="currentDetectionBox">
                                <h2 class="display-3 font-mono mb-0" id="objectCount">00</h2>
                                <h6 class="text-accent mb-2 small fw-bold">CURRENT ANALYSIS:</h6>
                                <div id="detectionLog" class="font-mono" style="font-size: 0.85rem;">
                                    <div id="detectionList" class="d-flex flex-wrap gap-2">
                                        <span class="text-dim opacity-50 small">-- NO OBJECTS DETECTED --</span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-end">
                                <small id="streamMeta" class="text-dim d-block mb-1 font-mono">FPS: <span id="fpsVal">12</span> | DELAY: 24ms</small>
                                <span class="badge-surv bg-accent3" id="streamStatus">STREAM ENCRYPTED</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer border-border bg-surface d-flex justify-content-between">
                    <button class="btn btn-sm btn-outline-danger border-0" onclick="clearLog()">
                        <i class="fas fa-trash-alt me-2"></i> CLEAR HISTORY
                    </button>
                    <a href="/api/detections/export" class="btn btn-sm btn-accent text-dark fw-bold">
                        <i class="fas fa-file-csv me-2"></i> EXPORT LOG (CSV)
                    </a>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: LIVE REPORT -->
        <div class="col-xl-4 col-lg-5">
            <div class="card mb-4">
                <div class="card-header"><h6 class="mb-0">TODAY'S SUMMARY</h6></div>
                <div class="card-body py-4">
                    <div class="row text-center">
                        <div class="col-4">
                            <h4 id="summaryAlerts" class="mb-0"><?php echo e($stats['total_detections']); ?></h4>
                            <small class="text-dim x-small">ALERTS</small>
                        </div>
                        <div class="col-4 border-start border-end border-border">
                            <h4 id="summaryObjects" class="mb-0"><?php echo e($stats['object_count']); ?></h4>
                            <small class="text-dim x-small">OBJECTS</small>
                        </div>
                        <div class="col-4">
                            <h4 id="summaryLatest" class="mb-0 text-truncate small" style="font-size: 0.8rem;"><?php echo e($stats['latest_time'] ?? '--:--:--'); ?></h4>
                            <small class="text-dim x-small">LATEST</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4" style="height: 300px;">
                <div class="card-header"><h6 class="mb-0">BREAKDOWN BY CLASS</h6></div>
                <div class="card-body overflow-auto" id="breakdownBody">
                    <div class="text-center py-5 text-dim opacity-50">INITIALIZING ANALYSIS...</div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between">
                    <h6 class="mb-0">RECENT CAPTURES</h6>
                    <small class="text-accent underline cursor-pointer" onclick="fetchReport()">REFRESH</small>
                </div>
                <div class="card-body p-2">
                    <div class="row g-2" id="snapshotGrid">
                        <?php $__currentLoopData = $recent_snapshots; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $snap): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="col-3">
                            <a href="<?php echo e(asset('snapshots/' . basename($snap->snapshot_path))); ?>" target="_blank">
                                <img src="<?php echo e(asset('snapshots/' . basename($snap->snapshot_path))); ?>" class="img-fluid rounded border border-border hover-zoom" title="<?php echo e($snap->detected_at); ?>">
                            </a>
                        </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">LIVE ACTIVITY FEED</div>
                <div class="list-group list-group-flush overflow-auto" style="max-height: 400px;" id="activityFeed">
                    <div class="list-group-item bg-transparent text-dim text-center py-4 opacity-50">WAITING FOR SYSTEM EVENTS...</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
    let frameRefreshTimer = null;

    function buildFrameUrl(camId) {
        return `/frames/cam_${camId}.jpg?t=${Date.now()}`;
    }

    function startStream() {
        const img = document.getElementById('gateFeed');
        if (!img) return;

        const camId = document.getElementById('cameraSelect')?.value || 1;
        const overlay = document.getElementById('noSignalOverlay');
        const streamMeta = document.getElementById('streamMeta');
        const streamStatus = document.getElementById('streamStatus');

        if (frameRefreshTimer) clearInterval(frameRefreshTimer);

        img.onload = function() {
            if (overlay) overlay.style.display = 'none';
            if (streamMeta) {
                streamMeta.innerHTML = 'FPS: <span id="fpsVal">1</span> | DELAY: FRAME POLL';
            }
            if (streamStatus) streamStatus.textContent = 'FRAME LINK ACTIVE';
        };

        img.onerror = function() {
            if (overlay) overlay.style.display = 'flex';
            if (streamMeta) {
                streamMeta.innerHTML = 'FPS: <span id="fpsVal">0</span> | DELAY: NO SIGNAL';
            }
            if (streamStatus) streamStatus.textContent = 'SIGNAL LOST';
        };

        // Poll the latest JPEG frame instead of holding open an MJPEG stream.
        // This keeps Laravel's local dev server responsive for the report APIs.
        const refreshFrame = () => {
            img.src = buildFrameUrl(camId);
        };

        refreshFrame();
        frameRefreshTimer = setInterval(refreshFrame, 1000);
    }

    document.getElementById('cameraSelect')?.addEventListener('change', startStream);

    startStream();

    function fetchReport() {
        fetch('/api/detections/report', {
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
        .then(r => r.json())
        .then(data => {
            const alertEl = document.getElementById('summaryAlerts');
            const objEl = document.getElementById('summaryObjects');
            const lastEl = document.getElementById('summaryLatest');
            if (alertEl) alertEl.textContent = data.total_detections ?? 0;
            if (objEl) objEl.textContent = data.total_objects ?? 0;
            if (lastEl) lastEl.textContent = data.last_detection ?? '--:--:--';

            const breakdown = document.getElementById('breakdownBody');
            if (!breakdown || !data.breakdown) return;
            breakdown.innerHTML = '';
            const maxCount = data.breakdown[0]?.count || 1;
            data.breakdown.forEach(row => {
                const pct = Math.round((row.count / maxCount) * 100);
                const color = getObjColor(row.object);
                breakdown.innerHTML += `
                <div class="breakdown-row d-flex align-items-center gap-2 mb-2">
                    <span style="min-width:90px;font-size:10px;letter-spacing:1px;color:${color};text-transform:uppercase">${row.object}</span>
                    <div style="flex:1;height:4px;background:#1a2e3a;border-radius:2px">
                        <div style="width:${pct}%;height:100%;background:${color};border-radius:2px;transition:width 0.5s ease"></div>
                    </div>
                    <span style="color:white;font-weight:700;font-size:12px;min-width:20px">${row.count}</span>
                </div>`;
            });
            if (data.breakdown.length === 0) {
                breakdown.innerHTML =
                    '<div style="color:#4a6478;font-size:10px;letter-spacing:2px;text-align:center;padding:16px">' +
                    'NO DATA TODAY</div>';
            }

            renderSnapshots(data.snapshots || []);
            renderActivityFeed(data.activity || []);
            if (data.latest_event) {
                showDetectionOverlay(data.latest_event);
            }
        })
        .catch(() => {});
    }

    function getObjColor(obj) {
        const map = {
            person:'#ff3d6b', phone:'#ffaa00', watch:'#b8a0ff',
            bag:'#b8ff57', backpack:'#b8ff57', laptop:'#00e5ff',
            food:'#57ff8a', bottle:'#57ff8a', cup:'#57ff8a',
            knife:'#ff3d6b', scissors:'#ff3d6b',
        };
        return map[String(obj).toLowerCase()] || '#4a6478';
    }

    function addActivityItem(data) {
        const feed = document.getElementById('activityFeed');
        if (!feed) return;
        if (feed.innerText.includes('WAITING FOR SYSTEM EVENTS')) feed.innerHTML = '';
        const time = new Date().toLocaleTimeString('en-GB');
        const objs = (data.carried_objects || [])
            .map(o => `<span style="font-size:9px;padding:1px 5px;border-radius:2px;background:#1a2e3a;color:${getObjColor(o)};margin:1px;display:inline-block">${o}</span>`).join('');
        const item = document.createElement('div');
        item.style.cssText = 'padding:8px 12px;border-left:2px solid #ff3d6b;' +
            'margin-bottom:6px;background:#111c23;animation:slideIn 0.3s ease';
        item.innerHTML = `
        <div style="font-size:9px;color:#4a6478;letter-spacing:1px">${time} · ${data.camera_name}</div>
        <div style="font-size:11px;color:white;margin:2px 0">${data.person_count} person(s) detected</div>
        <div>${objs || '<span style="font-size:9px;color:#4a6478">no objects</span>'}</div>`;
        feed.prepend(item);
        while (feed.children.length > 20) feed.removeChild(feed.lastChild);
    }

    function renderSnapshots(items) {
        const grid = document.getElementById('snapshotGrid');
        if (!grid) return;

        if (!items.length) {
            grid.innerHTML = '<div class="col-12 text-center py-3 text-dim opacity-50 small">NO RECENT CAPTURES</div>';
            return;
        }

        grid.innerHTML = items.map(item => `
            <div class="col-3">
                <a href="${item.snapshot_url}" target="_blank">
                    <img src="${item.snapshot_url}" class="img-fluid rounded border border-border hover-zoom" title="${item.detected_at || ''}">
                </a>
            </div>
        `).join('');
    }

    function renderActivityFeed(items) {
        const feed = document.getElementById('activityFeed');
        if (!feed) return;

        if (!items.length) {
            feed.innerHTML = '<div class="list-group-item bg-transparent text-dim text-center py-4 opacity-50">WAITING FOR SYSTEM EVENTS...</div>';
            return;
        }

        feed.innerHTML = items.map(item => {
            const time = item.detected_at ? new Date(item.detected_at).toLocaleTimeString('en-GB') : '--:--:--';
            const objects = (item.display_objects || item.carried_objects || item.all_objects || [])
                .map(object => `<span style="font-size:9px;padding:1px 5px;border-radius:2px;background:#1a2e3a;color:${getObjColor(object)};margin:1px;display:inline-block">${object}</span>`)
                .join('');

            return `
                <div style="padding:8px 12px;border-left:2px solid #ff3d6b;margin-bottom:6px;background:#111c23;animation:slideIn 0.3s ease">
                    <div style="font-size:9px;color:#4a6478;letter-spacing:1px">${time} · ${item.camera_name}</div>
                    <div style="font-size:11px;color:white;margin:2px 0">${item.person_count} person(s) detected</div>
                    <div>${objects || '<span style="font-size:9px;color:#4a6478">no objects</span>'}</div>
                </div>
            `;
        }).join('');
    }

    function showDetectionOverlay(data) {
        const list = document.getElementById('detectionList');
        const count = document.getElementById('objectCount');
        if (!list || !count) return;

        const displayObjects = data.display_objects || data.carried_objects || data.all_objects || [];
        count.textContent = String(data.object_count ?? displayObjects.length ?? 0).padStart(2, '0');

        if (displayObjects.length === 0) {
            list.innerHTML = '<span class="text-dim opacity-50 small">-- NO OBJECTS DETECTED --</span>';
            return;
        }

        list.innerHTML = displayObjects.map(object => `
            <span class="badge text-dark px-3 py-2" style="background:${getObjColor(object)}">
                ${String(object).toUpperCase()}
            </span>
        `).join('');

        setTimeout(() => {
            list.innerHTML = '<span class="text-dim opacity-50 small">-- NO OBJECTS DETECTED --</span>';
            count.textContent = '00';
        }, 5000);
    }

    async function fetchLatestDetection() {
        try {
            const res = await fetch('/api/detections/latest', {
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            });
            if (!res.ok) return;
            const data = await res.json();
            if (!data) return;
            showDetectionOverlay(data);
        } catch (_) {}
    }

    setInterval(fetchReport, 5000);
    fetchReport();
    setInterval(fetchLatestDetection, 3000);
    fetchLatestDetection();

    /*
    window.Echo.channel('detections')
      .listen('.object.detected', (data) => {
          addActivityItem(data);
          showDetectionOverlay(data);
          fetchReport();
          showAlert(`${data.camera_name}: ${data.person_count} person(s) detected`);
      });
    */

    async function clearLog() {
        if (!confirm('TRUNCATE ALL DETECTION RECORDS? (Action cannot be reversed)')) return;
        const res = await fetch('/api/detections', {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>',
                'X-Requested-With': 'XMLHttpRequest',
            }
        });
        if (res.ok) location.reload();
    }
</script>

<style>
    @keyframes flash {
        0% { background: rgba(0, 229, 255, 0.1); }
        100% { background: transparent; }
    }
    @keyframes slideIn {
        0% { opacity: 0; transform: translateX(-8px); }
        100% { opacity: 1; transform: translateX(0); }
    }
    .animate-flash { animation: flash 1.5s ease-out; }
    .x-small { font-size: 0.65rem; font-weight: 800; letter-spacing: 1px; }
    .hover-zoom { transition: transform 0.3s ease; cursor: pointer; }
    .hover-zoom:hover { transform: scale(1.1); z-index: 10; }
</style>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Mahadi Hassan Arif\Desktop\Camera Tracking\sentinel\resources\views\gate\dashboard.blade.php ENDPATH**/ ?>