<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workorder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WorkorderUiHelperTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * 全局辅助函数应在非视图上下文（测试/命令行）中可用，
     * 不再依赖 Blade 视图渲染。
     */
    public function test_global_helpers_available_without_blade_rendering(): void
    {
        $this->assertTrue(function_exists('getWorkorderActionButtons'));
        $this->assertTrue(function_exists('svg_icon'));
        $this->assertTrue(function_exists('canResolveWorkorder'));
        $this->assertTrue(function_exists('canStartWorkorder'));
        $this->assertTrue(function_exists('getCollaborationIcon'));
    }

    /**
     * svg_icon 应返回有效的 SVG HTML
     */
    public function test_svg_icon_returns_html(): void
    {
        $html = svg_icon('eye', 'w-5 h-5');
        $this->assertStringContainsString('<svg', $html);
        $this->assertStringContainsString('w-5 h-5', $html);
    }

    /**
     * 未知图标名应返回空字符串而非报错
     */
    public function test_svg_icon_returns_empty_for_unknown_name(): void
    {
        $this->assertSame('', svg_icon('nonexistent'));
    }
}
