<?php
session_start();

// إذا المستخدم غير مسجل دخول، يرجع لتسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "hospitals_db";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$user_name = "";
$profile_picture = "";

$stmt = $conn->prepare("SELECT full_name, profile_picture FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($full_name, $profile_picture_db);
if ($stmt->fetch()) {
    $user_name = $full_name;
    if ($profile_picture_db) {
        $profile_picture = "../uploads/" . $profile_picture_db;
    }
}
$stmt->close();
$conn->close();

$has_profile_picture = ($profile_picture && file_exists($profile_picture));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Hospital Dashboard</title>
    <style>
        /* --- General and Base Styles --- */
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { height: 100vh; overflow: hidden; direction: ltr; }
        .dashboard-container { display: flex; height: 100vh; }

        /* --- Sidebar (Left Navigation) --- */
        .sidebar {
            width: 250px; background-color: #2c3e50; color: white;
            display: flex; flex-direction: column; justify-content: space-between;
            flex-shrink: 0; transition: transform 0.3s ease-in-out;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
            z-index: 50;
            position: relative; 
        }
        
        .logo {
            padding: 20px;
            text-align: center;
        }
        .logo .user-avatar {
            margin: 0 auto 10px;
            width: 60px; 
            height: 60px;
            border-radius: 50%;
            overflow: hidden;
            background-color: #3498db;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 30px;
            font-weight: bold;
            color: white;
        }
        .logo .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .logo h3 {
            font-size: 1.2rem;
            margin-top: 5px;
        }

        .nav-item a {
            display: block; padding: 15px 20px; color: #ecf0f1; text-decoration: none;
            transition: background-color 0.3s;
            text-align: left;
        }
        .nav-item a:hover { background-color: #34495e; }
        .nav-item.active a { background-color: #1abc9c; }

        /* --- Logout Footer --- */
        .logout-section { padding: 20px; }
        .logout-btn {
            display: block; text-align: center; background-color: #e74c3c;
            color: white; padding: 10px; border-radius: 5px; text-decoration: none;
            font-weight: bold;
        }
        .logout-btn:hover { background-color: #c0392b; }

        /* --- Main Content Area --- */
        .main-content { flex-grow: 1; display: flex; flex-direction: column; background-color: #f4f7f6; }
        
        /* Top Header */
        .top-header {
            height: 60px; background: white; display: flex; align-items: center;
            justify-content: space-between; padding: 0 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            font-weight: 600;
            font-size: 16px;
        }
        
        /* User Avatar in Header */
        .header-user-avatar {
            width: 35px;
            height: 35px;
            background: #3498db;
            border-radius: 50%;
            color: white;
            text-align: center;
            line-height: 35px;
            font-weight: bold;
            overflow: hidden;
        }
        .header-user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        /* Mobile Menu Button */
        .menu-toggle { display: none; cursor: pointer; font-size: 24px; }
        
        /* --- iframe Container --- */
        iframe[name="content-frame"] {
            width: 100%;
            height: calc(100vh - 60px); 
            border: none;
        }

        /* --- 📱 Responsiveness (Mobile Media Query) --- */
        @media (max-width: 768px) {
            .sidebar { 
                position: fixed; 
                height: 100%; 
                transform: translateX(-100%); 
                width: 220px; 
                left: 0; 
                box-shadow: 2px 0 5px rgba(0, 0, 0, 0.2);
            }
            .sidebar.active { transform: translateX(0); }
            .menu-toggle { display: block; }
            .top-header div:nth-child(2) { display: none; }
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <aside class="sidebar" id="sidebar">
        <div>
            <div class="logo">
                <div class="user-avatar">
                    <?php if ($has_profile_picture): ?>
                        <img src="<?= htmlspecialchars($profile_picture) ?>" alt="User Profile Picture" />
                    <?php else: ?>
                        <?= strtoupper(substr($user_name, 0, 1)) ?>
                    <?php endif; ?>
                </div>
                <h3>Central Hospital</h3>
                <p style="color:#bdc3c7; font-size:14px; margin-top:5px;"><?= htmlspecialchars($user_name) ?></p>
            </div>
            <nav>
                <ul style="list-style: none;" id="nav-list">
                    <li class="nav-item active"><a href="map.php" target="content-frame" data-file="map.html">Dashboard (Map)</a></li>
                    <li class="nav-item"><a href="doctor.php" target="content-frame" data-file="doctors.html">Doctors</a></li>
                    <li class="nav-item"><a href="doctor_rating.php" target="content-frame" data-file="doctor_rating.html">Doctor Report</a></li>
                    <li class="nav-item"><a href="patient_profile.php" target="content-frame" data-file="profile.html">My Profile</a></li>
                    <li class="nav-item"><a href="history.php" target="content-frame" data-file="history.html">History</a></li>
                    <li class="nav-item"><a href="contact.php" target="content-frame" data-file="contact.html">Contact Us</a></li>
                </ul>
            </nav>
        </div>
        <div class="logout-section">
            <a href="../index.php" class="logout-btn nav-item">Logout</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="top-header">
            <div class="menu-toggle" id="menu-toggle">☰</div>
            <div>Welcome, <?= htmlspecialchars($user_name) ?></div>
            <div class="header-user-avatar">
                <?php if ($has_profile_picture): ?>
                    <img src="<?= htmlspecialchars($profile_picture) ?>" alt="User Profile Picture" />
                <?php else: ?>
                    <?= strtoupper(substr($user_name, 0, 1)) ?>
                <?php endif; ?>
            </div>
        </header>

        <iframe name="content-frame" id="content-frame" src="map.php"></iframe>
    </main>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.getElementById('sidebar');
        const menuToggle = document.getElementById('menu-toggle');
        const navItems = document.querySelectorAll('#nav-list .nav-item');
        const contentFrame = document.getElementById('content-frame');
        
        function isMobile() {
            return window.innerWidth <= 768;
        }
        
        function setActiveLink(url) {
            navItems.forEach(item => item.classList.remove('active'));
            const currentFileName = url.split('/').pop().split('?')[0];
            navItems.forEach(item => {
                const link = item.querySelector('a');
                const linkFileName = link.href.split('/').pop().split('?')[0]; 
                if (linkFileName === currentFileName) {
                    item.classList.add('active');
                }
            });
        }
        
        setActiveLink(contentFrame.src);
        
        navItems.forEach(item => {
            const link = item.querySelector('a');
            link.addEventListener('click', () => {
                navItems.forEach(i => i.classList.remove('active'));
                item.classList.add('active');
                if (isMobile()) {
                    setTimeout(() => { sidebar.classList.remove('active'); }, 100); 
                }
            });
        });

        menuToggle.addEventListener('click', (event) => {
            event.stopPropagation();
            sidebar.classList.toggle('active');
        });

        document.body.addEventListener('click', (event) => {
            if (sidebar.classList.contains('active') && isMobile()) {
                const isClickInsideSidebar = sidebar.contains(event.target);
                if (!isClickInsideSidebar) {
                    sidebar.classList.remove('active');
                }
            }
        });
    });
</script>

</body>
</html>