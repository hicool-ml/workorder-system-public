<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WorkorderController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\WorkorderTypeController;
use App\Http\Controllers\WorkorderCategoryController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\WorkorderTemplateController;
use App\Http\Controllers\SystemSettingController;
use App\Http\Controllers\WorkorderSourceController;

// 首页
Route::get('/', function () {
    return redirect()->route('workorders.index');
});

// 连接测试页面
Route::get('/test_connection', function () {
    return view('test_connection');
});

// 认证路由
Route::middleware('guest')->group(function () {
    Route::get('login', [App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'store']);
    
    // 注册路由
    Route::get('register', [App\Http\Controllers\Auth\RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [App\Http\Controllers\Auth\RegisteredUserController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('logout', [App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'destroy'])->name('logout.get');
});

// 需要登录的路由
Route::middleware(['auth'])->group(function () {
    
    // 批量操作 - 必须放在资源路由之前，避免路由冲突
    Route::post('workorders/batch/assign', [WorkorderController::class, 'batchAssign'])->name('workorders.batch.assign');
    Route::post('workorders/batch/start', [WorkorderController::class, 'batchStart'])->name('workorders.batch.start');
    Route::post('workorders/batch/resolve', [WorkorderController::class, 'batchResolve'])->name('workorders.batch.resolve');
    Route::post('workorders/batch/complete', [WorkorderController::class, 'batchComplete'])->name('workorders.batch.complete');
    Route::post('workorders/batch/close', [WorkorderController::class, 'batchClose'])->name('workorders.batch.close');
    
    // 工单管理
    Route::resource('workorders', WorkorderController::class)->names([
        'index' => 'workorders.index',
        'create' => 'workorders.create',
        'store' => 'workorders.store',
        'show' => 'workorders.show',
        'edit' => 'workorders.edit',
        'update' => 'workorders.update',
        'destroy' => 'workorders.destroy',
    ]);
    
    // 工单操作
    Route::post('workorders/{workorder}/assign', [WorkorderController::class, 'assign'])->name('workorders.assign');
    Route::post('workorders/{workorder}/claim', [WorkorderController::class, 'claim'])->name('workorders.claim');
    Route::post('workorders/{workorder}/start', [WorkorderController::class, 'start'])->name('workorders.start');
    Route::post('workorders/{workorder}/resolve', [WorkorderController::class, 'resolve'])->name('workorders.resolve');
    Route::post('workorders/{workorder}/complete', [WorkorderController::class, 'complete'])->name('workorders.complete');
    Route::post('workorders/{workorder}/close', [WorkorderController::class, 'close'])->name('workorders.close');
    Route::post('workorders/{workorder}/logs', [WorkorderController::class, 'addLog'])->name('workorders.logs.add');
    Route::post('workorders/{workorder}/materials', [WorkorderController::class, 'updateMaterials'])->name('workorders.materials.update');
    Route::post('workorders/{workorder}/invite-collaborator', [WorkorderController::class, 'inviteCollaborator'])->name('workorders.invite.collaborator');
    Route::post('workorder-collaborations/{collaboration}/accept', [WorkorderController::class, 'acceptCollaboration'])->name('workorders.collaborations.accept');
    Route::post('workorder-collaborations/{collaboration}/reject', [WorkorderController::class, 'rejectCollaboration'])->name('workorders.collaborations.reject');
    Route::post('workorders/{workorder}/visit', [WorkorderController::class, 'storeVisit'])->name('workorders.visit.store');
    Route::get('api/workorders/subcategories', [WorkorderController::class, 'getSubCategories'])->name('api.workorders.subcategories');
    Route::get('workorders/{workorder}/materials-usage', [WorkorderController::class, 'getMaterialsUsage'])->name('workorders.materials-usage');
    
    // 工单模板管理（管理员和工单管理员）
    Route::resource('workorder-templates', WorkorderTemplateController::class)->names([
        'index' => 'workorder-templates.index',
        'create' => 'workorder-templates.create',
        'store' => 'workorder-templates.store',
        'edit' => 'workorder-templates.edit',
        'update' => 'workorder-templates.update',
        'destroy' => 'workorder-templates.destroy',
    ])->middleware('role:admin,workorder_manager');
    
    Route::post('workorder-templates/{workorderTemplate}/createFromTemplate', [WorkorderTemplateController::class, 'createFromTemplate'])->name('workorder-templates.createFromTemplate')->middleware('role:admin,workorder_manager');
    Route::post('workorder-templates/{workorderTemplate}/toggleStatus', [WorkorderTemplateController::class, 'toggleStatus'])->name('workorder-templates.toggleStatus')->middleware('role:admin,workorder_manager');
    
    // 附件相关
    Route::get('attachments/{attachment}/download', [AttachmentController::class, 'download'])->name('attachments.download');
    Route::get('attachments/{attachment}/preview', [AttachmentController::class, 'preview'])->name('attachments.preview');
    Route::get('attachments/{attachment}/preview/v/{version}', [AttachmentController::class, 'previewWithVersion'])->name('attachments.preview.version');
    Route::get('attachments/{attachment}/info', [AttachmentController::class, 'info'])->name('attachments.info');
    Route::delete('attachments/{attachment}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');
   Route::post('workorders/{workorder}/attachments/upload', [WorkorderController::class, 'uploadAttachments'])->name('workorders.attachments.upload');
    
    // 部门管理（管理员和工单管理员）
    Route::resource('departments', DepartmentController::class)->names([
        'index' => 'departments.index',
        'create' => 'departments.create',
        'store' => 'departments.store',
        'show' => 'departments.show',
        'edit' => 'departments.edit',
        'update' => 'departments.update',
        'destroy' => 'departments.destroy',
    ])->middleware('role:admin,workorder_manager');
    
    Route::get('departments/tree', [DepartmentController::class, 'tree'])->name('departments.tree')->middleware('role:admin,workorder_manager');
    Route::get('departments/{department}/statistics', [DepartmentController::class, 'statistics'])->name('departments.statistics')->middleware('role:admin,workorder_manager');
    
    // 地址管理（需要登录即可访问）
    Route::middleware(['auth'])->group(function () {
        Route::middleware(['role:admin,workorder_manager'])->group(function () {
        // 校区管理
        Route::get('locations/campuses', [LocationController::class, 'campuses'])->name('locations.campuses');
        Route::get('locations/create-campus', [LocationController::class, 'createCampus'])->name('locations.create-campus');
        Route::post('locations/store-campus', [LocationController::class, 'storeCampus'])->name('locations.store-campus');
        Route::get('locations/{campus}/show-campus', [LocationController::class, 'showCampus'])->name('locations.show-campus');
        Route::get('locations/{campus}/edit-campus', [LocationController::class, 'editCampus'])->name('locations.edit-campus');
        Route::put('locations/{campus}/update-campus', [LocationController::class, 'updateCampus'])->name('locations.update-campus');
        Route::delete('locations/{campus}/destroy-campus', [LocationController::class, 'destroyCampus'])->name('locations.destroy-campus');
        Route::patch('locations/{campus}/toggle-campus-status', [LocationController::class, 'toggleCampusStatus'])->name('locations.toggle-campus-status');
        
        // 校区重定向
        Route::redirect('/campuses', '/locations/campuses', 301);
        Route::redirect('/campuses/create', '/locations/create-campus', 301);
        
        // 地址资源路由 - must be after campus routes to avoid {location} catching them
        Route::resource('locations', LocationController::class)->names([
            'index' => 'locations.index',
            'create' => 'locations.create',
            'store' => 'locations.store',
            'show' => 'locations.show',
            'edit' => 'locations.edit',
            'update' => 'locations.update',
            'destroy' => 'locations.destroy',
        ]);
        });
    });
    
    // 工单分类管理（管理员和工单管理员）- 使用简化的工单分类
    Route::middleware(['role:admin,workorder_manager'])->group(function () {
        Route::resource('workorder-categories', WorkorderCategoryController::class)->names([
            'index' => 'workorder-categories.index',
            'create' => 'workorder-categories.create',
            'store' => 'workorder-categories.store',
            'show' => 'workorder-categories.show',
            'edit' => 'workorder-categories.edit',
            'update' => 'workorder-categories.update',
            'destroy' => 'workorder-categories.destroy',
        ]);
        
        Route::get('workorder-categories/options', [WorkorderCategoryController::class, 'options'])->name('workorder-categories.options');
        Route::get('workorder-categories/cascade', [WorkorderCategoryController::class, 'cascade'])->name('workorder-categories.cascade');
        Route::post('workorder-categories/sort', [WorkorderCategoryController::class, 'updateSort'])->name('workorder-categories.sort');
        Route::get('workorder-categories/{workorderCategory}/statistics', [WorkorderCategoryController::class, 'statistics'])->name('workorder-categories.statistics');
        Route::get('workorder-categories/{workorderCategory}/delete-confirm', [WorkorderCategoryController::class, 'deleteConfirm'])->name('workorder-categories.delete-confirm');
        Route::patch('workorder-categories/{workorderCategory}/toggle-status', [WorkorderCategoryController::class, 'toggleStatus'])->name('workorder-categories.toggle-status');
    });
    
    // 工单类型路由重定向到工单分类（向后兼容）
    Route::middleware(['role:admin,workorder_manager'])->group(function () {
        Route::redirect('/workorder-types', '/workorder-categories', 301);
        Route::redirect('/workorder-types/create', '/workorder-categories/create', 301);
    });
    
    // 用户管理（仅管理员）
    Route::middleware(['role:admin'])->group(function () {
        Route::resource('users', UserController::class)->names([
            'index' => 'users.index',
            'create' => 'users.create',
            'store' => 'users.store',
            'show' => 'users.show',
            'edit' => 'users.edit',
            'update' => 'users.update',
            'destroy' => 'users.destroy',
        ]);
        
        Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::post('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::get('users/{user}/statistics', [UserController::class, 'statistics'])->name('users.statistics');
        Route::get('users/engineers', [UserController::class, 'engineers'])->name('users.engineers');
        Route::post('users/batch-operation', [UserController::class, 'batchOperation'])->name('users.batch-operation');
        
        // 用户管理（安全删除功能）
        Route::get('users-management', [UserManagementController::class, 'index'])->name('users.management');
        Route::get('users-management/{id}/delete', [UserManagementController::class, 'deleteConfirm'])->name('users.management.delete');
        Route::delete('users-management/{id}', [UserManagementController::class, 'destroy'])->name('users.management.destroy');
        Route::post('users-management/batch', [UserManagementController::class, 'batchAction'])->name('users.management.batch');
        Route::get('api/users/{id}/stats', [UserManagementController::class, 'getUserStats'])->name('api.users.stats');
        
        // 系统设置管理（仅管理员）
        Route::get('system-settings', [SystemSettingController::class, 'index'])->name('system-settings.index');
        Route::post('system-settings', [SystemSettingController::class, 'update'])->name('system-settings.update');
        Route::post('system-settings/toggle-registration', [SystemSettingController::class, 'toggleRegistration'])->name('system-settings.toggle-registration');
        Route::post('system-settings/initialize-defaults', [SystemSettingController::class, 'initializeDefaults'])->name('system-settings.initialize-defaults');
        Route::post('system-settings/update-version', [SystemSettingController::class, 'updateVersion'])->name('system-settings.update-version');
        Route::get('system-settings/version-history', [SystemSettingController::class, 'getVersionHistory'])->name('system-settings.version-history');
        Route::delete('system-settings/{systemSetting}', [SystemSettingController::class, 'destroy'])->name('system-settings.destroy');
        
        // 工单来源管理（仅管理员）
        Route::resource('workorder-sources', WorkorderSourceController::class)->names([
            'index' => 'workorder-sources.index',
            'create' => 'workorder-sources.create',
            'store' => 'workorder-sources.store',
            'edit' => 'workorder-sources.edit',
            'update' => 'workorder-sources.update',
            'destroy' => 'workorder-sources.destroy',
        ]);
        
        Route::patch('workorder-sources/{workorderSource}/toggle-status', [WorkorderSourceController::class, 'toggleStatus'])->name('workorder-sources.toggle-status');
    });
    
    // 统计报表（需要登录）
    Route::middleware(['auth', 'role:admin,workorder_manager'])->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::match(['get', 'post'], '/reports/export', [ReportController::class, 'export'])->name('reports.export');
    });
    
    // 通知管理
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    
    // 批量操作路由必须放在具体资源路由之前
    Route::post('notifications/batch-read', [NotificationController::class, 'batchMarkAsRead'])->name('notifications.batch-read');
    Route::delete('notifications/batch', [NotificationController::class, 'batchDestroy'])->name('notifications.batch-destroy');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::post('notifications/create-announcement', [NotificationController::class, 'createAnnouncement'])->name('notifications.create-announcement');
    
    // 单个通知操作
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::delete('notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    
    // API路由
    Route::get('notifications/unread-count', [NotificationController::class, 'getUnreadCount'])->name('notifications.unread-count');
    Route::get('notifications/latest', [NotificationController::class, 'getLatest'])->name('notifications.latest');
    
    // 仪表板
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
    
    // 个人资料
    Route::get('/profile', function () {
        return view('profile');
    })->name('profile');
    
    Route::put('/profile', function (Illuminate\Http\Request $request) {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:users,email,' . auth()->id(),
            'phone' => 'nullable|string|max:20',
            'employee_id' => 'nullable|string|max:50',
            'department_id' => 'nullable|exists:departments,id',
            'location' => 'nullable|string|max:255',
            'remarks' => 'nullable|string|max:500',
        ]);
        
        auth()->user()->update($request->only([
            'name', 'email', 'phone', 'employee_id',
            'department_id', 'location', 'remarks'
        ]));
        
        return back()->with('success', '个人信息更新成功');
    })->name('profile.update');
    
    Route::put('/profile/password', function (Illuminate\Http\Request $request) {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);
        
        if (!\Illuminate\Support\Facades\Hash::check($request->current_password, auth()->user()->password)) {
            return back()->withErrors(['current_password' => '当前密码不正确']);
        }
        
        auth()->user()->update([
            'password' => \Illuminate\Support\Facades\Hash::make($request->password)
        ]);
        
        return back()->with('success', '密码修改成功');
    })->name('profile.password');
});

include base_path('routes/web_signature_routes.php');
