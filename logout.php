<?php
// ==========================================================
// logout.php
// Taruh file ini di folder utama (perpustakaan/logout.php)
// Fungsinya cuma satu: hapus semua data sesi login, lalu
// arahkan kembali ke halaman login.
// ==========================================================
session_start();

// Hapus semua data sesi (id_anggota, role, username, dll)
$_SESSION = array();

// Hapus cookie sesi juga (opsional tapi lebih bersih)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Hancurkan sesinya
session_destroy();

// Arahkan ke landing page (satu folder yang sama dengan logout.php ini)
header("Location: index.php");
exit;