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
// 2. متغيرات النموذج
// =======================================================
$id = 0;
$name = "";
$area = "";
$lat = "";
$lng = "";
$description = "";
$update = false;

// =======================================================
// 3. إضافة أو تحديث
// =======================================================
if (isset($_POST['save'])) {
    $id   = (int)$_POST['id'];
    $name = $_POST['name'];
    $area = $_POST['area'];
    $lat  = $_POST['lat'];
    $lng  = $_POST['lng'];
    $desc = $_POST['description'];

    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE hospitals SET hospital_name=?, area=?, latitude=?, longitude=?, description=? WHERE id=?");
        $stmt->execute([$name, $area, $lat, $lng, $desc, $id]);
    } else {
        $stmt = $conn->prepare("INSERT INTO hospitals (hospital_name, area, latitude, longitude, description) VALUES (?,?,?,?,?)");
        $stmt->execute([$name, $area, $lat, $lng, $desc]);
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// =======================================================
// 4. حذف
// =======================================================
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM hospitals WHERE id=?");
    $stmt->execute([$id]);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// =======================================================
// 5. وضع التعديل
// =======================================================
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $update = true;
    $stmt = $conn->prepare("SELECT * FROM hospitals WHERE id=?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $name = $row['hospital_name'];
        $area = $row['area'];
        $lat  = $row['latitude'];
        $lng  = $row['longitude'];
        $description = $row['description'];
    }
}

// =======================================================
// 6. البحث
// =======================================================
$search = $_GET['q'] ?? "";
if ($search !== "") {
    $stmt = $conn->prepare("SELECT * FROM hospitals WHERE hospital_name LIKE ? ORDER BY id DESC");
    $stmt->execute(["%$search%"]);
} else {
    $stmt = $conn->query("SELECT * FROM hospitals ORDER BY id DESC");
}
$hospitals = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إدارة المستشفيات</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #007bff;
            --secondary: #2c3e50;
            --success: #27ae60;
            --danger: #e74c3c;
            --warning: #f39c12;
            --bg: #f8f9fa;
        }

        body {
            font-family: 'Tajawal', sans-serif;
            background: var(--bg);
            margin: 0;
            padding: 20px;
            color: #333;
        }

        .container { max-width: 1200px; margin: auto; }

        .card {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,.05);
            margin-bottom: 30px;
            border-top: 5px solid var(--primary);
        }

        h2 { margin-top: 0; color: var(--secondary); display: flex; align-items: center; gap: 10px; }

        .form-row { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); 
            gap: 20px; 
            margin-bottom: 20px; 
        }

        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; font-size: 14px; }

        input, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }

        input:focus { border-color: var(--primary); outline: none; }

        .btn-group { display: flex; gap: 10px; margin-top: 10px; }

        button {
            background: var(--primary);
            color: #fff;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: 0.3s;
        }

        button:hover { opacity: 0.9; transform: translateY(-1px); }

        /* تنسيق الجدول */
        .table-container { overflow-x: auto; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: #fff;
        }

        th {
            background: var(--secondary);
            color: #fff;
            padding: 15px;
            text-align: right;
            font-size: 15px;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }

        tr:hover { background: #fdfdfd; }

        .coord-box {
            background: #f1f3f5;
            padding: 5px 10px;
            border-radius: 6px;
            display: inline-block;
            font-family: monospace;
            font-size: 13px;
            color: #444;
            margin: 2px 0;
        }

        .action-btn {
            padding: 8px 15px;
            color: #fff;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            font-weight: bold;
            display: inline-block;
            transition: 0.3s;
        }

        .edit { background: var(--warning); }
        .delete { background: var(--danger); }
        .cancel { background: #95a5a6; }

        .search-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
        }

        .search-box { display: flex; gap: 8px; width: 100%; max-width: 400px; }

        @media (max-width: 768px) {
            body { padding: 10px; }
            .form-row { grid-template-columns: 1fr; }
            th, td { padding: 10px; font-size: 13px; }
            .search-box { max-width: 100%; }
        }
    </style>
</head>
<body>

<div class="container">

    <div class="card">
        <h2><?= $update ? "✏️ تعديل بيانات المستشفى" : "➕ إضافة مستشفى جديد" ?></h2>
        <form method="POST">
            <input type="hidden" name="id" value="<?= $id ?>">
            
            <div class="form-row">
                <div class="form-group">
                    <label>اسم المستشفى</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" placeholder="مثال: مستشفى الأمل" required>
                </div>
                <div class="form-group">
                    <label>المنطقة</label>
                    <input type="text" name="area" value="<?= htmlspecialchars($area) ?>" placeholder="مثال: وسط المدينة" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>خط العرض (Latitude)</label>
                    <input type="text" name="lat" value="<?= htmlspecialchars($lat) ?>" placeholder="15.XXXXXX" required>
                </div>
                <div class="form-group">
                    <label>خط الطول (Longitude)</label>
                    <input type="text" name="lng" value="<?= htmlspecialchars($lng) ?>" placeholder="32.XXXXXX" required>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label>وصف مختصر</label>
                <textarea name="description" rows="3" placeholder="أدخل وصفاً للموقع أو الخدمات..."><?= htmlspecialchars($description) ?></textarea>
            </div>

            <div class="btn-group">
                <button type="submit" name="save">
                    <?= $update ? "تحديث البيانات" : "حفظ المستشفى" ?>
                </button>
                <?php if($update): ?>
                    <a href="<?= $_SERVER['PHP_SELF'] ?>" class="action-btn cancel" style="padding: 12px 30px;">إلغاء التعديل</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="search-container">
            <h2>📋 قائمة المستشفيات المسجلة</h2>
            <form class="search-box" method="GET">
                <input type="text" name="q" placeholder="ابحث عن اسم مستشفى..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit">بحث</button>
            </form>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th width="50">#</th>
                        <th>الاسم</th>
                        <th>المنطقة</th>
                        <th>الإحداثيات الجغرافية</th>
                        <th>الوصف</th>
                        <th width="150" style="text-align: center;">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($hospitals): foreach($hospitals as $h): ?>
                    <tr>
                        <td><strong><?= $h['id'] ?></strong></td>
                        <td style="color: var(--secondary); font-weight: bold;"><?= htmlspecialchars($h['hospital_name']) ?></td>
                        <td><?= htmlspecialchars($h['area']) ?></td>
                        <td>
                            <div class="coord-box">Lat: <?= $h['latitude'] ?></div><br>
                            <div class="coord-box">Lng: <?= $h['longitude'] ?></div>
                        </td>
                        <td style="font-size: 14px; color: #666; max-width: 250px;"><?= htmlspecialchars($h['description']) ?></td>
                        <td style="text-align: center;">
                            <div style="display: flex; gap: 5px; justify-content: center;">
                                <a href="?edit=<?= $h['id'] ?>" class="action-btn edit" title="تعديل">تعديل</a>
                                <a href="?delete=<?= $h['id'] ?>" class="action-btn delete" onclick="return confirm('هل أنت متأكد من حذف هذا المستشفى نهائياً؟')" title="حذف">حذف</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding: 40px; color: #999;">
                            🚫 لا توجد مستشفيات مسجلة حالياً
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

</body>
</html>
