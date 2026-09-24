<?php
session_start();
include "../koneksi.php";

// Cek apakah sudah login sebagai admin
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit;
}

$username = $_SESSION['username'] ?? 'Admin';

// Nama file aktif untuk menandai menu sidebar yang sedang dibuka
$halaman_aktif = basename($_SERVER['PHP_SELF']);

// Mengambil data statistik aktual dari database
$q_buku = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM buku"));
$q_anggota = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM anggota"));
$q_transaksi = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM transaksi WHERE status='dipinjam'"));
$q_riwayat = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM transaksi"));
$q_petugas = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users WHERE role='petugas'"));
$q_denda = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT SUM(denda) as total FROM transaksi"));
$total_denda = $q_denda['total'] ?? 0;

// --- 1. DATA GRAFIK HARIAN (7 Hari Terakhir) ---
$labels_grafik = [];
$data_grafik = [];

for ($i = 6; $i >= 0; $i--) {
    $tanggal = date('Y-m-d', strtotime("-$i days"));
    $label_tgl = date('d M', strtotime("-$i days"));

    $q_harian = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM transaksi WHERE DATE(tgl_pinjam) = '$tanggal'");
    $d_harian = mysqli_fetch_assoc($q_harian);

    $labels_grafik[] = $label_tgl;
    $data_grafik[] = $d_harian['total'] ?? 0;
}

$json_labels = json_encode($labels_grafik);
$json_data = json_encode($data_grafik);


// --- 2. DATA GRAFIK BULANAN (Tahun Berjalan) ---
$data_per_bulan = array_fill(1, 12, 0); // Default nilai 0 untuk 12 bulan

$query_grafik_bulan = mysqli_query($koneksi, "
    SELECT MONTH(tgl_pinjam) as bulan, COUNT(*) as total 
    FROM transaksi 
    WHERE YEAR(tgl_pinjam) = YEAR(CURDATE()) 
    GROUP BY MONTH(tgl_pinjam)
");

while ($row = mysqli_fetch_assoc($query_grafik_bulan)) {
    $bulan_ke = (int)$row['bulan'];
    $data_per_bulan[$bulan_ke] = (int)$row['total'];
}

$json_data_bulan = json_encode(array_values($data_per_bulan));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Perpustakaan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        body { 
            background: #f4f7f6; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar-admin { 
            background: linear-gradient(135deg, #0d3b66 0%, #0077b6 100%) !important; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }
        .card { 
            border: none; 
            border-radius: 15px; 
            transition: .3s;
        }
        .card-clickable:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.12) !important;
            cursor: pointer;
        }
        .bg-admin-gradasi {
            background: linear-gradient(135deg, #0d3b66 0%, #0077b6 60%, #00b4d8 100%) !important;
            color: #ffffff !important;
            position: relative;
            overflow: hidden;
        }
        .bg-admin-gradasi::before {
            content: "";
            position: absolute;
            top: -40px;
            right: -40px;
            width: 160px;
            height: 160px;
            background: rgba(255,255,255,0.08);
            border-radius: 50%;
        }
        .bg-admin-gradasi .text-white-50 { color: rgba(255,255,255,0.75) !important; }
        .progress-bar-custom {
            height: 8px;
            border-radius: 4px;
        }
        .card-link {
            text-decoration: none;
        }

        /* ===== SIDEBAR ===== */
        :root {
            --sidebar-lebar: 250px;
            --tinggi-navbar: 62px;
        }
        .sidebar-admin {
            position: fixed;
            top: var(--tinggi-navbar);
            left: 0;
            bottom: 0;
            width: var(--sidebar-lebar);
            background: linear-gradient(180deg, #0d3b66 0%, #0077b6 100%);
            box-shadow: 2px 0 10px rgba(0,0,0,0.15);
            overflow-y: auto;
            z-index: 1035;
            transition: transform .25s ease;
            padding: 18px 0 30px;
        }
        .sidebar-judul {
            color: rgba(255,255,255,.5);
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .5px;
            padding: 0 20px;
            margin-bottom: 8px;
        }
        .sidebar-admin .nav-link {
            color: rgba(255,255,255,.82);
            padding: 11px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: .94rem;
            border-left: 4px solid transparent;
            transition: .2s;
        }
        .sidebar-admin .nav-link i {
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
        }
        .sidebar-admin .nav-link:hover {
            background: rgba(255,255,255,.12);
            color: #fff;
        }
        .sidebar-admin .nav-link.aktif {
            background: rgba(255,255,255,.2);
            color: #fff;
            font-weight: 600;
            border-left-color: #ffc107;
            box-shadow: none;
        }
        .sidebar-admin .nav-link:focus-visible {
            outline: 2px solid #ffffff;
            outline-offset: -2px;
        }
        .sidebar-pemisah {
            border-top: 1px solid rgba(255,255,255,.15);
            margin: 14px 20px;
        }

        /* Widget Total Denda di sidebar */
        .widget-denda {
            margin: 4px 20px 18px;
            border-radius: 12px;
            padding: 16px;
            background: linear-gradient(135deg, #0077b6 0%, #00b4d8 100%);
            box-shadow: 0 4px 14px rgba(0,0,0,0.18);
        }
        .widget-denda .label-denda {
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .5px;
            text-transform: uppercase;
            color: #ffffff;
            opacity: .85;
        }
        .widget-denda .nilai-denda {
            font-size: 1.28rem;
            font-weight: 800;
            color: #ffffff;
            text-shadow: 0 1px 3px rgba(0,0,0,0.25);
            margin: 4px 0 8px;
        }
        .widget-denda a {
            color: #ffffff;
            font-size: .78rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .widget-denda a:hover {
            text-decoration: underline;
        }

        /* Konten digeser ke kanan supaya tidak tertutup sidebar */
        .konten-utama {
            margin-left: var(--sidebar-lebar);
        }

        .tombol-sidebar { display: none; }
        .overlay-sidebar {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.45);
            z-index: 1030;
            display: none;
        }
        .overlay-sidebar.tampil { display: block; }

        /* Mobile: sidebar disembunyikan, dibuka lewat tombol menu */
        @media (max-width: 991.98px) {
            .konten-utama { margin-left: 0; }
            .sidebar-admin { transform: translateX(-100%); }
            .sidebar-admin.buka { transform: translateX(0); }
            .tombol-sidebar { display: inline-flex; }
        }
        @media (prefers-reduced-motion: reduce) {
            .sidebar-admin { transition: none; }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-dark navbar-expand-lg navbar-admin fixed-top">
    <div class="container-fluid px-3 px-lg-4">
        <div class="d-flex align-items-center">
            <button class="btn btn-outline-light btn-sm me-2 tombol-sidebar" type="button" onclick="bukaTutupSidebar()" aria-label="Buka menu">
                <i class="bi bi-list"></i>
            </button>
            <span class="navbar-brand fw-bold mb-0"><i class="bi bi-shield-shaded me-2 text-warning"></i>Admin Perpustakaan</span>
        </div>
        <div class="d-flex align-items-center">
            <span class="text-white me-3 d-none d-md-inline">Halo, <b><?= htmlspecialchars($username); ?></b> (Administrator)</span>
            <a href="#" onclick="konfirmasiLogout(event)" class="btn btn-outline-light btn-sm"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
    </div>
</nav>

<aside class="sidebar-admin" id="sidebarAdmin">
    <div class="sidebar-judul">MENU UTAMA</div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a href="index.php" class="nav-link <?= $halaman_aktif == 'index.php' ? 'aktif' : ''; ?>">
                <i class="bi bi-speedometer2"></i> Beranda
            </a>
        </li>
        <li class="nav-item">
            <a href="buku.php" class="nav-link <?= $halaman_aktif == 'buku.php' ? 'aktif' : ''; ?>">
                <i class="bi bi-journal-bookmark-fill"></i> Data Buku
            </a>
        </li>
        <li class="nav-item">
            <a href="anggota.php" class="nav-link <?= $halaman_aktif == 'anggota.php' ? 'aktif' : ''; ?>">
                <i class="bi bi-people-fill"></i> Data Anggota
            </a>
        </li>
        <li class="nav-item">
            <a href="transaksi.php" class="nav-link <?= $halaman_aktif == 'transaksi.php' ? 'aktif' : ''; ?>">
                <i class="bi bi-arrow-left-right"></i> Transaksi
            </a>
        </li>
        <li class="nav-item">
            <a href="rating.php" class="nav-link <?= $halaman_aktif == 'rating.php' ? 'aktif' : ''; ?>">
                <i class="bi bi-star-fill"></i> Rating &amp; Ulasan
            </a>
        </li>
    </ul>

    <div class="sidebar-pemisah"></div>

    <div class="widget-denda">
        <div class="label-denda"><i class="bi bi-cash-coin me-1"></i>Total Denda</div>
        <div class="nilai-denda">Rp <?= number_format($total_denda, 0, ',', '.'); ?></div>
        <a href="rincian_denda.php">Lihat rincian <i class="bi bi-arrow-right-short"></i></a>
    </div>

    <div class="sidebar-judul">PENGATURAN</div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a href="atur_password_petugas.php" class="nav-link <?= $halaman_aktif == 'atur_password_petugas.php' ? 'aktif' : ''; ?>">
                <i class="bi bi-key-fill"></i> Password Petugas
            </a>
        </li>
        <li class="nav-item">
            <a href="#" onclick="konfirmasiLogout(event)" class="nav-link">
                <i class="bi bi-box-arrow-right"></i> Keluar
            </a>
        </li>
    </ul>
</aside>

<div class="overlay-sidebar" id="overlaySidebar" onclick="bukaTutupSidebar()"></div>

<div class="konten-utama">
<div class="container-fluid px-3 px-lg-4 mb-5" style="padding-top: 80px;">

    <div class="card shadow mb-4 bg-admin-gradasi">
        <div class="card-body p-4 position-relative" style="z-index:1;">
            <h3 class="fw-bold">Dashboard Pusat Administrator 📊</h3>
            <p class="mb-0 text-white-50">Selamat datang kembali, <b><?= htmlspecialchars($username); ?></b>. Grafik di bawah otomatis merekam aktivitas peminjaman buku secara real-time.</p>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3 mb-3 mb-md-0">
            <a href="buku.php" class="card-link">
                <div class="card card-clickable shadow p-3 border-start border-primary border-4 h-100">
                    <div class="text-muted small fw-bold text-uppercase">Total Buku</div>
                    <div class="d-flex align-items-center justify-content-between mt-2">
                        <h2 class="fw-bold mb-0 text-dark"><?= $q_buku['total'] ?? 0; ?></h2>
                        <i class="bi bi-journal-bookmark-fill text-primary fs-2"></i>
                    </div>
                    <div class="progress mt-3 progress-bar-custom bg-light">
                        <div class="progress-bar bg-primary" style="width: 100%"></div>
                    </div>
                    <small class="text-primary mt-2 d-block fw-semibold"><i class="bi bi-arrow-right-circle"></i> Kelola Buku</small>
                </div>
            </a>
        </div>

        <div class="col-md-3 mb-3 mb-md-0">
            <a href="anggota.php" class="card-link">
                <div class="card card-clickable shadow p-3 border-start border-success border-4 h-100">
                    <div class="text-muted small fw-bold text-uppercase">Total Anggota</div>
                    <div class="d-flex align-items-center justify-content-between mt-2">
                        <h2 class="fw-bold mb-0 text-dark"><?= $q_anggota['total'] ?? 0; ?></h2>
                        <i class="bi bi-people-fill text-success fs-2"></i>
                    </div>
                    <div class="progress mt-3 progress-bar-custom bg-light">
                        <div class="progress-bar bg-success" style="width: 100%"></div>
                    </div>
                    <small class="text-success mt-2 d-block fw-semibold"><i class="bi bi-arrow-right-circle"></i> Kelola Anggota</small>
                </div>
            </a>
        </div>

        <div class="col-md-3 mb-3 mb-md-0">
            <a href="transaksi.php" class="card-link">
                <div class="card card-clickable shadow p-3 border-start border-warning border-4 h-100">
                    <div class="text-muted small fw-bold text-uppercase">Buku Dipinjam</div>
                    <div class="d-flex align-items-center justify-content-between mt-2">
                        <h2 class="fw-bold mb-0 text-dark"><?= $q_transaksi['total'] ?? 0; ?></h2>
                        <i class="bi bi-book-fill text-warning fs-2"></i>
                    </div>
                    <div class="progress mt-3 progress-bar-custom bg-light">
                        <div class="progress-bar bg-warning" style="width: 70%"></div>
                    </div>
                    <small class="text-warning mt-2 d-block fw-semibold"><i class="bi bi-arrow-right-circle"></i> Lihat Transaksi</small>
                </div>
            </a>
        </div>

        <div class="col-md-3">
            <a href="transaksi.php" class="card-link">
                <div class="card card-clickable shadow p-3 border-start border-info border-4 h-100">
                    <div class="text-muted small fw-bold text-uppercase">Total Transaksi</div>
                    <div class="d-flex align-items-center justify-content-between mt-2">
                        <h2 class="fw-bold mb-0 text-dark"><?= $q_riwayat['total'] ?? 0; ?></h2>
                        <i class="bi bi-receipt text-info fs-2"></i>
                    </div>
                    <div class="progress mt-3 progress-bar-custom bg-light">
                        <div class="progress-bar bg-info" style="width: 85%"></div>
                    </div>
                    <small class="text-info mt-2 d-block fw-semibold"><i class="bi bi-arrow-right-circle"></i> Riwayat Transaksi</small>
                </div>
            </a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <a href="atur_password_petugas.php" class="card-link">
                <div class="card card-clickable shadow p-3 border-start border-dark border-4 h-100">
                    <div class="text-muted small fw-bold text-uppercase">Total Petugas</div>
                    <div class="d-flex align-items-center justify-content-between mt-2">
                        <h2 class="fw-bold mb-0 text-dark"><?= $q_petugas['total'] ?? 0; ?></h2>
                        <i class="bi bi-key-fill text-dark fs-2"></i>
                    </div>
                    <div class="progress mt-3 progress-bar-custom bg-light">
                        <div class="progress-bar bg-dark" style="width: 100%"></div>
                    </div>
                    <small class="text-dark mt-2 d-block fw-semibold"><i class="bi bi-arrow-right-circle"></i> Atur Password Petugas</small>
                </div>
            </a>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-bar-chart-fill me-2 text-primary"></i>Grafik Statistik Peminjaman 7 Hari Terakhir (Termasuk Hari Ini)</h5>
            <span class="badge bg-primary">Live Update</span>
        </div>
        <div class="card-body">
            <canvas id="grafikHarian" style="max-height: 320px;"></canvas>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-graph-up-arrow me-2 text-success"></i>Grafik Statistik Peminjaman Per Bulan (Tahun <?= date('Y'); ?>)</h5>
            <span class="badge bg-success">Tahun Ini</span>
        </div>
        <div class="card-body">
            <canvas id="grafikBulanan" style="max-height: 320px;"></canvas>
        </div>
    </div>

</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Menyesuaikan posisi sidebar dengan tinggi navbar sebenarnya
    function aturTinggiNavbar() {
        const nav = document.querySelector('.navbar-admin');
        if (nav) {
            document.documentElement.style.setProperty('--tinggi-navbar', nav.offsetHeight + 'px');
        }
    }
    window.addEventListener('load', aturTinggiNavbar);
    window.addEventListener('resize', aturTinggiNavbar);

    // Buka / tutup sidebar di layar kecil
    function bukaTutupSidebar() {
        document.getElementById('sidebarAdmin').classList.toggle('buka');
        document.getElementById('overlaySidebar').classList.toggle('tampil');
    }

    // Konfirmasi logout pakai SweetAlert2
    function konfirmasiLogout(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Yakin ingin keluar?',
            text: 'Kamu akan keluar dari sesi admin ini.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#0077b6',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, keluar',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "../logout.php";
            }
        });
    }

    // 1. Skrip Grafik Harian
    const labelsHari = <?= $json_labels; ?>;
    const dataHarian = <?= $json_data; ?>;

    const ctxHarian = document.getElementById('grafikHarian').getContext('2d');
    new Chart(ctxHarian, {
        type: 'bar',
        data: {
            labels: labelsHari,
            datasets: [{
                label: 'Jumlah Buku Dipinjam',
                data: dataHarian,
                backgroundColor: 'rgba(0, 119, 182, 0.75)',
                borderColor: 'rgba(0, 119, 182, 1)',
                borderWidth: 2,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                }
            }
        }
    });

    // 2. Skrip Grafik Bulanan
    const dataBulanan = <?= $json_data_bulan; ?>;

    const ctxBulanan = document.getElementById('grafikBulanan').getContext('2d');
    new Chart(ctxBulanan, {
        type: 'bar',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
            datasets: [{
                label: 'Jumlah Buku Dipinjam Per Bulan',
                data: dataBulanan,
                backgroundColor: 'rgba(25, 135, 84, 0.75)',
                borderColor: 'rgba(25, 135, 84, 1)',
                borderWidth: 2,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                }
            }
        }
    });
</script>

</body>
</html>