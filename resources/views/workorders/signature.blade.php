@extends('layouts.app')

@section('title', '工单签单 - ' . $workorder->ticket_no)

@section('content')
<!-- 全屏横屏签名界面 -->
<div class="fullscreen-signature-modal" id="fullscreenSignatureModal">
    <div class="signature-header">
        <div class="signature-title">手写签名</div>
        <button type="button" class="signature-close" id="closeSignatureModal">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="signature-body">
        <div class="signature-canvas-container">
            <canvas id="fullscreenSignatureCanvas" class="fullscreen-signature-canvas"></canvas>
        </div>
    </div>
    <div class="signature-footer">
        <button type="button" class="signature-btn signature-btn-clear" id="clearFullscreenSignature">
            <i class="fas fa-eraser"></i> 清除
        </button>
        <button type="button" class="signature-btn signature-btn-undo" id="undoFullscreenSignature">
            <i class="fas fa-undo"></i> 撤销
        </button>
        <button type="button" class="signature-btn signature-btn-cancel" id="cancelFullscreenSignature">
            <i class="fas fa-times"></i> 取消
        </button>
        <button type="button" class="signature-btn signature-btn-confirm" id="confirmFullscreenSignature">
            <i class="fas fa-check"></i> 确认签名
        </button>
    </div>
</div>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">工单签单</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('workorders.show', $workorder->id) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> 返回工单
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-signature"></i> 工单签单确认
                    <span class="badge bg-primary ms-2">{{ $workorder->ticket_no }}</span>
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('workorders.signature.store', $workorder->id) }}" id="signatureForm">
                    @csrf
                    
                    <!-- 工单基本信息 -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="fas fa-info-circle"></i> 工单基本信息</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">工单编号</label>
                                        <div class="form-control-plaintext">{{ $workorder->ticket_no }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">地址</label>
                                        <div class="form-control-plaintext">
                                            @php
                                                $addressParts = [];
                                                
                                                // 添加校区
                                                if($workorder->campus) {
                                                    $addressParts[] = $workorder->campus;
                                                }
                                                
                                                // 添加楼栋
                                                if($workorder->building) {
                                                    $building = \App\Models\Location::find($workorder->building);
                                                    if ($building) {
                                                        $addressParts[] = $building->name;
                                                    } else {
                                                        $addressParts[] = $workorder->building;
                                                    }
                                                }
                                                
                                                // 添加门牌号
                                                if($workorder->location_detail) {
                                                    $addressParts[] = $workorder->location_detail;
                                                }
                                                
                                                // 用"-"连接各部分
                                                echo implode(' - ', $addressParts);
                                            @endphp
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label class="form-label">故障描述</label>
                                        <div class="form-control-plaintext">{{ $workorder->description }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">解决方案</label>
                                        <div class="form-control-plaintext">{{ $workorder->solution }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">处理人</label>
                                        <div class="form-control-plaintext">{{ $workorder->assignee_name }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 满意度评分 -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="fas fa-star"></i> 满意度评分 <span class="text-danger">*</span></h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12">
                                    <div class="star-rating-container">
                                        <div class="star-rating" id="starRating">
                                            @for($i = 1; $i <= 5; $i++)
                                                <i class="far fa-star star" data-rating="{{ $i }}"></i>
                                            @endfor
                                        </div>
                                        <div class="rating-text" id="ratingText">请选择满意度评分</div>
                                        <input type="hidden" id="satisfaction" name="satisfaction" value="" required>
                                    </div>
                                    @error('satisfaction')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 用户反馈 -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="fas fa-comment"></i> 用户反馈</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="feedback" class="form-label">您的意见和建议（选填）</label>
                                <textarea class="form-control" id="feedback" name="feedback" rows="4" 
                                          placeholder="请输入您的意见和建议，帮助我们改进服务质量..."
                                          maxlength="1000">{{ old('feedback') }}</textarea>
                                <div class="form-text">
                                    <span id="feedbackCount">0</span>/1000 字符
                                </div>
                                @error('feedback')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <!-- 手写签名 -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="fas fa-pen"></i> 手写签名 <span class="text-danger">*</span></h6>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <div class="signature-preview-container" id="signaturePreviewContainer">
                                    <div class="signature-preview-placeholder" id="signaturePreviewPlaceholder">
                                        <i class="fas fa-signature fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">尚未签名</p>
                                        <button type="button" class="btn btn-primary btn-lg" id="startSignatureBtn">
                                            <i class="fas fa-pen"></i> 开始签名
                                        </button>
                                    </div>
                                    <div class="signature-preview-image d-none" id="signaturePreviewImage">
                                        <img src="" alt="签名预览" class="img-fluid">
                                        <div class="mt-3">
                                            <button type="button" class="btn btn-outline-primary me-2" id="reSignBtn">
                                                <i class="fas fa-redo"></i> 重新签名
                                            </button>
                                            <button type="button" class="btn btn-outline-danger" id="clearSignatureBtn">
                                                <i class="fas fa-trash"></i> 清除签名
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" id="signature" name="signature" required>
                            @error('signature')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <!-- 提交按钮 -->
                    <div class="d-flex justify-content-end">
                        <a href="{{ route('workorders.show', $workorder->id) }}" class="btn btn-secondary me-2">
                            <i class="fas fa-times"></i> 取消
                        </a>
                        <button type="submit" class="btn btn-primary" id="submitSignature">
                            <i class="fas fa-check"></i> 确认签单
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- 签单说明 -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">签单说明</h6>
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <li>请确认工单已按要求完成</li>
                    <li>请对本次服务进行满意度评分</li>
                    <li>请在签名区域手写签名</li>
                    <li>签名完成后将生成故障处理记录单</li>
                    <li>记录单包含完整的工单处理信息</li>
                </ul>
            </div>
        </div>
        
        <!-- 满意度说明 -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">满意度说明</h6>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <span class="badge bg-danger">1星</span>
                    <small>很不满意 - 服务质量很差</small>
                </div>
                <div class="mb-2">
                    <span class="badge bg-warning">2星</span>
                    <small>不满意 - 服务质量有待提高</small>
                </div>
                <div class="mb-2">
                    <span class="badge bg-info">3星</span>
                    <small>一般 - 服务质量一般</small>
                </div>
                <div class="mb-2">
                    <span class="badge bg-primary">4星</span>
                    <small>满意 - 服务质量良好</small>
                </div>
                <div class="mb-2">
                    <span class="badge bg-success">5星</span>
                    <small>非常满意 - 服务质量优秀</small>
                </div>
            </div>
        </div>
        
        <!-- 签名提示 -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">签名提示</h6>
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <li>使用鼠标或触摸屏进行签名</li>
                    <li>签名应清晰可辨认</li>
                    <li>可以点击"撤销"重新签名</li>
                    <li>点击"清除"清空签名区域</li>
                    <li>提交前请确保签名完整</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
.signature-preview-container {
    border: 2px dashed #ccc;
    border-radius: 8px;
    background-color: #f9f9f9;
    padding: 20px;
    min-height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.signature-preview-placeholder {
    text-align: center;
    color: #999;
}

.signature-preview-image {
    text-align: center;
    width: 100%;
}

.signature-preview-image img {
    max-height: 150px;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 10px;
    background-color: #fff;
}

/* 全屏横屏签名界面 */
.fullscreen-signature-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background-color: rgba(0, 0, 0, 0.95);
    z-index: 9999;
    display: none;
    flex-direction: column;
}

.fullscreen-signature-modal.active {
    display: flex;
}

.signature-header {
    background-color: rgba(255, 255, 255, 0.1);
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: white;
}

.signature-title {
    font-size: 18px;
    font-weight: bold;
}

.signature-close {
    background: none;
    border: none;
    color: white;
    font-size: 24px;
    cursor: pointer;
    padding: 0;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: background-color 0.3s;
}

.signature-close:hover {
    background-color: rgba(255, 255, 255, 0.2);
}

.signature-body {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.signature-canvas-container {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    width: 90%;
    max-width: 800px;
    height: 400px;
    position: relative;
}

.fullscreen-signature-canvas {
    width: 100%;
    height: 100%;
    border-radius: 8px;
    cursor: crosshair;
    touch-action: none;
}

.signature-footer {
    background-color: rgba(255, 255, 255, 0.1);
    padding: 15px 20px;
    display: flex;
    justify-content: center;
    gap: 15px;
}

.signature-btn {
    min-width: 120px;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s;
}

.signature-btn-clear {
    background-color: #6c757d;
    color: white;
}

.signature-btn-clear:hover {
    background-color: #5a6268;
}

.signature-btn-undo {
    background-color: #ffc107;
    color: #212529;
}

.signature-btn-undo:hover {
    background-color: #e0a800;
}

.signature-btn-confirm {
    background-color: #28a745;
    color: white;
}

.signature-btn-confirm:hover {
    background-color: #218838;
}

.signature-btn-cancel {
    background-color: #dc3545;
    color: white;
}

.signature-btn-cancel:hover {
    background-color: #c82333;
}

/* 横屏优化 */
@media (orientation: landscape) {
    .signature-canvas-container {
        height: 300px;
    }
}

/* 移动设备适配 */
@media (max-width: 768px) {
    .signature-canvas-container {
        width: 95%;
        height: 250px;
    }
    
    .signature-btn {
        min-width: 100px;
        font-size: 14px;
        padding: 8px 15px;
    }
    
    .signature-footer {
        flex-wrap: wrap;
        gap: 10px;
    }
}

/* 满意度评分样式 */
.star-rating-container {
    text-align: center;
}

.star-rating {
    display: inline-flex;
    font-size: 48px;
    cursor: pointer;
    margin-bottom: 15px;
}

.star {
    color: #ddd;
    transition: color 0.2s ease;
    margin: 0 5px;
}

.star:hover,
.star.hover {
    color: #ffc107;
}

.star.selected {
    color: #ffc107;
}

.rating-text {
    font-size: 16px;
    color: #666;
    margin-top: 10px;
    min-height: 24px;
}

/* 全屏签名模式样式 */
.fullscreen-signature {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background-color: rgba(0, 0, 0, 0.9);
    z-index: 9999;
    display: flex;
    flex-direction: column;
    padding: 20px;
}

.fullscreen-signature-title {
    padding: 15px 0;
    text-align: center;
}

.fullscreen-signature .signature-container {
    flex: 1;
    border: 3px solid #fff;
    background-color: #fff;
    margin-bottom: 20px;
    position: relative;
}

.fullscreen-signature .signature-canvas {
    width: 100%;
    height: 100%;
    cursor: crosshair;
}

.fullscreen-signature .signature-overlay {
    color: #666;
}

.fullscreen-signature-controls {
    display: flex;
    justify-content: center;
    gap: 15px;
    padding: 15px;
    background-color: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
}

.fullscreen-signature-controls button {
    min-width: 120px;
}

@media (max-width: 768px) {
    .satisfaction-rating {
        flex-direction: column;
        gap: 10px;
    }
    
    .signature-canvas {
        height: 150px;
    }
    
    .fullscreen-signature-controls {
        flex-wrap: wrap;
    }
    
    .fullscreen-signature-controls button {
        min-width: 100px;
        font-size: 14px;
    }
}
</style>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // 满意度评分功能
    const stars = document.querySelectorAll('.star');
    const ratingText = document.getElementById('ratingText');
    const satisfactionInput = document.getElementById('satisfaction');
    const ratingTexts = [
        '请选择满意度评分',
        '很不满意 - 服务质量很差',
        '不满意 - 服务质量有待提高',
        '一般 - 服务质量一般',
        '满意 - 服务质量良好',
        '非常满意 - 服务质量优秀'
    ];
    
    // 设置星星评分
    function setRating(rating) {
        // 更新星星显示
        stars.forEach((star, index) => {
            if (index < rating) {
                star.classList.remove('far');
                star.classList.add('fas', 'selected');
            } else {
                star.classList.remove('fas', 'selected');
                star.classList.add('far');
            }
        });
        
        // 更新评分文本
        ratingText.textContent = ratingTexts[rating];
        
        // 更新隐藏字段值
        satisfactionInput.value = rating;
    }
    
    // 星星点击事件
    stars.forEach(star => {
        star.addEventListener('click', function() {
            const rating = parseInt(this.dataset.rating);
            setRating(rating);
        });
        
        // 星星悬停效果
        star.addEventListener('mouseenter', function() {
            const rating = parseInt(this.dataset.rating);
            stars.forEach((s, index) => {
                if (index < rating) {
                    s.classList.add('hover');
                } else {
                    s.classList.remove('hover');
                }
            });
            ratingText.textContent = ratingTexts[rating];
        });
    });
    
    // 鼠标离开星级评分区域时恢复当前评分状态
    document.querySelector('.star-rating').addEventListener('mouseleave', function() {
        const currentRating = parseInt(satisfactionInput.value) || 0;
        setRating(currentRating);
    });
    
    // 签名相关变量
    const signatureModal = document.getElementById('fullscreenSignatureModal');
    const signatureCanvas = document.getElementById('fullscreenSignatureCanvas');
    const signatureCtx = signatureCanvas.getContext('2d');
    const signatureInput = document.getElementById('signature');
    const signaturePreviewContainer = document.getElementById('signaturePreviewContainer');
    const signaturePreviewPlaceholder = document.getElementById('signaturePreviewPlaceholder');
    const signaturePreviewImage = document.getElementById('signaturePreviewImage');
    const signaturePreviewImg = signaturePreviewImage.querySelector('img');
    
    let isDrawing = false;
    let strokes = [];
    let currentStroke = [];
    
    // 设置画布大小
    function resizeCanvas() {
        const container = signatureCanvas.parentElement;
        const rect = container.getBoundingClientRect();
        signatureCanvas.width = rect.width;
        signatureCanvas.height = rect.height;
        redrawCanvas();
    }
    
    // 重绘画布
    function redrawCanvas() {
        signatureCtx.clearRect(0, 0, signatureCanvas.width, signatureCanvas.height);
        signatureCtx.strokeStyle = '#000';
        signatureCtx.lineWidth = 2;
        signatureCtx.lineCap = 'round';
        signatureCtx.lineJoin = 'round';
        
        strokes.forEach(stroke => {
            if (stroke.length > 0) {
                signatureCtx.beginPath();
                signatureCtx.moveTo(stroke[0].x, stroke[0].y);
                stroke.forEach(point => {
                    signatureCtx.lineTo(point.x, point.y);
                });
                signatureCtx.stroke();
            }
        });
    }
    
    // 获取鼠标/触摸位置
    function getPosition(e) {
        const rect = signatureCanvas.getBoundingClientRect();
        const x = (e.clientX || e.touches[0].clientX) - rect.left;
        const y = (e.clientY || e.touches[0].clientY) - rect.top;
        return { x, y };
    }
    
    // 开始绘制
    function startDrawing(e) {
        e.preventDefault();
        isDrawing = true;
        currentStroke = [getPosition(e)];
    }
    
    // 绘制
    function draw(e) {
        e.preventDefault();
        if (!isDrawing) return;
        
        const point = getPosition(e);
        currentStroke.push(point);
        
        signatureCtx.beginPath();
        signatureCtx.moveTo(currentStroke[0].x, currentStroke[0].y);
        currentStroke.forEach(p => {
            signatureCtx.lineTo(p.x, p.y);
        });
        signatureCtx.stroke();
    }
    
    // 结束绘制
    function stopDrawing(e) {
        e.preventDefault();
        if (!isDrawing) return;
        
        isDrawing = false;
        if (currentStroke.length > 0) {
            strokes.push(currentStroke);
            currentStroke = [];
        }
    }
    
    // 清除签名
    function clearSignature() {
        strokes = [];
        currentStroke = [];
        redrawCanvas();
    }
    
    // 撤销最后一笔
    function undoSignature() {
        if (strokes.length > 0) {
            strokes.pop();
            redrawCanvas();
        }
    }
    
    // 验证签名
    function validateSignature() {
        if (strokes.length === 0) {
            alert('请先完成签名');
            return false;
        }
        return true;
    }
    
    // 显示签名预览
    function showSignaturePreview() {
        if (strokes.length === 0) return;
        
        // 创建临时画布生成签名图片
        const tempCanvas = document.createElement('canvas');
        const tempCtx = tempCanvas.getContext('2d');
        tempCanvas.width = signatureCanvas.width;
        tempCanvas.height = signatureCanvas.height;
        
        // 复制签名到临时画布
        tempCtx.strokeStyle = '#000';
        tempCtx.lineWidth = 2;
        tempCtx.lineCap = 'round';
        tempCtx.lineJoin = 'round';
        
        strokes.forEach(stroke => {
            if (stroke.length > 0) {
                tempCtx.beginPath();
                tempCtx.moveTo(stroke[0].x, stroke[0].y);
                stroke.forEach(point => {
                    tempCtx.lineTo(point.x, point.y);
                });
                tempCtx.stroke();
            }
        });
        
        // 转换为图片并显示
        const dataURL = tempCanvas.toDataURL();
        signaturePreviewImg.src = dataURL;
        signatureInput.value = dataURL;
        
        // 切换显示
        signaturePreviewPlaceholder.classList.add('d-none');
        signaturePreviewImage.classList.remove('d-none');
    }
    
    // 清除签名预览
    function clearSignaturePreview() {
        signaturePreviewPlaceholder.classList.remove('d-none');
        signaturePreviewImage.classList.add('d-none');
        signatureInput.value = '';
        clearSignature();
    }
    
    // 打开签名模态框
    function openSignatureModal() {
        signatureModal.classList.add('active');
        resizeCanvas();
        
        // 防止页面滚动
        document.body.style.overflow = 'hidden';
    }
    
    // 关闭签名模态框
    function closeSignatureModal() {
        signatureModal.classList.remove('active');
        
        // 恢复页面滚动
        document.body.style.overflow = '';
    }
    
    // 绑定画布事件
    signatureCanvas.addEventListener('mousedown', startDrawing);
    signatureCanvas.addEventListener('mousemove', draw);
    signatureCanvas.addEventListener('mouseup', stopDrawing);
    signatureCanvas.addEventListener('mouseout', stopDrawing);
    
    // 触摸事件
    signatureCanvas.addEventListener('touchstart', startDrawing);
    signatureCanvas.addEventListener('touchmove', draw);
    signatureCanvas.addEventListener('touchend', stopDrawing);
    
    // 绑定按钮事件
    document.getElementById('startSignatureBtn').addEventListener('click', openSignatureModal);
    document.getElementById('closeSignatureModal').addEventListener('click', closeSignatureModal);
    document.getElementById('clearFullscreenSignature').addEventListener('click', clearSignature);
    document.getElementById('undoFullscreenSignature').addEventListener('click', undoSignature);
    document.getElementById('cancelFullscreenSignature').addEventListener('click', closeSignatureModal);
    document.getElementById('confirmFullscreenSignature').addEventListener('click', function() {
        if (validateSignature()) {
            showSignaturePreview();
            closeSignatureModal();
        }
    });
    
    // 重新签名按钮
    document.getElementById('reSignBtn').addEventListener('click', function() {
        clearSignaturePreview();
        openSignatureModal();
    });
    
    // 清除签名按钮
    document.getElementById('clearSignatureBtn').addEventListener('click', clearSignaturePreview);
    
    // 表单提交验证
    document.getElementById('signatureForm').addEventListener('submit', function(e) {
        if (!signatureInput.value) {
            e.preventDefault();
            alert('请先完成签名');
            return false;
        }
        
        // 显示加载状态
        const submitBtn = document.getElementById('submitSignature');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 提交中...';
        submitBtn.disabled = true;
        
        // 如果提交失败，恢复按钮状态
        setTimeout(() => {
            if (submitBtn.disabled) {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        }, 5000);
    });
    
    // 反馈字符计数
    const feedbackTextarea = document.getElementById('feedback');
    const feedbackCount = document.getElementById('feedbackCount');
    
    feedbackTextarea.addEventListener('input', function() {
        const length = this.value.length;
        feedbackCount.textContent = length;
        
        if (length > 1000) {
            this.value = this.value.substring(0, 1000);
            feedbackCount.textContent = 1000;
        }
    });
    
    // 初始化字符计数
    feedbackCount.textContent = feedbackTextarea.value.length;
    
    // ESC键关闭模态框
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && signatureModal.classList.contains('active')) {
            closeSignatureModal();
        }
    });
});
</script>
@endsection