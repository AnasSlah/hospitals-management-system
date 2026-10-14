<?php
session_start();

/* 1. التحقق من تسجيل دخول الأدمن */
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php'); // وجهه لصفحة تسجيل الدخول إذا لم يكن مسجلاً
    exit;
}

$adminId = $_SESSION['admin_id'];

/* 2. إعدادات قاعدة البيانات */
$host = 'localhost';
$db   = 'hospitals_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    /* 3. معالجة تحديث البيانات (النص + الصورة) */
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_changes'])) {

        // أ- تحديث البيانات النصية في جدول admins
        $stmt = $pdo->prepare("
            UPDATE admins 
            SET full_name=?, birth_date=?, phone=?, email=?, address=?
            WHERE id=?
        ");
        $stmt->execute([
            $_POST['full_name'],
            $_POST['birth_date'],
            $_POST['phone'],
            $_POST['email'],
            $_POST['address'],
            $adminId
        ]);

        // ب- معالجة رفع الصورة
        if (!empty($_FILES['profile_image']['name'])) {
            
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $fileInfo = pathinfo($_FILES['profile_image']['name']);
            $ext = strtolower($fileInfo['extension']);

            if (in_array($ext, $allowed)) {
                $uploadDir = __DIR__ . '/../uploads/';
                
                // جلب اسم الصورة القديمة لحذفها
                $stmt = $pdo->prepare("SELECT profile_image FROM admins WHERE id=?");
                $stmt->execute([$adminId]);
                $oldImage = $stmt->fetchColumn();

                $newName = "admin_{$adminId}_" . time() . "." . $ext;
                $uploadPath = $uploadDir . $newName;

                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadPath)) {
                    // تحديث قاعدة البيانات بالاسم الجديد
                    $stmt = $pdo->prepare("UPDATE admins SET profile_image=? WHERE id=?");
                    $stmt->execute([$newName, $adminId]);

                    // حذف الصورة القديمة
                    if (!empty($oldImage)) {
                        $oldPath = $uploadDir . $oldImage;
                        if (file_exists($oldPath)) { unlink($oldPath); }
                    }
                }
            }
        }

        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    /* 4. جلب بيانات الأدمن */
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE id=?");
    $stmt->execute([$adminId]);
    $adminData = $stmt->fetch();

    if (!$adminData) die("Admin not found");

    /* 5. تحضير رابط الصورة */
    $uploadDir = __DIR__ . '/../uploads/';
    $picName = $adminData['profile_image'];
    
    if (!empty($picName) && file_exists($uploadDir . $picName)) {
        $profile_picture_url = '../uploads/' . $picName . '?v=' . filemtime($uploadDir . $picName);
        $has_picture = true;
    } else {
        $profile_picture_url = '../uploads/default_admin.png'; 
        $has_picture = false;
    }

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Profile - <?= htmlspecialchars($adminData['full_name']) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        /* تم الإبقاء على نفس التصميم (CSS) الخاص بك مع تعديلات طفيفة لتناسب الأدمن */
        * { box-sizing: border-box; }
        body { margin: 0; padding: 30px; font-family: 'Segoe UI', sans-serif; background: #f4f7f6; color: #333; }
        h2 { color: #2c3e50; margin-bottom: 25px; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
        .profile-container { max-width: 1200px; margin: auto; }
        form { display: grid; grid-template-columns: 1fr 2fr; gap: 30px; }
        .profile-sidebar { background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,.08); text-align: center; height: fit-content; }
        .avatar { width: 130px; height: 130px; border-radius: 50%; background: #2c3e50; color: #fff; font-size: 3rem; line-height: 130px; margin: 0 auto 15px auto; overflow: hidden; font-weight: bold; }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .admin-name { font-size: 1.4rem; font-weight: 700; color: #2c3e50; margin-bottom: 5px; }
        .admin-status { font-size: 0.9rem; margin-bottom: 20px; color: #27ae60; font-weight: bold; }
        .btn { display: block; width: 100%; padding: 12px; border: none; border-radius: 6px; margin-top: 10px; cursor: pointer; font-weight: 600; transition: 0.3s; text-decoration: none; text-align: center; }
        .btn-edit { background: #3498db; color: #fff; }
        .btn-save { background: #27ae60; color: #fff; display: none; }
        .btn-cancel { background: #95a5a6; color: #fff; display: none; }
        .info-card { background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,.08); }
        .info-card h3 { margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 15px; color: #34495e; }
        .details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px; }
        .detail-item label { font-size: 0.85rem; color: #7f8c8d; font-weight: bold; display: block; margin-bottom: 5px; text-transform: uppercase; }
        .detail-item p { margin: 0; font-weight: 600; color: #2c3e50; font-size: 1.05rem; }
        .detail-item input { display: none; width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 1rem; }
        .image-upload-container { display: none; margin: 15px 0; background: #f9f9f9; padding: 10px; border-radius: 5px; border: 1px dashed #ccc; }
        form.editing .detail-item p { display: none; }
        form.editing .detail-item input { display: block; }
        form.editing .image-upload-container { display: block; }
        form.editing .btn-edit { display: none; }
        form.editing .btn-save, form.editing .btn-cancel { display: block; }
        @media(max-width:900px) { form { grid-template-columns: 1fr; } .details-grid { grid-template-columns: 1fr; } }
    </style>
    <script>
        function toggleEditMode(cancel = false) {
            const form = document.getElementById('profileForm');
            if (cancel) { form.classList.remove('editing'); form.reset(); } 
            else { form.classList.add('editing'); }
        }
    </script>
</head>
<body>

    <h2>Admin Control Profile</h2>

    <div class="profile-container">
        <form method="POST" id="profileForm" enctype="multipart/form-data">
            
            <div class="profile-sidebar">
                <div class="avatar">
                    <?php if ($has_picture): ?>
                        <img src="<?= htmlspecialchars($profile_picture_url) ?>" alt="Admin Profile">
                    <?php else: ?>
                        <?= strtoupper(mb_substr($adminData['full_name'], 0, 1, 'UTF-8')) ?>
                    <?php endif; ?>
                </div>

                <div class="admin-name"><?= htmlspecialchars($adminData['full_name']) ?></div>
                <div class="admin-status">● <?= ucfirst($adminData['status']) ?> Admin</div>

                <div class="image-upload-container">
                    <label>Update Profile Photo:</label>
                    <input type="file" name="profile_image" accept="image/*">
                </div>

                <button type="button" class="btn btn-edit" onclick="toggleEditMode()">Edit My Info</button>
                <button type="submit" name="save_changes" class="btn btn-save">Save Profile</button>
                <button type="button" class="btn btn-cancel" onclick="toggleEditMode(true)">Cancel</button>
            </div>

            <div class="profile-main">
                <div class="info-card">
                    <h3>Administrative Information</h3>

                    <div class="details-grid">
                        <div class="detail-item">
                            <label>Full Name</label>
                            <p><?= htmlspecialchars($adminData['full_name']) ?></p>
                            <input type="text" name="full_name" value="<?= htmlspecialchars($adminData['full_name']) ?>">
                        </div>

                        <div class="detail-item">
                            <label>Username</label>
                            <p>@<?= htmlspecialchars($adminData['username']) ?></p>
                            <i style="font-size: 0.8rem; color: #999;">(Username cannot be changed)</i>
                        </div>

                        <div class="detail-item">
                            <label>Date of Birth</label>
                            <p><?= $adminData['birth_date'] ?></p>
                            <input type="date" name="birth_date" value="<?= $adminData['birth_date'] ?>">
                        </div>

                        <div class="detail-item">
                            <label>Phone Number</label>
                            <p><?= htmlspecialchars($adminData['phone']) ?></p>
                            <input type="text" name="phone" value="<?= htmlspecialchars($adminData['phone']) ?>">
                        </div>

                        <div class="detail-item">
                            <label>Email Address</label>
                            <p><?= htmlspecialchars($adminData['email']) ?></p>
                            <input type="email" name="email" value="<?= htmlspecialchars($adminData['email']) ?>">
                        </div>

                        <div class="detail-item">
                            <label>Work Address</label>
                            <p><?= htmlspecialchars($adminData['address']) ?></p>
                            <input type="text" name="address" value="<?= htmlspecialchars($adminData['address']) ?>">
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

</body>
</html>
