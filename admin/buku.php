<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit;
}

// Proses tambah buku
if (isset($_POST['tambah'])) {
    $judul = $_POST['judul'];
    $pengarang = $_POST['pengarang'];
    $penerbit = $_POST['penerbit'];
    $stok = $_POST['stok'];

    mysqli_query($koneksi, "INSERT INTO buku (judul, pengarang, penerbit, stok) VALUES ('$judul', '$pengarang', '$penerbit', '$stok')");
    header("Location: buku.php");
    exit;
}

// Proses hapus buku
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM buku WHERE id_buku = '$id'");
    header("Location: buku.php");
    exit;
}

$buku = mysqli_query($koneksi, "SELECT * FROM buku");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Buku - Admin</title>
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

        .badge-stok {
            background: linear-gradient(135deg, #2a9d8f, #52b788);
            color: #fff;
        }

        .form-control:focus {
            border-color: #0077b6;
            box-shadow: 0 0 0 0.2rem rgba(0, 119, 182, 0.15);
        }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-dark navbar-kelola px-4 mb-4">
    <a href="index.php" class="navbar-brand"><i class="bi bi-arrow-left"></i> Kembali ke Dashboard</a>
    <span class="text-white fw-semibold"><i class="bi bi-journal-bookmark-fill me-1"></i> Manajemen Buku</span>
</nav>

<div class="container mb-5">
    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header card-header-gradasi fw-bold"><i class="bi bi-plus-circle me-1"></i> Tambah Buku Baru</div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Judul Buku</label>
                            <input type="text" name="judul" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Pengarang</label>
                            <input type="text" name="pengarang" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Penerbit</label>
                            <input type="text" name="penerbit" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Stok</label>
                            <input type="number" name="stok" class="form-control" required>
                        </div>
                        <button type="submit" name="tambah" class="btn btn-gradasi w-100 fw-semibold">Simpan Buku</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header card-header-soft fw-bold py-3"><i class="bi bi-list-stars me-1 text-primary"></i> Daftar Buku Perpustakaan</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Judul</th>
                                    <th>Pengarang</th>
                                    <th>Stok</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no=1; while($row = mysqli_fetch_assoc($buku)) { ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= htmlspecialchars($row['judul']); ?></td>
                                    <td><?= htmlspecialchars($row['pengarang']); ?></td>
                                    <td><span class="badge badge-stok"><?= $row['stok']; ?></span></td>
                                    <td>
                                        <a href="buku.php?hapus=<?= $row['id_buku']; ?>"
                                           class="btn btn-danger btn-sm btn-hapus-buku"
                                           data-judul="<?= htmlspecialchars($row['judul']); ?>">
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
    // Konfirmasi hapus buku dengan SweetAlert2
    document.querySelectorAll('.btn-hapus-buku').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const targetUrl = this.getAttribute('href');
            const judul = this.getAttribute('data-judul') || 'buku ini';

            Swal.fire({
                icon: 'warning',
                title: 'Hapus Buku?',
                text: '"' + judul + '" akan dihapus secara permanen dan tidak bisa dikembalikan.',
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