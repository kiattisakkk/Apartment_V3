<?php
require_once 'config.php'; // Include the database configuration file

$selected_room = $selected_month = $selected_year = "";
$rooms = [];
$months = [];
$years = [];

// Fetch distinct rooms, months, and years from the database for dropdown
$roomQuery = "SELECT DISTINCT Room_number FROM users WHERE Room_number IN ('201', '202', '302', '303', '304', '305', '306', '203', '204', '205', '206', '301', 'S1', 'S2') ORDER BY Room_number";
$monthQuery = "SELECT DISTINCT month FROM bill ORDER BY month";
$yearQuery = "SELECT DISTINCT year FROM bill ORDER BY year";

if ($roomResult = $conn->query($roomQuery)) {
    while ($row = $roomResult->fetch_assoc()) {
        $rooms[] = $row['Room_number'];
    }
}
if ($monthResult = $conn->query($monthQuery)) {
    while ($row = $monthResult->fetch_assoc()) {
        $months[] = $row['month'];
    }
}
if ($yearResult = $conn->query($yearQuery)) {
    while ($row = $yearResult->fetch_assoc()) {
        $years[] = $row['year'];
    }
}

// Handle the form submission
$bill_details = null;
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['view_bill'])) {
    $selected_room = $_POST['selected_room'];
    $selected_month = $_POST['selected_month'];
    $selected_year = $_POST['selected_year'];

    // Prepare and execute the SQL query to fetch the latest bill for the selected room
    $sql = "SELECT b.*, u.Room_number, u.First_name, u.Last_name, 
                   CASE 
                       WHEN u.Room_number IN ('201', '202', '302', '303', '304', '305', '306', '203', '204', '205', '206', '301') THEN u.water_was 
                       WHEN u.Room_number = 'S1' THEN b.water_cost 
                       ELSE b.water_cost 
                   END as water_cost_display
            FROM bill b
            LEFT JOIN users u ON b.user_id = u.id
            WHERE u.Room_number = ? AND b.month = ? AND b.year = ?
            ORDER BY b.id DESC LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $selected_room, $selected_month, $selected_year);
    $stmt->execute();
    $result = $stmt->get_result();
    $bill_details = $result->fetch_assoc();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ใบเสร็จรับเงิน</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { width: 80%; margin: auto; padding: 20px; background: #f4f4f4; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        form { margin-bottom: 20px; }
        label { margin-right: 10px; }
        select, button { padding: 5px; }
        .total { margin-top: 20px; font-weight: bold; }
        .print-button { margin-top: 20px; }
    </style>
</head>
<body>
<div class="container">
    <h1>เลือกข้อมูลสำหรับการออกใบเสร็จรับเงิน</h1>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <label for="selected_room">หมายเลขห้อง:</label>
        <select id="selected_room" name="selected_room" required>
            <?php foreach ($rooms as $room): ?>
                <option value="<?php echo $room; ?>" <?php echo $room == $selected_room ? 'selected' : ''; ?>><?php echo $room; ?></option>
            <?php endforeach; ?>
        </select>
        <label for="selected_month">เดือน:</label>
        <select id="selected_month" name="selected_month" required>
            <?php foreach ($months as $month): ?>
                <option value="<?php echo $month; ?>" <?php echo $month == $selected_month ? 'selected' : ''; ?>><?php echo $month; ?></option>
            <?php endforeach; ?>
        </select>
        <label for="selected_year">ปี:</label>
        <select id="selected_year" name="selected_year" required>
            <?php foreach ($years as $year): ?>
                <option value="<?php echo $year; ?>" <?php echo $year == $selected_year ? 'selected' : ''; ?>><?php echo $year; ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" name="view_bill">ดูใบเสร็จรับเงิน</button>
    </form>

    <?php if (!empty($bill_details)): ?>
        <h2>ใบเสร็จรับเงิน</h2>
        <table>
            <tr>
                <th>หมายเลขห้อง</th>
                <td><?php echo htmlspecialchars($bill_details['Room_number']); ?></td>
            </tr>
            <tr>
                <th>ชื่อ</th>
                <td><?php echo htmlspecialchars($bill_details['First_name']); ?> <?php echo htmlspecialchars($bill_details['Last_name']); ?></td>
            </tr>
            <tr>
                <th>เดือน</th>
                <td><?php echo htmlspecialchars($bill_details['month']); ?></td>
            </tr>
            <tr>
                <th>ปี</th>
                <td><?php echo htmlspecialchars($bill_details['year']); ?></td>
            </tr>
            <tr>
                <th>ค่าไฟฟ้า</th>
                <td><?php echo htmlspecialchars($bill_details['electric_cost']); ?> บาท</td>
            </tr>
            <tr>
                <th>ค่าน้ำ</th>
                <td><?php echo htmlspecialchars($bill_details['water_cost_display']); ?> บาท</td>
            </tr>
            <tr>
                <th>ค่าห้อง</th>
                <td><?php echo htmlspecialchars($bill_details['room_cost']); ?> บาท</td>
            </tr>
            <tr>
                <th>ค่าใช้จ่ายทั้งหมด</th>
                <td><?php echo htmlspecialchars($bill_details['total_cost']); ?> บาท</td>
            </tr>
        </table>
        <div class="total">
            <strong>ยอดรวม: <?php echo htmlspecialchars($bill_details['total_cost']); ?> บาท</strong>
        </div>
        <div class="print-button">
            <button onclick="window.print()">พิมพ์ใบเสร็จ</button>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
