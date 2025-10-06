<?php
// Direktori: /qr-signature/
// Nama File: persons.php
// Path Lengkap: /qr-signature/persons.php

require_once 'includes/auth.php';
require_once 'includes/db.php';

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $position = $conn->real_escape_string($_POST['position']);
    $person_id = $_POST['id'] ?? null;
    $photo_url = $_POST['existing_photo'] ?? '';

    // Photo upload handling
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $target_dir = "assets/uploads/photos/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        $file_name = time() . '_' . basename($_FILES["photo"]["name"]);
        $target_file = $target_dir . $file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Validation
        $check = getimagesize($_FILES["photo"]["tmp_name"]);
        if ($check === false) {
            $error = "File bukan gambar.";
        } elseif ($_FILES["photo"]["size"] > 2000000) { // 2MB
            $error = "Ukuran file terlalu besar.";
        } elseif (!in_array($imageFileType, ['jpg', 'png', 'jpeg', 'gif'])) {
            $error = "Hanya format JPG, JPEG, PNG & GIF yang diizinkan.";
        }

        if (empty($error)) {
            if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
                // Hapus foto lama jika ada
                if (!empty($photo_url) && file_exists($photo_url)) {
                    unlink($photo_url);
                }
                $photo_url = $target_file;
            } else {
                $error = "Gagal mengunggah foto.";
            }
        }
    }

    if (empty($error)) {
        if ($person_id) { // Update
            $stmt = $conn->prepare("UPDATE persons SET full_name = ?, position = ?, photo_url = ? WHERE id = ?");
            $stmt->bind_param("sssi", $full_name, $position, $photo_url, $person_id);
            if ($stmt->execute()) {
                $success = "Data person berhasil diperbarui.";
            } else {
                $error = "Gagal memperbarui data: " . $stmt->error;
            }
        } else { // Insert
            $stmt = $conn->prepare("INSERT INTO persons (full_name, position, photo_url) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $full_name, $position, $photo_url);
            if ($stmt->execute()) {
                $success = "Data person berhasil ditambahkan.";
            } else {
                $error = "Gagal menambahkan data: " . $stmt->error;
            }
        }
        $stmt->close();
        if ($success) {
            header("Location: persons.php?success=" . urlencode($success));
            exit();
        }
    }
}

// Handle delete
if ($action === 'delete' && $id) {
    // Get photo url to delete the file
    $stmt = $conn->prepare("SELECT photo_url FROM persons WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if (!empty($row['photo_url']) && file_exists($row['photo_url'])) {
            unlink($row['photo_url']);
        }
    }
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM persons WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header("Location: persons.php?success=" . urlencode("Data person berhasil dihapus."));
        exit();
    } else {
        $error = "Gagal menghapus data.";
    }
}

$success = $_GET['success'] ?? '';

require_once 'includes/header.php';

if ($action === 'add' || $action === 'edit') {
    $person = null;
    if ($action === 'edit' && $id) {
        $stmt = $conn->prepare("SELECT id, full_name, position, photo_url FROM persons WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $person = $result->fetch_assoc();
        $stmt->close();
    }
?>
    <h1 class="text-2xl font-bold mb-4"><?php echo $action === 'add' ? 'Tambah Person' : 'Edit Person'; ?></h1>
    <?php if ($error) : ?><div class="bg-red-100 text-red-700 p-3 rounded mb-4"><?php echo $error; ?></div><?php endif; ?>
    <div class="bg-white p-6 rounded-xl shadow-md">
        <form method="POST" action="persons.php" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="id" value="<?php echo $person['id'] ?? ''; ?>">
            <input type="hidden" name="existing_photo" value="<?php echo $person['photo_url'] ?? ''; ?>">
            <div>
                <label for="full_name" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                <input type="text" name="full_name" id="full_name" value="<?php echo htmlspecialchars($person['full_name'] ?? ''); ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label for="position" class="block text-sm font-medium text-gray-700">Jabatan</label>
                <input type="text" name="position" id="position" value="<?php echo htmlspecialchars($person['position'] ?? ''); ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label for="photo" class="block text-sm font-medium text-gray-700">Foto</label>
                <input type="file" name="photo" id="photo" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                <?php if (!empty($person['photo_url'])): ?>
                    <img src="<?php echo htmlspecialchars($person['photo_url']); ?>" alt="Current Photo" class="mt-2 h-20 w-20 object-cover rounded-md">
                <?php endif; ?>
            </div>
            <div class="flex items-center space-x-4">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-500 active:bg-blue-700">Simpan</button>
                <a href="persons.php" class="bg-gray-200 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-300">Batal</a>
            </div>
        </form>
    </div>
<?php
} else { // List view
?>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Manage Persons</h1>
        <a href="persons.php?action=add" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-500 active:bg-blue-700">Tambah Person</a>
    </div>

    <?php if ($success) : ?><div class="bg-blue-100 text-blue-700 p-3 rounded mb-4"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3">Foto</th>
                        <th scope="col" class="px-6 py-3">Nama</th>
                        <th scope="col" class="px-6 py-3">Jabatan</th>
                        <th scope="col" class="px-6 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $result = $conn->query("SELECT * FROM persons ORDER BY full_name ASC");
                    if ($result->num_rows > 0):
                        while($row = $result->fetch_assoc()): ?>
                            <tr class="bg-white border-b hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <img src="<?php echo htmlspecialchars($row['photo_url'] ?: 'https://placehold.co/40x40/E2E8F0/4A5568?text=N/A'); ?>" alt="Photo" class="h-10 w-10 rounded-full object-cover">
                                </td>
                                <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                                    <?php echo htmlspecialchars($row['full_name']); ?>
                                </th>
                                <td class="px-6 py-4">
                                    <?php echo htmlspecialchars($row['position']); ?>
                                </td>
                                <td class="px-6 py-4 flex items-center space-x-2">
                                    <a href="persons.php?action=edit&id=<?php echo $row['id']; ?>" class="font-medium text-blue-600 hover:underline">Edit</a>
                                    <a href="persons.php?action=delete&id=<?php echo $row['id']; ?>" class="font-medium text-red-600 hover:underline" onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?');">Hapus</a>
                                </td>
                            </tr>
                        <?php endwhile;
                    else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-4">Belum ada data.</td>
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