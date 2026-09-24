<?php
session_start();
include "koneksi.php";

$status_register = "";

if (isset($_POST['register'])) {
    $nama     = mysqli_real_escape_string($koneksi, trim($_POST['nama']));
    $username = mysqli_real_escape_string($koneksi, trim($_POST['username']));
    // Password belum ditentukan saat daftar - diisi kode acak sementara
    // yang tidak bisa dipakai login, sampai admin mengatur password aslinya
    // lewat halaman admin/atur_password_petugas.php
    $password_sementara = bin2hex(random_bytes(8));
    $role     = 'petugas';

    // Cek apakah username sudah dipakai
    $cek_username = mysqli_query($koneksi, "SELECT * FROM users WHERE username='$username'");

    if (mysqli_num_rows($cek_username) > 0) {
        $status_register = "username_kembar";
    } else {
        // Enkripsi password sementara menggunakan password_hash()
        $password_hashed = password_hash($password_sementara, PASSWORD_DEFAULT);

        // Simpan data ke tabel users
        $insert = mysqli_query($koneksi, "INSERT INTO users (nama, username, password, role) VALUES ('$nama', '$username', '$password_hashed', '$role')");

        if ($insert) {
            $status_register = "sukses";
        } else {
            $status_register = "gagal";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Petugas - Perpustakaan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        body {
            background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?q=80&w=1920&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .register-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
            overflow: hidden;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
        .register-header {
            background: transparent;
            padding: 35px 20px 10px 20px;
            text-align: center;
        }
        .icon-box {
            width: 75px;
            height: 75px;
            background: linear-gradient(135deg, #0077b6, #00b4d8);
            color: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 34px;
            margin: 0 auto 15px auto;
            box-shadow: 0 6px 15px rgba(0, 180, 216, 0.4);
        }
        .form-control, .form-select {
            border-radius: 10px;
            padding: 12px 15px;
            border: 1px solid #ced4da;
        }
        .form-control:focus, .form-select:focus {
            border-color: #0077b6;
            box-shadow: 0 0 0 0.25rem rgba(0, 119, 182, 0.25);
        }
        .btn-gradient {
            background: linear-gradient(135deg, #0077b6 0%, #00b4d8 100%);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 119, 182, 0.3);
        }
        .btn-gradient:hover {
            background: linear-gradient(135deg, #023e8a 0%, #0077b6 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(0, 119, 182, 0.4);
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-6 col-lg-4">
            <div class="card register-card my-4">
                
                <div class="register-header">
                    <div class="icon-box">
                        <i class="bi bi-person-plus"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-1">Registrasi Petugas</h4>
                    <p class="text-muted small">Daftar sebagai calon petugas perpustakaan</p>
                </div>

                <div class="card-body p-4 pt-2">
                    <form method="POST" action="">
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">
                                <i class="bi bi-card-heading me-1"></i> Nama Lengkap
                            </label>
                            <input type="text" name="nama" class="form-control" placeholder="Masukkan nama lengkap" required autocomplete="off">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">
                                <i class="bi bi-person me-1"></i> Username
                            </label>
                            <input type="text" name="username" class="form-control" placeholder="Masukkan username" required autocomplete="off">
                        </div>

                        <div class="mb-4">
                            <small class="text-muted d-block">
                                <i class="bi bi-info-circle me-1"></i> Akun kamu akan aktif setelah admin mengatur password login-nya. Silakan konfirmasi ke admin setelah mendaftar.
                            </small>
                        </div>

                        <button type="submit" name="register" class="btn btn-gradient w-100 mb-3">
                            <i class="bi bi-check-circle me-1"></i> Daftar Sekarang
                        </button>
                        
                    </form>

                    <div class="text-center border-top pt-3">
                        <p class="text-muted small mb-0">Sudah punya akun? <a href="login.php" class="text-primary fw-bold text-decoration-none">Login di sini</a></p>
                    </div>
                </div>

            </div>
            
            <div class="text-center text-white small">
                &copy; <?php echo date('Y'); ?> Sistem Informasi Perpustakaan
            </div>
        </div>
    </div>
</div>

<script>
<?php if ($status_register == "sukses") : ?>
    Swal.fire({
        title: 'Registrasi Berhasil!',
        text: 'Akun kamu sudah tersimpan. Silakan hubungi admin supaya password login-nya diatur.',
        icon: 'success',
        timer: 2000,
        showConfirmButton: false
    }).then(function() {
        window.location.href = 'login.php';
    });
<?php elseif ($status_register == "username_kembar") : ?>
    Swal.fire({
        title: 'Registrasi Gagal!',
        text: 'Username sudah terdaftar, gunakan username lain.',
        icon: 'warning',
        confirmButtonColor: '#0077b6'
    });
<?php elseif ($status_register == "gagal") : ?>
    Swal.fire({
        title: 'Registrasi Gagal!',
        text: 'Terjadi kesalahan sistem saat menyimpan data.',
        icon: 'error',
        confirmButtonColor: '#0077b6'
    });
<?php endif; ?>
</script>

</body>
</html>