<?php
// ==========================================================
// cari_peminjaman.php
// Dipanggil via AJAX oleh scan_pengembalian.php setiap kali
// QR kartu anggota berhasil discan.
// Menerima POST 'id_anggota' -> ISINYA ADALAH NIS/NIP SISWA
// (karena QR kartu anggota di-generate dari NIS, bukan dari
// id_anggota / primary key)
// ==========================================================
session_start();
include "../koneksi.php"; // sesuaikan path sesuai lokasi file ini

header('Content-Type: application/json');

// TODO: sesuaikan dengan pengecekan login petugas/admin kamu jika perlu

$nis = mysqli_real_escape_string($koneksi, trim($_POST['id_anggota'] ?? ''));

if ($nis === '') {
    echo json_encode(['status' => 'error', 'pesan' => 'QR tidak terbaca / data kosong.']);
    exit;
}

// --- 1. Cari data anggota berdasarkan NIS ---
// TODO: sesuaikan nama tabel & kolom kalau di database kamu beda
// (misal: tabel 'anggota' punya kolom id_anggota, nama, nis)
$q_anggota = mysqli_query($koneksi, "SELECT * FROM anggota WHERE nis = '$nis'");

if (!$q_anggota || mysqli_num_rows($q_anggota) == 0) {
    echo json_encode(['status' => 'error', 'pesan' => 'Data anggota dengan NIS tersebut tidak ditemukan.']);
    exit;
}

$anggota    = mysqli_fetch_assoc($q_anggota);
$id_anggota = $anggota['id_anggota']; // primary key anggota, dipakai buat query transaksi

// --- 2. Ambil daftar buku yang sedang dipinjam anggota ini ---
// TODO: sesuaikan nama tabel/kolom transaksi & buku kalau beda
// (transaksi: id_transaksi, id_anggota, id_buku, tgl_kembali, status)
// (buku: id_buku, judul)
$q_buku = mysqli_query($koneksi, "
    SELECT t.id_transaksi, t.tgl_kembali, b.judul
    FROM transaksi t
    JOIN buku b ON b.id_buku = t.id_buku
    WHERE t.id_anggota = '$id_anggota' AND t.status = 'dipinjam'
    ORDER BY t.tgl_kembali ASC
");

$daftar_buku = [];
$hari_ini = date('Y-m-d');

if ($q_buku) {
    while ($row = mysqli_fetch_assoc($q_buku)) {
        $telat      = $hari_ini > $row['tgl_kembali'];
        $hari_telat = $telat ? (int) floor((strtotime($hari_ini) - strtotime($row['tgl_kembali'])) / 86400) : 0;

        $daftar_buku[] = [
            'id_transaksi' => $row['id_transaksi'],
            'judul'        => $row['judul'],
            'tgl_kembali'  => date('d-m-Y', strtotime($row['tgl_kembali'])),
            'telat'        => $telat,
            'hari_telat'   => $hari_telat,
        ];
    }
}

echo json_encode([
    'status' => 'sukses',
    'nama'   => $anggota['nama'],
    'nis'    => $anggota['nis'],
    'buku'   => $daftar_buku,
]);