<?php
namespace App\Models;

class Theater extends Database
{
    /**
     *
     * @return array
     */
    public function all(): array
    {
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
     *
     * @param string $name     
     * @param int    $capacity 
     * @return bool    
     */
    /**
     * @param string $name 
     * @param int    $rows 
     * @param int    $cols 
     * @return bool 
     */
    public function create(string $name, int $rows, int $cols): bool
    {

        $pdo = $this->getConnection();
        try {
            $stmt = $pdo->prepare('CALL proc_create_theater(:name, :rows, :cols)');
            $stmt->execute([
                'name' => $name,
                'rows' => $rows,
                'cols' => $cols
            ]);
            $stmt->fetch();
            $stmt->closeCursor();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }


    /*
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {

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
     * @param int $theaterId
     * @return bool 
     */
    public function canDelete(int $theaterId): bool
    {

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
     * @param int    $id   
     * @param string $name
     * @return bool 
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
     * @param int    $id       
     * @param string $name      
     * @param int    $addRows   
     * @param int    $addCols   
     * @return bool       
     */
    public function modify(int $id, string $name, int $addRows, int $addCols): bool
    {
        $pdo = $this->getConnection();
        try {
            $pdo->beginTransaction();
            if ($name !== '') {
                $stmtName = $pdo->prepare('CALL proc_update_theater(:tid, :tname)');
                $stmtName->execute([
                    'tid'   => $id,
                    'tname' => $name
                ]);
                $stmtName->closeCursor();
            }
   
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