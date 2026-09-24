<?php
include "../koneksi.php";

$data = mysqli_query($koneksi,"
SELECT transaksi.*, anggota.nama, buku.judul
FROM transaksi
JOIN anggota ON transaksi.id_anggota = anggota.id_anggota
JOIN buku ON transaksi.id_buku = buku.id_buku
ORDER BY transaksi.id_transaksi DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Transaksi</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body onload="window.print()">

<div class="container mt-4">

    <h2 class="text-center fw-bold">Laporan Data Transaksi</h2>
    <hr>

    <table class="table table-bordered align-middle">
        <thead>
            <tr class="table-dark text-center">
                <th width="50">No</th>
                <th>Nama Anggota</th>
                <th>Judul Buku</th>
                <th>Tanggal Pinjam</th>
                <th>Tanggal Kembali</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            if(mysqli_num_rows($data) > 0){
                while($d = mysqli_fetch_array($data)){
            ?>
            <tr>
                <td class="text-center"><?= $no++; ?></td>
                <td><?= $d['nama']; ?></td>
                <td><?= $d['judul']; ?></td>
                <td class="text-center"><?= $d['tgl_pinjam']; ?></td>
                <td class="text-center"><?= $d['tgl_kembali']; ?></td>
                <td class="text-center"><?= $d['status']; ?></td>
            </tr>
            <?php 
                } 
            } else {
            ?>
            <tr>
                <td colspan="6" class="text-center text-muted">Belum ada data transaksi.</td>
            </tr>
            <?php } ?>
        </tbody>
    </table>

</div>

</body>
</html>