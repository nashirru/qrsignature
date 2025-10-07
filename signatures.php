<?php
// Direktori: /qr-signature/
// Nama File: signatures.php
// Path Lengkap: /qr-signature/signatures.php

require_once 'includes/auth.php';
require_once 'includes/db.php';

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $person_id = (int)$_POST['person_id'];
    $signature_id = $_POST['id'] ?? null;
    $signature_url = $_POST['existing_signature'] ?? '';
    $signature_base64 = $_POST['signature_base64'] ?? '';
    
    if (empty($person_id)) {
        $error = "Person harus dipilih.";
    } else {
        if (!empty($signature_base64) && strpos($signature_base64, 'data:image/png;base64,') === 0) {
            // Handle Base64 signature from canvas
            $signature_url = $signature_base64;
        } elseif (isset($_FILES['signature_file']) && $_FILES['signature_file']['error'] == 0) {
            // Handle file upload
            $target_dir = "assets/uploads/signatures/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }
            $file_name = time() . '_' . basename($_FILES["signature_file"]["name"]);
            $target_file = $target_dir . $file_name;
            $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            // Validation
            if ($_FILES["signature_file"]["size"] > 2000000) { // 2MB
                $error = "Ukuran file terlalu besar.";
            } elseif (!in_array($fileType, ['png', 'svg'])) {
                $error = "Hanya format PNG & SVG yang diizinkan.";
            }

            if (empty($error)) {
                if (move_uploaded_file($_FILES["signature_file"]["tmp_name"], $target_file)) {
                     // Hapus signature lama jika ada dan bukan base64
                     if (!empty($signature_url) && file_exists($signature_url) && strpos($signature_url, 'data:image') !== 0) {
                        unlink($signature_url);
                    }
                    $signature_url = $target_file;
                } else {
                    $error = "Gagal mengunggah tanda tangan.";
                }
            }
        }
    }


    if (empty($error)) {
        if ($signature_id) { // Update
            $stmt = $conn->prepare("UPDATE signatures SET person_id = ?, signature_url = ? WHERE id = ?");
            $stmt->bind_param("isi", $person_id, $signature_url, $signature_id);
            if ($stmt->execute()) {
                $success = "Tanda tangan berhasil diperbarui.";
            } else {
                $error = "Gagal memperbarui: " . $stmt->error;
            }
        } else { // Insert
            if (empty($signature_url)) {
                $error = "Tanda tangan tidak boleh kosong. Silakan upload file atau gambar langsung.";
            } else {
                $stmt = $conn->prepare("INSERT INTO signatures (person_id, signature_url) VALUES (?, ?)");
                $stmt->bind_param("is", $person_id, $signature_url);
                if ($stmt->execute()) {
                    $success = "Tanda tangan berhasil ditambahkan.";
                } else {
                    $error = "Gagal menambahkan: " . $stmt->error;
                }
            }
        }
        if (isset($stmt)) $stmt->close();
        
        if ($success) {
            header("Location: signatures.php?success=" . urlencode($success));
            exit();
        }
    }
}

// Handle delete
if ($action === 'delete' && $id) {
    $stmt = $conn->prepare("SELECT signature_url FROM signatures WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if (!empty($row['signature_url']) && file_exists($row['signature_url']) && strpos($row['signature_url'], 'data:image') !== 0) {
            unlink($row['signature_url']);
        }
    }
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM signatures WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header("Location: signatures.php?success=" . urlencode("Tanda tangan berhasil dihapus."));
        exit();
    } else {
        header("Location: signatures.php?error=" . urlencode("Gagal menghapus data."));
        exit();
    }
}

if (isset($_GET['success'])) {
    $success = htmlspecialchars($_GET['success']);
}
if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
}
require_once 'includes/header.php';

if ($action === 'add' || $action === 'edit') {
    $signature = null;
    if ($action === 'edit' && $id) {
        $stmt = $conn->prepare("SELECT id, person_id, signature_url FROM signatures WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $signature = $result->fetch_assoc();
        $stmt->close();
    }
    $persons = $conn->query("SELECT id, full_name FROM persons ORDER BY full_name");
?>
    <h1 class="text-2xl font-bold mb-4"><?php echo $action === 'add' ? 'Tambah Tanda Tangan' : 'Edit Tanda Tangan'; ?></h1>
    <?php if ($error) : ?><div class="bg-red-100 text-red-700 p-3 rounded mb-4"><?php echo $error; ?></div><?php endif; ?>
    <div class="bg-white p-6 rounded-xl shadow-md">
        <form id="signatureForm" method="POST" action="signatures.php" enctype="multipart/form-data" class="space-y-6">
            <input type="hidden" name="id" value="<?php echo $signature['id'] ?? ''; ?>">
            <input type="hidden" name="existing_signature" value="<?php echo htmlspecialchars($signature['signature_url'] ?? ''); ?>">
            <input type="hidden" name="signature_base64" id="signature_base64">
            
            <div>
                <label for="person_id" class="block text-sm font-medium text-gray-700">Person</label>
                <select name="person_id" id="person_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">-- Pilih Person --</option>
                    <?php while($p = $persons->fetch_assoc()): ?>
                        <option value="<?php echo $p['id']; ?>" <?php echo (isset($signature['person_id']) && $signature['person_id'] == $p['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($p['full_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Signature Input Tabs -->
            <div x-data="{ tab: 'upload' }" class="w-full">
                 <div class="mb-4 border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                        <button type="button" @click="tab = 'upload'" :class="{ 'border-blue-500 text-blue-600': tab === 'upload', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': tab !== 'upload' }" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">Upload File</button>
                        <button type="button" @click="tab = 'draw'; $nextTick(() => { resizeCanvas() });" :class="{ 'border-blue-500 text-blue-600': tab === 'draw', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': tab !== 'draw' }" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">Gambar Langsung</button>
                    </nav>
                </div>
                
                <div x-show="tab === 'upload'">
                    <label for="signature_file" class="block text-sm font-medium text-gray-700">Upload Tanda Tangan (PNG/SVG)</label>
                    <input type="file" name="signature_file" id="signature_file" accept=".png,.svg" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    <?php if (!empty($signature['signature_url'])): ?>
                        <p class="text-sm text-gray-600 mt-2">Tanda Tangan Saat Ini:</p>
                        <img src="<?php echo htmlspecialchars($signature['signature_url']); ?>" alt="Current Signature" class="mt-2 h-20 bg-gray-100 p-2 border rounded-md">
                    <?php endif; ?>
                </div>

                <div x-show="tab === 'draw'" x-cloak>
                    <label class="block text-sm font-medium text-gray-700">Area Gambar</label>
                    <div class="mt-1 border border-gray-300 rounded-md touch-none">
                        <canvas id="signature-pad" class="w-full h-48"></canvas>
                    </div>
                    <button type="button" id="clear-signature" class="mt-2 text-sm text-blue-600 hover:underline">Bersihkan</button>
                </div>
            </div>

            <div class="flex items-center space-x-4 pt-4">
                <button type="submit" id="save-btn" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-500 active:bg-blue-700">Simpan</button>
                <a href="signatures.php" class="bg-gray-200 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-300">Batal</a>
            </div>
        </form>
    </div>
    <!-- AlpineJS and Signature Pad -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<?php
} else { // List view
?>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Manage Signatures</h1>
        <a href="signatures.php?action=add" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-500 active:bg-blue-700">Tambah Tanda Tangan</a>
    </div>

    <?php if ($success) : ?><div class="bg-blue-100 text-blue-700 p-3 rounded mb-4"><?php echo $success; ?></div><?php endif; ?>
    <?php if ($error) : ?><div class="bg-red-100 text-red-700 p-3 rounded mb-4"><?php echo $error; ?></div><?php endif; ?>

    <div class="bg-white rounded-xl shadow-md overflow-hidden">
         <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3">Tanda Tangan</th>
                        <th scope="col" class="px-6 py-3">Milik</th>
                        <th scope="col" class="px-6 py-3">Dibuat Pada</th>
                        <th scope="col" class="px-6 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT s.id, s.signature_url, s.created_at, p.full_name 
                            FROM signatures s 
                            JOIN persons p ON s.person_id = p.id 
                            ORDER BY s.created_at DESC";
                    $result = $conn->query($sql);
                    if ($result->num_rows > 0):
                        while($row = $result->fetch_assoc()): ?>
                            <tr class="bg-white border-b hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <img src="<?php echo htmlspecialchars($row['signature_url']); ?>" alt="Signature" class="h-10 bg-gray-100 p-1 border rounded">
                                </td>
                                <th scope="row" class="px-6 py-4 font-medium text-gray-900">
                                    <?php echo htmlspecialchars($row['full_name']); ?>
                                </th>
                                <td class="px-6 py-4">
                                    <?php echo date('d M Y, H:i', strtotime($row['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 flex items-center space-x-2">
                                    <a href="signatures.php?action=edit&id=<?php echo $row['id']; ?>" class="font-medium text-blue-600 hover:underline">Edit</a>
                                    <a href="signatures.php?action=delete&id=<?php echo $row['id']; ?>" class="font-medium text-red-600 hover:underline" onclick="return confirm('Apakah Anda yakin ingin menghapus data ini? Ini juga akan menghapus QR code terkait.');">Hapus</a>
                                </td>
                            </tr>
                        <?php endwhile;
                    else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-4">Belum ada data tanda tangan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php
}
require_once 'includes/footer.php';
?>