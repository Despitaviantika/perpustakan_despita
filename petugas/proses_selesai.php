<?php
// 1. Hubungkan ke database
include '../koneksi.php'; // Sesuaikan lokasi file koneksi database-mu

// 2. Tangkap ID dari URL
if (isset($_GET['id'])) {
    $id_transaksi = $_GET['id'];

    // 3. Update status transaksi menjadi 'Dikembalikan'
    $query = mysqli_query($koneksi, "UPDATE transaksi SET status = 'Dikembalikan' WHERE id_transaksi = '$id_transaksi'");

    if ($query) {
        // Jika berhasil, balikkan ke halaman data transaksi
        echo "<script>
                alert('Status transaksi berhasil diperbarui!');
                window.location.href='transaksi.php';
              </script>";
    } else {
        // Jika gagal
        echo "<script>
                alert('Gagal memperbarui status!');
                window.location.href='transaksi.php';
              </script>";
    }
} else {
    header("Location: transaksi.php");
}
?>