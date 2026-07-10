<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>{{ $pageTitle }}</title>
    <style>
        @font-face {
            font-family: 'SimSun';
            src: url('{{ public_path('fonts/SimSun.ttf') }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        
        body {
            font-family: 'SimSun', '宋体', serif;
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
        <div class="section-title">&#22522;&#21333;&#21333;&#20449;&#21333;</div>
        <table class="table">
            <tr>
                <td class="info-label">&#24037;&#21333;&#32534;&#21495;&#32534;&#65306;</td>
                <td class="info-value">{{ $workorder->ticket_no }}</td>
                <td class="info-label">&#21019;&#24314;&#26102;&#38388;&#38395;&#65306;</td>
                <td class="info-value">{{ $workorder->created_at->format('Y&#24180;m&#26376;d&#26085; H:i') }}</td>
            </tr>
            <tr>
                <td class="info-label">&#25253;&#20462;&#20154;&#65306;</td>
                <td class="info-value">{{ $workorder->contact_name }}</td>
                <td class="info-label">&#32852;&#39064;&#35774;&#35805;&#65306;</td>
                <td class="info-value">{{ $workorder->contact_phone }}</td>
            </tr>
            <tr>
                <td class="info-label">&#22320;&#22336;&#65306;</td>
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
                <td class="info-label">&#20248;&#20848;&#32423;&#32423;&#65306;</td>
                <td class="info-value">{{ $workorder->priority_text }}</td>
                <td class="info-label">&#22788;&#29702;&#20154;&#65306;</td>
                <td class="info-value">{{ $workorder->assignee_name ?? '未分配' }}</td>
            </tr>
        </table>
    </div>
    
    <!-- 故障描述 -->
    <div class="section">
        <div class="section-title">&#25925;&#38556;&#25551;&#36848;&#36848;</div>
        <div class="description-box">
            {{ $workorder->description }}
        </div>
    </div>
    
    <!-- 解决方案 -->
    @if($workorder->solution)
    <div class="section">
        <div class="section-title">&#35299;&#20915;&#26041;&#26041;&#26041;</div>
        <div class="description-box">
            {{ $workorder->solution }}
        </div>
    </div>
    @endif
    
    <!-- 备件耗材 -->
    @if($workorder->materials_usage)
    <div class="section">
        <div class="section-title">&#22791;&#20214;&#32791;&#26435;&#20351;&#29992;&#29992;&#24773;&#20917;</div>
        <div class="description-box">
            {{ $workorder->materials_usage }}
        </div>
    </div>
    @endif
    
    <!-- 用户反馈与签名 -->
    <div class="section">
        <div class="section-title">&#29992;&#25143;&#21453;&#39118;&#19982;&#31616;&#21512;</div>
        
        <!-- 满意度评分 -->
        @if(isset($signatureData['satisfaction']))
        <div style="margin: 10px 0;">
            <span class="bold">&#28385;&#24847;&#24230;&#35780;&#35757;&#65306;</span>
            @for($i = 1; $i <= 5; $i++)
                @if($i <= $signatureData['satisfaction'])
                    &#9733;
                @else
                    &#9734;
                @endif
            @endfor
            {{ \App\Services\WorkingChinesePDFService::formatSatisfactionText($signatureData['satisfaction']) }}
        </div>
        @endif
        
        <!-- 用户反馈 -->
        @if(isset($signatureData['feedback']) && !empty($signatureData['feedback']))
        <div style="margin: 10px 0;">
            <span class="bold">&#29992;&#25143;&#21453;&#39118;&#65306;</span>
            <div class="description-box">
                {{ $signatureData['feedback'] }}
            </div>
        </div>
        @endif
        
        <!-- 签名区域 -->
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-label">&#29992;&#25143;&#31616;&#21512;</div>
                <div class="signature-image">
                    @if(isset($signatureData['signature']) && !empty($signatureData['signature']))
                        <img src="{{ $signatureData['signature'] }}" alt="&#29992;&#25143;&#31616;&#21512;">
                    @else
                        <span>（&#26080;&#31616;&#21512;&#65289;</span>
                    @endif
                </div>
                <div class="signature-date">
                    &#31616;&#21512;&#26102;&#38388;&#65306;：{{ $signatureData['signed_at'] ?? now()->format('Y&#24180;m&#26376;d&#26085; H:i:s') }}
                </div>
            </div>
            
            <div class="signature-box">
                <div class="signature-label">&#22788;&#29702;&#29702;&#20154;&#31616;&#21512;</div>
                <div class="signature-image">
                    <span>（&#39044;&#30041;&#65289;</span>
                </div>
                <div class="signature-date">
                    &#22788;&#29702;&#29702;&#20154;&#65306;：{{ $workorder->assignee_name ?? '未分配' }}
                </div>
            </div>
        </div>
    </div>
    
    <!-- 页面底部 -->
    <div class="footer">
        <div>&#29983;&#25104;&#26102;&#38388;&#38395;&#65306;：{{ $generatedAt }}</div>
        <div>&#27492;&#20026;&#31995;&#32479;&#25103;&#25115;&#30340;&#30340;&#25919;&#29702;&#21333;&#21333;&#20449;&#21333;&#65292;&#19981;&#38656;&#29228;</div>
    </div>
</body>
</html>