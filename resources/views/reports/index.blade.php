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

{{-- Charts row: status + priority --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="card p-5">
        <h3 class="text-sm font-semibold text-ink mb-4">工单状态分布</h3>
        <div class="flex items-center justify-center" style="height:220px;"><canvas id="statusChart"></canvas></div>
    </div>
    <div class="card p-5">
        <h3 class="text-sm font-semibold text-ink mb-4">优先级分布</h3>
        <div class="grid grid-cols-3 gap-3">
            @php $prioTotal = max(array_sum($priorityDistribution), 1); @endphp
            <div class="p-4 rounded-lg text-center" style="background-color: rgba(239,68,68,0.1);">
                <p class="text-2xl font-bold text-red-600">{{ $priorityDistribution['high'] }}</p>
                <p class="text-xs mt-1" style="color: var(--c-ink-muted);">高 ({{ round($priorityDistribution['high']/$prioTotal*100) }}%)</p>
            </div>
            <div class="p-4 rounded-lg text-center" style="background-color: rgba(245,158,11,0.1);">
                <p class="text-2xl font-bold text-amber-600">{{ $priorityDistribution['medium'] }}</p>
                <p class="text-xs mt-1" style="color: var(--c-ink-muted);">中 ({{ round($priorityDistribution['medium']/$prioTotal*100) }}%)</p>
            </div>
            <div class="p-4 rounded-lg text-center" style="background-color: rgba(34,197,94,0.1);">
                <p class="text-2xl font-bold text-green-600">{{ $priorityDistribution['low'] }}</p>
                <p class="text-xs mt-1" style="color: var(--c-ink-muted);">低 ({{ round($priorityDistribution['low']/$prioTotal*100) }}%)</p>
            </div>
        </div>
        <div class="mt-4" style="height:100px;"><canvas id="priorityChart"></canvas></div>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.min.js"></script>
<script>
(function() {
    var cssVar = function(name){ return getComputedStyle(document.documentElement).getPropertyValue(name).trim(); };
    var inkMuted = cssVar('--c-ink-muted') || '#888';

    // Trend chart
    var trendCtx = document.getElementById('trendChart');
    if (trendCtx) new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: [@foreach($recentStats as $s)'{{ $s["display_date"] }}',@endforeach],
            datasets: [
                { label: '新建', data: [@foreach($recentStats as $s){{ $s["total"] }},@endforeach], borderColor: '#2563eb', backgroundColor: 'rgba(37,99,235,0.1)', fill: true, tension: 0.3, pointRadius: 2 },
                { label: '完成', data: [@foreach($recentStats as $s){{ $s["completed"] }},@endforeach], borderColor: '#16a34a', backgroundColor: 'rgba(22,163,74,0.1)', fill: true, tension: 0.3, pointRadius: 2 }
            ]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } }, scales: { x: { ticks: { color: inkMuted, maxTicksLimit: 12 } }, y: { beginAtZero: true, ticks: { color: inkMuted } } } }
    });

    // Status chart
    var statusCtx = document.getElementById('statusChart');
    if (statusCtx) new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['待处理','已分配','处理中','已解决','已关闭'],
            datasets: [{ data: [
                {{ $statusDistribution['pending'] ?? 0 }},
                {{ $statusDistribution['assigned'] ?? 0 }},
                {{ $statusDistribution['processing'] ?? 0 }},
                {{ $statusDistribution['resolved'] ?? 0 }},
                {{ $statusDistribution['closed'] ?? 0 }}
            ], backgroundColor: ['#FFC107','#17A2B8','#36A2EB','#00C851','#6C757D'] }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right' } } }
    });

    // Priority chart
    var prioCtx = document.getElementById('priorityChart');
    if (prioCtx) new Chart(prioCtx, {
        type: 'bar',
        data: {
            labels: ['高','中','低'],
            datasets: [{ data: [{{ $priorityDistribution['high'] }}, {{ $priorityDistribution['medium'] }}, {{ $priorityDistribution['low'] }}], backgroundColor: ['#EF4444','#F59E0B','#22C55E'] }]
        },
        options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { color: inkMuted } }, y: { ticks: { color: inkMuted } } } }
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
})();
</script>
@endsection
