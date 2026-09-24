<?php
session_start();
include "../koneksi.php";

// Pastikan yang mengakses adalah admin atau petugas
if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'petugas')) {
    header("Location: ../login.php");
    exit;
}

$id_buku = isset($_GET['id']) ? mysqli_real_escape_string($koneksi, $_GET['id']) : 0;

// Ambil data buku berdasarkan id_buku
$query = mysqli_query($koneksi, "SELECT * FROM buku WHERE id_buku = '$id_buku'");
$data = mysqli_fetch_assoc($query);

if (!$data) {
    echo "<!DOCTYPE html><html><head><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head><body>
    <script>
        Swal.fire({
            title: 'Data tidak ditemukan',
            text: 'Data buku tidak ditemukan!',
            icon: 'error',
            confirmButtonColor: '#0d6efd'
        }).then(() => { window.location = 'buku.php'; });
    </script>
    </body></html>";
    exit;
}

$status_pesan = "";
$pesan_error  = "";

// Jika tombol update diklik
if (isset($_POST['update'])) {
    $judul      = mysqli_real_escape_string($koneksi, $_POST['judul']);
    $pengarang  = mysqli_real_escape_string($koneksi, $_POST['pengarang']);
    $penerbit   = mysqli_real_escape_string($koneksi, $_POST['penerbit']);
    $tahun      = mysqli_real_escape_string($koneksi, $_POST['tahun']);
    $stok       = mysqli_real_escape_string($koneksi, $_POST['stok']);

    $gambar_lama = $data['gambar']; // Nama kolom gambar di database: gambar
    $nama_file_baru = $gambar_lama;

    // Cek apakah user mengupload gambar cover baru
    if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
        $file_tmp   = $_FILES['cover']['tmp_name'];
        $file_name  = $_FILES['cover']['name'];
        $ekstensi   = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $ekstensi_diperbolehkan = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($ekstensi, $ekstensi_diperbolehkan)) {
            // Buat nama file unik agar tidak bentrok
            $nama_file_baru = uniqid() . '_' . $file_name;
            $folder_tujuan = '../assets/img/' . $nama_file_baru; // Sesuaikan folder penyimpanan gambar Anda

            // Buat folder jika belum ada
            if (!is_dir('../assets/img/')) {
                mkdir('../assets/img/', 0777, true);
            }

            if (move_uploaded_file($file_tmp, $folder_tujuan)) {
                // Hapus gambar lama jika ada di folder
                if (!empty($gambar_lama) && file_exists('../assets/img/' . $gambar_lama)) {
                    unlink('../assets/img/' . $gambar_lama);
                }
            }
        }
    }

    // Update data ke database (nama kolom sesuai struktur tabel buku)
    $sql_update = "UPDATE buku SET 
                    judul = '$judul', 
                    pengarang = '$pengarang', 
                    penerbit = '$penerbit', 
                    tahun_terbit = '$tahun', 
                    stok = '$stok', 
                    gambar = '$nama_file_baru' 
                    WHERE id_buku = '$id_buku'";

    $update = mysqli_query($koneksi, $sql_update);

    if ($update) {
        $status_pesan = "sukses";
    } else {
        $status_pesan = "gagal";
        $pesan_error  = mysqli_error($koneksi);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Data Buku - Perpustakaan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">

<div class="container mt-5 mb-5">
    <div class="card shadow-sm border-0 rounded-4 p-4" style="max-width: 650px; margin: 0 auto;">
        <h3 class="mb-4 text-primary fw-bold">Edit Data Buku</h3>
        
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="judul" class="form-label">Judul Buku</label>
                <input type="text" class="form-control" id="judul" name="judul" value="<?= htmlspecialchars($data['judul'] ?? ''); ?>" required>
            </div>

            <div class="mb-3">
                <label for="pengarang" class="form-label">Pengarang</label>
                <input type="text" class="form-control" id="pengarang" name="pengarang" value="<?= htmlspecialchars($data['pengarang'] ?? ''); ?>" required>
            </div>

            <div class="mb-3">
                <label for="penerbit" class="form-label">Penerbit</label>
                <input type="text" class="form-control" id="penerbit" name="penerbit" value="<?= htmlspecialchars($data['penerbit'] ?? ''); ?>" required>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="tahun" class="form-label">Tahun Terbit</label>
                    <input type="number" class="form-control" id="tahun" name="tahun" value="<?= htmlspecialchars($data['tahun_terbit'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="stok" class="form-label">Stok Buku</label>
                    <input type="number" class="form-control" id="stok" name="stok" value="<?= htmlspecialchars($data['stok'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="cover" class="form-label">Cover Buku (Opsional)</label>
                <?php if (!empty($data['gambar'])): ?>
                    <div class="mb-2">
                        <img src="../assets/img/<?= htmlspecialchars($data['gambar']); ?>" alt="Cover Buku" style="width: 80px; height: 100px; object-fit: cover; border-radius: 5px;">
                        <br><small class="text-muted">Cover saat ini</small>
                    </div>
                <?php endif; ?>
                <input type="file" class="form-control" id="cover" name="cover" accept=".jpg, .jpeg, .png, .webp">
                <small class="text-muted">Biarkan kosong jika tidak ingin mengganti gambar cover.</small>
            </div>

            <button type="submit" name="update" class="btn btn-primary">Simpan Perubahan</button>
            <a href="buku.php" class="btn btn-secondary">Kembali</a>
        </form>
    </div>
</div>

<script>
<?php if ($status_pesan == "sukses") : ?>
    Swal.fire({
        title: 'Berhasil!',
        text: 'Data buku berhasil diperbarui!',
        icon: 'success',
        timer: 2000,
        showConfirmButton: false
    }).then(() => {
        window.location = 'buku.php';
    });
<?php elseif ($status_pesan == "gagal") : ?>
    Swal.fire({
        title: 'Gagal!',
        text: 'Gagal memperbarui data: <?= addslashes($pesan_error); ?>',
        icon: 'error',
        confirmButtonColor: '#0d6efd'
    });
<?php endif; ?>
</script>

</body>
</html>