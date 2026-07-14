<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>故障处理记录单 - {{ $workorder->ticket_no }}</title>
<style>
@page { size: A4; margin: 0; }
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Microsoft YaHei','SimSun',sans-serif; background:#e9ecef; color:#222; padding:20px; }
.no-print { text-align:center; margin:15px 0; }

.a4-sheet {
    width: 210mm;
    min-height: 297mm;
    max-width: 100%;
    margin: 0 auto;
    padding: 15mm 14mm;
    background: #fff;
    box-shadow: 0 0 8px rgba(0,0,0,0.12);
    font-size: 12pt;
    line-height: 1.5;
}
.a4-title {
    text-align: center;
    font-size: 22pt;
    font-weight: bold;
    letter-spacing: 8px;
    margin-bottom: 10px;
}
.record-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}
.record-table td {
    border: 1px solid #555;
    padding: 9px 10px;
    vertical-align: middle;
    word-break: break-word;
}
.td-label {
    background: #f0f0f0;
    font-weight: bold;
    text-align: center;
    width: 90px;
    white-space: nowrap;
}
.sat-display { display:flex; align-items:center; gap:14px; }
.sat-box {
    display:inline-block;
    border:1.5px solid #555;
    width:18px; height:18px;
    text-align:center; line-height:16px;
    margin-right:3px; font-size:12px;
}
.sat-checked::after { content:'✓'; }
.sat-text { margin-right:8px; }

.print-btn {
    background:#007bff; color:#fff; padding:10px 24px;
    border:none; border-radius:5px; font-size:15px; cursor:pointer;
}
.print-btn:hover { background:#0056b3; }

@media print {
    body { background:#fff; padding:0; }
    .no-print { display:none !important; }
    .a4-sheet { box-shadow:none; }
    .td-label { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
}
</style>
</head>
<body>
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

    $satVal = $workorder->user_satisfaction;
    $satMap = [1=>'满意', 2=>'一般', 3=>'不满意', 4=>'其它'];
    $visitMap = ['needed'=>'需要回访','not_needed'=>'不需要回访','visited'=>'已回访'];
@endphp

<div class="no-print">
    <button class="print-btn" onclick="window.print()"><i class="fas fa-print"></i> 打印记录单</button>
</div>

<div class="a4-sheet">
    <div class="a4-title">故 障 处 理 记 录 单</div>

    <table class="record-table">
        <tr>
            <td class="td-label">故障单号</td>
            <td>{{ $workorder->ticket_no }}</td>
            <td class="td-label">报障日期</td>
            <td>{{ $workorder->created_at ? $workorder->created_at->format('Y/m/d') : '' }}</td>
        </tr>
        <tr>
            <td class="td-label">报障人</td>
            <td>{{ $workorder->contact_name }}</td>
            <td class="td-label">联系方式</td>
            <td>{{ $workorder->contact_phone ?: '' }}</td>
        </tr>
        <tr>
            <td class="td-label">地 址</td>
            <td colspan="3">{{ $fullAddress }}</td>
        </tr>
        <tr>
            <td class="td-label">处理人</td>
            <td>{{ $workorder->assignee_name }}</td>
            <td class="td-label">处理日期</td>
            <td>{{ $workorder->resolved_at ? $workorder->resolved_at->format('Y/m/d') : '' }}</td>
        </tr>
        <tr>
            <td class="td-label">故障现象</td>
            <td colspan="3" style="height:80px;">{{ $workorder->description }}</td>
        </tr>
        <tr>
            <td class="td-label">处理方式</td>
            <td colspan="3" style="height:80px;">{{ $workorder->solution }}</td>
        </tr>
        <tr>
            <td class="td-label">解决方案</td>
            <td colspan="3" style="height:70px;">{{ $workorder->remarks ?: '已恢复正常' }}</td>
        </tr>
        @if($workorder->materials_usage && $workorder->materials_usage !== '无备件耗材使用')
        <tr>
            <td class="td-label">备件耗材</td>
            <td colspan="3" style="height:50px;">{{ $workorder->materials_usage }}</td>
        </tr>
        @endif
        <tr>
            <td class="td-label" style="width:90px;">用户满意度</td>
            <td colspan="3">
                <div class="sat-display">
                    @foreach([1,2,3,4] as $val)
                        <span class="sat-text">
                            <span class="sat-box {{ $satVal === $val ? 'sat-checked' : '' }}"></span>{{ $satMap[$val] }}
                        </span>
                    @endforeach
                </div>
            </td>
        </tr>
        <tr>
            <td class="td-label">回访情况</td>
            <td colspan="3">
                <div class="sat-display">
                    @foreach(['needed'=>'需要回访','not_needed'=>'不需要回访','visited'=>'已回访'] as $key => $label)
                        <span class="sat-text">
                            <span class="sat-box {{ $workorder->visit_status === $key ? 'sat-checked' : '' }}"></span>{{ $label }}
                        </span>
                    @endforeach
                </div>
            </td>
        </tr>
        <tr>
            <td class="td-label">意见和建议</td>
            <td colspan="3" style="height:70px;">{{ $workorder->user_feedback ?: '' }}</td>
        </tr>
        <tr>
            <td class="td-label" style="width:90px;">用户签字</td>
            <td style="height:110px; vertical-align:bottom;">
                @if($workorder->user_signature)
                    @if(str_starts_with($workorder->user_signature, 'data:image'))
                        <img src="{{ $workorder->user_signature }}" alt="签名" style="max-height:100px;">
                    @else
                        {{ $workorder->user_signature }}
                    @endif
                @endif
            </td>
            <td class="td-label">日 期</td>
            <td>{{ $workorder->user_signed_at ? $workorder->user_signed_at->format('Y/m/d') : date('Y/m/d') }}</td>
        </tr>
    </table>
</div>
</body>
</html>
