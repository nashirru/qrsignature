<?php
// Direktori: /qr-signature/
// Nama File: qr_list.php
// Path Lengkap: /qr-signature/qr_list.php

require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'lib/phpqrcode/qrlib.php';

$error = '';
$success = '';

// Handle QR generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_qr'])) {
    $signature_id = (int)$_POST['signature_id'];
    $expires_at = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;

    if ($signature_id > 0) {
        $short_code = substr(md5(uniqid(rand(), true)), 0, 8);
        $public_url = BASE_URL . 's.php?id=' . $short_code;
        
        $qr_dir = 'assets/qr/';
        if (!is_dir($qr_dir)) {
            mkdir($qr_dir, 0755, true);
        }
        $qr_file = $qr_dir . $short_code . '.png';

        // Generate QR Code
        QRcode::png($public_url, $qr_file, QR_ECLEVEL_L, 10);
        
        $stmt = $conn->prepare("INSERT INTO qr_codes (target_id, short_code, expires_at) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $signature_id, $short_code, $expires_at);
        if ($stmt->execute()) {
            $success = "QR Code berhasil dibuat.";
        } else {
            $error = "Gagal menyimpan data QR Code: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = "Silakan pilih tanda tangan terlebih dahulu.";
    }
}

// Handle delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT short_code FROM qr_codes WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if($qr = $result->fetch_assoc()) {
        $qr_file = 'assets/qr/' . $qr['short_code'] . '.png';
        if (file_exists($qr_file)) {
            unlink($qr_file);
        }
    }
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM qr_codes WHERE id = ?");
    $stmt->bind_param("i", $id);
    if($stmt->execute()) {
        $success = "QR Code berhasil dihapus.";
    } else {
        $error = "Gagal menghapus QR Code.";
    }
}


require_once 'includes/header.php';
?>
<div class="flex flex-col md:flex-row justify-between md:items-center mb-6 gap-4">
    <h1 class="text-3xl font-bold text-gray-800">QR Codes</h1>
</div>

<?php if ($success) : ?><div class="bg-blue-100 text-blue-700 p-3 rounded mb-4"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
<?php if ($error) : ?><div class="bg-red-100 text-red-700 p-3 rounded mb-4"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>


<!-- Generate QR Form -->
<div class="bg-white p-6 rounded-xl shadow-md mb-8">
    <h2 class="text-xl font-semibold mb-4">Generate QR Code Baru</h2>
    <form method="POST" action="qr_list.php" class="flex flex-col sm:flex-row sm:items-end gap-4">
        <div class="flex-grow">
            <label for="signature_id" class="block text-sm font-medium text-gray-700">Pilih Tanda Tangan</label>
            <select name="signature_id" id="signature_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">-- Pilih --</option>
                <?php
                $sql = "SELECT s.id, p.full_name, p.position FROM signatures s JOIN persons p ON s.person_id = p.id ORDER BY p.full_name";
                $result = $conn->query($sql);
                while($row = $result->fetch_assoc()): ?>
                    <option value="<?php echo $row['id']; ?>">
                        <?php echo htmlspecialchars($row['full_name']) . ' - ' . htmlspecialchars($row['position']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div>
            <label for="expires_at" class="block text-sm font-medium text-gray-700">Tanggal Kedaluwarsa (Opsional)</label>
            <input type="date" name="expires_at" id="expires_at" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        </div>
        <div class="flex-shrink-0">
             <button type="submit" name="generate_qr" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-500 active:bg-blue-700">Generate</button>
        </div>
    </form>
</div>


<!-- QR List -->
<div class="bg-white rounded-xl shadow-md overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">QR Code</th>
                    <th scope="col" class="px-6 py-3">Milik</th>
                    <th scope="col" class="px-6 py-3">Link Publik</th>
                    <th scope="col" class="px-6 py-3">Kedaluwarsa</th>
                    <th scope="col" class="px-6 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT qr.id, qr.short_code, qr.expires_at, p.full_name 
                        FROM qr_codes qr 
                        JOIN signatures s ON qr.target_id = s.id
                        JOIN persons p ON s.person_id = p.id
                        ORDER BY qr.id DESC";
                $result = $conn->query($sql);
                if ($result->num_rows > 0):
                    while($row = $result->fetch_assoc()): 
                        $public_link = BASE_URL . 's.php?id=' . $row['short_code'];
                        $qr_image_path = 'assets/qr/' . $row['short_code'] . '.png';
                    ?>
                        <tr class="bg-white border-b hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <?php if(file_exists($qr_image_path)): ?>
                                <img src="<?php echo $qr_image_path; ?>" alt="QR Code" class="h-16 w-16">
                                <?php else: ?>
                                <span class="text-xs text-red-500">File tidak ditemukan</span>
                                <?php endif; ?>
                            </td>
                            <th scope="row" class="px-6 py-4 font-medium text-gray-900">
                                <?php echo htmlspecialchars($row['full_name']); ?>
                            </th>
                            <td class="px-6 py-4">
                                <a href="<?php echo $public_link; ?>" target="_blank" class="text-blue-600 hover:underline text-xs"><?php echo $public_link; ?></a>
                            </td>
                            <td class="px-6 py-4">
                                <?php echo $row['expires_at'] ? date('d M Y', strtotime($row['expires_at'])) : 'Tidak ada'; ?>
                            </td>
                            <td class="px-6 py-4">
                                <a href="qr_list.php?action=delete&id=<?php echo $row['id']; ?>" class="font-medium text-red-600 hover:underline" onclick="return confirm('Yakin ingin menghapus QR code ini?');">Hapus</a>
                            </td>
                        </tr>
                    <?php endwhile;
                else: ?>
                    <tr>
                        <td colspan="5" class="text-center py-4">Belum ada QR Code yang dibuat.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>