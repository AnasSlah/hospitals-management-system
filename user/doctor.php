<?php
// ابدأ الجلسة
session_start();

// =======================================================
// Database Connection
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
    die("Connection failed: " . $e->getMessage());
}

// ================== دالة تسجيل النشاط ====================
function logActivity($userId, $action, $description, $conn) {
    if (!$userId) return; 

    $stmt = $conn->prepare("INSERT INTO activity_log (user_id, action, description) VALUES (?, ?, ?)");
    if ($stmt) {
        $stmt->execute([$userId, $action, $description]);
        $stmt->closeCursor();
    }
}

// =======================================================
// 1. Fetch Current User Data
// =======================================================
$currentUserId = $_SESSION['user_id'] ?? null; 
$currentUserName = 'Guest';
$currentUserAddress = '';
$currentUserPhone = '';

if ($currentUserId) {
    $stmt = $conn->prepare("SELECT full_name, address, phone_number FROM users WHERE id = ?");
    $stmt->execute([$currentUserId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($userData) {
        $currentUserName = $userData['full_name'];
        $currentUserAddress = $userData['address'];
        $currentUserPhone = $userData['phone_number'];
    }
}

// تسجيل زيارة الصفحة
if ($currentUserId) {
    logActivity($currentUserId, 'visit_doctors_page', 'قام المستخدم بزيارة صفحة الأطباء', $conn);
}

// =======================================================
// Handle Booking Submission
// =======================================================
$successMsg = '';
$errorMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'book_doctor') {
    $patientName    = $_POST['patientName'] ?? '';
    $patientAddress = $_POST['patientAddress'] ?? '';
    $phoneNumber    = $_POST['phoneNumber'] ?? '';
    $appointmentDate= $_POST['appointmentDate'] ?? '';
    $doctorName     = $_POST['doctorName'] ?? '';
    $hospitalName   = $_POST['hospitalName'] ?? '';

    if ($phoneNumber && $appointmentDate && $doctorName && $hospitalName) {
        $stmt = $conn->prepare("INSERT INTO doctor_bookings 
            (patient_name, patient_address, phone_number, appointment_date, doctor_name, hospital_name) 
            VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$patientName, $patientAddress, $phoneNumber, $appointmentDate, $doctorName, $hospitalName]);
        
        logActivity($currentUserId, 'book_doctor', "حجز موعد مع الدكتور $doctorName في مستشفى $hospitalName بتاريخ $appointmentDate", $conn);
        
        $successMsg = "✅ تم إرسال الطلب بنجاح. يرجى متابعة الجدول للتأكيد.";
    } else {
        $errorMsg = "❌ يرجى تعبئة جميع الحقول المطلوبة.";
    }
}

// =======================================================
// Fetch All Doctors
// =======================================================
$stmt = $conn->query("SELECT * FROM doctors ORDER BY id DESC");
$doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sections = [];
foreach ($doctors as $doc) {
    $key = strtolower(str_replace(' ', '_', $doc['specialty']));
    if (!isset($sections[$key])) $sections[$key] = [];
    $sections[$key][] = $doc;
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Doctors Directory</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');
body { font-family:'Cairo',sans-serif; margin:0; background:#f4f6f9; color:#333; }
.header { background:#3498db; padding:22px; color:#fff; text-align:center; font-size:1.6rem; font-weight:700; border-radius:0 0 18px 18px; box-shadow:0 4px 14px rgba(0,0,0,0.2); }
.sections { padding:15px 20px; display:flex; gap:12px; flex-wrap:wrap; justify-content:center; }
.section-btn { background:#fff; padding:10px 16px; border-radius:10px; cursor:pointer; box-shadow:0 4px 10px rgba(0,0,0,0.06); transition:.3s; user-select:none; }
.section-btn:hover { background:#e9f5ff; }
.section-btn.active { background:#3498db; color:#fff; }
.doctors { padding:20px; display:grid; gap:18px; grid-template-columns:repeat(3,1fr); }
@media(max-width:992px){ .doctors{ grid-template-columns:repeat(2,1fr); } }
@media(max-width:600px){ .doctors{ grid-template-columns:repeat(1,1fr); } }
.doctor-card { display:none; background:#fff; padding:18px; border-radius:16px; box-shadow:0 6px 18px rgba(0,0,0,0.08); transition:.3s; }
.doctor-card:hover { transform:translateY(-6px); box-shadow:0 10px 28px rgba(0,0,0,0.12); }
.doctor-img, .doctor-letter { width:85px; height:85px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:2rem; font-weight:700; color:#fff; background:#3498db; margin-bottom:12px; box-shadow:0 4px 14px rgba(0,0,0,0.18); }
.doctor-img { object-fit:cover; border:4px solid #e7ecf3; }
.doctor-name { font-size:1.1rem; font-weight:700; margin-bottom:5px; }
.doctor-special { color:#666; font-size:.95rem; margin-bottom:10px; }
.info-line { display:flex; align-items:center; gap:6px; margin:4px 0; font-size:.92rem; color:#444; }
.stars { color:#3498db; font-size:1.1rem; margin-bottom:8px; }
.price { font-weight:700; margin-top:8px; color:#3498db; }
.book-btn { margin-top:12px; background:#3498db; color:#fff; padding:10px; text-align:center; border-radius:10px; cursor:pointer; font-weight:600; transition:.3s; user-select:none; }
.book-btn:hover { background:#217dbb; }
.modal { display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:9999; justify-content:center; align-items:center; }
.modal.active { display:flex; }
.modal-content { background:#fff; padding:25px 30px; border-radius:16px; max-width:400px; width:90%; box-shadow:0 8px 28px rgba(0,0,0,0.25); position:relative; }
.modal-header { font-weight:700; font-size:1.3rem; margin-bottom:20px; color:#3498db; text-align:center; }
.modal-close { position:absolute; top:15px; right:20px; font-size:1.5rem; cursor:pointer; color:#999; transition:color .3s; user-select:none; }
.modal-close:hover { color:#3498db; }
.modal form { display:flex; flex-direction:column; gap:15px; }
.modal label { font-size:.95rem; font-weight:600; margin-bottom:5px; color:#333; }
.modal input { padding:10px; border-radius:8px; border:1.5px solid #ccc; font-size:1rem; outline:none; transition:border-color .3s; }
.modal input:focus { border-color:#3498db; }
.modal button.submit-btn { background:#3498db; color:white; border:none; padding:12px; border-radius:10px; font-weight:700; cursor:pointer; transition:background .3s; }
.modal button.submit-btn:hover { background:#217dbb; }
.success-msg { background:#d4edda; color:#155724; padding:10px 15px; border-radius:8px; margin:10px 20px; }
.error-msg { background:#f8d7da; color:#721c24; padding:10px 15px; border-radius:8px; margin:10px 20px; }
</style>
</head>
<body>

<div class="header">Doctors Directory</div>

<?php if(!empty($successMsg) || !empty($errorMsg)): ?>
    <div id="bookingMessage" class="<?= !empty($successMsg)?'success-msg':'error-msg' ?>">
        <?= !empty($successMsg) ? $successMsg : htmlspecialchars($errorMsg) ?>
    </div>
    <script>
        setTimeout(function(){
            const msg = document.getElementById('bookingMessage');
            if(msg) msg.style.display = 'none';
        }, 10000);
    </script>
<?php endif; ?>

<div class="sections">
    <?php foreach($sections as $key=>$docs): ?>
        <div class="section-btn" onclick="filterSection('<?= $key ?>', this)">
            <?= ucwords(str_replace('_',' ',$key)) ?>
        </div>
    <?php endforeach; ?>
</div>

<div class="doctors">
    <?php foreach($sections as $key=>$docs): ?>
        <?php foreach($docs as $d): ?>
            <div class="doctor-card <?= $key ?>">
                <?php 
                // تعديل مسار الصورة: نخرج من المجلد الحالي ثم ندخل لمجلد uploads
                $imagePath = "../uploads/" . $d['image'];
                if(!empty($d['image'])): 
                ?>
                    <img src="<?= htmlspecialchars($imagePath) ?>" class="doctor-img" alt="<?= htmlspecialchars($d['name']) ?>" />
                <?php else: ?>
                    <div class="doctor-letter"><?= strtoupper($d['name'][0]) ?></div>
                <?php endif; ?>
                
                <div class="doctor-name"><?= htmlspecialchars($d['name']) ?></div>
                <div class="doctor-special"><?= htmlspecialchars($d['specialty']) ?></div>
                <div class="stars"><?= str_repeat('★', $d['stars']) ?></div>
                <div class="info-line">📍 <?= htmlspecialchars($d['hospital']) ?></div>
                <div class="info-line">🕒 <?= htmlspecialchars($d['work_time']) ?></div>
                <div class="price">Price: <?= htmlspecialchars($d['price']) ?> EGP</div>
                <div class="book-btn" onclick="openBookingModal('<?= htmlspecialchars($d['name']) ?>', '<?= htmlspecialchars($d['hospital']) ?>')">Book Now</div>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>
</div>

<div class="modal" id="bookingModal">
    <div class="modal-content">
        <div class="modal-close" onclick="closeBookingModal()">×</div>
        <div class="modal-header" id="modalDoctorName">Booking Appointment</div>
        <form id="bookingForm" method="POST">
            <input type="hidden" name="action" value="book_doctor">
            
            <label for="patientName">Patient Name</label>
            <input type="text" id="patientName" name="patientName" 
                   value="<?= htmlspecialchars($currentUserName) ?>" 
                   required <?= $currentUserId ? 'readonly' : '' ?>>
            
            <label for="patientAddress">Address</label>
            <input type="text" id="patientAddress" name="patientAddress" 
                   value="<?= htmlspecialchars($currentUserAddress) ?>" 
                   required <?= $currentUserId ? 'readonly' : '' ?>>
            
            <label for="phoneNumber">Phone Number</label>
            <input type="tel" id="phoneNumber" name="phoneNumber" 
                   required pattern="[0-9+]{6,15}" 
                   value="<?= htmlspecialchars($currentUserPhone) ?>">
            
            <label for="appointmentDate">Appointment Date</label>
            <input type="date" id="appointmentDate" name="appointmentDate" 
                   required value="<?= date('Y-m-d') ?>">
            
            <label for="doctorName">Doctor</label>
            <input type="text" id="doctorName" name="doctorName" readonly required>
            
            <label for="hospitalName">Hospital</label>
            <input type="text" id="hospitalName" name="hospitalName" readonly required>
            
            <button type="submit" class="submit-btn">Confirm Booking</button>
        </form>
    </div>
</div>

<script>
function filterSection(section, btn){
    document.querySelectorAll(".section-btn").forEach(b=>b.classList.remove("active"));
    btn.classList.add("active");
    document.querySelectorAll(".doctor-card").forEach(card=>card.style.display="none");
    document.querySelectorAll(".doctor-card."+section).forEach(card=>card.style.display="block");
}

function openBookingModal(doctorName, hospitalName){
    const modal = document.getElementById("bookingModal");
    modal.classList.add("active");
    document.getElementById("doctorName").value = doctorName;
    document.getElementById("hospitalName").value = hospitalName;
    document.getElementById("modalDoctorName").textContent = `Booking Appointment - ${doctorName}`;
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('appointmentDate').setAttribute('min', today);
}

function closeBookingModal(){
    document.getElementById("bookingModal").classList.remove("active");
}

document.addEventListener("DOMContentLoaded",()=>{
    const firstBtn=document.querySelector(".section-btn");
    if(firstBtn) firstBtn.click();
});
</script>

</body>
</html>
