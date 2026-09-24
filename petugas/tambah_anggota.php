<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'petugas')) {
    header("Location: ../login.php");
    exit;
}

$status_pesan = "";
$pesan_error  = "";

if (isset($_POST['simpan'])) {
    $nis_nip       = mysqli_real_escape_string($koneksi, $_POST['nis_nip']);
    $nama          = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $kelas         = mysqli_real_escape_string($koneksi, $_POST['kelas']);
    $jenis_kelamin = mysqli_real_escape_string($koneksi, $_POST['jenis_kelamin']);
    $telepon       = mysqli_real_escape_string($koneksi, $_POST['telepon']);
    $alamat        = mysqli_real_escape_string($koneksi, $_POST['alamat']);
    $password_raw  = $_POST['password'];

    if (empty($password_raw)) {
        $status_pesan = "password_kosong";
    } else {
        $password_hashed = password_hash($password_raw, PASSWORD_DEFAULT);

        // Diperbaiki dari 'nis' menjadi 'nis_nip'
        $cek_nis = mysqli_query($koneksi, "SELECT * FROM anggota WHERE nis_nip = '$nis_nip'");
        if (mysqli_num_rows($cek_nis) > 0) {
            $status_pesan = "nis_terdaftar";
        } else {
            $query = "INSERT INTO anggota (nis_nip, nama, kelas, jenis_kelamin, telepon, alamat, password) 
                      VALUES ('$nis_nip', '$nama', '$kelas', '$jenis_kelamin', '$telepon', '$alamat', '$password_hashed')";

            $simpan = mysqli_query($koneksi, $query);

            if ($simpan) {
                $status_pesan = "sukses";
            } else {
                $status_pesan = "gagal";
                $pesan_error  = mysqli_error($koneksi);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Anggota - Perpustakaan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">

<div class="container mt-5 mb-5">
    <div class="card shadow-sm border-0 rounded-4 p-4" style="max-width: 600px; margin: 0 auto;">
        <h3 class="mb-4 text-primary fw-bold">Form Tambah Anggota</h3>
        
        <form action="" method="POST">
            <div class="mb-3">
                <label for="nis_nip" class="form-label">NIS / NIP</label>
                <input type="text" class="form-control" id="nis_nip" name="nis_nip" placeholder="Masukkan NIS atau NIP" value="<?= isset($_POST['nis_nip']) ? htmlspecialchars($_POST['nis_nip']) : ''; ?>" required>
            </div>

            <div class="mb-3">
                <label for="nama" class="form-label">Nama Lengkap</label>
                <input type="text" class="form-control" id="nama" name="nama" placeholder="Masukkan nama lengkap" value="<?= isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : ''; ?>" required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password Akun</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Buat password untuk login" required>
            </div>

            <div class="mb-3">
                <label for="kelas" class="form-label">Kelas</label>
                <input type="text" class="form-control" id="kelas" name="kelas" placeholder="Contoh: XII RPL 1" value="<?= isset($_POST['kelas']) ? htmlspecialchars($_POST['kelas']) : ''; ?>">
            </div>

            <div class="mb-3">
                <label for="jenis_kelamin" class="form-label">Jenis Kelamin</label>
                <select class="form-control" id="jenis_kelamin" name="jenis_kelamin" required>
                    <option value="">-- Pilih Jenis Kelamin --</option>
                    <option value="L" <?= (isset($_POST['jenis_kelamin']) && $_POST['jenis_kelamin'] == 'L') ? 'selected' : ''; ?>>Laki-laki (L)</option>
                    <option value="P" <?= (isset($_POST['jenis_kelamin']) && $_POST['jenis_kelamin'] == 'P') ? 'selected' : ''; ?>>Perempuan (P)</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="telepon" class="form-label">Nomor Telepon</label>
                <input type="text" class="form-control" id="telepon" name="telepon" placeholder="Contoh: 081234567890" value="<?= isset($_POST['telepon']) ? htmlspecialchars($_POST['telepon']) : ''; ?>">
            </div>

            <div class="mb-3">
                <label for="alamat" class="form-label">Alamat</label>
                <textarea class="form-control" id="alamat" name="alamat" rows="3" placeholder="Masukkan alamat lengkap"><?= isset($_POST['alamat']) ? htmlspecialchars($_POST['alamat']) : ''; ?></textarea>
            </div>

            <button type="submit" name="simpan" class="btn btn-primary">Simpan Anggota</button>
            <a href="anggota.php" class="btn btn-secondary">Kembali</a>
        </form>
    </div>
</div>

<script>
<?php if ($status_pesan == "password_kosong") : ?>
    Swal.fire({
        title: 'Password kosong',
        text: 'Password tidak boleh kosong!',
        icon: 'warning',
        confirmButtonColor: '#0d6efd'
    });
<?php elseif ($status_pesan == "nis_terdaftar") : ?>
    Swal.fire({
        title: 'NIS/NIP sudah ada',
        text: 'NIS/NIP tersebut sudah terdaftar, gunakan yang lain.',
        icon: 'warning',
        confirmButtonColor: '#0d6efd'
    });
<?php elseif ($status_pesan == "sukses") : ?>
    Swal.fire({
        title: 'Berhasil!',
        text: 'Data anggota berhasil ditambahkan!',
        icon: 'success',
        timer: 2000,
        showConfirmButton: false
    }).then(() => {
        window.location = 'anggota.php';
    });
<?php elseif ($status_pesan == "gagal") : ?>
    Swal.fire({
        title: 'Gagal!',
        text: 'Gagal menyimpan: <?= addslashes($pesan_error); ?>',
        icon: 'error',
        confirmButtonColor: '#0d6efd'
    });
<?php endif; ?>
</script>

</body>
</html>