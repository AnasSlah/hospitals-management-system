<?php
session_start();

// 1. الاتصال بقاعدة البيانات
$conn = new mysqli("localhost", "root", "", "hospitals_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 2. التحقق من تسجيل الدخول
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../index.php");
    exit();
}

// 3. جلب بيانات الأدمن
$admin_id = $_SESSION['admin_id'];
$sql = "SELECT * FROM admins WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

// جلب عدد الإشعارات غير المقروءة
$sql_notify = "SELECT COUNT(*) AS unread_count FROM activity_log WHERE is_read = 0";
$res_notify = $conn->query($sql_notify);
$unread_count = $res_notify->fetch_assoc()['unread_count'];

// 4. تجهيز البيانات
$fullname = $admin['full_name'];
$role = "System Administrator"; 
$first_letter = strtoupper(substr($fullname, 0, 1));

// اسم ملف الصورة فقط من الداتا بيز
$image_filename = $admin['profile_image'];

// مسار الفحص (على السيرفر): ../uploads/filename
$image_path_server = "../uploads/" . $image_filename;

// مسار العرض (URL): uploads/filename بدون ../
$image_path_url = "../uploads/" . $image_filename;

function displayAvatar($server_path, $url_path, $letter, $size = '70px') {
    if (!empty($server_path) && file_exists($server_path)) {
        return "<img src='$url_path' style='width:100%; height:100%; object-fit:cover; border-radius:50%;'>";
    } else {
        return "<div style='width:$size; height:$size; background:#3498db; color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:".(intval($size)*0.4)."px; font-weight:bold;'>$letter</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Admin Dashboard - <?php echo htmlspecialchars($fullname); ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
/* التنسيقات كما في كودك السابق */
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',Tahoma,Verdana,sans-serif;}
:root{--primary:#3498db;--primary-dark:#2c3e50;--bg:#f4f7f6;--white:#ffffff;--text:#2c3e50;--danger:#e74c3c;}
body{background:var(--bg);height:100vh;overflow:hidden;}
.dashboard{display:flex;height:100vh;}
.sidebar{width:260px;background:var(--primary-dark);color:#fff;display:flex;flex-direction:column;justify-content:space-between;transition:.3s;}
.sidebar .brand{padding:20px;text-align:center;}
.avatar{width:70px;height:70px;background:var(--primary);border-radius:50%;margin:0 auto 10px;display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:bold; overflow: hidden;}
.brand h3{margin-bottom:5px;}
.brand p{font-size:14px;opacity:.8;}
.menu{list-style:none;}
.menu li a{display:flex; align-items:center; justify-content:space-between; padding:15px 25px;color:#ecf0f1;text-decoration:none;transition:.3s; position: relative;}
.menu li a:hover{background:rgba(255,255,255,.1);}
.menu li.active a{background:var(--primary);font-weight:bold;}
.badge-notify {
    background: var(--danger);
    color: white;
    font-size: 11px;
    padding: 2px 7px;
    border-radius: 10px;
    font-weight: bold;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}
.logout{padding:20px;}
.logout a{display:block;background:#e74c3c;padding:10px;border-radius:6px;text-align:center;color:#fff;text-decoration:none;}
.main{flex:1;display:flex;flex-direction:column;}
.header{height:60px;background:var(--white);display:flex;align-items:center;justify-content:space-between;padding:0 20px;box-shadow:0 2px 6px rgba(0,0,0,.08);}
.menu-btn{font-size:24px;cursor:pointer;display:none;}
.header .title{font-weight:600;color:var(--text);}
.content{flex:1;padding:0;}
.content iframe{width:100%;height:100%;border:none;}
@media(max-width:768px){.sidebar{position:fixed;left:-100%;height:100%;z-index:100;}.sidebar.show{left:0;}.menu-btn{display:block;}}
</style>
</head>

<body>

<div class="dashboard">

    <aside class="sidebar" id="sidebar">
        <div>
            <div class="brand">
                <div class="avatar">
                    <?php echo displayAvatar($image_path_server, $image_path_url, $first_letter); ?>
                </div>
                <h3><?php echo htmlspecialchars($fullname); ?></h3>
                <p><?php echo $role; ?></p>
            </div>

            <ul class="menu" id="menu">
                <li class="active">
                    <a href="#" onclick="loadPage('map.php')">Dashboard</a>
                </li>
                <li>
                    <a href="#" onclick="loadPage('doctor.php')">Doctors</a>
                </li>
                <li>
                    <a href="#" onclick="loadPage('doctor_rating.php')">Reports</a>
                </li>
                <li>
                    <a href="#" onclick="loadPage('patient_profile.php?id=<?php echo $admin_id; ?>')">Profile</a>
                </li>
                
                <li>
                    <a href="#" onclick="loadPage('history.php')">
                        <span>History</span>
                        <?php if($unread_count > 0): ?>
                            <span class="badge-notify" id="historyBadge"><?php echo $unread_count; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <a href="#" onclick="loadPage('contact.php')">Contact</a>
                </li>
            </ul>
        </div>

        <div class="logout">
            <a href="logout.php">Logout</a>
        </div>
    </aside>

    <div class="main">
        <header class="header">
            <div class="menu-btn" id="menuBtn">☰</div>
            <div class="title">Admin Dashboard</div>
            
            <div class="avatar" style="width:38px;height:38px;font-size:18px; position:relative;">
                <?php echo displayAvatar($image_path_server, $image_path_url, $first_letter, '38px'); ?>
            </div>
        </header>

        <section class="content">
            <iframe id="contentFrame" src="map.php"></iframe>
        </section>
    </div>

</div>

<script>
const menuBtn = document.getElementById("menuBtn");
const sidebar = document.getElementById("sidebar");
const menuItems = document.querySelectorAll(".menu li");
const frame = document.getElementById("contentFrame");

menuBtn.onclick = () => {
    sidebar.classList.toggle("show");
};

function loadPage(page){
    frame.src = page;
    menuItems.forEach(i => i.classList.remove("active"));
    
    event.currentTarget.parentElement.classList.add("active");
    
    if(page.includes('history.php')){
        const badge = document.getElementById('historyBadge');
        if(badge) badge.style.display = 'none';
    }

    sidebar.classList.remove("show");
}
</script>

</body>
</html>