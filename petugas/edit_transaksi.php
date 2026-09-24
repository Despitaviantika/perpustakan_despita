<?php
session_start();
include "../koneksi.php";

// Pastikan yang mengakses adalah admin atau petugas
if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'petugas')) {
    header("Location: ../login.php");
    exit;
}

$id_transaksi = isset($_GET['id']) ? mysqli_real_escape_string($koneksi, $_GET['id']) : 0;

// Nama tabel yang benar sesuai phpMyAdmin Anda
$nama_tabel_aktif = 'transaksi';

// Ambil data transaksi berdasarkan id_transaksi
$query = mysqli_query($koneksi, "SELECT * FROM `$nama_tabel_aktif` WHERE `id_transaksi` = '$id_transaksi'");
$data = $query ? mysqli_fetch_assoc($query) : null;

if (!$data) {
    echo "<script>alert('Data transaksi tidak ditemukan!'); window.location='transaksi.php';</script>";
    exit;
}

// Menampung data struk setelah update berhasil
$update_berhasil = false;
$struk = null;

// Jika tombol update diklik
if (isset($_POST['update'])) {
    $tgl_p     = mysqli_real_escape_string($koneksi, $_POST['tgl_pinjam']);
    $tgl_k     = mysqli_real_escape_string($koneksi, $_POST['tgl_kembali']);
    $tgl_d     = !empty($_POST['tgl_dikembalikan']) ? "'" . mysqli_real_escape_string($koneksi, $_POST['tgl_dikembalikan']) . "'" : "NULL";
    $status    = mysqli_real_escape_string($koneksi, $_POST['status']);
    $denda     = mysqli_real_escape_string($koneksi, $_POST['denda']);
    $kondisi   = mysqli_real_escape_string($koneksi, $_POST['kondisi_buku']); // Untuk perhitungan denda fisik / catatan
    $metode    = mysqli_real_escape_string($koneksi, $_POST['metode_bayar'] ?? 'tunai');

    // Query update dengan nama kolom yang presisi sesuai phpMyAdmin
    $sql_update = "UPDATE `$nama_tabel_aktif` SET 
                    `tgl_pinjam` = '$tgl_p', 
                    `tgl_kembali` = '$tgl_k', 
                    `tgl_dikembalikan` = $tgl_d, 
                    `status` = '$status', 
                    `denda` = '$denda',
                    `metode_bayar` = '$metode',
                    `kondisi_buku` = '$kondisi'
                    WHERE `id_transaksi` = '$id_transaksi'";

    $update = mysqli_query($koneksi, $sql_update);

    if ($update) {
        $update_berhasil = true;

        // Coba ambil judul buku & nama anggota untuk ditampilkan di struk.
        $nama_anggota = '';
        $judul_buku   = '';

        $q_lengkap = @mysqli_query($koneksi, "
            SELECT t.*, b.judul AS judul_buku, a.nama AS nama_anggota
            FROM `$nama_tabel_aktif` t
            LEFT JOIN buku b ON b.id_buku = t.id_buku
            LEFT JOIN anggota a ON a.id_anggota = t.id_anggota
            WHERE t.id_transaksi = '$id_transaksi'
        ");
        if ($q_lengkap) {
            $d_lengkap = mysqli_fetch_assoc($q_lengkap);
            if ($d_lengkap) {
                $nama_anggota = $d_lengkap['nama_anggota'] ?? '';
                $judul_buku   = $d_lengkap['judul_buku'] ?? '';
            }
        }

        $struk = [
            'id_transaksi'     => $id_transaksi,
            'nama_anggota'     => $nama_anggota,
            'judul_buku'       => $judul_buku,
            'tgl_pinjam'       => $_POST['tgl_pinjam'],
            'tgl_kembali'      => $_POST['tgl_kembali'],
            'tgl_dikembalikan' => $_POST['tgl_dikembalikan'],
            'status'           => $_POST['status'],
            'kondisi_buku'     => $_POST['kondisi_buku'],
            'denda'            => $_POST['denda'],
            'metode_bayar'     => $metode,
        ];
    } else {
        echo "<script>alert('Gagal memperbarui data: " . mysqli_error($koneksi) . "');</script>";
    }
}

$status_saat_ini = strtolower(trim($data['status'] ?? 'dipinjam'));
$metode_saat_ini = strtolower(trim($data['metode_bayar'] ?? 'tunai'));

// Label yang lebih enak dibaca untuk struk
function label_status($s) {
    $s = strtolower(trim($s));
    if ($s == 'kembali') return 'Buku Telah Dikembalikan';
    if ($s == 'dibatalkan') return 'Transaksi Dibatalkan';
    return 'Masih Dipinjam';
}
function label_kondisi($k) {
    if ($k == 'Rusak') return 'Rusak';
    if ($k == 'Hilang') return 'Hilang';
    return 'Baik (Normal)';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Transaksi & Denda - Perpustakaan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background: #f4f7f6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* ===== STRUK ===== */
        .struk-wrapper {
            max-width: 380px;
            margin: 0 auto;
        }
        .struk {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 18px rgba(0,0,0,.08);
            padding: 28px 26px;
            font-family: 'Courier New', Courier, monospace;
        }
        .struk-kepala {
            text-align: center;
            margin-bottom: 14px;
        }
        .struk-kepala .ikon-cek {
            width: 54px;
            height: 54px;
            border-radius: 50%;
            background: #198754;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            margin: 0 auto 10px;
        }
        .struk-kepala h5 {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-weight: 700;
            margin-bottom: 2px;
        }
        .struk-kepala small {
            color: #6c757d;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .struk-garis {
            border-top: 1px dashed #adb5bd;
            margin: 16px 0;
        }
        .struk-baris {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            font-size: .88rem;
            padding: 4px 0;
        }
        .struk-baris .label {
            color: #6c757d;
        }
        .struk-baris .nilai {
            text-align: right;
            font-weight: 600;
            color: #212529;
        }
        .struk-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 6px;
            padding-top: 10px;
            border-top: 1px dashed #adb5bd;
        }
        .struk-total .label {
            font-size: .95rem;
            font-weight: 700;
        }
        .struk-total .nilai {
            font-size: 1.25rem;
            font-weight: 700;
            color: #d90429;
        }
        .struk-total.lunas .nilai {
            color: #198754;
        }
        .struk-catatan {
            text-align: center;
            font-size: .74rem;
            color: #adb5bd;
            margin-top: 16px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .badge-kondisi-Baik   { background: #d1e7dd; color: #0f5132; }
        .badge-kondisi-Rusak  { background: #fff3cd; color: #664d03; }
        .badge-kondisi-Hilang { background: #f8d7da; color: #842029; }

        /* ===== PILIHAN METODE BAYAR DI FORM ===== */
        .pilihan-metode {
            display: flex;
            gap: 10px;
        }
        .pilihan-metode input[type="radio"] {
            display: none;
        }
        .pilihan-metode label {
            flex: 1;
            text-align: center;
            padding: 10px;
            border: 2px solid #dee2e6;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            color: #495057;
            transition: .2s;
        }
        .pilihan-metode input[type="radio"]:checked + label {
            border-color: #0d6efd;
            background: #e7f1ff;
            color: #0d6efd;
        }

        /* ===== QR GOPAY DI STRUK ===== */
        .qr-gopay {
            text-align: center;
            margin: 14px 0 4px;
        }
        .qr-gopay img {
            width: 170px;
            height: 170px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 6px;
        }
        .qr-gopay .ket {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: .72rem;
            color: #6c757d;
            margin-top: 6px;
        }

        @media print {
            body * { visibility: hidden; }
            .struk, .struk * { visibility: visible; }
            .struk {
                position: absolute;
                top: 0; left: 50%;
                transform: translateX(-50%);
                box-shadow: none;
            }
            .tanpa-print { display: none !important; }
        }
    </style>
    <script>
        function hitungDendaOtomatis() {
            var tglKembaliRencana = document.getElementById('tgl_kembali').value;
            var tglDikembalikan   = document.getElementById('tgl_dikembalikan').value;
            var kondisiBuku       = document.getElementById('kondisi_buku').value;
            var inputDenda        = document.getElementById('denda');
            var selectStatus      = document.getElementById('status');

            let dendaKeterlambatan = 0;
            let dendaKondisiFisik = 0;

            // Jika tanggal dikembalikan diisi, otomatis ubah status menjadi 'kembali'
            if (tglDikembalikan && selectStatus) {
                selectStatus.value = "kembali";
            } else if (!tglDikembalikan && selectStatus) {
                selectStatus.value = "dipinjam";
            }

            // Hitung denda keterlambatan (Tarif Rp 1.000 per hari, silakan ubah jika perlu)
            if (tglKembaliRencana && tglDikembalikan) {
                let dateRencana = new Date(tglKembaliRencana);
                let dateAktual  = new Date(tglDikembalikan);
                let selisihWaktu = dateAktual - dateRencana;
                let selisihHari  = Math.ceil(selisihWaktu / (1000 * 60 * 60 * 24));

                if (selisihHari > 0) {
                    let tarifPerHari = 1000; 
                    dendaKeterlambatan = selisihHari * tarifPerHari;
                }
            }

            // Hitung denda kondisi fisik buku
            if (kondisiBuku === 'Rusak') {
                dendaKondisiFisik = 25000;
            } else if (kondisiBuku === 'Hilang') {
                dendaKondisiFisik = 75000;
            }

            if(inputDenda) {
                inputDenda.value = dendaKeterlambatan + dendaKondisiFisik;
            }
        }
    </script>
</head>
<body class="bg-light">

<?php if ($update_berhasil && $struk): ?>

    <!-- ===== TAMPILAN STRUK SETELAH SIMPAN BERHASIL ===== -->
    <div class="container mt-5 mb-5">
        <div class="struk-wrapper">

            <div class="struk">
                <div class="struk-kepala">
                    <div class="ikon-cek"><i class="bi bi-check-lg"></i></div>
                    <h5>Bukti Pengembalian Buku</h5>
                    <small>Perpustakaan &middot; No. Transaksi #<?= htmlspecialchars($struk['id_transaksi']); ?></small>
                </div>

                <div class="struk-garis"></div>

                <?php if (!empty($struk['nama_anggota'])): ?>
                <div class="struk-baris">
                    <span class="label">Nama Peminjam</span>
                    <span class="nilai"><?= htmlspecialchars($struk['nama_anggota']); ?></span>
                </div>
                <?php endif; ?>

                <?php if (!empty($struk['judul_buku'])): ?>
                <div class="struk-baris">
                    <span class="label">Judul Buku</span>
                    <span class="nilai"><?= htmlspecialchars($struk['judul_buku']); ?></span>
                </div>
                <?php endif; ?>

                <div class="struk-baris">
                    <span class="label">Tgl Pinjam</span>
                    <span class="nilai"><?= date('d/m/Y', strtotime($struk['tgl_pinjam'])); ?></span>
                </div>
                <div class="struk-baris">
                    <span class="label">Jatuh Tempo</span>
                    <span class="nilai"><?= date('d/m/Y', strtotime($struk['tgl_kembali'])); ?></span>
                </div>
                <?php if (!empty($struk['tgl_dikembalikan'])): ?>
                <div class="struk-baris">
                    <span class="label">Tgl Dikembalikan</span>
                    <span class="nilai"><?= date('d/m/Y', strtotime($struk['tgl_dikembalikan'])); ?></span>
                </div>
                <?php endif; ?>
                <div class="struk-baris">
                    <span class="label">Kondisi Buku</span>
                    <span class="nilai"><?= label_kondisi($struk['kondisi_buku']); ?></span>
                </div>
                <div class="struk-baris">
                    <span class="label">Status</span>
                    <span class="nilai"><?= label_status($struk['status']); ?></span>
                </div>

                <div class="struk-total <?= ((int)$struk['denda'] === 0) ? 'lunas' : ''; ?>">
                    <span class="label">Total Denda</span>
                    <span class="nilai">
                        <?php if ((int)$struk['denda'] === 0): ?>
                            Rp 0 (Lunas)
                        <?php else: ?>
                            Rp <?= number_format((int)$struk['denda'], 0, ',', '.'); ?>
                        <?php endif; ?>
                    </span>
                </div>

                <?php if ((int)$struk['denda'] > 0): ?>
                    <div class="struk-baris">
                        <span class="label">Metode Pembayaran</span>
                        <span class="nilai"><?= ($struk['metode_bayar'] === 'qris') ? 'QRIS (GoPay)' : 'Tunai (Cash)'; ?></span>
                    </div>

                    <?php if ($struk['metode_bayar'] === 'qris'): ?>
                        <div class="qr-gopay">
                            <img src="../gambar/qris_gopay.jpeg" alt="QRIS GoPay"
                                 onerror="this.onerror=null; this.src='https://via.placeholder.com/170x170?text=QR+Belum+Diupload';">
                            <div class="ket">Scan QR di atas pakai GoPay / e-wallet lain buat bayar denda</div>
                        </div>
                    <?php else: ?>
                        <div class="text-center" style="font-size:.72rem; color:#d90429; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; margin-top:4px;">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>Denda dibayarkan secara tunai di perpustakaan
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="struk-catatan">
                    Dicetak <?= date('d/m/Y H:i'); ?> &middot; Simpan struk ini sebagai bukti
                </div>
            </div>

            <div class="d-flex gap-2 justify-content-center mt-4 tanpa-print">
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="bi bi-printer-fill me-1"></i> Cetak Struk
                </button>
                <a href="transaksi.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Transaksi
                </a>
            </div>

        </div>
    </div>

<?php else: ?>

    <!-- ===== FORM EDIT (TAMPILAN ASLI, TIDAK DIUBAH) ===== -->
    <div class="container mt-5 mb-5">
        <div class="card shadow-sm border-0 rounded-4 p-4" style="max-width: 600px; margin: 0 auto;">
            <h3 class="mb-4 text-primary fw-bold">Edit Transaksi, Telat & Denda</h3>
            
            <form action="" method="POST">
                <div class="mb-3">
                    <label for="tgl_pinjam" class="form-label">Tanggal Pinjam</label>
                    <input type="date" class="form-control" id="tgl_pinjam" name="tgl_pinjam" value="<?= htmlspecialchars($data['tgl_pinjam'] ?? ''); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="tgl_kembali" class="form-label">Batas Tanggal Kembali (Jatuh Tempo)</label>
                    <input type="date" class="form-control" id="tgl_kembali" name="tgl_kembali" value="<?= htmlspecialchars($data['tgl_kembali'] ?? ''); ?>" onchange="hitungDendaOtomatis()" required>
                </div>

                <div class="mb-3">
                    <label for="tgl_dikembalikan" class="form-label">Tanggal Buku Dikembalikan (Aktual)</label>
                    <input type="date" class="form-control" id="tgl_dikembalikan" name="tgl_dikembalikan" value="<?= htmlspecialchars($data['tgl_dikembalikan'] ?? date('Y-m-d')); ?>" onchange="hitungDendaOtomatis()">
                    <small class="text-muted">Mengisi tanggal ini otomatis mengubah status menjadi 'kembali'.</small>
                </div>

                <div class="mb-3">
                    <label for="status" class="form-label">Status Peminjaman</label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="dipinjam" <?= ($status_saat_ini == 'dipinjam') ? 'selected' : ''; ?>>dipinjam</option>
                        <option value="kembali" <?= ($status_saat_ini == 'kembali') ? 'selected' : ''; ?>>kembali</option>
                        <option value="dibatalkan" <?= ($status_saat_ini == 'dibatalkan') ? 'selected' : ''; ?>>dibatalkan</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="kondisi_buku" class="form-label">Kondisi Pengembalian Buku</label>
                    <?php $kondisi_saat_ini = $data['kondisi_buku'] ?? 'Normal'; ?>
                    <select class="form-select" id="kondisi_buku" name="kondisi_buku" onchange="hitungDendaOtomatis()" required>
                        <option value="Normal" <?= ($kondisi_saat_ini == 'Normal') ? 'selected' : ''; ?>>Normal (Baik)</option>
                        <option value="Rusak" <?= ($kondisi_saat_ini == 'Rusak') ? 'selected' : ''; ?>>Rusak (+Rp 25.000)</option>
                        <option value="Hilang" <?= ($kondisi_saat_ini == 'Hilang') ? 'selected' : ''; ?>>Hilang (+Rp 75.000)</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="denda" class="form-label">Total Denda (Telat + Fisik) (Rp)</label>
                    <input type="number" class="form-control" id="denda" name="denda" value="<?= htmlspecialchars($data['denda'] ?? '0'); ?>" required>
                </div>

                <div class="mb-4">
                    <label class="form-label d-block">Metode Pembayaran Denda</label>
                    <div class="pilihan-metode">
                        <input type="radio" id="metode_tunai" name="metode_bayar" value="tunai" <?= ($metode_saat_ini != 'qris') ? 'checked' : ''; ?>>
                        <label for="metode_tunai"><i class="bi bi-cash-coin me-1"></i> Tunai</label>

                        <input type="radio" id="metode_qris" name="metode_bayar" value="qris" <?= ($metode_saat_ini == 'qris') ? 'checked' : ''; ?>>
                        <label for="metode_qris"><i class="bi bi-qr-code me-1"></i> QRIS (GoPay)</label>
                    </div>
                </div>

                <button type="submit" name="update" class="btn btn-primary">Simpan Perubahan</button>
                <a href="transaksi.php" class="btn btn-secondary">Kembali</a>
            </form>
        </div>
    </div>

<?php endif; ?>

</body>
</html>