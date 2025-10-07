<?php
// Direktori: /qr-signature/
// Nama File: persons.php

require_once 'includes/auth.php';
require_once 'includes/db.php';
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $full_name = trim($_POST['full_name']);
    $position = trim($_POST['position']);
    $photo_url = $_POST['existing_photo'] ?? null;

    if (empty($full_name) || empty($position)) {
        $error = "Nama lengkap dan jabatan harus diisi.";
    } else {
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = 'assets/uploads/photos/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $file_name = time() . '_' . basename($_FILES['photo']['name']);
            $target_file = $upload_dir . $file_name;
            
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
                 $error = "Hanya format JPG, JPEG, PNG & GIF yang diizinkan.";
            } elseif ($_FILES['photo']['size'] > 2000000) {
                $error = "Ukuran file terlalu besar. Maksimal 2MB.";
            } elseif (move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
                // Hapus foto lama jika ada
                if ($photo_url && file_exists($photo_url)) {
                    unlink($photo_url);
                }
                $photo_url = $target_file;
            } else {
                $error = "Gagal mengunggah foto.";
            }
        }

        if (empty($error)) {
            if ($id > 0) {
                $stmt = $conn->prepare("UPDATE persons SET full_name = ?, position = ?, photo_url = ? WHERE id = ?");
                $stmt->bind_param("sssi", $full_name, $position, $photo_url, $id);
                $success = "Data diri berhasil diperbarui.";
            } else {
                $stmt = $conn->prepare("INSERT INTO persons (full_name, position, photo_url) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $full_name, $position, $photo_url);
                $success = "Data diri berhasil ditambahkan.";
            }

            if (!$stmt->execute()) {
                $error = "Operasi gagal: " . $stmt->error;
                $success = '';
            }
            $stmt->close();
        }
    }
}


// Handle delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT photo_url FROM persons WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if($person = $result->fetch_assoc()) {
        if (!empty($person['photo_url']) && file_exists($person['photo_url'])) {
            unlink($person['photo_url']);
        }
    }
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM persons WHERE id = ?");
    $stmt->bind_param("i", $id);
    if($stmt->execute()){
         $success = "Data diri berhasil dihapus.";
    } else {
        $error = "Gagal menghapus data.";
    }
    $stmt->close();
}

$edit_person = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM persons WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_person = $result->fetch_assoc();
    $stmt->close();
}

require_once 'includes/header.php';
?>

<div class="flex flex-col md:flex-row justify-between md:items-center mb-6 gap-4">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Manajemen Data Diri</h1>
     <button id="open-add-person-modal" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 shadow-md flex items-center justify-center">
        <i class="fas fa-plus mr-2"></i>
        <span>Tambah Data Diri</span>
    </button>
</div>


<?php if ($success) : ?><div class="bg-green-100 text-green-700 p-3 rounded mb-4"><?php echo $success; ?></div><?php endif; ?>
<?php if ($error) : ?><div class="bg-red-100 text-red-700 p-3 rounded mb-4"><?php echo $error; ?></div><?php endif; ?>

<!-- Add/Edit Person Modal -->
<div id="add-person-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 hidden z-40">
    <div class="bg-white p-6 rounded-xl shadow-2xl w-full max-w-lg">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-semibold"><?php echo $edit_person ? 'Edit Data Diri' : 'Tambah Data Diri Baru'; ?></h2>
            <button class="close-modal text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
        </div>
        <form method="POST" action="persons.php" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo $edit_person['id'] ?? ''; ?>">
            <input type="hidden" name="existing_photo" value="<?php echo $edit_person['photo_url'] ?? ''; ?>">
            <div class="space-y-4">
                <div>
                    <label for="full_name" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                    <input type="text" name="full_name" id="full_name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" value="<?php echo htmlspecialchars($edit_person['full_name'] ?? ''); ?>" required>
                </div>
                <div>
                    <label for="position" class="block text-sm font-medium text-gray-700">Jabatan</label>
                    <input type="text" name="position" id="position" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" value="<?php echo htmlspecialchars($edit_person['position'] ?? ''); ?>" required>
                </div>
                 <div>
                    <label for="photo" class="block text-sm font-medium text-gray-700">Foto</label>
                    <input type="file" name="photo" id="photo" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    <?php if (!empty($edit_person['photo_url'])): ?>
                        <div class="mt-2">
                            <img src="<?php echo htmlspecialchars($edit_person['photo_url']); ?>" alt="Current Photo" class="h-20 w-20 rounded-full object-cover">
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="flex justify-end mt-6 pt-4 border-t">
                 <button type="button" class="close-modal bg-gray-200 text-gray-800 px-5 py-2 rounded-lg hover:bg-gray-300 mr-3">Batal</button>
                <button type="submit" class="bg-blue-600 text-white px-5 py-2 rounded-lg hover:bg-blue-500 active:bg-blue-700"><?php echo $edit_person ? 'Perbarui' : 'Simpan'; ?></button>
            </div>
        </form>
    </div>
</div>


<!-- Persons List -->
<div class="bg-white rounded-xl shadow-md overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">Foto</th>
                    <th scope="col" class="px-6 py-3">Nama Lengkap</th>
                    <th scope="col" class="px-6 py-3 hidden sm:table-cell">Jabatan</th>
                    <th scope="col" class="px-6 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $sql = "SELECT * FROM persons ORDER BY full_name";
            $result = $conn->query($sql);
            if ($result->num_rows > 0):
                while($row = $result->fetch_assoc()): ?>
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <img src="<?php echo !empty($row['photo_url']) ? htmlspecialchars($row['photo_url']) : 'https://via.placeholder.com/50'; ?>" alt="Foto" class="h-10 w-10 rounded-full object-cover">
                        </td>
                        <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                            <?php echo htmlspecialchars($row['full_name']); ?>
                        </th>
                        <td class="px-6 py-4 hidden sm:table-cell">
                            <?php echo htmlspecialchars($row['position']); ?>
                        </td>
                        <td class="px-6 py-4 flex items-center space-x-4">
                            <a href="persons.php?action=edit&id=<?php echo $row['id']; ?>" class="text-blue-600 hover:text-blue-800" title="Edit"><i class="fas fa-edit"></i></a>
                            <a href="persons.php?action=delete&id=<?php echo $row['id']; ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('Yakin ingin menghapus data ini?');" title="Hapus"><i class="fas fa-trash-alt"></i></a>
                        </td>
                    </tr>
                <?php endwhile;
            else: ?>
                <tr>
                    <td colspan="4" class="text-center py-4">Tidak ada data.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// Jika mode edit aktif, buka modal secara otomatis
if ($edit_person) {
    echo "<script>document.addEventListener('DOMContentLoaded', () => { document.getElementById('open-add-person-modal').click(); });</script>";
}
require_once 'includes/footer.php';
?>