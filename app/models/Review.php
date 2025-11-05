<?php
namespace App\Models;


use PDO;


/**
 * Review model handles CRUD operations for user reviews on shows.
 * Each review is linked to a show and a user, and includes a rating
 * (1–5 stars) and textual content.  Administrators can view and delete
 * reviews from the dashboard, while customers may submit new reviews
 * on show detail pages.
 */
class Review extends Database
{
    /**
     * Get all reviews for a specific show, joined with the reviewing user's
     * display name.  Reviews are ordered by creation date descending.
     *
     * @param int $showId
     * @return array
     */
    public function getByShow(int $showId): array
    {
        $pdo = $this->getConnection();
        // Use the stored procedure only.  In the lighter version no fallback
        // direct query is executed; if the procedure fails or returns no
        // rows an empty array is returned.
        try {
            $stmt = $pdo->prepare('CALL proc_get_reviews_by_show(:sid)');
            $stmt->execute(['sid' => $showId]);
            $rows = $stmt->fetchAll();
            $stmt->closeCursor();
            return $rows ?: [];
        } catch (\Throwable $ex) {
            return [];
        }
    }


    /**
     * Create a new review record.  Sets the created_at timestamp to now.
     *
     * @param int    $showId
     * @param int    $userId
     * @param int    $rating  Rating between 1 and 5
     * @param string $content
     * @return bool
     */
    public function create(int $showId, int $userId, int $rating, string $content): bool
    {
        // Insert a new review using a stored procedure.  Returns true on success.
        $pdo = $this->getConnection();
        try {
            $stmt = $pdo->prepare('CALL proc_create_review(:sid, :uid, :rating, :content)');
            $stmt->execute([
                'sid'     => $showId,
                'uid'     => $userId,
                'rating'  => $rating,
                'content' => $content
            ]);
            // Fetch the returned review_id to clear the cursor.  Some drivers
            // will otherwise prevent subsequent queries with "commands out of sync".
            $stmt->fetch();
            $stmt->closeCursor();
            return true;
        } catch (\Throwable $ex) {
            return false;
        }
    }


    /**
     * Retrieve all reviews across all shows with user and show titles.
     * Used by the admin dashboard for management.
     *
     * @return array
     */
    public function getAll(): array
    {
        $pdo = $this->getConnection();
        // Use stored procedure only; return an empty array on failure.
        try {
            $stmt = $pdo->query('CALL proc_get_all_reviews()');
            $rows = $stmt->fetchAll();
            $stmt->closeCursor();
            return $rows ?: [];
        } catch (\Throwable $ex) {
            return [];
        }
    }


    /**
     * Retrieve the latest reviews across all shows.  Results are sorted by
     * creation date descending and limited to the provided count.  Joins
     * with users and shows to include the reviewer account name and show
     * title.  A stored procedure is used when available for efficiency.
     *
     * @param int $limit  Maximum number of reviews to return
     * @return array
     */
    public function getLatest(int $limit = 15): array
    {
        $pdo = $this->getConnection();
        // Call the stored procedure to get the latest reviews.  If it fails or
        // returns no rows, an empty array is returned.  Normalize the returned
        // column names where necessary.
        try {
            $stmt = $pdo->prepare('CALL proc_get_latest_reviews(:lim)');
            $stmt->execute(['lim' => $limit]);
            $rows = $stmt->fetchAll();
            $stmt->closeCursor();
            if ($rows) {
                foreach ($rows as &$r) {
                    if (isset($r['show_title']) && !isset($r['title'])) {
                        $r['title'] = $r['show_title'];
                    }
                }
                unset($r);
            }
            return $rows ?: [];
        } catch (\Throwable $ex) {
            return [];
        }
    }


    /**
     * Delete a review by its primary key.  Returns true on success.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        // Delete a review using a stored procedure.  Returns true on success.
        try {
            $stmt = $this->getConnection()->prepare('CALL proc_delete_review(:id)');
            $stmt->execute(['id' => $id]);
            $stmt->closeCursor();
            return true;
        } catch (\Throwable $ex) {
            return false;
        }
    }


    /**
     * Calculate the average rating for a given show.  If no reviews
     * exist for the show, null is returned.  A stored procedure
     * `proc_get_average_rating_by_show` may be used if available; otherwise
     * a simple AVG query is executed.  The returned value is rounded
     * to one decimal place for display.
     *
     * @param int $showId
     * @return float|null Average rating or null if no reviews
     */
    public function getAverageRatingByShow(int $showId): ?float
    {
        $pdo = $this->getConnection();
        // Use the stored procedure only; if unavailable or returns null, return null
        try {
            $stmt = $pdo->prepare('CALL proc_get_average_rating_by_show(:sid)');
            $stmt->execute(['sid' => $showId]);
            $row = $stmt->fetch();
            $stmt->closeCursor();
            if ($row && isset($row['avg_rating']) && $row['avg_rating'] !== null) {
                return round((float)$row['avg_rating'], 1);
            }
            return null;
        } catch (\Throwable $ex) {
            return null;
        }
    }
}

