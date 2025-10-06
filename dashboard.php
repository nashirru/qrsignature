<?php
// Direktori: /qr-signature/
// Nama File: dashboard.php
// Path Lengkap: /qr-signature/dashboard.php

require_once 'includes/auth.php';
require_once 'includes/db.php';

// Fetch stats
$persons_count = $conn->query("SELECT COUNT(*) as count FROM persons")->fetch_assoc()['count'];
$signatures_count = $conn->query("SELECT COUNT(*) as count FROM signatures")->fetch_assoc()['count'];
$qrcodes_count = $conn->query("SELECT COUNT(*) as count FROM qr_codes")->fetch_assoc()['count'];

require_once 'includes/header.php';
?>

<h1 class="text-3xl font-bold text-gray-800 mb-6">Dashboard</h1>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
    <!-- Card Persons -->
    <div class="bg-white p-6 rounded-xl shadow-md flex items-center justify-between">
        <div>
            <p class="text-sm font-medium text-gray-500">Total Persons</p>
            <p class="text-3xl font-bold text-blue-600"><?php echo $persons_count; ?></p>
        </div>
        <a href="persons.php" class="bg-blue-100 text-blue-600 hover:bg-blue-200 p-3 rounded-full">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
        </a>
    </div>

    <!-- Card Signatures -->
    <div class="bg-white p-6 rounded-xl shadow-md flex items-center justify-between">
        <div>
            <p class="text-sm font-medium text-gray-500">Total Signatures</p>
            <p class="text-3xl font-bold text-blue-600"><?php echo $signatures_count; ?></p>
        </div>
        <a href="signatures.php" class="bg-blue-100 text-blue-600 hover:bg-blue-200 p-3 rounded-full">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
        </a>
    </div>

    <!-- Card QR Codes -->
    <div class="bg-white p-6 rounded-xl shadow-md flex items-center justify-between">
        <div>
            <p class="text-sm font-medium text-gray-500">Total QR Codes</p>
            <p class="text-3xl font-bold text-blue-600"><?php echo $qrcodes_count; ?></p>
        </div>
         <a href="qr_list.php" class="bg-blue-100 text-blue-600 hover:bg-blue-200 p-3 rounded-full">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6.5 6.5l-1.5-1.5M4 12H2m17.5 6.5l-1.5-1.5M12 20v-1m6-11h-2M4.5 7.5l1.5 1.5M12 8V7m-6 5h2m1.5-6.5l1.5 1.5" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
        </a>
    </div>
</div>

<div class="mt-8 bg-white p-6 rounded-xl shadow-md">
    <h2 class="text-xl font-semibold text-gray-700">Selamat Datang, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h2>
    <p class="mt-2 text-gray-600">
        Gunakan menu di samping untuk mengelola data person, tanda tangan, dan membuat QR code.
    </p>
</div>

<?php
require_once 'includes/footer.php';
?>