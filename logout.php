<?php
session_start();
session_destroy();
header('Location: loreg.php');
exit;
?>
