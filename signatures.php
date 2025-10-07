<?php
// Direktori: /qr-signature/
// Nama File: signatures.php

require_once 'includes/auth.php';
require_once 'includes/db.php';
$error = '';
$success = '';

// Handle form submission for adding/editing a signature
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $person_id = (int)$_POST['person_id'];
    $signature_url = null;

    if ($person_id <= 0) {
        $error = "Silakan pilih data diri.";
    } else {
        $upload_dir = 'assets/uploads/signatures/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Handle drawn signature (prioritas pertama)
        if (!empty($_POST['signature_data'])) {
            $data = $_POST['signature_data'];
            list($type, $data) = explode(';', $data);
            list(, $data)      = explode(',', $data);
            $data = base64_decode($data);
            $file_name = time() . '_' . uniqid() . '.png';
            $target_file = $upload_dir . $file_name;
            if (file_put_contents($target_file, $data)) {
                $signature_url = $target_file;
            } else {
                $error = "Gagal menyimpan gambar tanda tangan.";
            }
        }
        // Handle file upload
        elseif (isset($_FILES['signature_image']) && $_FILES['signature_image']['error'] == UPLOAD_ERR_OK) {
            $file_name = time() . '_' . basename($_FILES['signature_image']['name']);
            $target_file = $upload_dir . $file_name;
            
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            if (!in_array($imageFileType, ['png', 'jpg', 'jpeg'])) {
                 $error = "Hanya format PNG, JPG, dan JPEG yang diizinkan untuk tanda tangan.";
            } elseif ($_FILES['signature_image']['size'] > 2000000) { // 2MB
                $error = "Ukuran file terlalu besar. Maksimal 2MB.";
            } else {
                if (move_uploaded_file($_FILES['signature_image']['tmp_name'], $target_file)) {
                    $signature_url = $target_file;
                } else {
                    $error = "Gagal mengunggah tanda tangan.";
                }
            }
        }

        if (empty($error)) {
             if ($id > 0) { // Update
                if ($signature_url) {
                    // Hapus file lama jika ada
                    $stmt = $conn->prepare("SELECT signature_url FROM signatures WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if($old_sig = $result->fetch_assoc()) {
                        if (!empty($old_sig['signature_url']) && file_exists($old_sig['signature_url'])) {
                            unlink($old_sig['signature_url']);
                        }
                    }
                    $stmt->close();
                    
                    $stmt = $conn->prepare("UPDATE signatures SET person_id = ?, signature_url = ? WHERE id = ?");
                    $stmt->bind_param("isi", $person_id, $signature_url, $id);
                    $success = "Tanda tangan berhasil diperbarui.";
                } else { // No new signature provided, just update person
                    $stmt = $conn->prepare("UPDATE signatures SET person_id = ? WHERE id = ?");
                    $stmt->bind_param("ii", $person_id, $id);
                    $success = "Data diri untuk tanda tangan berhasil diperbarui.";
                }
            } else { // Insert
                if ($signature_url) {
                    $stmt = $conn->prepare("INSERT INTO signatures (person_id, signature_url) VALUES (?, ?)");
                    $stmt->bind_param("is", $person_id, $signature_url);
                    $success = "Tanda tangan berhasil ditambahkan.";
                } else {
                    $error = "Tidak ada file tanda tangan yang diunggah atau digambar.";
                }
            }

            if (isset($stmt) && $stmt->execute()) {
                // Berhasil - redirect untuk clear POST data
                if ($success) {
                    header("Location: signatures.php?success=" . urlencode($success));
                    exit();
                }
            } else if (isset($stmt)) {
                $error = "Operasi gagal: " . $stmt->error;
                $success = '';
            }
            if(isset($stmt)) $stmt->close();
        }
    }
}

// Tampilkan success message dari redirect
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}

// Handle delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT signature_url FROM signatures WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if($sig = $result->fetch_assoc()) {
        if (!empty($sig['signature_url']) && file_exists($sig['signature_url'])) {
            unlink($sig['signature_url']);
        }
    }
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM signatures WHERE id = ?");
    $stmt->bind_param("i", $id);
    if($stmt->execute()) {
        header("Location: signatures.php?success=" . urlencode("Tanda tangan berhasil dihapus."));
        exit();
    } else {
        $error = "Gagal menghapus data.";
    }
    $stmt->close();
}

$edit_signature = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM signatures WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_signature = $result->fetch_assoc();
    $stmt->close();
}

require_once 'includes/header.php';
?>

<div class="flex flex-col md:flex-row justify-between md:items-center mb-6 gap-4">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Manajemen Tanda Tangan</h1>
    <button id="open-add-signature-modal" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 shadow-md flex items-center justify-center">
        <i class="fas fa-plus mr-2"></i>
        <span>Tambah Tanda Tangan</span>
    </button>
</div>


<?php if ($success) : ?><div class="bg-green-100 text-green-700 p-3 rounded mb-4 flex items-center"><i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
<?php if ($error) : ?><div class="bg-red-100 text-red-700 p-3 rounded mb-4 flex items-center"><i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<!-- Add/Edit Signature Modal -->
<div id="add-signature-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-40">
    <div class="bg-white p-6 rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-semibold"><?php echo $edit_signature ? 'Edit Tanda Tangan' : 'Tambah Tanda Tangan Baru'; ?></h2>
            <button class="close-modal text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
        </div>
        
        <form method="POST" action="signatures.php" enctype="multipart/form-data" id="signature-form">
            <input type="hidden" name="id" value="<?php echo $edit_signature['id'] ?? ''; ?>">
            
            <div class="mb-4">
                <label for="person_id" class="block text-sm font-medium text-gray-700 mb-2">Pilih Data Diri <span class="text-red-500">*</span></label>
                <select name="person_id" id="person_id" required class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2">
                    <option value="">-- Pilih Data Diri --</option>
                    <?php
                    $sql = "SELECT * FROM persons ORDER BY full_name";
                    $result = $conn->query($sql);
                    while($row = $result->fetch_assoc()): ?>
                        <option value="<?php echo $row['id']; ?>" <?php echo (isset($edit_signature) && $edit_signature['person_id'] == $row['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($row['full_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="mb-4 border-b border-gray-200">
                <p class="text-sm text-gray-600 mb-3">Pilih metode untuk menambahkan tanda tangan:</p>
                <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" role="tablist">
                    <li class="mr-2" role="presentation">
                        <button class="inline-block p-4 border-b-2 rounded-t-lg text-gray-500" id="upload-tab" type="button" role="tab">
                            <i class="fas fa-upload mr-2"></i>Upload Gambar
                        </button>
                    </li>
                    <li role="presentation">
                        <button class="inline-block p-4 border-b-2 rounded-t-lg text-gray-500" id="draw-tab" type="button" role="tab">
                            <i class="fas fa-pencil-alt mr-2"></i>Gambar Langsung
                        </button>
                    </li>
                </ul>
            </div>
            
            <div>
                <!-- Upload Tab -->
                <div id="upload-tab-content" role="tabpanel">
                    <label for="signature_image" class="block text-sm font-medium text-gray-700 mb-2">
                        Upload File Tanda Tangan (PNG, JPG, JPEG)
                    </label>
                    <input type="file" name="signature_image" id="signature_image" accept="image/png,image/jpeg,image/jpg" 
                           class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-gray-300 rounded-md">
                    <p class="text-xs text-gray-500 mt-2">Maksimal ukuran file: 2MB</p>
                    
                    <?php if ($edit_signature && !empty($edit_signature['signature_url'])): ?>
                    <div class="mt-4 p-3 bg-gray-50 rounded-md">
                        <p class="text-sm text-gray-600 mb-2">Tanda tangan saat ini:</p>
                        <img src="<?php echo htmlspecialchars($edit_signature['signature_url']); ?>" alt="Current Signature" class="max-h-24 bg-white p-2 border border-gray-200 rounded">
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Draw Tab -->
                <div id="draw-tab-content" class="hidden" role="tabpanel">
                    <p class="text-sm text-gray-600 mb-3">
                        <i class="fas fa-info-circle text-blue-500 mr-1"></i>
                        Gambar tanda tangan Anda di area di bawah ini menggunakan mouse atau touchscreen.
                    </p>
                    <div class="border-2 border-gray-300 rounded-lg relative bg-gray-50" style="height: 250px;">
                        <canvas id="signature-pad" class="absolute top-0 left-0 w-full h-full bg-gray-100 rounded-lg cursor-crosshair"></canvas>
                    </div>
                    <input type="hidden" name="signature_data" id="signature_data">
                    <div class="mt-4 flex justify-between items-center">
                        <p class="text-xs text-gray-500">
                            <i class="fas fa-mouse-pointer mr-1"></i>
                            Gunakan mouse atau sentuh layar untuk menggambar
                        </p>
                        <button type="button" id="clear-signature-btn" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 transition-colors">
                            <i class="fas fa-eraser mr-2"></i>Bersihkan
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex justify-end mt-6 pt-4 border-t gap-3">
                <button type="button" class="close-modal bg-gray-200 text-gray-800 px-5 py-2 rounded-lg hover:bg-gray-300 transition-colors">
                    <i class="fas fa-times mr-2"></i>Batal
                </button>
                <button type="submit" class="bg-blue-600 text-white px-5 py-2 rounded-lg hover:bg-blue-700 active:bg-blue-800 transition-colors">
                     <i class="fas fa-save mr-2"></i><?php echo $edit_signature ? 'Perbarui' : 'Simpan'; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Signatures List -->
<div class="bg-white rounded-xl shadow-md overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">Tanda Tangan</th>
                    <th scope="col" class="px-6 py-3">Nama</th>
                    <th scope="col" class="px-6 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $sql = "SELECT s.id, s.signature_url, p.full_name FROM signatures s JOIN persons p ON s.person_id = p.id ORDER BY p.full_name";
            $result = $conn->query($sql);
            if ($result->num_rows > 0):
                while($row = $result->fetch_assoc()): ?>
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <img src="<?php echo htmlspecialchars($row['signature_url']); ?>" alt="Signature" class="h-16 max-w-xs bg-gray-100 p-2 rounded border border-gray-200">
                        </td>
                        <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                            <?php echo htmlspecialchars($row['full_name']); ?>
                        </th>
                        <td class="px-6 py-4">
                            <div class="flex items-center space-x-4">
                                <a href="signatures.php?action=edit&id=<?php echo $row['id']; ?>" class="text-blue-600 hover:text-blue-800 transition-colors" title="Edit">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="signatures.php?action=delete&id=<?php echo $row['id']; ?>" class="text-red-600 hover:text-red-800 transition-colors" onclick="return confirm('Yakin ingin menghapus tanda tangan ini?');" title="Hapus">
                                    <i class="fas fa-trash-alt"></i> Hapus
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endwhile;
            else: ?>
                <tr>
                    <td colspan="3" class="text-center py-8 text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-2 block"></i>
                        <p>Tidak ada data tanda tangan.</p>
                        <p class="text-sm mt-1">Klik tombol "Tambah Tanda Tangan" untuk menambah data baru.</p>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($edit_signature): ?>
<script>
// Auto-open modal untuk edit mode
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('add-signature-modal');
    if (modal) {
        setTimeout(() => {
            modal.classList.remove('hidden');
            console.log('Modal opened for edit mode');
        }, 100);
    }
});
</script>
<?php endif; ?>

<?php
require_once 'includes/footer.php';
?>