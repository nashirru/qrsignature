<?php
// Direktori: /qr-signature/
// Nama File: s.php
// Path Lengkap: /qr-signature/s.php

require_once 'includes/db.php';

$short_code = $_GET['id'] ?? null;
if (!$short_code) {
    die("ID tidak valid.");
}

$stmt = $conn->prepare(
    "SELECT p.full_name, p.position, p.photo_url, s.signature_url, qr.expires_at 
     FROM qr_codes qr
     JOIN signatures s ON qr.target_id = s.id
     JOIN persons p ON s.person_id = p.id
     WHERE qr.short_code = ?"
);
$stmt->bind_param("s", $short_code);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->close();

if (!$data) {
    http_response_code(404);
    $page_title = "Tidak Ditemukan";
    $error_message = "Tanda tangan digital dengan kode ini tidak ditemukan.";
} else {
    $page_title = "Verifikasi Tanda Tangan";
    $is_expired = ($data['expires_at'] && strtotime($data['expires_at']) < time());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Inter', sans-serif;
            background-color: #f0f4f8; /* Light blue-gray background */
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">
    <div class="w-full max-w-sm mx-auto">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <?php if (isset($error_message)): ?>
                <div class="p-8 text-center">
                    <h1 class="text-2xl font-bold text-red-600">Error 404</h1>
                    <p class="text-gray-600 mt-2"><?php echo $error_message; ?></p>
                    <a href="<?php echo BASE_URL; ?>" class="mt-6 inline-block bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-500">Kembali</a>
                </div>
            <?php else: ?>
                <div class="bg-blue-600 p-4 text-center">
                    <h1 class="text-xl font-bold text-white">Tanda Tangan Digital</h1>
                </div>

                <?php if ($is_expired): ?>
                <div class="bg-red-100 p-4 text-center">
                    <p class="font-semibold text-red-700">❌ Tanda Tangan Tidak Valid (Kedaluwarsa)</p>
                </div>
                <?php else: ?>
                <div class="bg-green-100 p-4 text-center">
                    <p class="font-semibold text-green-700">✔️ Tanda Tangan Valid</p>
                </div>
                <?php endif; ?>

                <div class="p-6">
                    <div class="text-center">
                        <img class="w-24 h-24 mx-auto rounded-full object-cover ring-4 ring-blue-200" 
                             src="<?php echo htmlspecialchars($data['photo_url'] ?: 'https://placehold.co/100x100/E2E8F0/4A5568?text=Foto'); ?>" 
                             alt="Foto <?php echo htmlspecialchars($data['full_name']); ?>">
                        <h2 class="mt-4 text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($data['full_name']); ?></h2>
                        <p class="text-gray-600 text-md"><?php echo htmlspecialchars($data['position']); ?></p>
                    </div>

                    <div class="mt-6 border-t pt-4">
                        <p class="text-sm text-center text-gray-500 mb-2">Tanda Tangan:</p>
                        <div class="flex justify-center bg-gray-50 p-4 rounded-lg border">
                             <img src="<?php echo htmlspecialchars($data['signature_url']); ?>" alt="Tanda Tangan" class="h-20">
                        </div>
                    </div>
                </div>

                <footer class="bg-gray-50 px-6 py-3 text-center text-xs text-gray-500 border-t">
                    Dokumen diverifikasi oleh QR Signature
                </footer>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>