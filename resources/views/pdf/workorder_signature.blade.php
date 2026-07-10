<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pageTitle }}</title>
    <style>
        @page {
            size: A4;
            margin: 15mm;
        }
        
        @font-face {
            font-family: 'SimSun';
            src: url('{{ public_path('fonts/simsun_normal_cfda25ee71b1f24d4fb75730d817e676.ttf') }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        
        * {
            font-family: 'SimSun' !important;
        }
        
        body {
            font-family: 'SimSun' !important;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
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
            padding: 0;
        }
        
        .company-info {
            font-size: 10px;
            color: #666;
            margin-top: 5px;
        }
        
        .section {
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #ccc;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .info-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 5px;
        }
        
        .info-label {
            font-weight: bold;
            min-width: 80px;
            flex-shrink: 0;
        }
        
        .info-value {
            flex-grow: 1;
            word-break: break-all;
        }
        
        .full-width {
            grid-column: 1 / -1;
        }
        
        .description-box {
            border: 1px solid #ccc;
            padding: 10px;
            min-height: 60px;
            background-color: #f9f9f9;
        }
        
        .signature-section {
            margin-top: 30px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .signature-box {
            border: 1px solid #ccc;
            padding: 10px;
            min-height: 100px;
            display: flex;
            flex-direction: column;
        }
        
        .signature-label {
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .signature-image {
            flex-grow: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px dashed #ccc;
            min-height: 80px;
            background-color: #f9f9f9;
        }
        
        .signature-image img {
            max-width: 100%;
            max-height: 80px;
        }
        
        .signature-date {
            text-align: right;
            margin-top: 10px;
            font-size: 10px;
            color: #666;
        }
        
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }
        
        .satisfaction-rating {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .rating-stars {
            color: #ffc107;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        .table th,
        .table td {
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
        
        .text-right {
            text-align: right;
        }
        
        .bold {
            font-weight: bold;
        }
        
        .mt-20 {
            margin-top: 20px;
        }
        
        .mb-20 {
            margin-bottom: 20px;
        }
        
        @media print {
            body {
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }
            
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- 页面头部 -->
    <div class="header">
        <h1>{{ $pageTitle }}</h1>
        <div class="company-info">
            {{ $companyName }} | {{ $companyAddress }} | {{ $companyPhone }}
        </div>
    </div>
    
    <!-- 基本信息 -->
    <div class="section">
        <div class="section-title">基本信息</div>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">工单编号：</span>
                <span class="info-value">{{ $workorder->ticket_no }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">创建时间：</span>
                <span class="info-value">{{ $workorder->created_at->format('Y年m月d日 H:i') }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">报修人：</span>
                <span class="info-value">{{ $workorder->contact_name }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">联系电话：</span>
                <span class="info-value">{{ $workorder->contact_phone }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">地址：</span>
                <span class="info-value">
                    @php
                        $addressParts = [];
                        
                        // 添加校区
                        if($workorder->campus) {
                            $addressParts[] = \App\Models\Location::CAMPUSES[$workorder->campus] ?? $workorder->campus;
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
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">优先级：</span>
                <span class="info-value">{{ $workorder->priority_text }}</span>
            </div>
            @if($workorder->department_name)
            <div class="info-item">
                <span class="info-label">所属部门：</span>
                <span class="info-value">{{ $workorder->department_name }}</span>
            </div>
            @endif
            @if($workorder->location_detail)
            <div class="info-item">
                <span class="info-label">详细地址：</span>
                <span class="info-value">{{ $workorder->location_detail }}</span>
            </div>
            @endif
        </div>
    </div>
    
    <!-- 故障描述 -->
    <div class="section">
        <div class="section-title">故障描述</div>
        <div class="description-box">
            {{ $workorder->description }}
        </div>
    </div>
    
    <!-- 处理过程 -->
    <div class="section">
        <div class="section-title">处理过程</div>
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 20%">时间</th>
                    <th style="width: 15%">操作人</th>
                    <th style="width: 15%">操作类型</th>
                    <th style="width: 50%">内容</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $logs = \App\Services\WorkorderSignaturePDFService::formatProcessingLogs($workorder);
                @endphp
                @forelse($logs as $log)
                <tr>
                    <td>{{ $log['time'] }}</td>
                    <td>{{ $log['user'] }}</td>
                    <td>{{ $log['action'] }}</td>
                    <td>{{ $log['content'] ?? '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center">暂无处理记录</td>
                </tr>
                @endforelse
            </tbody>
        </table>
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
    
    <!-- 处理时间 -->
    <div class="section">
        <div class="section-title">处理时间</div>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">创建时间：</span>
                <span class="info-value">{{ $workorder->created_at->format('Y年m月d日 H:i:s') }}</span>
            </div>
            @if($workorder->assigned_at)
            <div class="info-item">
                <span class="info-label">分配时间：</span>
                <span class="info-value">{{ $workorder->assigned_at->format('Y年m月d日 H:i:s') }}</span>
            </div>
            @endif
            @if($workorder->started_at)
            <div class="info-item">
                <span class="info-label">开始时间：</span>
                <span class="info-value">{{ $workorder->started_at->format('Y年m月d日 H:i:s') }}</span>
            </div>
            @endif
            @if($workorder->resolved_at)
            <div class="info-item">
                <span class="info-label">解决时间：</span>
                <span class="info-value">{{ $workorder->resolved_at->format('Y年m月d日 H:i:s') }}</span>
            </div>
            @endif
            <div class="info-item full-width">
                <span class="info-label">处理时长：</span>
                <span class="info-value">{{ $workorder->formatted_processing_duration }}</span>
            </div>
        </div>
    </div>
    
    <!-- 用户反馈与签名 -->
    <div class="section">
        <div class="section-title">用户反馈与签名</div>
        
        <!-- 满意度评分 -->
        @if(isset($signatureData['satisfaction']))
        <div class="info-item mb-20">
            <span class="info-label">满意度评分：</span>
            <span class="info-value">
                <div class="satisfaction-rating">
                    <div class="rating-stars">
                        @for($i = 1; $i <= 5; $i++)
                            @if($i <= $signatureData['satisfaction'])
                                ★
                            @else
                                ☆
                            @endif
                        @endfor
                    </div>
                    <span>{{ \App\Services\WorkorderSignaturePDFService::formatSatisfactionText($signatureData['satisfaction']) }}</span>
                </div>
            </span>
        </div>
        @endif
        
        <!-- 用户反馈 -->
        @if(isset($signatureData['feedback']) && !empty($signatureData['feedback']))
        <div class="mb-20">
            <div class="info-label">用户反馈：</div>
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
                    处理人：{{ $workorder->assignee_name }}
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