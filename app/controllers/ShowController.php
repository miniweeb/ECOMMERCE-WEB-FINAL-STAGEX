<?php
namespace App\Controllers;


use App\Models\Show;


/**
 * ShowController handles displaying details for a specific show (vở diễn)
 * and listing performances for that show.
 */
class ShowController extends BaseController
{
    /**
     * List shows with optional filtering.  Displays all plays
     * together with a sidebar containing filters for genre, date
     * range, price range and a keyword search.  For each show the
     * next available performance date and a price range (lowest to
     * highest ticket price) are calculated on the fly.  Results
     * can be ordered by the nearest performance date to give users
     * a sense of upcoming shows.
     */
    public function index(): void
    {
        $showModel = new Show();
        $genreModel = new \App\Models\Genre();
        $seatCatModel = new \App\Models\SeatCategory();


        // Fetch all shows and supporting data
        $shows  = $showModel->all();
        $genres = $genreModel->all();
        $seatCats = $seatCatModel->all();


        $today = date('Y-m-d');


        // Instantiate Review model to compute average ratings for shows
        $reviewModel = new \App\Models\Review();


        // Enrich each show with its nearest performance date and price range
        foreach ($shows as &$show) {
            $performances = $showModel->performances($show['show_id']);
            $nearestDate = null;
            $lowestPrice = null;
            $highestPrice = null;
            if ($performances) {
                foreach ($performances as $p) {
                    $perfDate = $p['performance_date'];
                    $perfPrice = (float)$p['price'];
                    // Determine next upcoming performance date
                    if ($perfDate >= $today) {
                        if ($nearestDate === null || strcmp($perfDate, $nearestDate) < 0) {
                            $nearestDate = $perfDate;
                        }
                    }
                    // Compute price range across seat categories
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


            // Calculate average rating for this show.  If no reviews exist
            // the value will be null; this allows the view to handle the
            // display accordingly (e.g. show "Chưa có đánh giá").
            $avg = $reviewModel->getAverageRatingByShow($show['show_id']);
            $show['avg_rating'] = $avg;
        }
        unset($show);


        // Retrieve filter parameters from the query string.  Empty
        // strings are normalised to null for easier comparisons.
        $keyword    = trim($_GET['keyword'] ?? '');
        $filterGenre = trim($_GET['genre'] ?? '');
        $startDate  = trim($_GET['start_date'] ?? '');
        $endDate    = trim($_GET['end_date'] ?? '');
        $priceMin   = trim($_GET['price_min'] ?? '');
        $priceMax   = trim($_GET['price_max'] ?? '');
        $filtered   = [];


        // Normalise Vietnamese text for case‑ and accent‑insensitive searching.  This
        // closure converts accented characters to their unaccented equivalents and
        // lowercases the string.  Prior to transliteration we explicitly map
        // the special characters "đ" and "Đ" to the ASCII letter "d" because
        // some `iconv` implementations fail to transliterate these correctly.
        // After transliteration the function removes all non‑alphanumeric
        // characters so that only letters, numbers and spaces remain.  See:
        // https://vi.wikipedia.org/wiki/Chữ_quốc_ngữ for a full list of
        // Vietnamese accents.
        $normalize = function (string $str): string {
            // Explicitly replace the Vietnamese "đ" character with "d" before transliteration
            $str = str_replace(['Đ', 'đ'], 'd', $str);
            // Attempt to transliterate to ASCII; fall back to the original string on failure
            $translit = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
            if ($translit === false) {
                $translit = $str;
            }
            // Convert to lowercase for case‑insensitive matching
            $translit = mb_strtolower($translit, 'UTF-8');
            // Remove any characters that are not letters, numbers or spaces
            $translit = preg_replace('/[^a-z0-9 ]+/u', '', $translit);
            return $translit ?? '';
        };
        // Precompute the normalised keyword.  The genre filter will be
        // normalised after handling the special 'all' value below.
        $normalizedKeyword = $normalize($keyword);
        // Note: do not normalise $genreFilter here because the variable is
        // defined below.  The normalised genre will be recomputed after
        // $genreFilter is set based on the query string.


        // Normalise genre to lowercase for comparison.  A value of
        // 'all' means no filtering.
        $genreFilter = strtolower($filterGenre);
        if ($genreFilter === 'all') {
            $genreFilter = '';
        }


        // Recompute the normalised genre after applying the 'all' rule.  When
        // $genreFilter is empty the normalised value will also be empty which
        // effectively disables genre filtering.
        $normalizedGenre = $normalize($genreFilter);


        foreach ($shows as $s) {
            // Keyword filter: perform a case- and accent-insensitive search
            // against the show title.  Both the keyword and the title are
            // normalised using the helper defined above.  When no keyword is
            // provided the filter is skipped.
            if ($keyword !== '' && strpos($normalize($s['title']), $normalizedKeyword) === false) {
                continue;
            }
            // Genre filter: only include shows that contain the specified
            // genre name in their concatenated genres list.  Use the
            // normalised comparison for accent-insensitive matching.
            if ($normalizedGenre !== '' && strpos($normalize($s['genres']), $normalizedGenre) === false) {
                continue;
            }
            // Date range: ensure the show has a performance within the
            // specified start and end dates.  The start date filter
            // returns shows whose performances occur on or after
            // $startDate.  The end date filter returns shows whose
            // performances occur on or before $endDate.
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
            // Price range: compare the calculated price range of the show
            // against the user provided min and max.  Shows with no
            // price information are excluded from price filtering.
            if ($priceMin !== '' && $s['price_to'] !== null && $s['price_to'] < (float)$priceMin) {
                continue;
            }
            if ($priceMax !== '' && $s['price_from'] !== null && $s['price_from'] > (float)$priceMax) {
                continue;
            }
            $filtered[] = $s;
        }


        // Sort filtered shows by nearest upcoming performance date.  Shows
        // without a future performance will appear last.
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
        // Compute the lowest ticket price for each performance by combining
        // the base seat price with the performance price.  The lowest price
        // is used to display "Giá chỉ từ" in the performances table.
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


        // Handle review submission.  Only logged-in customers can submit reviews.
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
        // Retrieve existing reviews for the show
        $reviews = $reviewModel->getByShow($id);
        // Compute the average rating for this show.  This is shown above the title
        // on the detail page.  Returns null if no reviews exist.
        $avgRating = $reviewModel->getAverageRatingByShow($id);


        $this->render('show', [
            'show'         => $show,
            'performances' => $performances,
            'reviews'      => $reviews,
            'avgRating'    => $avgRating
        ]);
    }
<<<<<<< HEAD
}

=======
}
>>>>>>> cb2b1272143999ee52507153ec5d741714b4cb11
