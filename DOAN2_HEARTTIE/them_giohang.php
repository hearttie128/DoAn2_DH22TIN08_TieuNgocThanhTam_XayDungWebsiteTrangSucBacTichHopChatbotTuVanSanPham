<?php
session_start();
$conn = new mysqli("localhost", "root", "", "qlchdt");
mysqli_set_charset($conn, "utf8mb4");

// Kiểm tra đăng nhập
if (!isset($_SESSION['MA_ND'])) {
    $redirect = !empty($_GET['back']) ? $_GET['back'] : "index.php";
    header("Location: dangnhap_user.php?redirect=" . urlencode($redirect));
    exit;
}

// Kiểm tra mã sản phẩm
if (!isset($_GET['ma_sp']) || !is_numeric($_GET['ma_sp'])) {
    die("Thiếu mã sản phẩm!");
}

$ma_nd = (int)$_SESSION['MA_ND'];
$ma_sp = (int)$_GET['ma_sp'];

// Lấy thông tin sản phẩm
$stmt = $conn->prepare("SELECT TEN_SP, GIA FROM SANPHAM WHERE MA_SP = ?");
$stmt->bind_param("i", $ma_sp);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    die("Sản phẩm không tồn tại!");
}

// Kiểm tra xem sản phẩm đã có trong giỏ hàng chưa
$stmt = $conn->prepare("SELECT g.SO_LUONG FROM GIOHANG g WHERE g.MA_ND = ? AND g.MA_SP = ?");
$stmt->bind_param("ii", $ma_nd, $ma_sp);
$stmt->execute();
$cart_result = $stmt->get_result();

if ($cart_result->num_rows > 0) {
    // Sản phẩm đã có, tăng số lượng
    $sql = "UPDATE GIOHANG g SET g.SO_LUONG = g.SO_LUONG + 1 WHERE g.MA_ND = ? AND g.MA_SP = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $ma_nd, $ma_sp);
    $stmt->execute();
} else {
    // Sản phẩm chưa có, thêm mới
    $sql = "INSERT INTO GIOHANG (MA_ND, MA_SP, GIA, SO_LUONG) VALUES (?, ?, ?, 1)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iid", $ma_nd, $ma_sp, $product['GIA']);
    $stmt->execute();
}

// Lưu thông báo vào session
$_SESSION['cart_success'] = "✅ Đã thêm sản phẩm vào giỏ hàng!";

// Xử lý redirect
$type = isset($_GET['type']) ? $_GET['type'] : 'add';

if ($type === 'buy') {
    // Mua ngay → chuyển sang giỏ hàng
    header("Location: giohang.php");
} else {
    // Thêm giỏ hàng → quay lại trang chi tiết (không cần ma_sp)
    header("Location: chitietsanpham{$ma_sp}.php");
}
exit;
?>