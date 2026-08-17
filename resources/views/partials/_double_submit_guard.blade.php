{{--
    防重复提交通用脚本
    给需要保护的 <form> 添加 data-prevent-double-submit 属性即可。
    提交时立即禁用提交按钮并显示「提交中…」，同时用闭包标志拦截重复 submit 事件，
    避免外网访问慢（如 Cloudflare 隧道）导致用户多次点击、重复创建工单。
--}}
<script>
(function () {
    function bind(form) {
        if (!form || form.dataset.doubleSubmitBound) return;
        form.dataset.doubleSubmitBound = '1';

        var submitted = false;

        form.addEventListener('submit', function (e) {
            if (submitted) {
                // 已有提交在进行中，拦截重复提交
                e.preventDefault();
                e.stopPropagation();
                return false;
            }

            // 兜底：浏览器原生校验未通过时不进入提交态（正常情况 submit 事件前已校验）
            if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
                e.preventDefault();
                if (typeof form.reportValidity === 'function') form.reportValidity();
                return false;
            }

            submitted = true;

            var btn = form.querySelector('button[type="submit"]');
            if (btn && !btn.disabled) {
                btn.disabled = true;
                btn.setAttribute('aria-busy', 'true');
                btn.innerHTML = '提交中…';
                btn.classList.add('opacity-60');
                btn.style.cursor = 'not-allowed';
            }
        });
    }

    function bindAll() {
        document.querySelectorAll('form[data-prevent-double-submit]').forEach(bind);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindAll);
    } else {
        bindAll();
    }
})();
</script>
