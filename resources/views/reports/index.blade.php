@extends('layouts.app')

@section('title', '统计报表')

@section('content')
<div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
    <h1 class="text-xl font-semibold text-ink">统计报表</h1>
    <div class="flex flex-wrap items-center gap-2 text-sm">
        <div class="flex items-center gap-1 p-1 rounded-lg" style="background-color: var(--c-muted);">
            <button type="button" id="catModeNatural" class="px-3 py-1 rounded-md text-sm font-medium transition-colors" style="color: var(--c-ink-muted);">自然月</button>
            <button type="button" id="catModeCustom" class="px-3 py-1 rounded-md text-sm font-medium transition-colors" style="color: var(--c-ink-muted);">自定义周期</button>
        </div>
        <span style="color:var(--c-ink-muted);">起始日期</span>
        <input type="date" id="catStart" value="{{ $categoryTrend['startStr'] }}" class="input" style="padding:0.3rem 0.5rem;font-size:0.8rem;width:auto;">
        <span style="color:var(--c-ink-muted);">周期数</span>
        <input type="number" id="catPeriods" min="1" max="24" value="{{ $categoryTrend['periodCount'] }}" class="input" style="width:72px;padding:0.3rem 0.5rem;font-size:0.8rem;">

        <button type="button" id="catApplyBtn" class="btn btn-primary btn-sm">应用</button>
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
    <h3 class="text-sm font-semibold text-ink mb-4">工单量趋势</h3>
    <div style="position:relative;height:300px;"><canvas id="trendChart"></canvas></div>
</div>

{{-- Charts row: network + media sub-category top10 --}}
<div class="grid grid-cols-1 gap-6 mb-6">
    <div class="card p-5">
        <h3 class="text-sm font-semibold text-ink mb-4">网络</h3>
        <div id="networkSubChart" class="treemap" style="aspect-ratio:4/3;"></div>
    </div>
    <div class="card p-5">
        <h3 class="text-sm font-semibold text-ink mb-4">多媒体</h3>
        <div id="mediaSubChart" class="treemap" style="aspect-ratio:4/3;"></div>
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


{{-- 故障类型占比（百分比堆积柱形图） --}}
<div class="card p-5 mb-6">
    <h3 class="text-sm font-semibold text-ink mb-4">故障类型占比</h3>
    <div style="position:relative;height:520px;max-width:1100px;margin:0 auto;"><canvas id="categoryTrendChart"></canvas></div>
</div>
{{-- Campus + processing time --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="card p-5">
        <h3 class="text-sm font-semibold text-ink mb-4">区域工单统计</h3>
        <div class="overflow-x-auto max-h-[280px]">
            <table class="w-full text-sm">
                <thead><tr class="text-left border-b border-border sticky top-0" style="background-color: var(--c-card);">
                    <th class="py-2 font-medium" style="color: var(--c-ink-muted);">区域</th>
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
    var cssVar = function(name){ return getComputedStyle(document.documentElement).getPropertyValue(name).trim(); };
    var inkMuted = cssVar('--c-ink-muted') || '#888';

    // Trend chart
    <?php
        $trendStats = $recentStats['stats'];
        $trendCats = $recentStats['topCats'];
        // 固定排序：网络→多媒体→专项，其它放最后
        $ctOrder = ['网络' => 1, '多媒体' => 2, '专项' => 3, '其它' => 4];
        $trendCats = collect($trendCats)->sortBy(function ($n, $id) use ($ctOrder) { return $ctOrder[$n] ?? 99; })->toArray();
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

    @php
        $treemapNetwork = collect($networkSubDistribution)->map(function ($i) { return ['name' => $i['name'], 'count' => (int)$i['count']]; })->values();
        $treemapMedia = collect($mediaSubDistribution)->map(function ($i) { return ['name' => $i['name'], 'count' => (int)$i['count']]; })->values();
    @endphp
    var networkData = {!! json_encode($treemapNetwork, JSON_UNESCAPED_UNICODE) !!};
    var mediaData = {!! json_encode($treemapMedia, JSON_UNESCAPED_UNICODE) !!};

    // 矩形树图（squarified treemap）：面积映射数值，颜色区分类别
    var TM_PALETTE = ['#2563eb', '#4f46e5', '#0891b2', '#7c3aed', '#db2777', '#dc2626', '#d97706', '#16a34a', '#0d9488', '#64748b'];

    function squarify(items, x, y, w, h) {
        var rects = [];
        if (!items.length || w <= 0 || h <= 0) return rects;
        var total = 0;
        for (var i = 0; i < items.length; i++) total += items[i].value;
        if (total <= 0) return rects;
        var area = w * h;
        var scaled = items.map(function (it) { return { name: it.name, value: it.value, area: (it.value / total) * area }; });
        var c = { x: x, y: y, w: w, h: h };
        var row = [], idx = 0, side = Math.min(c.w, c.h);

        function worstRatio(r, s) {
            var sum = 0, rmax = 0, rmin = Infinity;
            for (var k = 0; k < r.length; k++) { var a = r[k].area; sum += a; if (a > rmax) rmax = a; if (a < rmin) rmin = a; }
            var s2 = s * s;
            return Math.max((s2 * rmax) / (sum * sum), (sum * sum) / (s2 * rmin));
        }
        function layoutRow(r) {
            var sum = 0;
            for (var k = 0; k < r.length; k++) sum += r[k].area;
            if (c.w >= c.h) {
                var colW = sum / c.h, oy = 0;
                for (var k = 0; k < r.length; k++) { var ih = r[k].area / colW; rects.push({ x: c.x, y: c.y + oy, w: colW, h: ih, item: r[k] }); oy += ih; }
                c.x += colW; c.w -= colW;
            } else {
                var rowH = sum / c.w, ox = 0;
                for (var k = 0; k < r.length; k++) { var iw = r[k].area / rowH; rects.push({ x: c.x + ox, y: c.y, w: iw, h: rowH, item: r[k] }); ox += iw; }
                c.y += rowH; c.h -= rowH;
            }
        }
        while (idx < scaled.length) {
            var candidate = row.concat([scaled[idx]]);
            if (row.length === 0 || worstRatio(candidate, side) <= worstRatio(row, side)) { row = candidate; idx++; }
            else { layoutRow(row); row = []; side = Math.min(c.w, c.h); }
        }
        if (row.length) layoutRow(row);
        return rects;
    }

    // 文字测量：用 canvas 量取指定字号下文字宽度
    var _tmMeasureCtx;
    function tmTextWidth(text, fontSize, weight) {
        if (!_tmMeasureCtx) _tmMeasureCtx = document.createElement('canvas').getContext('2d');
        _tmMeasureCtx.font = (weight || '600') + ' ' + fontSize + 'px ' + getComputedStyle(document.body).fontFamily;
        return _tmMeasureCtx.measureText(text).width;
    }
    // 逐级缩小字号直到文字适配宽度，最低不低于 minSize
    function tmFitSize(text, maxWidth, base, minSize, weight) {
        var s = base;
        while (s > minSize && tmTextWidth(text, s, weight) > maxWidth) s = Math.max(minSize, s - 0.5);
        return s;
    }

    function renderTreemap(container, data, palette) {
        if (!container) return;
        container.innerHTML = '';
        var total = 0;
        for (var i = 0; i < data.length; i++) total += data[i].count;
        if (!data.length || total <= 0) {
            var empty = document.createElement('div');
            empty.className = 'treemap-empty';
            empty.textContent = '暂无数据';
            container.appendChild(empty);
            return;
        }
        var items = data.map(function (d) { return { name: d.name, value: d.count }; });
        items.sort(function (a, b) { return b.value - a.value; });
        var W = container.clientWidth || container.offsetWidth;
        var H = container.clientHeight;
        var rects = squarify(items, 0, 0, W, H);
        var gap = 3, frag = document.createDocumentFragment();
        for (var r = 0; r < rects.length; r++) {
            var rect = rects[r];
            var cell = document.createElement('div');
            cell.className = 'treemap-cell';
            var cw = Math.max(rect.w - gap, 0);
            var ch = Math.max(rect.h - gap, 0);
            cell.style.left = (rect.x + gap / 2) + 'px';
            cell.style.top = (rect.y + gap / 2) + 'px';
            cell.style.width = cw + 'px';
            cell.style.height = ch + 'px';
            cell.style.backgroundColor = palette[r % palette.length];
            var pct = Math.round(rect.item.value / total * 100);
            cell.title = rect.item.name + '：' + rect.item.value + ' 起（' + pct + '%）';

            // 字号直接按占比比例缩放：占比越大字号越大，占比越小字号越小
            var ratio = rect.item.value / total;         // 0-1
            var nameSize = Math.max(8, Math.round(9 + ratio * 22));   // 9px(最小) ~ 31px(最大)
            var countText = rect.item.value + '起 ' + pct + '%';
            var countSize = Math.max(7, Math.round(nameSize * 0.75));
            var padW = Math.max(cw - 8, 12);

            // 保证完整显示：超宽时逐级缩小至可容纳
            nameSize = tmFitSize(rect.item.name, padW, nameSize, 8, '600');
            countSize = tmFitSize(countText, padW, countSize, 7, '400');

            var nm = document.createElement('div'); nm.className = 'tm-name';
            nm.textContent = rect.item.name;
            nm.style.fontSize = nameSize + 'px';
            nm.style.lineHeight = '1.15';
            nm.style.whiteSpace = 'normal';
            nm.style.wordBreak = 'break-all';
            cell.appendChild(nm);

            var ct = document.createElement('div'); ct.className = 'tm-count';
            ct.textContent = countText;
            ct.style.fontSize = countSize + 'px';
            ct.style.lineHeight = '1.15';
            ct.style.marginTop = '2px';
            ct.style.whiteSpace = 'normal';
            ct.style.wordBreak = 'break-all';
            cell.appendChild(ct);
            frag.appendChild(cell);
        }
        container.appendChild(frag);
    }

    var networkEl = document.getElementById('networkSubChart');
    var mediaEl = document.getElementById('mediaSubChart');
    function drawTreemaps() {
        renderTreemap(networkEl, networkData, TM_PALETTE);
        renderTreemap(mediaEl, mediaData, TM_PALETTE);
    }
    drawTreemaps();
    var tmResizeTimer;
    window.addEventListener('resize', function () { clearTimeout(tmResizeTimer); tmResizeTimer = setTimeout(drawTreemaps, 200); });

    // 故障类型占比：百分比堆积柱形图（含汇总列，标注数量+百分比）
    @php
        $ctColors = ['#2563eb', '#7c3aed', '#f59e0b', '#64748b', '#dc2626', '#16a34a'];
    @endphp
    var ctLabels = @json($categoryTrend['periodLabels']);
    var ctDatasets = [];
    var ctCounts = [];
    @foreach($categoryTrend['categories'] as $ctIdx => $ctCat)
    ctDatasets.push({
        label: @json($ctCat['name']),
        data: @json($ctCat['percents']),
        backgroundColor: @json($ctColors[$ctIdx % count($ctColors)]),
        borderWidth: 1,
        borderColor: 'rgba(255,255,255,0.45)',
        barPercentage: 0.6,
        categoryPercentage: 0.7
    });
    ctCounts.push(@json($ctCat['counts']));
    @endforeach

    // 标注插件：高色块内标注数量+百分比；矮色块改为外部标注+引导线，保证打印/截图都能看到每项数据
    // 标注插件：大色块内标注数量+百分比；矮色块统一在柱顶上方分层标注，避免重叠
    var catLabelPlugin = {
        id: 'catLabels',
        afterDatasetsDraw: function (chart) {
            var ctx = chart.ctx;
            var fontFamily = getComputedStyle(document.body).fontFamily;
            ctx.save();
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            var smallByBar = {};
            chart.data.datasets.forEach(function (ds, di) {
                var meta = chart.getDatasetMeta(di);
                meta.data.forEach(function (el, bi) {
                    var cnt = ctCounts[di][bi];
                    var pct = ds.data[bi];
                    if (!cnt) return;
                    var h = Math.abs(el.base - el.y);
                    var topY = Math.min(el.base, el.y);
                    if (h >= 18) {
                        // 色块内居中标注（白字）
                        ctx.fillStyle = '#fff';
                        ctx.font = '600 11px ' + fontFamily;
                        var cy = (el.base + el.y) / 2;
                        ctx.fillText(cnt + '起', el.x, cy - 7);
                        if (h >= 34) ctx.fillText(pct + '%', el.x, cy + 8);
                    } else {
                        if (!smallByBar[bi]) smallByBar[bi] = { x: el.x, top: Infinity, segs: [] };
                        if (topY < smallByBar[bi].top) smallByBar[bi].top = topY;
                        smallByBar[bi].segs.push({ cnt: cnt, pct: pct, color: ds.backgroundColor, topY: topY });
                    }
                });
            });
            // 矮色块：统一在柱顶上方分层标注（自上而下排列，间距16px），引导线连接
            Object.keys(smallByBar).forEach(function (bi) {
                var info = smallByBar[bi];
                var segs = info.segs.sort(function (a, b) { return a.topY - b.topY; });
                var baseY = info.top - 24;
                ctx.font = '600 10px ' + fontFamily;
                for (var si = 0; si < segs.length; si++) {
                    var s = segs[si];
                    var labelY = baseY - si * 16;
                    ctx.strokeStyle = s.color;
                    ctx.lineWidth = 1;
                    ctx.beginPath();
                    ctx.moveTo(info.x, s.topY - 1);
                    ctx.lineTo(info.x, labelY + 5);
                    ctx.stroke();
                    ctx.fillStyle = s.color;
                    ctx.fillText(s.cnt + '起 ' + s.pct + '%', info.x, labelY);
                }
            });
            ctx.restore();
        }
    };

    var ctCtx = document.getElementById('categoryTrendChart');
    if (ctCtx) new Chart(ctCtx, {
        type: 'bar',
        data: { labels: ctLabels, datasets: ctDatasets },
        options: {
            responsive: true, maintainAspectRatio: false,
            layout: { padding: { top: 70 } },
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'right', labels: { color: inkMuted, boxWidth: 14, padding: 12, font: { size: 13 } } },
                tooltip: {
                    callbacks: {
                        label: function (c) {
                            var cnt = ctCounts[c.datasetIndex][c.dataIndex];
                            return c.dataset.label + '：' + cnt + ' 起（' + c.parsed.y + '%）';
                        }
                    }
                }
            },
            scales: {
                x: { stacked: true, ticks: { color: inkMuted, autoSkip: false, maxRotation: 45, minRotation: 25 } },
                y: { stacked: true, max: 100, beginAtZero: true, ticks: { color: inkMuted, callback: function (v) { return v + '%'; } } }
            }
        },
        plugins: [catLabelPlugin]
    });

    // 周期控件交互
    var catMode = @json($categoryTrend['mode']);
    var catModeNatural = document.getElementById('catModeNatural');
    var catModeCustom = document.getElementById('catModeCustom');

    var catApplyBtn = document.getElementById('catApplyBtn');
    function refreshCatModeUI() {
        var isCustom = (catMode === 'custom');
        var activeStyle = 'background-color: var(--c-brand); color:#fff;';
        var inactiveStyle = 'color: var(--c-ink-muted);';
        catModeNatural.setAttribute('style', isCustom ? inactiveStyle : activeStyle);
        catModeCustom.setAttribute('style', isCustom ? activeStyle : inactiveStyle);

    }
    catModeNatural.addEventListener('click', function () { catMode = 'natural'; refreshCatModeUI(); });
    catModeCustom.addEventListener('click', function () { catMode = 'custom'; refreshCatModeUI(); });
    refreshCatModeUI();
    function catApply() {
        var params = [];
        var catStartVal = document.getElementById('catStart').value;
        params.push('cat_mode=' + catMode);
        if (catStartVal) params.push('cat_start=' + catStartVal);
        params.push('cat_periods=' + document.getElementById('catPeriods').value);

        window.location.href = '{{ route('reports.index') }}?' + params.join('&');
    }
    catApplyBtn.addEventListener('click', catApply);
    // Auto-apply on input change: no need to click button manually
    document.getElementById('catStart').addEventListener('change', catApply);
    document.getElementById('catPeriods').addEventListener('change', catApply);

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
