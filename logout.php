<?php
session_start();
session_destroy();
header("Location: ../pages/admin/login.php");
?>
