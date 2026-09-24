<?php
session_start();
include "../koneksi.php";

// Cek apakah sudah login dan rolenya benar-benar petugas
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'petugas') {
    header("Location: ../login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Transaksi Peminjaman - Petugas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            /* Disamakan dengan tema utama web (biru gradasi) */
            background: linear-gradient(135deg, #0d3b66 0%, #0077b6 60%, #00b4d8 100%);
            min-height: 100vh;
            color: #333;
        }
        .main-container {
            background: rgba(255, 255, 255, 0.97);
            border-radius: 18px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            padding: 30px;
            margin-top: 40px;
            margin-bottom: 40px;
        }
        .table thead {
            background: linear-gradient(135deg, #0d3b66 0%, #0077b6 100%);
            color: white;
        }
        .table-hover tbody tr:hover {
            background-color: #f0f7ff;
        }
        .ket-denda {
            font-size: 0.8rem;
            display: block;
            margin-top: 3px;
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
        .btn-cetak {
            background: linear-gradient(135deg, #2a9d8f 0%, #52b788 100%);
            color: #fff;
            border: none;
        }
        .btn-cetak:hover {
            background: linear-gradient(135deg, #21867a 0%, #429e6f 100%);
            color: #fff;
        }
        /* Sembunyikan tombol saat dicetak */
        @media print {
            body {
                background: white;
            }
            .main-container {
                box-shadow: none;
                margin: 0;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="container main-container">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h2 class="fw-bold mb-1 text-dark"><i class="bi bi-arrow-left-right me-2 text-primary"></i>Data Transaksi Peminjaman Buku</h2>
                <p class="text-muted mb-0">Kelola status peminjaman, pengembalian buku, dan denda anggota di sini.</p>
            </div>
            <div class="no-print">
                <button onclick="window.print()" class="btn btn-cetak fw-bold px-4 py-2"><i class="bi bi-printer-fill me-1"></i> Cetak Laporan</button>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead>
                    <tr>
                        <th class="text-center">No</th>
                        <th>Nama Siswa</th>
                        <th>Judul Buku</th>
                        <th>Tanggal Pinjam</th>
                        <th>Tanggal Harus Kembali</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Denda &amp; Keterangan</th>
                        <th class="text-center no-print">Aksi / Kelola</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    // LEFT JOIN ke buku: kalau bukunya sudah dihapus, baris transaksi tetap muncul
                    $sql = mysqli_query($koneksi, "
                        SELECT transaksi.*, anggota.nama, buku.judul AS judul_dari_tabel_buku 
                        FROM transaksi 
                        JOIN anggota ON transaksi.id_anggota = anggota.id_anggota 
                        LEFT JOIN buku ON transaksi.id_buku = buku.id_buku
                        ORDER BY transaksi.id_transaksi DESC
                    ");

                    // Tarif denda per hari keterlambatan
                    // PENTING: samakan angka ini dengan tarifPerHari di edit_transaksi.php biar hitungannya konsisten
                    $tarif_per_hari = 1000;

                    if (mysqli_num_rows($sql) > 0) {
                        while ($row = mysqli_fetch_assoc($sql)) {
                            $status_val = !empty($row['status']) ? $row['status'] : 'dipinjam';
                            $status_lower = strtolower($status_val);

                            // Judul buku: utamakan salinan yang tersimpan di tabel transaksi,
                            // fallback ke tabel buku (untuk transaksi lama), lalu fallback terakhir teks default
                            if (!empty($row['judul_buku'])) {
                                $judul_tampil = $row['judul_buku'];
                            } elseif (!empty($row['judul_dari_tabel_buku'])) {
                                $judul_tampil = $row['judul_dari_tabel_buku'];
                            } else {
                                $judul_tampil = 'Buku (sudah dihapus)';
                            }

                            if ($status_lower == 'kembali' || $status_lower == 'dikembalikan') {
                                $badge_color = 'bg-success';
                                $badge_style = '';
                            } elseif ($status_lower == 'dibatalkan') {
                                $badge_color = 'text-dark';
                                $badge_style = 'background: #f4c95d;';
                            } else {
                                $badge_color = 'text-dark';
                                $badge_style = 'background: #f7b733;';
                            }

                            $denda = (int) $row['denda'];

                            // ==== Logika keterangan denda, sekarang berdasarkan data ASLI (bukan tebakan) ====
                            $kondisi_buku = $row['kondisi_buku'] ?? 'Normal'; // Normal / Rusak / Hilang, tersimpan dari edit_transaksi.php

                            $sebab = []; // menampung semua alasan (bisa lebih dari satu, misal terlambat + rusak)

                            if ($status_lower == 'dibatalkan') {
                                $keterangan = "Transaksi dibatalkan";
                            } else {
                                // 1) Alasan dari kondisi fisik buku
                                if ($kondisi_buku == 'Rusak') {
                                    $sebab[] = "Buku rusak (+Rp 25.000)";
                                } elseif ($kondisi_buku == 'Hilang') {
                                    $sebab[] = "Buku hilang (+Rp 75.000)";
                                }

                                // 2) Alasan dari keterlambatan, dihitung dari tanggal asli (bukan estimasi)
                                if (!empty($row['tgl_dikembalikan'])) {
                                    $tgl_batas          = new DateTime($row['tgl_kembali']);
                                    $tgl_kembali_aktual  = new DateTime($row['tgl_dikembalikan']);
                                    if ($tgl_kembali_aktual > $tgl_batas) {
                                        $selisih = $tgl_batas->diff($tgl_kembali_aktual)->days;
                                        $sebab[] = "Terlambat $selisih hari (Rp " . number_format($tarif_per_hari, 0, ',', '.') . "/hari)";
                                    }
                                }

                                if (!empty($sebab)) {
                                    $keterangan = implode(" & ", $sebab);
                                } elseif ($status_lower == 'kembali' || $status_lower == 'dikembalikan') {
                                    $keterangan = "Dikembalikan tepat waktu, kondisi baik";
                                } else {
                                    $keterangan = "Belum dikembalikan";
                                }
                            }
                            // ==== akhir logika keterangan ====
                            
                            echo "<tr>";
                            echo "<td class='text-center'>" . $no++ . "</td>";
                            echo "<td>" . htmlspecialchars($row['nama']) . "</td>";
                            echo "<td class='fw-semibold text-primary'>" . htmlspecialchars($judul_tampil) . "</td>";
                            echo "<td>" . htmlspecialchars($row['tgl_pinjam']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['tgl_kembali']) . "</td>";
                            echo "<td class='text-center'><span class='badge " . $badge_color . "' style='" . $badge_style . "'>" . htmlspecialchars($status_val) . "</span></td>";
                            echo "<td class='text-center'>";
                            echo "Rp " . number_format($denda, 0, ',', '.');
                            echo "<span class='ket-denda text-muted'>" . htmlspecialchars($keterangan) . "</span>";
                            echo "</td>";
                            echo "<td class='text-center no-print'>";
                            echo "<a href='edit_transaksi.php?id=" . $row['id_transaksi'] . "' class='btn btn-sm btn-gradasi fw-bold px-3'>Kelola / Edit</a>";
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='8' class='text-center py-4 text-muted'>Belum ada transaksi peminjaman.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="mt-4 no-print">
            <a href="index.php" class="btn btn-outline-secondary px-4 py-2"><i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard Petugas</a>
        </div>
    </div>
</body>
</html>