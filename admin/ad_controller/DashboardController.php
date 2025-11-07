<?php
namespace App\Controllers;




use App\Models\Booking;




class DashboardController extends AdBaseController
{
    
    public function index(): void
    {
        
        if (!$this->ensureAdmin()) {
            return;
        }
        $bookingModel = new Booking();
        $bookings     = $bookingModel->ad_getAllBookings();
        $totalBookings = count($bookings);
        $totalRevenue  = 0;
        $monthlyRevenue = [];
        $showRevenue    = [];
        
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
        
        $paymentModel = new \App\Models\Payment();
        foreach ($bookings as $bk) {
            $payment = $paymentModel->findByBooking((int)$bk['booking_id']);
            
            if ($payment && ($payment['status'] ?? '') === 'Thành công') {
                $amount = (float)($bk['total_amount'] ?? 0);
                $totalRevenue += $amount;
                
                if (!empty($bk['created_at'])) {
                    $monthKey = substr($bk['created_at'], 0, 7);
                    if (!isset($monthlyRevenue[$monthKey])) {
                        $monthlyRevenue[$monthKey] = 0;
                    }
                    $monthlyRevenue[$monthKey] += $amount;
                }
                
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
        
        $showTicketsSold = [];
        $topShowTickets  = [];
       
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
                
                $ticketCount = 0;
                if ($row = $ticketCountStmt->fetch()) {
                    
                    $ticketCount = (int)($row['ticket_count'] ?? 0);
                }
                
                $ticketCountStmt->closeCursor();
               
                if (!isset($ticketSalesDay[$dateStr])) {
                    $ticketSalesDay[$dateStr] = 0;
                }
                $ticketSalesDay[$dateStr] += $ticketCount;
                
                $timestamp = strtotime($dateStr);
                $weekKey   = date('o-W', $timestamp);
                if (!isset($ticketSalesWeek[$weekKey])) {
                    $ticketSalesWeek[$weekKey] = 0;
                }
                $ticketSalesWeek[$weekKey] += $ticketCount;
            
                $monthKey = substr($dateStr, 0, 7);
                if (!isset($ticketSalesMonth[$monthKey])) {
                    $ticketSalesMonth[$monthKey] = 0;
                }
                $ticketSalesMonth[$monthKey] += $ticketCount;
              
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
     
        $showModel  = new \App\Models\Show();
        $shows      = $showModel->all();
        $totalShows = is_array($shows) ? count($shows) : 0;
        $totalPerfs = is_array($perfs) ? count($perfs) : 0;
        $userModel  = new \App\Models\User();
        $totalCustomers = $userModel->countCustomers();
  
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





