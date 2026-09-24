<?php
session_start();
include '../koneksi.php';

// Cek apakah sudah login dan rolenya benar-benar petugas
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'petugas') {
    header("Location: ../login.php");
    exit;
}

$username = $_SESSION['nama'] ?? 'Petugas';

// Nama file aktif, dipakai untuk menandai menu sidebar yang sedang dibuka
$halaman_aktif = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Petugas - Perpustakaan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background: #f4f7f6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Navbar disamakan dengan tema utama web (biru gradasi) */
        .navbar-petugas {
            background: linear-gradient(135deg, #0d3b66 0%, #0077b6 100%) !important;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }

        .badge-role {
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 20px;
            padding: 4px 14px;
            font-size: 0.8rem;
        }

        /* Kartu sambutan dengan gradasi lembut */
        .welcome-card {
            background: linear-gradient(135deg, #0d3b66 0%, #0077b6 60%, #00b4d8 100%);
            border-radius: 18px;
            color: #fff;
            position: relative;
            overflow: hidden;
        }
        .welcome-card::before {
            content: "";
            position: absolute;
            top: -40px;
            right: -40px;
            width: 160px;
            height: 160px;
            background: rgba(255,255,255,0.08);
            border-radius: 50%;
        }
        .welcome-card::after {
            content: "";
            position: absolute;
            bottom: -60px;
            right: 60px;
            width: 120px;
            height: 120px;
            background: rgba(255,255,255,0.06);
            border-radius: 50%;
        }

        /* Kartu menu */
        .card {
            border: none;
            border-radius: 16px;
        }
        .menu-card {
            transition: transform .25s ease, box-shadow .25s ease;
            overflow: hidden;
            position: relative;
        }
        .menu-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 25px rgba(0, 119, 182, 0.18) !important;
        }

        .icon-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            color: #fff;
            margin-bottom: 14px;
        }

        .icon-transaksi { background: linear-gradient(135deg, #0077b6, #00b4d8); }
        .icon-buku      { background: linear-gradient(135deg, #023e8a, #0077b6); }
        .icon-anggota   { background: linear-gradient(135deg, #0096c7, #48cae4); }
        .icon-scan      { background: linear-gradient(135deg, #f77f00, #fcbf49); }

        .btn-gradasi {
            background: linear-gradient(135deg, #0077b6 0%, #00b4d8 100%);
            color: #fff;
            border: none;
            transition: .2s;
        }
        .btn-gradasi:hover {
            background: linear-gradient(135deg, #023e8a 0%, #0077b6 100%);
            color: #fff;
        }

        .menu-tag {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #0077b6;
            font-weight: 600;
        }

        /* ===== SIDEBAR ===== */
        :root {
            --lebar-sidebar: 250px;
        }
        .sidebar-petugas {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--lebar-sidebar);
            height: 100vh;
            background: linear-gradient(180deg, #0d3b66 0%, #0077b6 100%);
            box-shadow: 4px 0 10px rgba(0,0,0,0.15);
            z-index: 1045;
            display: flex;
            flex-direction: column;
            transition: transform .3s ease;
        }
        .sidebar-brand {
            padding: 20px 18px;
            color: #fff;
            font-weight: 700;
            font-size: 1.05rem;
            border-bottom: 1px solid rgba(255,255,255,.15);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .sidebar-menu {
            padding: 14px 12px;
            overflow-y: auto;
            flex: 1;
        }
        .sidebar-menu .judul-grup {
            color: rgba(255,255,255,.5);
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .06em;
            padding: 12px 10px 6px;
        }
        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 12px;
            margin-bottom: 4px;
            border-radius: 10px;
            color: rgba(255,255,255,.82);
            text-decoration: none;
            font-size: .93rem;
            transition: background .2s, color .2s;
        }
        .sidebar-menu a i {
            font-size: 1.05rem;
        }
        .sidebar-menu a:hover {
            background: rgba(255,255,255,.12);
            color: #fff;
        }
        .sidebar-menu a.aktif {
            background: rgba(255,255,255,.2);
            color: #fff;
            font-weight: 600;
        }
        .sidebar-footer {
            padding: 14px;
            border-top: 1px solid rgba(255,255,255,.15);
        }

        /* Area konten digeser ke kanan supaya tidak tertutup sidebar */
        .konten-utama {
            margin-left: var(--lebar-sidebar);
            min-height: 100vh;
        }

        .tombol-sidebar { display: none; }
        .overlay-sidebar {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.45);
            z-index: 1040;
            display: none;
        }
        .overlay-sidebar.tampil { display: block; }

        /* Mobile: sidebar disembunyikan, dibuka lewat tombol di navbar */
        @media (max-width: 991.98px) {
            .sidebar-petugas { transform: translateX(-100%); }
            .sidebar-petugas.buka { transform: translateX(0); }
            .konten-utama { margin-left: 0; }
            .tombol-sidebar { display: inline-flex; }
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar-petugas" id="sidebarPetugas">
    <div class="sidebar-brand">
        <i class="bi bi-person-badge-fill text-warning"></i> Panel Petugas
    </div>
    <nav class="sidebar-menu">
        <div class="judul-grup">Menu Utama</div>
        <a href="index.php" class="<?= $halaman_aktif == 'index.php' ? 'aktif' : ''; ?>">
            <i class="bi bi-house-door-fill"></i> Beranda
        </a>
        <a href="scan_pengembalian.php" class="<?= $halaman_aktif == 'scan_pengembalian.php' ? 'aktif' : ''; ?>">
            <i class="bi bi-qr-code-scan"></i> Scan Pengembalian
        </a>
        <a href="transaksi.php" class="<?= $halaman_aktif == 'transaksi.php' ? 'aktif' : ''; ?>">
            <i class="bi bi-arrow-left-right"></i> Manajemen Transaksi
        </a>
        <a href="buku.php" class="<?= $halaman_aktif == 'buku.php' ? 'aktif' : ''; ?>">
            <i class="bi bi-journal-bookmark"></i> Cek Data Buku
        </a>
        <a href="anggota.php" class="<?= $halaman_aktif == 'anggota.php' ? 'aktif' : ''; ?>">
            <i class="bi bi-people"></i> Manajemen Anggota
        </a>
    </nav>
    <div class="sidebar-footer">
        <a href="../logout.php" id="btnLogoutSidebar" class="btn btn-outline-light btn-sm w-100">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>
</aside>
<div class="overlay-sidebar" id="overlaySidebar" onclick="tutupSidebar()"></div>

<div class="konten-utama">

<nav class="navbar navbar-dark navbar-expand-lg navbar-petugas py-3">
    <div class="container">
        <button class="btn btn-outline-light btn-sm tombol-sidebar me-2" type="button" onclick="bukaSidebar()">
            <i class="bi bi-list"></i>
        </button>
        <span class="navbar-brand fw-bold"><i class="bi bi-person-badge-fill me-2 text-warning"></i>Panel Petugas Perpustakaan</span>
        <div class="d-flex align-items-center">
            <span class="text-white me-3">Halo, <b><?= htmlspecialchars($username); ?></b> <span class="badge-role text-white ms-1">Petugas</span></span>
            <a href="../logout.php" id="btnLogout" class="btn btn-outline-light btn-sm"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-4 mb-5">
    <div class="card welcome-card shadow mb-4">
        <div class="card-body p-4 position-relative" style="z-index:1;">
            <h4 class="fw-bold mb-1">Selamat Datang, <?= htmlspecialchars($username); ?>! 👋</h4>
            <p class="mb-0" style="opacity:.9;">Panel operasional harian untuk melayani transaksi peminjaman, pengembalian buku, dan pengecekan data buku.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3 col-6 mb-3">
            <div class="card menu-card shadow-sm p-4 h-100">
                <div class="icon-circle icon-scan">
                    <i class="bi bi-qr-code-scan"></i>
                </div>
                <span class="menu-tag mb-1">Operasional</span>
                <h5 class="fw-bold text-dark">Scan Pengembalian</h5>
                <p class="text-muted small">Scan QR kartu anggota untuk memproses pengembalian buku dengan cepat.</p>
                <a href="scan_pengembalian.php" class="btn btn-gradasi btn-sm mt-2 w-100 fw-semibold">
                    Mulai Scan <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        </div>

        <div class="col-md-3 col-6 mb-3">
            <div class="card menu-card shadow-sm p-4 h-100">
                <div class="icon-circle icon-transaksi">
                    <i class="bi bi-arrow-left-right"></i>
                </div>
                <span class="menu-tag mb-1">Operasional</span>
                <h5 class="fw-bold text-dark">Manajemen Transaksi</h5>
                <p class="text-muted small">Catat peminjaman buku baru atau proses pengembalian buku beserta pengecekan denda.</p>
                <a href="transaksi.php" class="btn btn-gradasi btn-sm mt-2 w-100 fw-semibold">
                    Buka Transaksi <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        </div>

        <div class="col-md-3 col-6 mb-3">
            <div class="card menu-card shadow-sm p-4 h-100">
                <div class="icon-circle icon-buku">
                    <i class="bi bi-journal-bookmark"></i>
                </div>
                <span class="menu-tag mb-1">Koleksi</span>
                <h5 class="fw-bold text-dark">Cek Data Buku</h5>
                <p class="text-muted small">Periksa ketersediaan stok judul buku yang ada di rak perpustakaan.</p>
                <a href="buku.php" class="btn btn-gradasi btn-sm mt-2 w-100 fw-semibold">
                    Lihat Data Buku <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        </div>

        <div class="col-md-3 col-6 mb-3">
            <div class="card menu-card shadow-sm p-4 h-100">
                <div class="icon-circle icon-anggota">
                    <i class="bi bi-people"></i>
                </div>
                <span class="menu-tag mb-1">Keanggotaan</span>
                <h5 class="fw-bold text-dark">Manajemen Anggota</h5>
                <p class="text-muted small">Tambah data anggota perpustakaan baru atau kelola daftar anggota aktif.</p>
                <a href="anggota.php" class="btn btn-gradasi btn-sm mt-2 w-100 fw-semibold">
                    Kelola Anggota <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
</div>

</div><!-- /konten-utama -->

<script>
    // Buka/tutup sidebar di layar kecil
    function bukaSidebar() {
        document.getElementById('sidebarPetugas').classList.add('buka');
        document.getElementById('overlaySidebar').classList.add('tampil');
    }
    function tutupSidebar() {
        document.getElementById('sidebarPetugas').classList.remove('buka');
        document.getElementById('overlaySidebar').classList.remove('tampil');
    }

    // Konfirmasi logout (dipakai tombol di navbar maupun di sidebar)
    function konfirmasiLogout(e) {
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
    }

    document.getElementById('btnLogout')?.addEventListener('click', konfirmasiLogout);
    document.getElementById('btnLogoutSidebar')?.addEventListener('click', konfirmasiLogout);
</script>

</body>
</html>