<?php
session_start();
include "../koneksi.php";

// Pastikan yang mengakses adalah admin atau petugas
if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'petugas')) {
    header("Location: ../login.php");
    exit;
}

$id_anggota = isset($_GET['id']) ? mysqli_real_escape_string($koneksi, $_GET['id']) : 0;

// Ambil data anggota berdasarkan id_anggota
$query = mysqli_query($koneksi, "SELECT * FROM anggota WHERE id_anggota = '$id_anggota'");
$data = mysqli_fetch_assoc($query);

if (!$data) {
    // Data tidak ditemukan: tampilkan notifikasi menarik lalu redirect
    $notif_tipe  = 'error';
    $notif_judul = 'Data Tidak Ditemukan';
    $notif_pesan = 'Data anggota yang Anda cari tidak ada atau sudah dihapus.';
    $notif_redirect = 'anggota.php';
    include __DIR__ . '/_notif_dan_keluar.php'; // lihat catatan di bawah kode
    exit;
}

// Status notifikasi yang akan ditampilkan lewat SweetAlert2
$notif_tipe     = null;   // 'success' atau 'error'
$notif_judul    = '';
$notif_pesan    = '';
$notif_redirect = '';     // ke mana diarahkan setelah notifikasi ditutup (khusus sukses)

// Jika tombol Simpan Perubahan diklik
if (isset($_POST['update'])) {
    $nis_nip   = mysqli_real_escape_string($koneksi, $_POST['nis_nip']);
    $nama      = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $kelas     = mysqli_real_escape_string($koneksi, $_POST['kelas']);
    $telepon   = mysqli_real_escape_string($koneksi, $_POST['telepon']);
    $alamat    = mysqli_real_escape_string($koneksi, $_POST['alamat']);
    $password  = $_POST['password'];

    // Cek apakah password diisi atau dikosongkan
    if (!empty($password)) {
        // Jika password diisi, update beserta password baru (di-hash)
        $password_hashed = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE anggota SET 
                nis_nip = '$nis_nip', 
                nama = '$nama', 
                kelas = '$kelas', 
                telepon = '$telepon', 
                alamat = '$alamat', 
                password = '$password_hashed' 
                WHERE id_anggota = '$id_anggota'";
    } else {
        // Jika password kosong, jangan ubah password lama di database
        $sql = "UPDATE anggota SET 
                nis_nip = '$nis_nip', 
                nama = '$nama', 
                kelas = '$kelas', 
                telepon = '$telepon', 
                alamat = '$alamat' 
                WHERE id_anggota = '$id_anggota'";
    }

    $update = mysqli_query($koneksi, $sql);

    if ($update) {
        $notif_tipe     = 'success';
        $notif_judul    = 'Berhasil Disimpan!';
        $notif_pesan    = 'Data anggota <b>' . htmlspecialchars($nama) . '</b> berhasil diperbarui.';
        $notif_redirect = 'anggota.php';

        // Refresh data supaya form menampilkan nilai terbaru jika notif ditutup tanpa redirect
        $data = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM anggota WHERE id_anggota = '$id_anggota'"));
    } else {
        $notif_tipe  = 'error';
        $notif_judul = 'Gagal Menyimpan';
        $notif_pesan = 'Terjadi kesalahan saat memperbarui data: ' . htmlspecialchars(mysqli_error($koneksi));
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Anggota - Perpustakaan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background: #f4f7f6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .kartu-form {
            max-width: 600px;
            margin: 0 auto;
            border: none;
            border-radius: 18px;
            overflow: hidden;
        }
        .kepala-form {
            background: linear-gradient(135deg, #1d3557 0%, #457b9d 100%);
            color: #fff;
            padding: 26px 28px;
        }
        .kepala-form .ikon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: rgba(255,255,255,.15);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            margin-bottom: 10px;
        }
        .isi-form {
            padding: 28px;
        }
        .form-label {
            font-weight: 600;
            color: #33415c;
            font-size: .9rem;
        }
        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #dee2e6;
            padding: 10px 14px;
        }
        .form-control:focus, .form-select:focus {
            border-color: #457b9d;
            box-shadow: 0 0 0 .2rem rgba(69,123,157,.15);
        }
        .btn-simpan {
            background: linear-gradient(135deg, #1d3557 0%, #457b9d 100%);
            border: none;
            color: #fff;
            font-weight: 600;
            padding: 10px 22px;
            border-radius: 10px;
            transition: .2s;
        }
        .btn-simpan:hover {
            filter: brightness(1.08);
            color: #fff;
        }
        .btn-kembali {
            border-radius: 10px;
            padding: 10px 22px;
            font-weight: 600;
        }
        .catatan-password {
            background: #f1f5f9;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: .82rem;
            color: #556;
            display: flex;
            gap: 8px;
            align-items: flex-start;
        }
    </style>
</head>
<body class="bg-light">

<div class="container mt-5 mb-5">
    <div class="card shadow kartu-form">

        <div class="kepala-form">
            <div class="ikon"><i class="bi bi-person-gear"></i></div>
            <h4 class="fw-bold mb-1">Edit Data Anggota</h4>
            <small style="opacity:.85;">Perbarui informasi anggota perpustakaan</small>
        </div>

        <div class="isi-form">
            <form action="" method="POST">
                <div class="mb-3">
                    <label for="nis_nip" class="form-label">NIS / NIP</label>
                    <input type="text" class="form-control" id="nis_nip" name="nis_nip" value="<?= htmlspecialchars($data['nis_nip'] ?? ''); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="nama" class="form-label">Nama Lengkap</label>
                    <input type="text" class="form-control" id="nama" name="nama" value="<?= htmlspecialchars($data['nama'] ?? ''); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password Baru (Opsional)</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Kosongkan jika tidak ingin mengubah password">
                    <div class="catatan-password mt-2">
                        <i class="bi bi-info-circle text-primary"></i>
                        <span>Isi bagian ini hanya jika ingin mengganti password anggota.</span>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="kelas" class="form-label">Kelas</label>
                    <input type="text" class="form-control" id="kelas" name="kelas" value="<?= htmlspecialchars($data['kelas'] ?? ''); ?>">
                </div>

                <div class="mb-3">
                    <label for="telepon" class="form-label">No. Telepon / HP</label>
                    <input type="text" class="form-control" id="telepon" name="telepon" value="<?= htmlspecialchars($data['telepon'] ?? ''); ?>">
                </div>

                <div class="mb-4">
                    <label for="alamat" class="form-label">Alamat</label>
                    <textarea class="form-control" id="alamat" name="alamat" rows="3"><?= htmlspecialchars($data['alamat'] ?? ''); ?></textarea>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" name="update" class="btn btn-simpan">
                        <i class="bi bi-check-lg me-1"></i> Simpan Perubahan
                    </button>
                    <a href="anggota.php" class="btn btn-outline-secondary btn-kembali">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($notif_tipe): ?>
<script>
    Swal.fire({
        icon: '<?= $notif_tipe; ?>',
        title: '<?= addslashes($notif_judul); ?>',
        html: '<?= addslashes($notif_pesan); ?>',
        confirmButtonText: '<?= $notif_tipe == 'success' ? 'Oke, Lanjutkan' : 'Tutup'; ?>',
        confirmButtonColor: '<?= $notif_tipe == 'success' ? '#457b9d' : '#d33'; ?>',
        customClass: { popup: 'rounded-4' }
    })<?php if ($notif_redirect): ?>.then(() => {
        window.location.href = '<?= $notif_redirect; ?>';
    })<?php endif; ?>;
</script>
<?php endif; ?>

</body>
</html>