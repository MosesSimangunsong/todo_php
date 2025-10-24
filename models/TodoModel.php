<?php

require_once (__DIR__ . '/../config.php');

/**
 * Class TodoModel
 *
 * Bertanggung jawab untuk semua interaksi database yang terkait dengan tabel 'todo'.
 * Menangani operasi CRUD (Create, Read, Update, Delete) dan logika bisnis
 * seperti sorting dan pengecekan data duplikat.
 */
class TodoModel
{
    /**
     * Resource koneksi ke database PostgreSQL.
     * @var resource|false
     */
    private $conn;

    /**
     * TodoModel constructor.
     * Membangun koneksi ke database PostgreSQL.
     *
     * @throws RuntimeException Jika koneksi database gagal.
     */
    public function __construct()
    {
        // Bangun string koneksi
        $connection_string = 'host=' . DB_HOST . ' port=' . DB_PORT . ' dbname=' . DB_NAME . ' user=' . DB_USER . ' password=' . DB_PASSWORD;
        
        // Coba koneksi
        $this->conn = pg_connect($connection_string);
        
        // Ganti die() dengan Exception untuk penanganan error yang lebih baik
        if (!$this->conn) {
            throw new RuntimeException('Koneksi database gagal: ' . pg_last_error());
        }
    }

    /**
     * TodoModel destructor.
     * Secara otomatis menutup koneksi database saat objek dihancurkan.
     */
    public function __destruct()
    {
        if ($this->conn) {
            pg_close($this->conn);
        }
    }

    /**
     * Mengambil semua item todo dengan filter dan pencarian.
     *
     * @param string $filter Status filter ('all', 'finished', 'unfinished').
     * @param string $search Kueri pencarian (opsional).
     * @return array Daftar item todo.
     */
    public function getAllTodos($filter = 'all', $search = '')
    {
        $query = 'SELECT * FROM todo';
        $params = [];
        $conditions = [];
        $paramIndex = 1; // Indeks parameter untuk PostgreSQL

        // Terapkan filter status
        if ($filter === 'finished') {
            $conditions[] = 'is_finished = TRUE';
        } elseif ($filter === 'unfinished') {
            $conditions[] = 'is_finished = FALSE';
        }

        // Terapkan filter pencarian (case-insensitive)
        if (!empty($search)) {
            // Gunakan $paramIndex untuk placeholder
            $conditions[] = '(title ILIKE $' . $paramIndex . ' OR description ILIKE $' . $paramIndex . ')';
            $params[] = '%' . $search . '%';
            $paramIndex++;
        }

        // Gabungkan semua kondisi (jika ada)
        if (!empty($conditions)) {
            $query .= ' WHERE ' . implode(' AND ', $conditions);
        }

        // Terapkan sorting
        $query .= ' ORDER BY sort_order ASC, created_at DESC';

        try {
            $result = pg_query_params($this->conn, $query, $params);
            $todos = [];
            
            if ($result && pg_num_rows($result) > 0) {
                while ($row = pg_fetch_assoc($result)) {
                    // Gunakan helper untuk konversi data
                    $todos[] = $this->convertTodoRow($row);
                }
            }
            return $todos;

        } catch (Exception $e) {
            error_log("Gagal getAllTodos: " . $e->getMessage());
            return []; // Kembalikan array kosong jika terjadi error
        }
    }

    /**
     * Membuat item todo baru.
     *
     * @param string $title Judul todo.
     * @param string $description Deskripsi todo (opsional).
     * @return bool True jika berhasil, false jika gagal.
     */
    public function createTodo($title, $description)
    {
        // PERUBAHAN: Tambahkan "RETURNING id" untuk mendapatkan ID baru
        $query = 'INSERT INTO todo (title, description, sort_order) 
                  VALUES ($1, $2, (SELECT COALESCE(MAX(sort_order), 0) + 1 FROM todo))
                  RETURNING id'; // <-- TAMBAHAN DI SINI
        try {
            $result = pg_query_params($this->conn, $query, [$title, $description]);
            
            if ($result && pg_num_rows($result) > 0) {
                // Ambil ID yang dikembalikan
                $row = pg_fetch_assoc($result);
                return (int)$row['id']; // Kembalikan ID
            }
            return false; // Gagal insert

        } catch (Exception $e) {
            error_log("Gagal createTodo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Memperbarui item todo yang ada.
     *
     * @param int    $id ID todo yang akan diperbarui.
     * @param string $title Judul baru.
     * @param string $description Deskripsi baru.
     * @param bool   $is_finished Status selesai (boolean).
     * @return bool True jika berhasil, false jika gagal.
     */
    public function updateTodo($id, $title, $description, $is_finished)
    {
        // Model harus menerima boolean murni, bukan string 'true'/'false'.
        // PostgreSQL akan menangani konversi boolean PHP ke boolean SQL.
        $query = 'UPDATE todo SET title=$1, description=$2, is_finished=$3, updated_at=CURRENT_TIMESTAMP WHERE id=$4';
        try {
            $result = pg_query_params($this->conn, $query, [
                $title,
                $description,
                $is_finished, // Kirim sebagai boolean murni
                (int)$id
            ]);
            return $result !== false;
        } catch (Exception $e) {
            error_log("Gagal updateTodo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Menghapus item todo.
     *
     * @param int $id ID todo yang akan dihapus.
     * @return bool True jika berhasil, false jika gagal.
     */
    public function deleteTodo($id)
    {
        $query = 'DELETE FROM todo WHERE id=$1';
        try {
            $result = pg_query_params($this->conn, $query, [(int)$id]);
            return $result !== false;
        } catch (Exception $e) {
            error_log("Gagal deleteTodo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Memeriksa apakah judul sudah ada di database.
     *
     * @param string $title Judul yang akan diperiksa.
     * @param int|null $excludeId ID todo yang akan dikecualikan (digunakan saat update).
     * @return bool True jika judul sudah ada, false jika belum.
     */
    public function isTitleExists($title, $excludeId = null)
    {
        // Gunakan COUNT(*) AS count untuk hasil yang lebih jelas
        $query = 'SELECT COUNT(*) AS count FROM todo WHERE title = $1';
        $params = [$title];
        
        if ($excludeId !== null) {
            // Tambahkan kondisi untuk mengecualikan ID saat ini
            $query .= ' AND id != $2';
            $params[] = (int)$excludeId;
        }

        try {
            $result = pg_query_params($this->conn, $query, $params);
            if ($result) {
                $row = pg_fetch_assoc($result);
                return $row['count'] > 0;
            }
            return false; // Gagal query
        } catch (Exception $e) {
            error_log("Gagal isTitleExists: " . $e->getMessage());
            return false; // Asumsikan tidak ada jika error
        }
    }

    /**
     * Mengambil satu item todo berdasarkan ID.
     *
     * @param int $id ID todo.
     * @return array|null Data todo jika ditemukan, null jika tidak.
     */
    public function getTodoById($id)
    {
        $query = 'SELECT * FROM todo WHERE id = $1';
        try {
            $result = pg_query_params($this->conn, $query, [(int)$id]);
            
            if ($result && pg_num_rows($result) > 0) {
                $todo = pg_fetch_assoc($result);
                // Gunakan helper untuk konversi data
                return $this->convertTodoRow($todo);
            }
            return null; // Data tidak ditemukan
        } catch (Exception $e) {
            error_log("Gagal getTodoById: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Memperbarui urutan (sort_order) semua item berdasarkan array ID.
     * Menggunakan Transaksi Database untuk memastikan integritas data.
     *
     * @param array $idArray Array ID todo sesuai urutan baru.
     * @return bool True jika berhasil, false jika gagal.
     */
    public function updateSorting($idArray)
    {
        if (empty($idArray) || !is_array($idArray)) {
            return false;
        }

        // 1. Mulai Transaksi
        pg_query($this->conn, "BEGIN");

        try {
            // Loop melalui array (indeks = urutan baru, nilai = id)
            foreach ($idArray as $sortOrder => $id) {
                // Konversi ke integer untuk keamanan
                // sort_order dimulai dari 1 (index 0 + 1)
                $newOrder = (int)$sortOrder + 1;
                $todoId = (int)$id;

                // 3. Jalankan query UPDATE sederhana
                $query = 'UPDATE todo SET sort_order = $1 WHERE id = $2';
                $result = pg_query_params($this->conn, $query, [$newOrder, $todoId]);

                if (!$result) {
                    // Jika satu query gagal, lemparkan Exception untuk memicu ROLLBACK
                    throw new Exception(pg_last_error($this->conn));
                }
            }

            // 4. Jika semua berhasil, simpan perubahan
            pg_query($this->conn, "COMMIT");
            return true;

        } catch (Exception $e) {
            // 5. Jika terjadi error, batalkan semua perubahan
            pg_query($this->conn, "ROLLBACK");
            error_log("Gagal updateSorting: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Helper privat untuk mengonversi baris data todo dari database.
     * Utamanya mengubah 't'/'f' PostgreSQL menjadi boolean PHP.
     *
     * @param array $row Baris data asosiatif dari pg_fetch_assoc.
     * @return array Baris data yang telah dikonversi.
     */
    private function convertTodoRow($row)
    {
        // Konversi 't' (true) / 'f' (false) dari PostgreSQL ke boolean PHP
        $row['is_finished'] = ($row['is_finished'] === 't');
        return $row;
    }
}