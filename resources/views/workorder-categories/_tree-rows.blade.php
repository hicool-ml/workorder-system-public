@forelse($nodes as $node)
    @php($hasChildren = $node->relationLoaded('children') && $node->children->isNotEmpty())
    <tr class="tree-row hover:bg-surface-muted/50" data-id="{{ $node->id }}"
        data-parent-id="{{ $node->parent_id }}"
        data-collapsible="{{ $hasChildren ? 1 : 0 }}"
        data-default-collapsed="1">
        <td class="px-4 py-3">
            <div class="flex items-center" style="padding-left: {{ $depth * 1.5 }}rem">
                @if($hasChildren)
                    <button type="button" class="tree-toggle w-4 h-4 mr-1.5 flex-shrink-0 text-ink-muted hover:text-ink" data-id="{{ $node->id }}" aria-label="折叠/展开">
                        <svg class="w-3.5 h-3.5 transition-transform duration-150" fill="currentColor" viewBox="0 0 20 20"><path d="M5 7l5 5 5-5H5z"/></svg>
                    </button>
                @else
                    <span class="w-4 h-4 mr-1.5 flex-shrink-0"></span>
                @endif
                <span class="font-medium text-ink">{{ $node->name }}</span>
                @if($hasChildren)
                    <span class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs bg-surface-muted text-ink-muted">{{ $node->children->count() }} 项</span>
                @endif
            </div>
        </td>
        <td class="px-4 py-3 text-ink-muted text-sm">{{ $node->ticket_prefix ?: 'WO' }}</td>
        <td class="px-4 py-3">
            @if($node->status)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-700">启用</span>
            @else
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-500">禁用</span>
            @endif
        </td>
        <td class="px-4 py-3 text-right whitespace-nowrap">
            <a href="{{ route('workorder-categories.show', $node) }}" class="text-brand-600 hover:underline text-sm">查看</a>
            <a href="{{ route('workorder-categories.create', ['parent_id' => $node->id]) }}" class="text-brand-600 hover:underline text-sm ml-3">添加子分类</a>
            <a href="{{ route('workorder-categories.edit', $node) }}" class="text-brand-600 hover:underline text-sm ml-3">编辑</a>
            <form method="POST" action="{{ route('workorder-categories.toggle-status', $node->id) }}" class="inline ml-3">@csrf @method('PATCH')<button type="submit" class="{{ $node->status ? 'text-amber-600' : 'text-green-600' }} hover:underline text-sm">{{ $node->status ? '停用' : '启用' }}</button></form>
            @if(!$hasChildren && $node->workorders()->count() == 0)
            <form method="POST" action="{{ route('workorder-categories.destroy', $node->id) }}" class="inline ml-3" onsubmit="return confirm('确认删除？')">@csrf @method('DELETE')<button type="submit" class="text-red-500 hover:underline text-sm">删除</button></form>
            @endif
        </td>
    </tr>
    @if($hasChildren)
        @include('workorder-categories._tree-rows', ['nodes' => $node->children, 'depth' => $depth + 1])
    @endif
@empty
    @if($depth === 0)
        <tr><td colspan="4" class="px-4 py-8 text-center text-ink-muted">暂无分类，点击「新建分类」创建</td></tr>
    @endif
@endforelse
