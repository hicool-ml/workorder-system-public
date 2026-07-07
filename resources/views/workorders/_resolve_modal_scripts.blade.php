<!-- 引入共享的解决工单模态框脚本 -->
<script src="{{ asset('js/workorder-resolve.js') }}"></script>
<script>
// 解决工单模态框初始化和验证
$(document).ready(function() {
    // 解决工单模态框显示时初始化
    $('#resolveModal').on('show.bs.modal', function (e) {
        var button = $(e.relatedTarget);
        var workorderId = button.data('workorder-id');
        $('#resolveWorkorderId').val(workorderId);
        var action = $('#resolveForm').attr('action').replace(':workorder_id', workorderId);
        $('#resolveForm').attr('action', action);
        
        // 调用共享的初始化函数
        window.initResolveModal(workorderId);
    });
});
</script>