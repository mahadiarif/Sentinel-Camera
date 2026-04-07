@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center pt-5">
        <div class="col-lg-8">
            <div class="card shadow-lg border-accent">
                <div class="card-header bg-dark d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0">
                        <i class="fas fa-microchip text-accent me-2"></i>
                        AI TRAINING ENGINE: {{ strtoupper($class->display_name) }}
                    </h5>
                    <span id="overallStatus" class="badge-surv bg-warning text-dark animated-pulse">PIPELINE: TRAINING</span>
                </div>
                <div class="card-body p-4">
                    
                    <!-- Progress Stats -->
                    <div class="row text-center mb-5 mt-3">
                        <div class="col-4">
                            <h6 class="text-dim x-small">EPOCH PROGRESS</h6>
                            <h2 id="epochText" class="mb-0">0 / {{ $class->training_epochs }}</h2>
                        </div>
                        <div class="col-4 border-start border-end border-border">
                            <h6 class="text-dim x-small">MODEL ACCURACY</h6>
                            <h2 id="accuracyText" class="mb-0 text-success">--</h2>
                        </div>
                        <div class="col-4">
                            <h6 class="text-dim x-small">LOSS VALUE</h6>
                            <h2 id="lossText" class="mb-0 text-accent">0.000</h2>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mb-5 px-3">
                        <div class="progress" style="height: 12px; background: rgba(255,255,255,0.05); border-radius: 6px;">
                            <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-accent" style="width: 0%"></div>
                        </div>
                        <div class="d-flex justify-content-between mt-2 small opacity-50 font-monospace">
                            <span>0% INITIALIZED</span>
                            <span id="percentText">0%</span>
                            <span>100% DEPLOYED</span>
                        </div>
                    </div>

                    <!-- Terminal log -->
                    <div class="bg-black p-4 rounded-3 border border-border font-monospace small" style="height: 250px; overflow-y: auto;" id="logTerminal">
                        <div class="text-success mb-2 small">[SYSTEM] HANDSHAKE SUCCESSFUL. GPU DETECTED.</div>
                        <div class="text-accent mb-2 small">[SYSTEM] LOADING BASE MODEL: yolov8n.pt...</div>
                        <div class="text-dim" id="logContent">
                            <!-- JS Populated -->
                            Waiting for training engine logs...
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-surface border-border p-3 d-flex justify-content-between align-items-center">
                    <div class="text-dim small italic">
                        <i class="fas fa-triangle-exclamation me-2 text-warn"></i> Do not close this terminal while training is active.
                    </div>
                    <a href="{{ route('training.index') }}" class="btn btn-sm btn-outline-light border-border px-4 py-2 opacity-50" id="returnBtn">
                        <i class="fas fa-arrow-left me-2"></i> BACK TO DASHBOARD
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const classId = {{ $class->id }};
    const logBox = document.getElementById('logContent');
    const logTerminal = document.getElementById('logTerminal');
    const progressBar = document.getElementById('progressBar');
    const epochText = document.getElementById('epochText');
    const accuracyText = document.getElementById('accuracyText');
    const lossText = document.getElementById('lossText');
    const percentText = document.getElementById('percentText');
    const statusBadge = document.getElementById('overallStatus');
    const totalEpochs = {{ $class->training_epochs }};

    async function updateStatus() {
        try {
            const res = await fetch(`/api/v1/training/classes/${classId}/status`);
            const data = await res.json();
            
            if (data.progress) {
                const p = data.progress;
                const progressPct = Math.round((p.current_epoch / totalEpochs) * 100);
                
                progressBar.style.width = progressPct + '%';
                percentText.innerText = progressPct + '%';
                epochText.innerText = `${p.current_epoch} / ${totalEpochs}`;
                
                if (p.accuracy) accuracyText.innerText = (p.accuracy * 100).toFixed(1) + '%';
                if (p.loss) lossText.innerText = p.loss.toFixed(4);

                // Append logs
                if (p.logs && p.logs.length > 0) {
                    logBox.innerHTML = '';
                    p.logs.forEach(line => {
                        const div = document.createElement('div');
                        div.className = 'mb-1 opacity-75';
                        div.innerText = `> ${line}`;
                        logBox.appendChild(div);
                    });
                    logTerminal.scrollTop = logTerminal.scrollHeight;
                }
            }

            // Handle States
            if (data.status === 'trained') {
                statusBadge.innerText = 'PIPELINE: COMPLETED';
                statusBadge.className = 'badge-surv bg-success';
                statusBadge.classList.remove('animated-pulse');
                progressBar.classList.remove('progress-bar-animated');
                progressBar.style.width = '100%';
                percentText.innerText = '100%';
                document.getElementById('returnBtn').classList.remove('opacity-50');
                showAlert('TRAINING COMPLETED SUCCESSFULLY. MODEL DEPLOYED.');
                return; // Stop polling
            }

            if (data.status === 'failed') {
                statusBadge.innerText = 'PIPELINE: FAILED';
                statusBadge.className = 'badge-surv bg-danger';
                statusBadge.classList.remove('animated-pulse');
                document.getElementById('returnBtn').classList.remove('opacity-50');
                showAlert('TRAINING PIPELINE FAILED. CHECK ENGINE LOGS.');
                return; // Stop polling
            }

            setTimeout(updateStatus, 3000);
        } catch (e) { 
            console.error('Status poll error:', e);
            setTimeout(updateStatus, 5000);
        }
    }

    updateStatus();
</script>
<style>
    .animated-pulse { animation: pulse 1.5s infinite; }
    @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.4; } 100% { opacity: 1; } }
    .x-small { font-size: 0.7rem; font-weight: 800; letter-spacing: 1px; margin-bottom: 8px; }
</style>
@endpush
@endsection
