<?php
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

// ================= FETCH DATA =================
$stmt = $conn->prepare("
    SELECT appointment_date, doctor_name, hospital_name, status
    FROM doctor_bookings
    ORDER BY appointment_date DESC
");
$stmt->execute();
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Patient History</title>

  <style>
    * { box-sizing: border-box; }
    body {
      margin: 0;
      padding: 30px;
      font-family: 'Segoe UI', sans-serif;
      background-color: #f4f7f6;
    }
    h2 {
      color: #2c3e50;
      margin-bottom: 30px;
      border-bottom: 2px solid #ddd;
      padding-bottom: 10px;
      max-width: 1200px;
      margin-left: auto;
      margin-right: auto;
    }

    .profile-container {
      display: grid;
      grid-template-columns: 1fr;
      gap: 30px;
      max-width: 1200px;
      margin: 0 auto;
    }

    .history-card {
      background: white;
      padding: 25px;
      border-radius: 12px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    }

    .history-card h3 {
      color: #3498db;
      margin-top: 0;
      margin-bottom: 15px;
      font-size: 1.3rem;
      border-bottom: 1px solid #eee;
      padding-bottom: 10px;
    }

    .appointment-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
      font-size: 0.9rem;
    }

    .appointment-table th,
    .appointment-table td {
      padding: 12px;
      text-align: left;
      border-bottom: 1px solid #f0f0f0;
    }

    .appointment-table th {
      background-color: #f7f7f7;
      color: #2c3e50;
      font-weight: 600;
    }

    @media (max-width: 900px) {
      body { padding: 15px; }

      .appointment-table,
      .appointment-table thead,
      .appointment-table tbody,
      .appointment-table th,
      .appointment-table td,
      .appointment-table tr {
        display: block;
      }

      .appointment-table thead tr {
        position: absolute;
        top: -9999px;
        left: -9999px;
      }

      .appointment-table tr {
        border: 1px solid #ccc;
        margin-bottom: 10px;
        border-radius: 8px;
        overflow: hidden;
      }

      .appointment-table td {
        border: none;
        border-bottom: 1px solid #eee;
        position: relative;
        padding-left: 50%;
        text-align: right;
        font-size: 1rem;
      }

      .appointment-table td:before {
        content: attr(data-label);
        position: absolute;
        left: 6px;
        width: 45%;
        font-weight: bold;
        color: #555;
        text-align: left;
      }
    }
  </style>
</head>

<body>

<h2>Patient Appointments History</h2>

<div class="profile-container">
  <div class="history-card">
    <h3>All Appointments</h3>

    <table class="appointment-table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Doctor</th>
          <th>Hospital</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>

      <?php if ($appointments): ?>
        <?php foreach ($appointments as $row): ?>
          <tr>
            <td data-label="Date"><?= htmlspecialchars($row['appointment_date']) ?></td>
            <td data-label="Doctor"><?= htmlspecialchars($row['doctor_name']) ?></td>
            <td data-label="Hospital"><?= htmlspecialchars($row['hospital_name']) ?></td>
            <td data-label="Status"
                style="font-weight:600;
                color:<?= $row['status'] === 'accepted' ? '#27ae60' : '#c0392b' ?>;">
              <?= htmlspecialchars(ucfirst($row['status'])) ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="4" style="text-align:center;">No appointments found</td>
        </tr>
      <?php endif; ?>

      </tbody>
    </table>
  </div>
</div>

</body>
</html>