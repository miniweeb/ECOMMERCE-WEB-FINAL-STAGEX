<?php
namespace App\Controllers;


use App\Models\Show;

class ShowController extends BaseController
{
    public function index(): void
    {
        $showModel = new Show();
        $genreModel = new \App\Models\Genre();
        $seatCatModel = new \App\Models\SeatCategory();
        $shows  = $showModel->all();
        $genres = $genreModel->all();
        $seatCats = $seatCatModel->all();


        $today = date('Y-m-d');


        
        $reviewModel = new \App\Models\Review();

        foreach ($shows as &$show) {
            $performances = $showModel->performances($show['show_id']);
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
            $show['nearest_date'] = $nearestDate;
            $show['price_from']   = $lowestPrice;
            $show['price_to']     = $highestPrice;


        
            $avg = $reviewModel->getAverageRatingByShow($show['show_id']);
            $show['avg_rating'] = $avg;
        }
        unset($show);


        $keyword    = trim($_GET['keyword'] ?? '');
        $filterGenre = trim($_GET['genre'] ?? '');
        $startDate  = trim($_GET['start_date'] ?? '');
        $endDate    = trim($_GET['end_date'] ?? '');
        $priceMin   = trim($_GET['price_min'] ?? '');
        $priceMax   = trim($_GET['price_max'] ?? '');
        $filtered   = [];

        $normalize = function (string $str): string {
            
            $str = str_replace(['Đ', 'đ'], 'd', $str);
            
            $translit = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
            if ($translit === false) {
                $translit = $str;
            }
            
            $translit = mb_strtolower($translit, 'UTF-8');
            
            $translit = preg_replace('/[^a-z0-9 ]+/u', '', $translit);
            return $translit ?? '';
        };
        
        $normalizedKeyword = $normalize($keyword);
        

        $genreFilter = strtolower($filterGenre);
        if ($genreFilter === 'all') {
            $genreFilter = '';
        }


        $normalizedGenre = $normalize($genreFilter);


        foreach ($shows as $s) {
            
            if ($keyword !== '' && strpos($normalize($s['title']), $normalizedKeyword) === false) {
                continue;
            }
            
            if ($normalizedGenre !== '' && strpos($normalize($s['genres']), $normalizedGenre) === false) {
                continue;
            }
            
            if ($startDate !== '') {
                $hasAfterStart = false;
                $perfs = $showModel->performances($s['show_id']);
                foreach ($perfs as $p) {
                    if ($p['performance_date'] >= $startDate) {
                        $hasAfterStart = true;
                        break;
                    }
                }
                if (!$hasAfterStart) {
                    continue;
                }
            }
            if ($endDate !== '') {
                $hasBeforeEnd = false;
                $perfs = $showModel->performances($s['show_id']);
                foreach ($perfs as $p) {
                    if ($p['performance_date'] <= $endDate) {
                        $hasBeforeEnd = true;
                        break;
                    }
                }
                if (!$hasBeforeEnd) {
                    continue;
                }
            }
            
            if ($priceMin !== '' && $s['price_to'] !== null && $s['price_to'] < (float)$priceMin) {
                continue;
            }
            if ($priceMax !== '' && $s['price_from'] !== null && $s['price_from'] > (float)$priceMax) {
                continue;
            }
            $filtered[] = $s;
        }


        usort($filtered, function ($a, $b) {
            $ad = $a['nearest_date'];
            $bd = $b['nearest_date'];
            if ($ad === $bd) {
                return 0;
            }
            if ($ad === null) {
                return 1;
            }
            if ($bd === null) {
                return -1;
            }
            return strcmp($ad, $bd);
        });


        $this->render('show_list', [
            'shows'      => $filtered,
            'genres'     => $genres,
            'selectedGenre' => $filterGenre,
            'keyword'    => $keyword,
            'startDate'  => $startDate,
            'endDate'    => $endDate,
            'priceMin'   => $priceMin,
            'priceMax'   => $priceMax
        ]);
    }
    public function detail(int $id): void
    {
        $showModel = new Show();
        $show = $showModel->find($id);
        if (!$show) {
            $this->redirect('index.php');
            return;
        }
        $performances = $showModel->performances($id);
       
        $seatCatModel = new \App\Models\SeatCategory();
        $seatCats = $seatCatModel->all();
        foreach ($performances as &$perf) {
            $basePrice = (float)$perf['price'];
            $lowest = $basePrice;
            if ($seatCats) {
                foreach ($seatCats as $cat) {
                    $p = $basePrice + (float)$cat['base_price'];
                    if ($lowest === null || $p < $lowest) {
                        $lowest = $p;
                    }
                }
            }
            $perf['lowest_price'] = $lowest;
        }
        unset($perf);


    
        $reviewModel = new \App\Models\Review();
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rating'])) {
            $user = $_SESSION['user'] ?? null;
            $rating = (int)($_POST['rating'] ?? 0);
            $content = trim($_POST['content'] ?? '');
            if (!$user || !is_array($user) || ($user['user_type'] ?? '') !== 'customer') {
                $_SESSION['error'] = 'Bạn cần đăng nhập với tài khoản khách hàng để đánh giá.';
                $this->redirect('index.php?pg=show&id=' . $id);
                return;
            }
            if ($rating < 1 || $rating > 5) {
                $_SESSION['error'] = 'Vui lòng chọn số sao hợp lệ (1–5).';
                $this->redirect('index.php?pg=show&id=' . $id);
                return;
            }
            if ($content === '') {
                $_SESSION['error'] = 'Nội dung đánh giá không được để trống.';
                $this->redirect('index.php?pg=show&id=' . $id);
                return;
            }
            $ok = $reviewModel->create($id, (int)$user['user_id'], $rating, $content);
            if ($ok) {
                $_SESSION['success'] = 'Cảm ơn bạn đã để lại đánh giá!';
            } else {
                $_SESSION['error'] = 'Không thể lưu đánh giá. Vui lòng thử lại sau.';
            }
            $this->redirect('index.php?pg=show&id=' . $id);
            return;
        }
       
        $reviews = $reviewModel->getByShow($id);
       
        $avgRating = $reviewModel->getAverageRatingByShow($id);


        $this->render('show', [
            'show'         => $show,
            'performances' => $performances,
            'reviews'      => $reviews,
            'avgRating'    => $avgRating
        ]);
    }
}