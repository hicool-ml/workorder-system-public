@forelse($nodes as $node)
    <tr class="hover:bg-surface-muted/50">
        <td class="px-4 py-3">
            <div class="flex items-center" style="padding-left: {{ $depth * 1.5 }}rem">
                @if($node->relationLoaded('children') && $node->children->isNotEmpty())
                    <svg class="w-3.5 h-3.5 text-ink-muted mr-1.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M5 7l5 5 5-5H5z"/></svg>
                @else
                    <span class="w-3.5 h-3.5 mr-1.5 flex-shrink-0"></span>
                @endif
                <span class="font-medium text-ink">{{ $node->name }}</span>
                @if($node->level)
                    <span class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded text-xs bg-surface-muted text-ink-muted">{{ $node->level->name }}</span>
                @endif
            </div>
        </td>
        <td class="px-4 py-3 text-ink-muted text-sm">{{ $node->code ?? '' }}</td>
        <td class="px-4 py-3">
            @if($node->status === 'active')
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-700">启用</span>
            @else
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-500">禁用</span>
            @endif
        </td>
        <td class="px-4 py-3 text-right whitespace-nowrap">
            <a href="{{ route('locations.show', $node) }}" class="text-brand-600 hover:underline text-sm">详情</a>
            <a href="{{ route('locations.create', ['parent_id' => $node->id]) }}" class="text-brand-600 hover:underline text-sm ml-3">添加子节点</a>
            <a href="{{ route('locations.edit', $node) }}" class="text-brand-600 hover:underline text-sm ml-3">编辑</a>
            <form action="{{ route('locations.destroy', $node) }}" method="POST" class="inline ml-3" onsubmit="return confirm('确定删除该地址？')">
                @csrf @method('DELETE')
                <button type="submit" class="text-red-500 hover:underline text-sm">删除</button>
            </form>
        </td>
    </tr>
    @if($node->relationLoaded('children') && $node->children->isNotEmpty())
        @include('locations._tree-rows', ['nodes' => $node->children, 'depth' => $depth + 1])
    @endif
@empty
    @if($depth === 0)
        <tr><td colspan="4" class="px-4 py-8 text-center text-ink-muted">暂无地址数据，点击"新增地址"或先配置 <a href="{{ route('location-levels.index') }}" class="text-brand-600 underline">地址层级定义</a></td></tr>
    @endif
@endforelse