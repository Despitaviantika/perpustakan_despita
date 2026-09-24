<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit;
}

// Pencarian Buku
if (isset($_GET['cari']) && $_GET['cari'] != "") {
    $cari = $_GET['cari'];
    $data = mysqli_query($koneksi, "SELECT * FROM buku WHERE judul LIKE '%$cari%' OR pengarang LIKE '%$cari%'");
} else {
    $data = mysqli_query($koneksi, "SELECT * FROM buku ORDER BY id_buku DESC");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Katalog Buku - Perpustakaan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>📚 Daftar Buku Perpustakaan</h2>
        <a href="index.php" class="btn btn-secondary">← Kembali</a>
    </div>

    <form method="GET" class="mb-4">
        <div class="input-group">
            <input type="text" name="cari" class="form-control" placeholder="Cari Judul atau Pengarang..." value="<?= isset($_GET['cari']) ? $_GET['cari'] : ''; ?>">
            <button type="submit" class="btn btn-success">Cari</button>
            <a href="buku.php" class="btn btn-secondary">Refresh</a>
        </div>
    </form>

    <div class="row">
        <?php 
        if (mysqli_num_rows($data) > 0) {
            while ($d = mysqli_fetch_assoc($data)) { 
                $nama_file = !empty($d['gambar']) ? $d['gambar'] : 'default.jpg';
                
                // Deteksi otomatis lokasi folder gambar
                if (file_exists("../gambar/" . $nama_file)) {
                    $src = "../gambar/" . $nama_file;
                } else if (file_exists("gambar/" . $nama_file)) {
                    $src = "gambar/" . $nama_file;
                } else {
                    $src = "";
                }
        ?>
            <div class="col-md-3 mb-4">
                <div class="card h-100 shadow-sm border-0">
                    
                    <div class="text-center pt-3 bg-white">
                        <?php if ($src != "") { ?>
                            <img src="<?= $src; ?>" style="height: 180px; width: 130px; object-fit: cover;" class="rounded shadow-sm">
                        <?php } else { ?>
                            <div class="bg-secondary text-white d-flex align-items-center justify-content-center mx-auto rounded shadow-sm" style="height: 180px; width: 130px;">
                                <small class="text-center">Gambar tidak<br>ditemukan<br>(<?= $nama_file; ?>)</small>
                            </div>
                        <?php } ?>
                    </div>

                    <div class="card-body d-flex flex-column">
                        <h6 class="card-title fw-bold text-dark text-truncate"><?= $d['judul']; ?></h6>
                        <p class="card-text text-muted small mb-1">Pengarang: <?= $d['pengarang']; ?></p>
                        <p class="card-text text-muted small mb-2">Penerbit: <?= $d['penerbit']; ?></p>
                        
                        <div class="mt-auto d-flex justify-content-between align-items-center">
                            <span class="badge bg-success">Stok: <?= $d['stok']; ?></span>
                            <a href="detail_buku.php?id=<?= $d['id_buku']; ?>" class="btn btn-primary btn-sm">Detail</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php 
            } 
        } else { 
        ?>
            <div class="col-12 text-center text-muted py-5">
                <h5>Buku tidak ditemukan!</h5>
            </div>
        <?php } ?>
    </div>
</div>

</body>
</html>