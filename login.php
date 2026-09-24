<?php
session_start();
include "koneksi.php";

$status_login = ""; 
$nama_login = ""; 

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($koneksi, trim($_POST['username']));
    $password = $_POST['password'];
    $role     = isset($_POST['role']) ? $_POST['role'] : '';

    if ($role == 'admin' || $role == 'petugas') {
        $query = mysqli_query($koneksi, "SELECT * FROM users WHERE username='$username' AND role='$role'");

        if ($query && mysqli_num_rows($query) > 0) {
            $data = mysqli_fetch_assoc($query);

            if (password_verify($password, $data['password']) || $password == $data['password'] || md5($password) == $data['password']) {
                $_SESSION['id_user']  = $data['id'] ?? $data['id_user'] ?? 1;
                $_SESSION['username'] = $data['username'];
                $_SESSION['nama']     = $data['nama'];
                $_SESSION['role']     = $data['role'];

                $nama_login   = $data['nama'];
                $status_login = ($data['role'] == 'admin') ? "admin_sukses" : "petugas_sukses";
            } else {
                $status_login = "password_salah";
            }
        } else {
            $status_login = "user_tidak_ditemukan";
        }

    } else if ($role == 'user') {
        $cek_kolom = mysqli_query($koneksi, "SHOW COLUMNS FROM anggota");
        $kolom_tersedia = [];
        if($cek_kolom) {
            while($col = mysqli_fetch_assoc($cek_kolom)){
                $kolom_tersedia[] = $col['Field'];
            }
        }

        $where_clause = [];
        if (in_array('nis', $kolom_tersedia)) $where_clause[] = "nis = '$username'";
        if (in_array('nisn', $kolom_tersedia)) $where_clause[] = "nisn = '$username'";
        if (in_array('nis_nip', $kolom_tersedia)) $where_clause[] = "nis_nip = '$username'";
        if (in_array('username', $kolom_tersedia)) $where_clause[] = "username = '$username'";

        if (!empty($where_clause)) {
            $sql_anggota = "SELECT * FROM anggota WHERE " . implode(" OR ", $where_clause);
            $query = mysqli_query($koneksi, $sql_anggota);
        } else {
            $query = false;
        }

        if ($query && mysqli_num_rows($query) > 0) {
            $data = mysqli_fetch_assoc($query);
            $pass_db = $data['password'] ?? '';

            if (empty($pass_db) || $password == $pass_db || password_verify($password, $pass_db) || md5($password) == $pass_db) {
                $_SESSION['id_anggota'] = $data['id_anggota'] ?? $data['id'] ?? 1;
                $_SESSION['nis']        = $data['nis'] ?? $data['nisn'] ?? $username;
                $_SESSION['nama']       = $data['nama'] ?? 'Anggota';
                $_SESSION['role']       = 'user';

                $nama_login   = $data['nama'] ?? 'Siswa';
                $status_login = "user_sukses";
            } else {
                $status_login = "password_salah";
            }
        } else {
            $status_login = "user_tidak_ditemukan";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Perpustakaan</title>
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
        .login-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
            overflow: hidden;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
        .login-header {
            padding: 35px 20px 10px 20px;
            text-align: center;
            position: relative;
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
        .back-link {
            position: absolute;
            top: 14px;
            left: 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 600;
            color: #0077b6;
            text-decoration: none;
            background: #e6f4fb;
            border: 1px solid #b6e2f2;
            padding: 6px 14px;
            border-radius: 20px;
            transition: all 0.2s ease;
        }
        .back-link:hover {
            background: #0077b6;
            color: #ffffff;
            border-color: #0077b6;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-6 col-lg-4">
            <div class="card login-card my-4">
                
                <div class="login-header">
                    <a href="index.php" class="back-link">
                        <i class="bi bi-arrow-left"></i> Beranda
                    </a>
                    <div class="icon-box">
                        <i class="bi bi-book"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-1">Perpustakaan</h4>
                    <p class="text-muted small">Silakan masuk ke akun Anda</p>
                </div>

                <div class="card-body p-4 pt-2">
                    <form method="POST" action="">
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">
                                <i class="bi bi-person-badge me-1"></i> Login Sebagai
                            </label>
                            <select name="role" class="form-select" required>
                                <option value="" selected disabled>-- Pilih Level Login --</option>
                                <option value="user">Siswa / Anggota</option>
                                <option value="petugas">Petugas</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">
                                <i class="bi bi-person me-1"></i> Username / NIS
                            </label>
                            <input type="text" name="username" class="form-control" placeholder="Masukkan Username / NIS" required autocomplete="off">
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold text-secondary">
                                <i class="bi bi-lock me-1"></i> Password
                            </label>
                            <input type="password" name="password" class="form-control" placeholder="Masukkan Password" required>
                        </div>

                        <button type="submit" name="login" class="btn btn-gradient w-100 mb-3">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Masuk Sekarang
                        </button>
                        
                    </form>

                    <div class="text-center border-top pt-3">
                        <p class="text-muted small mb-2">Daftar Petugas Baru </p>
                        <a href="register_admin.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                            <i class="bi bi-person-plus me-1"></i> Daftar di sini
                        </a>
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
    // Fungsi putarSuara diperbaiki agar tetap jalan di HP.
    // Penyebab suara hilang di mobile: daftar "voices" pada speechSynthesis
    // sering belum siap saat halaman baru selesai load (terutama Chrome Android).
    // Kalau speak() dipanggil sebelum voices siap, suara gagal tanpa error apapun.
    function putarSuara(pesanTeks) {
        if (!("speechSynthesis" in window)) return;

        function speakNow() {
            window.speechSynthesis.cancel();
            var utterance = new SpeechSynthesisUtterance(pesanTeks);
            utterance.lang = "id-ID";
            utterance.rate = 0.85;
            window.speechSynthesis.speak(utterance);
        }

        var voices = window.speechSynthesis.getVoices();
        if (voices.length > 0) {
            speakNow();
        } else {
            // Tunggu voices siap (event ini yang sering telat/tidak jalan di mobile)
            window.speechSynthesis.onvoiceschanged = function () {
                speakNow();
            };
            // Fallback: kalau voiceschanged tidak kepanggil dalam 300ms, tetap coba paksa
            setTimeout(speakNow, 300);
        }
    }

<?php if ($status_login == "admin_sukses") : ?>
    putarSuara("Selamat Datang di Perpustakaan Pendidikan, Admin <?= addslashes($nama_login); ?>");
    Swal.fire({
        title: 'Login Berhasil!',
        text: 'Selamat datang, Admin <?= htmlspecialchars($nama_login); ?>!',
        icon: 'success',
        timer: 4500, // Durasi diperpanjang menjadi 4.5 detik
        showConfirmButton: false
    }).then(function() {
        window.location.href = 'admin/index.php';
    });
<?php elseif ($status_login == "petugas_sukses") : ?>
    putarSuara("Selamat Datang di Perpustakaan Pendidikan, Petugas <?= addslashes($nama_login); ?>");
    Swal.fire({
        title: 'Login Berhasil!',
        text: 'Selamat datang, Petugas <?= htmlspecialchars($nama_login); ?>!',
        icon: 'success',
        timer: 4500,
        showConfirmButton: false
    }).then(function() {
        window.location.href = 'petugas/index.php';
    });
<?php elseif ($status_login == "user_sukses") : ?>
    putarSuara("Selamat Datang di Perpustakaan Pendidikan, <?= addslashes($nama_login); ?>");
    Swal.fire({
        title: 'Login Berhasil!',
        text: 'Selamat datang, <?= htmlspecialchars($nama_login); ?>!',
        icon: 'success',
        timer: 4500,
        showConfirmButton: false
    }).then(function() {
        window.location.href = 'user/index.php';
    });
<?php elseif ($status_login == "password_salah") : ?>
    Swal.fire({
        title: 'Gagal Login!',
        text: 'Password yang Anda masukkan salah.',
        icon: 'error',
        confirmButtonColor: '#0077b6'
    });
<?php elseif ($status_login == "user_tidak_ditemukan") : ?>
    Swal.fire({
        title: 'Gagal Login!',
        text: 'Username/NIS tidak ditemukan atau salah memilih role login.',
        icon: 'error',
        confirmButtonColor: '#0077b6'
    });
<?php endif; ?>
</script>

</body>
</html>