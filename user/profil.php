<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['id_anggota'])) {
    header("Location: ../login_user.php");
    exit;
}

$id = $_SESSION['id_anggota'];

$query = mysqli_query($koneksi, "SELECT * FROM anggota WHERE id_anggota='$id'");
$data = mysqli_fetch_assoc($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - Perpustakaan</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        body {
            background-color: #f4f7f6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Navbar Gradasi */
        .navbar-gradasi {
            background: linear-gradient(135deg, #0d3b66 0%, #0077b6 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        /* Card Custom */
        .card-custom {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            background: #ffffff;
        }

        .card-header-gradasi {
            background: linear-gradient(135deg, #0d3b66 0%, #0077b6 100%);
            color: #ffffff;
            padding: 20px;
        }

        /* Tombol Utama Gradasi Biru */
        .btn-gradasi {
            background: linear-gradient(135deg, #0077b6 0%, #00b4d8 100%);
            color: #ffffff;
            border: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-gradasi:hover {
            background: linear-gradient(135deg, #023e8a 0%, #0077b6 100%);
            color: #ffffff;
        }
    </style>
</head>

<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-gradasi py-3">
    <div class="container">
        <span class="navbar-brand fw-bold fs-5">
            <i class="bi bi-book-half me-2"></i>Perpustakaan
        </span>

        <div class="d-flex align-items-center gap-2">
            <a href="index.php" class="btn btn-light btn-sm fw-semibold rounded-pill px-3">
                <i class="bi bi-house-door me-1"></i> Dashboard
            </a>

            <a href="riwayat.php" class="btn btn-warning btn-sm fw-semibold rounded-pill px-3 text-dark">
                <i class="bi bi-journal-text me-1"></i> Riwayat
            </a>

            <a href="logout_user.php" class="btn btn-outline-light btn-sm fw-semibold rounded-pill px-3">
                <i class="bi bi-box-arrow-right me-1"></i> Logout
            </a>
        </div>
    </div>
</nav>

<div class="container mt-4 mb-5">

    <div class="row justify-content-center">
        <div class="col-md-8">

            <div class="card card-custom">
                <div class="card-header-gradasi text-center py-4">
                    <div class="mb-2">
                        <i class="bi bi-person-circle fs-1"></i>
                    </div>
                    <h4 class="fw-bold mb-0">Profil Saya</h4>
                    <p class="text-white-50 small mb-0">Informasi data diri anggota perpustakaan</p>
                </div>

                <div class="card-body p-4">

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-4">
                            <tbody>
                                <tr>
                                    <th width="180" class="text-muted"><i class="bi bi-card-heading me-2"></i>NIS</th>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($data['nis'] ?? '-'); ?></td>
                                </tr>
                                <tr>
                                    <th class="text-muted"><i class="bi bi-person me-2"></i>Nama Lengkap</th>
                                    <td class="fw-semibold"><?= htmlspecialchars($data['nama'] ?? '-'); ?></td>
                                </tr>
                                <tr>
                                    <th class="text-muted"><i class="bi bi-mortarboard me-2"></i>Kelas</th>
                                    <td>
                                        <span class="badge bg-secondary-subtle text-secondary border px-3 py-1">
                                            <?= htmlspecialchars($data['kelas'] ?? '-'); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="text-muted"><i class="bi bi-geo-alt me-2"></i>Alamat</th>
                                    <td><?= htmlspecialchars($data['alamat'] ?? '-'); ?></td>
                                </tr>
                                <tr>
                                    <th class="text-muted"><i class="bi bi-telephone me-2"></i>No HP</th>
                                    <td><?= htmlspecialchars($data['no_hp'] ?? '-'); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                        <a href="edit_profil.php" class="btn btn-gradasi rounded-pill px-4">
                            <i class="bi bi-pencil-square me-1"></i> Edit Profil
                        </a>

                        <a href="ganti_password.php" class="btn btn-warning rounded-pill px-4 text-dark fw-semibold">
                            <i class="bi bi-key me-1"></i> Ganti Password
                        </a>
                    </div>

                </div>
            </div>

        </div>
    </div>

</div>

</body>
</html>
<?php ?>