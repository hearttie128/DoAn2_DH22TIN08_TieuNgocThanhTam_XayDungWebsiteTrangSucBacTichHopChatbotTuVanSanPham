<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['order_id'])) {
    echo json_encode(['success' => false, 'message' => 'Thiếu mã đơn hàng']);
    exit;
}

$order_id = (int)$data['order_id'];
$status   = 'Đã thanh toán';
$ngay_tt  = date('Y-m-d H:i:s');

// Kết nối database
$conn = new mysqli("localhost", "root", "", "qlchdt");
mysqli_set_charset($conn, "utf8mb4");

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối database']);
    exit;
}

// Kiểm tra đã có bản ghi trong bảng THANHTOAN chưa
$check = $conn->prepare("SELECT MA_DH FROM THANHTOAN WHERE MA_DH = ?");
$check->bind_param("i", $order_id);
$check->execute();
$exists = $check->get_result()->num_rows > 0;
$check->close();

if ($exists) {
    // Nếu đã có thì UPDATE
    $stmt = $conn->prepare("UPDATE THANHTOAN SET TRANG_THAI = ?, NGAY_TT = ? WHERE MA_DH = ?");
    $stmt->bind_param("ssi", $status, $ngay_tt, $order_id);
} else {
    // Nếu chưa có thì INSERT
    $stmt = $conn->prepare("INSERT INTO THANHTOAN (MA_DH, TRANG_THAI, NGAY_TT) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $order_id, $status, $ngay_tt);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Cập nhật thành công', 'order_id' => $order_id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>