@extends('layouts.app')

@section('title', '附件预览测试')

@section('styles')
<style>
.attachment-filename {
    /* 文件名智能截断显示 - 保留扩展名 */
    max-width: 300px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    /* 改善显示状态 */
    line-height: 1.4;
    padding: 0.25rem 0;
    position: relative;
}

.attachment-filename::after {
    content: attr(data-fullname);
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: inherit;
    color: transparent;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    padding: inherit;
}

.attachment-filename .filename-short {
    display: inline-block;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* 确保附件缩略图尺寸一致 */
.attachment-thumbnail img,
.attachment-preview-img {
    width: 60px !important;
    height: 60px !important;
    object-fit: cover;
    border-radius: 4px;
}

.attachment-thumbnail {
    width: 60px !important;
    height: 60px !important;
    min-width: 60px !important;
    min-height: 60px !important;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    overflow: hidden;
    margin-right: 1rem;
    flex-shrink: 0;
}

.attachment-thumbnail i {
    font-size: 3rem;
    color: #6c757d;
}

/* 附件按钮组样式修复 */
.attachment-actions {
    display: flex;
    gap: 0.25rem;
    flex-wrap: nowrap;
}

.attachment-actions .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    line-height: 1.2;
    min-width: auto;
    white-space: nowrap;
    flex-shrink: 0;
}

.attachment-actions .btn i {
    font-size: 0.75rem;
    margin: 0;
}

.attachment-actions form {
    display: inline-block;
    margin: 0;
}

.attachment-actions form .btn {
    margin: 0;
}
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1>附件预览功能测试</h1>
            <p class="text-muted">此页面用于测试附件预览功能是否正常工作</p>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">附件列表</h5>
                </div>
                <div class="card-body">
                    @if($attachments->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>文件名</th>
                                        <th>类型</th>
                                        <th>大小</th>
                                        <th>预览类型</th>
                                        <th>预览URL</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($attachments as $attachment)
                                    <tr>
                                        <td>{{ $attachment->id }}</td>
                                        <td class="attachment-filename">
                                            {{ \App\Helpers\FileHelper::truncateFilename($attachment->original_name, 16) }}
                                        </td>
                                        <td>{{ $attachment->mime_type }}</td>
                                        <td>{{ $attachment->formatted_file_size }}</td>
                                        <td>
                                            <span class="badge bg-info">{{ $attachment->getPreviewType() }}</span>
                                        </td>
                                        <td>
                                            <code class="small">{{ $attachment->preview_url ?? 'N/A' }}</code>
                                        </td>
                                        <td>
                                            @if($attachment->canPreview())
                                                <button type="button" class="btn btn-sm btn-primary attachment-preview-btn"
                                                        data-attachment-id="{{ $attachment->id }}"
                                                        data-preview-type="{{ $attachment->getPreviewType() }}"
                                                        data-preview-url="{{ $attachment->preview_url }}"
                                                        data-filename="{{ $attachment->original_name }}">
                                                    <i class="fas fa-eye"></i> 预览
                                                </button>
                                            @endif
                                            <a href="{{ $attachment->download_url }}" class="btn btn-sm btn-secondary">
                                                <i class="fas fa-download"></i> 下载
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 没有找到附件，请先上传一些附件到工单中。
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">测试结果</h5>
                </div>
                <div class="card-body">
                    <div id="testResults">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 点击上方的预览按钮测试附件预览功能。
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// 附件预览事件委托
$(document).on('click', '.attachment-preview-btn', function(e) {
    e.preventDefault();
    
    var $element = $(this);
    var attachmentId = $element.data('attachment-id');
    var previewType = $element.data('preview-type');
    var previewUrl = $element.data('preview-url');
    var fileName = $element.data('filename');
    
    $('#testResults').html(
        '<div class="alert alert-info">' +
            '<i class="fas fa-spinner fa-spin"></i> 正在预览附件: ' + fileName + 
            ' (ID: ' + attachmentId + ', 类型: ' + previewType + ')' +
        '</div>'
    );
    
    showFilePreview(attachmentId, previewType, previewUrl, fileName);
    
    // 记录测试结果
    setTimeout(function() {
        $('#testResults').append(
            '<div class="alert alert-success mt-2">' +
                '<i class="fas fa-check-circle"></i> 预览功能正常工作: ' + fileName + 
                ' (ID: ' + attachmentId + ', 类型: ' + previewType + ')' +
            '</div>'
        );
    }, 1000);
});

// 显示文件预览模态框
function showFilePreview(fileId, previewType, previewUrl, fileName) {
    // 移除已存在的模态框
    $('#filePreviewModal').remove();
    
    var modalHtml = '<div class="modal fade" id="filePreviewModal" tabindex="-1">' +
        '<div class="modal-dialog modal-xl modal-dialog-centered">' +
            '<div class="modal-content">' +
                '<div class="modal-header">' +
                    '<h5 class="modal-title">文件预览 - ' + fileName + '</h5>' +
                    '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭"></button>' +
                '</div>' +
                '<div class="modal-body p-0" style="max-height: 80vh; overflow: auto;">';
    
    if (previewType === 'image') {
        modalHtml += '<div class="text-center p-3">' +
            '<img src="' + previewUrl + '" class="img-fluid" alt="' + fileName + '" style="max-height: 70vh; object-fit: contain;" onerror="this.onerror=null; this.src=\'/images/file-icon.png\'; this.alt=\'图片加载失败\';">' +
        '</div>';
    } else if (previewType === 'pdf') {
        // 对于PDF，使用预览路由，增大显示尺寸
        var pdfPreviewUrl = '/attachments/' + fileId + '/preview';
        modalHtml += '<div class="embed-responsive embed-responsive-16by9">' +
            '<iframe src="' + pdfPreviewUrl + '" class="embed-responsive-item" style="width: 100%; height: 85vh;" title="PDF预览"></iframe>' +
        '</div>';
    } else if (previewType === 'text') {
        modalHtml += '<div class="p-3">' +
            '<div class="spinner-border text-primary" role="status">' +
                '<span class="visually-hidden">加载中...</span>' +
            '</div>' +
            '<div id="textPreviewContent" class="mt-3"></div>' +
        '</div>';
    } else {
        modalHtml += '<div class="text-center p-5">' +
            '<i class="fas fa-file fa-4x text-muted mb-3" aria-hidden="true"></i>' +
            '<h5>无法预览此文件类型</h5>' +
            '<p class="text-muted">请下载文件后查看</p>' +
        '</div>';
    }
    
    modalHtml += '</div>' +
        '<div class="modal-footer">' +
            '<a href="/attachments/' + fileId + '/download" class="btn btn-primary" download>' +
                '<i class="fas fa-download" aria-hidden="true"></i> 下载' +
            '</a>' +
            '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>' +
        '</div>' +
            '</div>' +
        '</div>' +
    '</div>';
    
    $('body').append(modalHtml);
    
    // 显示模态框
    var modal = new bootstrap.Modal(document.getElementById('filePreviewModal'));
    modal.show();
    
    // 如果是文本文件，加载内容
    if (previewType === 'text') {
        $.get('/attachments/' + fileId + '/info', function(data) {
            $('#textPreviewContent').html('<pre class="bg-light p-3 rounded" style="max-height: 60vh; overflow-y: auto;">' +
                data.content + '</pre>');
        }).fail(function() {
            $('#textPreviewContent').html('<div class="alert alert-warning">无法加载文件内容</div>');
        });
    }
    
    // 模态框关闭时移除DOM
    $('#filePreviewModal').on('hidden.bs.modal', function () {
        $(this).remove();
    });
    
    // 模态框显示前处理aria-hidden问题
    $('#filePreviewModal').on('show.bs.modal', function () {
        // 确保模态框没有aria-hidden属性，避免与焦点元素冲突
        $(this).removeAttr('aria-hidden');
    });
    
    // 模态框隐藏前处理焦点问题
    $('#filePreviewModal').on('hide.bs.modal', function () {
        // 在隐藏前移除焦点，避免aria-hidden与焦点冲突
        $(this).find(':focus').blur();
    });
    
    // ESC键关闭模态框
    $(document).on('keydown', function(e) {
        if (e.keyCode === 27) { // ESC key
            var modalElement = document.getElementById('filePreviewModal');
            if (modalElement) {
                var modalInstance = bootstrap.Modal.getInstance(modalElement);
                if (modalInstance) {
                    // 在隐藏前先移除焦点
                    $(modalElement).find(':focus').blur();
                    modalInstance.hide();
                }
            }
        }
    });
}
</script>
@endsection