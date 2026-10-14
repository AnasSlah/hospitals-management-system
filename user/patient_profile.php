<?php
session_start();

/* 1. Check Login */
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$userId = $_SESSION['user_id'];

/* 2. Database Configuration */
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

// ================== دالة تسجيل النشاط logActivity ==================
function logActivity($userId, $action, $description, $pdo) {
    try {
        $query = "INSERT INTO activity_log (user_id, action, description) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$userId, $action, $description]);
    } catch (Exception $e) {
        error_log("Activity Log Error: " . $e->getMessage());
    }
}

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    /* 3. Handle Single Form Submission (Text + Image) */
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_changes'])) {

        // --- Step A: Update Text Data ---
        $stmt = $pdo->prepare("
            UPDATE users 
            SET date_of_birth=?, phone_number=?, email=?, address=?, blood_type=?
            WHERE id=?
        ");
        $stmt->execute([
            $_POST['date_of_birth'],
            $_POST['phone_number'],
            $_POST['email'],
            $_POST['address'],
            $_POST['blood_type'],
            $userId
        ]);

        // سجل نشاط: تحديث البيانات النصية
        logActivity($userId, 'Update Profile', 'قام المستخدم بتحديث بياناته الشخصية (العنوان، الهاتف، إلخ)', $pdo);

        // --- Step B: Handle Image Upload ---
        if (!empty($_FILES['profile_picture']['name'])) {
            
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $fileInfo = pathinfo($_FILES['profile_picture']['name']);
            $ext = strtolower($fileInfo['extension']);

            if (in_array($ext, $allowed)) {
                $uploadDir = __DIR__ . '/../uploads/';
                
                $stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE id=?");
                $stmt->execute([$userId]);
                $oldImage = $stmt->fetchColumn();

                $newName = "user_{$userId}_" . time() . "." . $ext;
                $uploadPath = $uploadDir . $newName;

                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadPath)) {
                    
                    $stmt = $pdo->prepare("UPDATE users SET profile_picture=? WHERE id=?");
                    $stmt->execute([$newName, $userId]);

                    // سجل نشاط: تغيير الصورة الشخصية
                    logActivity($userId, 'Change Avatar', 'قام المستخدم بتغيير صورتة الشخصية', $pdo);

                    if (!empty($oldImage) && $oldImage !== 'default.png') {
                        $oldPath = $uploadDir . $oldImage;
                        if (file_exists($oldPath)) {
                            unlink($oldPath);
                        }
                    }
                    $_SESSION['profile_picture'] = $newName;
                }
            }
        }

        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    /* 4. Fetch User Data */
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
    $stmt->execute([$userId]);
    $userData = $stmt->fetch();

    if (!$userData) die("User not found");

    /* 5. Prepare Image URL */
    $uploadDir = __DIR__ . '/../uploads/';
    $picName = $userData['profile_picture'];
    
    if (!empty($picName) && file_exists($uploadDir . $picName)) {
        $profile_picture_url = '../uploads/' . $picName . '?v=' . filemtime($uploadDir . $picName);
        $has_profile_picture = true;
    } else {
        $profile_picture_url = '../uploads/default.png'; 
        $has_profile_picture = false;
    }

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Profile</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        /* [نفس كود التنسيق CSS الخاص بك دون تغيير] */
        * { box-sizing: border-box; }
        body { margin: 0; padding: 30px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f7f6; color: #333; }
        h2 { color: #2c3e50; margin-bottom: 25px; border-bottom: 2px solid #ddd; padding-bottom: 10px; }
        .profile-container { max-width: 1200px; margin: auto; }
        form { display: grid; grid-template-columns: 1fr 2fr; gap: 30px; }
        .profile-sidebar { background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0, 0, 0, .08); text-align: center; height: fit-content; }
        .avatar { width: 130px; height: 130px; border-radius: 50%; background: #3498db; color: #fff; font-size: 3rem; line-height: 130px; margin: 0 auto 15px auto; overflow: hidden; font-weight: bold; }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .patient-name { font-size: 1.4rem; font-weight: 700; color: #2c3e50; margin-bottom: 5px; }
        .patient-id { color: #777; font-size: 0.9rem; margin-bottom: 20px; }
        .btn { display: block; width: 100%; padding: 12px; border: none; border-radius: 6px; margin-top: 10px; cursor: pointer; font-weight: 600; font-size: 1rem; transition: 0.3s; text-decoration: none; text-align: center; }
        .btn:hover { opacity: 0.9; }
        .btn-history { background: #1abc9c; color: #fff; }
        .btn-edit { background: #3498db; color: #fff; }
        .btn-save { background: #27ae60; color: #fff; display: none; }
        .btn-cancel { background: #95a5a6; color: #fff; display: none; }
        .info-card { background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0, 0, 0, .08); }
        .info-card h3 { margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 15px; color: #34495e; }
        .details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px; }
        .detail-item label { font-size: 0.85rem; color: #7f8c8d; font-weight: bold; display: block; margin-bottom: 5px; text-transform: uppercase; }
        .detail-item p { margin: 0; font-weight: 600; color: #2c3e50; font-size: 1.05rem; }
        .detail-item input { display: none; width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 1rem; }
        .image-upload-container { display: none; margin: 15px 0; text-align: left; background: #f9f9f9; padding: 10px; border-radius: 5px; border: 1px dashed #ccc; }
        .image-upload-container label { display: block; margin-bottom: 5px; font-size: 0.85rem; color: #555; }
        form.editing .detail-item p { display: none; }
        form.editing .detail-item input { display: block; }
        form.editing .image-upload-container { display: block; }
        form.editing .btn-edit, form.editing .btn-history { display: none; }
        form.editing .btn-save, form.editing .btn-cancel { display: block; }
        @media(max-width:900px) { form { grid-template-columns: 1fr; } .details-grid { grid-template-columns: 1fr; } }
    </style>

    <script>
        function toggleEditMode(cancel = false) {
            const form = document.getElementById('profileForm');
            if (cancel) {
                form.classList.remove('editing');
                form.reset();
            } else {
                form.classList.add('editing');
            }
        }
    </script>
</head>
<body>

    <h2>Patient Profile</h2>

    <div class="profile-container">
        <form method="POST" id="profileForm" enctype="multipart/form-data">
            
            <div class="profile-sidebar">
                <div class="avatar">
                    <?php
                    if ($has_profile_picture) {
                        echo '<img src="' . htmlspecialchars($profile_picture_url) . '" alt="Profile Picture">';
                    } else {
                        $initials = mb_substr($userData['full_name'], 0, 1, 'UTF-8');
                        echo strtoupper($initials);
                    }
                    ?>
                </div>

                <div class="patient-name"><?= htmlspecialchars($userData['full_name']) ?></div>
                <div class="patient-id">ID: <?= $userId ?></div>

                <div class="image-upload-container">
                    <label>Update Profile Picture:</label>
                    <input type="file" name="profile_picture" accept="image/*">
                </div>

                <a href="history.php" class="btn btn-history">Medical History</a>
                <button type="button" class="btn btn-edit" onclick="toggleEditMode()">Edit Profile</button>
                
                <button type="submit" name="save_changes" class="btn btn-save">Save Changes</button>
                <button type="button" class="btn btn-cancel" onclick="toggleEditMode(true)">Cancel</button>
            </div>

            <div class="profile-main">
                <div class="info-card">
                    <h3>Personal Information</h3>

                    <div class="details-grid">
                        <div class="detail-item">
                            <label>Date of Birth</label>
                            <p><?= $userData['date_of_birth'] ?: 'N/A' ?></p>
                            <input type="date" name="date_of_birth" value="<?= $userData['date_of_birth'] ?>">
                        </div>

                        <div class="detail-item">
                            <label>Phone Number</label>
                            <p><?= $userData['phone_number'] ?: 'N/A' ?></p>
                            <input type="text" name="phone_number" value="<?= $userData['phone_number'] ?>">
                        </div>

                        <div class="detail-item">
                            <label>Email Address</label>
                            <p><?= $userData['email'] ?: 'N/A' ?></p>
                            <input type="email" name="email" value="<?= $userData['email'] ?>">
                        </div>

                        <div class="detail-item">
                            <label>Address</label>
                            <p><?= $userData['address'] ?: 'N/A' ?></p>
                            <input type="text" name="address" value="<?= $userData['address'] ?>">
                        </div>

                        <div class="detail-item">
                            <label>Blood Type</label>
                            <p><?= $userData['blood_type'] ?: 'N/A' ?></p>
                            <input type="text" name="blood_type" value="<?= $userData['blood_type'] ?>">
                        </div>
                    </div>
                </div>
            </div>

        </form>
    </div>

</body>
</html>
