<?php
require_once (__DIR__ . '/../config.php');

class TodoModel
{
    private $conn;

    public function __construct()
    {
        $this->conn = pg_connect('host=' . DB_HOST . ' port=' . DB_PORT . ' dbname=' . DB_NAME . ' user=' . DB_USER . ' password=' . DB_PASSWORD);
        if (!$this->conn) {
            die('Koneksi database gagal');
        }
    }

    public function getAllTodos($filter = 'all', $search = '')
    {
        $query = 'SELECT * FROM todo';
        $params = [];
        $conditions = [];
        $paramIndex = 1;

        if ($filter === 'finished') {
            $conditions[] = 'is_finished = TRUE';
        } elseif ($filter === 'unfinished') {
            $conditions[] = 'is_finished = FALSE';
        }

        if (!empty($search)) {
            $conditions[] = '(title ILIKE $' . $paramIndex . ' OR description ILIKE $' . $paramIndex . ')';
            $params[] = '%' . $search . '%';
            $paramIndex++;
        }

        if (!empty($conditions)) {
            $query .= ' WHERE ' . implode(' AND ', $conditions);
        }

        // Query sorting (sudah benar)
        $query .= ' ORDER BY sort_order ASC, created_at DESC';

        $result = pg_query_params($this->conn, $query, $params);
        $todos = [];
        if ($result && pg_num_rows($result) > 0) {
            while ($row = pg_fetch_assoc($result)) {
                $row['is_finished'] = ($row['is_finished'] === 't');
                $todos[] = $row;
            }
        }
        return $todos;
    }

    public function createTodo($title, $description)
    {
        // Query create (sudah benar)
        $query = 'INSERT INTO todo (title, description, sort_order) 
                  VALUES ($1, $2, (SELECT COALESCE(MAX(sort_order), 0) + 1 FROM todo))';
        $result = pg_query_params($this->conn, $query, [$title, $description]);
        return $result !== false;
    }

    public function updateTodo($id, $title, $description, $is_finished)
    {
        $is_finished_bool = ($is_finished === 'true') ? 'TRUE' : 'FALSE';
        $query = 'UPDATE todo SET title=$1, description=$2, is_finished=$3 WHERE id=$4';
        $result = pg_query_params($this->conn, $query, [$title, $description, $is_finished_bool, (int)$id]);
        return $result !== false;
    }

    public function deleteTodo($id)
    {
        $query = 'DELETE FROM todo WHERE id=$1';
        $result = pg_query_params($this->conn, $query, [(int)$id]);
        return $result !== false;
    }

    /**
     * PERBAIKAN BUG LOGIKA:
     * Versi ini lebih bersih dan dijamin benar.
     */
    public function isTitleExists($title, $excludeId = null)
    {
        // Query dasar
        $query = 'SELECT COUNT(*) FROM todo WHERE title = $1';
        $params = [$title];
        
        if ($excludeId !== null) {
            // Jika ada excludeId, tambahkan kondisi AND
            $query .= ' AND id != $2';
            $params[] = (int)$excludeId; // Pastikan ini integer
        }

        $result = pg_query_params($this->conn, $query, $params);
        if ($result) {
            $row = pg_fetch_row($result);
            return $row[0] > 0;
        }
        return false;
    }

    public function getTodoById($id)
    {
        $query = 'SELECT * FROM todo WHERE id = $1';
        $result = pg_query_params($this->conn, $query, [(int)$id]); // Pastikan ini integer
        
        if ($result && pg_num_rows($result) > 0) {
            $todo = pg_fetch_assoc($result);
            $todo['is_finished'] = ($todo['is_finished'] === 't');
            return $todo;
        }
        return null;
    }

    /**
     * =========================================================
     * PERBAIKAN TOTAL UNTUK ERROR SORTING
     * Mengganti 1 query CASE kompleks dengan Transaksi
     * =========================================================
     */
    public function updateSorting($idArray)
    {
        if (empty($idArray)) {
            return false;
        }

        // 1. Mulai Transaksi
        pg_query($this->conn, "BEGIN");

        try {
            foreach ($idArray as $sortOrder => $id) {
                // $sortOrder adalah index (0, 1, 2...)
                // $id adalah ID todo (misal: "3", "1", "2")

                // 2. Siapkan data sebagai INTEGER (Ini kuncinya)
                $newOrder = (int)$sortOrder + 1; // Urutan baru (1, 2, 3...)
                $todoId = (int)$id;             // ID todo

                // 3. Jalankan query UPDATE sederhana
                $query = 'UPDATE todo SET sort_order = $1 WHERE id = $2';
                $result = pg_query_params($this->conn, $query, [$newOrder, $todoId]);

                if (!$result) {
                    // Jika satu saja gagal, lemparkan error untuk memicu ROLLBACK
                    throw new Exception(pg_last_error($this->conn));
                }
            }

            // 4. Jika semua berhasil, simpan perubahan
            pg_query($this->conn, "COMMIT");
            return true;

        } catch (Exception $e) {
            // 5. Jika terjadi error, batalkan semua perubahan
            pg_query($this->conn, "ROLLBACK");
            error_log("Gagal update sorting: " . $e->getMessage()); // Catat error
            return false;
        }
    }
}