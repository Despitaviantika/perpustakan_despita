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

// Total denda (untuk widget sidebar, konsisten dengan halaman lain)
$q_denda = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT SUM(denda) as total FROM transaksi"));
$total_denda = $q_denda['total'] ?? 0;

// --- Statistik rating ---
$q_ringkasan = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as jumlah, AVG(rating) as rerata FROM rating"));
$jumlah_rating = $q_ringkasan['jumlah'] ?? 0;
$rerata_rating = $q_ringkasan['rerata'] ?? 0;

// Distribusi bintang 1-5
$distribusi = array_fill(1, 5, 0);
$q_distribusi = mysqli_query($koneksi, "SELECT rating, COUNT(*) as jumlah FROM rating GROUP BY rating");
while ($row = mysqli_fetch_assoc($q_distribusi)) {
    $bintang = (int)$row['rating'];
    if ($bintang >= 1 && $bintang <= 5) {
        $distribusi[$bintang] = (int)$row['jumlah'];
    }
}

// Daftar semua rating, terbaru dulu, dengan nama anggota & judul buku
$sql = mysqli_query($koneksi, "
    SELECT rating.*, anggota.nama, buku.judul AS judul_buku
    FROM rating
    JOIN anggota ON rating.id_anggota = anggota.id_anggota
    LEFT JOIN buku ON rating.id_buku = buku.id_buku
    ORDER BY rating.tanggal DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Rating &amp; Ulasan - Admin Perpustakaan</title>
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

        .header-rating {
            background: linear-gradient(135deg, #ffb703 0%, #fb8500 100%);
            border-radius: 15px;
            color: #ffffff;
            padding: 26px 28px;
        }
        .bintang-besar { font-size: 2.4rem; font-weight: 800; }
        .bar-distribusi {
            height: 10px;
            border-radius: 6px;
            background: rgba(255,255,255,0.25);
            overflow: hidden;
        }
        .bar-distribusi .isi {
            height: 100%;
            background: #ffffff;
            border-radius: 6px;
        }

        .table thead {
            background: linear-gradient(135deg, #1d3557 0%, #457b9d 100%);
            color: #fff;
        }
        .bintang-teks { color: #fb8500; font-weight: 700; }
        .komentar-box {
            font-size: .88rem;
            color: #555;
            max-width: 260px;
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

    <div class="header-rating mb-4">
        <div class="row align-items-center g-4">
            <div class="col-md-4 text-center text-md-start">
                <div class="text-uppercase small fw-bold opacity-75">Rata-rata Rating</div>
                <div class="bintang-besar"><i class="bi bi-star-fill me-1"></i><?= number_format($rerata_rating, 1); ?></div>
                <div class="opacity-75">dari <?= $jumlah_rating; ?> ulasan anggota</div>
            </div>
            <div class="col-md-8">
                <?php for ($b = 5; $b >= 1; $b--):
                    $jumlah_b = $distribusi[$b];
                    $persen = $jumlah_rating > 0 ? round(($jumlah_b / $jumlah_rating) * 100) : 0;
                ?>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span style="width:46px;"><?= $b; ?> <i class="bi bi-star-fill" style="font-size:.7rem;"></i></span>
                    <div class="bar-distribusi flex-grow-1">
                        <div class="isi" style="width: <?= $persen; ?>%"></div>
                    </div>
                    <span style="width:30px; font-size:.8rem;" class="text-end"><?= $jumlah_b; ?></span>
                </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-chat-square-text-fill me-2 text-warning"></i>Semua Rating &amp; Ulasan</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="text-center">No</th>
                            <th>Nama Anggota</th>
                            <th>Judul Buku</th>
                            <th class="text-center">Rating</th>
                            <th>Komentar</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        if (mysqli_num_rows($sql) > 0) {
                            while ($row = mysqli_fetch_assoc($sql)) {
                                $judul_tampil = !empty($row['judul_buku']) ? $row['judul_buku'] : 'Buku (sudah dihapus)';
                                $bintang_teks = str_repeat('★', (int)$row['rating']) . str_repeat('☆', 5 - (int)$row['rating']);
                                ?>
                                <tr>
                                    <td class="text-center"><?= $no++; ?></td>
                                    <td><?= htmlspecialchars($row['nama']); ?></td>
                                    <td class="fw-semibold text-primary"><?= htmlspecialchars($judul_tampil); ?></td>
                                    <td class="text-center bintang-teks"><?= $bintang_teks; ?></td>
                                    <td class="komentar-box"><?= !empty($row['komentar']) ? htmlspecialchars($row['komentar']) : '<span class="text-muted fst-italic">Tidak ada komentar</span>'; ?></td>
                                    <td><?= date('d M Y, H:i', strtotime($row['tanggal'])); ?></td>
                                </tr>
                                <?php
                            }
                        } else {
                            echo "<tr><td colspan='6' class='text-center py-4 text-muted'><i class='bi bi-star fs-2 d-block mb-1'></i>Belum ada rating dari anggota.</td></tr>";
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