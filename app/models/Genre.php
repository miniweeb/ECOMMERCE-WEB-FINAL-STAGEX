<?php
namespace App\Models;


/**
 * Genre model provides CRUD operations for the `genres` table. Each genre
 * represents a category of plays and can be associated with one or many
 * shows via the `show_genres` pivot table. Administrators can create
 * new genres and delete existing ones. Listing all genres returns
 * rows sorted by their name for easy display in forms and tables.
 */
class Genre extends Database
{
    /**
     * Retrieve all genres ordered alphabetically.
     *
     * @return array
     */
    public function all(): array
    {
        // Retrieve all genres via stored procedure only.  No fallback query is
        // executed in this lighter version.  If the procedure is missing or
        // returns no rows, an empty array is returned.
        $pdo = $this->getConnection();
        try {
            $stmt = $pdo->query('CALL proc_get_all_genres()');
            $rows = $stmt->fetchAll();
            $stmt->closeCursor();
            return $rows ?: [];
        } catch (\Throwable $ex) {
            return [];
        }
    }


    /**
     * Insert a new genre into the database.
     *
     * @param string $name Name of the genre
     * @return bool True on success, false on failure
     */
    public function create(string $name): bool
    {
        // Use a stored procedure to insert a new genre.  The procedure
        // encapsulates the INSERT statement and timestamps the record.
        try {
            $stmt = $this->getConnection()->prepare('CALL proc_create_genre(:name)');
            $stmt->execute(['name' => $name]);
            // Fetch the returned genre_id to ensure the cursor is cleared.
            $stmt->fetch();
            $stmt->closeCursor();
            return true;
        } catch (\Throwable $ex) {
            return false;
        }
    }


    /**
     * Delete a genre by its ID. Any associated show_genres records will
     * cascade delete due to foreign key constraints. Returns true on
     * successful deletion, false otherwise.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        // Delete a genre via stored procedure.  Returns true on success.
        try {
            $stmt = $this->getConnection()->prepare('CALL proc_delete_genre(:id)');
            $stmt->execute(['id' => $id]);
            $stmt->closeCursor();
            return true;
        } catch (\Throwable $ex) {
            return false;
        }
    }


    /**
     * Update a genre's name.  Only the name field is editable.  Returns
     * true on success.  Used by the admin genre editor.
     *
     * @param int    $id   Genre identifier
     * @param string $name New genre name
     * @return bool        True on success
     */
    public function update(int $id, string $name): bool
    {
        // Invoke stored procedure to update the genre.  Suppress errors
        // and return false on failure.
        try {
            $stmt = $this->getConnection()->prepare('CALL proc_update_genre(:id, :name)');
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
}

