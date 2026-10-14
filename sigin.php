<?php
// اتصال بالداتا بيز
$host = "localhost";   // عدل لو عندك معلومات مختلفة
$user = "root";
$pass = "";
$dbname = "hospitals_db";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$successMsg = "";
$errorMsg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $dob = $_POST['dob'];
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $errorMsg = "Passwords do not match.";
    } else {
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['profile_picture']['tmp_name'];
            $fileName = $_FILES['profile_picture']['name'];
            $fileSize = $_FILES['profile_picture']['size'];
            $fileType = $_FILES['profile_picture']['type'];
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));

            $allowedfileExtensions = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($fileExtension, $allowedfileExtensions)) {
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                $uploadFileDir = './uploads/';
                if (!is_dir($uploadFileDir)) {
                    mkdir($uploadFileDir, 0755, true);
                }
                $dest_path = $uploadFileDir . $newFileName;

                if(move_uploaded_file($fileTmpPath, $dest_path)) {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);

                    $stmt = $conn->prepare("INSERT INTO users (full_name, email, date_of_birth, phone_number, address, profile_picture, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssssss", $full_name, $email, $dob, $phone, $address, $newFileName, $password_hash);

                    if ($stmt->execute()) {
                        $successMsg = "Registration successful!";
                    } else {
                        if ($conn->errno === 1062) {
                            $errorMsg = "Email already registered.";
                        } else {
                            $errorMsg = "Database error: " . $conn->error;
                        }
                        unlink($dest_path);
                    }
                    $stmt->close();

                } else {
                    $errorMsg = "Error uploading profile picture.";
                }

            } else {
                $errorMsg = "Upload failed. Allowed file types: " . implode(", ", $allowedfileExtensions);
            }

        } else {
            $errorMsg = "Please upload a profile picture.";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>New User Registration</title>
    <style>
        :root {
            --btn-color: #16a085;
            --card-bg: #ffffff;
            --page-bg: #2c3e50;
            --text-dark: #333333;
            --text-medium: #8898aa;
            --phone-prefix-bg: #eeeeee;
            --white: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--page-bg);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            direction: ltr;
        }

        .register-container {
            background-color: var(--card-bg);
            width: 100%;
            max-width: 450px;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            text-align: center;
            color: var(--text-dark);
        }

        .register-title {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 20px;
            color: var(--btn-color);
        }

        .form-group {
            margin-bottom: 15px;
            text-align: left;
        }

        .form-group label {
            display: block;
            color: var(--text-medium);
            font-size: 13px;
            margin-bottom: 6px;
            font-weight: 600;
        }

        .form-group input:not([type="date"]):not([type="tel"]):not([type="file"]) {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #bbb;
            border-radius: 6px;
            font-size: 15px;
            color: var(--text-dark);
            outline: none;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            border-color: var(--btn-color);
            box-shadow: 0 0 0 1px var(--btn-color);
        }

        .phone-group {
            display: flex;
            align-items: center;
            justify-content: flex-start;
        }

        .phone-group .prefix {
            padding: 12px 15px;
            background-color: var(--phone-prefix-bg);
            border: 1px solid #bbb;
            border-left: none;
            border-radius: 0 6px 6px 0;
            color: var(--text-medium);
            font-weight: bold;
            font-size: 15px;
            pointer-events: none;
            min-width: 55px;
            text-align: center;
        }

        .phone-group input {
            flex-grow: 1;
            padding: 12px 15px;
            border: 1px solid #bbb;
            border-right: none;
            border-radius: 6px 0 0 6px;
            font-size: 15px;
            color: var(--text-dark);
            outline: none;
            transition: border-color 0.3s;
            min-width: 0;
        }
        
        .form-group input[type="date"] {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #bbb;
            border-radius: 6px;
            font-size: 15px;
            color: var(--text-dark);
            outline: none;
            cursor: pointer;
        }

        .form-group input[type="file"] {
            width: 100%;
            padding: 6px 10px;
            border: 1px solid #bbb;
            border-radius: 6px;
            font-size: 14px;
            color: var(--text-dark);
            outline: none;
            cursor: pointer;
            transition: border-color 0.3s;
        }

        .form-group input[type="file"]:focus {
            border-color: var(--btn-color);
            box-shadow: 0 0 0 1px var(--btn-color);
        }

        .btn-register {
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
            margin-top: 20px;
        }

        .btn-register:hover {
            opacity: 0.9;
        }

        .back-login-link {
            margin-top: 25px;
            font-size: 14px;
            text-align: center;
        }

        .back-login-link a {
            color: var(--btn-color);
            text-decoration: none;
            font-weight: 600;
        }

        .back-login-link a:hover {
            text-decoration: underline;
        }
        
        .error-message {
            color: #e74c3c;
            font-size: 13px;
            margin-top: -10px;
            margin-bottom: 10px;
            display: <?php echo ($errorMsg != "") ? 'block' : 'none'; ?>;
            text-align: left;
        }

        .success-message {
            color: #27ae60;
            font-size: 14px;
            margin-bottom: 15px;
            display: <?php echo ($successMsg != "") ? 'block' : 'none'; ?>;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <div class="register-container">
        <div class="register-title">Create an Account</div>

        <?php if ($errorMsg != ""): ?>
            <p class="error-message"><?php echo htmlspecialchars($errorMsg); ?></p>
        <?php endif; ?>

        <?php if ($successMsg != ""): ?>
            <p class="success-message"><?php echo htmlspecialchars($successMsg); ?></p>
        <?php endif; ?>

        <?php if ($successMsg == ""): ?>
        <form method="POST" enctype="multipart/form-data" id="registrationForm">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" name="name" id="name" placeholder="Enter your full name" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" placeholder="name@example.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="dob">Date of Birth</label>
                <input type="date" name="dob" id="dob" required value="<?php echo isset($_POST['dob']) ? htmlspecialchars($_POST['dob']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <div class="phone-group">
                    <span class="prefix">+249</span>
                    <input type="tel" name="phone" id="phone" placeholder="912 345 678" pattern="[0-9]{8,15}" title="Enter a valid phone number" required value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="address">Address</label>
                <input type="text" name="address" id="address" placeholder="e.g., Street 40, Building 12" required value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="profile-picture">Profile Picture</label>
                <input type="file" name="profile_picture" id="profile-picture" accept="image/*" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" placeholder="Minimum 6 characters" required>
            </div>

            <div class="form-group">
                <label for="confirm-password">Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm-password" placeholder="Re-enter password" required>
            </div>

            <button type="submit" class="btn-register">Register</button>
        </form>
        <?php endif; ?>

        <div class="back-login-link">
            <a href="index.php">&larr; Back to Login Page</a>
        </div>
    </div>

</body>
</html>