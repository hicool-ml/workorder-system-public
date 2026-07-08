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
<div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-4 gap-4 mb-6">
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
        <p class="text-xs mt-1" style="color: var(--c-ink-muted);">已完成</p>
    </div>
    <div class="card p-4">
        <p class="text-2xl font-bold text-red-600">{{ $stats['overdue_workorders'] }}</p>
        <p class="text-xs mt-1" style="color: var(--c-ink-muted);">超时工单</p>
    </div>
</div>
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    <div class="card p-4">
        <p class="text-2xl font-bold text-ink">{{ $stats['total_users'] }}</p>
        <p class="text-xs mt-1" style="color: var(--c-ink-muted);">总用户数</p>
    </div>
    <div class="card p-4">
        <p class="text-2xl font-bold text-ink">{{ $stats['total_departments'] }}</p>
        <p class="text-xs mt-1" style="color: var(--c-ink-muted);">部门数</p>
    </div>
    <div class="card p-4">
        <p class="text-2xl font-bold text-ink">{{ $stats['total_categories'] }}</p>
        <p class="text-xs mt-1" style="color: var(--c-ink-muted);">工单分类</p>
    </div>
    <div class="card p-4">
        <p class="text-2xl font-bold text-orange-600">{{ $stats['emergency_workorders'] }}</p>
        <p class="text-xs mt-1" style="color: var(--c-ink-muted);">紧急工单</p>
    </div>
</div>

{{-- Export --}}
<div class="card p-5 mb-6">
    <h3 class="text-sm font-semibold text-ink mb-4">数据导出</h3>
    <form method="GET" action="{{ route('reports.export') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
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
            <label class="label" for="format">格式</label>
            <select id="format" name="format" class="input">
                <option value="csv">CSV</option>
                <option value="xlsx">Excel</option>
            </select>
        </div>
        <div>
            <label class="label" for="status">状态</label>
            <select id="status" name="status" class="input">
                <option value="">全部状态</option>
                <option value="pending">待处理</option>
                <option value="assigned">已分配</option>
                <option value="processing">处理中</option>
                <option value="resolved">已解决</option>
                <option value="closed">已关闭</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4 M7 10l5 5 5-5 M12 15V3"/></svg>
            <span>导出数据</span>
        </button>
    </form>
</div>

{{-- Charts row --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="card p-5">
        <h3 class="text-sm font-semibold text-ink mb-4">工单状态分布</h3>
        <div class="flex items-center justify-center"><canvas id="statusChart"></canvas></div>
    </div>
    <div class="card p-5">
        <h3 class="text-sm font-semibold text-ink mb-4">工单分类分布</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-center">
            <div class="flex items-center justify-center"><canvas id="categoryChart"></canvas></div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="text-left border-b border-border">
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
</div>

{{-- Campus + processing time --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="card p-5">
        <h3 class="text-sm font-semibold text-ink mb-4">校区工单统计</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-center">
            <div class="flex items-center justify-center"><canvas id="campusChart"></canvas></div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="text-left border-b border-border">
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

{{-- Satisfaction + engineers --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="card p-5">
        <h3 class="text-sm font-semibold text-ink mb-4">满意度统计</h3>
        @if($satisfactionStats['total_visits'] > 0)
        <div class="mb-4">
            <div class="flex items-center gap-3">
                <svg class="w-8 h-8 text-amber-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                <div>
                    <p class="text-2xl font-bold text-ink">{{ $satisfactionStats['average_score'] }}</p>
                    <p class="text-xs" style="color: var(--c-ink-muted);">平均满意度（{{ $satisfactionStats['total_visits'] }} 人评价）</p>
                </div>
            </div>
        </div>
        <div class="space-y-3">
            @foreach($satisfactionStats['distribution'] as $score => $count)
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="text-ink">{{ $score }} 分</span>
                    <span style="color: var(--c-ink-muted);">{{ $count }} 人</span>
                </div>
                <div class="h-2.5 rounded-full overflow-hidden" style="background-color: var(--c-muted);">
                    <div class="h-full rounded-full transition-all" style="width: {{ ($count / max($satisfactionStats['total_visits'], 1)) * 100 }}%; background-color: var(--c-brand);"></div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="py-8 text-center" style="color: var(--c-ink-muted);">
            <p class="text-sm">暂无满意度数据</p>
        </div>
        @endif
    </div>
    <div class="card p-5">
        <h3 class="text-sm font-semibold text-ink mb-4">工程师处理统计</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="text-left border-b border-border">
                    <th class="py-2 font-medium" style="color: var(--c-ink-muted);">工程师</th>
                    <th class="py-2 font-medium text-right" style="color: var(--c-ink-muted);">总工单</th>
                    <th class="py-2 font-medium text-right" style="color: var(--c-ink-muted);">待处理</th>
                    <th class="py-2 font-medium text-right" style="color: var(--c-ink-muted);">已完成</th>
                </tr></thead>
                <tbody>
                @foreach($engineerStats as $engineer)
                <tr class="border-b border-border">
                    <td class="py-2 text-ink">{{ $engineer->name }}</td>
                    <td class="py-2 text-right text-ink">{{ $engineer->assigned_workorders_count }}</td>
                    <td class="py-2 text-right text-amber-600">{{ $engineer->pending_workorders_count }}</td>
                    <td class="py-2 text-right text-green-600">{{ $engineer->completed_workorders_count }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Recent daily stats table --}}
<div class="card p-5">
    <h3 class="text-sm font-semibold text-ink mb-4">最近{{ $dateRange == '90days' ? '90' : ($dateRange == '30days' ? '30' : '7') }}天工单统计</h3>
    <div class="md:hidden divide-y divide-border">
        @foreach($recentStats as $stat)
        <div class="py-3 flex items-center justify-between">
            <span class="text-sm text-ink">{{ $stat['display_date'] }}</span>
            <div class="flex items-center gap-3 text-xs">
                <span class="text-ink">{{ $stat['total'] }} 总</span>
                <span class="text-green-600">{{ $stat['completed'] }} 完成</span>
                <span class="text-amber-600">{{ $stat['pending'] }} 待</span>
            </div>
        </div>
        @endforeach
    </div>
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="text-left border-b border-border">
                <th class="py-2 font-medium" style="color: var(--c-ink-muted);">日期</th>
                <th class="py-2 font-medium text-right" style="color: var(--c-ink-muted);">总工单</th>
                <th class="py-2 font-medium text-right" style="color: var(--c-ink-muted);">已完成</th>
                <th class="py-2 font-medium text-right" style="color: var(--c-ink-muted);">待处理</th>
                <th class="py-2 font-medium text-right" style="color: var(--c-ink-muted);">紧急工单</th>
            </tr></thead>
            <tbody>
            @foreach($recentStats as $stat)
            <tr class="border-b border-border">
                <td class="py-2 text-ink">{{ $stat['display_date'] }}</td>
                <td class="py-2 text-right text-ink">{{ $stat['total'] }}</td>
                <td class="py-2 text-right text-green-600">{{ $stat['completed'] }}</td>
                <td class="py-2 text-right text-amber-600">{{ $stat['pending'] }}</td>
                <td class="py-2 text-right text-red-600">{{ $stat['emergency'] }}</td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.min.js"></script>
<script>
(function() {
    var statusCtx = document.getElementById('statusChart');
    if (statusCtx) new Chart(statusCtx, {
        type: 'pie',
        data: {
            labels: ['待处理', '已分配', '处理中', '已解决', '待验证', '已关闭', '已拒绝'],
            datasets: [{
                data: [
                    {{ $statusDistribution['pending'] ?? 0 }},
                    {{ $statusDistribution['assigned'] ?? 0 }},
                    {{ $statusDistribution['processing'] ?? 0 }},
                    {{ $statusDistribution['resolved'] ?? 0 }},
                    {{ $statusDistribution['verifying'] ?? 0 }},
                    {{ $statusDistribution['closed'] ?? 0 }},
                    {{ $statusDistribution['rejected'] ?? 0 }}
                ],
                backgroundColor: ['#FFC107', '#17A2B8', '#36A2EB', '#00C851', '#FF9800', '#6C757D', '#DC3545']
            }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });

    var categoryCtx = document.getElementById('categoryChart');
    if (categoryCtx) new Chart(categoryCtx, {
        type: 'doughnut',
        data: {
            labels: [@foreach($categoryDistribution as $category)'{{ $category->name }}',@endforeach],
            datasets: [{
                data: [@foreach($categoryDistribution as $category){{ $category->workorders_count }},@endforeach],
                backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF']
            }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });

    var campusCtx = document.getElementById('campusChart');
    if (campusCtx) new Chart(campusCtx, {
        type: 'bar',
        data: {
            labels: [@foreach($campusStats as $key => $stat)'{{ $stat['name'] }}',@endforeach],
            datasets: [{
                label: '工单数量',
                data: [@foreach($campusStats as $key => $stat){{ $stat['total'] }},@endforeach],
                backgroundColor: '#2563eb'
            }]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } } }
    });
})();
</script>
@endsection