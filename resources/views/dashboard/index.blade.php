@extends('layouts.app')

@section('content')
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
                        <h2 class="mb-0">{{ $stats['total_cameras'] }}</h2>
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
                        <h2 class="mb-0 text-accent">{{ $stats['detections_today'] }}</h2>
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
                        <h2 class="mb-0 text-danger">{{ $stats['alerts_today'] }}</h2>
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
                        <h2 class="mb-0" style="color: var(--accent3)">{{ $stats['trained_models'] }}</h2>
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
                    @forelse($breakdown as $b)
                        @php
                            $color = in_array(strtolower($b['name']), ['person']) ? 'var(--accent2)' : 
                                    (in_array(strtolower($b['name']), ['car','truck','bus']) ? 'var(--accent)' : 'var(--accent3)');
                            $pct = ($b['count'] / max($stats['detections_today'], 1)) * 100;
                        @endphp
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="small font-mono text-uppercase">{{ $b['name'] }}</span>
                                <span class="small fw-bold">{{ $b['count'] }}</span>
                            </div>
                            <div class="progress" style="height: 6px; background: rgba(255,255,255,0.05); border-radius: 3px;">
                                <div class="progress-bar" style="width: {{ $pct }}%; background: {{ $color }}; box-shadow: 0 0 10px {{ $color }}; border-radius: 3px;"></div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5 opacity-25">
                            <i class="fas fa-chart-pie fa-3x mb-3"></i>
                            <p class="small mb-0">NO ANALYTICS FOR TODAY</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Recent Logs Table (Modified to span 8 columns) -->
        <div class="col-md-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 small fw-bold">RECENT DETECTION LOGS</h5>
                    <a href="{{ route('gate.monitor') }}" class="btn btn-sm btn-outline-info border-0">
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
                            @forelse($recent_detections as $d)
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.03);">
                                <td class="ps-4 text-dim font-mono small">{{ $d->created_at->format('H:i:s') }}</td>
                                <td class="small fw-bold">{{ $d->camera_name }}</td>
                                <td>
                                    @foreach($d->detected_objects as $obj)
                                        @php
                                            $color = in_array($obj, ['person']) ? 'bg-accent2' : 
                                                    (in_array($obj, ['car','truck','bus']) ? 'bg-accent' : 'bg-warning text-dark');
                                        @endphp
                                        <span class="badge {{ $color }} x-small me-1">{{ $obj }}</span>
                                    @endforeach
                                </td>
                                <td>
                                    @if($d->person_detected)
                                        <span class="badge-surv bg-accent2">CRITICAL</span>
                                    @else
                                        <span class="badge-surv bg-accent text-dark">SECURE</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-5 text-dim">
                                    <i class="fas fa-inbox fa-3x mb-3 d-block opacity-25"></i>
                                    NO RECENT DETECTIONS
                                </td>
                            </tr>
                            @endforelse
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
@endsection
