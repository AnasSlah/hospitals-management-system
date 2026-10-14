<?php
session_start();

// 1. التحقق من تسجيل الدخول (حسب النظام المستخدم لديك، هنا نفترض admin)
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// ================= DB CONNECTION =================
try {
    $conn = new PDO(
        "mysql:host=localhost;dbname=hospitals_db;charset=utf8mb4",
        "root",
        "",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// ================= [التعديل الجديد: تصفير الإشعارات] =================
// بمجرد تحميل هذه الصفحة، يتم تحويل كل الحالات غير المقروءة إلى مقروءة
try {
    $updateStmt = $conn->prepare("UPDATE activity_log SET is_read = 1 WHERE is_read = 0");
    $updateStmt->execute();
} catch (PDOException $e) {
    // خطأ صامت لعدم تعطيل العرض
}

// ================= FETCH ACTIVITY LOG WITH USERNAME =================
$stmt = $conn->prepare("
    SELECT 
        a.action, 
        a.description, 
        a.created_at, 
        u.full_name 
    FROM activity_log a
    LEFT JOIN users u ON a.user_id = u.id
    ORDER BY a.created_at DESC
");
$stmt->execute();
$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>سجل نشاطات النظام</title>
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
    * { box-sizing: border-box; }
    body {
      margin: 0; padding: 20px;
      font-family: 'Tajawal', sans-serif;
      background-color: #f4f7f6;
    }
    .container { max-width: 100%; margin: 0 auto; }
    h2 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; margin-bottom: 20px; font-size: 1.5rem; }
    
    .history-card {
      background: white; padding: 20px; border-radius: 12px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    }

    .log-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .log-table th, .log-table td { padding: 12px 15px; text-align: right; border-bottom: 1px solid #f0f0f0; }
    .log-table th { background-color: #f7f7f7; color: #2c3e50; font-weight: 700; border-radius: 4px; }

    .user-badge { color: #2c3e50; font-weight: 600; font-size: 0.9rem; }
    .action-badge {
      padding: 4px 10px; border-radius: 50px; font-size: 0.75rem;
      background: #e1f5fe; color: #0288d1; font-weight: bold;
      display: inline-block;
    }
    .timestamp { color: #7f8c8d; font-size: 0.8rem; white-space: nowrap; }
    .desc-text { color: #555; font-size: 0.9rem; line-height: 1.4; }

    @media (max-width: 900px) {
      .log-table, thead, tbody, th, td, tr { display: block; }
      thead tr { position: absolute; top: -9999px; }
      tr { border: 1px solid #ddd; margin-bottom: 10px; border-radius: 8px; background: #fff; }
      td { border: none; border-bottom: 1px solid #eee; position: relative; padding-right: 40%; text-align: left; min-height: 45px; display: flex; align-items: center; justify-content: flex-end; }
      td:before {
        content: attr(data-label); position: absolute; right: 15px;
        width: 35%; font-weight: bold; color: #3498db; text-align: right; font-size: 0.85rem;
      }
    }
  </style>
</head>
<body>

<div class="container">
  <h2><i class="fa-solid fa-history"></i> سجل النشاطات الأخير</h2>

  <div class="history-card">
    <table class="log-table">
      <thead>
        <tr>
          <th>المستخدم</th>
          <th>نوع النشاط</th>
          <th>الوصف</th>
          <th>التوقيت</th>
        </tr>
      </thead>
      <tbody>

      <?php if ($activities): ?>
        <?php foreach ($activities as $row): ?>
          <tr>
            <td data-label="المستخدم" class="user-badge">
                <i class="fa-regular fa-user" style="margin-left:5px; color:#3498db;"></i> <?= htmlspecialchars($row['full_name'] ?? 'زائر') ?>
            </td>
            <td data-label="نوع النشاط">
              <span class="action-badge"><?= htmlspecialchars($row['action']) ?></span>
            </td>
            <td data-label="الوصف" class="desc-text">
              <?= htmlspecialchars($row['description']) ?>
            </td>
            <td data-label="التوقيت" class="timestamp">
              <i class="fa-regular fa-clock" style="margin-left:5px;"></i> <?= date('Y-m-d H:i', strtotime($row['created_at'])) ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="4" style="text-align:center; padding: 40px; color: #999;">لا توجد سجلات نشاط متاحة حالياً.</td></tr>
      <?php endif; ?>

      </tbody>
    </table>
  </div>
</div>

</body>
</html>
