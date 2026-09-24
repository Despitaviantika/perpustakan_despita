<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['id_anggota'])) {
    header("Location: ../login_user.php");
    exit;
}

$id_anggota = $_SESSION['id_anggota'];
$id_transaksi = $_GET['id'] ?? '';

if (!empty($id_transaksi)) {
    // Pastikan transaksi ini benar milik user yang login dan statusnya masih 'Dipinjam'
    $cek = mysqli_query($koneksi, "
        SELECT * FROM transaksi 
        WHERE id_transaksi = '$id_transaksi' 
        AND id_anggota = '$id_anggota' 
        AND status = 'Dipinjam'
    ");

    if (mysqli_num_rows($cek) > 0) {
        // Ubah status menjadi Dibatalkan
        $update = mysqli_query($koneksi, "
            UPDATE transaksi 
            SET status = 'Dibatalkan' 
            WHERE id_transaksi = '$id_transaksi'
        ");

        if ($update) {
            echo "<script>alert('Peminjaman berhasil dibatalkan!'); window.location='riwayat.php';</script>";
        } else {
            echo "<script>alert('Gagal membatalkan peminjaman!'); window.location='riwayat.php';</script>";
        }
    } else {
        header("Location: riwayat.php");
    }
} else {
    header("Location: riwayat.php");
}
?>