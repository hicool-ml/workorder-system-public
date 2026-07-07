@extends('layouts.app')

@section('title', '统计报表')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">统计报表</h3>
                    <div class="btn-group btn-group-sm">
                        <a href="{{ route('reports.index', ['date_range' => '7days']) }}"
                           class="btn {{ $dateRange == '7days' ? 'btn-primary' : 'btn-outline-primary' }}">
                            7天
                        </a>
                        <a href="{{ route('reports.index', ['date_range' => '30days']) }}"
                           class="btn {{ $dateRange == '30days' ? 'btn-primary' : 'btn-outline-primary' }}">
                            30天
                        </a>
                        <a href="{{ route('reports.index', ['date_range' => '90days']) }}"
                           class="btn {{ $dateRange == '90days' ? 'btn-primary' : 'btn-outline-primary' }}">
                            90天
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- 概览统计 -->
                    <div class="row mb-4">
                        <div class="col-md-2">
                            <div class="info-box">
                                <span class="info-box-icon bg-info"><i class="fas fa-list"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">总工单数</span>
                                    <span class="info-box-number">{{ $stats['total_workorders'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning"><i class="fas fa-clock"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">待处理</span>
                                    <span class="info-box-number">{{ $stats['pending_workorders'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="info-box">
                                <span class="info-box-icon bg-success"><i class="fas fa-check"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">已完成</span>
                                    <span class="info-box-number">{{ $stats['completed_workorders'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="info-box">
                                <span class="info-box-icon bg-primary"><i class="fas fa-users"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">总用户数</span>
                                    <span class="info-box-number">{{ $stats['total_users'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="info-box">
                                <span class="info-box-icon bg-secondary"><i class="fas fa-building"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">部门数</span>
                                    <span class="info-box-number">{{ $stats['total_departments'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="info-box">
                                <span class="info-box-icon bg-danger"><i class="fas fa-tags"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">工单分类</span>
                                    <span class="info-box-number">{{ $stats['total_categories'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="info-box">
                                <span class="info-box-icon bg-danger"><i class="fas fa-exclamation-triangle"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">超时工单</span>
                                    <span class="info-box-number">{{ $stats['overdue_workorders'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="info-box">
                                <span class="info-box-icon bg-danger"><i class="fas fa-bolt"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">紧急工单</span>
                                    <span class="info-box-number">{{ $stats['emergency_workorders'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 导出功能 -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">数据导出</h5>
                                </div>
                                <div class="card-body">
                                    <form method="GET" action="{{ route('reports.export') }}" class="form-inline">
                                        @csrf
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="start_date">开始日期</label>
                                                    <input type="date" id="start_date" name="start_date" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="end_date">结束日期</label>
                                                    <input type="date" id="end_date" name="end_date" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label for="format">格式</label>
                                                    <select id="format" name="format" class="form-control">
                                                        <option value="csv">CSV</option>
                                                        <option value="xlsx">Excel</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label for="status">状态</label>
                                                    <select id="status" name="status" class="form-control">
                                                        <option value="">全部状态</option>
                                                        <option value="pending">待处理</option>
                                                        <option value="assigned">已分配</option>
                                                        <option value="processing">处理中</option>
                                                        <option value="resolved">已解决</option>
                                                        <option value="closed">已关闭</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label for="campus">校区</label>
                                                    <select id="campus" name="campus" class="form-control">
                                                        <option value="">全部校区</option>
                                                        <option value="old_campus">老校区</option>
                                                        <option value="new_campus">新校区</option>
                                                        <option value="asean_campus">东盟校区</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mt-3">
                                            <div class="col-12 text-center">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-download"></i> 导出数据
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 最近N天统计 -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">最近{{ $dateRange == '90days' ? '90' : ($dateRange == '30days' ? '30' : '7') }}天工单统计</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>日期</th>
                                                    <th>总工单</th>
                                                    <th>已完成</th>
                                                    <th>待处理</th>
                                                    <th>紧急工单</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($recentStats as $stat)
                                                <tr>
                                                    <td>{{ $stat['display_date'] }}</td>
                                                    <td>{{ $stat['total'] }}</td>
                                                    <td>{{ $stat['completed'] }}</td>
                                                    <td>{{ $stat['pending'] }}</td>
                                                    <td>{{ $stat['emergency'] }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- 工单状态分布 -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">工单状态分布</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="statusChart" width="400" height="200"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 工单分类分布 -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">工单分类分布</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="chart-container">
                                                <canvas id="categoryChart" width="400" height="200"></canvas>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <thead>
                                                        <tr>
                                                                <th>分类名称</th>
                                                                <th>工单数量</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($categoryDistribution as $category)
                                                        <tr>
                                                            <td>{{ $category->name }}</td>
                                                            <td>{{ $category->workorders_count }}</td>
                                                        </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- 校区工单统计 -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">校区工单统计</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="chart-container">
                                                <canvas id="campusChart" width="400" height="200"></canvas>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <thead>
                                                        <tr>
                                                            <th>校区</th>
                                                            <th>总工单</th>
                                                            <th>待处理</th>
                                                            <th>已完成</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($campusStats as $key => $stat)
                                                        <tr>
                                                            <td>{{ $stat['name'] }}</td>
                                                            <td>{{ $stat['total'] }}</td>
                                                            <td>{{ $stat['pending'] }}</td>
                                                            <td>{{ $stat['completed'] }}</td>
                                                        </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        
                        <!-- 处理时长统计 -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">处理时长统计</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="info-box">
                                                <span class="info-box-icon bg-info"><i class="fas fa-clock"></i></span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">平均处理时长</span>
                                                    <span class="info-box-number">{{ $processingTimeStats['average_time'] }} 分钟</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-box">
                                                <span class="info-box-icon bg-success"><i class="fas fa-check"></i></span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">已完成工单</span>
                                                    <span class="info-box-number">{{ $processingTimeStats['total_completed'] }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mt-3">
                                            <div class="col-md-6">
                                                <small class="text-muted">最短处理时长: {{ $processingTimeStats['min_time'] }} 分钟</small>
                                            </div>
                                            <div class="col-md-6">
                                                <small class="text-muted">最长处理时长: {{ $processingTimeStats['max_time'] }} 分钟</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <!-- 满意度统计 -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">满意度统计</h5>
                                    </div>
                                    <div class="card-body">
                                        @if($satisfactionStats['total_visits'] > 0)
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="info-box">
                                                    <span class="info-box-icon bg-warning"><i class="fas fa-star"></i></span>
                                                    <div class="info-box-content">
                                                        <span class="info-box-text">平均满意度</span>
                                                        <span class="info-box-number">{{ $satisfactionStats['average_score'] }} 分</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-12 mt-3">
                                                <h6>满意度分布</h6>
                                                <div class="progress-container">
                                                    @foreach($satisfactionStats['distribution'] as $score => $count)
                                                    <div class="mb-2">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <span>{{ $score }}分</span>
                                                            <span>{{ $count }}人</span>
                                                        </div>
                                                        <div class="progress">
                                                            <div class="progress-bar" style="width: {{ ($count / $satisfactionStats['total_visits']) * 100 }}%"></div>
                                                        </div>
                                                    </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                        @else
                                        <div class="text-center text-muted">
                                            <i class="fas fa-star fa-2x mb-2"></i>
                                            <p>暂无满意度数据</p>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 工程师处理统计 -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">工程师处理统计</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>工程师</th>
                                                    <th>总工单</th>
                                                    <th>待处理</th>
                                                    <th>已完成</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($engineerStats as $engineer)
                                                <tr>
                                                    <td>{{ $engineer->name }}</td>
                                                    <td>{{ $engineer->assigned_workorders_count }}</td>
                                                    <td>{{ $engineer->pending_workorders_count }}</td>
                                                    <td>{{ $engineer->completed_workorders_count }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.min.js"></script>
<style>
.progress-container {
    max-width: 300px;
    margin: 0 auto;
}
.progress {
    height: 20px;
    background-color: #e9ecef;
    border-radius: 10px;
    overflow: hidden;
}
.progress-bar {
    height: 100%;
    background-color: #007bff;
    transition: width 0.3s ease;
}
</style>
<script>
$(document).ready(function() {
    // 工单状态分布图表
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    const statusChart = new Chart(statusCtx, {
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
                backgroundColor: [
                    '#FFC107', // 黄色 - 待处理
                    '#17A2B8', // 蓝色 - 已分配
                    '#36A2EB', // 浅蓝 - 处理中
                    '#00C851', // 绿色 - 已解决
                    '#FF9800', // 橙色 - 待验证
                    '#6C757D', // 灰色 - 已关闭
                    '#DC3545'  // 红色 - 已拒绝
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });

    // 工单分类分布图表
    const categoryCtx = document.getElementById('categoryChart')?.getContext('2d');
    if (categoryCtx) {
        const categoryChart = new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: [
                    @foreach($categoryDistribution as $category)
                    '{{ $category->name }}',
                    @endforeach
                ],
                datasets: [{
                    data: [
                        @foreach($categoryDistribution as $category)
                        {{ $category->workorders_count }},
                        @endforeach
                    ],
                    backgroundColor: [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });
    }

    // 校区分布图表
    const campusCtx = document.getElementById('campusChart')?.getContext('2d');
    if (campusCtx) {
        const campusChart = new Chart(campusCtx, {
            type: 'bar',
            data: {
                labels: [
                    @foreach($campusStats as $key => $stat)
                    '{{ $stat['name'] }}',
                    @endforeach
                ],
                datasets: [{
                    label: '工单数量',
                    data: [
                        @foreach($campusStats as $key => $stat)
                        {{ $stat['total'] }},
                        @endforeach
                    ],
                    backgroundColor: '#007bff'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
});
</script>
@endsection
@endsection