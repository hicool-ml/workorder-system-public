<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

use App\Models\Concerns\HasWorkorderMetrics;
use App\Models\Location;

class Workorder extends Model
{
    use HasFactory, SoftDeletes, HasWorkorderMetrics;

    // ===== 领域常量（状态机 / 枚举文本映射）=====

    /** 工单状态机：status => 中文文本 */
    public const STATUS_TEXTS = [
        'pending' => '待处理',
        'assigned' => '已分配',
        'processing' => '处理中',
        'resolved' => '已解决',
        'completed' => '已完结',
        'closed' => '已关闭',
    ];

    /** 未完结状态集合 */
    public const OPEN_STATUSES = ['pending', 'assigned', 'processing'];

    /** 优先级文本 */
    public const PRIORITY_TEXTS = [
        'high' => '高',
        'medium' => '中',
        'low' => '低',
    ];

    /** 来源文本 */
    public const SOURCE_TEXTS = [
        'phone' => '电话',
        'web' => '网络',
        'email' => '邮件',
        'scene' => '现场',
        'other' => '其他',
    ];

    protected $fillable = [
        'ticket_no',
        'ticket_prefix',
        'title',
        'description',
        'failure_description',
        'type_id',
        'category_id',
        'creator_id',
        'assignee_id',
        'department_id',
        'department_name',
        'contact_name',
        'contact_phone',
        'contact_email',
        'location_detail',
        'location_id',
        'appointment_time_start',
        'appointment_time_end',
        'appointment_time',
        'source',
        'custom_source',
        'priority',
        'status',
        'assigned_at',
        'expected_complete_at',
        'solution',
        'remarks',
        'materials_usage',
        'need_visit',
        'is_emergency',
        'phone_assisted',
        'other_reason',
        'requires_signature',
        'visit_status',
    ];

    /**
     * 仅允许在生命周期方法（start/resolve/complete/close 等）内部写入的字段
     * 防止通过 mass assignment 伪造完结/签名/满意度等敏感数据。
     */
    private const PROTECTED_FIELDS = [
        'started_at',
        'resolved_at',
        'completed_at',
        'closed_at',
        'processing_duration',
        'is_user_signed',
        'user_signature',
        'user_satisfaction',
        'user_feedback',
        'user_signed_at',
        'sms_acceptance_sent_at',
        'sms_survey_sent_at',
        'sms_satisfaction',
        'sms_satisfaction_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'started_at' => 'datetime',
        'resolved_at' => 'datetime',
        'completed_at' => 'datetime',
        'closed_at' => 'datetime',
        'expected_complete_at' => 'datetime',
        'appointment_time_start' => 'datetime',
        'appointment_time_end' => 'datetime',
        'appointment_time' => 'string', // 保持兼容性，存储时间段描述
        'processing_duration' => 'integer',
        'need_visit' => 'boolean',
        'is_emergency' => 'boolean',
        'phone_assisted' => 'boolean',
        'requires_signature' => 'boolean',
        'is_user_signed' => 'boolean',
        'user_satisfaction' => 'integer',
        'user_signed_at' => 'datetime',
        'sms_acceptance_sent_at' => 'datetime',
        'sms_survey_sent_at' => 'datetime',
        'sms_satisfaction' => 'integer',
        'sms_satisfaction_at' => 'datetime',
    ];

    /**
     * 获取工单类型
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(WorkorderType::class, 'type_id');
    }

    /**
     * 获取工单分类
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(WorkorderCategorySimplified::class, 'category_id');
    }

    /**
     * 获取创建人
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * 获取处理人
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * 获取处理人名称（考虑电话协助情况）
     */
    public function getAssigneeNameAttribute(): string
    {
        // 如果有分配的处理人，返回处理人姓名
        if ($this->assignee) {
            return $this->assignee->name;
        }
        
        // 如果是电话协助完成的工单，返回创建人姓名
        if ($this->phone_assisted && $this->status === 'resolved' && $this->creator) {
            return $this->creator->name . '（电话协助）';
        }
        
        // 否则返回"未分配"
        return '未分配';
    }

    /**
     * 检查是否已签单
     */
    public function hasSignature(): bool
    {
        return (bool) ($this->is_user_signed && $this->user_signature);
    }
    /**
     * 获取部门
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /**
     * 地址树节点（自引用树结构，工单地址的唯一标准引用）。
     * 通过 location_id 关联到 locations 表。
     */
    public function treeLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    /**
     * 历史兼容别名：locationInfo 与 treeLocation 等价（均走 location_id）。
     * 旧代码通过 $workorder->locationInfo 引用，保留别名避免视图大面积改动。
     */
    public function locationInfo(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    /**
     * 工单所在「区域」节点（沿 location_id 父链向上查找日常层级第一级，旧称「校区」）。
     */
    public function getCampusNodeAttribute(): ?Location
    {
        if (! $this->location_id) {
            return null;
        }

        $regionLevelId = self::getRegionLevelIdCached();
        if (! $regionLevelId) {
            return null;
        }

        // 如果当前节点本身就是区域层级，直接返回
        $loc = $this->treeLocation;
        if (! $loc) {
            return null;
        }
        if ($loc->level_id === $regionLevelId) {
            return $loc;
        }

        // 沿父链向上查找
        foreach ($loc->getAncestors() as $ancestor) {
            if ($ancestor->level_id === $regionLevelId) {
                return $ancestor;
            }
        }

        return null;
    }

    /**
     * 日常层级第一级（区域）的 level_id（请求级静态缓存，避免列表页每行都查库）。
     */
    private static ?int $regionLevelIdCache = null;
    private static function getRegionLevelIdCached(): ?int
    {
        return self::$regionLevelIdCache ??= LocationLevel::dailyLevelAt(0)?->id;
    }

    /**
     * 可读「区域」名：沿 location_id 父链找日常层级第一级节点，找不到则空字符串。
     * 前缀根之上的层级（省/市/区）通过 address_full 体现，不再混入这里。
     */
    public function getCampusNameAttribute(): string
    {
        return $this->campus_node?->name ?? '';
    }

    /**
     * 可读楼栋名：直接返回 location_id 对应节点名。
     */
    public function getBuildingNameAttribute(): string
    {
        return $this->treeLocation?->name ?? '';
    }

    /**
     * 完整地址：从地址前缀根的下一层级开始，沿祖先链拼接到 location_id。
     * 例：location_id="1教"，前缀根="2025号"，则返回"总部园区 / 1教"。
     */
    public function getAddressFullAttribute(): string
    {
        $loc = $this->treeLocation;
        if (! $loc) {
            return trim(($this->location_detail ?? ''), ' /-');
        }

        $prefix = Location::getPrefixRoot();
        $chain = $loc->getAncestors();

        // 如果有前缀根，截断到前缀根之后
        if ($prefix) {
            $chain = $chain->filter(fn ($node) => $node->id !== $prefix->id);
        }

        $parts = $chain->pluck('name')->all();
        if ($this->location_detail) {
            $parts[] = $this->location_detail;
        }

        return implode(' / ', array_filter($parts, fn ($p) => $p !== '' && $p !== null));
    }

    /**
     * 获取处理记录
     */
    public function logs(): HasMany
    {
        return $this->hasMany(WorkorderLog::class)->orderBy('created_at', 'desc');
    }

    /**
     * 获取附件
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(WorkorderAttachment::class);
    }

    /**
     * 获取回访记录
     */
    public function visits(): HasMany
    {
        return $this->hasMany(WorkorderVisit::class);
    }

    /**
     * 获取协作记录
     */
    public function collaborations(): HasMany
    {
        return $this->hasMany(WorkorderCollaboration::class);
    }

    /**
     * 获取已接受的协作工程师
     */
   public function collaborators(): HasMany
   {
       return $this->hasMany(WorkorderCollaboration::class)
           ->where('status', 'accepted')
           ->with('collaborator');
   }

    /**
     * 工单正向流转顺序（从早到晚）
     * 回滚只能把状态退回到当前节点之前的某个节点。
     */
    public static function statusFlow(): array
    {
        return ['pending', 'assigned', 'processing', 'resolved', 'completed', 'closed'];
    }

    /**
     * 回滚目标节点的中文标签（管理员视角）
     */
    public static function rollbackTargetLabels(): array
    {
        return [
            'pending' => '待分配',
            'assigned' => '已分配',
            'processing' => '处理中',
            'resolved' => '已解决',
        ];
    }

    /**
     * 当前状态可回滚到的目标节点列表
     * 只能回退到流程上更早的节点；completed/closed 不作为回滚目标。
     * 返回 [status => label] 数组，为空表示当前不可回滚。
     */
    public function getRollbackOptions(): array
    {
        $flow = self::statusFlow();
        $currentIdx = array_search($this->status, $flow);
        if ($currentIdx === false || $currentIdx === 0) {
            return [];
        }

        $labels = self::rollbackTargetLabels();
        $options = [];
        // 回退目标范围：从第一个节点到当前节点的前一个节点，且必须是可回滚目标
        for ($i = 0; $i < $currentIdx; $i++) {
            $status = $flow[$i];
            if (isset($labels[$status])) {
                $options[$status] = $labels[$status];
            }
        }
        return $options;
    }

    /**
     * 检查目标节点是否为当前状态的合法回滚目标
     */
    public function canRollbackTo(string $target): bool
    {
        return array_key_exists($target, $this->getRollbackOptions());
    }

    /**
     * 回滚工单到指定流程节点
     *
     * 与"修改状态"不同：回滚会把该节点之后产生的派生数据一并清理
     * （处理人、处理时间、解决方案、协作邀请等），并写入一条带操作人
     * 与原因的审计日志。
     *
     * @param string $target 回滚目标状态
     * @param string|null $reason 回滚原因（审计用）
     * @param int|null $operatorId 操作人 ID
     * @return bool
     */
    public function rollback(string $target, ?string $reason = null, ?int $operatorId = null): bool
    {
        if (!$this->canRollbackTo($target)) {
            return false;
        }

        $operatorId = $operatorId ?? (auth()->check() ? auth()->id() : $this->creator_id);
        $oldStatus = $this->status;

        return DB::transaction(function () use ($target, $reason, $operatorId, $oldStatus) {
            // 基础回滚：清除目标节点之后的时间戳与解决方案
            $data = [
                'status' => $target,
                'resolved_at' => null,
                'completed_at' => null,
                'closed_at' => null,
                'solution' => null,
                'processing_duration' => null,
            ];

            switch ($target) {
                case 'pending':
                    // 回到待分配：清空处理人及所有处理痕迹
                    $data['assignee_id'] = null;
                    $data['assigned_at'] = null;
                    $data['started_at'] = null;
                    $data['expected_complete_at'] = null;
                    break;
                case 'assigned':
                    // 回到已分配：保留处理人，清除开始处理之后的痕迹
                    $data['started_at'] = null;
                    $data['expected_complete_at'] = null;
                    break;
                case 'processing':
                    // 回到处理中：保留处理人与开始时间
                    break;
                case 'resolved':
                    // 回到已解决：保留解决方案（在下方按需恢复）
                    break;
            }

            // 已解决作为目标时，应保留之前的解决方案与解决时间
            if ($target === 'resolved') {
                unset($data['solution']);
                // resolved_at 由数据库原值保留：这里不强制清除
                $data['resolved_at'] = $this->getOriginal('resolved_at');
            }

            $this->forceFill($data)->save();

            // 回退到已分配/待分配时清理协作邀请（这些邀请产生于分配后的处理阶段）
            $clearedCollaborations = 0;
            if (in_array($target, ['pending', 'assigned'], true)) {
                // 删除前记录被清理的协作，避免审计丢失
                $collabs = $this->collaborations()->get();
                $clearedCollaborations = $collabs->count();
                if ($clearedCollaborations > 0) {
                    $this->collaborations()->delete();
                }
            }

            // 写入审计日志：状态变更 + 清理说明 + 原因
            $logContent = '回滚工单状态';
            $parts = [];
            if ($reason) {
                $parts[] = '原因：' . $reason;
            }
            if ($clearedCollaborations > 0) {
                $parts[] = '清理协作邀请 ' . $clearedCollaborations . ' 条';
            }
            if ($target === 'pending') {
                $parts[] = '清空处理人';
            }
            if (!empty($parts)) {
                $logContent .= '（' . implode('；', $parts) . '）';
            }

            $this->logs()->create([
                'user_id' => $operatorId,
                'action' => 'rolled_back',
                'content' => $logContent,
                'old_value' => $oldStatus,
                'new_value' => $target,
                'is_system' => false,
            ]);

            return true;
        });
    }

   /**
     * 生成工单编号（委托给 generateTicketNoByPrefix）
     */
    public static function generateTicketNo($prefix = 'WO'): string
    {
        return static::generateTicketNoByPrefix($prefix);
    }

    /**
     * 根据工单类型生成工单编号
     */
    public static function generateTicketNoByType($typeId): string
    {
        $type = WorkorderType::find($typeId);
        $prefix = $type ? $type->ticket_prefix : 'WO';
        return self::generateTicketNo($prefix);
    }

   /**
    * 根据前缀生成工单编号
    * 格式：大类编码 + 日期 + 工单生成时间 + 序号（4位）
    * 例如：N2015111813550001
    *
    * 序号来自 ticket_sequences 表的原子 UPSERT 自增：
    * 消除"首单并发生成重复编号"（PG 空结果集不加行锁）与">99 单序号回绕"两类缺陷。
    */
    public static function generateTicketNoByPrefix($prefix = 'WO'): string
    {
        $date = now()->format('Ymd');
        $time = now()->format('His');

        $seq = static::nextTicketSequence($prefix, $date);

        return $prefix . $date . $time . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * 原子获取当日序号。
     * 优先使用 PG 原生 UPSERT（单条 SQL，绝对原子）；
     * 其他数据库退化为事务 + 行锁 + upsert 重试。
     */
    private static function nextTicketSequence(string $prefix, string $date): int
    {
        $driver = static::query()->getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            $row = DB::selectOne(
                'INSERT INTO ticket_sequences (prefix, date, seq, created_at, updated_at)
                 VALUES (?, ?, 1, NOW(), NOW())
                 ON CONFLICT (prefix, date) DO UPDATE SET seq = ticket_sequences.seq + 1, updated_at = NOW()
                 RETURNING seq',
                [$prefix, $date]
            );
            return (int) $row->seq;
        }

        return (int) DB::transaction(function () use ($prefix, $date) {
            $affected = DB::table('ticket_sequences')
                ->where('prefix', $prefix)
                ->where('date', $date)
                ->lockForUpdate()
                ->update([
                    'seq' => DB::raw('seq + 1'),
                    'updated_at' => now(),
                ]);

            if ($affected === 0) {
                try {
                    DB::table('ticket_sequences')->insert([
                        'prefix' => $prefix,
                        'date' => $date,
                        'seq' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    return 1;
                } catch (\Illuminate\Database\QueryException $e) {
                    // 并发插入撞唯一约束：重试一次行锁更新
                    DB::table('ticket_sequences')
                        ->where('prefix', $prefix)
                        ->where('date', $date)
                        ->lockForUpdate()
                        ->update([
                            'seq' => DB::raw('seq + 1'),
                            'updated_at' => now(),
                        ]);
                }
            }

            return (int) DB::table('ticket_sequences')
                ->where('prefix', $prefix)
                ->where('date', $date)
                ->value('seq');
        });
    }
    
    /**
     * 获取状态文本
     */
    public function getStatusTextAttribute(): string
    {
        return self::STATUS_TEXTS[$this->status] ?? $this->status;
    }

    /**
     * 获取优先级文本
     */
    public function getPriorityTextAttribute(): string
    {
        return self::PRIORITY_TEXTS[$this->priority] ?? $this->priority;
    }

    /**
     * 获取来源文本
     */
    public function getSourceTextAttribute(): string
    {
        if ($this->source === 'custom' && $this->custom_source) {
            return $this->custom_source;
        }

        return self::SOURCE_TEXTS[$this->source] ?? (string) $this->source;
    }

    /**
     * 获取预约时间段描述
     */
    public function getAppointmentTimeRangeAttribute(): string
    {
        if ($this->appointment_time_start && $this->appointment_time_end) {
            return $this->appointment_time_start->format('m月d日 H:i') . ' - ' .
                   $this->appointment_time_end->format('m月d日 H:i');
        }
        
        return $this->appointment_time ?: '无预约时间';
    }

    /**
     * 检查是否在预约时间段内
     */
    public function isInAppointmentTime(): bool
    {
        if (!$this->appointment_time_start || !$this->appointment_time_end) {
            return false;
        }
        
        $now = now();
        return $now->between($this->appointment_time_start, $this->appointment_time_end);
    }

    /**
     * 检查是否可以分配
     */
    public function canBeAssigned(): bool
    {
        return in_array($this->status, ['pending']);
    }

    /**
     * 检查是否可以开始处理
     */
    public function canBeStarted(): bool
    {
        return in_array($this->status, ['pending', 'assigned', 'processing']) &&
               $this->assignee_id &&
               !$this->started_at;
    }

    /**
     * 检查是否可以解决
     */
    public function canBeResolved(): bool
    {
        return in_array($this->status, ['processing']);
    }

    /**
     * 检查是否可以完结
     */
    public function canBeCompleted(): bool
    {
        return $this->status === 'resolved';
    }

    /**
     * 检查是否可以关闭
     */
    public function canBeClosed(): bool
    {
        return in_array($this->status, ['resolved']);
    }

    /**
     * 检查是否需要回访
     */
    public function needsVisit(): bool
    {
        return $this->need_visit && $this->status === 'resolved' && !$this->visits()->exists();
    }

    /**
     * 判断工单是否与给定用户「相关」（用于工程师列表高亮）。
     * 相关 = 该用户是创建人、负责人，或已接受邀请的协作者。
     */
    public function isRelatedToUser(?User $user = null): bool
    {
        $user = $user ?: auth()->user();
        if (!$user) {
            return false;
        }
        if ($this->creator_id === $user->id || $this->assignee_id === $user->id) {
            return true;
        }
        return $this->collaborations()
            ->where('collaborator_id', $user->id)
            ->where('status', 'accepted')
            ->exists();
    }

    /**
     * 添加处理记录
     */
    public function addLog(string $action, ?string $content = null, ?int $userId = null): WorkorderLog
    {
        // 如果没有提供用户ID，尝试从认证用户获取，否则使用创建人ID
        $userId = $userId ?? (auth()->check() ? auth()->id() : $this->creator_id);
        
        return $this->logs()->create([
            'user_id' => $userId,
            'action' => $action,
            'content' => $content,
        ]);
    }

    /**
     * 分配工单
     */
    public function assign(int $assigneeId, $note = null, ?int $userId = null): bool
    {
        if (!$this->canBeAssigned()) {
            return false;
        }
        
        // 检查权限
        $user = $userId ? User::find($userId) : auth()->user();
        if (!$user) {
            return false;
        }
        
        // 管理员和工单管理员可以分配给任何人
        if (!$user->canAssignWorkorders()) {
            // 工程师只能分配给自己（接单）
            if (!$user->canAssignWorkorderToSelf() || $assigneeId !== $user->id) {
                return false;
            }
        }
        
        // 使用事务确保数据一致性
        return DB::transaction(function() use ($assigneeId, $note, $userId, $user) {
            // 乐观锁：仅当工单仍为 pending 时才能分配，防止并发重复接单
            $affected = static::where('id', $this->id)
                ->where('status', 'pending')
                ->update([
                    'assignee_id' => $assigneeId,
                    'status' => 'assigned',
                    'assigned_at' => now(),
                ]);

            if ($affected === 0) {
                // 工单已被他人接单或状态已变更，放弃本次操作
                return false;
            }

            // 同步内存中的模型状态，保证后续日志/通知读到正确值
            $this->assignee_id = $assigneeId;
            $this->status = 'assigned';
            $this->assigned_at = now();
            
            $assigneeName = User::find($assigneeId)->name ?? '未知用户';
            $logContent = "分配给: {$assigneeName}";
            if ($note) {
                $logContent .= "（备注：{$note}）";
            }
            $this->addLog('assigned', $logContent, $userId);
            
            // 发送通知 - 只发送给新的处理人
            try {
                $this->sendNotification('assigned');
            } catch (\Exception $e) {
                // 通知发送失败不应该影响分配操作
                \Log::warning('工单分配通知发送失败', [
                    'workorder_id' => $this->id,
                    'assignee_id' => $assigneeId,
                    'error' => $e->getMessage()
                ]);
            }
            
            return true;
        });
    }

    /**
     * 开始处理（乐观锁：仅当状态仍为 assigned 时生效，防并发打穿状态机）
     */
    public function start(?int $userId = null): bool
    {
        if (!$this->canBeStarted()) {
            return false;
        }

        $affected = static::where('id', $this->id)
            ->where('status', 'assigned')
            ->update([
                'status' => 'processing',
                'started_at' => now(),
            ]);

        if ($affected === 0) {
            return false;
        }

        $this->status = 'processing';
        $this->started_at = now();

        $this->addLog('started', '开始处理', $userId);

        return true;
    }

    /**
     * 解决工单（乐观锁 + 同步落库 processing_duration，统计 SQL 直接读列）
     */
    public function resolve(string $solution, ?int $userId = null): bool
    {
        if (!$this->canBeResolved()) {
            return false;
        }

        $now = now();
        $affected = static::where('id', $this->id)
            ->where('status', 'processing')
            ->update([
                'status' => 'resolved',
                'solution' => $solution,
                'resolved_at' => $now,
                // processing_duration 是 integer 列，diffInMinutes 返回 float，
                // 直接写入浮点数会触发 PG「无效的 integer 输入语法」，这里取整
                'processing_duration' => (int) $this->created_at->diffInMinutes($now),
            ]);

        if ($affected === 0) {
            return false;
        }

        $this->status = 'resolved';
        $this->solution = $solution;
        $this->resolved_at = $now;

        $this->addLog('resolved', $solution, $userId);

        return true;
    }

    /**
     * 关闭工单（乐观锁）
     */
    public function close(?int $userId = null): bool
    {
        if (!$this->canBeClosed()) {
            return false;
        }

        $affected = static::where('id', $this->id)
            ->where('status', 'resolved')
            ->update([
                'status' => 'closed',
                'closed_at' => now(),
            ]);

        if ($affected === 0) {
            return false;
        }

        $this->status = 'closed';
        $this->closed_at = now();

        $this->addLog('closed', '工单已关闭', $userId);

        return true;
    }

    /**
     * 完结工单（乐观锁）
     */
    public function complete(?int $userId = null): bool
    {
        if (!$this->canBeCompleted()) {
            return false;
        }

        $affected = static::where('id', $this->id)
            ->where('status', 'resolved')
            ->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

        if ($affected === 0) {
            return false;
        }

        $this->status = 'completed';
        $this->completed_at = now();

        $this->addLog('completed', '工单已完结', $userId);

        return true;
    }
    /**
     * 发送工单通知
     *
     * 接收者完全由 NotificationDispatcher::resolveRecipients 按事件类型决定，
     * 并自动排除触发操作的用户本人；此方法仅转发事件，不再接收指定用户列表。
     *
     * NOTIFY_QUEUE=true 时走队列异步发送（需运行 queue:work，Docker 部署已内置），
     * 默认保持同步以便本地开发无需 worker。
     */
    public function sendNotification(string $type, array $data = []): void
    {
        try {
            if (config('notification.queue_enabled')) {
                \App\Jobs\SendWorkorderNotificationJob::dispatch($this, $type);
                return;
            }

            // 多通道调度：根据通知规则决定站内/短信是否发送
            try {
                app(\App\Services\Notification\NotificationDispatcher::class)
                    ->dispatch($this, $type);
            } catch (\Exception $dispatchEx) {
                \Log::warning('多通道通知调度异常', [
                    'workorder_id' => $this->id,
                    'type' => $type,
                    'error' => $dispatchEx->getMessage(),
                ]);
            }

            // In-app notifications are sent solely by the dispatcher above
            // (NotificationDispatcher::sendInApp); the per-event factory
            // calls that used to live here duplicated those notifications.
        } catch (\Exception $e) {
            \Log::warning('工单通知发送失败', [
                'workorder_id' => $this->id,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }
    /**
     * 发送处理记录通知
     */
    public function sendCommentNotification(string $content, User $user): void
    {
        \App\Models\Notification::createWorkorderComment($this, $content, $user, true);
    }
    
    /**
     * 发送回访完成通知
     */
    public function sendVisitCompletedNotification(WorkorderVisit $visit): void
    {
        \App\Models\Notification::createWorkorderVisitCompleted($this, true);
    }
    
    /**
     * 邀请协作工程师
     */
    public function inviteCollaborator(int $collaboratorId, ?string $reason = null, ?int $inviterId = null): bool
    {
        // 检查权限
        $inviter = $inviterId ? User::find($inviterId) : auth()->user();
        if (!$inviter || !$inviter->canInviteCollaborators()) {
            return false;
        }
        
        // 检查是否可以邀请：工单负责人、管理员、工单管理员，或已接受的协作工程师
        // 注意：需与 WorkorderPermissionService::canInviteCollaboration 的判断保持一致，
        // 否则前端按钮显示但提交被静默拒绝。
        if ($inviter->id !== $this->assignee_id
            && !$inviter->isAdmin()
            && !$inviter->isWorkorderManager()
        ) {
            // 检查是否是已接受的协作工程师
            $isCollaborator = $this->collaborations()
                ->where('collaborator_id', $inviter->id)
                ->where('status', 'accepted')
                ->exists();
                
            if (!$isCollaborator) {
                return false;
            }
        }
        
        // 创建协作邀请
        $collaboration = WorkorderCollaboration::createInvitation($this->id, $collaboratorId, $reason);
        
        if ($collaboration) {
            $collaboratorName = User::find($collaboratorId)->name ?? '未知用户';
            $inviterName = $inviter->name ?? '未知用户';
            $logContent = "邀请协作工程师: {$collaboratorName}";
            if ($reason) {
                $logContent .= "（原因：{$reason}）";
            }
            $this->addLog('collaboration_invited', $logContent, $inviterId);

            // 发送协作邀请通知给被邀请的工程师
            $collaborator = User::find($collaboratorId);
            if ($collaborator) {
                \App\Models\Notification::createWorkorderCollaborationInvited($this, $inviter, $collaborator, $reason);

                // 同步到企业微信：群机器人推送一条消息并 @ 被邀请人，
                // 否则被邀请人只有在登录系统后才能看到邀请。
                $this->notifyWeComCollaborationInvite($inviter, $collaborator, $reason);
            }
        }
        return $collaboration !== false;
    }

    /**
     * 协作邀请的企业微信群机器人推送。
     * @ 被邀请人：优先用 wecom_userid，其次用手机号；都没有则发普通消息不 @。
     */
    private function notifyWeComCollaborationInvite(User $inviter, User $collaborator, ?string $reason): void
    {
        try {
            $wecom = app(\App\Services\Notification\WeComWebhookService::class);
            if (!$wecom->isEnabled()) {
                return;
            }

            $systemName = \App\Models\SystemSetting::get('system_name', '工单系统');
            $address = trim(implode(' ', array_filter([
                $this->campus_name,
                $this->building_name,
                $this->location_detail && trim($this->location_detail) ? trim($this->location_detail) : '',
            ]))) ?: '未知地点';
            $description = mb_substr($this->description ?: $this->title ?: '未知故障', 0, 30);
            $timestamp = now()->format('Y-m-d H:i');
            $reasonLine = $reason ? "\n邀请原因：{$reason}" : '';

            $content = "【{$systemName}】协作邀请\n"
                . "时间：{$timestamp}\n"
                . "编号：{$this->ticket_no}\n"
                . "地点：{$address}\n"
                . "描述：{$description}\n"
                . "邀请人：{$inviter->name}\n"
                . "被邀请人：{$collaborator->name}{$reasonLine}";

            $baseUrl = rtrim(\App\Models\SystemSetting::get('system_url', config('app.url', '')), '/');
            if ($baseUrl) {
                $content .= "\n{$baseUrl}/workorders/{$this->id}";
            }

            // 收集 @ 信息：userid 优先，手机号兜底
            $mentionedUserIds = [];
            $mentionedMobiles = [];
            if (!empty($collaborator->wecom_userid)) {
                $mentionedUserIds[] = $collaborator->wecom_userid;
            } elseif (!empty($collaborator->phone)) {
                $mentionedMobiles[] = $collaborator->phone;
            }

            $wecom->sendText($content, $mentionedUserIds, $mentionedMobiles);
        } catch (\Exception $e) {
            \Log::warning('协作邀请企业微信通知失败', [
                'workorder_id' => $this->id,
                'collaborator_id' => $collaborator->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * 检查用户是否可以操作此工单
     */
    public function canBeOperatedBy(User $user, string $action = 'view'): bool
    {
        switch ($action) {
            case 'view':
                return $user->canViewWorkorder($this);
                
            case 'edit':
                return $user->canEditWorkorder($this);
                
            case 'assign':
                return $user->canAssignWorkorders() ||
                       ($user->canAssignWorkorderToSelf() && $this->assignee_id !== $user->id);
                
            case 'start':
            case 'resolve':
                // 工单负责人或已接受的协作工程师可以处理
                if ($user->isAdmin() || $user->isWorkorderManager()) {
                    return true;
                }
                
                if ($this->assignee_id === $user->id) {
                    return true;
                }
                
                return $this->collaborations()
                    ->where('collaborator_id', $user->id)
                    ->where('status', 'accepted')
                    ->exists();
                
            case 'upload_attachment':
                return $user->canUploadAttachment($this);
                
            case 'invite_collaborator':
                return $user->canInviteCollaborators() &&
                       ($this->assignee_id === $user->id ||
                        $this->collaborations()
                            ->where('collaborator_id', $user->id)
                            ->where('status', 'accepted')
                            ->exists());
                
            case 'add_note':
            case 'add_materials':
                return $user->canAddWorkorderNotes() &&
                       ($this->assignee_id === $user->id ||
                        $this->collaborations()
                            ->where('collaborator_id', $user->id)
                            ->where('status', 'accepted')
                            ->exists() ||
                        $user->isAdmin() || $user->isWorkorderManager());
                
            default:
                return false;
        }
    }
    
    /**
     * 获取所有可以操作此工单的用户
     */
    public function getOperableUsers(string $action = 'view'): \Illuminate\Database\Eloquent\Collection
    {
        $userIds = collect();

        // 管理员和工单管理员
        $adminUsers = User::whereIn('role', ['admin', 'workorder_manager'])
            ->where('status', 'active')
            ->pluck('id');
        $userIds = $userIds->merge($adminUsers);
        
        // 工单创建人
        if ($this->creator_id) {
            $userIds->push($this->creator_id);
        }
        
        // 工单负责人
        if ($this->assignee_id) {
            $userIds->push($this->assignee_id);
        }
        
        // 协作工程师
        $collaboratorIds = $this->collaborations()
            ->where('status', 'accepted')
            ->pluck('collaborator_id');
        $userIds = $userIds->merge($collaboratorIds);
        
        return User::whereIn('id', $userIds->unique())
            ->where('status', 'active')
            ->get();
    }
}
