@extends('layouts.app')

@section('content')
<div style="display:flex;flex-direction:column;gap:20px;">

    {{-- ── STAT CARDS ROW ── --}}
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;">

        <div class="stat-card cyan">
            <div class="stat-icon text-cyan"><i class="fas fa-users-viewfinder"></i></div>
            <div class="stat-value text-cyan" id="summaryAlerts">{{ $stats['total_detections'] }}</div>
            <div class="stat-label">Total Shop Visits</div>
        </div>

        <div class="stat-card green">
            <div class="stat-icon text-green"><i class="fas fa-user-check"></i></div>
            <div class="stat-value text-green" id="summaryPersons">{{ $stats['person_count'] }}</div>
            <div class="stat-label">Unique Customers</div>
        </div>

        <div class="stat-card amber">
            <div class="stat-icon text-amber"><i class="fas fa-list-ol"></i></div>
            <div class="stat-value text-amber" id="summaryQueue" style="font-size:24px;">NORMAL</div>
            <div class="stat-label">Queue Status</div>
        </div>

        <div class="stat-card red">
            <div class="stat-icon text-red"><i class="fas fa-hourglass-half"></i></div>
            <div class="stat-value" id="summaryWait"
                 style="font-size:24px;color:var(--text-bright);letter-spacing:1px;">
                <span id="estimatedWait">0</span> MIN
            </div>
            <div class="stat-label">Est. Checkout Time</div>
        </div>

    </div>

    {{-- ── MAIN ROW ── --}}
    <div style="display:grid;grid-template-columns:1fr 380px;gap:20px;align-items:start;">

        {{-- LEFT: LIVE FEED --}}
        <div class="card" style="overflow:hidden;">
            <div class="card-header">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div class="card-icon" style="background:rgba(0,212,255,0.1);">
                        <i class="fas fa-video text-cyan" style="font-size:12px;"></i>
                    </div>
                    <span>Live Feed Terminal</span>
                    <span class="badge-status badge-live ms-2" id="streamBadge">CONNECTING</span>
                </div>
                <select id="cameraSelect"
                        style="background:var(--surface2);border:1px solid var(--border2);color:var(--text);
                               border-radius:7px;padding:5px 10px;font-size:12px;outline:none;cursor:pointer;">
                    @foreach($cameras as $camera)
                    <option value="{{ $camera->id }}">{{ $camera->name }} · {{ $camera->location }}</option>
                    @endforeach
                    @if($cameras->isEmpty())
                    <option value="1">No cameras configured</option>
                    @endif
                </select>
            </div>

            {{-- Feed container --}}
            <div style="position:relative;background:#000;height:460px;overflow:hidden;">
                <img id="gateFeed"
                     src="{{ asset('gate_frames/cam_' . ($cameras->first()->id ?? 1) . '.jpg') }}?t={{ now()->timestamp }}"
                     style="position:absolute;inset:0;width:100%;height:100%;object-fit:contain;display:block;">

                {{-- No signal overlay --}}
                <div id="noSignalOverlay"
                     style="display:none;position:absolute;inset:0;
                            background:var(--bg);flex-direction:column;
                            align-items:center;justify-content:center;gap:12px;">
                    <div style="width:64px;height:64px;border-radius:16px;background:var(--surface2);
                                display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-video-slash" style="font-size:24px;color:var(--text-muted);"></i>
                    </div>
                    <div style="font-family:'JetBrains Mono',monospace;font-size:11px;
                                letter-spacing:3px;color:var(--text-muted);">NO SIGNAL</div>
                    <div style="font-family:'JetBrains Mono',monospace;font-size:9px;
                                letter-spacing:1px;color:var(--border2);">Waiting for camera stream...</div>
                </div>

                {{-- Detection overlay at bottom --}}
                <div style="position:absolute;bottom:0;left:0;right:0;padding:20px;
                            background:linear-gradient(transparent, rgba(6,11,18,0.9));">
                    <div style="display:flex;justify-content:space-between;align-items:flex-end;">
                        <div>
                            <div style="font-family:'JetBrains Mono',monospace;font-size:48px;
                                        font-weight:700;line-height:1;color:var(--text-bright);" id="objectCount">00</div>
                            <div style="font-family:'JetBrains Mono',monospace;font-size:10px;
                                        letter-spacing:2px;color:var(--cyan);margin-bottom:8px;">OBJECTS · CURRENT FRAME</div>
                            <div id="detectionList" style="display:flex;flex-wrap:wrap;gap:6px;"></div>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-family:'JetBrains Mono',monospace;font-size:11px;
                                        color:var(--text-muted);margin-bottom:6px;"
                                 id="streamMeta">FPS: — · POLLING</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div style="padding:12px 18px;border-top:1px solid var(--border);
                        display:flex;gap:10px;align-items:center;">
                <button class="btn-danger-custom" onclick="clearLog()" style="font-size:12px;">
                    <i class="fas fa-trash me-2"></i>Clear History
                </button>
                <a href="/gate/report" class="btn-primary-custom" style="font-size:12px;text-decoration:none;">
                    <i class="fas fa-file-csv me-2"></i>Export CSV
                </a>
                <div style="margin-left:auto;display:flex;align-items:center;gap:6px;
                            font-family:'JetBrains Mono',monospace;font-size:10px;color:var(--text-muted);">
                    <div class="live-dot" style="width:6px;height:6px;"></div>
                    Frame polling every 1s
                </div>
            </div>
        </div>

        {{-- RIGHT COLUMN --}}
        <div style="display:flex;flex-direction:column;gap:16px;">

            {{-- Breakdown by class --}}
            <div class="card">
                <div class="card-header">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div class="card-icon" style="background:rgba(0,229,122,0.1);">
                            <i class="fas fa-chart-bar text-green" style="font-size:11px;"></i>
                        </div>
                        <span>Breakdown by Class</span>
                    </div>
                    <button onclick="fetchReport()" class="btn-ghost"
                            style="font-size:11px;padding:4px 10px;display:flex;align-items:center;gap:5px;">
                        <i class="fas fa-rotate-right" style="font-size:10px;"></i> Refresh
                    </button>
                </div>
                <div class="card-body" id="breakdownBody" style="min-height:150px;">
                    <div style="text-align:center;padding:32px 0;
                                font-family:'JetBrains Mono',monospace;font-size:10px;
                                letter-spacing:2px;color:var(--text-muted);">
                        INITIALIZING...
                    </div>
                </div>
            </div>

            {{-- Recent snapshots --}}
            <div class="card">
                <div class="card-header">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div class="card-icon" style="background:rgba(168,85,247,0.1);">
                            <i class="fas fa-images" style="font-size:11px;color:#a855f7;"></i>
                        </div>
                        <span>Recent Captures</span>
                    </div>
                    <span style="font-family:'JetBrains Mono',monospace;font-size:10px;color:var(--text-muted);">
                        Last 8
                    </span>
                </div>
                <div class="card-body" style="padding:12px;">
                    <div id="snapshotGrid"
                         style="display:grid;grid-template-columns:repeat(4,1fr);gap:6px;">
                        @foreach($recent_snapshots as $snap)
                        <a href="{{ asset('gate_snapshots/' . basename($snap->snapshot_path)) }}" target="_blank"
                           style="border-radius:6px;overflow:hidden;display:block;aspect-ratio:1;
                                  background:var(--surface2);">
                            <img src="{{ asset('gate_snapshots/' . basename($snap->snapshot_path)) }}"
                                 style="width:100%;height:100%;object-fit:cover;transition:transform 0.2s;"
                                 onmouseover="this.style.transform='scale(1.05)'"
                                 onmouseout="this.style.transform='scale(1)'">
                        </a>
                        @endforeach
                        @if($recent_snapshots->isEmpty())
                        <div style="grid-column:span 4;text-align:center;padding:20px;
                                    font-family:'JetBrains Mono',monospace;font-size:10px;
                                    letter-spacing:1.5px;color:var(--text-muted);">
                            NO CAPTURES YET
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Activity feed --}}
            <div class="card">
                <div class="card-header">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div class="card-icon" style="background:rgba(255,68,102,0.1);">
                            <i class="fas fa-wave-square text-red" style="font-size:11px;"></i>
                        </div>
                        <span>Live Activity Feed</span>
                    </div>
                </div>
                <div id="activityFeed"
                     style="max-height:340px;overflow-y:auto;padding:10px;">
                    <div style="text-align:center;padding:28px 0;
                                font-family:'JetBrains Mono',monospace;font-size:10px;
                                letter-spacing:2px;color:var(--text-muted);">
                        WAITING FOR EVENTS...
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<style>
    @keyframes slideIn {
        from { opacity:0; transform:translateY(-6px); }
        to   { opacity:1; transform:translateY(0); }
    }
    .activity-item { animation: slideIn 0.3s ease; }

    .live-dot {
        width: 6px; height: 6px;
        background: var(--green);
        border-radius: 50%;
        animation: livePulse 1.4s ease-in-out infinite;
        display: inline-block;
    }

    @keyframes livePulse {
        0%, 100% { opacity:1; box-shadow:0 0 0 0 rgba(0,229,122,0.4); }
        50%       { opacity:0.7; box-shadow:0 0 0 4px rgba(0,229,122,0); }
    }
</style>

<script>
    // ─── Frame streaming ───────────────────────────────────────
    let frameTimer = null;

    function buildFrameUrl(camId) {
        return `/gate_frames/cam_${camId}.jpg?t=${Date.now()}`;
    }

    function startStream() {
        const img    = document.getElementById('gateFeed');
        const camId  = document.getElementById('cameraSelect')?.value || 1;
        const overlay = document.getElementById('noSignalOverlay');
        const badge  = document.getElementById('streamBadge');
        const meta   = document.getElementById('streamMeta');

        if (frameTimer) clearInterval(frameTimer);

        img.onload = () => {
            overlay.style.display = 'none';
            if (badge) { badge.textContent = 'LIVE'; badge.className = 'badge-status badge-live ms-2'; }
        };

        img.onerror = () => {
            overlay.style.display = 'flex';
            if (badge) { badge.textContent = 'NO SIGNAL'; badge.className = 'badge-status badge-alert ms-2'; }
        };

        const refresh = () => { img.src = buildFrameUrl(camId); };
        refresh();
        frameTimer = setInterval(refresh, 1000);
    }

    document.getElementById('cameraSelect')?.addEventListener('change', startStream);
    startStream();

    // ─── Report fetch ─────────────────────────────────────────
    function objColor(obj) {
        const map = {
            person:'#ff4466', phone:'#ffa726', watch:'#a855f7',
            bag:'#00e57a', backpack:'#00e57a', laptop:'#00d4ff',
            food:'#57ffa0', bottle:'#57ffa0', cup:'#57ffa0',
            knife:'#ff4466', scissors:'#ff4466', tv:'#00d4ff',
        };
        return map[String(obj).toLowerCase()] || '#4e6880';
    }

    function fetchReport() {
        fetch('/api/detections/report', { headers:{'X-Requested-With':'XMLHttpRequest'} })
        .then(r => r.json())
        .then(data => {
            if (!data) return;
            document.getElementById('summaryAlerts').textContent  = data.total_detections ?? 0;
            document.getElementById('summaryPersons').textContent = data.total_persons    ?? 0;
            document.getElementById('summaryObjects').textContent = data.total_objects    ?? 0;
            if (data.last_detection)
                document.getElementById('summaryLatest').textContent = data.last_detection;

            // breakdown
            const bd = document.getElementById('breakdownBody');
            if (bd && data.breakdown) {
                if (!data.breakdown.length) {
                    bd.innerHTML = `<div style="text-align:center;padding:32px 0;
                        font-family:'JetBrains Mono',monospace;font-size:10px;letter-spacing:2px;
                        color:var(--text-muted);">NO DATA TODAY</div>`;
                } else {
                    const maxC = data.breakdown[0]?.count || 1;
                    bd.innerHTML = data.breakdown.map(row => {
                        const pct = Math.round((row.count / maxC) * 100);
                        const col = objColor(row.object);
                        return `<div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
                            <div style="width:70px;font-family:'JetBrains Mono',monospace;font-size:10px;
                                        letter-spacing:1px;color:${col};text-transform:uppercase;
                                        white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                ${row.object}
                            </div>
                            <div style="flex:1;height:5px;background:var(--surface2);border-radius:3px;overflow:hidden;">
                                <div style="width:${pct}%;height:100%;background:${col};
                                            border-radius:3px;transition:width 0.5s ease;"></div>
                            </div>
                            <div style="font-family:'JetBrains Mono',monospace;font-size:11px;
                                        font-weight:700;color:var(--text-bright);min-width:22px;text-align:right;">
                                ${row.count}
                            </div>
                        </div>`;
                    }).join('');
                }
            }

            renderSnapshots(data.snapshots || []);
            renderActivityFeed(data.activity || []);
            if (data.latest_event) {
                showDetectionOverlay(data.latest_event);
                
                // SuperShop Queue Logic
                const pCount = data.latest_event.person_count || 0;
                const queueElem = document.getElementById('summaryQueue');
                const waitElem = document.getElementById('estimatedWait');
                
                if (queueElem && waitElem) {
                    if (pCount > 5) {
                        queueElem.textContent = 'HEAVY';
                        queueElem.style.color = 'var(--red)';
                        waitElem.textContent = (pCount * 1.5).toFixed(0);
                    } else if (pCount > 2) {
                        queueElem.textContent = 'BUSY';
                        queueElem.style.color = 'var(--amber)';
                        waitElem.textContent = (pCount * 1).toFixed(0);
                    } else {
                        queueElem.textContent = 'NORMAL';
                        queueElem.style.color = 'var(--cyan)';
                        waitElem.textContent = '0';
                    }
                }
            }
        })
        .catch(() => {});
    }

    // ─── Snapshots ────────────────────────────────────────────
    function renderSnapshots(items) {
        const grid = document.getElementById('snapshotGrid');
        if (!grid) return;

        if (!items.length) {
            grid.innerHTML = `<div style="grid-column:span 4;text-align:center;padding:20px;
                font-family:'JetBrains Mono',monospace;font-size:10px;
                letter-spacing:1.5px;color:var(--text-muted);">NO CAPTURES YET</div>`;
            return;
        }

        grid.innerHTML = items.map(item => `
            <a href="${item.snapshot_url}" target="_blank"
               style="border-radius:6px;overflow:hidden;display:block;aspect-ratio:1;background:var(--surface2);">
                <img src="${item.snapshot_url}"
                     style="width:100%;height:100%;object-fit:cover;transition:transform 0.2s;"
                     onmouseover="this.style.transform='scale(1.05)'"
                     onmouseout="this.style.transform='scale(1)'">
            </a>
        `).join('');
    }

    // ─── Activity feed ────────────────────────────────────────
    function renderActivityFeed(items) {
        const feed = document.getElementById('activityFeed');
        if (!feed) return;

        if (!items.length) {
            feed.innerHTML = `<div style="text-align:center;padding:28px 0;
                font-family:'JetBrains Mono',monospace;font-size:10px;
                letter-spacing:2px;color:var(--text-muted);">WAITING FOR EVENTS...</div>`;
            return;
        }

        feed.innerHTML = items.map(item => {
            const time = item.detected_at
                ? new Date(item.detected_at).toLocaleTimeString('en-GB')
                : '--:--:--';
            const objs = (item.display_objects || item.all_objects || [])
                .map(o => `<span style="font-family:'JetBrains Mono',monospace;font-size:9px;
                    padding:2px 7px;border-radius:4px;background:var(--surface3);
                    color:${objColor(o)};border:1px solid ${objColor(o)}22;">${o}</span>`)
                .join('');
            const hasPersons = (item.person_count || 0) > 0;

            return `<div class="activity-item" style="padding:10px 12px;border-radius:8px;
                margin-bottom:6px;background:var(--surface2);
                border-left:3px solid ${hasPersons ? 'var(--red)' : 'var(--border2)'};">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
                    <span style="font-family:'JetBrains Mono',monospace;font-size:9px;
                                 letter-spacing:1px;color:var(--text-muted);">
                        ${time}
                    </span>
                    <span style="font-family:'JetBrains Mono',monospace;font-size:9px;
                                 color:var(--text-muted);">${item.camera_name}</span>
                </div>
                <div style="font-size:12px;font-weight:600;color:var(--text-bright);margin-bottom:5px;">
                    ${item.person_count} person(s) detected
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:4px;">
                    ${objs || `<span style="font-size:9px;color:var(--text-muted);">no objects</span>`}
                </div>
            </div>`;
        }).join('');
    }

    // ─── Detection object overlay ─────────────────────────────
    function showDetectionOverlay(data) {
        const list  = document.getElementById('detectionList');
        const count = document.getElementById('objectCount');
        if (!list || !count) return;

        const objs = data.display_objects || data.carried_objects || data.all_objects || [];
        count.textContent = String(data.object_count ?? objs.length ?? 0).padStart(2, '0');

        if (!objs.length) {
            list.innerHTML = `<span style="font-family:'JetBrains Mono',monospace;
                font-size:10px;color:var(--text-muted);letter-spacing:1px;">NO OBJECTS</span>`;
            return;
        }

        list.innerHTML = objs.map(o => `
            <span style="font-family:'JetBrains Mono',monospace;font-size:11px;font-weight:700;
                         padding:4px 12px;border-radius:6px;letter-spacing:0.5px;
                         background:${objColor(o)}18;color:${objColor(o)};
                         border:1px solid ${objColor(o)}33;text-transform:uppercase;">
                ${o}
            </span>
        `).join('');

        setTimeout(() => {
            list.innerHTML = '';
            count.textContent = '00';
        }, 5000);
    }

    // ─── Latest detection (polling) ───────────────────────────
    async function fetchLatestDetection() {
        try {
            const res = await fetch('/api/detections/latest', {
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            });
            if (!res.ok) return;
            const data = await res.json();
            if (data) showDetectionOverlay(data);
        } catch(_) {}
    }

    // ─── Clear log ────────────────────────────────────────────
    async function clearLog() {
        if (!confirm('Truncate all detection records? This cannot be undone.')) return;
        const res = await fetch('/api/detections', {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        if (res.ok) location.reload();
    }

    // ─── Real-Time WebSockets (Reverb) ────────────────────────
    const pusherScript = document.createElement('script');
    pusherScript.src = 'https://cdn.jsdelivr.net/npm/pusher-js@8.3.0/dist/web/pusher.min.js';
    pusherScript.onload = () => {
        const pusher = new Pusher('{{ env("REVERB_APP_KEY") }}', {
            wsHost: '{{ env("REVERB_HOST") }}',
            wsPort: {{ env("REVERB_PORT") }},
            forceTLS: false,
            enabledTransports: ['ws', 'wss'],
            cluster: 'mt1'
        });

        const channel = pusher.subscribe('detections');
        channel.bind('object.detected', function(data) {
            console.log('Real-time Event:', data);
            
            // Instantly sync UI
            if (typeof fetchReport === 'function') fetchReport();
            if (typeof showDetectionOverlay === 'function') showDetectionOverlay(data);
            
            // Trigger Global Alarm if person found
            if (data.person_count > 0 && typeof showAlert === 'function') {
                showAlert('URGENT: Individual detected at ' + data.camera_name);
            }
        });
    };
    document.head.appendChild(pusherScript);

    // Initial load
    fetchReport();
    fetchLatestDetection();
    
    // Safety fallback polling (much slower)
    setInterval(fetchReport, 30000); 
    setInterval(fetchLatestDetection, 15000);
</script>
@endpush
