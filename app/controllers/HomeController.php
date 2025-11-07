<?php
namespace App\Controllers;


use App\Models\Show;

class HomeController extends BaseController
{
    public function index(): void
    {
    
        $showModel = new Show();
        $seatCatModel = new \App\Models\SeatCategory();
        $shows = $showModel->all();
        $seatCats = $seatCatModel->all();
        $today = date('Y-m-d');
        $upcoming = [];
        $selling  = [];
        $reviewModel = new \App\Models\Review();
        foreach ($shows as &$s) {
            $performances = $showModel->performances($s['show_id']);
            $nearestDate = null;
            $lowestPrice = null;
            $highestPrice = null;
            if ($performances) {
                foreach ($performances as $p) {
                    $perfDate = $p['performance_date'];
                    $perfPrice = (float)$p['price'];
                    if ($perfDate >= $today) {
                        if ($nearestDate === null || strcmp($perfDate, $nearestDate) < 0) {
                            $nearestDate = $perfDate;
                        }
                    }
                    foreach ($seatCats as $cat) {
                        $price = $perfPrice + (float)$cat['base_price'];
                        if ($lowestPrice === null || $price < $lowestPrice) {
                            $lowestPrice = $price;
                        }
                        if ($highestPrice === null || $price > $highestPrice) {
                            $highestPrice = $price;
                        }
                    }
                }
            }
            $s['nearest_date'] = $nearestDate;
            $s['price_from']   = $lowestPrice;
            $s['price_to']     = $highestPrice;
            $avg = $reviewModel->getAverageRatingByShow($s['show_id']);
            $s['avg_rating'] = $avg;
            if (($s['status'] ?? '') === 'Sắp chiếu') {
                $upcoming[] = $s;
            }
            $hasOpenPerf = false;
            if ($performances) {
                foreach ($performances as $p) {
                    if (($p['status'] ?? '') === 'Đang mở bán') {
                        $hasOpenPerf = true;
                        break;
                    }
                }
            }
            if ($hasOpenPerf) {
                $selling[] = $s;
            }
        }
        unset($s);
        if (!empty($upcoming)) {
            usort($upcoming, function ($a, $b) {
                return strcmp($b['created_at'], $a['created_at']);
            });
        }
        usort($selling, function ($a, $b) {
            return strcmp($b['created_at'], $a['created_at']);
        });
        $heroCandidates = [];
        foreach ($shows as $show) {
            $performances = $showModel->performances($show['show_id']);
            foreach ($performances as $p) {
                if (($p['status'] ?? '') === 'Đang mở bán') {
                    $heroCandidates[$show['show_id']] = $show;
                    break;
                }
            }
        }
        usort($heroCandidates, function ($a, $b) {
            return strcmp($b['created_at'], $a['created_at']);
        });
        $hero = array_slice($heroCandidates, 0, 3);
        $reviewModel = new \App\Models\Review();
        $latestReviews = $reviewModel->getLatest(15);
        $this->render('home', [
            'upcomingShows' => $upcoming,
            'sellingShows'  => $selling,
            'heroShows'     => $hero,
            'latestReviews' => $latestReviews
        ]);
    }
}

