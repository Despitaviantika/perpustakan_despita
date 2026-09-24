<?php
session_start();
include "../koneksi.php";

// Cek apakah user sudah login
if (!isset($_SESSION['id_anggota'])) {
    header("Location: ../login.php");
    exit;
}

$id = $_SESSION['id_anggota'];

$user = mysqli_query($koneksi, "SELECT * FROM anggota WHERE id_anggota='$id'");
$dataUser = mysqli_fetch_assoc($user);

// ==========================
// PROSES SIMPAN RATING BUKU
// ==========================
if (isset($_POST['kirim_rating'])) {
    $id_buku = intval($_POST['id_buku']);
    $nilai_rating = intval($_POST['rating']);
    $komentar = mysqli_real_escape_string($koneksi, $_POST['komentar']);

    if ($nilai_rating >= 1 && $nilai_rating <= 5) {
        // Cek apakah user sudah pernah merating buku ini sebelumnya
        $cek_rating = mysqli_query($koneksi, "SELECT * FROM rating WHERE id_anggota='$id' AND id_buku='$id_buku'");
        
        if (mysqli_num_rows($cek_rating) > 0) {
            // Update jika sudah ada
            mysqli_query($koneksi, "UPDATE rating SET rating='$nilai_rating', komentar='$komentar', tanggal=NOW() WHERE id_anggota='$id' AND id_buku='$id_buku'");
        } else {
            // Insert jika belum ada
            mysqli_query($koneksi, "INSERT INTO rating (id_anggota, id_buku, rating, komentar, tanggal) VALUES ('$id', '$id_buku', '$nilai_rating', '$komentar', NOW())");
        }
        header("Location: index.php?pesan=sukses_rating");
        exit;
    }
}

// ==========================
// PENCARIAN BUKU
// ==========================

if (isset($_GET['cari']) && $_GET['cari'] != "") {
    $cari = mysqli_real_escape_string($koneksi, $_GET['cari']);
    $buku = mysqli_query($koneksi, "SELECT * FROM buku WHERE judul LIKE '%$cari%' OR pengarang LIKE '%$cari%'");
} else {
    $buku = mysqli_query($koneksi, "SELECT * FROM buku");
}

// ==========================
// CARD DASHBOARD
// ==========================

$total_buku = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM buku"));
$dipinjam = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM transaksi WHERE id_anggota='$id' AND status='Dipinjam'"));
$riwayat = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM transaksi WHERE id_anggota='$id'"));

// ==========================
// URL DASAR (untuk isi QR Code, harus alamat lengkap biar bisa di-scan dari HP)
// ==========================
$protokol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$folder_saat_ini = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'); // folder tempat file ini berada (user/)
$base_url = $protokol . '://' . $host . $folder_saat_ini;

// ==========================
// DATA UNTUK QR KARTU ANGGOTA (unik per user yang login)
// ==========================
// Isi QR: ID anggota (dipakai sebagai kode unik).
// Kalau nanti QR ini mau discan petugas buat absen/pinjam otomatis,
// isi $info_qr_user cukup id_anggota-nya saja (jangan data lain) supaya gampang diproses sistem.
$nama_user    = $dataUser['nama'] ?? $_SESSION['nama'] ?? 'Siswa';
$nis_user     = $dataUser['nis_nip'] ?? $dataUser['nis'] ?? $_SESSION['nis_nip'] ?? '-';
$kelas_user   = $dataUser['kelas'] ?? $_SESSION['kelas'] ?? '-';
$info_qr_user = $id; // isi QR = id_anggota. Bisa diganti "ANGGOTA:$id" kalau mau ada penanda.

// ==========================
// MAPPING PESAN NOTIFIKASI (untuk SweetAlert2)
// ==========================
$notif = null;
if (isset($_GET['pesan'])) {
    switch ($_GET['pesan']) {
        case 'sukses_rating':
            $notif = [
                'icon'  => 'success',
                'title' => 'Rating Tersimpan!',
                'text'  => 'Terima kasih, rating dan ulasan buku berhasil disimpan.'
            ];
            break;
        case 'sukses_pinjam':
            $notif = [
                'icon'  => 'success',
                'title' => 'Peminjaman Berhasil!',
                'text'  => 'Buku sudah berhasil kamu pinjam. Selamat membaca 📚'
            ];
            break;
        case 'gagal_pinjam':
            $notif = [
                'icon'  => 'error',
                'title' => 'Peminjaman Gagal',
                'text'  => 'Stok buku habis atau terjadi kesalahan, coba lagi ya.'
            ];
            break;
        case 'buku_tidak_ada':
            $notif = [
                'icon'  => 'error',
                'title' => 'Buku Tidak Ditemukan',
                'text'  => 'Buku yang kamu pilih tidak tersedia atau sudah dihapus.'
            ];
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard User - Perpustakaan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,600;0,9..144,700&family=Source+Serif+4:wght@400;500;600&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root{
            --ink: #03045e;
            --ink-soft: #0077b6;
            --accent: #00b4d8;
            --accent-light: #90e0ef;
            --paper: #f3f7fb;
            --card-line: rgba(3,4,94,0.10);
        }

        body { 
            background: var(--paper);
            font-family: 'Source Serif 4', Georgia, serif;
            color: var(--ink);
        }

        h1, h2, h3, h4, h5, h6, .navbar-brand, .sidebar-header {
            font-family: 'Fraunces', Georgia, serif;
        }

        /* LAYOUT UTAMA DENGAN SIDEBAR */
        .wrapper {
            display: flex;
            width: 100%;
            align-items: stretch;
        }

        /* SIDEBAR STYLING */
        #sidebar {
            min-width: 250px;
            max-width: 250px;
            background: linear-gradient(180deg, #03045e 0%, #0a1f5c 100%);
            color: #fff;
            position: fixed;
            height: 100vh;
            z-index: 1050;
            top: 0;
            left: 0;
            overflow-y: auto;
            transition: margin-left .3s ease;
            border-right: 3px solid var(--accent-light);
        }

        #sidebar .sidebar-header {
            padding: 22px 20px;
            background: rgba(0,0,0,0.18);
            font-size: 1.2rem;
            font-weight: 700;
            letter-spacing: .01em;
        }

        #sidebar ul.components {
            padding: 18px 0;
        }

        #sidebar ul li a {
            padding: 12px 20px;
            font-size: .88rem;
            font-family: 'JetBrains Mono', monospace;
            letter-spacing: .02em;
            display: block;
            color: rgba(238,247,252,0.78);
            text-decoration: none;
            transition: 0.2s;
            border-left: 3px solid transparent;
        }

        #sidebar ul li a:hover, #sidebar ul li.active > a {
            color: #fff;
            background: rgba(0,180,216,0.16);
            border-left-color: var(--accent-light);
        }

        #sidebar ul li a i {
            margin-right: 10px;
        }

        /* KONTEN UTAMA DIGESER KANAN AGAR TIDAK TERTUTUP SIDEBAR */
        #content {
            width: calc(100% - 250px);
            margin-left: 250px;
            min-height: 100vh;
            transition: margin-left .3s ease, width .3s ease;
        }

        .navbar { 
            background: linear-gradient(120deg, #03045e 0%, #0077b6 100%) !important; 
            box-shadow: 0 4px 14px rgba(3,4,94,0.22);
        }

        .navbar-brand{ font-weight: 700; letter-spacing: .01em; }

        .card { 
            border: 1px solid var(--card-line);
            border-radius: 12px; 
            box-shadow: 0 6px 18px rgba(3,4,94,0.06) !important;
        }
        .info-card { 
            transition: .3s; 
        }
        .info-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 12px 26px rgba(3,4,94,0.14) !important;
        }
        .info-card h5{
            font-family: 'JetBrains Mono', monospace;
            font-size: .82rem;
            letter-spacing: .03em;
            text-transform: uppercase;
        }

        .bg-gradasi {
            background: linear-gradient(120deg, #03045e 0%, #0077b6 100%) !important;
            color: #ffffff !important;
        }

        .btn-gradasi {
            background: linear-gradient(120deg, #0077b6 0%, #00b4d8 100%);
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-weight: 600;
        }
        .btn-gradasi:hover {
            background: linear-gradient(120deg, #03045e 0%, #0077b6 100%);
            color: #ffffff;
        }

        .book-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            height: 100%;
            border-radius: 12px;
        }
        .book-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 22px rgba(3,4,94,0.12);
        }
        .book-img {
            width: 100px;
            height: 140px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(3,4,94,0.2);
        }

        /* Star Rating Input Styling */
        .rating-input {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
        }
        .rating-input input {
            display: none;
        }
        .rating-input label {
            font-size: 1.5rem;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s;
        }
        .rating-input input:checked ~ label,
        .rating-input label:hover,
        .rating-input label:hover ~ label {
            color: #00b4d8;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--ink-soft);
            box-shadow: 0 0 0 0.2rem rgba(0, 119, 182, 0.18);
        }

        .modal-content{
            border-radius: 12px;
            overflow: hidden;
        }

        /* Tombol QR Kartu Anggota di card Selamat Datang */
        .btn-qr-anggota {
            border: 1.5px solid var(--ink-soft);
            color: var(--ink-soft);
            background: #fff;
            border-radius: 8px;
            font-weight: 600;
            font-size: .85rem;
            padding: 8px 16px;
            transition: .2s;
        }
        .btn-qr-anggota:hover {
            background: var(--ink-soft);
            color: #fff;
        }

        /* Tombol buka/tutup sidebar khusus tampilan HP */
        #sidebarToggle {
            display: none;
            background: transparent;
            border: none;
            color: #fff;
            font-size: 1.4rem;
        }

        /* ===== RESPONSIVE: TAMPILAN HP/TABLET ===== */
        @media (max-width: 991.98px) {
            #sidebar {
                margin-left: -250px;
            }
            #sidebar.active {
                margin-left: 0;
            }
            #content {
                width: 100%;
                margin-left: 0;
            }
            #sidebarToggle {
                display: inline-block;
            }
            .navbar .navbar-brand {
                font-size: 1rem;
            }
        }

        @media (max-width: 575.98px) {
            .container, .container-fluid {
                padding-left: 12px;
                padding-right: 12px;
            }
            .navbar span.text-white {
                font-size: 0.8rem;
            }
            .card-body.p-4 {
                padding: 1rem !important;
            }
            .book-card .card-body {
                flex-direction: column;
                align-items: center !important;
                text-align: center;
            }
            .book-card .text-center.me-3 {
                margin-right: 0 !important;
                margin-bottom: 10px;
            }
            .book-img {
                width: 90px;
                height: 125px;
            }
        }
    </style>
</head>
<body>

<div class="wrapper">
    <nav id="sidebar">
        <div class="sidebar-header">
            <i class="bi bi-book-half me-2"></i>Perpustakaan
        </div>
        <ul class="list-unstyled components">
            <li class="active">
                <a href="index.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
            </li>
            <li>
                <a href="profil.php"><i class="bi bi-person-circle"></i> Profil Saya</a>
            </li>
            <li>
                <a href="riwayat.php"><i class="bi bi-clock-history"></i> Riwayat Peminjaman</a>
            </li>
            <li>
                <a href="#" data-bs-toggle="modal" data-bs-target="#panduanModal"><i class="bi bi-journal-text"></i> Panduan / Bantuan</a>
            </li>
            <li>
                <a href="../logout.php" id="btnLogout"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </li>
        </ul>
    </nav>

    <div id="content">
        <nav class="navbar navbar-dark navbar-expand-lg sticky-top px-4">
            <div class="container-fluid">
                <button id="sidebarToggle" type="button" class="me-2">
                    <i class="bi bi-list"></i>
                </button>
                <span class="navbar-brand fw-bold">Panel Siswa</span>
                <div class="ms-auto">
                    <span class="text-white">Halo, <b><?= htmlspecialchars($nama_user); ?></b></span>
                </div>
            </div>
        </nav>

        <div class="container mt-4 mb-5">

            <div class="card shadow mb-4">
                <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <h3 class="fw-bold text-dark">Selamat Datang 👋</h3>
                        <p class="mb-1"><b>NIS / NIP :</b> <?= htmlspecialchars($nis_user); ?></p>
                        <p class="mb-0"><b>Kelas :</b> <?= htmlspecialchars($kelas_user); ?></p>
                    </div>
                    <div>
                        <button type="button" class="btn btn-qr-anggota"
                                onclick="tampilkanQRAnggota('<?= addslashes($info_qr_user); ?>', '<?= addslashes($nama_user); ?>', '<?= addslashes($nis_user); ?>')">
                            <i class="bi bi-qr-code me-1"></i> QR Kartu Anggota
                        </button>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="card shadow info-card">
                        <div class="card-body text-center p-4">
                            <h5 class="text-muted"><i class="bi bi-journals text-primary me-2"></i>Total Buku</h5>
                            <h2 class="fw-bold mb-0"><?= $total_buku['total'] ?? 0; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="card shadow info-card">
                        <div class="card-body text-center p-4">
                            <h5 class="text-muted"><i class="bi bi-book text-warning me-2"></i>Sedang Dipinjam</h5>
                            <h2 class="fw-bold mb-0"><?= $dipinjam['total'] ?? 0; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow info-card">
                        <div class="card-body text-center p-4">
                            <h5 class="text-muted"><i class="bi bi-receipt text-success me-2"></i>Riwayat</h5>
                            <h2 class="fw-bold mb-0"><?= $riwayat['total'] ?? 0; ?></h2>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow">
                <div class="card-header bg-gradasi py-3 fw-bold fs-5">
                    <i class="bi bi-list-stars me-2"></i>Daftar Koleksi Buku & Rating
                </div>
                <div class="card-body p-4">
                    
                    <form method="GET" action="" class="mb-4">
                        <div class="input-group">
                            <input type="text" name="cari" class="form-control" placeholder="Cari Judul atau Pengarang..." value="<?= htmlspecialchars($_GET['cari'] ?? ''); ?>">
                            <button class="btn btn-gradasi fw-semibold" type="submit"><i class="bi bi-search"></i> Cari</button>
                            <?php if (isset($_GET['cari']) && $_GET['cari'] != ''): ?>
                                <a href="index.php" class="btn btn-secondary">Reset</a>
                            <?php endif; ?>
                        </div>
                    </form>

                    <div class="row">
                        <?php
                        if ($buku && mysqli_num_rows($buku) > 0) {
                            while ($b = mysqli_fetch_assoc($buku)) {
                                $gambar = !empty($b['gambar']) ? $b['gambar'] : 'default.jpg';
                                $stok = $b['stok'] ?? 0;
                                $id_buku = $b['id_buku'];
                                
                                // Menangkap data kualitas buku (mengatasi berbagai kemungkinan nama kolom)
                                $kualitas = $b['kualitas'] ?? $b['kondisi'] ?? 'Baik';

                                // Menghitung Rata-rata Rating Buku
                                $q_avg = mysqli_query($koneksi, "SELECT AVG(rating) as rata, COUNT(*) as jml FROM rating WHERE id_buku='$id_buku'");
                                $d_avg = mysqli_fetch_assoc($q_avg);
                                $rata_rating = round($d_avg['rata'], 1);
                                $jumlah_penilai = $d_avg['jml'];

                                // Cek rating yang sudah diberikan user ini pada buku tersebut
                                $q_user_rating = mysqli_query($koneksi, "SELECT * FROM rating WHERE id_anggota='$id' AND id_buku='$id_buku'");
                                $d_user_rating = mysqli_fetch_assoc($q_user_rating);
                                $user_score = $d_user_rating['rating'] ?? 0;
                        ?>
                        <div class="col-md-6 mb-3">
                            <div class="card book-card border shadow-sm">
                                <div class="card-body d-flex align-items-start p-3">
                                    <div class="text-center me-3" style="min-width: 100px;">
                                        <img src="../gambar/<?= htmlspecialchars($gambar); ?>" class="book-img mb-2" onerror="this.onerror=null; this.src='https://via.placeholder.com/90x125?text=No+Cover';">
                                        
                                        <?php if ($stok > 0) { ?>
                                            <a href="pinjam.php?id=<?= $id_buku; ?>" class="btn btn-gradasi btn-sm fw-semibold w-100 py-1 mb-1">
                                                <i class="bi bi-bookmark-plus me-1"></i> Pinjam
                                            </a>
                                        <?php } else { ?>
                                            <button class="btn btn-secondary btn-sm w-100 py-1 mb-1" disabled>Habis</button>
                                        <?php } ?>

                                        <button type="button" class="btn btn-outline-warning btn-sm w-100 py-1 text-dark mb-1" data-bs-toggle="modal" data-bs-target="#ratingModal<?= $id_buku; ?>">
                                            <i class="bi bi-star-fill text-warning"></i> <?= $rata_rating > 0 ? $rata_rating : 'Beri Rating'; ?>
                                        </button>
                                    </div>

                                    <div class="flex-grow-1">
                                        <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($b['judul'] ?? '-'); ?></h6>
                                        <p class="mb-1 text-muted small"><i class="bi bi-person me-1"></i><b>Pengarang:</b> <?= htmlspecialchars($b['pengarang'] ?? '-'); ?></p>
                                        <p class="mb-1 text-muted small"><i class="bi bi-building me-1"></i><b>Penerbit:</b> <?= htmlspecialchars($b['penerbit'] ?? '-'); ?></p>
                                        <p class="mb-1 text-muted small"><i class="bi bi-calendar-event me-1"></i><b>Tahun:</b> <?= htmlspecialchars($b['tahun_terbit'] ?? $b['tahun'] ?? '-'); ?></p>
                                        
                                        <p class="mb-1 text-muted small"><i class="bi bi-shield-check me-1"></i><b>Kualitas:</b> <span class="badge bg-info text-dark"><?= htmlspecialchars($kualitas); ?></span></p>

                                        <div class="d-flex justify-content-between align-items-center mt-2 mb-1">
                                            <span class="small text-secondary">Stok: <b><?= $stok; ?></b></span>
                                            <?php if ($stok > 0) { ?>
                                                <span class="badge bg-success">Tersedia</span>
                                            <?php } else { ?>
                                                <span class="badge bg-danger">Habis</span>
                                            <?php } ?>
                                        </div>
                                        <div class="small text-warning">
                                            <?php 
                                            $full_stars = floor($rata_rating);
                                            for ($i = 1; $i <= 5; $i++) {
                                                if ($i <= $full_stars) {
                                                    echo '<i class="bi bi-star-fill"></i>';
                                                } else {
                                                    echo '<i class="bi bi-star"></i>';
                                                }
                                            }
                                            ?>
                                            <span class="text-muted ms-1">(<?= $jumlah_penilai; ?> ulasan)</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal fade" id="ratingModal<?= $id_buku; ?>" tabindex="-1" aria-labelledby="ratingModalLabel<?= $id_buku; ?>" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="" method="POST">
                                        <div class="modal-header bg-gradasi text-white">
                                            <h5 class="modal-title" id="ratingModalLabel<?= $id_buku; ?>">Beri Rating & Ulasan Buku</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="id_buku" value="<?= $id_buku; ?>">
                                            <p class="fw-bold mb-2"><?= htmlspecialchars($b['judul']); ?></p>
                                            
                                            <div class="mb-3 text-center">
                                                <label class="form-label d-block text-muted">Pilih Bintang</label>
                                                <div class="rating-input justify-content-center">
                                                    <?php for ($s = 5; $s >= 1; $s--): ?>
                                                        <input type="radio" id="star<?= $s; ?>_<?= $id_buku; ?>" name="rating" value="<?= $s; ?>" <?= ($user_score == $s) ? 'checked' : ''; ?> required>
                                                        <label for="star<?= $s; ?>_<?= $id_buku; ?>" title="<?= $s; ?> Bintang"><i class="bi bi-star-fill"></i></label>
                                                    <?php endfor; ?>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label for="komentar<?= $id_buku; ?>" class="form-label">Ulasan / Komentar</label>
                                                <textarea class="form-control" id="komentar<?= $id_buku; ?>" name="komentar" rows="3" placeholder="Bagaimana pendapatmu tentang buku ini?"><?= htmlspecialchars($d_user_rating['komentar'] ?? ''); ?></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" name="kirim_rating" class="btn btn-gradasi btn-sm">Simpan Ulasan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php 
                            } 
                        } else {
                        ?>
                        <div class="col-12">
                            <div class="text-center text-muted py-4">Data buku tidak ditemukan.</div>
                        </div>
                        <?php } ?>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="panduanModal" tabindex="-1" aria-labelledby="panduanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-gradasi text-white">
                <h5 class="modal-title" id="panduanModalLabel"><i class="bi bi-journal-text me-2"></i>Panduan Penggunaan Perpustakaan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6 class="fw-bold text-primary">1. Cara Mencari Buku</h6>
                <p class="small text-muted">Gunakan kolom pencarian pada halaman dashboard untuk mencari judul buku atau nama pengarang yang ingin Anda baca, lalu klik tombol <b>Cari</b>.</p>

                <h6 class="fw-bold text-primary">2. Memeriksa Kualitas / Kondisi Buku</h6>
                <p class="small text-muted">Setiap informasi buku mencantumkan label kualitas fisik buku (seperti Baik, Sangat Baik, dll.) agar Anda mengetahui kondisi buku sebelum melakukan peminjaman.</p>

                <h6 class="fw-bold text-primary">3. Cara Meminjam Buku</h6>
                <p class="small text-muted">Pastikan status stok buku <b>Tersedia</b>. Klik tombol <b>Pinjam</b> pada buku yang diinginkan, kemudian ikuti instruksi selanjutnya hingga proses peminjaman selesai tercatat.</p>

                <h6 class="fw-bold text-primary">4. Memeriksa Riwayat & Status Peminjaman</h6>
                <p class="small text-muted">Anda dapat mengecek buku apa saja yang sedang dipinjam atau melihat riwayat peminjaman sebelumnya melalui menu <b>Riwayat Peminjaman</b> di sidebar.</p>

                <h6 class="fw-bold text-primary">5. Memberikan Rating & Ulasan</h6>
                <p class="small text-muted">Klik tombol rating berupa ikon bintang di bawah gambar buku untuk memberikan penilaian (1 sampai 5 bintang) beserta ulasan pendapat Anda mengenai buku tersebut.</p>

                <h6 class="fw-bold text-primary">6. Kartu Anggota (QR Code)</h6>
                <p class="small text-muted">Klik tombol <b>QR Kartu Anggota</b> di bagian atas dashboard untuk menampilkan kode QR pribadi Anda. QR ini bisa dipindai petugas perpustakaan saat proses peminjaman atau pengembalian buku.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Toggle sidebar khusus tampilan HP/tablet
    document.getElementById('sidebarToggle')?.addEventListener('click', function () {
        document.getElementById('sidebar').classList.toggle('active');
    });

    // Konfirmasi logout dengan SweetAlert2
    document.getElementById('btnLogout')?.addEventListener('click', function (e) {
        e.preventDefault();
        const targetUrl = this.getAttribute('href');
        Swal.fire({
            icon: 'question',
            title: 'Yakin ingin keluar?',
            text: 'Kamu akan diarahkan kembali ke halaman login.',
            showCancelButton: true,
            confirmButtonText: 'Ya, Logout',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#0077b6',
            cancelButtonColor: '#6c757d',
            customClass: { popup: 'rounded-4' }
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = targetUrl;
            }
        });
    });

    // Menampilkan QR code kartu anggota, unik untuk tiap user yang login
    function tampilkanQRAnggota(idAnggota, nama, nis) {
        const urlQR = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' + encodeURIComponent(idAnggota);
        Swal.fire({
            title: 'Kartu Anggota Digital',
            html: `
                <img src="${urlQR}" alt="QR Kartu Anggota" style="width:220px;height:220px;">
                <p class="fw-bold mb-0 mt-3">${nama}</p>
                <p class="text-muted small mb-0">NIS/NIP: ${nis}</p>
                <p class="text-muted small mt-2 mb-0">Tunjukkan QR ini ke petugas saat pinjam/kembalikan buku</p>
            `,
            confirmButtonText: 'Tutup',
            confirmButtonColor: '#0077b6'
        });
    }
</script>

<?php if ($notif): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    Swal.fire({
        icon: '<?= $notif['icon']; ?>',
        title: '<?= addslashes($notif['title']); ?>',
        text: '<?= addslashes($notif['text']); ?>',
        confirmButtonText: 'Oke',
        confirmButtonColor: '#0077b6',
        customClass: { popup: 'rounded-4' }
    }).then(function () {
        // Bersihkan parameter ?pesan= dari URL biar notif tidak muncul lagi saat refresh
        const url = new URL(window.location.href);
        url.searchParams.delete('pesan');
        window.history.replaceState({}, document.title, url.toString());
    });
});
</script>
<?php endif; ?>

</body>
</html>