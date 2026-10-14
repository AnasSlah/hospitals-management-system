<?php
session_start();

// إعدادات قاعدة البيانات
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "hospitals_db";

// إنشاء اتصال
$conn = new mysqli($host, $user, $pass, $dbname);

// التحقق من الاتصال
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$errorMsg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // 1. البحث في جدول المشرفين (admins) أولاً
    $stmt_admin = $conn->prepare("SELECT id, full_name, password FROM admins WHERE email = ?");
    $stmt_admin->bind_param("s", $email);
    $stmt_admin->execute();
    $res_admin = $stmt_admin->get_result();

    if ($res_admin->num_rows === 1) {
        $admin_data = $res_admin->fetch_assoc();
        
        // التحقق من كلمة المرور (سواء كانت نصاً عادياً مثل 123456 أو مشفرة)
        if ($password === $admin_data['password'] || password_verify($password, $admin_data['password'])) {
            $_SESSION['admin_id'] = $admin_data['id'];
            $_SESSION['admin_name'] = $admin_data['full_name'];
            
            // التوجيه إلى لوحة تحكم الأدمن
            header("Location: admin"); 
            exit;
        } else {
            $errorMsg = "Incorrect password for Admin.";
        }
    } 
    // 2. البحث في جدول المستخدمين (users) إذا لم يوجد في الأدمن
    else {
        $stmt_user = $conn->prepare("SELECT id, full_name, password_hash FROM users WHERE email = ?");
        $stmt_user->bind_param("s", $email);
        $stmt_user->execute();
        $res_user = $stmt_user->get_result();

        if ($res_user->num_rows === 1) {
            $user_data = $res_user->fetch_assoc();
            
            if (password_verify($password, $user_data['password_hash'])) {
                $_SESSION['user_id'] = $user_data['id'];
                $_SESSION['user_name'] = $user_data['full_name'];
                
                header("Location: user/index.php");
                exit;
            } else {
                $errorMsg = "Incorrect password.";
            }
        } else {
            $errorMsg = "No account found with this email.";
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login</title>
    <style>
        :root {
            --btn-color: #16a085;
            --card-bg: #ffffff;
            --body-bg: #2c3e50;
            --text-dark: #333333;
            --text-light: #2c3e50;
            --input-bg: #ecf0f1;
            --white: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--body-bg);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-container {
            background-color: var(--card-bg);
            width: 100%;
            max-width: 380px;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            text-align: center;
            color: var(--text-dark);
        }

        .login-header h2 {
            margin-bottom: 5px;
            font-size: 24px;
            color: var(--text-dark);
        }

        .login-header p {
            margin-bottom: 30px;
            font-size: 14px;
            color: var(--text-light);
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            color: var(--text-dark);
            font-size: 13px;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
            color: var(--text-dark);
            background-color: var(--input-bg);
            outline: none;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .form-group input:focus {
            border-color: var(--btn-color);
            box-shadow: 0 0 0 2px var(--btn-color);
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background-color: var(--btn-color);
            color: var(--white);
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: opacity 0.3s;
            margin-top: 10px;
        }

        .btn-submit:hover {
            opacity: 0.9;
        }

        .new-user-link {
            margin-top: 25px;
            font-size: 14px;
            color: var(--text-light);
        }

        .new-user-link a {
            color: var(--btn-color);
            text-decoration: none;
            font-weight: 500;
            transition: 0.3s;
        }

        .new-user-link a:hover {
            text-decoration: underline;
        }

        .error-message {
            color: #e74c3c;
            background: #fceae9;
            padding: 10px;
            border-radius: 5px;
            font-size: 14px;
            margin-bottom: 20px;
            text-align: center;
            border: 1px solid #e74c3c;
        }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="login-header">
            <h2>Welcome Back</h2>
            <p>Sign in to your account</p>
        </div>

        <?php if ($errorMsg != ""): ?>
            <div class="error-message"><?php echo htmlspecialchars($errorMsg); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    placeholder="Enter your email" 
                    required 
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                />
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    placeholder="Enter your password" 
                    required 
                />
            </div>

            <button type="submit" class="btn-submit">Login</button>
        </form>

        <div class="new-user-link">
            Don't have an account? 
            <a href="sigin.php">Register Here</a>
        </div>
    </div>

</body>
</html>
