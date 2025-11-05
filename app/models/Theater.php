<?php
namespace App\Models;


/**
 * Theater model encapsulates CRUD operations for theatres. Each theatre
 * represents a physical space where performances occur and has a
 * capacity (number of seats). Admins can add new theatres, list
 * existing theatres and remove theatres that are no longer used.
 */
class Theater extends Database
{
    /**
     * Retrieve all theatres ordered by name.
     *
     * @return array
     */
    public function all(): array
    {
        // Retrieve all theatres via stored procedure only.  No fallback
        // query is executed in this lighter version.  Returns an empty
        // array on error.
        $pdo = $this->getConnection();
        try {
            $stmt = $pdo->query('CALL proc_get_all_theaters()');
            $rows = $stmt->fetchAll();
            $stmt->closeCursor();
            return $rows ?: [];
        } catch (\Throwable $ex) {
            return [];
        }
    }


    /**
     * Insert a new theatre.
     *
     * @param string $name     Name of the theatre
     * @param int    $capacity Total seats available
     * @return bool            True on success
     */
    /**
     * Create a new theatre and its seats.  The caller must provide
     * the number of rows and columns; seats will be generated with
     * row characters starting from 'A' and numbers starting from 1.
     * All seats are created without a category assignment (NULL).
     *
     * @param string $name The theatre name
     * @param int    $rows Number of seat rows
     * @param int    $cols Number of seats per row
     * @return bool  True on success
     */
    public function create(string $name, int $rows, int $cols): bool
    {
        // Create a new theatre by invoking a stored procedure.  The
        // procedure inserts the theatre and generates its seats based on
        // the provided row and column counts.  Returns true on success.
        $pdo = $this->getConnection();
        try {
            $stmt = $pdo->prepare('CALL proc_create_theater(:name, :rows, :cols)');
            $stmt->execute([
                'name' => $name,
                'rows' => $rows,
                'cols' => $cols
            ]);
            // Fetch the returned result set (theater_id) to clear the cursor.  Some
            // database drivers require fetching all result sets from
            // procedures before executing additional queries.  If no result is
            // returned, fetch() will simply return false and the subsequent
            // closeCursor will still succeed.
            $stmt->fetch();
            $stmt->closeCursor();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }


    /**
     * Delete a theatre by its ID.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        // Delete a theatre via stored procedure.  Returns true when the
        // deletion succeeds, false otherwise.
        try {
            $stmt = $this->getConnection()->prepare('CALL proc_delete_theater(:id)');
            $stmt->execute(['id' => $id]);
            $stmt->closeCursor();
            return true;
        } catch (\Throwable $ex) {
            return false;
        }
    }


    /**
     * Determine whether a theatre can be safely deleted.  A theatre is
     * only deletable when it does not host any performances that are
     * currently open for sale (status = "Đang mở bán").  If there is at
     * least one open performance associated with the theatre, the theatre
     * must not be deleted because doing so would orphan performance
     * records and break seat maps.
     *
     * @param int $theaterId
     * @return bool True if the theatre can be deleted, false otherwise
     */
    public function canDelete(int $theaterId): bool
    {
        // Determine whether a theatre can be deleted by calling a stored
        // procedure.  The procedure returns the number of open
        // performances associated with the theatre.  Deletion is
        // permitted only when the count is zero.
        $pdo = $this->getConnection();
        try {
            $stmt = $pdo->prepare('CALL proc_can_delete_theater(:tid)');
            $stmt->execute(['tid' => $theaterId]);
            $row = $stmt->fetch();
            $stmt->closeCursor();
            $count = isset($row['cnt']) ? (int)$row['cnt'] : 0;
            return $count === 0;
        } catch (\Throwable $ex) {
            return false;
        }
    }


    /**
     * Update the name of a theatre.  Rows and columns cannot be
     * modified after creation because seats are pre‑generated.  Returns
     * true on success.  Uses a stored procedure to perform the update.
     *
     * @param int    $id   Theatre ID
     * @param string $name New theatre name
     * @return bool        True on success
     */
    public function update(int $id, string $name): bool
    {
        try {
            $stmt = $this->getConnection()->prepare('CALL proc_update_theater(:id, :name)');
            $stmt->execute([
                'id'   => $id,
                'name' => $name
            ]);
            $stmt->closeCursor();
            return true;
        } catch (\Throwable $ex) {
            return false;
        }
    }


    /**
     * Modify an existing theatre by changing its name and optionally
     * adjusting the number of rows and columns.  When addRows or
     * addCols are non‑zero, rows/columns will be added (positive) or
     * removed (negative) from the end of the layout.  The update is
     * performed entirely via stored procedures inside a transaction.
     *
     * @param int    $id        Theatre identifier
     * @param string $name      New name for the theatre (leave empty to keep unchanged)
     * @param int    $addRows   Number of rows to add (>0) or remove (<0)
     * @param int    $addCols   Number of columns to add (>0) or remove (<0)
     * @return bool             True on success
     */
    public function modify(int $id, string $name, int $addRows, int $addCols): bool
    {
        $pdo = $this->getConnection();
        try {
            $pdo->beginTransaction();
            // Update name if provided
            if ($name !== '') {
                $stmtName = $pdo->prepare('CALL proc_update_theater(:tid, :tname)');
                $stmtName->execute([
                    'tid'   => $id,
                    'tname' => $name
                ]);
                $stmtName->closeCursor();
            }
            // Modify seat layout if rows or columns differ from zero
            if ($addRows !== 0 || $addCols !== 0) {
                $stmtMod = $pdo->prepare('CALL proc_modify_theater_size(:tid, :arow, :acol)');
                $stmtMod->execute([
                    'tid'  => $id,
                    'arow' => $addRows,
                    'acol' => $addCols
                ]);
                $stmtMod->closeCursor();
            }
            $pdo->commit();
            return true;
        } catch (\Throwable $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return false;
        }
    }


    /**
     * Approve a theatre by setting its status to "Đã hoạt động" via
     * stored procedure.  The caller should validate the seat layout
     * before invoking this method.  Returns true on success.
     *
     * @param int $id Theatre ID
     * @return bool
     */
    public function approve(int $id): bool
    {
        try {
            $stmt = $this->getConnection()->prepare('CALL proc_approve_theater(:tid)');
            $stmt->execute(['tid' => $id]);
            $stmt->closeCursor();
            return true;
        } catch (\Throwable $ex) {
            return false;
        }
    }
}