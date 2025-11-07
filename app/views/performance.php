<h2 class="h4 mb-3">Chọn ghế</h2>
<p><strong>Vở diễn:</strong> <?= htmlspecialchars($show['title']) ?> | <strong>Ngày:</strong> <?= htmlspecialchars(date('d/m/Y', strtotime($performance['performance_date']))) ?> | <strong>Giờ:</strong> <?= htmlspecialchars(substr($performance['start_time'],0,5)) ?> | <strong>Phòng:</strong> <?= htmlspecialchars($performance['theater_name']) ?></p>

<div class="mb-3">
    <h5 class="h6">Loại ghế và phụ thu</h5>
    <ul class="list-inline">
        <?php foreach ($categories as $c): ?>
            <?php
            $catColor = $c['color_class'] ?? '';
            $style    = '';
            if (preg_match('/^[0-9a-fA-F]{6}$/', $catColor)) {
                $style = 'style="background-color:#' . htmlspecialchars($catColor) . ';"';
                $catClass = '';
            } else {
                $catClass = htmlspecialchars($catColor);
            }
            ?>
            <li class="list-inline-item me-3">
                <span class="seat <?= $catClass ?>" <?= $style ?>></span>
                <?= htmlspecialchars($c['category_name']) ?> (+<?= number_format($c['base_price'],0,',','.') ?> VND)
            </li>
        <?php endforeach; ?>
    </ul>
</div>

<form method="post">
    <?php
    $priceMap = [];
    $seatRowsMap = [];
    $maxSeatNum = 0;

    $catNames = [];
    foreach ($categories as $cItem) {
        $catNames[$cItem['category_id']] = $cItem['category_name'];
    }
    $seatInfo = [];
    foreach ($seats as $s) {
        $row = $s['row_char'];
        $num = (int)$s['seat_number'];
        if ($num > $maxSeatNum) $maxSeatNum = $num;
        if (!isset($seatRowsMap[$row])) {
            $seatRowsMap[$row] = [];
        }
        $seatRowsMap[$row][$num] = $s;
        if ($s['category_id'] !== null) {
            $priceMap[$s['seat_id']] = (float)$performance['price'] + (float)$s['base_price'];
            $label = $s['row_char'] . $s['real_seat_number'];
            $catName = $catNames[$s['category_id']] ?? '';
            $seatInfo[$s['seat_id']] = [
                'label'    => $label,
                'category' => $catName
            ];
        }
    }

    ksort($seatRowsMap);
    foreach ($seatRowsMap as &$seatList) {
        ksort($seatList);
    }
    unset($seatList);

    $seatGrid = [];
    foreach ($seatRowsMap as $rowChar => $seatsByNum) {
        $rowEntries = [];
        for ($n = 1; $n <= $maxSeatNum; $n++) {
            if (isset($seatsByNum[$n])) {
                $seat = $seatsByNum[$n];
                if ($seat['category_id'] !== null) {
                    $rowEntries[] = [
                        'id'     => $seat['seat_id'],
                        'num'    => $seat['real_seat_number'],
                        'class'  => $seat['color_class'],
                        'color'  => $seat['color_class'],
                        'booked' => isset($booked[$seat['seat_id']])
                    ];
                } else {
                    $rowEntries[] = [
                        'id'     => null,
                        'num'    => '',
                        'class'  => '',
                        'color'  => '',
                        'booked' => false
                    ];
                }
            } else {
                $rowEntries[] = [
                    'id'     => null,
                    'num'    => '',
                    'class'  => '',
                    'color'  => '',
                    'booked' => false
                ];
            }
        }
        $seatGrid[$rowChar] = $rowEntries;
    }
    ?>
    <div class="row">
        <div class="col-md-8 mb-3">
            <div id="seat-container" data-price-map='<?= json_encode($priceMap) ?>' data-seat-info='<?= json_encode($seatInfo) ?>'></div>
        </div>
        <div class="col-md-4">
            <h5 class="mb-3">Hóa đơn</h5>
            <ul class="list-group mb-3" id="selected-list"></ul>
            <p class="fs-5">Tổng cộng: <strong id="selected-total">0&nbsp;₫</strong></p>
            <button type="submit" class="btn btn-warning w-100">Xác nhận và thanh toán tại cổng VNPay</button>
        </div>
    </div>

    <input type="hidden" name="seats[]" id="selected-seats-input" value="[]">
    <script>
        document.addEventListener('DOMContentLoaded', function(){
            const container = document.getElementById('seat-container');
            const priceMapJson = container.getAttribute('data-price-map');
            const seatInfoJson = container.getAttribute('data-seat-info');
            const priceMap = priceMapJson ? JSON.parse(priceMapJson) : {};
            const seatInfo = seatInfoJson ? JSON.parse(seatInfoJson) : {};
            let html = '<div class="d-inline-block border p-3 bg-dark rounded-3">';
            const seatGrid = <?= json_encode($seatGrid) ?>;
            const maxSeat = <?= $maxSeatNum ?>;
            for (const row in seatGrid) {
                html += `<div class="d-flex align-items-center mb-1"><div class="me-2">${row}</div>`;
                seatGrid[row].forEach(s => {
                    if (s.id) {
                        const classes = ['seat'];
                        if (s.class && !/^[0-9a-fA-F]{6}$/.test(s.class)) {
                            classes.push(s.class);
                        }
                        if (s.booked) classes.push('booked');
                        let style = '';
                        if (s.color && /^[0-9a-fA-F]{6}$/.test(s.color)) {
                            style = `background-color:#${s.color};`;
                        }
                        html += `<div class="${classes.join(' ')}" data-seat-id="${s.id}" style="${style}">${s.num}</div>`;
                    } else {
                        html += '<div class="seat" style="background-color:transparent; border:none; cursor:default;"></div>';
                    }
                });
                html += '</div>';
            }
            html += '<div class="d-flex align-items-center mt-2">';
            html += '<div class="me-2" style="min-width:1.5rem;"></div>';
            for (let n = 1; n <= maxSeat; n++) {
                html += `<div class="text-muted" style="width:32px; font-size:0.75rem; text-align:center;">${n}</div>`;
            }
            html += '</div>';
            html += '</div>';
            container.innerHTML = html;

            const selectedInput = document.getElementById('selected-seats-input');
            const totalSpan = document.getElementById('selected-total');
            const selectedListEl = document.getElementById('selected-list');
            const selected = new Set();
            container.addEventListener('click', (e) => {
                const target = e.target;
                if (!target.classList.contains('seat') || target.classList.contains('booked')) return;
                const seatId = target.getAttribute('data-seat-id');
                if (!seatId) return;
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
                const arr = Array.from(selected).map(seatId => `${seatId}|${priceMap[seatId]}`);
                selectedInput.value = JSON.stringify(arr);
                let total = 0;
                selected.forEach(sid => { total += parseFloat(priceMap[sid]); });
                totalSpan.textContent = total.toLocaleString('vi-VN', { style: 'currency', currency: 'VND' });
                selectedListEl.innerHTML = '';
                selected.forEach(sid => {
                    const info = seatInfo[sid];
                    const price = parseFloat(priceMap[sid]);
                    const li = document.createElement('li');
                    li.className = 'list-group-item d-flex justify-content-between align-items-center';
                    li.textContent = `${info.label} (${info.category})`;
                    const span = document.createElement('span');
                    span.textContent = price.toLocaleString('vi-VN', { style: 'currency', currency: 'VND' });
                    li.appendChild(span);
                    selectedListEl.appendChild(li);
                });
            }
        });
    </script>
</form>