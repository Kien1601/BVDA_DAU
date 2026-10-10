{{--
    Đặt chế độ sáng/tối trước khi trang vẽ để không nháy màu.
    Ưu tiên lựa chọn đã lưu (localStorage 'theme'); chưa có thì dùng mặc định của khu vực (data-area-default trên <html>).
    data-theme viết sẵn trên <html> là giá trị dự phòng khi JavaScript tắt hoặc localStorage bị chặn.
--}}
<script>
    (function () {
        var root = document.documentElement;
        var theme = null;
        try { theme = localStorage.getItem('theme'); } catch (e) {}
        if (theme !== 'light' && theme !== 'dark') theme = root.dataset.areaDefault || root.dataset.theme;
        root.dataset.theme = theme;
    })();
</script>
