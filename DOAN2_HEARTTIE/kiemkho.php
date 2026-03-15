<?php
session_start();
header('Content-Type: application/json');

$host = "localhost";
$user = "root";
$pass = "";
$db = "qlchdt";
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối database']);
    exit;
}

mysqli_set_charset($conn, "utf8mb4");

// Nhận dữ liệu JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['cart']) || empty($input['cart'])) {
    echo json_encode(['success' => false, 'message' => 'Giỏ hàng trống!']);
    exit;
}

$cart = $input['cart'];
$errors = [];

// Kiểm tra tồn kho cho từng sản phẩm
foreach ($cart as $item) {
    $id = (int)$item['id'];
    $qty = (int)$item['quantity'];
    
    $sql = "SELECT TEN_SP, SO_LUONG FROM SANPHAM WHERE MA_SP = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if ($qty > $row['SO_LUONG']) {
            $errors[] = "Sản phẩm {$row['TEN_SP']} chỉ còn {$row['SO_LUONG']} cái.";
        }
    } else {
        $errors[] = "Không tìm thấy sản phẩm với ID: $id";
    }
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode("\n", $errors)]);
} else {
    echo json_encode(['success' => true, 'message' => 'Kiểm tra kho thành công!']);
}

$conn->close();
?>