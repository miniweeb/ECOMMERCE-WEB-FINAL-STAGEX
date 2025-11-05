<?php
namespace App\Models;

<<<<<<< HEAD
use PDO;

=======

use PDO;


>>>>>>> cb2b1272143999ee52507153ec5d741714b4cb11
/**
 * Seat model provides access to seats for a given theater.  The
 * `seats` table defines each seat with row character, number and
 * category.  Seat availability is determined by cross‑checking
 * booked tickets for a performance.
 */
class Seat extends Database
{
    /**
     * Get all seats for a theater ordered by row and seat number.
     *
     * @param int $theaterId
     * @return array
     */
    public function seatsForTheater(int $theaterId): array
    {
        $pdo = $this->getConnection();
        // Retrieve all seats and their category details for a theatre via
        // stored procedure.  No fallback query is executed.
        try {
            $stmt = $pdo->prepare('CALL proc_get_seats_for_theater(:tid)');
            $stmt->execute(['tid' => $theaterId]);
            $rows = $stmt->fetchAll();
            $stmt->closeCursor();
            return $rows ?: [];
        } catch (\Throwable $ex) {
            return [];
        }
    }

<<<<<<< HEAD
=======

>>>>>>> cb2b1272143999ee52507153ec5d741714b4cb11
    /**
     * Get an associative array of seat_id => booked status for a specific
     * performance.  Seats with status 'booked' in the `tickets` table are
     * considered unavailable.
     *
     * @param int $performanceId
     * @return array
     */
    public function bookedForPerformance(int $performanceId): array
    {
        $pdo = $this->getConnection();
        $ids = [];
        try {
            // Call stored procedure to retrieve booked seat IDs for the performance
            $stmt = $pdo->prepare('CALL proc_get_booked_seat_ids(:pid)');
            $stmt->execute(['pid' => $performanceId]);
            while ($row = $stmt->fetch()) {
                if (isset($row['seat_id'])) {
                    $ids[(int)$row['seat_id']] = true;
                }
            }
            $stmt->closeCursor();
        } catch (\Throwable $ex) {
            // On failure simply return an empty array; no fallback inline SQL is executed
            return [];
        }
        return $ids;
    }

<<<<<<< HEAD
=======

>>>>>>> cb2b1272143999ee52507153ec5d741714b4cb11
    /**
     * Update a range of seats in a theatre to a specific category.  Only
     * seats matching the given row and between the start and end numbers
     * (inclusive) will be updated.  If categoryId is null or zero,
     * seats are reset to having no category.
     *
     * @param int    $theaterId  Theatre ID
     * @param string $rowChar    Row letter (e.g. 'A')
     * @param int    $startSeat  Starting seat number
     * @param int    $endSeat    Ending seat number
     * @param int    $categoryId Category ID or 0/null to clear
     * @return bool  True on success
     */
    public function updateCategoryRange(int $theaterId, string $rowChar, int $startSeat, int $endSeat, ?int $categoryId): bool
    {
        // Update a range of seats to a specific category using a stored
        // procedure.  The procedure handles normalisation of the seat
        // range and clearing the category when a null/zero value is
        // provided.  Returns true on success.
        $pdo = $this->getConnection();
        try {
            $stmt = $pdo->prepare('CALL proc_update_seat_category_range(:tid, :row, :start, :end, :cid)');
            $stmt->execute([
                'tid'   => $theaterId,
                'row'   => $rowChar,
                'start' => $startSeat,
                'end'   => $endSeat,
                'cid'   => $categoryId ?: 0
            ]);
            $stmt->closeCursor();
            return true;
        } catch (\Throwable $ex) {
            return false;
        }
    }
}