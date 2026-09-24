<?php
date_default_timezone_set('Asia/Jakarta');
session_start();
include '../koneksi.php';

// 1. Cek apakah user sudah login sebagai siswa/anggota
if (!isset($_SESSION['id_anggota'])) {
    header("Location: ../login.php?pesan=harus_login");
    exit;
}

// 2. Ambil ID buku dari URL
$id_buku = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Ambil data buku dari database
$query_buku = mysqli_query($koneksi, "SELECT * FROM buku WHERE id_buku = $id_buku");
$buku = mysqli_fetch_assoc($query_buku);

if (!$buku) {
    header("Location: index.php?pesan=buku_tidak_ada");
    exit;
}

$pesan_gagal = "";

// Tanggal hari ini, format Indonesia (statis)
$nama_hari = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
$nama_bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$hari_ini = $nama_hari[date('w')] . ', ' . date('d') . ' ' . $nama_bulan[(int)date('n')] . ' ' . date('Y');

// --- Cek jam operasional perpustakaan (08:00 - 15:00) ---
$jam_buka  = '07:00';
$jam_tutup = '15:00';
$jam_sekarang = date('H:i');
$sedang_buka = ($jam_sekarang >= $jam_buka && $jam_sekarang < $jam_tutup);

// 3. Jika tombol konfirmasi pinjam diklik
if (isset($_POST['konfirmasi_pinjam'])) {
    if (!$sedang_buka) {
        // Jaga-jaga: tolak juga di sisi server, jangan cuma andalkan tombol yang disabled di tampilan
        $pesan_gagal = "Maaf, perpustakaan sedang tutup. Jam operasional: $jam_buka - $jam_tutup.";
    } else {
        $id_anggota = $_SESSION['id_anggota'];
        $tgl_pinjam = date('Y-m-d');
        $tgl_kembali = date('Y-m-d', strtotime('+7 days')); 
        $status     = 'Dipinjam';
        $judul_buku_snapshot     = mysqli_real_escape_string($koneksi, $buku['judul'] ?? '');
        $pengarang_buku_snapshot = mysqli_real_escape_string($koneksi, $buku['pengarang'] ?? '');

        $query_insert = "INSERT INTO transaksi (id_anggota, id_buku, tgl_pinjam, tgl_kembali, status, denda, judul_buku, pengarang_buku) 
                         VALUES ('$id_anggota', '$id_buku', '$tgl_pinjam', '$tgl_kembali', '$status', 0, '$judul_buku_snapshot', '$pengarang_buku_snapshot')";
        
        if (mysqli_query($koneksi, $query_insert)) {
            // Redirect ke dashboard, notifikasi cantik ditangani oleh SweetAlert2 di index.php
            header("Location: index.php?pesan=sukses_pinjam");
            exit;
        } else {
            $pesan_gagal = "Gagal meminjam buku: " . mysqli_error($koneksi);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Peminjaman Buku - Perpustakaan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Desain Latar Belakang Biru Gradasi (tetap sama) */
        body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 24px 0;
        }

        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.28);
            overflow: hidden;
        }

        /* Header dengan warna yang sama, tapi lebih hidup */
        .card-header {
            background: linear-gradient(135deg, #00c6ff 0%, #0072ff 100%) !important;
            border: none !important;
            padding: 34px 30px 28px;
            position: relative;
            overflow: hidden;
        }
        .card-header::before {
            content: "";
            position: absolute;
            top: -40px;
            right: -40px;
            width: 140px;
            height: 140px;
            background: rgba(255,255,255,0.08);
            border-radius: 50%;
        }
        .card-header::after {
            content: "";
            position: absolute;
            bottom: -50px;
            left: -20px;
            width: 110px;
            height: 110px;
            background: rgba(255,255,255,0.07);
            border-radius: 50%;
        }

        .header-icon {
            width: 56px;
            height: 56px;
            background: rgba(255,255,255,0.18);
            border: 2px solid rgba(255,255,255,0.35);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            color: #ffffff;
            margin: 0 auto 14px;
            position: relative;
            z-index: 1;
        }

        .tanggal-hari-ini {
            font-size: 0.85rem;
            color: rgba(255,255,255,0.85);
            position: relative;
            z-index: 1;
        }

        .card-body {
            padding: 30px 32px 32px;
        }

        .buku-info-box {
            background: linear-gradient(135deg, #f4f9ff 0%, #eaf3ff 100%);
            border: 1px solid #d7e7fb;
            border-radius: 14px;
            padding: 18px 20px;
            position: relative;
        }
        .buku-info-box .judul-buku {
            color: #0d3b66;
            font-size: 1.08rem;
        }
        .buku-info-box p {
            font-size: 0.92rem;
        }
        .buku-info-box i {
            color: #0072ff;
            width: 18px;
        }

        .badge-durasi {
            background: linear-gradient(135deg, #00c6ff 0%, #0072ff 100%);
            color: #fff;
            font-weight: 600;
            padding: 5px 12px;
            border-radius: 30px;
            font-size: 0.78rem;
        }

        .info-list {
            background: #eef7ff;
            border: 1px solid #d3e8ff;
            border-radius: 14px;
            padding: 16px 18px;
            font-size: 0.88rem;
        }
        .info-list.tutup {
            background: #fff6e8;
            border-color: #ffe1a8;
        }
        .info-list .info-row {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 8px;
        }
        .info-list .info-row:last-child { margin-bottom: 0; }
        .info-list .info-row i {
            color: #0072ff;
            font-size: 1rem;
            margin-top: 2px;
        }
        .info-list.tutup .info-row i { color: #d97706; }
        .info-list .warning-row {
            background: #fff0d6;
            border-radius: 10px;
            padding: 8px 10px;
            margin-top: 4px;
        }

        .btn-pinjam {
            background: linear-gradient(135deg, #00c6ff 0%, #0072ff 100%);
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 13px;
            font-weight: 700;
            letter-spacing: 0.2px;
            box-shadow: 0 8px 18px rgba(0, 114, 255, 0.35);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .btn-pinjam:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 12px 22px rgba(0, 114, 255, 0.45);
            color: #fff;
        }
        .btn-pinjam:disabled {
            background: #b9c4d0;
            box-shadow: none;
            opacity: 0.85;
        }

        .btn-batal {
            border: 1.5px solid #cbd5e1;
            color: #475569;
            border-radius: 12px;
            padding: 12px;
            font-weight: 600;
            transition: background 0.15s ease;
        }
        .btn-batal:hover {
            background: #f1f5f9;
            color: #1e3c72;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow">
                    <div class="card-header text-white text-center">
                        <div class="header-icon">
                            <i class="bi bi-journal-check"></i>
                        </div>
                        <h4 class="mb-1 fw-bold position-relative">Konfirmasi Peminjaman Buku</h4>
                        <div class="tanggal-hari-ini">
                            <i class="bi bi-calendar-event me-1"></i><?= $hari_ini; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Apakah Anda yakin ingin meminjam buku berikut?</p>

                        <div class="buku-info-box mb-3">
                            <h5 class="fw-bold judul-buku mb-2"><?php echo htmlspecialchars($buku['judul'] ?? 'Judul Buku'); ?></h5>
                            <p class="mb-1 text-muted"><i class="bi bi-person-fill me-2"></i>Penulis: <?php echo htmlspecialchars($buku['pengarang'] ?? '-'); ?></p>
                            <p class="mb-0 text-muted"><i class="bi bi-building me-2"></i>Penerbit: <?php echo htmlspecialchars($buku['penerbit'] ?? '-'); ?></p>
                        </div>

                        <div class="info-list <?= $sedang_buka ? '' : 'tutup'; ?> mb-4">
                            <div class="info-row">
                                <i class="bi bi-calendar3"></i>
                                <div>Tanggal pinjam: <strong><?= $hari_ini; ?></strong></div>
                            </div>
                            <div class="info-row">
                                <i class="bi bi-hourglass-split"></i>
                                <div>Durasi peminjaman otomatis: <span class="badge-durasi">7 Hari</span></div>
                            </div>
                            <div class="info-row">
                                <i class="bi bi-clock-history"></i>
                                <div>Jam operasional: <strong><?= $jam_buka; ?> - <?= $jam_tutup; ?></strong></div>
                            </div>
                            <?php if (!$sedang_buka): ?>
                            <div class="warning-row">
                                <i class="bi bi-exclamation-triangle-fill me-1 text-warning"></i>
                                <strong class="text-dark">Perpustakaan sedang tutup, peminjaman tidak bisa dilakukan sekarang.</strong>
                            </div>
                            <?php endif; ?>
                        </div>

                        <form action="" method="POST">
                            <button type="submit" name="konfirmasi_pinjam" class="btn btn-pinjam w-100 mb-2" <?= $sedang_buka ? '' : 'disabled'; ?>>
                                <i class="bi bi-check2-circle me-1"></i> Ya, Pinjam Buku Ini
                            </button>
                            <a href="index.php" class="btn btn-batal w-100">
                                <i class="bi bi-arrow-left me-1"></i> Batal / Kembali
                            </a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php if ($pesan_gagal): ?>
<script>
Swal.fire({
    icon: 'error',
    title: 'Peminjaman Gagal',
    text: '<?= addslashes($pesan_gagal); ?>',
    confirmButtonText: 'Oke',
    confirmButtonColor: '#0072ff',
    customClass: { popup: 'rounded-4' }
});
</script>
<?php endif; ?>

</body>
</html>