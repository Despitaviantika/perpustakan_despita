<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit;
}

$id = mysqli_real_escape_string($koneksi, $_GET['id']);

// Cek apakah anggota masih memiliki transaksi
$cek = mysqli_query($koneksi, "SELECT * FROM transaksi WHERE id_anggota='$id'");

if (mysqli_num_rows($cek) > 0) {
    $notif_tipe  = 'error';
    $notif_judul = 'Tidak Bisa Dihapus';
    $notif_pesan = 'Anggota ini masih memiliki riwayat transaksi, jadi tidak dapat dihapus. Hapus atau pindahkan dulu transaksinya sebelum menghapus data anggota.';
} else {
    mysqli_query($koneksi, "DELETE FROM anggota WHERE id_anggota='$id'");

    $notif_tipe  = 'success';
    $notif_judul = 'Anggota Dihapus';
    $notif_pesan = 'Data anggota berhasil dihapus dari sistem.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hapus Anggota - Perpustakaan</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background: #f4f7f6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
    </style>
</head>
<body class="bg-light">

<script>
    Swal.fire({
        icon: '<?= $notif_tipe; ?>',
        title: '<?= addslashes($notif_judul); ?>',
        text: '<?= addslashes($notif_pesan); ?>',
        confirmButtonText: '<?= $notif_tipe == 'success' ? 'Oke' : 'Mengerti'; ?>',
        confirmButtonColor: '<?= $notif_tipe == 'success' ? '#198754' : '#d33'; ?>',
        customClass: { popup: 'rounded-4' }
    }).then(() => {
        window.location.href = 'anggota.php';
    });
</script>

</body>
</html>