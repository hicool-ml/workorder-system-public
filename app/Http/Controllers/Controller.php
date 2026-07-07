<?php

namespace App\Http\Controllers;

abstract class Controller
{
    //
    
    /**
     * 获取布局文件
     */
    protected function getLayout()
    {
        if (view()->shared('useEdgeLayout', false)) {
            return 'layouts.edge-compatible';
        }
        
        return 'layouts.app';
    }
}
