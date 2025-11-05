<h2 class="h4 mb-4">Bảng điều khiển</h2>
<!-- Dashboard summary cards -->
<div class="row g-4 mb-4">
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card bg-dark text-light shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <i class="bi bi-currency-dollar display-5 text-warning me-3"></i>
                <div>
                    <h6 class="card-title mb-1">Tổng doanh thu</h6>
                    <h4 class="mb-0"><?= number_format($totalRevenue, 0, ',', '.') ?>đ</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card bg-dark text-light shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <i class="bi bi-ticket-perforated display-5 text-warning me-3"></i>
                <div>
                    <h6 class="card-title mb-1">Đơn hàng</h6>
                    <h4 class="mb-0"><?= htmlspecialchars($totalBookings) ?></h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card bg-dark text-light shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <i class="bi bi-film display-5 text-warning me-3"></i>
                <div>
                    <h6 class="card-title mb-1">Vở diễn</h6>
                    <h4 class="mb-0"><?= htmlspecialchars($totalShows) ?></h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card bg-dark text-light shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <i class="bi bi-calendar-event display-5 text-warning me-3"></i>
                <div>
                    <h6 class="card-title mb-1">Suất diễn</h6>
                    <h4 class="mb-0"><?= htmlspecialchars($totalPerfs) ?></h4>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Additional metrics row -->
<!-- Removed secondary statistics row (customers, reviews, growth and time) -->
<!-- Revenue charts and tables -->
<div class="row mb-4">
    <div class="col-12 col-lg-6 mb-4">
        <div class="card bg-dark text-light shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title">Doanh thu theo tháng</h5>
                <canvas id="monthlyRevenueChart" height="200"></canvas>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-6 mb-4">
        <div class="card bg-dark text-light shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="card-title mb-0">Số lượng vé đã bán</h5>
                    <!-- Filter buttons: allow switching between day/week/month/year views -->
                    <div class="btn-group btn-group-sm" role="group" aria-label="Ticket Sales Filter">
                        <button type="button" class="btn btn-outline-warning active" data-filter="day">Ngày</button>
                        <button type="button" class="btn btn-outline-warning" data-filter="week">Tuần</button>
                        <button type="button" class="btn btn-outline-warning" data-filter="month">Tháng</button>
                        <button type="button" class="btn btn-outline-warning" data-filter="year">Năm</button>
                    </div>
                </div>
                <canvas id="ticketSalesChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>
<div class="row mb-4" style="display:none;">
    <div class="col-12">
        <div class="card bg-dark text-light shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Top vở diễn theo vé bán</h5>
                <div class="table-responsive">
                    <table class="table table-dark table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Vở diễn</th>
                                <th class="text-end">Số vé</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topShowTickets as $title => $count): ?>
                                <tr>
                                    <td><?= htmlspecialchars($title) ?></td>
                                    <td class="text-end"><?= htmlspecialchars($count) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


<p class="text-muted">Sử dụng menu bên trái để quản lý đơn hàng, loại & vở diễn, rạp & ghế, suất diễn, đánh giá và tài khoản.</p>


<!-- Include Chart.js for rendering charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Data from PHP for monthly revenue
    const monthlyRevenueData = <?php echo json_encode(array_values($monthlyRevenue)); ?>;
    const monthlyLabels = <?php echo json_encode(array_map(function($k){ return date('m/Y', strtotime($k.'-01')); }, array_keys($monthlyRevenue))); ?>;


    // Data from PHP for ticket sales by day/week/month/year.  Keys are sorted in the controller.
    const ticketSalesDayLabels   = <?php echo json_encode(array_keys($ticketSalesDay)); ?>;
    const ticketSalesDayValues   = <?php echo json_encode(array_values($ticketSalesDay)); ?>;
    const ticketSalesWeekLabels  = <?php echo json_encode(array_keys($ticketSalesWeek)); ?>;
    const ticketSalesWeekValues  = <?php echo json_encode(array_values($ticketSalesWeek)); ?>;
    const ticketSalesMonthLabels = <?php echo json_encode(array_keys($ticketSalesMonth)); ?>;
    const ticketSalesMonthValues = <?php echo json_encode(array_values($ticketSalesMonth)); ?>;
    const ticketSalesYearLabels  = <?php echo json_encode(array_keys($ticketSalesYear)); ?>;
    const ticketSalesYearValues  = <?php echo json_encode(array_values($ticketSalesYear)); ?>;


    // Monthly revenue chart (bar)
    const ctx1 = document.getElementById('monthlyRevenueChart').getContext('2d');
    new Chart(ctx1, {
        type: 'bar',
        data: {
            labels: monthlyLabels,
            datasets: [{
                label: 'Doanh thu (đ)',
                data: monthlyRevenueData,
                backgroundColor: 'rgba(255, 193, 7, 0.5)',
                borderColor: 'rgba(255, 193, 7, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });


    // Ticket sales bar chart with filter
    const ticketCtx = document.getElementById('ticketSalesChart').getContext('2d');
    // Organize datasets into an object keyed by the filter type
    const ticketDatasets = {
        day:   { labels: ticketSalesDayLabels,   data: ticketSalesDayValues   },
        week:  { labels: ticketSalesWeekLabels,  data: ticketSalesWeekValues  },
        month: { labels: ticketSalesMonthLabels, data: ticketSalesMonthValues },
        year:  { labels: ticketSalesYearLabels,  data: ticketSalesYearValues  }
    };
    // Initialise chart with day view by default
    let currentFilter = 'day';
    let ticketChart = new Chart(ticketCtx, {
        type: 'bar',
        data: {
            labels: ticketDatasets[currentFilter].labels,
            datasets: [{
                label: 'Số vé',
                data: ticketDatasets[currentFilter].data,
                backgroundColor: 'rgba(23, 162, 184, 0.5)',
                borderColor: 'rgba(23, 162, 184, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    // Listen for clicks on filter buttons to update the chart.  The active button
    // is highlighted by toggling the 'active' class.
    document.querySelectorAll('[data-filter]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const filter = this.getAttribute('data-filter');
            if (!ticketDatasets[filter] || filter === currentFilter) return;
            currentFilter = filter;
            // Update active state on buttons
            document.querySelectorAll('[data-filter]').forEach(function(b) {
                b.classList.remove('active');
            });
            this.classList.add('active');
            // Update chart labels and data
            ticketChart.data.labels = ticketDatasets[filter].labels;
            ticketChart.data.datasets[0].data = ticketDatasets[filter].data;
            ticketChart.update();
        });
    });
});
</script>



