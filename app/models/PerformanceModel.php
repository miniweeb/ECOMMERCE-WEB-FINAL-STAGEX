<?php
namespace App\Models;


use App\Models\Show;


/**
 * PerformanceModel provides methods for listing and creating performances.
 * A performance links a vở diễn to a theatre at a specific date and time
 * with a price. The model joins shows and theatres to provide
 * meaningful names in listing.
 */
class PerformanceModel extends Database
{
    /**
     * Retrieve all performances with show and theatre names. Results are
     * ordered by performance date descending.
     *
     * @return array
     */
    public function all(): array
    {
        // Retrieve all performances with show and theatre names using
        // a stored procedure.  Afterwards, compute missing end_time values
        // based on each show's duration.  Returns an empty array on error.
        $pdo = $this->getConnection();
        try {
            // Retrieve all performances joined with show and theatre names
            $stmt = $pdo->query('CALL proc_get_all_performances_detailed()');
            $rows = $stmt->fetchAll();
            $stmt->closeCursor();
        } catch (\Throwable $ex) {
            $rows = [];
        }
        if (!$rows) {
            return [];
        }
        foreach ($rows as &$row) {
            if (empty($row['end_time'])) {
                $start = $row['start_time'];
                $showModel = new Show();
                $show = $showModel->find((int)$row['show_id']);
                $duration = isset($show['duration_minutes']) ? (int)$show['duration_minutes'] : 0;
                if ($duration > 0 && $start) {
                    $startTimestamp = strtotime($start);
                    if ($startTimestamp !== false) {
                        $endTimestamp = $startTimestamp + ($duration * 60);
                        $row['end_time'] = date('H:i:s', $endTimestamp);
                    }
                }
            }
        }
        unset($row);
        return $rows ?: [];
    }


    /**
     * Find a single performance by ID, including joined show and theatre names.
     * Returns an associative array or null if not found.
     *
     * @param int $id
     * @return array|null
     */
    public function find(int $id): ?array
    {
        // Fetch a single performance via stored procedure.  Join show and
        // theatre names and compute the end_time if it is missing.  If
        // the performance is not found, return null.
        $pdo = $this->getConnection();
        try {
            // Retrieve a single performance with show and theatre names
            $stmt = $pdo->prepare('CALL proc_get_performance_detailed_by_id(:pid)');
            $stmt->execute(['pid' => $id]);
            $row = $stmt->fetch();
            $stmt->closeCursor();
        } catch (\Throwable $ex) {
            return null;
        }
        if ($row) {
            if (empty($row['end_time'])) {
                $startTimestamp = strtotime($row['start_time']);
                $showModel = new Show();
                $show = $showModel->find((int)$row['show_id']);
                $duration = isset($show['duration_minutes']) ? (int)$show['duration_minutes'] : 0;
                if ($duration > 0 && $startTimestamp !== false) {
                    $row['end_time'] = date('H:i:s', $startTimestamp + ($duration * 60));
                }
            }
            return $row;
        }
        return null;
    }


    /**
     * Update only the status of a performance.
     *
     * @param int    $id     Performance ID
     * @param string $status New status (scheduled, cancelled, completed)
     * @return bool
     */
    public function updateStatus(int $id, string $status): bool
    {
        // Only allow updates to the allowed statuses.  Use a stored
        // procedure to persist the new status.  Returns false when an
        // invalid status is provided or the procedure fails.
        $allowed = ['Đang mở bán', 'Đã hủy'];
        if (!in_array($status, $allowed, true)) {
            return false;
        }
        try {
            $stmt = $this->getConnection()->prepare('CALL proc_update_performance_status_single(:pid, :status)');
            $stmt->execute([
                'pid'    => $id,
                'status' => $status
            ]);
            $stmt->closeCursor();
            return true;
        } catch (\Throwable $ex) {
            return false;
        }
    }


    /**
     * Insert a new performance.
     *
     * @param int    $showId
     * @param int    $theaterId
     * @param string $date       Date in Y-m-d format
     * @param string $startTime  Start time (HH:MM)
     * @param string $endTime    End time (HH:MM)
     * @param float  $price
     * @return bool
     */
    public function create(int $showId, int $theaterId, string $date, string $startTime, string $endTime, float $price): bool
    {
        // Compute an end time when none is provided using the show's duration.
        if (!$endTime) {
            $showModel = new Show();
            $show      = $showModel->find($showId);
            $duration  = 0;
            if ($show && isset($show['duration_minutes'])) {
                $duration = (int)$show['duration_minutes'];
            }
            $startTimestamp = strtotime($startTime);
            if ($startTimestamp !== false && $duration > 0) {
                $endTimestamp = $startTimestamp + ($duration * 60);
                $endTime      = date('H:i:s', $endTimestamp);
            } else {
                $endTime = null;
            }
        }
        // Persist the new performance using a stored procedure.  The status is
        // automatically set to "Đang mở bán" in the procedure.  Returns true
        // on success and false on failure.
        try {
            $stmt = $this->getConnection()->prepare('CALL proc_create_performance(:sid, :tid, :pdate, :start, :end, :price)');
            $stmt->execute([
                'sid'   => $showId,
                'tid'   => $theaterId,
                'pdate' => $date,
                'start' => $startTime,
                'end'   => $endTime,
                'price' => $price
            ]);
            // Clear any result sets to free the connection.  The procedure does
            // not return a result, but some drivers require fetch() before
            // subsequent queries can be executed.
            $stmt->fetch();
            $stmt->closeCursor();
            return true;
        } catch (\Throwable $ex) {
            return false;
        }
    }


    /**
     * Delete a performance by its ID.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        // Delete a performance only when its status is "Đã kết thúc".  Use
        // stored procedures to retrieve and remove the performance.  If the
        // performance has not ended, return false.
        // Fetch current status via stored procedure
        $pdo = $this->getConnection();
        try {
            $stmtCheck = $pdo->prepare('CALL proc_get_performance_by_id(:pid)');
            $stmtCheck->execute(['pid' => $id]);
            $row = $stmtCheck->fetch();
            $stmtCheck->closeCursor();
            $current = $row['status'] ?? null;
        } catch (\Throwable $ex) {
            return false;
        }
        if ($current !== 'Đã kết thúc') {
            return false;
        }
        try {
            $stmtDel = $pdo->prepare('CALL proc_delete_performance_if_ended(:pid)');
            $stmtDel->execute(['pid' => $id]);
            $stmtDel->closeCursor();
            return true;
        } catch (\Throwable $ex) {
            return false;
        }
    }
}

