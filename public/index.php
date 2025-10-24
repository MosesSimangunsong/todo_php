<?php
/**
 * Front Controller (Router)
 *
 * File: public/index.php
 *
 * Ini adalah file entry point tunggal (single point of entry) untuk
 * semua permintaan (request) HTTP ke aplikasi.
 *
 * Tugasnya adalah:
 * 1. Memulai session untuk notifikasi/flash messages.
 * 2. Memuat (include) TodoController.
 * 3. Menentukan 'page' (aksi) yang diminta dari URL.
 * 4. Memvalidasi aksi terhadap "whitelist" (daftar yang diizinkan).
 * 5. Memanggil method (aksi) yang sesuai pada controller.
 */

// 1. Memulai session di paling atas, penting untuk flash messages
session_start();

// 2. Memuat file controller utama
// Menggunakan __DIR__ untuk path absolut yang lebih andal
require_once (__DIR__ . '/../controllers/TodoController.php');

// 3. Menentukan aksi (halaman) yang akan dieksekusi
// Gunakan null coalescing operator (??) untuk menetapkan 'index' sebagai default
$action = $_GET['page'] ?? 'index';

// 4. Whitelist aksi yang diizinkan
// Ini adalah langkah keamanan penting untuk mencegah pemanggilan method sembarangan.
// Nama dalam array ini HARUS sama persis dengan nama method di TodoController.
$allowedActions = [
    'index',
    'create',
    'update',
    'delete',
    'show',
    'saveSortOrder'
];

// Inisialisasi controller
$todoController = new TodoController();

// 5. Validasi dan panggil aksi
// Cek apakah aksi yang diminta ada di dalam whitelist DAN
// apakah method-nya benar-benar ada di class TodoController.
if (in_array($action, $allowedActions) && method_exists($todoController, $action)) {
    
    // Panggil method secara dinamis
    // (misal: $todoController->index(), $todoController->create(), dll.)
    $todoController->$action();

} else {
    
    // Jika aksi tidak valid atau tidak ada,
    // tampilkan halaman default (index) atau halaman 404.
    // 'index' adalah default yang aman.
    http_response_code(404); // Beri tahu browser bahwa halaman tidak ditemukan
    $todoController->index();
}