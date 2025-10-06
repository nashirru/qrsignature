<?php
// includes/header.php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Signature - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .active-nav {
            background-color: #1d4ed8; /* bg-blue-700 */
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">
    <div class="min-h-screen flex flex-col md:flex-row">
        <!-- Sidebar Navigation for Desktop -->
        <aside class="w-full md:w-64 bg-blue-800 text-white flex-shrink-0">
            <div class="p-4 text-center md:text-left">
                <h1 class="text-2xl font-bold">QR Signature</h1>
            </div>
            <nav class="mt-4">
                <a href="dashboard.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-blue-700 <?php echo $current_page == 'dashboard.php' ? 'active-nav' : ''; ?>">Dashboard</a>
                <a href="persons.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-blue-700 <?php echo $current_page == 'persons.php' ? 'active-nav' : ''; ?>">Persons</a>
                <a href="signatures.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-blue-700 <?php echo $current_page == 'signatures.php' ? 'active-nav' : ''; ?>">Signatures</a>
                <a href="qr_list.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-blue-700 <?php echo $current_page == 'qr_list.php' ? 'active-nav' : ''; ?>">QR Codes</a>
                <a href="index.php?action=logout" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-blue-700">Logout</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-4 sm:p-6 lg:p-8">
            <div class="max-w-4xl mx-auto">