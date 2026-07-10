<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>故障处理记录单</title>
    <style>
        /* 打印友好的样式 */
        @media print {
            @page {
                size: A4;
                margin: 0; /* 关键：去掉默认边距，页眉页脚会因无空间显示而消失 */
                /* 确保不会生成额外的空白页 */
                marks: none;
                orphans: 0;
                widows: 0;
            }
            
            html, body {
                /* 确保内容在一页内 */
                height: auto; /* 改为auto，避免强制占满整个页面 */
                max-height: 277mm; /* 限制最大高度 */
                overflow: hidden;
                font-family: 'Microsoft YaHei', 'SimSun', sans-serif;
                font-size: 10pt; /* 进一步减小字体 */
                line-height: 1.2; /* 进一步减小行高 */
                margin: 0;
                padding: 0; /* 移除内边距，由容器控制 */
                /* 彻底隐藏浏览器默认的页眉页脚 */
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }
            
            /* 隐藏打印时的页眉页脚 */
            @media print {
                /* 给打印内容手动加内边距（避免内容贴边） */
                body {
                    margin: 10mm; /* 上下左右各留10mm边距 */
                }
                
                /* 强制打印背景图/颜色（默认浏览器会禁用） */
                body {
                    -webkit-print-color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }
                
                /* 可选：隐藏不需要打印的元素（如导航、按钮） */
                .no-print {
                    display: none !important;
                }
            }
            
            .no-print { display: none !important; }
            .page-break { page-break-before: always; }
            .signature-pad {
                border: 1px dashed #ccc;
                background: #f9f9f9;
                min-height: 60px;
            }
            
            /* 防止任何元素分页 */
            * {
                page-break-inside: avoid !important;
                page-break-after: avoid !important;
                page-break-before: avoid !important;
            }
            
            /* 确保容器在页边距以内 */
            .container {
                height: 277mm !important; /* A4高度减去上下边距(297mm - 20mm) */
                max-height: 277mm !important;
                overflow: hidden !important;
                display: flex;
                flex-direction: column;
            }
            
            /* 调整各区域间距，确保不溢出 */
            .section {
                margin-bottom: 5px !important; /* 进一步减少底部边距 */
                page-break-inside: avoid;
                display: flex;
                flex-direction: column;
            }
            
            /* 问题描述、解决方案、用户反馈这三个区域等高 */
            .section:nth-child(3) {
                flex: 1;
            }
            
            /* 解决方案栏高度为当前的3倍高度 */
            .section:nth-child(4) {
                flex: 3;
            }
            
            /* 用户反馈栏固定高度50mm */
            .section:nth-child(6) {
                flex: 0;
                height: 50mm;
                overflow: hidden;
            }
            
            /* 只有最后一个区域（签名区域）自动填充剩余空间 */
            .section:last-child {
                flex: 2;
            }
            
            .info-item {
                padding: 4px !important; /* 进一步减少内边距 */
                margin-bottom: 4px !important;
            }
            
            .content-box {
                padding: 6px !important; /* 进一步减少内边距 */
                min-height: 30px !important; /* 进一步减少最小高度 */
            }
            
            /* 问题描述、解决方案、用户反馈这三个区域的内容框等高 */
            .section:nth-child(3) .content-box,
            .section:nth-child(4) .content-box,
            .section:nth-child(6) .content-box {
                flex: 1;
            }
            
            /* 只有签名区域的内容框自动填充剩余空间 */
            .section:last-child .content-box {
                flex: 1;
            }
            
            .signature-area {
                margin-top: 8px !important; /* 进一步减少顶部边距 */
                flex: 1;
            }
            
            .footer {
                display: none !important; /* 隐藏整个页脚 */
            }
        }
        
        /* 屏幕样式 */
        body {
            font-family: 'Microsoft YaHei', 'SimSun', sans-serif;
            font-size: 12pt; /* 恢复字体大小 */
            line-height: 1.4; /* 恢复行高 */
            margin: 10px;
            background: #f5f5f5;
            color: #333;
        }
        
        .container {
            max-width: 190mm; /* A4宽度减去左右边距(210mm - 20mm) */
            margin: 0 auto;
            background: white;
            padding: 10px; /* 进一步减少内边距 */
            border: 1px solid #ddd;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
            min-height: auto !important; /* 改为auto，让内容决定高度 */
            max-height: 277mm !important; /* 限制最大高度 */
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 8px; /* 减少底部边距 */
            margin-bottom: 10px; /* 减少底部边距 */
        }
        
        .title {
            font-size: 18px; /* 减小标题字体 */
            font-weight: bold;
            margin-bottom: 4px; /* 减少底部边距 */
            color: #2c3e50;
        }
        
        .subtitle {
            font-size: 12px; /* 减小副标题字体 */
            color: #666;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px; /* 减少间距 */
            margin-bottom: 10px; /* 减少底部边距 */
        }
        
        .info-item {
            padding: 6px; /* 减少内边距 */
            background: #f9f9f9;
            border-left: 3px solid #007bff;
            border-radius: 4px;
            font-size: 10pt; /* 减小字体 */
            margin-bottom: 6px; /* 减少底部边距 */
        }
        
        .info-label {
            font-weight: bold;
            color: #007bff;
            margin-bottom: 2px;
            font-size: 10pt; /* 减小字体 */
        }
        
        .section {
            margin-bottom: 8px; /* 减少底部边距 */
            page-break-inside: avoid;
            display: flex;
            flex-direction: column;
        }
        
        /* 问题描述、解决方案、用户反馈这三个区域等高 */
        .section:nth-child(3),
        .section:nth-child(4) {
            flex: 1;
        }
        
        /* 用户反馈栏压缩高度 */
        .section:nth-child(6) {
            flex: 0.8;
        }
        
        /* 只有最后一个区域（签名区域）自动填充剩余空间 */
        .section:last-child {
            flex: 2;
        }
        
        .section-title {
            font-size: 14px; /* 减小字体 */
            font-weight: bold;
            color: #333;
            border-bottom: 2px solid #ddd;
            padding-bottom: 4px;
            margin-bottom: 6px; /* 减少底部边距 */
            background: #e9ecef;
            padding: 6px; /* 减少内边距 */
            border-radius: 4px;
        }
        
        .content-box {
            background: #f8f9fa;
            padding: 8px; /* 减少内边距 */
            border-radius: 4px;
            border: 1px solid #e9ecef;
            min-height: 30px; /* 减少最小高度 */
            font-size: 10pt; /* 减小字体 */
            line-height: 1.3; /* 减小行高 */
        }
        
        /* 问题描述、解决方案、用户反馈这三个区域的内容框等高 */
        .section:nth-child(3) .content-box,
        .section:nth-child(4) .content-box,
        .section:nth-child(6) .content-box {
            flex: 1;
        }
        
        /* 只有签名区域的内容框自动填充剩余空间 */
        .section:last-child .content-box {
            flex: 1;
        }
        
        .signature-area {
            margin-top: 10px; /* 减少顶部边距 */
            text-align: center;
            page-break-inside: avoid;
            min-height: 40px; /* 减少最小高度 */
            flex: 1; /* 签名区域自动填充剩余空间 */
        }
        
        .signature-line {
            border-bottom: 2px solid #333;
            width: 180px; /* 减小签名线宽度 */
            margin: 10px auto;
        }
        
        .signature-text {
            margin: 6px 0;
            font-style: italic;
            color: #666;
            font-size: 9pt; /* 减小字体 */
        }
        
        .footer {
            display: none; /* 在屏幕视图中也隐藏页脚 */
        }
        
        .print-btn {
            background: #007bff;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            margin: 20px auto;
            display: block;
        }
        
        .print-btn:hover {
            background: #0056b3;
        }
        
        .status-completed {
            color: #28a745;
            font-weight: bold;
        }
        
        .status-processing {
            color: #ffc107;
            font-weight: bold;
        }
        
        .status-pending {
            color: #6c757d;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- 页面头部 -->
        <div class="header">
            <div class="title">故障处理记录单</div>
        </div>
        
        <!-- 基本信息 -->
        <div class="section">
            <div class="section-title">{{ $workorder->ticket_no }}基本信息</div>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">报修人：</span>
                    <span>{{ $workorder->contact_name }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">联系电话：</span>
                    <span>
                        @if($workorder->contact_phone)
                            <a href="tel:{{ $workorder->contact_phone }}" style="text-decoration: none; color: #007bff;">{{ $workorder->contact_phone }}</a>
                        @else
                            未提供
                        @endif
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">报修日期：</span>
                    <span>{{ $workorder->created_at ? $workorder->created_at->format('Y-m-d H:i') : '待处理' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">故障地点：</span>
                    <span>
                        @if($workorder->campus_id && $workorder->campus && is_object($workorder->campus))
                           {{ $workorder->campus->name }}
                        @elseif($workorder->campus_id)
                           {{ \App\Models\Campus::find($workorder->campus_id)->name ?? '未知校区' }}
                        @elseif($workorder->campus)
                           {{ $workorder->campus }}
                        @endif
                        @if($workorder->building)
                           @php
                               // 检查building是否为数字（可能是location_id）
                               if (is_numeric($workorder->building)) {
                                   $building = \App\Models\Location::find($workorder->building);
                                   if ($building) {
                                       echo ' - ' . $building->name;
                                       if ($workorder->location_detail) {
                                           echo ' ' . $workorder->location_detail;
                                       }
                                   } else {
                                       echo ' - ' . $workorder->building;
                                   }
                               } else {
                                   // 如果是文本，直接显示
                                   echo ' - ' . $workorder->building;
                                   if ($workorder->location_detail) {
                                       echo ' ' . $workorder->location_detail;
                                   }
                               }
                           @endphp
                        @endif
                        @if(!$workorder->campus_id && !$workorder->building)
                            未提供
                        @endif
                    </span>
                </div>
            </div>
        </div>
        
        <!-- 工单描述 -->
        <div class="section">
            <div class="section-title">问题描述</div>
            <div class="content-box">
                {!! nl2br($workorder->description ?? '') !!}
            </div>
        </div>
        
        <!-- 处理过程 -->
        @if($workorder->solution)
        <div class="section">
            <div class="section-title">解决方案</div>
            <div class="content-box">
                {!! nl2br($workorder->solution) !!}
            </div>
        </div>
        @endif
        
        <!-- 备件耗材使用情况 -->
        @if($workorder->materials_usage)
        <div class="section">
            <div class="section-title">备件耗材使用情况</div>
            <div class="content-box">
                {!! nl2br($workorder->materials_usage) !!}
            </div>
        </div>
        @endif
        
        <!-- 处理人员信息 -->
        <div class="section">
            <div class="info-item">
                <span class="info-label">处理人员：</span>
                <span>{{ $workorder->assignee ? $workorder->assignee->name : '未分配' }}</span>
                @if($workorder->collaborations && $workorder->collaborations->count() > 0)
                    <span class="info-label" style="margin-left: 15px;">协助人：</span>
                    <span>
                        @foreach($workorder->collaborations as $collaboration)
                            @if($collaboration->collaborator)
                                {{ $collaboration->collaborator->name }}@if(!$loop->last)、@endif
                            @endif
                        @endforeach
                    </span>
                @endif
                <span class="info-label" style="margin-left: 15px;">解决时间：</span>
                <span>{{ $workorder->resolved_at ? $workorder->resolved_at->format('Y-m-d H:i') : '未解决' }}</span>
            </div>
        </div>
        
        <!-- 用户反馈 -->
        @if($workorder->user_feedback)
        <div class="section">
            <div class="section-title">用户反馈</div>
            <div class="content-box" style="position: relative; padding-bottom: 60px;">
                <div class="info-label">满意度评分: {{ $workorder->user_satisfaction ?? '未评分' }}/5&nbsp;&nbsp;&nbsp;&nbsp;</div>
                @if($workorder->user_feedback)
                    <div class="info-label">反馈内容:</div>
                    <div style="margin-right: 150px;">{{ $workorder->user_feedback }}</div>
                @endif
                
                <!-- 用户签名（右下角） -->
                <div style="position: absolute; bottom: 10px; right: 10px; text-align: center;">
                    @if($workorder->user_signature)
                        @if(str_starts_with($workorder->user_signature, 'data:image'))
                                <div>
                                    <img src="{{ $workorder->user_signature }}"
                                         alt="用户签名"
                                         style="max-width: 120px; max-height: 60px; border: none;">
                                </div>
                        @else
                            <div style="font-size: 14px; color: #333;">
                                {{ $workorder->user_signature }}
                            </div>
                        @endif
                        <div style="font-size: 9pt; color: #666; margin-top: 2px; text-align: right;">
                            签名日期: {{ $workorder->user_signed_at ? $workorder->user_signed_at->format('Y-m-d') : date('Y-m-d') }}
                        </div>
                    @else
                        <div style="border-bottom: 1px solid #333; width: 80px; margin: 0 auto;"></div>
                        <div style="font-size: 9pt; color: #666; margin-top: 2px;">请签名</div>
                    @endif
                </div>
            </div>
        </div>
        @else
        <!-- 如果没有用户反馈，单独显示签名区域 -->
        @if($workorder->user_signature)
        <div class="section">
            <div class="section-title">用户签名</div>
            <div class="content-box" style="text-align: right; position: relative;">
                <div style="display: inline-block; text-align: center;">
                    @if(str_starts_with($workorder->user_signature, 'data:image'))
                        <div>
                            <img src="{{ $workorder->user_signature }}"
                                 alt="用户签名"
                                 style="max-width: 120px; max-height: 60px; border: none;">
                        </div>
                    @else
                        <div style="font-size: 14px; color: #333;">
                            {{ $workorder->user_signature }}
                        </div>
                    @endif
                    <div style="font-size: 9pt; color: #666; margin-top: 2px; text-align: right;">
                        签名日期: {{ $workorder->user_signed_at ? $workorder->user_signed_at->format('Y-m-d') : date('Y-m-d') }}
                    </div>
                </div>
            </div>
        </div>
        @endif
        @endif
        
        <!-- 页面底部已删除，不再显示生成时间 -->
    </div>
    
    <!-- 打印按钮 -->
    <div class="no-print">
        <button class="print-btn" onclick="window.print()">
            <i class="fas fa-print"></i> 打印处理单
        </button>
        <div style="text-align: center; margin: 10px;">
            <small>提示：使用 Ctrl+P 可以快速打印</small>
        </div>
    </div>
</body>
</html>