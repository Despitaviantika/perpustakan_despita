<?php
session_start();
// TODO: sesuaikan pengecekan login petugas/admin kamu di sini
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan Pengembalian Buku - Perpustakaan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <style>
        body { background: #f3f7fb; }
        #reader { width: 100%; max-width: 420px; margin: 0 auto; border-radius: 12px; overflow: hidden; }
        .box { max-width: 480px; margin: 20px auto 0; }
        .item-buku { display:flex; justify-content:space-between; align-items:center; padding:10px 14px; border:1px solid #ddd; border-radius:10px; margin-bottom:8px; background:#fff; }
    </style>
</head>
<body>
<div class="container py-4">
    <h4 class="fw-bold text-center mb-1"><i class="bi bi-arrow-return-left me-2"></i>Scan Pengembalian Buku</h4>
    <p class="text-center text-muted mb-4">Minta siswa tunjukkan QR kartu anggota dari dashboard-nya</p>

    <div id="reader"></div>

    <div class="box">
        <div id="infoAnggota" class="alert alert-secondary text-center mt-3 mb-3">
            Menunggu QR di-scan...
        </div>
        <div id="daftarBuku"></div>
    </div>
</div>

<script>
    let sedangProses = false;

    function onScanSuccess(decodedText) {
        if (sedangProses) return;
        sedangProses = true;

        document.getElementById('infoAnggota').className = 'alert alert-info text-center mt-3 mb-3';
        document.getElementById('infoAnggota').innerText = 'Mencari data...';
        document.getElementById('daftarBuku').innerHTML = '';

        fetch('cari_peminjaman.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id_anggota=' + encodeURIComponent(decodedText)
        })
        .then(res => res.json())
        .then(data => {
            const info = document.getElementById('infoAnggota');
            const list = document.getElementById('daftarBuku');

            if (data.status !== 'sukses') {
                info.className = 'alert alert-danger text-center mt-3 mb-3';
                info.innerText = '❌ ' + data.pesan;
                return;
            }

            info.className = 'alert alert-success text-center mt-3 mb-3';
            info.innerHTML = `<b>${data.nama}</b> <span class="text-muted">(${data.nis})</span>`;

            if (data.buku.length === 0) {
                list.innerHTML = '<div class="text-center text-muted">Tidak ada buku yang sedang dipinjam.</div>';
                return;
            }

            data.buku.forEach(b => {
                const div = document.createElement('div');
                div.className = 'item-buku';
                const infoTelat = b.telat
                    ? `<span class="text-danger small">⚠️ Telat ${b.hari_telat} hari (batas: ${b.tgl_kembali})</span>`
                    : `<span class="text-muted small">Batas kembali: ${b.tgl_kembali}</span>`;
                div.innerHTML = `
                    <div>
                        <div class="fw-semibold">${b.judul}</div>
                        <div>${infoTelat}</div>
                    </div>
                    <button class="btn btn-sm btn-primary" onclick="kembalikan(${b.id_transaksi}, this)">Kembalikan</button>
                `;
                list.appendChild(div);
            });
        })
        .catch(() => {
            document.getElementById('infoAnggota').className = 'alert alert-danger text-center mt-3 mb-3';
            document.getElementById('infoAnggota').innerText = '❌ Terjadi kesalahan koneksi ke server.';
        })
        .finally(() => {
            setTimeout(() => { sedangProses = false; }, 2000);
        });
    }

    function kembalikan(idTransaksi, tombol) {
        tombol.disabled = true;
        tombol.innerText = 'Memproses...';

        fetch('proses_pengembalian.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id_transaksi=' + encodeURIComponent(idTransaksi)
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'sukses') {
                Swal.fire({ icon: data.denda > 0 ? 'warning' : 'success', title: 'Berhasil', text: data.pesan, timer: 2200, showConfirmButton: false });
                tombol.closest('.item-buku').remove();
            } else {
                Swal.fire({ icon: 'error', title: 'Gagal', text: data.pesan });
                tombol.disabled = false;
                tombol.innerText = 'Kembalikan';
            }
        })
        .catch(() => {
            Swal.fire({ icon: 'error', title: 'Gagal', text: 'Terjadi kesalahan koneksi ke server.' });
            tombol.disabled = false;
            tombol.innerText = 'Kembalikan';
        });
    }

    const html5QrCode = new Html5Qrcode("reader");
    html5QrCode.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: { width: 240, height: 240 } },
        onScanSuccess
    ).catch(err => {
        document.getElementById('infoAnggota').className = 'alert alert-danger text-center mt-3 mb-3';
        document.getElementById('infoAnggota').innerText = 'Tidak bisa mengakses kamera: ' + err;
    });
</script>
</body>
</html>