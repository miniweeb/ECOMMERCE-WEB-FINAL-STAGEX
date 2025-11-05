// Tập lệnh này thêm hành vi tương tác vào việc chọn chỗ ngồi và đếm ngược thanh toán.


document.addEventListener('DOMContentLoaded', () => {
    // Logic chọn ghế
    const seatContainer = document.getElementById('seat-container');
    if (seatContainer) {
        const selectedInput = document.getElementById('selected-seats-input');
        const totalSpan = document.getElementById('selected-total');
        const priceMap = JSON.parse(seatContainer.getAttribute('data-price-map'));
        const selected = new Set();
        seatContainer.addEventListener('click', (e) => {
            const target = e.target;
            // Bỏ qua các nhấp chuột vào các thành phần không phải chỗ ngồi hoặc chỗ ngồi đã được đặt
            if (!target.classList.contains('seat') || target.classList.contains('booked')) return;
            const seatId = target.getAttribute('data-seat-id');
            // Không cho phép lựa chọn khoảng trống (không có seatId)
            if (!seatId) return;
            const price = priceMap[seatId];
            if (selected.has(seatId)) {
                selected.delete(seatId);
                target.classList.remove('selected');
            } else {
                selected.add(seatId);
                target.classList.add('selected');
            }
            updateSelected();
        });
        function updateSelected() {
            // Xây dựng mảng seat_id|price
            const arr = Array.from(selected).map(seatId => `${seatId}|${priceMap[seatId]}`);
            selectedInput.value = JSON.stringify(arr);
            // Cập nhật total
            let total = 0;
            selected.forEach(sid => { total += parseFloat(priceMap[sid]); });
            totalSpan.textContent = total.toLocaleString('vi-VN', { style: 'currency', currency: 'VND' });
        }
    }
    /**
     * Đếm ngược thời gian thanh toán.  Đọc thuộc tính data-remaining trên
     * phần tử #countdown nếu có, ngược lại mặc định 900 giây.
     */
    const countdownEl = document.getElementById('countdown');
    if (countdownEl) {
        let remaining = 900;
        const dataRemaining = countdownEl.getAttribute('data-remaining');
        if (dataRemaining && !isNaN(parseInt(dataRemaining, 10))) {
            remaining = parseInt(dataRemaining, 10);
        }
        function updateDisplay() {
            const minutes = String(Math.floor(remaining / 60)).padStart(2, '0');
            const seconds = String(remaining % 60).padStart(2, '0');
            countdownEl.textContent = `${minutes}:${seconds}`;
        }
        updateDisplay();
        if (remaining > 0) {
            const interval = setInterval(() => {
                remaining--;
                updateDisplay();
                if (remaining <= 0) {
                    clearInterval(interval);
                    window.location.href = `index.php?pg=pay&cancel=1`;
                }
            }, 1000);
        }
    }
});