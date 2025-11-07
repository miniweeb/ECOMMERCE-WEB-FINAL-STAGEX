<?php
namespace App\Models;


use App\Models\Show;

class PerformanceModel extends Database
{
    /**
     * 
     *
     * @return array
     */
    public function all(): array
    {

        $pdo = $this->getConnection();
        try {
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

     *
     * @param int $id
     * @return array|null
     */
    public function find(int $id): ?array
    {
        $pdo = $this->getConnection();
        try {
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
     *
     * @param int    $id     Performance ID
     * @param string $status New status (scheduled, cancelled, completed)
     * @return bool
     */
    public function updateStatus(int $id, string $status): bool
    {
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
            $stmt->fetch();
            $stmt->closeCursor();
            return true;
        } catch (\Throwable $ex) {
            return false;
        }
    }


    /**
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {

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

