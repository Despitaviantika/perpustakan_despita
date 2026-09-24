<?php
session_start();
include "../koneksi.php";

// Cek apakah sudah login dan rolenya benar-benar petugas
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'petugas') {
    header("Location: ../login.php");
    exit;
}

$status_pesan = "";
$pesan_error  = "";
$pesan_ekstensi = "";

if (isset($_POST['simpan'])) {
    $judul      = mysqli_real_escape_string($koneksi, $_POST['judul']);
    $pengarang  = mysqli_real_escape_string($koneksi, $_POST['pengarang']);
    $penerbit   = mysqli_real_escape_string($koneksi, $_POST['penerbit']);
    $tahun      = mysqli_real_escape_string($koneksi, $_POST['tahun']);
    $stok       = mysqli_real_escape_string($koneksi, $_POST['stok']);

    // Proses Upload Gambar
    $filename = $_FILES['gambar']['name'];
    $nama_gambar_baru = "";

    if ($filename != "") {
        $ekstensi_diperbolehkan = array('png', 'jpg', 'jpeg');
        $x = explode('.', $filename);
        $ekstensi = strtolower(end($x));
        $file_tmp = $_FILES['gambar']['tmp_name'];
        $angka_acak = rand(1, 999);
        $nama_gambar_baru = $angka_acak . '-' . $filename;

        if (in_array($ekstensi, $ekstensi_diperbolehkan) === true) {
            if (!file_exists('../gambar')) {
                mkdir('../gambar', 0777, true);
            }
            move_uploaded_file($file_tmp, '../gambar/' . $nama_gambar_baru);
        } else {
            $pesan_ekstensi = "Ekstensi gambar harus JPG, JPEG, atau PNG!";
            $nama_gambar_baru = "";
        }
    }

    if (empty($pesan_ekstensi)) {
        $query = mysqli_query($koneksi, "INSERT INTO buku (judul, pengarang, penerbit, tahun_terbit, stok, gambar) 
                  VALUES ('$judul', '$pengarang', '$penerbit', '$tahun', '$stok', '$nama_gambar_baru')");

        if ($query) {
            $status_pesan = "sukses";
        } else {
            $status_pesan = "gagal";
            $pesan_error  = mysqli_error($koneksi);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Buku - Perpustakaan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { 
            background: linear-gradient(135deg, #0077b6, #00b4d8, #90e0ef);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .card-custom { 
            border: none; 
            border-radius: 20px; 
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            background: #ffffff;
        }
        .btn-blue {
            background: #00b4d8;
            color: white;
            border: none;
        }
        .btn-blue:hover {
            background: #0077b6;
            color: white;
        }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <div class="card card-custom p-4 p-md-5">
                
                <div class="d-flex align-items-center mb-4">
                    <h4 class="fw-bold text-dark mb-0">
                        <i class="bi bi-book-fill text-primary me-2"></i>Tambah Data Buku
                    </h4>
                </div>

                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Judul Buku</label>
                        <input type="text" name="judul" class="form-control" placeholder="Masukkan judul buku" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Pengarang</label>
                        <input type="text" name="pengarang" class="form-control" placeholder="Masukkan nama pengarang" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Penerbit</label>
                        <input type="text" name="penerbit" class="form-control" placeholder="Masukkan penerbit" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Tahun Terbit</label>
                            <input type="number" name="tahun" class="form-control" placeholder="Contoh: 2024" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Stok</label>
                            <input type="number" name="stok" class="form-control" placeholder="Jumlah stok" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Cover / Gambar Buku</label>
                        <input type="file" name="gambar" class="form-control" accept="image/*">
                    </div>

                    <div class="d-flex justify-content-between pt-2">
                        <a href="buku.php" class="btn btn-secondary px-4 rounded-3">
                            <i class="bi bi-arrow-left me-1"></i> Batal
                        </a>
                        <button type="submit" name="simpan" class="btn btn-blue px-4 fw-semibold rounded-3">
                            <i class="bi bi-save me-1"></i> Simpan Buku
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<script>
<?php if (!empty($pesan_ekstensi)) : ?>
    Swal.fire({
        title: 'Format tidak didukung',
        text: '<?= addslashes($pesan_ekstensi); ?>',
        icon: 'warning',
        confirmButtonColor: '#0077b6'
    });
<?php elseif ($status_pesan == "sukses") : ?>
    Swal.fire({
        title: 'Berhasil!',
        text: 'Buku berhasil ditambahkan!',
        icon: 'success',
        timer: 2000,
        showConfirmButton: false
    }).then(() => {
        window.location.href = 'buku.php';
    });
<?php elseif ($status_pesan == "gagal") : ?>
    Swal.fire({
        title: 'Gagal!',
        text: 'Gagal menambah buku: <?= addslashes($pesan_error); ?>',
        icon: 'error',
        confirmButtonColor: '#0077b6'
    });
<?php endif; ?>
</script>

</body>
</html>