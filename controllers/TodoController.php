<?php
require_once (__DIR__ . '/../models/TodoModel.php');

class TodoController
{
    public function index()
    {
        $filter = $_GET['filter'] ?? 'all';
        $search = $_GET['search'] ?? '';
        $todoModel = new TodoModel();
        $todos = $todoModel->getAllTodos($filter, $search);
        include (__DIR__ . '/../views/TodoView.php');
    }

    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = $_POST['title'];
            $description = $_POST['description'];
            $todoModel = new TodoModel();

            if ($todoModel->isTitleExists($title)) {
                $_SESSION['error_message'] = 'Gagal menambah data. Judul "' . htmlspecialchars($title) . '" sudah ada!';
            } else {
                $todoModel->createTodo($title, $description);
                $_SESSION['success_message'] = 'Data todo berhasil ditambahkan!';
            }
        }
        header('Location: ' . $_SERVER['HTTP_REFERER'] ?? 'index.php');
    }

    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'];
            $title = $_POST['title'];
            $description = $_POST['description'];
            $is_finished = $_POST['is_finished'];
            $todoModel = new TodoModel();

            if ($todoModel->isTitleExists($title, $id)) {
                $_SESSION['error_message'] = 'Gagal memperbarui data. Judul "' . htmlspecialchars($title) . '" sudah ada!';
            } else {
                $todoModel->updateTodo($id, $title, $description, $is_finished);
                $_SESSION['success_message'] = 'Data todo berhasil diperbarui!';
            }
        }
        header('Location: ' . $_SERVER['HTTP_REFERER'] ?? 'index.php');
    }

    public function delete()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
            $id = $_GET['id'];
            $todoModel = new TodoModel();
            $todoModel->deleteTodo($id);
            $_SESSION['success_message'] = 'Data todo berhasil dihapus!';
        }
        header('Location: ' . $_SERVER['HTTP_REFERER'] ?? 'index.php');
    }

    public function show()
    {
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            $todoModel = new TodoModel();
            $todo = $todoModel->getTodoById($id);

            if ($todo) {
                include (__DIR__ . '/../views/TodoDetailView.php');
            } else {
                $_SESSION['error_message'] = 'Todo tidak ditemukan!';
                header('Location: index.php');
            }
        } else {
            $_SESSION['error_message'] = 'ID Todo tidak valid!';
            header('Location: index.php');
        }
    }

    /**
     * KEMBALIKAN method saveSortOrder()
     */
    public function saveSortOrder()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        $jsonPayload = file_get_contents('php://input');
        $data = json_decode($jsonPayload, true);

        if (empty($data['order']) || !is_array($data['order'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Data urutan tidak valid']);
            return;
        }

        $todoModel = new TodoModel();
        $success = $todoModel->updateSorting($data['order']); // Panggil method yang sudah diperbaiki

        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Urutan berhasil disimpan']);
        } else {
            // Berikan pesan error yang lebih spesifik jika query gagal
            http_response_code(500); 
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan urutan di database']);
        }
    }
}