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
    <title>Daftar Anggota - Perpustakaan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

        .card { border: none; border-radius: 16px; }
        .card-gradient-blue { background: linear-gradient(135deg, #f0f7ff 0%, #e1effe 100%); }

        /* Header tabel disamakan dengan tema utama web (biru gradasi) */
        .table-header-gradient {
            background: linear-gradient(135deg, #0d3b66 0%, #0077b6 100%) !important;
            color: #fff;
        }

        /* Header halaman bergaya sama dengan dashboard petugas/admin */
        .page-header-card {
            background: linear-gradient(135deg, #0d3b66 0%, #0077b6 60%, #00b4d8 100%);
            border-radius: 18px;
            color: #fff;
            position: relative;
            overflow: hidden;
        }
        .page-header-card::before {
            content: "";
            position: absolute;
            top: -40px;
            right: -40px;
            width: 160px;
            height: 160px;
            background: rgba(255,255,255,0.08);
            border-radius: 50%;
        }
        .page-header-card::after {
            content: "";
            position: absolute;
            bottom: -60px;
            right: 60px;
            width: 120px;
            height: 120px;
            background: rgba(255,255,255,0.06);
            border-radius: 50%;
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

        .table-hover tbody tr:hover {
            background-color: #f0f7ff;
        }

        .badge-nis {
            background: #eaf4fb;
            color: #0077b6;
            border: 1px solid #cfe6f7;
        }
    </style>
</head>
<body class="bg-light">

<div class="container mt-5 mb-5">
    <div class="card page-header-card shadow mb-4">
        <div class="card-body p-4 d-flex justify-content-between align-items-center flex-wrap gap-3 position-relative" style="z-index:1;">
            <div>
                <h2 class="fw-bold mb-1"><i class="bi bi-people-fill me-2"></i>Daftar Anggota Perpustakaan</h2>
                <p class="mb-0" style="opacity:.9;">Kelola data anggota perpustakaan yang terdaftar.</p>
            </div>
            <div>
                <a href="tambah_anggota.php" class="btn btn-light btn-sm fw-semibold px-3 text-primary"><i class="bi bi-plus-lg"></i> Tambah Anggota</a>
                <a href="index.php" class="btn btn-outline-light btn-sm fw-semibold px-3"><i class="bi bi-arrow-left"></i> Kembali</a>
            </div>
        </div>
    </div>

    <div class="card card-gradient-blue shadow-sm border-0 rounded-4 overflow-hidden border-start border-primary border-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 bg-transparent">
                    <thead class="table-header-gradient">
                        <tr>
                            <th class="py-3 px-3">No</th>
                            <th class="py-3">Nama Lengkap</th>
                            <th class="py-3">NIS / NIP</th>
                            <th class="py-3">Kelas</th>
                            <th class="py-3">No. Telepon</th>
                            <th class="py-3">Alamat</th>
                            <th class="py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        // Query dengan pengaman error
                        $query = mysqli_query($koneksi, "SELECT * FROM anggota ORDER BY id_anggota DESC") or die(mysqli_error($koneksi));
                        
                        if (mysqli_num_rows($query) > 0) {
                            while ($row = mysqli_fetch_assoc($query)) {
                                // Cek apakah data nama kosong atau berisi spasi kosong di database
                                $nama_tampil = !empty(trim($row['nama'])) ? $row['nama'] : '<span class="text-danger fst-italic">[Nama Kosong di DB]</span>';
                                $nis_tampil  = !empty(trim($row['nis_nip'])) ? $row['nis_nip'] : '-';
                        ?>
                        <tr>
                            <td class="px-3 fw-semibold text-secondary"><?= $no++; ?></td>
                            <td class="fw-semibold text-dark"><?= htmlspecialchars_decode($nama_tampil); ?></td>
                            <td>
                                <span class="badge badge-nis shadow-sm">
                                    <?= htmlspecialchars($nis_tampil); ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($row['kelas'] ?? '-'); ?></td>
                            <td><?= htmlspecialchars($row['telepon'] ?? '-'); ?></td>
                            <td><?= htmlspecialchars($row['alamat'] ?? '-'); ?></td>
                            <td class="text-center">
                                <?php $primary_id = $row['id_anggota'] ?? ''; ?>
                                <a href="edit_anggota.php?id=<?= $primary_id; ?>" class="btn btn-warning btn-sm text-white px-2 py-1 shadow-sm">
                                    <i class="bi bi-pencil-square"></i> Edit
                                </a>
                                <a href="hapus_anggota.php?id=<?= $primary_id; ?>"
                                   class="btn btn-danger btn-sm px-2 py-1 shadow-sm btn-hapus-anggota"
                                   data-nama="<?= htmlspecialchars(strip_tags($nama_tampil)); ?>">
                                    <i class="bi bi-trash"></i> Hapus
                                </a>
                            </td>
                        </tr>
                        <?php 
                            } 
                        } else { 
                        ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">Belum ada data anggota yang terdaftar.</td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    // Konfirmasi hapus anggota dengan SweetAlert2
    document.querySelectorAll('.btn-hapus-anggota').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const targetUrl = this.getAttribute('href');
            const nama = this.getAttribute('data-nama') || 'anggota ini';

            Swal.fire({
                icon: 'warning',
                title: 'Hapus Anggota?',
                text: 'Data "' + nama + '" akan dihapus secara permanen dan tidak bisa dikembalikan.',
                showCancelButton: true,
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                customClass: { popup: 'rounded-4' }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = targetUrl;
                }
            });
        });
    });
</script>

</body>
</html>
