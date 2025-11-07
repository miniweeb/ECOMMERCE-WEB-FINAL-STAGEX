<?php
namespace App\Models;


class SeatCategory extends Database
{
    public function all(): array
    {
        $pdo = $this->getConnection();
        try {
            $stmt = $pdo->query('CALL proc_get_all_seat_categories()');
            $rows = $stmt->fetchAll();
            $stmt->closeCursor();
            return $rows ?: [];
        } catch (\Throwable $ex) {
            return [];
        }
    }


    public function find(int $id)
    {
        $pdo = $this->getConnection();
        try {
            $stmt = $pdo->prepare('CALL proc_get_seat_category_by_id(:cid)');
            $stmt->execute(['cid' => $id]);
            $row = $stmt->fetch();
            $stmt->closeCursor();
            return $row ?: null;
        } catch (\Throwable $ex) {
            return null;
        }
    }


    /**
     * @param string $name       
     * @param float  $price      
     * @param string $colorClass 
     * @return bool
     */
    public function create(string $name, float $price, string $colorClass): bool
    {
        try {
            $stmt = $this->getConnection()->prepare('CALL proc_create_seat_category(:name, :price, :color)');
            $stmt->execute([
                'name'  => $name,
                'price' => $price,
                'color' => $colorClass
            ]);
            $stmt->fetch();
            $stmt->closeCursor();
            return true;
        } catch (\Throwable $ex) {
            return false;
        }
    }


    /**
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        try {
            $stmt = $this->getConnection()->prepare('CALL proc_delete_seat_category(:id)');
            $stmt->execute(['id' => $id]);
            $stmt->closeCursor();
            return true;
        } catch (\Throwable $ex) {
            return false;
        }
    }


    /**
     * @param int    $id         
     * @param string $name       
     * @param float  $price      
     * @param string $colorClass 
     * @return bool              
     */
    public function update(int $id, string $name, float $price, string $colorClass): bool
    {
        try {
            $stmt = $this->getConnection()->prepare('CALL proc_update_seat_category(:id, :name, :price, :color)');
            $stmt->execute([
                'id'    => $id,
                'name'  => $name,
                'price' => $price,
                'color' => $colorClass
            ]);
            $stmt->closeCursor();
            return true;
        } catch (\Throwable $ex) {
            return false;
        }
    }


    /**

     * @param float $price 
     * @return array|null
     */
    public function findByPrice(float $price)
    {
        $pdo = $this->getConnection();
        try {
            $stmt = $pdo->prepare('CALL proc_get_seat_category_by_price(:price)');
            $stmt->execute(['price' => $price]);
            $row = $stmt->fetch();
            $stmt->closeCursor();
            return $row ?: null;
        } catch (\Throwable $ex) {
            return null;
        }
    }


    /**
 in use.
     *
     * @param int $categoryId 
     * @return bool
     */
    public function canDelete(int $categoryId): bool
    {
        $pdo = $this->getConnection();
        try {
            $stmt = $pdo->prepare('CALL proc_can_delete_seat_category(:cid)');
            $stmt->execute(['cid' => $categoryId]);
            $row = $stmt->fetch();
            $stmt->closeCursor();
            $count = isset($row['cnt']) ? (int)$row['cnt'] : 0;
            return $count === 0;
        } catch (\Throwable $ex) {
            return false;
        }
    }
}

