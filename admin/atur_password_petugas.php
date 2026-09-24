<?php
session_start();
include "../koneksi.php"; // sesuaikan path kalau lokasi file koneksi.php beda

// Proteksi sederhana: hanya admin yang login yang boleh buka halaman ini
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$status = "";
$pesan  = "";

// Proses saat admin submit password baru untuk seorang petugas
if (isset($_POST['set_password'])) {
    $id_petugas    = intval($_POST['id_petugas']);
    $password_baru = $_POST['password_baru'];

    if (strlen($password_baru) < 4) {
        $status = "terlalu_pendek";
    } else {
        $password_hashed = password_hash($password_baru, PASSWORD_DEFAULT);
        $update = mysqli_query($koneksi, "UPDATE users SET password='$password_hashed' WHERE id_user=$id_petugas AND role='petugas'");

        if ($update) {
            $status = "sukses";
        } else {
            $status = "gagal";
            $pesan  = mysqli_error($koneksi);
        }
    }
}

// Ambil semua akun dengan role petugas
$daftar_petugas = mysqli_query($koneksi, "SELECT id_user, nama, username FROM users WHERE role='petugas' ORDER BY nama ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atur Password Petugas - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background-color: #f4f7fb;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .page-header {
            background: linear-gradient(135deg, #0077b6 0%, #00b4d8 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0, 119, 182, 0.25);
        }
        .card-petugas {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            margin-bottom: 16px;
        }
        .btn-gradient {
            background: linear-gradient(135deg, #0077b6 0%, #00b4d8 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
        }
        .btn-gradient:hover {
            color: white;
            background: linear-gradient(135deg, #023e8a 0%, #0077b6 100%);
        }
        .btn-kembali {
            background: rgba(255,255,255,0.15);
            color: white;
            border: 1px solid rgba(255,255,255,0.5);
            border-radius: 8px;
            font-weight: 600;
            white-space: nowrap;
        }
        .btn-kembali:hover {
            background: rgba(255,255,255,0.3);
            color: white;
        }
    </style>
</head>
<body>

<div class="page-header">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h3 class="fw-bold mb-1"><i class="bi bi-key-fill me-2"></i>Atur Password Petugas</h3>
            <p class="mb-0 opacity-75">Tentukan / ubah password login untuk akun petugas</p>
        </div>
        <a href="index.php" class="btn btn-kembali btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
        </a>
    </div>
</div>

<div class="container pb-5">

    <?php if (mysqli_num_rows($daftar_petugas) == 0) : ?>
        <div class="alert alert-info">Belum ada petugas yang terdaftar.</div>
    <?php else : ?>

        <?php while ($p = mysqli_fetch_assoc($daftar_petugas)) : ?>
            <div class="card card-petugas">
                <div class="card-body">
                    <form method="POST" action="" class="row g-3 align-items-end">
                        <input type="hidden" name="id_petugas" value="<?= $p['id_user']; ?>">

                        <div class="col-md-4">
                            <label class="form-label text-muted small mb-1">Nama</label>
                            <div class="fw-semibold"><?= htmlspecialchars($p['nama']); ?></div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label text-muted small mb-1">Username</label>
                            <div class="fw-semibold"><?= htmlspecialchars($p['username']); ?></div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label text-muted small mb-1">Password Baru</label>
                            <input type="text" name="password_baru" class="form-control" placeholder="Ketik password baru" required minlength="4">
                        </div>

                        <div class="col-md-2">
                            <button type="submit" name="set_password" class="btn btn-gradient w-100">
                                <i class="bi bi-check-lg me-1"></i> Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endwhile; ?>

    <?php endif; ?>

</div>

<script>
<?php if ($status == "sukses") : ?>
    Swal.fire({
        title: 'Berhasil!',
        text: 'Password petugas berhasil diperbarui.',
        icon: 'success',
        timer: 2000,
        showConfirmButton: false
    });
<?php elseif ($status == "terlalu_pendek") : ?>
    Swal.fire({
        title: 'Password terlalu pendek',
        text: 'Password minimal 4 karakter.',
        icon: 'warning',
        confirmButtonColor: '#0077b6'
    });
<?php elseif ($status == "gagal") : ?>
    Swal.fire({
        title: 'Gagal!',
        text: 'Terjadi kesalahan saat menyimpan password.',
        icon: 'error',
        confirmButtonColor: '#0077b6'
    });
<?php endif; ?>
</script>

</body>
</html>