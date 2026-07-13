@extends('layouts.app')

@section('title', '统计报表')

@section('content')
<div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
    <h1 class="text-xl font-semibold text-ink">统计报表</h1>
    <div class="flex items-center gap-1 p-1 rounded-lg" style="background-color: var(--c-muted);">
        @foreach(['7days' => '7天', '30days' => '30天', '90days' => '90天'] as $key => $label)
        <a href="{{ route('reports.index', ['date_range' => $key]) }}" class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors {{ $dateRange == $key ? 'text-white' : '' }}" {{ $dateRange == $key ? 'style="background-color: var(--c-brand);"' : 'style="color: var(--c-ink-muted);"' }}>{{ $label }}</a>
        @endforeach
    </div>
    <div class="flex items-center gap-2">
        <input type="date" id="customDateFrom" class="input" style="padding:0.3rem 0.5rem;font-size:0.8rem;width:auto;" value="{{ request('custom_from','') }}">
        <span class="text-sm" style="color:var(--c-ink-muted);">~</span>
        <input type="date" id="customDateTo" class="input" style="padding:0.3rem 0.5rem;font-size:0.8rem;width:auto;" value="{{ request('custom_to','') }}">
        <button type="button" id="customRangeBtn" class="btn btn-primary btn-sm">应用</button>
    </div>
</div>

{{-- Overview stats --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
    <div class="card p-4">
        <p class="text-2xl font-bold text-ink">{{ $stats['total_workorders'] }}</p>
        <p class="text-xs mt-1" style="color: var(--c-ink-muted);">总工单数</p>
    </div>
    <div class="card p-4">
        <p class="text-2xl font-bold text-amber-600">{{ $stats['pending_workorders'] }}</p>
        <p class="text-xs mt-1" style="color: var(--c-ink-muted);">待处理</p>
    </div>
    <div class="card p-4">
        <p class="text-2xl font-bold text-green-600">{{ $stats['completed_workorders'] }}</p>
        <p class="text-xs mt-1" style="color: var(--c-ink-muted);">已完成 ({{ $stats['completion_rate'] }}%)</p>
    </div>
    <div class="card p-4">
        <p class="text-2xl font-bold text-red-600">{{ $stats['overdue_workorders'] }}</p>
        <p class="text-xs mt-1" style="color: var(--c-ink-muted);">超时工单</p>
    </div>
</div>
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
    <div class="card p-4">
        <p class="text-2xl font-bold text-brand-600">{{ $stats['range_new'] }}</p>
        <p class="text-xs mt-1" style="color: var(--c-ink-muted);">本期新增</p>
    </div>
    <div class="card p-4">
        <p class="text-2xl font-bold text-green-600">{{ $stats['range_resolved'] }}</p>
        <p class="text-xs mt-1" style="color: var(--c-ink-muted);">本期完成</p>
    </div>
    <div class="card p-4">
        <p class="text-2xl font-bold text-orange-600">{{ $stats['emergency_workorders'] }}</p>
        <p class="text-xs mt-1" style="color: var(--c-ink-muted);">紧急工单</p>
    </div>
    <div class="card p-4">
        <p class="text-2xl font-bold text-ink">{{ $stats['total_users'] }}</p>
        <p class="text-xs mt-1" style="color: var(--c-ink-muted);">系统用户</p>
    </div>
</div>

{{-- Trend chart (full width) --}}
<div class="card p-5 mb-6">
    <h3 class="text-sm font-semibold text-ink mb-4">工单趋势（新建 vs 完成）</h3>
    <div style="position:relative;height:300px;"><canvas id="trendChart"></canvas></div>
</div>

{{-- Charts row: network + media sub-category top10 --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="card p-5">
        <h3 class="text-sm font-semibold text-ink mb-4">网络</h3>
        <div class="flex items-center justify-center" style="height:260px;"><canvas id="networkSubChart"></canvas></div>
    </div>
    <div class="card p-5">
        <h3 class="text-sm font-semibold text-ink mb-4">多媒体</h3>
        <div class="flex items-center justify-center" style="height:260px;"><canvas id="mediaSubChart"></canvas></div>
    </div>
</div>

{{-- Charts row: category + source --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="card p-5">
        <h3 class="text-sm font-semibold text-ink mb-4">工单分类分布</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-center">
            <div class="flex items-center justify-center"><canvas id="categoryChart"></canvas></div>
            <div class="overflow-x-auto max-h-[200px]">
                <table class="w-full text-sm">
                    <thead><tr class="text-left border-b border-border sticky top-0" style="background-color: var(--c-card);">
                        <th class="py-2 font-medium" style="color: var(--c-ink-muted);">分类</th>
                        <th class="py-2 font-medium text-right" style="color: var(--c-ink-muted);">数量</th>
                    </tr></thead>
                    <tbody>
                    @foreach($categoryDistribution as $category)
                    <tr class="border-b border-border">
                        <td class="py-2 text-ink">{{ $category->name }}</td>
                        <td class="py-2 text-right text-ink">{{ $category->workorders_count }}</td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="card p-5">
        <h3 class="text-sm font-semibold text-ink mb-4">工单来源分布</h3>
        <div class="flex items-center justify-center" style="height:220px;"><canvas id="sourceChart"></canvas></div>
    </div>
</div>

{{-- Campus + processing time --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="card p-5">
        <h3 class="text-sm font-semibold text-ink mb-4">校区工单统计</h3>
        <div class="overflow-x-auto max-h-[280px]">
            <table class="w-full text-sm">
                <thead><tr class="text-left border-b border-border sticky top-0" style="background-color: var(--c-card);">
                    <th class="py-2 font-medium" style="color: var(--c-ink-muted);">校区</th>
                    <th class="py-2 font-medium text-right" style="color: var(--c-ink-muted);">总数</th>
                    <th class="py-2 font-medium text-right" style="color: var(--c-ink-muted);">待处理</th>
                    <th class="py-2 font-medium text-right" style="color: var(--c-ink-muted);">完成</th>
                </tr></thead>
                <tbody>
                @foreach($campusStats as $key => $stat)
                <tr class="border-b border-border">
                    <td class="py-2 text-ink">{{ $stat['name'] }}</td>
                    <td class="py-2 text-right text-ink">{{ $stat['total'] }}</td>
                    <td class="py-2 text-right text-amber-600">{{ $stat['pending'] }}</td>
                    <td class="py-2 text-right text-green-600">{{ $stat['completed'] }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="card p-5">
        <h3 class="text-sm font-semibold text-ink mb-4">处理时长统计</h3>
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="p-4 rounded-lg text-center" style="background-color: var(--c-muted);">
                <p class="text-xl font-bold text-ink">{{ $processingTimeStats['average_time'] }}</p>
                <p class="text-xs mt-1" style="color: var(--c-ink-muted);">平均处理时长（分钟）</p>
            </div>
            <div class="p-4 rounded-lg text-center" style="background-color: var(--c-muted);">
                <p class="text-xl font-bold text-green-600">{{ $processingTimeStats['total_completed'] }}</p>
                <p class="text-xs mt-1" style="color: var(--c-ink-muted);">已完成工单</p>
            </div>
        </div>
        <div class="flex justify-between text-sm" style="color: var(--c-ink-muted);">
            <span>最短：{{ $processingTimeStats['min_time'] }} 分钟</span>
            <span>最长：{{ $processingTimeStats['max_time'] }} 分钟</span>
        </div>
    </div>
</div>

{{-- Engineers --}}
<div class="card p-5 mb-6">
    <h3 class="text-sm font-semibold text-ink mb-4">工程师处理统计</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="text-left border-b border-border">
                <th class="py-2 font-medium" style="color: var(--c-ink-muted);">工程师</th>
                <th class="py-2 font-medium text-right" style="color: var(--c-ink-muted);">总工单</th>
                <th class="py-2 font-medium text-right" style="color: var(--c-ink-muted);">待处理</th>
                <th class="py-2 font-medium text-right" style="color: var(--c-ink-muted);">已完成</th>
                <th class="py-2 font-medium text-right" style="color: var(--c-ink-muted);">完成率</th>
            </tr></thead>
            <tbody>
            @foreach($engineerStats as $engineer)
            <tr class="border-b border-border">
                <td class="py-2 text-ink">{{ $engineer->name }}</td>
                <td class="py-2 text-right text-ink">{{ $engineer->assigned_workorders_count }}</td>
                <td class="py-2 text-right text-amber-600">{{ $engineer->pending_workorders_count }}</td>
                <td class="py-2 text-right text-green-600">{{ $engineer->completed_workorders_count }}</td>
                <td class="py-2 text-right text-ink">
                    @php
                        $engTotal = max($engineer->assigned_workorders_count, 1);
                        echo round($engineer->completed_workorders_count / $engTotal * 100) . '%';
                    @endphp
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Export --}}
<div class="card p-5 mb-6">
    <h3 class="text-sm font-semibold text-ink mb-4">数据导出</h3>
    <form method="GET" action="{{ route('reports.export') }}" class="grid grid-cols-2 lg:grid-cols-5 gap-3 items-end">
        @csrf
        <div>
            <label class="label" for="start_date">开始日期</label>
            <input type="date" id="start_date" name="start_date" class="input">
        </div>
        <div>
            <label class="label" for="end_date">结束日期</label>
            <input type="date" id="end_date" name="end_date" class="input">
        </div>
        <div>
            <label class="label" for="exp_status">状态</label>
            <select id="exp_status" name="status" class="input">
                <option value="">全部状态</option>
                <option value="pending">待处理</option>
                <option value="assigned">已分配</option>
                <option value="processing">处理中</option>
                <option value="resolved">已解决</option>
                <option value="closed">已关闭</option>
            </select>
        </div>
        <div>
            <label class="label" for="exp_format">格式</label>
            <select id="exp_format" name="format" class="input">
                <option value="csv">CSV</option>
                <option value="xlsx">Excel</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4 M7 10l5 5 5-5 M12 15V3"/></svg>
            <span>导出数据</span>
        </button>
    </form>
</div>

@endsection

@section('scripts')
<script src="{{ asset('js/chart.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 自定义时间范围
    var btn = document.getElementById('customRangeBtn');
    if (btn) btn.addEventListener('click', function() {
        var from = document.getElementById('customDateFrom').value;
        var to = document.getElementById('customDateTo').value;
        var url = '{{ route('reports.index') }}?date_range=custom';
        if (from) url += '&custom_from=' + from;
        if (to) url += '&custom_to=' + to;
        window.location.href = url;
    });

    var cssVar = function(name){ return getComputedStyle(document.documentElement).getPropertyValue(name).trim(); };
    var inkMuted = cssVar('--c-ink-muted') || '#888';

    // Trend chart
    <?php
        $trendStats = $recentStats['stats'];
        $trendCats = $recentStats['topCats'];
        $catColors = ['#2563eb', '#dc2626', '#16a34a', '#f59e0b', '#8b5cf6', '#ec4899'];
        $catColorIdx = 0;
    ?>
    var trendCtx = document.getElementById('trendChart');
    if (trendCtx) new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: [@foreach($trendStats as $s)'{{ $s["display_date"] }}',@endforeach],
            datasets: [
                @php $catColorIdx = 0; @endphp
                @foreach($trendCats as $cid => $cname)
                {
                    label: '{{ $cname }}',
                    data: [@foreach($trendStats as $s){{ $s["cat_".$cid] ?? 0 }},@endforeach],
                    borderColor: '{{ $catColors[$catColorIdx % count($catColors)] }}',
                    backgroundColor: 'transparent',
                    tension: 0.3,
                    pointRadius: 2
                },
                @php $catColorIdx++; @endphp
                @endforeach
            ]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } }, scales: { x: { ticks: { color: inkMuted, maxTicksLimit: 12 } }, y: { beginAtZero: true, ticks: { color: inkMuted } } } }
    });

    // Network sub-category top10 chart
    var netSubCtx = document.getElementById('networkSubChart');
    if (netSubCtx) new Chart(netSubCtx, {
        type: 'doughnut',
        data: {
            labels: [@foreach($networkSubDistribution as $item)'{{ $item['name'] }}',@endforeach],
            datasets: [{ data: [@foreach($networkSubDistribution as $item){{ $item['count'] }},@endforeach], backgroundColor: ['#2563EB','#3B82F6','#60A5FA','#93C5FD','#1D4ED8','#1E40AF','#3730A3','#EF4444','#F59E0B','#22C55E'] }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { color: inkMuted, boxWidth: 12, font: { size: 11 } } } } }
    });

    // Media sub-category top10 chart
    var medSubCtx = document.getElementById('mediaSubChart');
    if (medSubCtx) new Chart(medSubCtx, {
        type: 'doughnut',
        data: {
            labels: [@foreach($mediaSubDistribution as $item)'{{ $item['name'] }}',@endforeach],
            datasets: [{ data: [@foreach($mediaSubDistribution as $item){{ $item['count'] }},@endforeach], backgroundColor: ['#7C3AED','#8B5CF6','#A78BFA','#C4B5FD','#6D28D9','#5B21B6','#4C1D95','#EF4444','#F59E0B','#22C55E'] }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { color: inkMuted, boxWidth: 12, font: { size: 11 } } } } }
    });

    // Category chart
    var catCtx = document.getElementById('categoryChart');
    if (catCtx) new Chart(catCtx, {
        type: 'doughnut',
        data: {
            labels: [@foreach($categoryDistribution as $c)'{{ $c->name }}',@endforeach],
            datasets: [{ data: [@foreach($categoryDistribution as $c){{ $c->workorders_count }},@endforeach], backgroundColor: ['#FF6384','#36A2EB','#FFCE56','#4BC0C0','#9966FF','#FF9F40','#C9CBCF','#FF6384','#36A2EB','#FFCE56'] }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });

    // Source chart
    var srcCtx = document.getElementById('sourceChart');
    if (srcCtx) new Chart(srcCtx, {
        type: 'doughnut',
        data: {
            labels: [@foreach($sourceDistribution as $name => $cnt)'{{ $name }}',@endforeach],
            datasets: [{ data: [@foreach($sourceDistribution as $cnt){{ $cnt }},@endforeach], backgroundColor: ['#2563eb','#16a34a','#F59E0B','#8B5CF6','#6B7280','#EC4899'] }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right' } } }
    });
});
</script>
@endsection
