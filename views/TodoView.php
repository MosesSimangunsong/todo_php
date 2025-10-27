<?php
/*
 * File: views/TodoView.php
 *
 * Ini adalah file tampilan utama untuk aplikasi Todolist.
 * Bertanggung jawab untuk me-render:
 * 1. Notifikasi (Toast)
 * 2. Header halaman
 * 3. Form Filter dan Pencarian
 * 4. Daftar item todo
 * 5. Modal untuk Tambah, Ubah, dan Hapus
 * 6. Skrip JavaScript yang diperlukan
 *
 * Variabel yang diharapkan dari Controller:
 * - $todos (array): Daftar item todo.
 * - $filter (string): Status filter saat ini ('all', 'finished', 'unfinished').
 * - $search (string): Kueri pencarian saat ini.
 * - $_SESSION['success_message'] (string): Pesan sukses (opsional).
 * - $_SESSION['error_message'] (string): Pesan error (opsional).
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP - Aplikasi Todolist</title>

    <link href="/assets/vendor/bootstrap-5.3.8-dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <link rel="stylesheet" href="/assets/css/custom.css">
</head>
<body>

    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100">
        
        <?php /* Tampilkan toast sukses jika ada di session */ ?>
        <?php if (isset($_SESSION['success_message'])): ?>
            <div id="successToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <?= htmlspecialchars($_SESSION['success_message']) ?><?php // Escape output ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
            <?php unset($_SESSION['success_message']); // Hapus pesan setelah ditampilkan ?>
        <?php endif; ?>

        <?php /* Tampilkan toast error jika ada di session */ ?>
        <?php if (isset($_SESSION['error_message'])): ?>
            <div id="errorToast" class="toast align-items-center text-bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?= htmlspecialchars($_SESSION['error_message']) ?><?php // Escape output ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
            <?php unset($_SESSION['error_message']); // Hapus pesan setelah ditampilkan ?>
        <?php endif; ?>
    </div>

    <div class="container main-container">
        <div class="card main-card">
            <div class="card-body">
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0 d-flex align-items-center">
                        <i class="bi bi-card-checklist me-2"></i>
                        Todolist
                    </h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTodo">
                        <i class="bi bi-plus-lg me-1"></i> 
                        Tambah Tugas
                    </button>
                </div>
                
                <form action="index.php" method="GET" class="row g-3 mb-4 align-items-end filter-form">
                    <input type="hidden" name="page" value="index">
                    
                    <div class="col-md-4">
                        <label for="filter" class="form-label">Filter Status</label>
                        <select name="filter" id="filter" class="form-select" onchange="this.form.submit()">
                            <?php // Gunakan ternary operator untuk menentukan 'selected' ?>
                            <option value="all" <?= ($filter === 'all') ? 'selected' : '' ?>>Semua</option>
                            <option value="unfinished" <?= ($filter === 'unfinished') ? 'selected' : '' ?>>Belum Selesai</option>
                            <option value="finished" <?= ($filter === 'finished') ? 'selected' : '' ?>>Selesai</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="search" class="form-label">Cari (Judul/Deskripsi)</label>
                        <input type="text" name="search" id="search" class="form-control" 
                               placeholder="Ketik untuk mencari..." 
                               value="<?= htmlspecialchars($search) // Selalu escape value dari user ?>">
                    </div>
                    
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-search"></i> Cari
                        </button>
                    </div>
                </form>

                <div class="list-group" id="sortable-list">
                    
                    <?php if (!empty($todos)): ?>
                        <?php foreach ($todos as $i => $todo): ?>
                            
                            <div class="list-group-item" data-id="<?= $todo['id'] // ID unik untuk sorting ?>">
                                <div class="d-flex align-items-center">
                                    
                                    <div class="me-3 drag-handle" data-bs-toggle="tooltip" data-bs-title="Geser untuk urutkan">
                                        <i class="bi bi-grip-vertical fs-5"></i>
                                    </div>

                                    <div class="flex-grow-1 todo-content">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <h5 class="todo-title mb-1"><?= htmlspecialchars($todo['title']) ?></h5>
                                            
                                            <?php // Tampilkan badge status ?>
                                            <?php if ($todo['is_finished']): ?>
                                                <span class="badge bg-success ms-3">Selesai</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger ms-3">Belum Selesai</span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="todo-description mb-0">
                                            <?php // Tampilkan deskripsi atau pesan placeholder ?>
                                            <?php if (!empty($todo['description'])): ?>
                                                <?= htmlspecialchars($todo['description']) ?>
                                            <?php else: ?>
                                                <em class="small">Tidak ada deskripsi.</em>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    
                                    <div class="ms-3 todo-actions">
                                        <a href="?page=show&id=<?= $todo['id'] ?>" class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip" data-bs-title="Lihat Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        
                                        <?php // PERBAIKAN: Gunakan data-* attributes untuk passing data ke JS ?>
                                        <button class="btn btn-sm btn-outline-warning" 
                                                data-bs-toggle="tooltip" 
                                                data-bs-title="Ubah Data"
                                                onclick="showModalEditTodo(this)"
                                                data-id="<?= $todo['id'] ?>"
                                                data-title="<?= htmlspecialchars($todo['title']) ?>"
                                                data-description="<?= htmlspecialchars($todo['description'] ?? '') ?>"
                                                data-finished="<?= $todo['is_finished'] ? 'true' : 'false' ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        
                                        <?php // PERBAIKAN: Gunakan data-* attributes ?>
                                        <button class="btn btn-sm btn-outline-danger" 
                                                data-bs-toggle="tooltip" 
                                                data-bs-title="Hapus Data"
                                                onclick="showModalDeleteTodo(this)"
                                                data-id="<?= $todo['id'] ?>"
                                                data-title="<?= htmlspecialchars($todo['title']) ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    
                    <?php else: ?>
                        
                        <div class="list-group-item text-center text-muted p-5">
                            <h4>Tidak ada data ditemukan</h4>
                            <p class="mb-0">Coba ubah filter Anda atau tambahkan tugas baru.</p>
                        </div>
                    
                    <?php endif; ?>
                    
                </div> </div> </div> </div> <div class="modal fade" id="addTodo" tabindex="-1" aria-labelledby="addTodoLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-3">
                <form action="?page=create" method="POST">
                    <div class="modal-header border-0">
                        <h5 class="modal-title" id="addTodoLabel">Tambah Data Todo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="inputTitle" class="form-label">Judul</label>
                            <input type="text" name="title" class="form-control" id="inputTitle" placeholder="Contoh: Belajar PHP MVC" required>
                        </div>
                        <div class="mb-3">
                            <label for="inputDescription" class="form-label">Deskripsi (Opsional)</label>
                            <textarea name="description" class="form-control" id="inputDescription" rows="3" placeholder="Contoh: Menyelesaikan praktikum PABWE..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editTodo" tabindex="-1" aria-labelledby="editTodoLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-3">
                <form action="?page=update" method="POST">
                    <input name="id" type="hidden" id="inputEditTodoId">
                    <div class="modal-header border-0">
                        <h5 class="modal-title" id="editTodoLabel">Ubah Data Todo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="inputEditTitle" class="form-label">Judul</label>
                            <input type="text" name="title" class="form-control" id="inputEditTitle" required>
                        </div>
                        <div class="mb-3">
                            <label for="inputEditDescription" class="form-label">Deskripsi (Opsional)</label>
                            <textarea name="description" class="form-control" id="inputEditDescription" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="selectEditIsFinished" class="form-label">Status</label>
                            <select class="form-select" name="is_finished" id="selectEditIsFinished">
                                <option value="false">Belum Selesai</option>
                                <option value="true">Selesai</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteTodo" tabindex="-1" aria-labelledby="deleteTodoLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-3">
                <div class="modal-header border-0">
                    <h5 class="modal-title" id="deleteTodoLabel">Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Kamu akan menghapus todo <strong class="text-danger" id="deleteTodoTitle"></strong>.</p>
                    <p>Apakah kamu yakin?</p>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <a id="btnDeleteTodo" class="btn btn-danger">Ya, Tetap Hapus</a>
                </div>
            </div>
        </div>
    </div>

    <script src="/assets/vendor/bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

    <script>
        /**
         * Menampilkan modal Edit dan mengisi formnya berdasarkan data dari tombol.
         * PERBAIKAN: Menggunakan 'element.dataset' untuk mengambil data
         * dari atribut data-* agar lebih aman dan maintenance-friendly.
         * @param {HTMLElement} element - Tombol edit yang diklik (this).
         */
        function showModalEditTodo(element) {
            // Ambil data dari atribut data-*
            const id = element.dataset.id;
            const title = element.dataset.title;
            const description = element.dataset.description;
            const isFinished = element.dataset.finished;

            // Isi nilai form di dalam modal
            document.getElementById("inputEditTodoId").value = id;
            document.getElementById("inputEditTitle").value = title;
            document.getElementById("inputEditDescription").value = description;
            document.getElementById("selectEditIsFinished").value = isFinished;
            
            // Tampilkan modal
            var myModal = new bootstrap.Modal(document.getElementById("editTodo"));
            myModal.show();
        }

        /**
         * Menampilkan modal konfirmasi Hapus.
         * PERBAIKAN: Menggunakan 'element.dataset' untuk mengambil data.
         * @param {HTMLElement} element - Tombol hapus yang diklik (this).
         */
        function showModalDeleteTodo(element) {
            // Ambil data dari atribut data-*
            const id = element.dataset.id;
            const title = element.dataset.title;

            // Ambil parameter URL saat ini untuk ditambahkan ke link Hapus
            // agar filter dan search tetap aktif setelah redirect.
            const urlParams = new URLSearchParams(window.location.search);
            const filter = urlParams.get('filter') ?? 'all';
            const search = urlParams.get('search') ?? '';

            // Isi judul todo yang akan dihapus
            document.getElementById("deleteTodoTitle").innerText = title;
            // Set link tombol "Ya, Tetap Hapus"
            document.getElementById("btnDeleteTodo").setAttribute("href", `?page=delete&id=${id}&filter=${filter}&search=${search}`);
            
            // Tampilkan modal
            var myModal = new bootstrap.Modal(document.getElementById("deleteTodo"));
            myModal.show();
        }
    </script>

    <script>
        // Inisialisasi SortableJS hanya jika elemen list-nya ada
        var el = document.getElementById('sortable-list');
        if (el) {
            var sortable = Sortable.create(el, {
                animation: 150, // Animasi saat di-drag
                handle: '.drag-handle', // Tentukan elemen mana yang bisa di-drag
                
                // Fungsi yang dijalankan setelah drag-and-drop selesai
                onEnd: function (evt) {
                    var itemIds = [];
                    // Ambil semua item di list setelah diurutkan
                    var items = el.getElementsByClassName('list-group-item'); 
                    
                    // Kumpulkan semua 'data-id' ke dalam array 'itemIds'
                    for (var i = 0; i < items.length; i++) {
                        var id = items[i].getAttribute('data-id');
                        if (id) {
                            itemIds.push(id);
                        }
                    }
                    
                    // Kirim urutan baru ke server via AJAX (Fetch API)
                    fetch('?page=saveSortOrder', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ order: itemIds })
                    })
                    .then(response => response.json())
                    .then(data => {
                        // Tampilkan error jika server gagal menyimpan
                        if (!data.success) {
                            console.error('Gagal menyimpan urutan:', data.message);
                            alert('Gagal menyimpan urutan. Silakan refresh halaman.');
                        }
                    })
                    .catch(error => {
                        // Tampilkan error jika AJAX-nya gagal
                        console.error('Error AJAX:', error);
                        alert('Terjadi error. Silakan refresh halaman.');
                    });
                }
            });
        }
    </script>

    <script>
    // Jalankan skrip setelah DOM selesai dimuat
    document.addEventListener('DOMContentLoaded', function () {
        
        // 1. Inisialisasi semua Tooltip
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

        // 2. Tampilkan Toast Sukses (jika ada) - INI YANG HILANG
        const successToastEl = document.getElementById('successToast');
        if (successToastEl) {
            const successToast = new bootstrap.Toast(successToastEl, { delay: 3000 }); // Hilang setelah 3 detik
            successToast.show();
        }

        // 3. Tampilkan Toast Error (jika ada) - INI YANG HILANG
        const errorToastEl = document.getElementById('errorToast');
        if (errorToastEl) {
            const errorToast = new bootstrap.Toast(errorToastEl, { delay: 5000 }); // Hilang setelah 5 detik
            errorToast.show();
        }

        /* * =============================================
         * ==      BLOK ANIMASI BARU (TAMBAHKAN INI)  ==
         * =============================================
         */
        <?php if (isset($_SESSION['highlight_id'])): ?>
            
            // Ambil ID yang disimpan dari PHP Session
            const highlightId = '<?= (int)$_SESSION['highlight_id'] ?>';
            
            // Cari elemen .list-group-item yang sesuai dengan ID tersebut
            const highlightedElement = document.querySelector(`.list-group-item[data-id="${highlightId}"]`);
            
            if (highlightedElement) {
                // 1. Gulir halaman agar elemen terlihat (di tengah layar)
                highlightedElement.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'center' 
                });
                
                // 2. Tambahkan kelas CSS untuk memicu animasi
                highlightedElement.classList.add('flash-in-item');
            }
            
            // 3. Hapus session agar tidak berulang saat di-refresh lagi
            <?php unset($_SESSION['highlight_id']); ?>
            
        <?php endif; ?>
        /* =============================================
         * ==      AKHIR BLOK ANIMASI BARU          ==
         * =============================================
         */

    });
</script>

</body>
</html>