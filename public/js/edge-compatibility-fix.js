// Microsoft Edge兼容性修复
(function() {
    'use strict';
    
    // 修复Mixed Content
    function fixMixedContent() {
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            if (form.action && form.action.startsWith('http://')) {
                form.action = form.action.replace('http://', 'https://');
                console.log('Fixed mixed content:', form.action);
            }
        });
    }
    
    // 监听动态内容
    function observeDynamicContent() {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(mutation => {
                mutation.addedNodes.forEach(node => {
                    if (node.nodeName === 'FORM' || node.querySelector('form')) {
                        const forms = node.nodeName === 'FORM' ? [node] : node.querySelectorAll('form');
                        forms.forEach(form => {
                            if (form.action && form.action.startsWith('http://')) {
                                form.action = form.action.replace('http://', 'https://');
                            }
                        });
                    }
                });
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
    // 检查存储访问
    function checkStorageAccess() {
        try {
            localStorage.setItem('test', 'test');
            localStorage.removeItem('test');
            sessionStorage.setItem('test', 'test');
            sessionStorage.removeItem('test');
            console.log('Storage access: OK');
        } catch (error) {
            console.warn('Storage access restricted:', error);
            showStorageWarning();
        }
    }
    
    // 显示存储警告
    function showStorageWarning() {
        const warning = document.createElement('div');
        warning.id = 'edge-storage-warning';
        warning.className = 'alert alert-warning alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x';
        warning.style.zIndex = '9999';
        warning.style.maxWidth = '500px';
        warning.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>存储访问受限</strong><br>
                    <small>Edge浏览器的跟踪防护可能影响网站功能。请在设置中调整跟踪防护级别。</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        document.body.insertBefore(warning, document.body.firstChild);
        
        // 5秒后自动隐藏
        setTimeout(() => {
            if (warning.parentNode) {
                warning.parentNode.removeChild(warning);
            }
        }, 5000);
    }
    
    // 初始化
    document.addEventListener('DOMContentLoaded', function() {
        fixMixedContent();
        observeDynamicContent();
        checkStorageAccess();
    });
})();