<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>{{ $pageTitle }}</title>
    <style>
        body {
            font-family: SimSun, "宋体", serif;
            font-size: 14px;
            line-height: 1.6;
            margin: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        
        .header h1 {
            font-size: 20px;
            font-weight: bold;
            margin: 0;
        }
        
        .section {
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #ccc;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-label {
            display: table-cell;
            font-weight: bold;
            width: 100px;
            padding: 5px;
        }
        
        .info-value {
            display: table-cell;
            padding: 5px;
        }
        
        .description-box {
            border: 1px solid #ccc;
            padding: 10px;
            min-height: 60px;
            background-color: #f9f9f9;
            margin: 10px 0;
        }
        
        .signature-section {
            margin-top: 30px;
            display: table;
            width: 100%;
        }
        
        .signature-box {
            display: table-cell;
            width: 50%;
            padding: 10px;
            vertical-align: top;
        }
        
        .signature-label {
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .signature-image {
            border: 1px dashed #ccc;
            min-height: 80px;
            background-color: #f9f9f9;
            text-align: center;
            padding: 10px;
            margin: 10px 0;
        }
        
        .signature-image img {
            max-width: 100%;
            max-height: 80px;
        }
        
        .signature-date {
            text-align: right;
            margin-top: 10px;
            font-size: 12px;
            color: #666;
        }
        
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        .table th, .table td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }
        
        .table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        
        .text-center {
            text-align: center;
        }
        
        .bold {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- 页面头部 -->
    <div class="header">
        <h1>{{ $pageTitle }}</h1>
        <div>{{ $companyName }} | {{ $companyAddress }} | {{ $companyPhone }}</div>
    </div>
    
    <!-- 基本信息 -->
    <div class="section">
        <div class="section-title">基本信息</div>
        <table class="table">
            <tr>
                <td class="info-label">工单编号：</td>
                <td class="info-value">{{ $workorder->ticket_no }}</td>
                <td class="info-label">创建时间：</td>
                <td class="info-value">{{ $workorder->created_at->format('Y年m月d日 H:i') }}</td>
            </tr>
            <tr>
                <td class="info-label">报修人：</td>
                <td class="info-value">{{ $workorder->contact_name }}</td>
                <td class="info-label">联系电话：</td>
                <td class="info-value">{{ $workorder->contact_phone }}</td>
            </tr>
            <tr>
                <td class="info-label">地址：</td>
                <td class="info-value" colspan="3">
                    @php
                        $addressParts = [];
                        if($workorder->campus) {
                            $addressParts[] = $workorder->campus;
                        }
                        if($workorder->building) {
                            $building = \App\Models\Location::find($workorder->building);
                            if ($building) {
                                $addressParts[] = $building->name;
                            } else {
                                $addressParts[] = $workorder->building;
                            }
                        }
                        if($workorder->location_detail) {
                            $addressParts[] = $workorder->location_detail;
                        }
                        echo implode(' - ', $addressParts);
                    @endphp
                </td>
            </tr>
            <tr>
                <td class="info-label">优先级：</td>
                <td class="info-value">{{ $workorder->priority_text }}</td>
                <td class="info-label">处理人：</td>
                <td class="info-value">{{ $workorder->assignee_name ?? '未分配' }}</td>
            </tr>
        </table>
    </div>
    
    <!-- 故障描述 -->
    <div class="section">
        <div class="section-title">故障描述</div>
        <div class="description-box">
            {{ $workorder->description }}
        </div>
    </div>
    
    <!-- 解决方案 -->
    @if($workorder->solution)
    <div class="section">
        <div class="section-title">解决方案</div>
        <div class="description-box">
            {{ $workorder->solution }}
        </div>
    </div>
    @endif
    
    <!-- 备件耗材 -->
    @if($workorder->materials_usage)
    <div class="section">
        <div class="section-title">备件耗材使用情况</div>
        <div class="description-box">
            {{ $workorder->materials_usage }}
        </div>
    </div>
    @endif
    
    <!-- 用户反馈与签名 -->
    <div class="section">
        <div class="section-title">用户反馈与签名</div>
        
        <!-- 满意度评分 -->
        @if(isset($signatureData['satisfaction']))
        <div style="margin: 10px 0;">
            <span class="bold">满意度评分：</span>
            @for($i = 1; $i <= 5; $i++)
                @if($i <= $signatureData['satisfaction'])
                    ★
                @else
                    ☆
                @endif
            @endfor
            {{ \App\Services\ChinesePDFService::formatSatisfactionText($signatureData['satisfaction']) }}
        </div>
        @endif
        
        <!-- 用户反馈 -->
        @if(isset($signatureData['feedback']) && !empty($signatureData['feedback']))
        <div style="margin: 10px 0;">
            <span class="bold">用户反馈：</span>
            <div class="description-box">
                {{ $signatureData['feedback'] }}
            </div>
        </div>
        @endif
        
        <!-- 签名区域 -->
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-label">用户签名：</div>
                <div class="signature-image">
                    @if(isset($signatureData['signature']) && !empty($signatureData['signature']))
                        <img src="{{ $signatureData['signature'] }}" alt="用户签名">
                    @else
                        <span>（无签名）</span>
                    @endif
                </div>
                <div class="signature-date">
                    签名时间：{{ $signatureData['signed_at'] ?? now()->format('Y年m月d日 H:i:s') }}
                </div>
            </div>
            
            <div class="signature-box">
                <div class="signature-label">处理人签名：</div>
                <div class="signature-image">
                    <span>（预留）</span>
                </div>
                <div class="signature-date">
                    处理人：{{ $workorder->assignee_name ?? '未分配' }}
                </div>
            </div>
        </div>
    </div>
    
    <!-- 页面底部 -->
    <div class="footer">
        <div>生成时间：{{ $generatedAt }}</div>
        <div>此为系统生成的故障处理记录单，无需盖章</div>
    </div>
</body>
</html>