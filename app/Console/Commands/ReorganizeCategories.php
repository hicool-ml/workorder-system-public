<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * 工单分类体系整理（幂等，可重复执行）。
 *
 * 背景：旧系统工单管理员建类不规范，产生「网络/网络故障」重复顶级、
 * 「软件支持」误设、咨询类碎类等问题。本命令把两轮人工+内容分析确定的
 * 整理规则固化为可复现的数据操作，用于：
 *   - 生产 MySQL 库导入（import-mysql）后对新数据执行同样整理
 *   - 上线演练时对账校验
 *
 * 全部操作按"工单迁移 → 分类改挂 → 删除空类"顺序，重复执行时
 * 已迁移/已删除的步骤自动变为无操作（幂等）。
 *
 * 用法：
 *   php artisan categories:reorganize --dry-run   # 预览
 *   php artisan categories:reorganize             # 执行
 */
class ReorganizeCategories extends Command
{
    protected $signature = 'categories:reorganize {--dry-run : 只统计不写入}';

    protected $description = '整理工单分类体系：合并重复大类、归并碎类（幂等）';

    /** 同父级下按名称合并：[父级名称 => [旧子类名 => 保留子类名]] */
    private const SUB_MERGE_BY_NAME = [
        '网络' => [
            '咨询帐号' => '电话咨询',
            '咨询校园网' => '电话咨询',
            '咨询OA' => '电话咨询',
            '咨询邮箱' => '电话咨询',
            '咨询5G' => '电话咨询',
            '咨询VPN' => '电话咨询',
            '互联网' => '上网故障',
            '网络故障' => '上网故障',      // 多媒体下同名子类并入上网故障（内容实为"上不了网"）
        ],
    ];

    /** 顶级大类重复合并：旧顶级名 => 保留顶级名（旧顶级下子类按同名并入保留顶级） */
    private const TOP_MERGE = [
        '网络故障' => '网络',
    ];

    /** 顶级降级为子类：顶级名 => 目标父级名 */
    private const TOP_DEMOTE = [
        '软件支持' => '其它',
    ];

    /** 整树迁移：分类名 => 新父级名（含工单一起走） */
    private const REPARENT = [
        '监控故障' => '专项',      // 内容为监控掉线/离线，IoT 基础设施归专项
    ];

    /** 按工单内容关键词拆分：分类名 => [关键词 => 目标（父级名.子类名 或 父级名.子类名）] */
    private const SPLIT_BY_KEYWORD = [
        '物联网' => [               // 仅当该子类挂在「网络」下时拆分
            '动环' => '专项.动环',
            '班牌' => '多媒体.电子班牌',
            '录播' => '多媒体.录播系统',
            // 其余真物联网单在调用方整树迁往 专项.物联网
        ],
    ];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $plan = [];

        // ===== 1. 顶级重复合并（如 网络故障 → 网络，子类按同名并入）=====
        foreach (self::TOP_MERGE as $oldName => $newName) {
            $old = $this->findTop($oldName);
            $keep = $this->findTop($newName);
            if (!$old || !$keep) {
                continue;
            }
            foreach ($this->children($old->id) as $child) {
                $target = $this->findChild($keep->id, $child->name)
                    ?? $this->ensureChild($keep->id, $child->name, $plan);
                $plan[] = "子类「{$oldName}.{$child->name}」工单 → 「{$newName}.{$child->name}」(#{$child->id}→#{$target->id})，删 #{$child->id}";
                $this->moveTickets($child->id, $target->id, $dry);
                $this->deleteCategory($child->id, $dry);
            }
            // 旧顶级直接挂的单（若有）挪到保留顶级
            $this->moveTickets($old->id, $keep->id, $dry);
            $plan[] = "删除顶级「{$oldName}」#{$old->id}";
            $this->deleteCategory($old->id, $dry);
        }

        // ===== 2. 顶级降级（软件支持 → 其他的子类）=====
        foreach (self::TOP_DEMOTE as $oldName => $newParentName) {
            $old = $this->findTop($oldName);
            $parent = $this->findTop($newParentName);
            if (!$old || !$parent) {
                continue;
            }
            foreach ($this->children($old->id) as $child) {
                $plan[] = "「{$oldName}.{$child->name}」改挂「{$newParentName}」";
                if (!$dry) {
                    DB::table('workorder_categories_simplified')->where('id', $child->id)->update(['parent_id' => $parent->id]);
                }
            }
            // 顶级直接挂的单挪到新父级（若顶级与新父同名冲突则进同名子类）
            $this->moveTickets($old->id, $parent->id, $dry);
            $plan[] = "删除顶级「{$oldName}」#{$old->id}";
            $this->deleteCategory($old->id, $dry);
        }

        // ===== 3. 同父级子类按名合并（咨询碎类 → 电话咨询 等）=====
        foreach (self::SUB_MERGE_BY_NAME as $parentName => $map) {
            $parent = $this->findTop($parentName);
            if (!$parent) {
                continue;
            }
            foreach ($map as $oldSub => $keepSub) {
                $from = $this->findChild($parent->id, $oldSub);
                if (!$from) {
                    continue;
                }
                $to = $this->findChild($parent->id, $keepSub) ?? $this->ensureChild($parent->id, $keepSub, $plan);
                $n = $this->moveTickets($from->id, $to->id, $dry);
                $plan[] = "「{$parentName}.{$oldSub}」{$n}单 → 「{$parentName}.{$keepSub}」，删 #{$from->id}";
                $this->deleteCategory($from->id, $dry);
            }
        }

        // ===== 4. 网络下的物联网按内容拆分 + 整树迁移 =====
        $net = $this->findTop('网络');
        $zx = $this->findTop('专项');
        if ($net && $zx) {
            $iot = $this->findChild($net->id, '物联网');
            if ($iot) {
                foreach (self::SPLIT_BY_KEYWORD['物联网'] as $kw => $target) {
                    [$parentName, $childName] = explode('.', $target);
                    $p = $this->findTop($parentName);
                    $t = $p ? ($this->findChild($p->id, $childName) ?? $this->ensureChild($p->id, $childName, $plan)) : null;
                    if ($t) {
                        $n = $this->moveTicketsByKeyword($iot->id, $t->id, $kw, $dry);
                        $plan[] = "「网络.物联网」含「{$kw}」{$n}单 → 「{$target}」";
                    }
                }
                // 其余真物联网单 → 专项.物联网
                $zxIot = $this->findChild($zx->id, '物联网') ?? $this->ensureChild($zx->id, '物联网', $plan);
                $n = $this->moveTickets($iot->id, $zxIot->id, $dry);
                $plan[] = "「网络.物联网」剩余 {$n}单 → 「专项.物联网」，删 #{$iot->id}";
                $this->deleteCategory($iot->id, $dry);
            }
        }

        // ===== 5. 整树改挂（监控故障 → 专项）=====
        foreach (self::REPARENT as $catName => $newParentName) {
            $cat = DB::table('workorder_categories_simplified')->where('name', $catName)->first();
            $parent = $this->findTop($newParentName);
            if ($cat && $parent && $cat->parent_id !== $parent->id) {
                $plan[] = "「{$catName}」改挂「{$newParentName}」";
                if (!$dry) {
                    DB::table('workorder_categories_simplified')->where('id', $cat->id)->update(['parent_id' => $parent->id]);
                }
            }
        }

        // ===== 6. 更名 =====
        if ($this->findTop('网络') && ($c = $this->findChild($this->findTop('网络')->id, '互联网')) !== null) {
            // 已在步骤 3 合并，此处无操作
        }
        $net2 = $this->findTop('网络');
        if ($net2 && ($u = $this->findChild($net2->id, '上网故障')) === null) {
            // 未执行过合并时（原始库），互联网需更名上网故障
            $old = $this->findChild($net2->id, '互联网');
            if ($old) {
                $plan[] = "「网络.互联网」更名「上网故障」";
                if (!$dry) {
                    DB::table('workorder_categories_simplified')->where('id', $old->id)->update(['name' => '上网故障']);
                }
            }
        }

        // ===== 7. 清理空类 =====
        foreach (['系统迁移' => '设备迁改'] as $emptyName => $targetName) {
            $e = DB::table('workorder_categories_simplified')->where('name', $emptyName)->first();
            if ($e) {
                $t = DB::table('workorder_categories_simplified')->where('name', $targetName)->first();
                if ($t) {
                    $this->moveTickets($e->id, $t->id, $dry);
                }
                $plan[] = "空类「{$emptyName}」删除";
                $this->deleteCategory($e->id, $dry);
            }
        }

        // ===== 输出 =====
        $this->info($dry ? '【DRY-RUN】执行计划：' : '已执行：');
        foreach ($plan as $p) {
            $this->line('  - ' . $p);
        }

        // 校验孤儿
        $orphan = DB::table('workorders')->whereNotNull('category_id')
            ->whereNotIn('category_id', DB::table('workorder_categories_simplified')->pluck('id'))->count();
        $this->info("孤儿工单引用：{$orphan}" . ($orphan > 0 ? ' ⚠' : ' ✔'));

        return self::SUCCESS;
    }

    // ===== 辅助 =====

    private function findTop(string $name): ?object
    {
        return DB::table('workorder_categories_simplified')->whereNull('parent_id')->where('name', $name)->first();
    }

    private function children(int $parentId): array
    {
        return DB::table('workorder_categories_simplified')->where('parent_id', $parentId)->get()->all();
    }

    private function findChild(int $parentId, string $name): ?object
    {
        return DB::table('workorder_categories_simplified')->where('parent_id', $parentId)->where('name', $name)->first();
    }

    /** 不存在则建（dry-run 时返回虚拟对象仅用于计划展示） */
    private function ensureChild(int $parentId, string $name, array &$plan): object
    {
        if ($c = $this->findChild($parentId, $name)) {
            return $c;
        }
        $plan[] = "新建子类「{$name}」";
        if (!$this->option('dry-run')) {
            $id = DB::table('workorder_categories_simplified')->insertGetId([
                'parent_id' => $parentId,
                'name' => $name,
                'sort_order' => 99,
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return (object) ['id' => $id, 'name' => $name];
        }
        return (object) ['id' => -1, 'name' => $name];
    }

    private function moveTickets(int $from, int $to, bool $dry): int
    {
        $n = DB::table('workorders')->where('category_id', $from)->count();
        if (!$dry && $n > 0) {
            DB::table('workorders')->where('category_id', $from)->update(['category_id' => $to]);
            DB::table('workorder_templates')->where('category_id', $from)->update(['category_id' => $to]);
        }
        return $n;
    }

    private function moveTicketsByKeyword(int $from, int $to, string $kw, bool $dry): int
    {
        $q = DB::table('workorders')->where('category_id', $from)->where('description', 'like', "%{$kw}%");
        $n = (clone $q)->count();
        if (!$dry && $n > 0) {
            $q->update(['category_id' => $to]);
        }
        return $n;
    }

    private function deleteCategory(int $id, bool $dry): void
    {
        if ($dry) {
            return;
        }
        // 安全校验：仍有工单引用则不删
        $cnt = DB::table('workorders')->where('category_id', $id)->count()
            + DB::table('workorder_templates')->where('category_id', $id)->count();
        if ($cnt > 0) {
            $this->warn("  分类 #{$id} 仍有 {$cnt} 条引用，跳过删除");
            return;
        }
        DB::table('workorder_categories_simplified')->where('id', $id)->delete();
    }
}
