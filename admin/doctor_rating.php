<?php
// إعدادات الاتصال بقاعدة البيانات
$servername = "localhost";  
$username = "root";         
$password = "";             
$dbname = "hospitals_db";  

// إنشاء الاتصال
$conn = new mysqli($servername, $username, $password, $dbname);

// التحقق من الاتصال
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

// جلب البيانات من الجدول مع الحقل الجديد
$sql = "SELECT * FROM doctor_ratings ORDER BY created_at ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>عرض تقييمات الأطباء</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            direction: rtl; 
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>

<h2>تقييمات الأطباء</h2>

<table>
    <thead>
        <tr>
            <th>الرقم</th>
            <th>اسم الطبيب</th>
            <th>التقييم بالنجوم</th>
            <th>الخبرة</th>
            <th>التعليقات</th>
            <th>اسم المعلق</th>
            <th>تاريخ التقييم</th>
        </tr>
    </thead>
    <tbody>
        <?php
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $stars = str_repeat("⭐", intval($row["star_rating"]));
                $commenter = !empty($row["commenter_name"]) ? $row["commenter_name"] : '-';
                echo "<tr>
                    <td>{$row['id']}</td>
                    <td>{$row['doctor_name']}</td>
                    <td>{$stars}</td>
                    <td>{$row['experience']}</td>
                    <td>{$row['comments']}</td>
                    <td>{$commenter}</td>
                    <td>{$row['created_at']}</td>
                </tr>";
            }
        } else {
            echo "<tr><td colspan='7'>لا توجد بيانات لعرضها</td></tr>";
        }
        $conn->close();
        ?>
    </tbody>
</table>

</body>
</html>