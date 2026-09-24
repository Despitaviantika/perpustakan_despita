<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit;
}

$anggota = mysqli_query($koneksi, "SELECT * FROM anggota");
$buku = mysqli_query($koneksi, "SELECT * FROM buku");

if (isset($_POST['simpan'])) {

    $id_anggota = $_POST['id_anggota'];
    $id_buku = $_POST['id_buku'];
    $tanggal_pinjam = $_POST['tanggal_pinjam'];
    $tanggal_kembali = $_POST['tanggal_kembali'];

    // Cek stok buku
    $cek = mysqli_query($koneksi, "SELECT stok FROM buku WHERE id_buku='$id_buku'");
    $data_buku = mysqli_fetch_assoc($cek);

    if ($data_buku['stok'] > 0) {

        // Simpan transaksi
        $insert = mysqli_query($koneksi, "INSERT INTO transaksi
        (id_anggota, id_buku, tanggal_pinjam, tanggal_kembali, status)
        VALUES
        ('$id_anggota','$id_buku','$tanggal_pinjam','$tanggal_kembali','Dipinjam')");

        if (!$insert) {
            die("Error INSERT: " . mysqli_error($koneksi));
        }

        // Kurangi stok buku
        $update = mysqli_query($koneksi, "UPDATE buku
        SET stok = stok - 1
        WHERE id_buku='$id_buku'");

        if (!$update) {
            die("Error UPDATE: " . mysqli_error($koneksi));
        }

        echo "<script>
        alert('Transaksi berhasil ditambahkan');
        window.location='transaksi.php';
        </script>";

    } else {

        echo "<script>
        alert('Stok buku habis!');
        window.location='tambah_transaksi.php';
        </script>";

    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Transaksi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

<div class="container mt-4">

    <h2>Tambah Transaksi</h2>

    <form method="POST">

        <div class="mb-3">
            <label>Anggota</label>
            <select name="id_anggota" class="form-control" required>
                <option value="">-- Pilih Anggota --</option>

                <?php while($a = mysqli_fetch_assoc($anggota)){ ?>

                <option value="<?= $a['id_anggota']; ?>">
                    <?= $a['nama']; ?>
                </option>

                <?php } ?>

            </select>
        </div>

        <div class="mb-3">
            <label>Buku</label>
            <select name="id_buku" class="form-control" required>
                <option value="">-- Pilih Buku --</option>

                <?php while($b = mysqli_fetch_assoc($buku)){ ?>

                <option value="<?= $b['id_buku']; ?>">
                    <?= $b['judul']; ?> (Stok: <?= $b['stok']; ?>)
                </option>

                <?php } ?>

            </select>
        </div>

        <div class="mb-3">
            <label>Tanggal Pinjam</label>
            <input type="date" name="tanggal_pinjam" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Tanggal Kembali</label>
            <input type="date" name="tanggal_kembali" class="form-control" required>
        </div>

        <button type="submit" name="simpan" class="btn btn-primary">
            Simpan
        </button>

        <a href="transaksi.php" class="btn btn-secondary">
            Kembali
        </a>

    </form>

</div>

</body>
</html>