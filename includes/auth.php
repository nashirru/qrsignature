<?php
// includes/auth.php
session_start();

// Jika pengguna tidak login, redirect ke halaman login
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
?>