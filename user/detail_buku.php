<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: buku.php");
    exit;
}

$id_buku = $_GET['id'];
$query = mysqli_query($koneksi, "SELECT * FROM buku WHERE id_buku = '$id_buku'");
$d = mysqli_fetch_assoc($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Buku - <?= $d['judul']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5" style="max-width: 700px;">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Detail Informasi Buku</h5>
        </div>
        <div class="card-body">
            <div class="row align-items-center">
                
                <div class="col-md-5 text-center mb-3 mb-md-0">
                    <?php if (!empty($d['gambar']) && file_exists("../gambar/" . $d['gambar'])) { ?>
                        <img src="../gambar/<?= $d['gambar']; ?>" style="max-height: 250px; width: auto; object-fit: cover;" class="rounded shadow">
                    <?php } else { ?>
                        <div class="bg-secondary text-white d-flex align-items-center justify-content-center mx-auto rounded shadow" style="height: 250px; width: 170px;">
                            No Cover
                        </div>
                    <?php } ?>
                </div>

                <div class="col-md-7">
                    <h4 class="fw-bold"><?= $d['judul']; ?></h4>
                    <hr>
                    <p class="mb-2"><strong>Pengarang:</strong> <?= $d['pengarang']; ?></p>
                    <p class="mb-2"><strong>Penerbit:</strong> <?= $d['penerbit']; ?></p>
                    <p class="mb-2"><strong>Tahun Terbit:</strong> <?= $d['tahun_terbit'] ?? $d['tahun'] ?? '-'; ?></p>
                    <p class="mb-3"><strong>Sisa Stok:</strong> <span class="badge bg-success"><?= $d['stok']; ?> Buku</span></p>

                    <div class="d-flex gap-2">
                        <a href="buku.php" class="btn btn-secondary">← Kembali</a>
                        <?php if ($d['stok'] > 0) { ?>
                            <a href="pinjam.php?id=<?= $d['id_buku']; ?>" class="btn btn-primary">Pinjam Buku</a>
                        <?php } else { ?>
                            <button class="btn btn-danger" disabled>Stok Habis</button>
                        <?php } ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

</body>
</html>