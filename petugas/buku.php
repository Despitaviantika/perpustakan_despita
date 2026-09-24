<?php
session_start();
include "../koneksi.php";

// Cek apakah sudah login dan rolenya benar-benar petugas
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'petugas') {
    header("Location: ../login.php");
    exit;
}

// Pencarian Buku
if(isset($_GET['cari']) && $_GET['cari'] != ""){
    $cari = mysqli_real_escape_string($koneksi, $_GET['cari']);
    $data = mysqli_query($koneksi,"SELECT * FROM buku WHERE judul LIKE '%$cari%'");
}else{
    $data = mysqli_query($koneksi,"SELECT * FROM buku");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Buku - Petugas</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body {
            background-color: #f4f7f6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

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

        .table thead tr.th-gradasi th {
            background: linear-gradient(135deg, #0d3b66 0%, #0077b6 100%) !important;
            color: #ffffff !important;
            border: none;
        }

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

<div class="container mt-4 mb-5">
    <div class="card card-custom">
        <div class="card-header-gradasi d-flex align-items-center justify-content-between">
            <h4 class="fw-bold mb-0"><i class="bi bi-book-half me-2"></i>Kelola Data Buku</h4>
            <div>
                <a href="index.php" class="btn btn-light btn-sm text-dark fw-semibold rounded-pill px-3">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
                <a href="tambah_buku.php" class="btn btn-gradasi btn-sm rounded-pill px-3 ms-1">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Buku
                </a>
            </div>
        </div>

        <div class="card-body p-4">
            <form method="GET" class="mb-4">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input
                        type="text"
                        name="cari"
                        class="form-control border-start-0"
                        placeholder="Cari Judul Buku..."
                        value="<?= isset($_GET['cari']) ? htmlspecialchars($_GET['cari']) : ''; ?>">

                    <button type="submit" class="btn btn-gradasi px-4">
                        Cari
                    </button>

                    <a href="buku.php" class="btn btn-outline-secondary">
                        Refresh
                    </a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead>
                        <tr class="th-gradasi">
                            <th width="50" class="text-center">No</th>
                            <th width="90" class="text-center">Cover</th>
                            <th>Judul</th>
                            <th>Pengarang</th>
                            <th>Penerbit</th>
                            <th width="90" class="text-center">Tahun</th>
                            <th width="80" class="text-center">Stok</th>
                            <th width="150" class="text-center">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php
                    $no = 1;

                    if(mysqli_num_rows($data) > 0){
                        while($d = mysqli_fetch_array($data)){
                            $gambar = !empty($d['gambar']) ? $d['gambar'] : 'default.jpg';
                    ?>
                    <tr>
                        <td class="text-center fw-semibold"><?= $no++; ?></td>

                        <td class="text-center">
                            <img src="../gambar/<?= $gambar; ?>" 
                                 width="50" 
                                 height="70" 
                                 style="object-fit: cover;" 
                                 class="rounded shadow-sm"
                                 onerror="this.onerror=null; this.src='../gambar/default.jpg';">
                        </td>

                        <td class="fw-bold text-dark"><?= htmlspecialchars($d['judul']); ?></td>
                        <td><?= htmlspecialchars($d['pengarang']); ?></td>
                        <td><?= htmlspecialchars($d['penerbit']); ?></td>
                        <td class="text-center"><?= isset($d['tahun_terbit']) ? $d['tahun_terbit'] : (isset($d['tahun']) ? $d['tahun'] : '-'); ?></td>
                        <td class="text-center fw-bold"><?= $d['stok']; ?></td>

                        <td class="text-center">
                            <a href="edit_buku.php?id=<?= $d['id_buku']; ?>" class="btn btn-warning btn-sm rounded-2">
                                <i class="bi bi-pencil-square me-1"></i>Edit
                            </a>

                            <a href="hapus_buku.php?id=<?= $d['id_buku']; ?>"
                               onclick="return konfirmasiHapus(event, this.href)"
                               class="btn btn-danger btn-sm rounded-2">
                                <i class="bi bi-trash me-1"></i>Hapus
                            </a>
                        </td>
                    </tr>
                    <?php 
                        } 
                    } else {
                    ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-2 d-block mb-1"></i>Data buku tidak ditemukan.
                        </td>
                    </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function konfirmasiHapus(e, url) {
        e.preventDefault();
        Swal.fire({
            title: 'Yakin ingin menghapus?',
            text: 'Data buku ini akan dihapus secara permanen.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
        return false;
    }
</script>

</body>
</html>