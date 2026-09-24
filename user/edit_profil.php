<?php
session_start();
include "../koneksi.php";

// Pastikan user sudah login sebagai role user
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'user') {
    header("Location: ../login.php");
    exit;
}

$id_anggota = $_SESSION['id_anggota'] ?? 0;
$nis_session = $_SESSION['nis'] ?? '';

// Cek kolom apa saja yang tersedia di tabel anggota secara dinamis agar tidak error
$cek_kolom = mysqli_query($koneksi, "SHOW COLUMNS FROM anggota");
$kolom_tersedia = [];
if($cek_kolom) {
    while($col = mysqli_fetch_assoc($cek_kolom)){
        $kolom_tersedia[] = $col['Field'];
    }
}

// Buat kueri pencarian berdasarkan kolom yang benar-benar ada di database
$where_clause = [];
if ($id_anggota > 0 && in_array('id_anggota', $kolom_tersedia)) {
    $where_clause[] = "id_anggota = '$id_anggota'";
}
if (!empty($nis_session)) {
    if (in_array('nis', $kolom_tersedia)) $where_clause[] = "nis = '$nis_session'";
    if (in_array('nisn', $kolom_tersedia)) $where_clause[] = "nisn = '$nis_session'";
    if (in_array('nis_nip', $kolom_tersedia)) $where_clause[] = "nis_nip = '$nis_session'";
    if (in_array('username', $kolom_tersedia)) $where_clause[] = "username = '$nis_session'";
}

$data = null;
if (!empty($where_clause)) {
    $sql = "SELECT * FROM anggota WHERE " . implode(" OR ", $where_clause) . " LIMIT 1";
    $query = mysqli_query($koneksi, $sql);
    if ($query && mysqli_num_rows($query) > 0) {
        $data = mysqli_fetch_assoc($query);
    }
}

if (!$data) {
    echo "<script>alert('Data anggota tidak ditemukan!'); window.location='../login.php';</script>";
    exit;
}

// Tentukan nama kolom primary key untuk update (id_anggota atau id)
$pk_kolom = in_array('id_anggota', $kolom_tersedia) ? 'id_anggota' : 'id';
$pk_nilai = $data[$pk_kolom] ?? $id_anggota;

// Tampilkan label NIS / NISN / ID yang tersedia di database
$label_nis_key = '';
foreach (['nis', 'nisn', 'nis_nip', 'username'] as $k) {
    if (isset($data[$k])) {
        $label_nis_key = $data[$k];
        break;
    }
}

// Jika tombol update diklik
if (isset($_POST['update'])) {
    $nama    = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $kelas   = mysqli_real_escape_string($koneksi, $_POST['kelas']);
    $alamat  = mysqli_real_escape_string($koneksi, $_POST['alamat']);
    $telepon = mysqli_real_escape_string($koneksi, $_POST['telepon']);

    $update = mysqli_query($koneksi, "UPDATE anggota SET nama='$nama', kelas='$kelas', alamat='$alamat', telepon='$telepon' WHERE $pk_kolom='$pk_nilai'");

    if ($update) {
        $_SESSION['nama'] = $nama;
        echo "<script>alert('Profil berhasil diperbarui!'); window.location='index.php';</script>";
    } else {
        echo "<script>alert('Gagal memperbarui profil!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Profil - Perpustakaan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5 mb-5">
    <div class="card shadow-sm border-0 rounded-4 p-4">
        <h3 class="mb-4 text-primary fw-bold">Edit Profil</h3>
        
        <form action="" method="POST">
            <div class="mb-3">
                <label class="form-label">NIS / Identitas</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($label_nis_key); ?>" readonly>
            </div>

            <div class="mb-3">
                <label for="nama" class="form-label">Nama</label>
                <input type="text" class="form-control" id="nama" name="nama" value="<?= htmlspecialchars($data['nama'] ?? ''); ?>" required>
            </div>

            <div class="mb-3">
                <label for="kelas" class="form-label">Kelas</label>
                <input type="text" class="form-control" id="kelas" name="kelas" value="<?= htmlspecialchars($data['kelas'] ?? ''); ?>" required>
            </div>

            <div class="mb-3">
                <label for="alamat" class="form-label">Alamat</label>
                <textarea class="form-control" id="alamat" name="alamat" rows="2" required><?= htmlspecialchars($data['alamat'] ?? ''); ?></textarea>
            </div>

            <div class="mb-3">
                <label for="telepon" class="form-label">No HP</label>
                <input type="text" class="form-control" id="telepon" name="telepon" value="<?= htmlspecialchars($data['telepon'] ?? ''); ?>" required>
            </div>

            <button type="submit" name="update" class="btn btn-primary">Simpan Perubahan</button>
            <a href="index.php" class="btn btn-secondary">Kembali</a>
        </form>
    </div>
</div>

</body>
</html>