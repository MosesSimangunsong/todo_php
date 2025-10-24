<?php

// Mengimpor file model yang diperlukan
require_once (__DIR__ . '/../models/TodoModel.php');

/**
 * Class TodoController
 *
 * Bertanggung jawab untuk menangani semua logika bisnis dan permintaan
 * yang terkait dengan item Todo.
 * Menghubungkan Model (TodoModel) dengan View (TodoView, TodoDetailView).
 */
class TodoController
{
    /**
     * Properti untuk menyimpan instance dari TodoModel.
     * @var TodoModel
     */
    private $todoModel;

    /**
     * Constructor untuk TodoController.
     * Secara otomatis menginisiasi TodoModel untuk digunakan di semua method.
     */
    public function __construct()
    {
        $this->todoModel = new TodoModel();
    }

    /**
     * Menampilkan halaman utama (daftar todo).
     * Mengambil semua todo berdasarkan filter dan kueri pencarian.
     *
     * @return void
     */
    public function index()
    {
        // Ambil parameter filter dan search dari URL
        $filter = $_GET['filter'] ?? 'all';
        $search = $_GET['search'] ?? '';
        
        // Ambil data todo dari model
        $todos = $this->todoModel->getAllTodos($filter, $search);
        
        // Muat file view dan teruskan variabel $todos, $filter, dan $search
        include (__DIR__ . '/../views/TodoView.php');
    }

    /**
     * Menangani pembuatan todo baru.
     * Hanya merespon request POST.
     *
     * @return void
     */
    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = $_POST['title'];
            $description = $_POST['description'];

            if ($this->todoModel->isTitleExists($title)) {
                $_SESSION['error_message'] = 'Gagal menambah data. Judul "' . htmlspecialchars($title) . '" sudah ada!';
            } else {
                // PERUBAHAN: Tangkap ID baru
                $newId = $this->todoModel->createTodo($title, $description);
                
                if ($newId) {
                    $_SESSION['success_message'] = 'Data todo berhasil ditambahkan!';
                    $_SESSION['highlight_id'] = $newId; // <-- SIMPAN ID KE SESSION
                } else {
                    $_SESSION['error_message'] = 'Gagal menyimpan data ke database.';
                }
            }
        }
        $this->redirectBack();
    }

    /**
     * Menangani pembaruan (update) todo yang sudah ada.
     * Hanya merespon request POST.
     *
     * @return void
     */
    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'];
            $title = $_POST['title'];
            $description = $_POST['description'];
            // PERBAIKAN KECIL: Pastikan ini dikonversi ke boolean
            $is_finished = (isset($_POST['is_finished']) && $_POST['is_finished'] === 'true');

            if ($this->todoModel->isTitleExists($title, $id)) {
                $_SESSION['error_message'] = 'Gagal memperbarui data. Judul "' . htmlspecialchars($title) . '" sudah ada!';
            } else {
                // PERUBAHAN: Cek hasil update
                if ($this->todoModel->updateTodo($id, $title, $description, $is_finished)) {
                    $_SESSION['success_message'] = 'Data todo berhasil diperbarui!';
                    $_SESSION['highlight_id'] = (int)$id; // <-- SIMPAN ID KE SESSION
                } else {
                    $_SESSION['error_message'] = 'Gagal memperbarui data di database.';
                }
            }
        }
        $this->redirectBack();
    }

    /**
     * Menangani penghapusan todo.
     * Merespon request GET dengan parameter ID.
     * Catatan: Sebaiknya gunakan POST/DELETE untuk operasi penghapusan demi keamanan.
     *
     * @return void
     */
    public function delete()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
            $id = $_GET['id'];
            $this->todoModel->deleteTodo($id);
            $_SESSION['success_message'] = 'Data todo berhasil dihapus!';
        }
        // Arahkan kembali pengguna ke halaman sebelumnya
        $this->redirectBack();
    }

    /**
     * Menampilkan halaman detail untuk satu todo.
     *
     * @return void
     */
    public function show()
    {
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            $todo = $this->todoModel->getTodoById($id);

            // Jika todo ditemukan, tampilkan detail
            if ($todo) {
                include (__DIR__ . '/../views/TodoDetailView.php');
            } else {
                // Jika tidak ditemukan, set error dan kembali ke index
                $_SESSION['error_message'] = 'Todo tidak ditemukan!';
                $this->redirectBack('index.php');
            }
        } else {
            // Jika ID tidak ada di URL
            $_SESSION['error_message'] = 'ID Todo tidak valid!';
            $this->redirectBack('index.php');
        }
    }

    /**
     * Menangani penyimpanan urutan (sorting) via AJAX.
     * Merespon request POST dengan payload JSON.
     *
     * @return void
     */
    public function saveSortOrder()
    {
        // Hanya izinkan metode POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405); // Method Not Allowed
            $this->jsonResponse(false, 'Method not allowed');
        }

        // Ambil data JSON mentah dari body request
        $jsonPayload = file_get_contents('php://input');
        $data = json_decode($jsonPayload, true);

        // Validasi data input
        if (empty($data['order']) || !is_array($data['order'])) {
            http_response_code(400); // Bad Request
            $this->jsonResponse(false, 'Data urutan tidak valid');
        }

        // Panggil model untuk memperbarui urutan
        $success = $this->todoModel->updateSorting($data['order']);

        if ($success) {
            $this->jsonResponse(true, 'Urutan berhasil disimpan');
        } else {
            http_response_code(500); // Internal Server Error
            $this->jsonResponse(false, 'Gagal menyimpan urutan di database');
        }
    }

    /**
     * Method helper untuk mengirim respons JSON terstandar.
     *
     * @param bool $success Status keberhasilan operasi.
     * @param string $message Pesan yang akan dikirim.
     * @param array $data Data tambahan (opsional).
     * @return void
     */
    private function jsonResponse($success, $message, $data = [])
    {
        header('Content-Type: application/json');
        $response = ['success' => $success, 'message' => $message];
        if (!empty($data)) {
            $response['data'] = $data;
        }
        echo json_encode($response);
        exit; // Pastikan skrip berhenti setelah mengirim respons JSON
    }

    /**
     * Method helper untuk mengalihkan (redirect) pengguna.
     * Mengarahkan ke HTTP_REFERER (halaman sebelumnya) jika ada, 
     * atau ke URL default jika tidak ada.
     *
     * @param string $default URL default untuk redirect.
     * @return void
     */
    private function redirectBack($default = 'index.php')
    {
        // Perbaiki sintaks null coalescing operator dan pastikan keamanan
        $location = $_SERVER['HTTP_REFERER'] ?? $default;
        
        // (Opsional) Tambahkan validasi host untuk mencegah Open Redirect Vulnerability
        // ...

        header('Location: ' . $location);
        exit; // Pastikan skrip berhenti setelah mengirim header redirect
    }
}