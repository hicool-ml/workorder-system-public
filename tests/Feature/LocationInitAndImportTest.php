<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\LocationLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * 地址管理：基础地址初始化 + 日常地址 CSV 导入 的端到端测试。
 *
 * 测试数据使用通用占位（"测试省 / 测试市 / 总部园区 / A 楼 / 101 室"），
 * 不依赖任何具体单位的真实地址。
 */
class LocationInitAndImportTest extends TestCase
{
    use RefreshDatabase;

    protected function adminUser(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_base_address_not_initialized_shows_banner_on_index(): void
    {
        // 无任何基础地址层级
        LocationLevel::query()->delete();

        $response = $this->actingAs($this->adminUser())->get(route('locations.index'));

        $response->assertOk();
        $response->assertSee('基础地址尚未初始化');
        $response->assertSee('去初始化基础地址');
    }

    public function test_base_address_form_renders(): void
    {
        $this->seed(\Database\Seeders\LocationLevelSeeder::class);

        $response = $this->actingAs($this->adminUser())->get(route('locations.base-address'));

        $response->assertOk();
        $response->assertSee('基础地址');
        // 项目列表页面特征
        $response->assertSee('新增项目');
    }

    public function test_init_base_address_creates_chain(): void
    {
        $this->seed(\Database\Seeders\LocationLevelSeeder::class);

        $response = $this->actingAs($this->adminUser())->post(route('locations.projects.store'), [
            'name_province' => '测试省',
            'name_city' => '测试市',
            'name_district' => '测试区',
            'name_street' => '测试大道',
            'name_road' => '1号',
            'code_province' => '000000',
            'code_city' => '000100',
            'code_district' => '000101',
        ]);

        $response->assertRedirect(route('locations.base-address'));
        $this->assertTrue(Location::isBaseAddressInitialized());

        // 新建项目后应能在项目列表里看到
        $response = $this->actingAs($this->adminUser())->get(route('locations.base-address'));
        $response->assertSee('测试省 / 测试市 / 测试区 / 测试大道 / 1号');
    }

    public function test_import_template_downloads_csv_with_daily_levels(): void
    {
        $this->seed(\Database\Seeders\LocationLevelSeeder::class);
        $this->actingAs($this->adminUser())->post(route('locations.projects.store'), [
            'name_province' => '测试省', 'name_city' => '测试市', 'name_district' => '测试区',
            'name_street' => '测试大道', 'name_road' => '1号',
        ]);

        $response = $this->actingAs($this->adminUser())->get(route('locations.import-template'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $content = $response->streamedContent();
        $this->assertStringContainsString('区域', $content);
        $this->assertStringContainsString('楼栋', $content);
        $this->assertStringContainsString('房间', $content);
    }

    public function test_import_csv_creates_daily_nodes(): void
    {
        $this->seed(\Database\Seeders\LocationLevelSeeder::class);
        $this->actingAs($this->adminUser())->post(route('locations.projects.store'), [
            'name_province' => '测试省', 'name_city' => '测试市', 'name_district' => '测试区',
            'name_street' => '测试大道', 'name_road' => '1号',
        ]);

        $csv = "区域/园区,楼栋/建筑,房间/工位\n总部园区,A 楼,101 室\n总部园区,A 楼,102 室\n分部园区,B 楼\n";
        $file = UploadedFile::fake()->createWithContent('addresses.csv', $csv);

        $response = $this->actingAs($this->adminUser())->post(route('locations.import.store'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('locations.import'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('locations', ['name' => '总部园区']);
        $this->assertDatabaseHas('locations', ['name' => 'A 楼']);
        $this->assertDatabaseHas('locations', ['name' => '101 室']);
        $this->assertDatabaseHas('locations', ['name' => 'B 楼']);

        // 重复导入同一数据不产生重复节点
        $before = Location::count();
        $this->actingAs($this->adminUser())->post(route('locations.import.store'), ['file' => $file]);
        $this->assertSame($before, Location::count());
    }

    public function test_import_blocked_before_base_init(): void
    {
        // 模拟全新环境：无任何地址数据（基础地址未初始化）
        Location::query()->delete();
        $csv = "区域/园区,楼栋/建筑,房间/工位\n总部园区,A 楼,101 室\n";
        $file = UploadedFile::fake()->createWithContent('addresses.csv', $csv);

        $response = $this->actingAs($this->adminUser())->post(route('locations.import.store'), [
            'file' => $file,
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('locations', ['name' => 'A 楼']);
    }
}
