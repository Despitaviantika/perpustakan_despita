<?php
session_start();
include "../koneksi.php";

// Cek apakah sudah login sebagai admin
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit;
}

$username = $_SESSION['username'] ?? 'Admin';
$halaman_aktif = basename($_SERVER['PHP_SELF']);

// Total denda keseluruhan (untuk widget sidebar, konsisten dengan halaman lain)
$q_denda = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT SUM(denda) as total FROM transaksi"));
$total_denda = $q_denda['total'] ?? 0;

// Ambil hanya transaksi yang ada dendanya, urut dari denda terbesar
$sql = mysqli_query($koneksi, "
    SELECT transaksi.*, anggota.nama, buku.judul AS judul_dari_tabel_buku
    FROM transaksi
    JOIN anggota ON transaksi.id_anggota = anggota.id_anggota
    LEFT JOIN buku ON transaksi.id_buku = buku.id_buku
    WHERE transaksi.denda > 0
    ORDER BY transaksi.denda DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rincian Denda - Admin Perpustakaan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body {
            background: #f4f9ff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar-admin {
            background: linear-gradient(135deg, #90caf9 0%, #42a5f5 55%, #1565c0 100%) !important;
            box-shadow: 0 4px 10px rgba(100,181,246,0.3);
        }
        .navbar-admin .navbar-brand { color: #000000 !important; }
        .navbar-admin .text-white { color: #000000 !important; }
        .navbar-admin .btn-outline-light { color: #000000 !important; border-color: #000000 !important; }
        .navbar-admin .btn-outline-light:hover { background: #000000 !important; color: #ffffff !important; }
        .card { border: none; border-radius: 15px; }

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
            background: linear-gradient(180deg, #90caf9 0%, #5b9bd5 100%);
            box-shadow: 2px 0 10px rgba(100,181,246,0.25);
            overflow-y: auto;
            z-index: 1035;
            transition: transform .25s ease;
            padding: 18px 0 30px;
        }
        .sidebar-judul {
            color: rgba(0,0,0,0.65);
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .5px;
            padding: 0 20px;
            margin-bottom: 8px;
        }
        .sidebar-admin .nav-link {
            color: #000000;
            padding: 11px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: .94rem;
            border-left: 4px solid transparent;
            transition: .2s;
        }
        .sidebar-admin .nav-link i { font-size: 1.1rem; width: 20px; text-align: center; }
        .sidebar-admin .nav-link:hover { background: rgba(0,0,0,0.08); color: #000000; }
        .sidebar-admin .nav-link.aktif {
            background: rgba(0,0,0,0.12);
            color: #000000;
            font-weight: 600;
            border-left-color: #000000;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        .sidebar-pemisah { border-top: 1px solid rgba(0,0,0,0.2); margin: 14px 20px; }

        .widget-denda {
            margin: 4px 20px 18px;
            border-radius: 12px;
            padding: 16px;
            background: linear-gradient(135deg, #e6f4ff 0%, #90caf9 55%, #1976d2 100%);
            box-shadow: 0 4px 14px rgba(0,0,0,0.18);
        }
        .widget-denda .label-denda {
            font-size: .7rem; font-weight: 700; letter-spacing: .5px;
            text-transform: uppercase; color: #0d3b66; opacity: .85;
        }
        .widget-denda .nilai-denda {
            font-size: 1.28rem; font-weight: 800; color: #ffffff;
            text-shadow: 0 1px 3px rgba(0,0,0,0.25); margin: 4px 0 8px;
        }
        .widget-denda a {
            color: #0d3b66; font-size: .78rem; font-weight: 600;
            text-decoration: none; display: inline-flex; align-items: center; gap: 4px;
        }
        .widget-denda a:hover { text-decoration: underline; }

        .konten-utama { margin-left: var(--sidebar-lebar); }
        .tombol-sidebar { display: none; }
        .overlay-sidebar { position: fixed; inset: 0; background: rgba(0,0,0,0.45); z-index: 1030; display: none; }
        .overlay-sidebar.tampil { display: block; }

        @media (max-width: 991.98px) {
            .konten-utama { margin-left: 0; }
            .sidebar-admin { transform: translateX(-100%); }
            .sidebar-admin.buka { transform: translateX(0); }
            .tombol-sidebar { display: inline-flex; }
        }

        .header-denda {
            background: linear-gradient(135deg, #e6f4ff 0%, #90caf9 55%, #1976d2 100%);
            border-radius: 15px;
            color: #ffffff;
            padding: 26px 28px;
        }
        .header-denda h3 { color: #0d3b66; }
        .header-denda .total-besar { font-size: 2rem; font-weight: 800; color: #ffffff; text-shadow: 0 2px 6px rgba(0,0,0,0.25); }

        .table thead {
            background: linear-gradient(135deg, #1d3557 0%, #457b9d 100%);
            color: #fff;
        }
        .badge-rp {
            background: linear-gradient(135deg, #1976d2, #0d47a1);
            color: #fff;
            font-weight: 700;
            padding: 6px 12px;
            border-radius: 20px;
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

    <div class="header-denda mb-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h3 class="fw-bold mb-1"><i class="bi bi-cash-coin me-2"></i>Rincian Denda</h3>
            <p class="mb-0" style="color:#0d3b66;">Daftar transaksi yang memiliki denda, diurutkan dari yang terbesar.</p>
        </div>
        <div class="text-end">
            <div class="label-denda" style="color:#0d3b66; font-size:.75rem; font-weight:700; text-transform:uppercase;">Total Semua Denda</div>
            <div class="total-besar">Rp <?= number_format($total_denda, 0, ',', '.'); ?></div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="text-center">No</th>
                            <th>Nama Peminjam</th>
                            <th>Judul Buku</th>
                            <th>Tgl Pinjam</th>
                            <th>Tgl Harus Kembali</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Jumlah Denda</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        if (mysqli_num_rows($sql) > 0) {
                            while ($row = mysqli_fetch_assoc($sql)) {
                                $judul_tampil = !empty($row['judul_buku'])
                                    ? $row['judul_buku']
                                    : (!empty($row['judul_dari_tabel_buku']) ? $row['judul_dari_tabel_buku'] : 'Buku (sudah dihapus)');
                                ?>
                                <tr>
                                    <td class="text-center"><?= $no++; ?></td>
                                    <td><?= htmlspecialchars($row['nama']); ?></td>
                                    <td class="fw-semibold text-primary"><?= htmlspecialchars($judul_tampil); ?></td>
                                    <td><?= htmlspecialchars($row['tgl_pinjam']); ?></td>
                                    <td><?= htmlspecialchars($row['tgl_kembali']); ?></td>
                                    <td class="text-center"><span class="badge bg-secondary"><?= htmlspecialchars($row['status']); ?></span></td>
                                    <td class="text-center"><span class="badge-rp">Rp <?= number_format((int)$row['denda'], 0, ',', '.'); ?></span></td>
                                </tr>
                                <?php
                            }
                        } else {
                            echo "<tr><td colspan='7' class='text-center py-4 text-muted'><i class='bi bi-emoji-smile fs-2 d-block mb-1'></i>Belum ada denda sama sekali.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <a href="index.php" class="btn btn-outline-secondary px-4 py-2"><i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard</a>

</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function aturTinggiNavbar() {
        const nav = document.querySelector('.navbar-admin');
        if (nav) {
            document.documentElement.style.setProperty('--tinggi-navbar', nav.offsetHeight + 'px');
        }
    }
    window.addEventListener('load', aturTinggiNavbar);
    window.addEventListener('resize', aturTinggiNavbar);

    function bukaTutupSidebar() {
        document.getElementById('sidebarAdmin').classList.toggle('buka');
        document.getElementById('overlaySidebar').classList.toggle('tampil');
    }

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
</script>

</body>
</html>