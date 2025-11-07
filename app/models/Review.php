<?php
namespace App\Models;


use PDO;

<<<<<<< HEAD
class Review extends Database
{
    /**
=======

/**
 */
class Review extends Database
{
    /**
     *
>>>>>>> 5f9c2c1998f1c4e6923fdbc246d31acd730dfc05
     * @param int $showId
     * @return array
     */
    public function getByShow(int $showId): array
    {
        $pdo = $this->getConnection();
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
<<<<<<< HEAD
=======
     *
>>>>>>> 5f9c2c1998f1c4e6923fdbc246d31acd730dfc05
     * @param int    $showId
     * @param int    $userId
     * @param int    $rating 
     * @param string $content
     * @return bool
     */
    public function create(int $showId, int $userId, int $rating, string $content): bool
    {
<<<<<<< HEAD
    
=======
>>>>>>> 5f9c2c1998f1c4e6923fdbc246d31acd730dfc05
        $pdo = $this->getConnection();
        try {
            $stmt = $pdo->prepare('CALL proc_create_review(:sid, :uid, :rating, :content)');
            $stmt->execute([
                'sid'     => $showId,
                'uid'     => $userId,
                'rating'  => $rating,
                'content' => $content
            ]);
            $stmt->fetch();
            $stmt->closeCursor();
            return true;
        } catch (\Throwable $ex) {
            return false;
        }
    }


    /**
<<<<<<< HEAD
=======
     *
>>>>>>> 5f9c2c1998f1c4e6923fdbc246d31acd730dfc05
     * @return array
     */
    public function getAll(): array
    {
        $pdo = $this->getConnection();
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
<<<<<<< HEAD
     * @param int $limit  Maximum number of reviews to return
=======
     * @param int $limit  
>>>>>>> 5f9c2c1998f1c4e6923fdbc246d31acd730dfc05
     * @return array
     */
    public function getLatest(int $limit = 15): array
    {
        $pdo = $this->getConnection();
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
<<<<<<< HEAD
=======
     *
>>>>>>> 5f9c2c1998f1c4e6923fdbc246d31acd730dfc05
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
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
<<<<<<< HEAD
=======
     *
>>>>>>> 5f9c2c1998f1c4e6923fdbc246d31acd730dfc05
     * @param int $showId
     * @return float|null 
     */
    public function getAverageRatingByShow(int $showId): ?float
    {
        $pdo = $this->getConnection();
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

