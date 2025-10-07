<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Tanda Tangan QR</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" xintegrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        /* Transisi untuk sidebar */
        .sidebar { transition: transform 0.3s ease-in-out; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen bg-gray-100">
        <!-- Overlay untuk Mobile Menu -->
        <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-20 hidden md:hidden"></div>

        <!-- Sidebar -->
        <div id="sidebar" class="sidebar fixed inset-y-0 left-0 w-64 bg-white shadow-lg transform -translate-x-full md:relative md:translate-x-0 z-30">
            <div class="flex items-center justify-center h-20 shadow-md">
                <h1 class="text-2xl font-bold text-blue-600">QR Signature</h1>
            </div>
            <nav class="flex-grow mt-5">
                <a href="dashboard.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-200">
                    <i class="fas fa-tachometer-alt w-6 h-6"></i>
                    <span class="mx-3">Dashboard</span>
                </a>
                <a href="persons.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-200">
                    <i class="fas fa-users w-6 h-6"></i>
                    <span class="mx-3">Data Diri</span>
                </a>
                <a href="signatures.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-200">
                     <i class="fas fa-signature w-6 h-6"></i>
                    <span class="mx-3">Tanda Tangan</span>
                </a>
                <a href="qr_list.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-200">
                    <i class="fas fa-qrcode w-6 h-6"></i>
                    <span class="mx-3">QR Codes</span>
                </a>
                 <a href="index.php?logout=true" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-200">
                    <i class="fas fa-sign-out-alt w-6 h-6"></i>
                    <span class="mx-3">Logout</span>
                </a>
            </nav>
        </div>

        <!-- Content -->
        <div class="flex flex-col flex-1 overflow-y-auto">
            <!-- Mobile Header -->
            <header class="md:hidden bg-white shadow-md p-4 flex justify-between items-center sticky top-0 z-10">
                 <h1 class="text-xl font-bold text-blue-600">QR Signature</h1>
                <button id="hamburger-btn" class="text-gray-700 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
                </button>
            </header>
            <main class="p-4 sm:p-6 md:p-8">