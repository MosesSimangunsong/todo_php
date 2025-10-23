<!DOCTYPE html>
<html>
<head>
    <title>Detail Todo - <?= htmlspecialchars($todo['title']) ?></title>
    <link href="/assets/vendor/bootstrap-5.3.8-dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>
<div class="container p-5">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h1 class="h4">Detail Todo</h1>
            <a href="index.php" class="btn btn-secondary">Kembali ke Daftar</a>
        </div>
        <div class="card-body">
            <h2 class="card-title"><?= htmlspecialchars($todo['title']) ?></h2>
            
            <p>
                <strong>Status:</strong>
                <?php if ($todo['is_finished']): ?>
                    <span class="badge bg-success">Selesai</span>
                <?php else: ?>
                    <span class="badge bg-danger">Belum Selesai</span>
                <?php endif; ?>
            </p>

            <p><strong>Deskripsi:</strong></p>
            <div class="p-3 bg-light border rounded">
                <?= nl2br(htmlspecialchars($todo['description'] ?? 'Tidak ada deskripsi.')) ?>
            </div>
            
            <hr>
            
            <p class="text-muted small">
                Dibuat pada: <?= date('d F Y - H:i', strtotime($todo['created_at'])) ?>
                <br>
                Terakhir diperbarui: <?= date('d F Y - H:i', strtotime($todo['updated_at'])) ?>
            </p>

        </div>
    </div>
</div>
<script src="/assets/vendor/bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>