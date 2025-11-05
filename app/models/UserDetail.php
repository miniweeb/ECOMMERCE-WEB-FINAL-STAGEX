<?php
namespace App\Models;

class UserDetail extends Database
{
    /**
     * Fetch the user_detail record for a specific user.  Returns null
     * if no record exists.
     *
     * @param int $userId
     * @return array|null
     */
    public function find(int $userId): ?array
    {
        $pdo = $this->getConnection();
        // Use stored procedure only; return null if not found or on error
        try {
            $stmt = $pdo->prepare('CALL proc_get_user_detail_by_id(:uid)');
            $stmt->execute(['uid' => $userId]);
            $row = $stmt->fetch();
            $stmt->closeCursor();
            return $row ?: null;
        } catch (\Throwable $ex) {
            return null;
        }
    }

    /**
     * Insert or update a user's detailed information.  If a record
     * already exists for the user ID, it will be updated; otherwise
     * a new row is inserted.  Returns true on success.
     *
     * @param int         $userId
     * @param string|null $fullName
     * @param string|null $dob       Date of birth in YYYY-MM-DD format
     * @param string|null $city
     * @return bool
     */
    public function save(int $userId, ?string $fullName, ?string $dob, ?string $address, ?string $phone): bool
    {
        $pdo = $this->getConnection();
        // Use stored procedure only for upsert; return false on error
        try {
            $stmt = $pdo->prepare('CALL proc_upsert_user_detail(:uid, :fullname, :dob, :addr, :phone)');
            $stmt->execute([
                'uid'      => $userId,
                'fullname' => $fullName,
                'dob'      => $dob,
                'addr'     => $address,
                'phone'    => $phone
            ]);
            $stmt->closeCursor();
            return true;
        } catch (\Throwable $ex) {
            return false;
        }
    }
}