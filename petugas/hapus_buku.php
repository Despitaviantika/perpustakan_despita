<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit;
}

$id = mysqli_real_escape_string($koneksi, $_GET['id']);

// Catatan: riwayat transaksi TIDAK dihapus lagi di sini.
// Judul & pengarang buku sudah tersalin ke tabel transaksi saat peminjaman dibuat,
// jadi riwayat tetap utuh meskipun baris buku ini dihapus.

// Hapus buku
mysqli_query($koneksi, "DELETE FROM buku WHERE id_buku='$id'");

echo "<!DOCTYPE html><html><head><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head><body>
<script>
    Swal.fire({
        title: 'Berhasil!',
        text: 'Data buku berhasil dihapus.',
        icon: 'success',
        timer: 2000,
        showConfirmButton: false
    }).then(() => {
        window.location = 'buku.php';
    });
</script>
</body></html>";
?>