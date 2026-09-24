<?php
session_start();
session_unset();
session_destroy();

// Diarahkan ke login.php di folder utama
header("Location: ../login.php");
exit;
?>