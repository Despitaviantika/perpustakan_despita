<?php
include 'koneksi.php';

// --- Data grafik utama: jumlah peminjaman per bulan (tahun berjalan) ---
$data_per_bulan = array_fill(1, 12, 0);
$q_bulan = mysqli_query($koneksi, "
    SELECT MONTH(tgl_pinjam) as bulan, COUNT(*) as total
    FROM transaksi
    WHERE YEAR(tgl_pinjam) = YEAR(CURDATE())
    GROUP BY MONTH(tgl_pinjam)
");
if ($q_bulan) {
    while ($row = mysqli_fetch_assoc($q_bulan)) {
        $data_per_bulan[(int)$row['bulan']] = (int)$row['total'];
    }
}
$json_data_bulan = json_encode(array_values($data_per_bulan));

// --- Data grafik mini footer: jumlah peminjaman per minggu (4 minggu terakhir) ---
$label_mingguan = [];
$data_mingguan  = [];
for ($i = 3; $i >= 0; $i--) {
    $akhir = date('Y-m-d', strtotime("-" . ($i * 7) . " days"));
    $awal  = date('Y-m-d', strtotime($akhir . " -6 days"));
    $q_minggu = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM transaksi WHERE tgl_pinjam BETWEEN '$awal' AND '$akhir'");
    $d_minggu = $q_minggu ? mysqli_fetch_assoc($q_minggu) : null;
    $label_mingguan[] = "Minggu " . (4 - $i);
    $data_mingguan[]  = (int)($d_minggu['total'] ?? 0);
}
$json_label_mingguan = json_encode($label_mingguan);
$json_data_mingguan  = json_encode($data_mingguan);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perpustakaan Pendidikan — Ruang Belajar Digital</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,600;0,9..144,700;1,9..144,500&family=Source+Serif+4:wght@400;500;600&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root{
            --ink: #03045e;
            --ink-soft: #0077b6;
            --paper: #eef7fc;
            --paper-deep: #dcedf8;
            --brass: #00b4d8;
            --brass-light: #90e0ef;
            --stamp-green: #2f5233;
            --stamp-red: #8c3a2b;
            --card-line: rgba(3,4,94,0.14);
        }

        *{ box-sizing: border-box; }

        body {
            font-family: 'Source Serif 4', Georgia, serif;
            color: var(--ink);
            background:
                linear-gradient(180deg, rgba(3,4,94,0.90) 0%, rgba(0,119,182,0.72) 32%, rgba(238,247,252,0.96) 62%, var(--paper) 100%),
                url('https://images.unsplash.com/photo-1521587760476-6c12a4b040da?q=80&w=1920&auto=format&fit=crop') center top / cover no-repeat fixed;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Fraunces', Georgia, serif;
        }

        .eyebrow{
            font-family: 'JetBrains Mono', monospace;
            letter-spacing: .14em;
            text-transform: uppercase;
            font-size: .72rem;
            color: var(--ink-soft);
            font-weight: 600;
        }

        .call-number{
            font-family: 'JetBrains Mono', monospace;
            font-size: .78rem;
            color: var(--ink-soft);
            opacity: .8;
        }

        /* ===== NAVBAR — gradasi biru laut ===== */
        .navbar {
            background: linear-gradient(120deg, #03045e 0%, #0077b6 100%);
            border-bottom: 3px solid var(--brass-light);
            box-shadow: 0 6px 18px rgba(3,4,94,0.28);
        }

        .navbar .navbar-brand{
            font-family: 'Fraunces', serif;
            font-weight: 700;
            color: #eef7fc !important;
            letter-spacing: .01em;
        }
        .navbar .navbar-brand i{ color: var(--brass-light); }

        .navbar .nav-link{
            color: rgba(238,247,252,0.85) !important;
            font-family: 'JetBrains Mono', monospace;
            font-size: .82rem;
            letter-spacing: .04em;
            text-transform: uppercase;
        }
        .navbar .nav-link.active,
        .navbar .nav-link:hover{
            color: var(--brass-light) !important;
        }

        .btn-catalog{
            background: linear-gradient(120deg, #00b4d8, #0077b6);
            border: none;
            color: #ffffff !important;
            font-weight: 600;
            border-radius: 3px;
        }
        .btn-catalog:hover{ background: linear-gradient(120deg, #90e0ef, #00b4d8); color: var(--ink) !important; }

        /* ===== HERO — open card-catalog drawer ===== */
        .hero-section {
            position: relative;
            color: #eef7fc;
            padding: 170px 0 150px 0;
            overflow: hidden;
            background: #03045e;
            border-bottom: 10px solid var(--brass-light);
        }

        .hero-section::before{
            /* overlay gradasi biru laut di atas foto wallpaper agar teks tetap terbaca */
            content:"";
            position:absolute; inset:0;
            background: linear-gradient(180deg, rgba(3,4,94,0.80) 0%, rgba(0,119,182,0.55) 55%, rgba(3,4,94,0.85) 100%);
            z-index:1;
        }

        .hero-slideshow {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            z-index: 0;
        }

        .hero-slideshow span {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background-size: cover;
            background-position: center;
            opacity: 0;
            animation: imageAnimation 18s infinite linear;
        }

        .hero-slideshow span:nth-child(1){ background-image: url('https://images.unsplash.com/photo-1521587760476-6c12a4b040da?q=80&w=1920&auto=format&fit=crop'); }
        .hero-slideshow span:nth-child(2){ background-image: url('https://images.unsplash.com/photo-1507842229443-77d83810fcaf?q=80&w=1920&auto=format&fit=crop'); animation-delay: 3s; }
        .hero-slideshow span:nth-child(3){ background-image: url('https://images.unsplash.com/photo-1457369804613-52c61a468e7d?q=80&w=1920&auto=format&fit=crop'); animation-delay: 6s; }
        .hero-slideshow span:nth-child(4){ background-image: url('https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?q=80&w=1920&auto=format&fit=crop'); animation-delay: 9s; }
        .hero-slideshow span:nth-child(5){ background-image: url('https://images.unsplash.com/photo-1522881450255-7a6e1273934d?q=80&w=1920&auto=format&fit=crop'); animation-delay: 12s; }
        .hero-slideshow span:nth-child(6){ background-image: url('https://images.unsplash.com/photo-1497633762265-9d179a990aa6?q=80&w=1920&auto=format&fit=crop'); animation-delay: 15s; }

        @keyframes imageAnimation {
            0% { opacity: 0; transform: scale(1); }
            10% { opacity: .95; }
            33% { opacity: .95; transform: scale(1.03); }
            43% { opacity: 0; transform: scale(1.05); }
            100% { opacity: 0; }
        }

        .hero-section .container{ position: relative; z-index: 2; }

        /* the lending-card element: signature piece */
        .lending-card{
            background: var(--paper);
            color: var(--ink);
            border-radius: 4px;
            padding: 34px 32px 28px;
            max-width: 640px;
            box-shadow: 0 24px 50px rgba(0,0,0,0.35), 0 2px 0 var(--brass);
            position: relative;
            transform: rotate(-1.1deg);
        }
        .lending-card::before{
            /* ruled lines like a real catalog card */
            content:"";
            position:absolute; left:32px; right:32px; top:88px; bottom:26px;
            background-image: repeating-linear-gradient(
                180deg, transparent 0 27px, var(--card-line) 27px 28px
            );
            z-index: 0;
        }
        .lending-card > *{ position: relative; z-index: 1; }

        h1.headline{
            font-size: clamp(2rem, 4.2vw, 3.1rem);
            font-weight: 700;
            line-height: 1.12;
            color: var(--ink);
        }

        .lending-card .lead{
            color: var(--ink-soft);
            font-size: 1.02rem;
        }

        .btn-hero-primary{
            background: var(--ink);
            color: var(--paper);
            border-radius: 3px;
            font-weight: 600;
            padding: .8rem 1.6rem;
        }
        .btn-hero-primary:hover{ background: var(--ink-soft); color: var(--paper); }

        .btn-hero-outline{
            border: 1.5px solid var(--ink);
            color: var(--ink);
            border-radius: 3px;
            font-weight: 600;
            padding: .8rem 1.6rem;
            background: transparent;
        }
        .btn-hero-outline:hover{ background: var(--ink); color: var(--paper); }

        /* ===== FEATURES — index cards with dewey call numbers ===== */
        #fitur{ background: transparent; }

        .feature-card {
            border: 1px solid var(--card-line);
            border-radius: 4px;
            background: rgba(255,255,255,0.88);
            backdrop-filter: blur(6px);
            transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
            position: relative;
        }
        .feature-card::before{
            content:"";
            position:absolute; left:0; top:14px; bottom:14px; width:3px;
            background: var(--brass);
            opacity: 0;
            transition: opacity .25s ease;
        }
        .feature-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 14px 30px rgba(22,40,63,0.12);
            border-color: transparent;
        }
        .feature-card:hover::before{ opacity: 1; }

        .feature-icon {
            width: 52px; height: 52px;
            background: linear-gradient(135deg, #03045e, #0077b6);
            color: #90e0ef;
            border-radius: 3px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px;
            margin-bottom: 18px;
        }

        .feature-card h4{ font-size: 1.15rem; }

        /* ===== VIDEO ===== */
        #video{ background: transparent; }
        .ratio.rounded-4{ border: 6px solid rgba(255,255,255,0.9); box-shadow: 0 18px 40px rgba(3,4,94,0.22); }

        /* ===== STATS ===== */
        #statistik{ background: transparent; }
        #statistik .card{
            background: rgba(255,255,255,0.88);
            backdrop-filter: blur(6px);
            border: 1px solid var(--card-line) !important;
        }

        /* ===== FOOTER — back of a library card ===== */
        footer {
            background: linear-gradient(120deg, #03045e 0%, #0077b6 100%);
            color: #eef7fc;
            padding: 48px 0 22px 0;
            border-top: 6px solid var(--brass-light);
        }
        footer h4, footer h5{ color: #eef7fc; }
        footer a{ color: rgba(238,247,252,0.78); text-decoration: none; font-family:'JetBrains Mono', monospace; font-size:.86rem; }
        footer a:hover{ color: var(--brass-light); text-decoration: underline; }
        footer .text-white-50{ color: rgba(238,247,252,0.6) !important; }

        footer .card{
            background: rgba(255,255,255,0.92) !important;
            border: 1px solid var(--card-line);
        }
        footer .card h6{ color: var(--ink); }

        footer hr{ border-color: rgba(238,247,252,0.2); }

        @media (prefers-reduced-motion: reduce){
            .hero-slideshow span{ animation: none !important; }
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold fs-4" href="#">
                <i class="bi bi-book-half me-2"></i>Perpustakaan Pendidikan
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link active fw-semibold me-3" href="#beranda">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold me-3" href="#fitur">Layanan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold me-3" href="#video">Video</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold me-3" href="#statistik">Statistik</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold me-3" href="#kontak">Kontak</a>
                    </li>
                    <li class="nav-item">
                        <a href="login.php" class="btn btn-catalog px-4 py-2 rounded-pill fw-semibold shadow-sm">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Masuk / Login
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <section id="beranda" class="hero-section text-center text-md-start">
        <div class="hero-slideshow">
            <span></span>
            <span></span>
            <span></span>
            <span></span>
            <span></span>
            <span></span>
        </div>

        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="lending-card text-start">
                        <h1 class="headline mb-3">Buka Setiap Halaman, Buka Setiap Peluang</h1>
                        <p class="lead mb-4">
                            Selamat datang di Perpustakaan Pendidikan, ruang belajar online sekolah kita. Jelajahi ribuan buku, jurnal, dan referensi pilihan untuk menemani setiap langkah belajarmu, kapan saja dan di mana saja.
                        </p>
                        <div class="d-flex flex-column flex-sm-row gap-3">
                            <a href="login.php" class="btn btn-hero-primary btn-lg rounded-pill">
                                Mulai Pinjam Buku <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                            <a href="#fitur" class="btn btn-hero-outline btn-lg rounded-pill">
                                Jelajahi Layanan
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="fitur" class="py-5">
        <div class="container py-4">
            <div class="text-center mb-5">
                <h6 class="eyebrow">Layanan Kami</h6>
                <h2 class="fw-bold">Sahabat Belajar yang Selalu Siap Membantu</h2>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card feature-card h-100 p-4">
                        <span class="call-number mb-2">025.3</span>
                        <div class="feature-icon">
                            <i class="bi bi-search"></i>
                        </div>
                        <h4 class="fw-bold mb-2">Katalog Digital</h4>
                        <p class="text-muted mb-0">Temukan judul, pengarang, atau kategori buku favoritmu hanya dalam hitungan detik, langsung dari genggaman.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card h-100 p-4">
                        <span class="call-number mb-2">025.6</span>
                        <div class="feature-icon">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <h4 class="fw-bold mb-2">Peminjaman Praktis</h4>
                        <p class="text-muted mb-0">Pinjam dan kembalikan buku tanpa antre, tercatat otomatis lewat sistem yang cepat dan terpercaya.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card h-100 p-4">
                        <span class="call-number mb-2">020.0</span>
                        <div class="feature-icon">
                            <i class="bi bi-journal-bookmark-fill"></i>
                        </div>
                        <h4 class="fw-bold mb-2">Koleksi Beragam</h4>
                        <p class="text-muted mb-0">Dari buku pelajaran, novel, hingga jurnal ilmiah — semua tersedia untuk memperkaya wawasanmu.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="video" class="py-5">
        <div class="container py-4">
            <div class="text-center mb-5">
                <h6 class="eyebrow">Kenali Lebih Dekat</h6>
                <h2 class="fw-bold">Mengenal Perpustakaan Pendidikan Lebih Dalam</h2>
                <p class="text-muted">Simak video berikut untuk melihat langsung suasana dan layanan perpustakaan kami.</p>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="ratio ratio-16x9 rounded-4 overflow-hidden">
                        <iframe src="https://www.youtube.com/embed/UybIxTs-Rc8" title="Video Profil Perpustakaan Pendidikan" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="statistik" class="py-5">
        <div class="container py-4">
            <div class="text-center mb-5">
                <h6 class="eyebrow">Data &amp; Statistik</h6>
                <h2 class="fw-bold">Semangat Membaca dari Bulan ke Bulan</h2>
                <p class="text-muted">Berikut jumlah buku yang berhasil dipinjam oleh anggota setiap bulannya.</p>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm p-4 rounded-4">
                        <canvas id="grafikPeminjaman"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer id="kontak">
        <div class="container">
            <div class="row g-4 mb-4">
                <div class="col-lg-5 col-md-6">
                    <h4 class="fw-bold mb-3"><i class="bi bi-book-half me-2"></i>Perpustakaan Pendidikan</h4>
                    <p class="text-white-50">Perpustakaan digital sekolah yang hadir untuk memudahkan siswa dan anggota menjelajahi ilmu pengetahuan lewat buku, jurnal, dan sumber belajar terpercaya.</p>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h5 class="fw-bold mb-3">Tautan Cepat</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#beranda">Beranda</a></li>
                        <li class="mb-2"><a href="#fitur">Layanan Kami</a></li>
                        <li class="mb-2"><a href="#statistik">Statistik</a></li>
                        <li class="mb-2"><a href="login.php">Halaman Login</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 col-md-12">
                    <h5 class="fw-bold mb-3">Hubungi Kami</h5>
                    <p class="text-white-50 mb-1"><i class="bi bi-geo-alt me-2"></i>Jl. Pendidikan No. 123, Yogyakarta</p>
                    <p class="text-white-50 mb-1"><i class="bi bi-envelope me-2"></i>@perpustakaanpendidikan.sch.id</p>
                    <p class="text-white-50 mb-1"><i class="bi bi-telephone me-2"></i> 889-8313-4656</p>
                    <p class="text-white-50"><i class="bi bi-clock me-2"></i>Jam Operasional: 08:00 - 15:00</p>
                </div>
            </div>

            <hr>

            <div class="row justify-content-center my-4">
                <div class="col-md-8">
                    <div class="card text-dark p-3 rounded-3 shadow">
                        <h6 class="text-center fw-bold mb-3">Grafik Pengunjung &amp; Peminjaman (Mini)</h6>
                        <canvas id="grafikFooterMini" height="90"></canvas>
                    </div>
                </div>
            </div>

            <div class="text-center text-white-50 small pt-2">
                &copy; <?php echo date('Y'); ?> Perpustakaan Pendidikan by despitaviantikadewi, smkn1sanden. All Rights Reserved.
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const ctx = document.getElementById('grafikPeminjaman').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
                datasets: [{
                    label: 'Jumlah Buku Dipinjam',
                    data: <?= $json_data_bulan; ?>,
                    backgroundColor: 'rgba(168, 127, 63, 0.75)',
                    borderColor: 'rgba(22, 40, 63, 0.9)',
                    borderWidth: 1.5,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'top' } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });

        const ctxMini = document.getElementById('grafikFooterMini').getContext('2d');
        new Chart(ctxMini, {
            type: 'line',
            data: {
                labels: <?= $json_label_mingguan; ?>,
                datasets: [{
                    label: 'Aktivitas Peminjaman',
                    data: <?= $json_data_mingguan; ?>,
                    backgroundColor: 'rgba(168, 127, 63, 0.18)',
                    borderColor: 'rgba(22, 40, 63, 0.9)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });
    </script>
</body>
</html>