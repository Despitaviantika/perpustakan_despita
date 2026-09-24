<?php
// ==========================================================
// proses_pengembalian.php
// Dipanggil via AJAX ketika petugas klik tombol "Kembalikan"
// pada salah satu buku di daftar peminjaman anggota
// ==========================================================
session_start();
include "../koneksi.php"; // sesuaikan path sesuai lokasi file ini

// TODO: sesuaikan dengan pengecekan login petugas/admin kamu
header('Content-Type: application/json');

// Ganti sesuai kebijakan perpustakaan kamu (denda per hari keterlambatan)
const DENDA_PER_HARI = 1000;

$id_transaksi = intval($_POST['id_transaksi'] ?? 0);

if ($id_transaksi <= 0) {
    echo json_encode(['status' => 'error', 'pesan' => 'Data transaksi tidak valid.']);
    exit;
}

// Ambil data transaksi dulu
$q_trx = mysqli_query($koneksi, "SELECT * FROM transaksi WHERE id_transaksi='$id_transaksi' AND status='dipinjam'");
if (mysqli_num_rows($q_trx) == 0) {
    echo json_encode(['status' => 'error', 'pesan' => 'Transaksi tidak ditemukan atau sudah dikembalikan.']);
    exit;
}
$trx = mysqli_fetch_assoc($q_trx);
$id_buku = $trx['id_buku'];

// Hitung denda kalau lewat batas waktu (tgl_kembali)
$hari_ini = date('Y-m-d');
$denda = 0;
if ($hari_ini > $trx['tgl_kembali']) {
    $hari_telat = (strtotime($hari_ini) - strtotime($trx['tgl_kembali'])) / 86400;
    $denda = (int)$hari_telat * DENDA_PER_HARI;
}

// Update transaksi: status jadi 'kembali', catat tanggal dikembalikan + denda
$update_trx = mysqli_query($koneksi, "
    UPDATE transaksi
    SET status='kembali', tgl_dikembalikan='$hari_ini', denda='$denda'
    WHERE id_transaksi='$id_transaksi'
");

// Tambah stok buku +1
$update_stok = mysqli_query($koneksi, "UPDATE buku SET stok = stok + 1 WHERE id_buku='$id_buku'");

if ($update_trx && $update_stok) {
    $pesan = $denda > 0
        ? "Buku berhasil dikembalikan. Ada denda keterlambatan: Rp" . number_format($denda, 0, ',', '.')
        : "Buku berhasil dikembalikan tepat waktu.";
    echo json_encode(['status' => 'sukses', 'pesan' => $pesan, 'denda' => $denda]);
} else {
    echo json_encode(['status' => 'error', 'pesan' => 'Gagal memproses pengembalian.']);
}