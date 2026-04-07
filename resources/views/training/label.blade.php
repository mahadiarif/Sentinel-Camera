@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row align-items-center mb-4">
        <div class="col-md-6">
            <h2 class="text-white mb-0">LABELING TERMINAL</h2>
            <p class="text-dim opacity-75">DATASET: {{ $class->display_name }} ({{ $class->labeled_count }} / {{ $class->training_images_count }} LABELED)</p>
        </div>
        <div class="col-md-6 text-md-end">
            <input type="file" id="batchUpload" class="d-none" multiple accept="image/*" onchange="uploadImages(event)">
            <button class="btn btn-outline-accent px-4 fw-bold me-2" onclick="document.getElementById('batchUpload').click()">
                <i class="fas fa-upload me-2"></i> UPLOAD BATCH (MAX 20)
            </button>
            <a href="{{ route('training.index') }}" class="btn btn-surface2 border-border">
                <i class="fas fa-close me-2"></i> EXIT LABELER
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- LEFT: CANVAS LABELING TOOL -->
        <div class="col-lg-9">
            <div class="card h-100">
                <div class="card-header bg-dark d-flex justify-content-between align-items-center">
                    <span class="small font-monospace text-accent fw-bold" id="currentFileName">SELECT IMAGE FROM RIGHT PANEL</span>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-surface2 border-border" id="prevBtn" onclick="navigate(-1)"><i class="fas fa-chevron-left"></i></button>
                        <button class="btn btn-sm btn-surface2 border-border" id="nextBtn" onclick="navigate(1)"><i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>
                <div class="card-body p-0 position-relative bg-black d-flex align-items-center justify-content-center" style="min-height: 600px; overflow: hidden;">
                    <canvas id="labelCanvas" style="cursor: crosshair; max-width: 100%; height: auto;"></canvas>
                    <div id="noImagePrompt" class="position-absolute text-center text-dim opacity-25">
                        <i class="fas fa-images fa-4x mb-3"></i><br>
                        NO IMAGE SELECTED. CAPTURE DATA OR START UPLOADING.
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between align-items-center bg-surface py-3">
                    <div class="text-dim small">
                        <i class="fas fa-circle-info me-2"></i>
                        CLICK AND DRAG TO DRAW BOUNDING BOX AROUND <b>{{ strtoupper($class->display_name) }}</b>
                    </div>
                    <div class="btn-group gap-2">
                        <button class="btn btn-sm btn-surface2 border-border" onclick="clearCanvas()">CLEAR BOX</button>
                        <button class="btn btn-sm btn-accent px-4 py-2 text-dark fw-bold" id="saveLabelBtn" onclick="saveLabel()">SAVE LABEL & ADVANCE</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT: THUMBNAIL STRIP -->
        <div class="col-lg-3">
            <div class="card h-100">
                <div class="card-header">UNLABELED CAPTURES</div>
                <div class="card-body p-2 overflow-auto" style="max-height: 700px;" id="thumbnailContainer">
                    <!-- JS Populated -->
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const classId = {{ $class->id }};
    const canvas = document.getElementById('labelCanvas');
    const ctx = canvas.getContext('2d');
    let images = [];
    let currentIndex = -1;
    let currentImg = new Image();
    let drawing = false;
    let startX, startY, endX, endY;
    let currentBBox = null;

    // Load Initial Images
    async function fetchImages() {
        const res = await fetch(`/api/v1/training/classes/${classId}/images`);
        images = await res.json();
        renderThumbnails();
        if (currentIndex === -1 && images.length > 0) {
            // Select first unlabeled image or first image
            const firstUnlabeled = images.findIndex(img => !img.is_labeled);
            selectImage(firstUnlabeled !== -1 ? firstUnlabeled : 0);
        }
    }

    function renderThumbnails() {
        const container = document.getElementById('thumbnailContainer');
        container.innerHTML = '';
        images.forEach((img, idx) => {
            const div = document.createElement('div');
            div.className = `thumbnail-item mb-2 p-1 border rounded cursor-pointer ${idx === currentIndex ? 'border-accent' : 'border-border'}`;
            div.onclick = () => selectImage(idx);
            div.innerHTML = `
                <div class="position-relative">
                    <img src="${img.url}" class="img-fluid rounded w-100" style="height: 80px; object-fit: cover;">
                    ${img.is_labeled ? '<span class="badge bg-success position-absolute top-0 end-0 m-1">LABELED</span>' : ''}
                </div>
            `;
            container.appendChild(div);
        });
    }

    function selectImage(idx) {
        if (idx < 0 || idx >= images.length) return;
        currentIndex = idx;
        const imgObj = images[idx];
        document.getElementById('currentFileName').innerText = imgObj.id + '.jpg / TARGET: ' + '{{ strtoupper($class->display_name) }}';
        document.getElementById('noImagePrompt').style.display = 'none';
        
        currentImg = new Image();
        currentImg.src = imgObj.url;
        currentImg.onload = () => {
            canvas.width = currentImg.width > 1200 ? 1200 : currentImg.width; // Cap width for display
            canvas.height = (canvas.width / currentImg.width) * currentImg.height;
            
            // If already labeled, show existing box
            if (imgObj.is_labeled && imgObj.label_data && imgObj.label_data.bbox) {
                const b = imgObj.label_data.bbox;
                currentBBox = {
                    x: b[0] - b[2]/2,
                    y: b[1] - b[3]/2,
                    w: b[2],
                    h: b[3]
                };
            } else {
                currentBBox = null;
            }
            redraw();
            renderThumbnails();
        };
    }

    function redraw() {
        ctx.clearRect(0,0,canvas.width, canvas.height);
        ctx.drawImage(currentImg, 0, 0, canvas.width, canvas.height);
        
        if (currentBBox) {
            ctx.strokeStyle = '#00e5ff';
            ctx.lineWidth = 3;
            ctx.strokeRect(currentBBox.x * canvas.width, currentBBox.y * canvas.height, currentBBox.w * canvas.width, currentBBox.h * canvas.height);
            
            // Fill with translucent overlay outside the box
            ctx.fillStyle = 'rgba(0,0,0,0.3)';
            ctx.fillRect(0,0,canvas.width, currentBBox.y * canvas.height); // top
            ctx.fillRect(0,(currentBBox.y + currentBBox.h)*canvas.height, canvas.width, canvas.height); // bottom
            ctx.fillRect(0, currentBBox.y*canvas.height, currentBBox.x*canvas.width, currentBBox.h*canvas.height); // left
            ctx.fillRect((currentBBox.x + currentBBox.w)*canvas.width, currentBBox.y*canvas.height, canvas.width, currentBBox.h*canvas.height); // right
        }
    }

    // Interaction logic
    canvas.onmousedown = (e) => {
        drawing = true;
        const rect = canvas.getBoundingClientRect();
        const scaleX = canvas.width / rect.width;
        const scaleY = canvas.height / rect.height;
        startX = (e.clientX - rect.left) * scaleX;
        startY = (e.clientY - rect.top) * scaleY;
    };

    canvas.onmousemove = (e) => {
        if (!drawing) return;
        const rect = canvas.getBoundingClientRect();
        const scaleX = canvas.width / rect.width;
        const scaleY = canvas.height / rect.height;
        const x = (e.clientX - rect.left) * scaleX;
        const y = (e.clientY - rect.top) * scaleY;
        
        currentBBox = {
            x: Math.min(startX, x) / canvas.width,
            y: Math.min(startY, y) / canvas.height,
            w: Math.abs(startX - x) / canvas.width,
            h: Math.abs(startY - y) / canvas.height
        };
        redraw();
    };

    canvas.onmouseup = () => { drawing = false; };

    function clearCanvas() { currentBBox = null; redraw(); }

    async function saveLabel() {
        if (!currentBBox) { alert('Draw a box around the object first!'); return; }
        if (currentIndex === -1) return;
        
        const imgId = images[currentIndex].id;
        // YOLO center_x center_y width height
        const payload = {
            bbox: [
                currentBBox.x + currentBBox.w/2, 
                currentBBox.y + currentBBox.h/2, 
                currentBBox.w, 
                currentBBox.h
            ]
        };

        try {
            const res = await fetch(`/api/v1/training/images/${imgId}/label`, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(payload)
            });
            if (res.ok) {
                images[currentIndex].is_labeled = true;
                images[currentIndex].label_data = {bbox: payload.bbox};
                
                // Auto-advance
                const nextUnlabeled = images.findIndex((img, idx) => idx > currentIndex && !img.is_labeled);
                if (nextUnlabeled !== -1) {
                    selectImage(nextUnlabeled);
                } else {
                    alert('LABELED! ALL IMAGES COMPLETED.');
                    renderThumbnails();
                }
            }
        } catch (e) { alert('System communication error.'); }
    }

    async function uploadImages(e) {
        const files = e.target.files;
        if (files.length === 0) return;
        const formData = new FormData();
        for (let i = 0; i < files.length; i++) {
            formData.append('images[]', files[i]);
        }

        try {
            const res = await fetch(`/api/v1/training/classes/${classId}/upload`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                fetchImages();
            } else { alert('UPLOAD FAILED: ' + data.error); }
        } catch (e) { alert('System communication error.'); }
    }

    function navigate(delta) {
        const next = currentIndex + delta;
        if (next >= 0 && next < images.length) selectImage(next);
    }

    // Initial load
    fetchImages();
</script>
<style>
    .cursor-pointer { cursor: pointer; }
    .thumbnail-item { transition: all 0.2s ease; opacity: 0.8; }
    .thumbnail-item:hover { transform: scale(1.02); opacity: 1; background: var(--surface2); }
    .thumbnail-item.border-accent { opacity: 1; background: var(--surface2); }
</style>
@endpush
@endsection
