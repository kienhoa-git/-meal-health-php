document.addEventListener('DOMContentLoaded', function () {
    // Tự đóng thông báo
    const alert = document.querySelector('.alert');
    if (alert) {
        const close = alert.querySelector('.alert-close');
        if (close) close.addEventListener('click', () => alert.remove());
        setTimeout(() => { if (alert.parentNode) alert.remove(); }, 6000);
    }

    // Xác nhận trước khi xóa
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (event) {
            if (!confirm(el.dataset.confirm || 'Bạn có chắc muốn xóa?')) {
                event.preventDefault();
            }
        });
    });

    // Tự tính tổng tiền trong giỏ hàng
    document.querySelectorAll('input[data-price]').forEach(function (input) {
        input.addEventListener('input', function () {
            const row = input.closest('.cart-item');
            if (!row) return;
            const line = row.querySelector('.line-total');
            if (line) {
                const qty = Math.max(0, parseInt(input.value, 10) || 0);
                line.textContent = new Intl.NumberFormat('vi-VN').format(qty * parseFloat(input.dataset.price)) + ' đ';
            }
        });
    });
});
