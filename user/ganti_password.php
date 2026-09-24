<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['id_anggota'])) {
    header("Location: ../login_user.php");
    exit;
}

$id = $_SESSION['id_anggota'];

if(isset($_POST['simpan'])){

    $password_lama = $_POST['password_lama'];
    $password_baru = $_POST['password_baru'];
    $konfirmasi = $_POST['konfirmasi'];

    $query = mysqli_query($koneksi,"SELECT * FROM anggota WHERE id_anggota='$id'");
    $user = mysqli_fetch_assoc($query);

    if($password_lama != $user['password']){

        echo "<script>alert('Password lama salah!');</script>";

    }elseif($password_baru != $konfirmasi){

        echo "<script>alert('Konfirmasi password tidak sama!');</script>";

    }else{

        mysqli_query($koneksi,"
        UPDATE anggota
        SET password='$password_baru'
        WHERE id_anggota='$id'
        ");

        echo "<script>
        alert('Password berhasil diubah');
        window.location='profil.php';
        </script>";

    }

}
?>

<!DOCTYPE html>
<html>
<head>

<meta charset="UTF-8">

<title>Ganti Password</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body class="bg-light">

<div class="container mt-5">

<div class="card shadow">

<div class="card-header bg-success text-white">

<h3>Ganti Password</h3>

</div>

<div class="card-body">

<form method="POST">

<div class="mb-3">
<label>Password Lama</label>
<input type="password" name="password_lama" class="form-control" required>
</div>

<div class="mb-3">
<label>Password Baru</label>
<input type="password" name="password_baru" class="form-control" required>
</div>

<div class="mb-3">
<label>Konfirmasi Password</label>
<input type="password" name="konfirmasi" class="form-control" required>
</div>

<button class="btn btn-success" name="simpan">
Simpan
</button>

<a href="profil.php" class="btn btn-secondary">
Kembali
</a>

</form>

</div>

</div>

</div>

</body>
</html>