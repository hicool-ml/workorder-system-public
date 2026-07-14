@extends('layouts.app')

@section('title', '故障处理记录单 - ' . $workorder->ticket_no)

@section('content')
@php
    $addressParts = [];
    if($workorder->campus) {
        $addressParts[] = is_object($workorder->campus) ? ($workorder->campus->name ?? '') : $workorder->campus;
    }
    if($workorder->building) {
        if(is_numeric($workorder->building)) {
            $bld = \App\Models\Location::find($workorder->building);
            $addressParts[] = $bld ? $bld->name : $workorder->building;
        } else {
            $addressParts[] = $workorder->building;
        }
    }
    if($workorder->location_detail) {
        $addressParts[] = $workorder->location_detail;
    }
    $fullAddress = implode(' - ', $addressParts);

    $oldSatisfaction = old('satisfaction');
    $oldVisit = old('visit_status');
    $oldFeedback = old('feedback');
@endphp

<form method="POST" action="{{ route('workorders.signature.store', $workorder->id) }}" id="recordForm">
    @csrf

    {{-- 顶部栏 --}}
    <div class="rc-topbar">
        <a href="{{ route('workorders.show', $workorder->id) }}" class="rc-back">
            <i class="fas fa-chevron-left"></i>
        </a>
        <span class="rc-topbar-title">故障处理记录单</span>
    </div>

    <div class="rc-wrap">

        {{-- ========== 工单信息 ========== --}}
        <section class="rc-card rc-card--ref">
            <div class="rc-card-title"><i class="fas fa-clipboard-list"></i> 工单信息</div>
            <div class="rc-info-grid">
                <div class="rc-info-item">
                    <span class="rc-info-label">故障单号</span>
                    <span class="rc-info-value rc-mono">{{ $workorder->ticket_no }}</span>
                </div>
                <div class="rc-info-item">
                    <span class="rc-info-label">报障日期</span>
                    <span class="rc-info-value">{{ $workorder->created_at ? $workorder->created_at->format('Y/m/d') : '' }}</span>
                </div>
                <div class="rc-info-item">
                    <span class="rc-info-label">报障人</span>
                    <span class="rc-info-value">{{ $workorder->contact_name }}</span>
                </div>
                <div class="rc-info-item">
                    <span class="rc-info-label">联系方式</span>
                    <span class="rc-info-value">{{ $workorder->contact_phone ?: '' }}</span>
                </div>
                <div class="rc-info-item rc-col-full">
                    <span class="rc-info-label">地址</span>
                    <span class="rc-info-value">{{ $fullAddress }}</span>
                </div>
                <div class="rc-info-item">
                    <span class="rc-info-label">处理人</span>
                    <span class="rc-info-value">{{ $workorder->assignee_name }}</span>
                </div>
                <div class="rc-info-item">
                    <span class="rc-info-label">处理日期</span>
                    <span class="rc-info-value">{{ $workorder->resolved_at ? $workorder->resolved_at->format('Y/m/d') : '' }}</span>
                </div>
            </div>
        </section>

        {{-- ========== 处理详情 ========== --}}
        <section class="rc-card rc-card--ref">
            <div class="rc-card-title"><i class="fas fa-tools"></i> 处理详情</div>
            <div class="rc-body-list">
                <div class="rc-body-item">
                    <span class="rc-body-label">故障现象</span>
                    <p class="rc-body-text">{{ $workorder->description }}</p>
                </div>
                <div class="rc-body-item">
                    <span class="rc-body-label">处理方式</span>
                    <p class="rc-body-text">{{ $workorder->solution }}</p>
                </div>
                <div class="rc-body-item">
                    <span class="rc-body-label">解决方案</span>
                    <p class="rc-body-text">{{ $workorder->remarks ?: '已恢复正常' }}</p>
                </div>
                @if($workorder->materials_usage && $workorder->materials_usage !== '无备件耗材使用')
                <div class="rc-body-item">
                    <span class="rc-body-label">备件耗材</span>
                    <p class="rc-body-text">{{ $workorder->materials_usage }}</p>
                </div>
                @endif
            </div>
        </section>

        {{-- ========== 用户评价 ========== --}}
        <div class="rc-fill-banner"><i class="fas fa-edit"></i> 以下内容需要您填写</div>

        <section class="rc-card rc-card--form">
            <div class="rc-card-title"><i class="fas fa-star"></i> 用户评价</div>

            {{-- 满意度 --}}
            <div class="rc-field">
                <label class="rc-field-label">用户满意度 <span class="rc-req">*</span></label>
                <div class="sat-pills">
                    <label class="sat-pill sat-satisfied">
                        <input type="radio" name="satisfaction" value="1" {{ $oldSatisfaction == '1' ? 'checked' : '' }}>
                        <span class="sat-pill-inner"><i class="far fa-smile"></i> 满意</span>
                    </label>
                    <label class="sat-pill sat-neutral">
                        <input type="radio" name="satisfaction" value="2" {{ $oldSatisfaction == '2' ? 'checked' : '' }}>
                        <span class="sat-pill-inner"><i class="far fa-meh"></i> 一般</span>
                    </label>
                    <label class="sat-pill sat-unsatisfied">
                        <input type="radio" name="satisfaction" value="3" {{ $oldSatisfaction == '3' ? 'checked' : '' }}>
                        <span class="sat-pill-inner"><i class="far fa-frown"></i> 不满意</span>
                    </label>
                    <label class="sat-pill sat-other">
                        <input type="radio" name="satisfaction" value="4" {{ $oldSatisfaction == '4' ? 'checked' : '' }}>
                        <span class="sat-pill-inner"><i class="fas fa-pen"></i> 其它</span>
                    </label>
                </div>
                @error('satisfaction')<div class="rc-field-error">{{ $message }}</div>@enderror
                <div id="satOtherWrap" class="rc-other-wrap" style="display:none;">
                    <input type="text" name="satisfaction_other" id="satisfactionOther" class="rc-input"
                           value="{{ old('satisfaction_other') }}" placeholder="请填写其它满意度说明">
                </div>
            </div>

            <div class="rc-divider"></div>

            {{-- 回访情况 --}}
            <div class="rc-field">
                <label class="rc-field-label">回访情况</label>
                <div class="sat-pills">
                    <label class="sat-pill">
                        <input type="radio" name="visit_status" value="needed" {{ $oldVisit == 'needed' ? 'checked' : '' }}>
                        <span class="sat-pill-inner">需要回访</span>
                    </label>
                    <label class="sat-pill">
                        <input type="radio" name="visit_status" value="not_needed" {{ $oldVisit == 'not_needed' ? 'checked' : '' }}>
                        <span class="sat-pill-inner">不需要</span>
                    </label>
                    <label class="sat-pill">
                        <input type="radio" name="visit_status" value="visited" {{ $oldVisit == 'visited' ? 'checked' : '' }}>
                        <span class="sat-pill-inner">已回访</span>
                    </label>
                </div>
                @error('visit_status')<div class="rc-field-error">{{ $message }}</div>@enderror
            </div>

            <div class="rc-divider"></div>

            {{-- 意见和建议 --}}
            <div class="rc-field">
                <label class="rc-field-label">意见和建议 <span class="rc-req">*</span></label>
                <textarea name="feedback" rows="4" class="rc-textarea" placeholder="请填写您的意见和建议（必填）">{{ $oldFeedback }}</textarea>
                @error('feedback')<div class="rc-field-error">{{ $message }}</div>@enderror
            </div>
        </section>

        {{-- ========== 用户签字 ========== --}}
        <section class="rc-card rc-card--form">
            <div class="rc-card-title"><i class="fas fa-signature"></i> 用户签字</div>

            {{-- 签名预览区：签之前显示按钮，签之后显示缩略图 --}}
            <div class="sig-preview-zone" id="sigPreviewZone">
                @if($workorder->user_signature)
                    <img src="{{ $workorder->user_signature }}" alt="签名" class="sig-preview-img">
                @else
                    <div id="sigEmptyState" class="sig-empty">
                        <i class="fas fa-pen-nib"></i>
                        <p>尚未签名</p>
                    </div>
                    <img id="sigPreviewImg" src="" alt="签名" class="sig-preview-img" style="display:none;">
                @endif
            </div>

            <input type="hidden" name="signature" id="signatureInput" value="{{ $workorder->user_signature ?? '' }}">
            @error('signature')<div class="rc-field-error">{{ $message }}</div>@enderror

            <div class="sig-action-row">
                <button type="button" class="rc-btn rc-btn-primary" id="openSignBtn">
                    <i class="fas fa-pen-nib"></i> 点击签名
                </button>
                <button type="button" class="rc-btn rc-btn-light d-none" id="clearSignBtn">
                    <i class="fas fa-redo"></i> 重新签
                </button>
            </div>
            <div class="sig-date-line">签署日期：{{ date('Y/m/d') }}</div>
        </section>
    </div>

    {{-- 底部提交栏 --}}
    <div class="rc-submit-bar">
        <button type="submit" class="rc-submit-btn" id="submitBtn">
            <i class="fas fa-check-circle"></i> 提交记录单
        </button>
    </div>
</form>

{{-- ======== 全屏横向签名画布 ======== --}}
<div class="sig-overlay" id="sigOverlay">
    {{-- 签名界面 --}}
    <div class="sig-landscape-ui" id="sigLandscapeUI">
        <div class="sig-canvas-section">
            <div class="sig-overlay-top">
                <span class="sig-overlay-title">手写签名</span>
                <span class="sig-overlay-hint">请在白色区域签写您的姓名</span>
            </div>
            <div class="sig-overlay-canvas-area">
                <canvas id="sigCanvas"></canvas>
            </div>
        </div>
        <div class="sig-sidebar">
            <button type="button" class="sig-ov-btn sig-ov-clear" id="clearCanvas">
                <i class="fas fa-eraser"></i> 清除
            </button>
            <button type="button" class="sig-ov-btn sig-ov-undo" id="undoStroke">
                <i class="fas fa-undo"></i> 撤销
            </button>
            <button type="button" class="sig-ov-btn sig-ov-cancel" id="cancelSig">
                <i class="fas fa-times"></i> 取消
            </button>
            <button type="button" class="sig-ov-btn sig-ov-ok" id="confirmSig">
                <i class="fas fa-check"></i> 确认签名
            </button>
        </div>
    </div>
</div>
@endsection

@section('head')
<style>
/* ===== 顶部栏 ===== */
.rc-topbar { display:flex; align-items:center; gap:8px; height:52px; padding:0 14px; position:sticky; top:0; z-index:100; background:#fff; border-bottom:1px solid #eee; box-shadow:0 1px 4px rgba(0,0,0,.03); }
.rc-back { width:36px; height:36px; display:flex; align-items:center; justify-content:center; color:#333; text-decoration:none; border-radius:10px; font-size:18px; transition:background .2s; }
.rc-back:active { background:#f0f0f0; }
.rc-topbar-title { font-size:18px; font-weight:700; color:#111; }

/* ===== 容器 ===== */
.rc-wrap { max-width:640px; margin:0 auto; padding:14px 12px 100px; }

/* ===== 卡片 ===== */
.rc-card { background:#fff; border-radius:16px; margin-bottom:14px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.05); border:1px solid #f0f1f3; }
.rc-card-title { display:flex; align-items:center; gap:8px; padding:16px 18px 12px; font-size:16px; font-weight:700; color:#111; }
.rc-card-title i { color:#5b7cfa; font-size:18px; }
/* 参考卡片：只读信息（工单信息、处理详情），弱化灰调文档风格 */
.rc-card--ref { background:#f7f8fa; border:1px solid #e6e8eb; box-shadow:none; }
.rc-card--ref .rc-card-title { color:#6b7280; font-size:14px; font-weight:600; }
.rc-card--ref .rc-card-title i { color:#9ca3af; }
/* 表单卡片：需用户填写，白底 + 蓝色顶边 + 更强阴影 */
.rc-card--form { background:#fff; border:1px solid #d8e0ff; border-top:4px solid #5b7cfa; box-shadow:0 6px 20px rgba(91,124,250,.10); }
.rc-card--form .rc-card-title { color:#1e293b; font-weight:700; }
/* 填写提示横幅 */
.rc-fill-banner { display:flex; align-items:center; justify-content:center; gap:8px; max-width:640px; margin:6px auto 14px; padding:11px 16px; background:linear-gradient(135deg,#eef2ff,#e0e7ff); border-radius:12px; color:#4263eb; font-size:14px; font-weight:700; }
.rc-fill-banner i { font-size:16px; }

/* ===== 信息网格 ===== */
.rc-info-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px 16px; padding:0 18px 18px; }
.rc-info-item { display:flex; flex-direction:column; gap:3px; }
.rc-col-full { grid-column:1 / -1; }
.rc-info-label { font-size:11px; color:#9ca3af; font-weight:500; letter-spacing:.3px; }
.rc-info-value { font-size:14px; color:#334155; font-weight:400; word-break:break-all; line-height:1.5; padding-bottom:3px; border-bottom:1px dashed #d1d5db; }
.rc-mono { font-family:'SF Mono','Consolas',monospace; font-size:13px; }

/* ===== 处理详情 ===== */
.rc-body-list { padding:0 18px 18px; }
.rc-body-item { padding:12px 0; border-bottom:1px solid #f5f5f5; }
.rc-body-item:last-child { border-bottom:none; }
.rc-body-label { display:block; font-size:12px; font-weight:500; color:#9ca3af; margin-bottom:6px; }
.rc-body-text { font-size:14px; color:#334155; line-height:1.6; margin:0; background:#fff; border:1px solid #e2e6ea; border-radius:8px; padding:10px 12px; word-break:break-all; }

/* ===== 表单字段 ===== */
.rc-field { padding:16px 18px; }
.rc-divider { height:1px; background:#f5f5f5; margin:0 18px; }
.rc-divider { height:1px; background:#eef0f3; margin:0 18px; }
.rc-field-label { display:flex; align-items:center; gap:2px; font-size:15px; font-weight:700; color:#0f172a; margin-bottom:12px; }
.rc-req { color:#ef4444; font-size:16px; }

/* ===== 满意度胶囊按钮 ===== */
.sat-pills { display:flex; gap:8px; flex-wrap:wrap; }
.sat-pill { position:relative; cursor:pointer; user-select:none; flex:1; min-width:72px; }
.sat-pill input[type="radio"] { position:absolute; opacity:0; pointer-events:none; }
.sat-pill-inner {
    display:flex; align-items:center; justify-content:center; gap:5px;
    padding:12px 6px; border-radius:12px; border:2px solid #e8eaed; background:#fff;
    font-size:13px; color:#666; transition:all .2s; text-align:center; font-weight:500;
}
.sat-pill-inner i { font-size:16px; }
.sat-pill:active .sat-pill-inner { transform:scale(.96); }
.sat-satisfied input:checked + .sat-pill-inner { border-color:#22c55e; background:#f0fdf4; color:#15803d; }
.sat-neutral input:checked + .sat-pill-inner { border-color:#f59e0b; background:#fffbeb; color:#b45309; }
.sat-unsatisfied input:checked + .sat-pill-inner { border-color:#ef4444; background:#fef2f2; color:#b91c1c; }
.sat-other input:checked + .sat-pill-inner { border-color:#5b7cfa; background:#eff5ff; color:#3730a3; }
.sat-pill input:checked + .sat-pill-inner { border-color:#5b7cfa; background:#eff5ff; color:#3730a3; box-shadow:0 2px 8px rgba(0,0,0,.06); font-weight:600; }

.rc-other-wrap { margin-top:12px; }

/* ===== 输入框 ===== */
.rc-input {
    width:100%; border:2.5px solid #94a3b8; border-radius:10px; padding:14px 16px;
    font-size:16px; color:#0f172a; background:#fff; transition:all .2s; box-sizing:border-box;
    -webkit-appearance:none; font-weight:500;
}
.rc-input:focus { outline:none; border-color:#5b7cfa; box-shadow:0 0 0 4px rgba(91,124,250,.15); }
.rc-input::placeholder { color:#94a3b8; font-weight:400; }

.rc-textarea {
    width:100%; border:2.5px solid #94a3b8; border-radius:10px; padding:14px 16px;
    font-size:16px; color:#0f172a; background:#fff; resize:none; min-height:100px; line-height:1.6;
    transition:all .2s; box-sizing:border-box; -webkit-appearance:none; font-weight:500;
}
.rc-textarea:focus { outline:none; border-color:#5b7cfa; box-shadow:0 0 0 4px rgba(91,124,250,.15); }
.rc-textarea::placeholder { color:#94a3b8; font-weight:400; }

.rc-field-error { color:#ef4444; font-size:13px; margin-top:8px; font-weight:500; }

/* ===== 签名区 ===== */
.sig-preview-zone {
    min-height:80px; margin:0 18px; border-radius:12px;
    display:flex; align-items:center; justify-content:center;
    background:linear-gradient(135deg,#f8f9fb,#f0f2f5);
    border:2px dashed #d6dae0; position:relative; overflow:hidden;
    transition:border-color .2s;
}
.sig-preview-zone.has-sig { border-style:solid; border-color:#e0e4ea; }
.sig-empty { display:flex; flex-direction:column; align-items:center; gap:6px; color:#c0c6cc; }
.sig-empty i { font-size:32px; }
.sig-empty p { font-size:13px; margin:0; }
.sig-preview-img { max-height:72px; max-width:100%; object-fit:contain; }

.sig-action-row { display:flex; gap:8px; padding:12px 18px; }
.sig-date-line { padding:0 18px 16px; font-size:12px; color:#9aa0a6; text-align:right; }

/* ===== 按钮 ===== */
.rc-btn {
    flex:1; display:flex; align-items:center; justify-content:center; gap:6px;
    padding:13px 18px; border-radius:12px; border:none; font-size:15px; font-weight:600;
    cursor:pointer; transition:all .15s;
}
.rc-btn:active { transform:scale(.97); }
.rc-btn-primary { background:linear-gradient(135deg,#5b7cfa,#4263eb); color:#fff; box-shadow:0 3px 10px rgba(91,124,250,.25); }
.rc-btn-light { background:#f3f4f6; color:#666; border:1px solid #e5e7eb; }
.d-none { display:none !important; }

/* ===== 底部提交栏 ===== */
.rc-submit-bar { position:fixed; bottom:0; left:0; right:0; z-index:100; background:#fff; border-top:1px solid #eee; padding:12px 16px; padding-bottom:max(12px,env(safe-area-inset-bottom)); box-shadow:0 -2px 12px rgba(0,0,0,.04); }
.rc-submit-btn {
    width:100%; max-width:612px; margin:0 auto; display:flex; align-items:center; justify-content:center;
    gap:8px; padding:16px; border-radius:14px; border:none;
    background:linear-gradient(135deg,#22c55e,#16a34a); color:#fff; font-size:17px; font-weight:700;
    cursor:pointer; box-shadow:0 4px 16px rgba(34,197,94,.3); transition:all .15s;
}
.rc-submit-btn:active { transform:scale(.98); }

/* ===== 小屏适配 ===== */
@media (max-width:380px) {
    .rc-info-grid { grid-template-columns:1fr; }
    .sat-pill { min-width:calc(50% - 4px); }
}

/* ===== 横向全屏签名 ===== */
.sig-overlay {
    position:fixed; inset:0; background:#1a1a2e; z-index:9999;
    display:none; flex-direction:column;
}
.sig-overlay.active { display:flex; }

/* 签名 UI：左=画布区域，右=按钮侧栏 */
.sig-landscape-ui { display:flex; flex-direction:row; width:100%; height:100%; }
.sig-canvas-section { flex:1; display:flex; flex-direction:column; min-width:0; }

.sig-overlay-top { display:flex; justify-content:space-between; align-items:center; padding:10px 20px; background:rgba(255,255,255,.04); flex-shrink:0; }
.sig-overlay-title { color:#fff; font-size:16px; font-weight:600; }
.sig-overlay-hint { color:rgba(255,255,255,.4); font-size:13px; }

.sig-overlay-canvas-area { flex:1; display:flex; align-items:center; justify-content:center; padding:16px; min-height:0; }
.sig-overlay-canvas-area canvas {
    width:100%; height:100%; background:#fff; border-radius:8px; cursor:crosshair;
    touch-action:none; display:block; box-shadow:0 4px 24px rgba(0,0,0,.3);
}

/* 右侧按钮栏：纵向排列 */
.sig-sidebar { display:flex; flex-direction:column; gap:10px; padding:16px 12px; padding-bottom:max(16px,env(safe-area-inset-bottom)); flex-shrink:0; background:rgba(255,255,255,.06); justify-content:center; }
.sig-ov-btn {
    width:80px; display:flex; flex-direction:column; align-items:center; gap:5px;
    padding:16px 8px; border:none; border-radius:12px; font-size:13px; font-weight:600;
    cursor:pointer; transition:opacity .15s;
}
.sig-ov-btn i { font-size:20px; }
.sig-ov-btn:active { opacity:.7; }
.sig-ov-clear { background:#495057; color:#fff; }
.sig-ov-undo { background:#ffc107; color:#222; }
.sig-ov-cancel { background:#dc3545; color:#fff; }
.sig-ov-ok { background:#22c55e; color:#fff; }

/* 竖屏：整体旋转90deg CW 模拟横屏，锁死避免陀螺仪混乱 */
@media (orientation:portrait) {
    .sig-overlay.active .sig-landscape-ui {
        width:100vh; height:100vw;
        position:absolute; top:50%; left:50%;
        transform:translate(-50%,-50%) rotate(90deg);
    }
}
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {

    // ---- 满意度"其它"联动 ----
    var satRadios = document.querySelectorAll('input[name="satisfaction"]');
    var satOtherWrap = document.getElementById('satOtherWrap');
    function toggleSatOther() {
        var checked = document.querySelector('input[name="satisfaction"]:checked');
        satOtherWrap.style.display = (checked && checked.value === '4') ? '' : 'none';
    }
    satRadios.forEach(function(r) { r.addEventListener('change', toggleSatOther); });
    toggleSatOther();

    // ---- 签名画布逻辑 ----
    var overlay     = document.getElementById('sigOverlay');
    var canvas      = document.getElementById('sigCanvas');
    var ctx         = canvas.getContext('2d');
    var sigInput    = document.getElementById('signatureInput');
    var previewZone = document.getElementById('sigPreviewZone');
    var previewImg  = document.getElementById('sigPreviewImg');
    var emptyState  = document.getElementById('sigEmptyState');
    var clearBtn    = document.getElementById('clearSignBtn');
    var openBtn     = document.getElementById('openSignBtn');

    var strokes = [];
    var currentStroke = [];
    var isDrawing = false;
    var isRotated = false;

    function resizeCanvas() {
        var area = canvas.parentElement;
        var rect = area.getBoundingClientRect();
        var w = rect.width - 32;   // padding
        var h = rect.height - 32;
        if (isRotated) {
            // 旋转后 boundingBox 宽高互换，需要还原为逻辑尺寸
            canvas.width  = h;
            canvas.height = w;
        } else {
            canvas.width  = w;
            canvas.height = h;
        }
        redraw();
    }

    function redraw() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.strokeStyle = '#1a1a2e';
        ctx.lineWidth = 3;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        strokes.forEach(function(stroke) {
            if (stroke.length > 0) {
                ctx.beginPath();
                ctx.moveTo(stroke[0].x, stroke[0].y);
                stroke.forEach(function(p) { ctx.lineTo(p.x, p.y); });
                ctx.stroke();
            }
        });
    }

    function getPos(e) {
        var rect = canvas.getBoundingClientRect();
        var cx = e.clientX || (e.touches && e.touches[0].clientX);
        var cy = e.clientY || (e.touches && e.touches[0].clientY);
        var sx = cx - rect.left;
        var sy = cy - rect.top;
        if (!isRotated) {
            return { x: sx, y: sy };
        }
        // 90deg CW: screen-left→canvas-bottom, screen-top→canvas-left
        return {
            x: sy * canvas.width / rect.height,
            y: (rect.width - sx) * canvas.height / rect.width
        };
    }

    function startDraw(e) {
        e.preventDefault();
        isDrawing = true;
        currentStroke = [getPos(e)];
    }

    function draw(e) {
        e.preventDefault();
        if (!isDrawing) return;
        var p = getPos(e);
        currentStroke.push(p);
        ctx.beginPath();
        ctx.moveTo(currentStroke[0].x, currentStroke[0].y);
        currentStroke.forEach(function(pt) { ctx.lineTo(pt.x, pt.y); });
        ctx.stroke();
    }

    function endDraw(e) {
        e.preventDefault();
        if (!isDrawing) return;
        isDrawing = false;
        if (currentStroke.length > 0) {
            strokes.push(currentStroke);
            currentStroke = [];
        }
    }

    function openOverlay() {
        strokes = [];
        currentStroke = [];
        isRotated = window.matchMedia('(orientation:portrait)').matches;
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
        setTimeout(resizeCanvas, 150);
    }

    function closeOverlay() {
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    // 确认签名：裁剪空白后生成 PNG，关闭遮罩，显示缩略图
    function confirmSignature() {
        if (strokes.length === 0) { alert('请先签名'); return; }

        // 渲染到临时画布并裁剪空白边界
        var tmp = document.createElement('canvas');
        tmp.width  = canvas.width;
        tmp.height = canvas.height;
        var tctx = tmp.getContext('2d');
        tctx.fillStyle = '#fff';
        tctx.fillRect(0, 0, tmp.width, tmp.height);
        tctx.strokeStyle = '#1a1a2e';
        tctx.lineWidth = 3;
        tctx.lineCap = 'round';
        tctx.lineJoin = 'round';
        strokes.forEach(function(stroke) {
            if (stroke.length > 0) {
                tctx.beginPath();
                tctx.moveTo(stroke[0].x, stroke[0].y);
                stroke.forEach(function(p) { tctx.lineTo(p.x, p.y); });
                tctx.stroke();
            }
        });

        // 裁剪空白
        var imgData = tctx.getImageData(0, 0, tmp.width, tmp.height);
        var minX = tmp.width, minY = tmp.height, maxX = 0, maxY = 0;
        var found = false;
        for (var y = 0; y < tmp.height; y++) {
            for (var x = 0; x < tmp.width; x++) {
                var idx = (y * tmp.width + x) * 4;
                if (imgData.data[idx] < 200 || imgData.data[idx+1] < 200 || imgData.data[idx+2] < 200) {
                    found = true;
                    if (x < minX) minX = x;
                    if (x > maxX) maxX = x;
                    if (y < minY) minY = y;
                    if (y > maxY) maxY = y;
                }
            }
        }
        if (!found) { alert('请先签名'); return; }

        var pad = 8;
        minX = Math.max(0, minX - pad);
        minY = Math.max(0, minY - pad);
        maxX = Math.min(tmp.width, maxX + pad);
        maxY = Math.min(tmp.height, maxY + pad);
        var cropW = maxX - minX;
        var cropH = maxY - minY;

        var cropped = document.createElement('canvas');
        cropped.width = cropW;
        cropped.height = cropH;
        var cctx = cropped.getContext('2d');
        cctx.drawImage(tmp, minX, minY, cropW, cropH, 0, 0, cropW, cropH);

        var dataURL = cropped.toDataURL('image/png');
        sigInput.value = dataURL;

        // 显示缩略图
        if (previewImg) {
            previewImg.src = dataURL;
            previewImg.style.display = '';
        }
        if (emptyState) emptyState.style.display = 'none';
        previewZone.classList.add('has-sig');
        if (clearBtn) clearBtn.classList.remove('d-none');
        if (openBtn) openBtn.innerHTML = '<i class="fas fa-redo"></i> 重新签名';

        closeOverlay();
    }

    // 清除签名
    function clearSignature() {
        sigInput.value = '';
        if (previewImg) { previewImg.src = ''; previewImg.style.display = 'none'; }
        if (emptyState) emptyState.style.display = '';
        previewZone.classList.remove('has-sig');
        if (clearBtn) clearBtn.classList.add('d-none');
        if (openBtn) openBtn.innerHTML = '<i class="fas fa-pen-nib"></i> 点击签名';
    }

    // ---- 事件绑定 ----
    canvas.addEventListener('mousedown', startDraw);
    canvas.addEventListener('mousemove', draw);
    canvas.addEventListener('mouseup', endDraw);
    canvas.addEventListener('mouseleave', endDraw);
    canvas.addEventListener('touchstart', startDraw, { passive: false });
    canvas.addEventListener('touchmove', draw, { passive: false });
    canvas.addEventListener('touchend', endDraw, { passive: false });

    openBtn.addEventListener('click', openOverlay);
    document.getElementById('clearCanvas').addEventListener('click', function() { strokes = []; redraw(); });
    document.getElementById('undoStroke').addEventListener('click', function() { strokes.pop(); redraw(); });
    document.getElementById('cancelSig').addEventListener('click', closeOverlay);
    document.getElementById('confirmSig').addEventListener('click', confirmSignature);
    if (clearBtn) clearBtn.addEventListener('click', clearSignature);

    // 旋转 / resize 时如果遮罩打开则重设画布
    window.addEventListener('orientationchange', function() {
        if (overlay.classList.contains('active')) {
            isRotated = window.matchMedia('(orientation:portrait)').matches;
            setTimeout(resizeCanvas, 300);
        }
    });
    window.addEventListener('resize', function() {
        if (overlay.classList.contains('active')) {
            isRotated = window.matchMedia('(orientation:portrait)').matches;
            setTimeout(resizeCanvas, 300);
        }
    });

    // 表单提交校验
    document.getElementById('recordForm').addEventListener('submit', function(e) {
        var sat = document.querySelector('input[name="satisfaction"]:checked');
        if (!sat) { e.preventDefault(); alert('请选择用户满意度'); return false; }
        if (!sigInput.value) { e.preventDefault(); alert('请完成手写签名'); return false; }
    });
});
</script>
@endsection
