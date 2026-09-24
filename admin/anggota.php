<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit;
}

// Proses tambah anggota
if (isset($_POST['tambah'])) {
    $nama = $_POST['nama'];
    $alamat = $_POST['alamat'];
    $telepon = $_POST['telepon'];

    mysqli_query($koneksi, "INSERT INTO anggota (nama, alamat, telepon) VALUES ('$nama', '$alamat', '$telepon')");
    header("Location: anggota.php");
    exit;
}

// Proses hapus anggota
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM anggota WHERE id_anggota = '$id'");
    header("Location: anggota.php");
    exit;
}

$anggota = mysqli_query($koneksi, "SELECT * FROM anggota");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Anggota - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

        .card-header-gradasi {
            background: linear-gradient(135deg, #0d3b66 0%, #0077b6 100%) !important;
            color: #fff !important;
        }

        .card-header-soft {
            background: linear-gradient(135deg, #f0f7ff 0%, #e1effe 100%) !important;
            border-bottom: 1px solid #e5eef7 !important;
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

        .form-control:focus, textarea.form-control:focus {
            border-color: #0077b6;
            box-shadow: 0 0 0 0.2rem rgba(0, 119, 182, 0.15);
        }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-dark navbar-kelola px-4 mb-4">
    <a href="index.php" class="navbar-brand"><i class="bi bi-arrow-left"></i> Kembali ke Dashboard</a>
    <span class="text-white fw-semibold"><i class="bi bi-people-fill me-1"></i> Manajemen Anggota</span>
</nav>

<div class="container mb-5">
    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header card-header-gradasi fw-bold"><i class="bi bi-person-plus-fill me-1"></i> Tambah Anggota Baru</div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="nama" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alamat</label>
                            <textarea name="alamat" class="form-control" rows="2" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">No Telepon</label>
                            <input type="text" name="telepon" class="form-control" required>
                        </div>
                        <button type="submit" name="tambah" class="btn btn-gradasi w-100 fw-semibold">Simpan Anggota</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header card-header-soft fw-bold py-3"><i class="bi bi-list-stars me-1 text-primary"></i> Daftar Anggota Terdaftar</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>Alamat</th>
                                    <th>Telepon</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no=1; while($row = mysqli_fetch_assoc($anggota)) { ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= htmlspecialchars($row['nama']); ?></td>
                                    <td><?= htmlspecialchars($row['alamat']); ?></td>
                                    <td><?= htmlspecialchars($row['telepon']); ?></td>
                                    <td>
                                        <a href="anggota.php?hapus=<?= $row['id_anggota']; ?>"
                                           class="btn btn-danger btn-sm btn-hapus-anggota"
                                           data-nama="<?= htmlspecialchars($row['nama']); ?>">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
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