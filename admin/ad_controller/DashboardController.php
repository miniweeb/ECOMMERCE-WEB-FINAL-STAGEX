<?php
/**
 * Controller for the StageX admin dashboard.
 *
 * The dashboard presents high level statistics about bookings, revenue,
 * shows, performances and customers.  It also provides aggregated
 * ticket sales data for charting.  This controller extends
 * AdBaseController to leverage the common authorisation and view
 * rendering logic.
 */
namespace App\Controllers;




use App\Models\Booking;




class DashboardController extends AdBaseController
{
    /**
     * Display the admin dashboard.
     *
     * This method calculates a number of metrics including total
     * bookings, total revenue, numbers of shows, performances and
     * customers.  It also aggregates monthly revenue and ticket sales
     * statistics (per day/week/month/year) for chart display.  Once
     * computed, the metrics are passed to the ad_dashboard view.
     */
    public function index(): void
    {
        // Authorise admin access
        if (!$this->ensureAdmin()) {
            return;
        }
        // Gather bookings and compute revenue statistics
        $bookingModel = new Booking();
        $bookings     = $bookingModel->ad_getAllBookings();
        $totalBookings = count($bookings);
        $totalRevenue  = 0;
        $monthlyRevenue = [];
        $showRevenue    = [];
        // Load performances to build a map from performance_id to show details
        $perfModel = new \App\Models\PerformanceModel();
        $perfs     = $perfModel->all();
        if (!is_array($perfs)) {
            $perfs = [];
        }
        $perfMap = [];
        foreach ($perfs as $p) {
            $pid = $p['performance_id'] ?? null;
            if ($pid !== null) {
                $perfMap[$pid] = [
                    'show_id'    => $p['show_id'] ?? null,
                    'show_title' => $p['show_title'] ?? ''
                ];
            }
        }
        // Aggregate revenue and booking statistics.  Payment status is now stored in the payments
        // table, so fetch each booking's latest payment to determine success.
        $paymentModel = new \App\Models\Payment();
        foreach ($bookings as $bk) {
            $payment = $paymentModel->findByBooking((int)$bk['booking_id']);
            // Only count revenue for bookings with successful payment (status = 'Thành công')
            if ($payment && ($payment['status'] ?? '') === 'Thành công') {
                $amount = (float)($bk['total_amount'] ?? 0);
                $totalRevenue += $amount;
                // Monthly revenue keyed by year-month
                if (!empty($bk['created_at'])) {
                    $monthKey = substr($bk['created_at'], 0, 7);
                    if (!isset($monthlyRevenue[$monthKey])) {
                        $monthlyRevenue[$monthKey] = 0;
                    }
                    $monthlyRevenue[$monthKey] += $amount;
                }
                // Revenue per show based on performance map
                $perfId = $bk['performance_id'] ?? null;
                if ($perfId !== null && isset($perfMap[$perfId])) {
                    $showTitle = $perfMap[$perfId]['show_title'] ?? 'Unknown';
                    if (!isset($showRevenue[$showTitle])) {
                        $showRevenue[$showTitle] = 0;
                    }
                    $showRevenue[$showTitle] += $amount;
                }
            }
        }
        ksort($monthlyRevenue);
        arsort($showRevenue);
        $topShowRevenue = array_slice($showRevenue, 0, 5, true);
        // Ticket counts by show are no longer displayed on the dashboard.  The
        // following variables are retained for backwards compatibility but
        // initialised to empty arrays.
        $showTicketsSold = [];
        $topShowTickets  = [];
        // Ticket sales per day/week/month/year
        $ticketSalesDay   = [];
        $ticketSalesWeek  = [];
        $ticketSalesMonth = [];
        $ticketSalesYear  = [];
        try {
            $ticketCountPdo = null;
            if (!isset($pdo) || !$pdo) {
                $userModel = new \App\Models\User();
                $ticketCountPdo = $userModel->getPdo();
            } else {
                $ticketCountPdo = $pdo;
            }
            // Use stored procedure to count tickets instead of inline SQL
            $ticketCountStmt = $ticketCountPdo->prepare('CALL proc_count_tickets_by_booking(:bid)');
            foreach ($bookings as $bk) {
                $payment = $paymentModel->findByBooking((int)$bk['booking_id']);
                if (!$payment || ($payment['status'] ?? '') !== 'Thành công') {
                    continue;
                }
                $createdAt = $bk['created_at'] ?? '';
                if (!$createdAt) {
                    continue;
                }
                $dateStr = substr($createdAt, 0, 10);
                $ticketCountStmt->execute(['bid' => $bk['booking_id']]);
                // Stored procedure returns a single-row result set with the count
                $ticketCount = 0;
                if ($row = $ticketCountStmt->fetch()) {
                    // The procedure selects COUNT(*) AS ticket_count
                    $ticketCount = (int)($row['ticket_count'] ?? 0);
                }
                // Close the cursor to free connection for next procedure call
                $ticketCountStmt->closeCursor();
                // Day
                if (!isset($ticketSalesDay[$dateStr])) {
                    $ticketSalesDay[$dateStr] = 0;
                }
                $ticketSalesDay[$dateStr] += $ticketCount;
                // Week (ISO year-week)
                $timestamp = strtotime($dateStr);
                $weekKey   = date('o-W', $timestamp);
                if (!isset($ticketSalesWeek[$weekKey])) {
                    $ticketSalesWeek[$weekKey] = 0;
                }
                $ticketSalesWeek[$weekKey] += $ticketCount;
                // Month
                $monthKey = substr($dateStr, 0, 7);
                if (!isset($ticketSalesMonth[$monthKey])) {
                    $ticketSalesMonth[$monthKey] = 0;
                }
                $ticketSalesMonth[$monthKey] += $ticketCount;
                // Year
                $yearKey = substr($dateStr, 0, 4);
                if (!isset($ticketSalesYear[$yearKey])) {
                    $ticketSalesYear[$yearKey] = 0;
                }
                $ticketSalesYear[$yearKey] += $ticketCount;
            }
            ksort($ticketSalesDay);
            ksort($ticketSalesWeek);
            ksort($ticketSalesMonth);
            ksort($ticketSalesYear);
        } catch (\Throwable $th) {
            $ticketSalesDay   = [];
            $ticketSalesWeek  = [];
            $ticketSalesMonth = [];
            $ticketSalesYear  = [];
        }
        // Count shows, performances and customers
        $showModel  = new \App\Models\Show();
        $shows      = $showModel->all();
        $totalShows = is_array($shows) ? count($shows) : 0;
        $totalPerfs = is_array($perfs) ? count($perfs) : 0;
        $userModel  = new \App\Models\User();
        $totalCustomers = $userModel->countCustomers();
        // Render the dashboard view
        $this->renderAdmin('ad_dashboard', [
            'totalRevenue'    => $totalRevenue,
            'totalBookings'   => $totalBookings,
            'totalShows'      => $totalShows,
            'totalPerfs'      => $totalPerfs,
            'totalCustomers'  => $totalCustomers,
            'monthlyRevenue'  => $monthlyRevenue,
            'topShowRevenue'  => $topShowRevenue,
            'topShowTickets'  => $topShowTickets,
            'ticketSalesDay'   => $ticketSalesDay,
            'ticketSalesWeek'  => $ticketSalesWeek,
            'ticketSalesMonth' => $ticketSalesMonth,
            'ticketSalesYear'  => $ticketSalesYear
        ]);
    }
}





