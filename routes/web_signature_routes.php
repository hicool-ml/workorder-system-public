<?php

use App\Http\Controllers\WorkorderSignatureController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 工单签单相关路由
|--------------------------------------------------------------------------
|
| 这里定义了工单签单功能相关的路由
|
*/

Route::middleware(['auth'])->group(function () {
    // 签单页面
    Route::get('/workorders/{workorder}/signature', [WorkorderSignatureController::class, 'create'])
        ->name('workorders.signature.create');
    
    // 保存签名
    Route::post('/workorders/{workorder}/signature', [WorkorderSignatureController::class, 'store'])
        ->name('workorders.signature.store');
    
    // 签单统计（仅管理员）
    Route::get('/signature/statistics', [WorkorderSignatureController::class, 'getStatistics'])
        ->name('workorders.signature.statistics')
        ->middleware('role:admin');
    
    // HTML格式的处理单
    Route::get('/workorders/{workorder}/signature-html', [WorkorderSignatureController::class, 'generateHtml'])
        ->name('workorders.signature.html');
});