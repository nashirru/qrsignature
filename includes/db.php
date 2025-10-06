<?php
// includes/db.php
require_once __DIR__.'/../config/config.php';

// Membuat koneksi ke database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Set a custom error handler
function handle_db_error($message, $query = "") {
    // Log error to a file in a real application
    // error_log($message . "\nQuery: " . $query);
    die("Terjadi kesalahan pada database. Silakan coba lagi nanti.");
}
?>