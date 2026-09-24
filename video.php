<?php
$file = 'video.mp4';
header('Content-Type: video/mp4');
header('Content-Length: ' . filesize($file));
readfile($file);
?>