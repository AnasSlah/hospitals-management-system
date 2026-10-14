<?php
// =======================================================
// 1. الاتصال بقاعدة البيانات
// =======================================================
$host = "localhost";
$user = "root";
$pass = "";
$db   = "hospitals_db";

try {
    $conn = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch(PDOException $e) {
    die("فشل الاتصال: " . $e->getMessage());
}

// =======================================================
// 2. متغيرات النموذج والرسائل
// =======================================================
$id = 0; $name = ""; $specialty = ""; $stars = 5;
$hospital = ""; $work_time = ""; $price = ""; $image = "";
$update = false;
$actionMessage = ""; // الرسالة التي ستظهر وتختفي

// =======================================================
// 3. إضافة أو تحديث الأطباء
// =======================================================
if (isset($_POST['save'])) {
    $id        = (int)$_POST['id'];
    $name      = $_POST['name'];
    $specialty = $_POST['specialty'];
    $stars     = (int)$_POST['stars'];
    $hospital  = $_POST['hospital'];
    $work_time = $_POST['work_time'];
    $price     = $_POST['price'];
    $image_name = $_POST['old_image'] ?? "";

    if (isset($_FILES['image']) && $_FILES['image']['name'] != "") {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image_name = uniqid() . "." . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], "../uploads/" . $image_name);
    }

    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE doctors SET name=?, specialty=?, stars=?, hospital=?, work_time=?, price=?, image=? WHERE id=?");
        $stmt->execute([$name, $specialty, $stars, $hospital, $work_time, $price, $image_name, $id]);
        $actionMessage = "✅ تم تحديث بيانات الدكتور بنجاح.";
    } else {
        $stmt = $conn->prepare("INSERT INTO doctors (name, specialty, stars, hospital, work_time, price, image) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$name, $specialty, $stars, $hospital, $work_time, $price, $image_name]);
        $actionMessage = "✅ تم إضافة الدكتور الجديد بنجاح.";
    }
}

// =======================================================
// 4. حذف الأطباء
// =======================================================
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM doctors WHERE id=?");
    $stmt->execute([$id]);
    $actionMessage = "🗑️ تم حذف الدكتور من النظام.";
}

// =======================================================
// 5. تعديل دكتور
// =======================================================
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $update = true;
    $stmt = $conn->prepare("SELECT * FROM doctors WHERE id=?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $name = $row['name']; $specialty = $row['specialty'];
        $stars = $row['stars']; $hospital = $row['hospital'];
        $work_time = $row['work_time']; $price = $row['price']; $image = $row['image'];
    }
}

// =======================================================
// 6. معالجة الحجز (قبول / رفض)
// =======================================================
if (isset($_GET['booking_action'], $_GET['booking_id'])) {
    $b_id = (int)$_GET['booking_id'];
    if ($_GET['booking_action'] === "accept") {
        $conn->prepare("UPDATE doctor_bookings SET status='accepted' WHERE id=?")->execute([$b_id]);
        $actionMessage = "✅ تم قبول الحجز بنجاح.";
    } elseif ($_GET['booking_action'] === "reject") {
        $conn->prepare("DELETE FROM doctor_bookings WHERE id=?")->execute([$b_id]);
        $actionMessage = "❌ تم رفض الحجز وحذفه.";
    }
}

// جلب البيانات
$search = $_GET['q'] ?? "";
if ($search !== "") {
    $stmt = $conn->prepare("SELECT * FROM doctors WHERE name LIKE ? ORDER BY id DESC");
    $stmt->execute(["%$search%"]);
} else {
    $stmt = $conn->query("SELECT * FROM doctors ORDER BY id DESC");
}
$doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->query("SELECT * FROM doctor_bookings ORDER BY id DESC");
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>لوحة إدارة الأطباء والحجوزات</title>
<style>
body{font-family:Tajawal,sans-serif;background:#f4f7f6;padding:20px}
.container{max-width:1200px;margin:auto}
.card{background:#fff;padding:25px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,.08);margin-bottom:25px;border-top:5px solid #007bff}
h2{margin-bottom:20px;color:#2c3e50}
.form-row{display:flex;gap:15px;flex-wrap:wrap}
.form-group{flex:1;min-width:200px; margin-bottom: 15px;}
label{display:block;margin-bottom:5px;font-weight:bold}
input,textarea,select{width:100%;padding:10px;border:1px solid #ccc;border-radius:6px;font-size:15px;box-sizing:border-box}
button{background:#007bff;color:#fff;border:none;padding:10px 25px;border-radius:6px;cursor:pointer}
table{width:100%;border-collapse:collapse;margin-top:20px}
th{background:#2c3e50;color:#fff;padding:12px}
td{padding:12px;border-bottom:1px solid #eee;text-align:center}
img.doctor-img{width:60px;height:60px;border-radius:50%;object-fit:cover}
.action{padding:6px 10px;color:#fff;border-radius:4px;text-decoration:none;font-size:13px; margin: 0 2px;}
.edit{background:#f39c12}
.delete{background:#e74c3c}
.accept{background:#28a745}
.reject{background:#dc3545}
.search{display:flex;gap:10px;margin-bottom:10px}
.search input{flex:1;padding:8px;border-radius:6px;border:1px solid #ccc}
.status-accepted{color:green;font-weight:bold}
.status-pending{color:orange;font-weight:bold}
/* استايل الرسالة التي ستختفي */
.timed-message { background:#d4edda; color:#155724; padding:15px; border-radius:8px; margin-bottom:20px; border-right: 5px solid #28a745; transition: opacity 0.5s ease; }
</style>
</head>

<body>
<div class="container">

<?php if($actionMessage): ?>
    <div id="msgBox" class="timed-message">
        <?= $actionMessage ?>
    </div>
    <script>
        setTimeout(function(){
            const msg = document.getElementById('msgBox');
            if(msg) {
                msg.style.opacity = '0';
                setTimeout(() => msg.style.display = 'none', 500);
            }
        }, 10000); // 10000 ملي ثانية = 10 ثواني
    </script>
<?php endif; ?>

<div class="card">
<h2><?= $update ? "✏️ تعديل دكتور" : "➕ إضافة دكتور" ?></h2>
<form method="POST" enctype="multipart/form-data">
<input type="hidden" name="id" value="<?= $id ?>">
<input type="hidden" name="old_image" value="<?= $image ?>">

<div class="form-row">
    <div class="form-group">
        <label>اسم الدكتور</label>
        <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" required>
    </div>
    <div class="form-group">
        <label>التخصص</label>
        <select name="specialty" required>
            <option value="Pediatrics" <?= $specialty=="Pediatrics"?"selected":"" ?>>Pediatrics</option>
            <option value="Dermatology" <?= $specialty=="Dermatology"?"selected":"" ?>>Dermatology</option>
            <option value="Orthopedics" <?= $specialty=="Orthopedics"?"selected":"" ?>>Orthopedics</option>
            <option value="Dentistry" <?= $specialty=="Dentistry"?"selected":"" ?>>Dentistry</option>
            <option value="Internal Medicine" <?= $specialty=="Internal Medicine"?"selected":"" ?>>Internal Medicine</option>
        </select>
    </div>
    <div class="form-group">
        <label>النجوم (1-5)</label>
        <input type="number" name="stars" min="1" max="5" value="<?= $stars ?>">
    </div>
</div>

<div class="form-row">
    <div class="form-group">
        <label>المستشفى</label>
        <input type="text" name="hospital" value="<?= htmlspecialchars($hospital) ?>" required>
    </div>
    <div class="form-group">
        <label>أوقات العمل</label>
        <input type="text" name="work_time" value="<?= htmlspecialchars($work_time) ?>" required>
    </div>
    <div class="form-group">
        <label>السعر</label>
        <input type="number" name="price" value="<?= htmlspecialchars($price) ?>" required>
    </div>
</div>

<div class="form-group">
    <label>صورة الدكتور</label>
    <input type="file" name="image" accept="image/*">
    <?php if($image): ?>
        <img src="../uploads/<?= $image ?>" class="doctor-img" style="margin-top:10px">
    <?php endif; ?>
</div>

<button type="submit" name="save"><?= $update ? "تحديث" : "حفظ" ?></button>
<?php if($update): ?> <a href="doctors.php" class="action edit">إلغاء</a> <?php endif; ?>
</form>
</div>

<div class="card">
<div style="display:flex;justify-content:space-between;align-items:center">
<h2>📋 قائمة الأطباء</h2>
<form class="search" method="GET">
<input type="text" name="q" placeholder="بحث باسم الدكتور..." value="<?= htmlspecialchars($search) ?>">
<button>بحث</button>
</form>
</div>

<table>
<tr>
    <th>#</th><th>الصورة</th><th>الاسم</th><th>التخصص</th><th>المستشفى</th><th>إجراءات</th>
</tr>
<?php foreach($doctors as $d): ?>
<tr>
    <td><?= $d['id'] ?></td>
    <td><img src="../uploads/<?= $d['image'] ?>" class="doctor-img" onerror="this.src='https://via.placeholder.com/60'"></td>
    <td><?= htmlspecialchars($d['name']) ?></td>
    <td><?= htmlspecialchars($d['specialty']) ?></td>
    <td><?= htmlspecialchars($d['hospital']) ?></td>
    <td>
        <a href="?edit=<?= $d['id'] ?>" class="action edit">تعديل</a>
        <a href="?delete=<?= $d['id'] ?>" class="action delete" onclick="return confirm('حذف؟')">حذف</a>
    </td>
</tr>
<?php endforeach; ?>
</table>
</div>

<div class="card">
<h2>📑 إدارة الحجوزات</h2>
<table>
<tr>
    <th>المريض</th><th>الهاتف</th><th>تاريخ الموعد</th><th>الدكتور</th><th>الحالة</th><th>إجراءات</th>
</tr>
<?php foreach($bookings as $b): ?>
<tr>
    <td><?= htmlspecialchars($b['patient_name']) ?></td>
    <td><?= htmlspecialchars($b['phone_number']) ?></td>
    <td><?= htmlspecialchars($b['appointment_date']) ?></td>
    <td><?= htmlspecialchars($b['doctor_name']) ?></td>
    <td class="<?= ($b['status']??'pending')=='accepted'?'status-accepted':'status-pending' ?>">
        <?= ($b['status']??'pending')=='accepted'?'مقبول':'معلق' ?>
    </td>
    <td>
        <?php if(($b['status']??'pending') != 'accepted'): ?>
            <a href="?booking_action=accept&booking_id=<?= $b['id'] ?>" class="action accept">قبول</a>
            <a href="?booking_action=reject&booking_id=<?= $b['id'] ?>" class="action reject">رفض</a>
        <?php else: ?> - <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</table>
</div>

</div>
</body>
</html>
