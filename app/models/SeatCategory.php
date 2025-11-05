<?php
namespace App\Models;


/**
 * SeatCategory model provides access to seat categories (Hạng A/B/C) defined
 * in the `seat_categories` table.  Each category has a base price and a
 * CSS color class used when rendering the seat grid.
 */
class SeatCategory extends Database
{
    public function all(): array
    {
        // Retrieve all seat categories via stored procedure only.  No fallback
        // query is executed in this lighter version.  Return an empty array
        // if the procedure is missing or yields no result.
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
     * Insert a new seat category.  Each seat category contains a name,
     * a base price and a CSS color class for rendering seats.  Returns
     * true on success.
     *
     * @param string $name       Category name (e.g. "VIP", "Thường")
     * @param float  $price      Base price for tickets in this category
     * @param string $colorClass Bootstrap colour class (e.g. "danger")
     * @return bool
     */
    public function create(string $name, float $price, string $colorClass): bool
    {
        // Insert a new seat category via stored procedure.  This call
        // encapsulates the INSERT statement into a routine.  Returns true
        // on success and false on failure.
        try {
            $stmt = $this->getConnection()->prepare('CALL proc_create_seat_category(:name, :price, :color)');
            $stmt->execute([
                'name'  => $name,
                'price' => $price,
                'color' => $colorClass
            ]);
            // Fetch the returned category_id to clear the result set.  This helps
            // prevent "commands out of sync" errors when subsequent queries
            // are executed on the same connection.
            $stmt->fetch();
            $stmt->closeCursor();
            return true;
        } catch (\Throwable $ex) {
            return false;
        }
    }


    /**
     * Delete a seat category by its ID.  Related seats should be
     * removed separately.  Returns true on success.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        // Remove a seat category using a stored procedure.  Returns true
        // when the deletion succeeds, false otherwise.
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
     * Update an existing seat category.  Allows administrators to
     * modify the category name, base price and colour class.  Returns
     * true on success.  The colour class is required even if it is
     * unchanged because the stored procedure updates all fields.
     *
     * @param int    $id         Category identifier
     * @param string $name       New category name
     * @param float  $price      New base price
     * @param string $colorClass Colour class (Bootstrap)
     * @return bool              True on success
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
     * Find a seat category by its base price.  Returns the first matching
     * category or null if none exists.  This helper is used by the
     * admin controller to prevent duplicate price tiers when creating
     * new seat ranks.
     *
     * @param float $price Base price to look up
     * @return array|null
     */
    public function findByPrice(float $price)
    {
        $pdo = $this->getConnection();
        // Use a stored procedure to find a seat category by its price.  If no
        // category matches, null is returned.
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
     * Determine if a seat category can be deleted.  A category cannot be
     * deleted if it is assigned to seats that belong to any theatre
     * which has an open performance (status = "Đang mở bán").  This
     * prevents administrators from removing seat pricing tiers that are
     * currently in use.
     *
     * @param int $categoryId The seat category ID
     * @return bool True if deletable, false if the category is in use
     */
    public function canDelete(int $categoryId): bool
    {
        // Determine if a seat category can be deleted by invoking the
        // stored procedure.  The procedure returns the number of seats
        // currently in use by open performances.  When zero, deletion
        // is allowed.
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

