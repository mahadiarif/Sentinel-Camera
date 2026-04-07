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
                <input type="text" name="name" class="form-control camera-input" value="{{ $nameValue }}" placeholder="Main Gate Entrance" required>
            </div>
            <div class="col-lg-4">
                <label class="camera-label">Location</label>
                <input type="text" name="location" class="form-control camera-input" value="{{ $locationValue }}" placeholder="Gate 1 / Lobby / Parking" required>
            </div>
            <div class="col-lg-2">
                <label class="camera-label">Camera Type</label>
                <select name="type" class="form-select camera-input" required>
                    <option value="rtsp" @selected($typeValue === 'rtsp')>Network / IP</option>
                    <option value="usb" @selected($typeValue === 'usb')>USB</option>
                </select>
            </div>
            <div class="col-lg-2">
                <label class="camera-label">Status</label>
                <select name="status" class="form-select camera-input" required>
                    <option value="active" @selected($statusValue === 'active')>Active</option>
                    <option value="inactive" @selected($statusValue === 'inactive')>Inactive</option>
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
                    <option value="preset" @selected(($settings['config_mode'] ?? 'preset') === 'preset')>Vendor Preset</option>
                    <option value="direct" @selected(($settings['config_mode'] ?? 'preset') === 'direct')>Direct URL</option>
                </select>
            </div>
            <div class="col-lg-5" data-role="vendor-only">
                <label class="camera-label">Vendor Profile</label>
                <select name="vendor" class="form-select camera-input">
                    @foreach($vendorProfiles as $vendorKey => $vendorProfile)
                    <option value="{{ $vendorKey }}" @selected(($settings['vendor'] ?? 'generic') === $vendorKey)>{{ $vendorProfile['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-4" data-role="preset-fields">
                <label class="camera-label">Protocol</label>
                <select name="network_protocol" class="form-select camera-input">
                    <option value="rtsp" @selected(($settings['network_protocol'] ?? 'rtsp') === 'rtsp')>RTSP</option>
                    <option value="http" @selected(($settings['network_protocol'] ?? 'rtsp') === 'http')>HTTP</option>
                    <option value="https" @selected(($settings['network_protocol'] ?? 'rtsp') === 'https')>HTTPS</option>
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
                <input type="text" name="host" class="form-control camera-input" value="{{ $settings['host'] ?? '' }}" placeholder="192.168.1.100">
            </div>
            <div class="col-lg-2">
                <label class="camera-label">Port</label>
                <input type="number" name="port" class="form-control camera-input" value="{{ $settings['port'] ?? 554 }}" min="1" max="65535">
            </div>
            <div class="col-lg-2">
                <label class="camera-label">Channel</label>
                <input type="number" name="channel" class="form-control camera-input" value="{{ $settings['channel'] ?? 1 }}" min="1" max="64">
            </div>
            <div class="col-lg-4">
                <label class="camera-label">Stream Profile</label>
                <select name="stream_type" class="form-select camera-input">
                    <option value="main" @selected(($settings['stream_type'] ?? 'main') === 'main')>Main Stream</option>
                    <option value="sub" @selected(($settings['stream_type'] ?? 'main') === 'sub')>Sub Stream</option>
                </select>
            </div>
            <div class="col-lg-3">
                <label class="camera-label">Username</label>
                <input type="text" name="username" class="form-control camera-input" value="{{ $settings['username'] ?? '' }}" placeholder="admin">
            </div>
            <div class="col-lg-3">
                <label class="camera-label">Password</label>
                <input type="text" name="password" class="form-control camera-input" value="{{ $settings['password'] ?? '' }}" placeholder="camera password">
            </div>
            <div class="col-lg-6">
                <label class="camera-label">Custom Path Override</label>
                <input type="text" name="path" class="form-control camera-input font-mono" value="{{ $settings['path'] ?? '' }}" placeholder="/Streaming/Channels/101 or /stream1">
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
                <input type="text" name="source" class="form-control camera-input font-mono" value="{{ $sourceValue }}" placeholder="rtsp://... or http://... or USB index like 0">
                <div class="camera-field-help" data-role="network-only">
                    Direct URL mode keeps full manual control. Base HTTP URLs like <span class="font-mono">http://192.168.137.21:8080/</span> are normalized automatically.
                </div>
                <div class="camera-field-help" data-role="usb-only">
                    USB cameras must use a numeric device index such as <span class="font-mono">0</span> or <span class="font-mono">1</span>.
                </div>
            </div>
        </div>
    </div>

    @if($camera)
    <div class="camera-form-section camera-form-section--compact">
        <div class="row g-3 align-items-end">
            <div class="col-lg-4">
                <label class="camera-label">Frame Target</label>
                <div class="camera-static-value font-mono">cam_{{ $camera->id }}.jpg</div>
            </div>
            <div class="col-lg-8">
                <div class="camera-chip-row">
                    <span class="camera-chip">ID #{{ $camera->id }}</span>
                    <span class="camera-chip">{{ strtoupper($camera->status) }}</span>
                    <span class="camera-chip">{{ strtoupper($camera->type) }}</span>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if(!$camera)
    <div class="camera-form-actions">
        <button type="submit" class="btn btn-accent camera-primary-btn">
            <i class="fas fa-plus me-2"></i> {{ $submitLabel }}
        </button>
    </div>
    @endif
</div>
