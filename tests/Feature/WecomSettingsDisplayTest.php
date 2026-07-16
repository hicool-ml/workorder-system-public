<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WecomSettingsDisplayTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Webhook 启用状态下，页面应显示"已启用"和 checked checkbox
     */
    public function test_wecom_page_shows_enabled_when_webhook_is_enabled(): void
    {
        $admin = User::factory()->admin()->create();

        \App\Models\SystemSetting::set('wecom_webhook_enabled', true, 'boolean', 'test', false);
        \App\Models\SystemSetting::set('wecom_send_mode', 'webhook', 'string', 'test', false);

        $response = $this->actingAs($admin)->get('/system-settings/wecom');

        $response->assertStatus(200);
        $response->assertSee('checked', false);
        $response->assertSeeText('已启用');
    }

    /**
     * Webhook 未启用状态下，页面应显示"未启用"且 checkbox 不 checked
     */
    public function test_wecom_page_shows_disabled_when_webhook_is_disabled(): void
    {
        $admin = User::factory()->admin()->create();

        \App\Models\SystemSetting::set('wecom_webhook_enabled', false, 'boolean', 'test', false);
        \App\Models\SystemSetting::set('wecom_send_mode', 'webhook', 'string', 'test', false);

        $response = $this->actingAs($admin)->get('/system-settings/wecom');

        $response->assertStatus(200);
       $response->assertSeeText('未启用');
   }

    /**
     * 系统设置首页的"集成配置"卡片：Webhook 启用时应显示"已启用"而非"未启用"
     * 回归测试：旧代码用 === '1' 与布尔值严格比较，导致永远显示"未启用"
     */
    public function test_index_page_shows_enabled_when_webhook_is_enabled(): void
    {
        $admin = User::factory()->admin()->create();

        \App\Models\SystemSetting::set('wecom_webhook_enabled', true, 'boolean', 'test', false);
        \App\Models\SystemSetting::set('wecom_send_mode', 'webhook', 'string', 'test', false);

        $response = $this->actingAs($admin)->get('/system-settings');

        $response->assertStatus(200);
        $response->assertSeeText('已启用');
        $response->assertDontSeeText('未启用');
    }

    /**
     * 系统设置首页的"集成配置"卡片：Webhook 未启用时应显示"未启用"
     */
    public function test_index_page_shows_disabled_when_webhook_is_disabled(): void
    {
        $admin = User::factory()->admin()->create();

        \App\Models\SystemSetting::set('wecom_webhook_enabled', false, 'boolean', 'test', false);
        \App\Models\SystemSetting::set('wecom_send_mode', 'webhook', 'string', 'test', false);

        $response = $this->actingAs($admin)->get('/system-settings');

        $response->assertStatus(200);
        $response->assertSeeText('未启用');
    }
}
