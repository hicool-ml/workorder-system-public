<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TestAttachmentPreviewController extends Controller
{
    /**
     * 附件预览测试页面
     */
    public function index()
    {
        return view('test_attachment_preview');
    }
}
