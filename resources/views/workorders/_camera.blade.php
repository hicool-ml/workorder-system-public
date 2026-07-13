{{-- Camera capture modal (shared) --}}
{{-- 隐藏的 capture 输入框，用于移动端非安全上下文（http）下直接调用系统相机 --}}
<input type="file" id="nativeCameraInput" accept="image/*" capture="environment" class="hidden">
<div id="cameraModal" class="hidden fixed inset-0 z-[70] items-center justify-center p-4 bg-black/80" data-modal>
    <div class="card w-full max-w-lg overflow-hidden">
        <div class="flex items-center justify-between px-5 py-3 border-b border-border">
            <h3 class="text-sm font-semibold text-ink">拍照</h3>
            <button type="button" onclick="closeCameraModal()" class="btn btn-ghost btn-icon btn-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-4">
            <div class="relative rounded-lg overflow-hidden" style="background:#000;aspect-ratio:4/3;">
                <video id="cameraVideo" autoplay playsinline class="w-full h-full object-cover"></video>
                <canvas id="cameraCanvas" class="hidden"></canvas>
            </div>
            <div id="cameraError" class="hidden text-center text-sm text-red-600 py-8"></div>
            <div class="flex items-center justify-center gap-3 mt-4">
                <button type="button" id="captureBtn" onclick="capturePhoto()" class="btn btn-primary">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="3" fill="currentColor" stroke="none"/></svg>
                    <span>拍照</span>
                </button>
                <button type="button" id="retakeBtn" onclick="retakePhoto()" class="btn btn-secondary hidden">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M1 4v6h6 M23 20v-6h-6 M3.51 9a9 9 0 0 1 14.85-3.36L23 10 M20.49 15a9 9 0 0 1-14.85 3.36L1 14"/></svg>
                    <span>重拍</span>
                </button>
                <button type="button" id="confirmBtn" onclick="confirmPhoto()" class="btn btn-primary hidden">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 6L9 17l-5-5"/></svg>
                    <span>使用照片</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Camera capture logic (getUserMedia)
var cameraStream = null;
var cameraPhotoData = null;
var cameraTargetInput = 'attachments';

function openCameraModal(targetInputId) {
    if (targetInputId) cameraTargetInput = targetInputId;
    // 非安全上下文（http 局域网访问）下 getUserMedia 被浏览器禁用，
    // 回退到 HTML 原生 capture 属性，直接调用系统相机
    if (!window.isSecureContext || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        var nativeInput = document.getElementById('nativeCameraInput');
        if (nativeInput) {
            nativeInput.value = '';
            nativeInput.onchange = function() {
                if (this.files && this.files.length > 0) {
                    var fileInput = document.getElementById(cameraTargetInput);
                    if (!fileInput) return;
                    var dt = new DataTransfer();
                    if (fileInput.files) { for (var j = 0; j < fileInput.files.length; j++) { dt.items.add(fileInput.files[j]); } }
                    for (var k = 0; k < this.files.length; k++) { dt.items.add(this.files[k]); }
                    fileInput.files = dt.files;
                    if (typeof handleAttachmentSelect === 'function') {
                        handleAttachmentSelect(fileInput);
                    }
                }
                this.onchange = null;
            };
            nativeInput.click();
        }
        return;
    }
    var modal = document.getElementById('cameraModal');
    modal.classList.remove('hidden'); modal.classList.add('flex');
    document.body.classList.add('overflow-hidden');
    document.getElementById('captureBtn').classList.remove('hidden');
    document.getElementById('retakeBtn').classList.add('hidden');
    document.getElementById('confirmBtn').classList.add('hidden');
    document.getElementById('cameraError').classList.add('hidden');
    document.getElementById('cameraVideo').classList.remove('hidden');
    var old = document.getElementById('capturedPhoto'); if (old) old.remove();

    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment', width: { ideal: 1920 }, height: { ideal: 1080 } } })
            .then(function(stream) {
                cameraStream = stream;
                document.getElementById('cameraVideo').srcObject = stream;
            })
            .catch(function(err) {
                document.getElementById('cameraError').textContent = '无法访问摄像头：' + err.message;
                document.getElementById('cameraError').classList.remove('hidden');
                document.getElementById('cameraVideo').classList.add('hidden');
                document.getElementById('captureBtn').classList.add('hidden');
            });
    } else {
        document.getElementById('cameraError').textContent = '当前浏览器不支持摄像头功能';
        document.getElementById('cameraError').classList.remove('hidden');
        document.getElementById('cameraVideo').classList.add('hidden');
        document.getElementById('captureBtn').classList.add('hidden');
    }
}

function closeCameraModal() {
    var modal = document.getElementById('cameraModal');
    modal.classList.add('hidden'); modal.classList.remove('flex');
    document.body.classList.remove('overflow-hidden');
    if (cameraStream) { cameraStream.getTracks().forEach(function(t) { t.stop(); }); cameraStream = null; }
    var video = document.getElementById('cameraVideo');
    if (video.srcObject) { video.srcObject = null; }
}

function capturePhoto() {
    var video = document.getElementById('cameraVideo');
    var canvas = document.getElementById('cameraCanvas');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    var ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0);
    cameraPhotoData = canvas.toDataURL('image/jpeg', 0.85);
    video.classList.add('hidden');
    var imgDisplay = document.createElement('img');
    imgDisplay.src = cameraPhotoData;
    imgDisplay.className = 'w-full h-full object-cover absolute inset-0';
    imgDisplay.id = 'capturedPhoto';
    video.parentElement.appendChild(imgDisplay);
    document.getElementById('captureBtn').classList.add('hidden');
    document.getElementById('retakeBtn').classList.remove('hidden');
    document.getElementById('confirmBtn').classList.remove('hidden');
    if (cameraStream) { cameraStream.getTracks().forEach(function(t) { t.stop(); }); cameraStream = null; }
}

function retakePhoto() {
    var imgEl = document.getElementById('capturedPhoto');
    if (imgEl) imgEl.remove();
    cameraPhotoData = null;
    var video = document.getElementById('cameraVideo');
    video.classList.remove('hidden');
    document.getElementById('captureBtn').classList.remove('hidden');
    document.getElementById('retakeBtn').classList.add('hidden');
    document.getElementById('confirmBtn').classList.add('hidden');
    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
            .then(function(stream) {
                cameraStream = stream;
                video.srcObject = stream;
            });
    }
}

function confirmPhoto() {
    if (!cameraPhotoData) return;
    var byteString = atob(cameraPhotoData.split(',')[1]);
    var ab = new ArrayBuffer(byteString.length);
    var ia = new Uint8Array(ab);
    for (var i = 0; i < byteString.length; i++) { ia[i] = byteString.charCodeAt(i); }
    var blob = new Blob([ab], { type: 'image/jpeg' });
    var fileName = 'photo_' + Date.now() + '.jpg';
    var file = new File([blob], fileName, { type: 'image/jpeg' });
    var fileInput = document.getElementById(cameraTargetInput);
    if (!fileInput) { closeCameraModal(); return; }
    var dt = new DataTransfer();
    if (fileInput.files) { for (var j = 0; j < fileInput.files.length; j++) { dt.items.add(fileInput.files[j]); } }
    dt.items.add(file);
    fileInput.files = dt.files;
    closeCameraModal();
    if (typeof handleAttachmentSelect === 'function') {
        handleAttachmentSelect(fileInput);
    } else {
        var nameDiv = document.getElementById(cameraTargetInput.replace(/_create|_edit/, '') + 'Name') || document.getElementById('attCreateName') || document.getElementById('attEditName');
        if (nameDiv) nameDiv.textContent = fileInput.files.length ? '已选择 ' + fileInput.files.length + ' 个文件' : '未选择文件';
    }
}
</script>
