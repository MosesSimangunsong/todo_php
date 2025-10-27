<?php
/*
 * File: views/TodoDetailView.php
 *
 * Ini adalah file tampilan untuk halaman detail satu item todo.
 * Bertanggung jawab untuk me-render judul, status, deskripsi,
 * dan metadata (tanggal dibuat/diperbarui) dari todo yang dipilih.
 *
 * Variabel yang diharapkan dari Controller:
 * - $todo (array): Array asosiatif berisi data lengkap dari
 * satu item todo (id, title, description, is_finished, created_at, updated_at).
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>Detail Todo - <?= htmlspecialchars($todo['title']) ?></title>
    
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    <link href="/assets/vendor/bootstrap-5.3.8-dist/css/bootstrap.min.css" rel="stylesheet" />
    
    <link rel="stylesheet" href="/assets/css/custom.css">
</head>
<body>

    <div class="container p-5">
        <div class="card main-card"> <?php // Menggunakan .main-card untuk konsistensi ?>
            
            <div class="card-header d-flex justify-content-between align-items-center">
                <h1 class="h4 mb-0">Detail Todo</h1>
                <a href="index.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
                </a>
            </div>
            
            <div class="card-body">
                
                <h2 class="card-title h3"><?= htmlspecialchars($todo['title']) ?></h2>
                
                <p class="mb-3">
                    <strong>Status:</strong>
                    <?php if ($todo['is_finished']): ?>
                        <span class="badge bg-success">Selesai</span>
                    <?php else: ?>
                        <span class="badge bg-danger">Belum Selesai</span>
                    <?php endif; ?>
                </p>

                <p class="mb-1"><strong>Deskripsi:</strong></p>
                <div class="p-3 bg-light border rounded">
                    <?php // Gunakan nl2br untuk menghormati baris baru (enter) ?>
                    <?= nl2br(htmlspecialchars($todo['description'] ?? 'Tidak ada deskripsi.')) ?>
                </div>
                
                <hr>
                
                <p class="text-muted small mb-0">
                    <?php // Format tanggal agar lebih mudah dibaca ?>
                    Dibuat pada: <?= date('d F Y, H:i', strtotime($todo['created_at'])) ?>
                    <br>
                    Terakhir diperbarui: <?= date('d F Y, H:i', strtotime($todo['updated_at'])) ?>
                </p>

            </div> </div> </div> <script src="/assets/vendor/bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>