<?php
session_start();
include "../koneksi.php";

// Pengecekan session yang fleksibel untuk mencegah mental ke login.php
// Mendukung role 'anggota' atau 'user'
if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'anggota' && $_SESSION['role'] != 'user')) {
    header("Location: ../login.php");
    exit;
}

// Ambil ID anggota dari berbagai kemungkinan nama session login
$id_anggota = $_SESSION['id_anggota'] ?? $_SESSION['user_id'] ?? $_SESSION['id'] ?? '';

if (empty($id_anggota)) {
    echo "<script>alert('Sesi login anggota tidak ditemukan, silakan login ulang.'); window.location='../login.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat Peminjaman - Perpustakaan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .card { border: none; border-radius: 12px; }
        .card-gradient-blue { background: linear-gradient(135deg, #f0f7ff 0%, #e1effe 100%); }
        .table-header-gradient { background: linear-gradient(135deg, #0d6efd 0%, #0043a8 100%) !important; color: white; }
    </style>
</head>
<body class="bg-light">

<div class="container mt-5 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark"><i class="bi bi-clock-history"></i> Riwayat Peminjaman Buku</h2>
        <div>
            <a href="index.php" class="btn btn-secondary btn-sm fw-semibold px-3"><i class="bi bi-arrow-left"></i> Kembali ke Dashboard</a>
        </div>
    </div>

    <div class="card card-gradient-blue shadow-sm border-0 rounded-4 overflow-hidden border-start border-primary border-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 bg-transparent">
                    <thead class="table-header-gradient">
                        <tr>
                            <th class="py-3 px-3">No</th>
                            <th class="py-3">Judul Buku</th>
                            <th class="py-3">Tanggal Pinjam</th>
                            <th class="py-3">Tanggal Kembali</th>
                            <th class="py-3">Status</th>
                            <th class="py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        // Join tabel transaksi dengan buku untuk mengambil judul buku berdasarkan id_anggota yang sedang login
                        $query = mysqli_query($koneksi, "SELECT transaksi.*, buku.judul FROM transaksi 
                                                         JOIN buku ON transaksi.id_buku = buku.id_buku 
                                                         WHERE transaksi.id_anggota = '$id_anggota' 
                                                         ORDER BY transaksi.id_transaksi DESC") or die(mysqli_error($koneksi));

                        if (mysqli_num_rows($query) > 0) {
                            while ($row = mysqli_fetch_assoc($query)) {
                        ?>
                        <tr>
                            <td class="px-3 fw-semibold text-secondary"><?= $no++; ?></td>
                            <td class="fw-semibold text-dark"><?= htmlspecialchars($row['judul']); ?></td>
                            <td><?= htmlspecialchars($row['tgl_pinjam']); ?></td>
                            <td><?= htmlspecialchars($row['tgl_kembali']); ?></td>
                            <td>
                                <?php if ($row['status'] == 'dipinjam'): ?>
                                    <span class="badge bg-warning text-dark border shadow-sm">Dipinjam</span>
                                <?php elseif ($row['status'] == 'kembali'): ?>
                                    <span class="badge bg-success shadow-sm">Dikembalikan</span>
                                <?php else: ?>
                                    <span class="badge bg-danger shadow-sm">Dibatalkan</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($row['status'] == 'dipinjam'): ?>
                                    <a href="batalkan_pinjam.php?id=<?= $row['id_transaksi']; ?>" 
                                       class="btn btn-danger btn-sm px-2 py-1 shadow-sm" 
                                       onclick="return confirm('Apakah Anda yakin ingin membatalkan peminjaman buku ini?')">
                                        <i class="bi bi-x-circle"></i> Batalkan
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php 
                            } 
                        } else { 
                        ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">Belum ada riwayat peminjaman buku.</td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>