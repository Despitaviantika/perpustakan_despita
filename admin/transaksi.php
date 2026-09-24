<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit;
}

// Ambil semua data transaksi
$transaksi = mysqli_query($koneksi, "
    SELECT transaksi.*, anggota.nama, buku.judul 
    FROM transaksi 
    JOIN anggota ON transaksi.id_anggota = anggota.id_anggota
    JOIN buku ON transaksi.id_buku = buku.id_buku
    ORDER BY id_transaksi DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Transaksi - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background: #f4f7f6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Navbar disamakan dengan tema utama web (biru gradasi) */
        .navbar-kelola {
            background: linear-gradient(135deg, #0d3b66 0%, #0077b6 100%) !important;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }

        .card { border: none; border-radius: 16px; }

        .card-header-soft {
            background: linear-gradient(135deg, #f0f7ff 0%, #e1effe 100%) !important;
            border-bottom: 1px solid #e5eef7 !important;
        }

        .table-header-gradasi {
            background: linear-gradient(135deg, #0d3b66 0%, #0077b6 100%) !important;
            color: #fff !important;
        }
        .table-header-gradasi th {
            color: #fff !important;
            border-color: rgba(255,255,255,0.15) !important;
        }

        .table-hover tbody tr:hover {
            background-color: #f0f7ff;
        }

        .btn-gradasi {
            background: linear-gradient(135deg, #0077b6 0%, #00b4d8 100%);
            color: #fff;
            border: none;
        }
        .btn-gradasi:hover {
            background: linear-gradient(135deg, #023e8a 0%, #0077b6 100%);
            color: #fff;
        }

        .badge-dipinjam {
            background: #f7b733;
            color: #4a3300;
        }
        .badge-dikembalikan {
            background: linear-gradient(135deg, #2a9d8f, #52b788);
            color: #fff;
        }

        /* Style khusus saat mencetak (tombol Cetak / Ctrl+P) */
        @media print {
            .navbar-kelola, .btn-cetak, .no-print {
                display: none !important;
            }
            body {
                background: #fff !important;
            }
            .card {
                box-shadow: none !important;
                border-radius: 0 !important;
            }
            .table-header-gradasi, .table-header-gradasi th {
                background: #e9ecef !important;
                color: #000 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-dark navbar-kelola px-4 mb-4">
    <a href="index.php" class="navbar-brand"><i class="bi bi-arrow-left"></i> Kembali ke Dashboard</a>
    <span class="text-white fw-semibold"><i class="bi bi-arrow-left-right me-1"></i> Manajemen Transaksi</span>
</nav>

<div class="container mb-5">
    <div class="card shadow-sm">
        <div class="card-header card-header-soft fw-bold py-3 d-flex justify-content-between align-items-center">
            <span><i class="bi bi-clock-history me-1 text-primary"></i> Riwayat & Status Transaksi Peminjaman Buku</span>
            <button type="button" class="btn btn-sm btn-gradasi btn-cetak" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Cetak Transaksi
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-header-gradasi">
                        <tr>
                            <th>No</th>
                            <th>Nama Peminjam</th>
                            <th>Judul Buku</th>
                            <th>Tgl Pinjam</th>
                            <th>Tgl Kembali</th>
                            <th>Status</th>
                            <th class="no-print">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if (mysqli_num_rows($transaksi) > 0) {
                            $no = 1;
                            while ($row = mysqli_fetch_assoc($transaksi)) {
                                $tgl_pinjam = $row['tgl_pinjam'] ?? $row['tanggal_pinjam'] ?? '-';
                                $tgl_kembali = $row['tgl_kembali'] ?? $row['tanggal_kembali'] ?? '-';
                        ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td><?= htmlspecialchars($row['nama']); ?></td>
                            <td><?= htmlspecialchars($row['judul']); ?></td>
                            <td><?= htmlspecialchars($tgl_pinjam); ?></td>
                            <td><?= htmlspecialchars($tgl_kembali); ?></td>
                            <td>
                                <?php if ($row['status'] == 'Dipinjam') { ?>
                                    <span class="badge badge-dipinjam">Dipinjam</span>
                                <?php } else { ?>
                                    <span class="badge badge-dikembalikan">Dikembalikan</span>
                                <?php } ?>
                            </td>
                            <td class="no-print">
                                <?php if ($row['status'] == 'Dipinjam') { ?>
                                    <a href="kembali.php?id=<?= $row['id_transaksi']; ?>" class="btn btn-sm btn-gradasi">Proses Kembali</a>
                                <?php } else { ?>
                                    <span class="text-muted small">Selesai</span>
                                <?php } ?>
                            </td>
                        </tr>
                        <?php 
                            }
                        } else { 
                        ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-3">Belum ada data transaksi.</td>
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