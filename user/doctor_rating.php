<?php
session_start();

// ================== 1. اتصال قاعدة البيانات (PDO) ==================
try {
    $conn = new PDO(
        "mysql:host=localhost;dbname=hospitals_db;charset=utf8mb4",
        "root",
        "",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}

// ================== 2. تعريف دالة تسجيل النشاط logActivity ==================
function logActivity($userId, $action, $description, $pdo) {
    try {
        $query = "INSERT INTO activity_log (user_id, action, description) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$userId, $action, $description]);
    } catch (Exception $e) {
        error_log("Activity Log Error: " . $e->getMessage());
    }
}

// ================== VARIABLES ==================
$doctor   = null;
$success  = "";
$error    = "";
$current_userId = $_SESSION['user_id'] ?? 0;

// ================== جلب اسم المستخدم من الجلسة ==================
$commenter_name = "زائر"; 
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $stmtUser = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
    $stmtUser->execute([$userId]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
    if ($user && !empty($user['full_name'])) {
        $commenter_name = $user['full_name'];
    }
}

// ================== 3. معالجة البحث عن طبيب (SEARCH) ==================
if (isset($_GET['search']) && $_GET['search'] !== "") {
    $search = trim($_GET['search']);
    $stmt = $conn->prepare("SELECT name, image FROM doctors WHERE name LIKE ? LIMIT 1");
    $stmt->execute(["%$search%"]);
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($doctor) {
        logActivity($current_userId, 'Search Doctor', "قام بالبحث عن الطبيب: " . $doctor['name'], $conn);
    } else {
        $error = "❌ لم يتم العثور على الطبيب";
    }
}

// ================== 4. معالجة حفظ التقييم (SAVE) ==================
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $doctor_name = $_POST['doctor_name'] ?? '';
    $star_rating = (int)($_POST['star_rating'] ?? 0);
    $experience  = $_POST['experience'] ?? '';
    $comments    = trim($_POST['comments'] ?? '');

    if ($doctor_name && $star_rating >= 1 && $star_rating <= 5 && in_array($experience, ['Good','Average','Poor'])) {
        $stmt = $conn->prepare("INSERT INTO doctor_ratings (doctor_name, star_rating, experience, comments, commenter_name) VALUES (?,?,?,?,?)");
        $stmt->execute([$doctor_name, $star_rating, $experience, $comments, $commenter_name]);
        logActivity($current_userId, 'Submit Rating', "قيم الطبيب ($doctor_name) بـ ($star_rating) نجوم", $conn);
        $success = "✅ تم إرسال التقييم بنجاح";
    } else {
        $error = "❌ الرجاء تعبئة جميع الحقول المطلوبة";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>تقييم الطبيب</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    :root { --primary: #007bff; --warning: #ffc107; --bg: #f0f2f5; --card: #ffffff; }
    body { font-family: 'Tajawal', sans-serif; background: var(--bg); margin: 0; padding: 40px 20px; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
    .container { width: 100%; max-width: 550px; background: var(--card); padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
    h2 { text-align: center; margin-bottom: 30px; color: #2c3e50; font-weight: 700; position: relative; }
    h2::after { content: ''; display: block; width: 50px; height: 3px; background: var(--primary); margin: 10px auto 0; border-radius: 2px; }
    
    .search-box { position: relative; margin-bottom: 30px; }
    .search-box input { width: 100%; padding: 15px 50px 15px 15px; border-radius: 50px; border: 2px solid #e1e1e1; background: #fafafa; font-family: inherit; outline: none; box-sizing: border-box; }
    .search-box::before { content: '\f002'; font-family: 'Font Awesome 6 Free'; font-weight: 900; position: absolute; right: 20px; top: 50%; transform: translateY(-50%); color: #aaa; }

    .doctor { text-align: center; margin: 30px 0; }
    
    /* تنسيق الصورة والحرف الاحتياطي */
    .doctor-avatar { width: 130px; height: 130px; border-radius: 50%; border: 4px solid #fff; box-shadow: 0 5px 15px rgba(0,0,0,0.15); margin: 0 auto 15px; display: flex; align-items: center; justify-content: center; overflow: hidden; background: #e9ecef; }
    .doctor-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .doctor-letter { font-size: 60px; color: #fff; font-weight: bold; background: linear-gradient(135deg, var(--primary), #0056b3); width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; }

    .stars { display: flex; flex-direction: row-reverse; justify-content: center; gap: 5px; margin-bottom: 25px; background: #fafafa; padding: 10px; border-radius: 50px; width: fit-content; margin: 0 auto 25px; }
    .stars input { display: none; }
    .stars label { font-size: 35px; color: #ddd; cursor: pointer; transition: 0.2s; }
    .stars label:hover, .stars label:hover ~ label, .stars input:checked ~ label { color: var(--warning); }

    .options { display: flex; justify-content: center; gap: 15px; margin-bottom: 25px; }
    .options label { flex: 1; text-align: center; padding: 12px; border: 2px solid #eee; border-radius: 12px; cursor: pointer; background: #fff; transition: 0.3s; }
    .options input { display: none; }
    .options label:has(input:checked) { border-color: var(--primary); background: rgba(0, 123, 255, 0.05); color: var(--primary); font-weight: bold; }

    textarea { width: 100%; min-height: 100px; padding: 15px; border-radius: 12px; border: 2px solid #e1e1e1; font-family: inherit; box-sizing: border-box; margin-bottom: 25px; background: #fafafa; }
    button { width: 100%; padding: 15px; background: linear-gradient(135deg, var(--primary), #0056b3); color: #fff; border: none; border-radius: 50px; font-size: 16px; font-weight: bold; cursor: pointer; box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3); font-family: inherit; }

    .status-msg { padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align: center; font-weight: 500; transition: opacity 0.5s ease; }
    .success { background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
    .error { background: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }
</style>
</head>
<body>

<div class="container">
    <h2><i class="fa-solid fa-stethoscope"></i> تقييم الطبيب</h2>

    <?php if($success): ?> <div class="status-msg success auto-hide"><?= $success ?></div> <?php endif; ?>
    <?php if($error): ?> <div class="status-msg error auto-hide"><?= $error ?></div> <?php endif; ?>

    <form method="GET" class="search-box">
        <input type="text" name="search" placeholder="ابحث عن اسم الطبيب..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" required>
    </form>

    <?php if($doctor): ?>
    <div class="doctor">
        <div class="doctor-avatar">
            <?php 
            $image_path = "../uploads/" . $doctor['image'];
            // التحقق من وجود الصورة فعلياً في المجلد
            if (!empty($doctor['image']) && file_exists($image_path)): ?>
                <img src="<?= htmlspecialchars($image_path) ?>" alt="Doctor">
            <?php else: ?>
                <div class="doctor-letter"><?= mb_substr($doctor['name'], 0, 1, 'utf-8') ?></div>
            <?php endif; ?>
        </div>
        <h3>د. <?= htmlspecialchars($doctor['name']) ?></h3>
    </div>

    <form method="POST">
        <input type="hidden" name="doctor_name" value="<?= htmlspecialchars($doctor['name']) ?>">
        <div class="stars">
            <input type="radio" name="star_rating" id="s5" value="5" required><label for="s5">★</label>
            <input type="radio" name="star_rating" id="s4" value="4"><label for="s4">★</label>
            <input type="radio" name="star_rating" id="s3" value="3"><label for="s3">★</label>
            <input type="radio" name="star_rating" id="s2" value="2"><label for="s2">★</label>
            <input type="radio" name="star_rating" id="s1" value="1"><label for="s1">★</label>
        </div>
        <div class="options">
            <label><input type="radio" name="experience" value="Good" required> جيد</label>
            <label><input type="radio" name="experience" value="Average"> متوسط</label>
            <label><input type="radio" name="experience" value="Poor"> ضعيف</label>
        </div>
        <textarea name="comments" placeholder="اكتب ملاحظاتك هنا..."></textarea>
        <button type="submit">إرسال التقييم <i class="fa-solid fa-paper-plane"></i></button>
    </form>
    <?php endif; ?>
</div>

<script>
// إخفاء الرسائل تلقائياً بعد 10 ثواني
setTimeout(() => {
    document.querySelectorAll('.auto-hide').forEach(msg => {
        msg.style.opacity = '0';
        setTimeout(() => msg.remove(), 500);
    });
}, 10000);
</script>

</body>
</html>
