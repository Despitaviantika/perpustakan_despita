<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: transaksi.php");
    exit;
}

$id_transaksi = $_GET['id'];

// Ambil data detail transaksi
$query = mysqli_query($koneksi, "
    SELECT transaksi.*, anggota.nama, buku.judul 
    FROM transaksi 
    JOIN anggota ON transaksi.id_anggota = anggota.id_anggota
    JOIN buku ON transaksi.id_buku = buku.id_buku
    WHERE id_transaksi = '$id_transaksi'
");
$d = mysqli_fetch_assoc($query);

$tgl_pinjam = $d['tgl_pinjam'] ?? $d['tanggal_pinjam'] ?? $d['tanggal_peminjaman'] ?? '-';
$tgl_kembali = $d['tgl_kembali'] ?? $d['tanggal_kembali'] ?? $d['tgl_pengembalian'] ?? '-';

// Simpan data saat tombol diklik
if (isset($_POST['proses'])) {
    $status = $_POST['status'];
    
    $cek_kolom = mysqli_query($koneksi, "SHOW COLUMNS FROM transaksi LIKE 'denda'");
    if (mysqli_num_rows($cek_kolom) > 0) {
        $denda = $_POST['denda'];
        $update = mysqli_query($koneksi, "
            UPDATE transaksi 
            SET status = '$status', denda = '$denda' 
            WHERE id_transaksi = '$id_transaksi'
        ");
    } else {
        $update = mysqli_query($koneksi, "
            UPDATE transaksi 
            SET status = '$status' 
            WHERE id_transaksi = '$id_transaksi'
        ");
    }

    if ($update) {
        echo "<script>
                alert('Pengembalian berhasil diproses!');
                window.location.href='transaksi.php';
              </script>";
    } else {
        echo "<script>alert('Gagal memproses data!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Proses Pengembalian Buku</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f4f7f6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        /* Header Card Gradasi Biru */
        .bg-gradasi {
            background: linear-gradient(135deg, #0d3b66 0%, #0077b6 100%) !important;
            color: #ffffff !important;
        }
        /* Tombol Gradasi Biru Utama */
        .btn-gradasi {
            background: linear-gradient(135deg, #0077b6 0%, #00b4d8 100%);
            color: #ffffff;
            border: none;
        }
        .btn-gradasi:hover {
            background: linear-gradient(135deg, #023e8a 0%, #0077b6 100%);
            color: #ffffff;
        }
        .card {
            border: none;
            border-radius: 15px;
        }
    </style>
</head>
<body>

<div class="container mt-5" style="max-width: 500px;">
    <div class="card shadow-sm">
        <div class="card-header bg-gradasi py-3">
            <h5 class="mb-0 fw-bold">Form Pengembalian Buku</h5>
        </div>
        <div class="card-body p-4">
            <table class="table table-borderless small mb-3">
                <tr>
                    <td width="35%"><strong>Nama Peminjam</strong></td>
                    <td>: <?= htmlspecialchars($d['nama'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <td><strong>Judul Buku</strong></td>
                    <td>: <?= htmlspecialchars($d['judul'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <td><strong>Tgl Pinjam / Kembali</strong></td>
                    <td>: <?= htmlspecialchars($tgl_pinjam); ?> s/d <?= htmlspecialchars($tgl_kembali); ?></td>
                </tr>
            </table>

            <hr>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label fw-bold">Kondisi / Status Pengembalian</label>
                    <select name="status" class="form-select" required>
                        <option value="Dikembalikan">Dikembalikan (Baik)</option>
                        <option value="Rusak">Rusak</option>
                        <option value="Hilang">Hilang</option>
                    </select>
                </div>

                <?php 
                $cek_kolom = mysqli_query($koneksi, "SHOW COLUMNS FROM transaksi LIKE 'denda'");
                if (mysqli_num_rows($cek_kolom) > 0) { 
                ?>
                <div class="mb-3">
                    <label class="form-label fw-bold">Jumlah Denda (Rp)</label>
                    <input type="number" name="denda" class="form-control" value="0" placeholder="Masukkan nominal denda jika ada" required>
                </div>
                <?php } ?>

                <div class="d-flex justify-content-between mt-4">
                    <a href="transaksi.php" class="btn btn-secondary px-4">Batal</a>
                    <button type="submit" name="proses" class="btn btn-gradasi px-4 fw-semibold">Simpan Pengembalian</button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>