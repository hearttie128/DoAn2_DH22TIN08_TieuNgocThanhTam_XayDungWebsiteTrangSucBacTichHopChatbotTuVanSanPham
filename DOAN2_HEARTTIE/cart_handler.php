<?php
session_start();
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "qlchdt");
mysqli_set_charset($conn, "utf8mb4");

// Kiểm tra đăng nhập
if (!isset($_SESSION['MA_ND'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng đăng nhập để thêm sản phẩm vào giỏ hàng!'
    ]);
    exit;
}

$ma_nd = (int)$_SESSION['MA_ND'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_to_cart') {
    $ma_sp = (int)$_POST['ma_sp'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    if ($ma_sp <= 0 || $quantity <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Thông tin sản phẩm không hợp lệ!'
        ]);
        exit;
    }
    
    // Lấy thông tin sản phẩm để tính giá khuyến mãi
    $stmt = $conn->prepare("SELECT GIA, GIAM_GIA FROM SANPHAM WHERE MA_SP = ?");
    $stmt->bind_param("i", $ma_sp);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    
    if (!$product) {
        echo json_encode([
            'success' => false,
            'message' => 'Sản phẩm không tồn tại!'
        ]);
        exit;
    }
    
    // Tính giá sau giảm (GIÁ KHUYẾN MÃI)
    $giaGoc = $product['GIA'];
    $giamGia = isset($product['GIAM_GIA']) ? $product['GIAM_GIA'] : 0;
    
    if ($giamGia > 0) {
        // Giá khuyến mãi = Giá gốc * (100 - % giảm) / 100
        $giaKhuyenMai = $giaGoc * (100 - $giamGia) / 100;
    } else {
        $giaKhuyenMai = $giaGoc;
    }
    
    // Kiểm tra xem sản phẩm đã có trong giỏ hàng chưa
    $check_stmt = $conn->prepare("SELECT SO_LUONG FROM GIOHANG WHERE MA_ND = ? AND MA_SP = ?");
    $check_stmt->bind_param("ii", $ma_nd, $ma_sp);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Sản phẩm đã có, cập nhật số lượng
        $update_stmt = $conn->prepare("UPDATE GIOHANG SET SO_LUONG = SO_LUONG + ? WHERE MA_ND = ? AND MA_SP = ?");
        $update_stmt->bind_param("iii", $quantity, $ma_nd, $ma_sp);
        
        if ($update_stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Đã cập nhật số lượng sản phẩm trong giỏ hàng!'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật giỏ hàng!'
            ]);
        }
    } else {
        // Thêm sản phẩm mới với GIÁ KHUYẾN MÃI
        $insert_stmt = $conn->prepare("INSERT INTO GIOHANG (MA_ND, MA_SP, SO_LUONG, GIA) VALUES (?, ?, ?, ?)");
        $insert_stmt->bind_param("iiid", $ma_nd, $ma_sp, $quantity, $giaKhuyenMai);
        
        if ($insert_stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Đã thêm sản phẩm vào giỏ hàng!'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi thêm vào giỏ hàng!'
            ]);
        }
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Yêu cầu không hợp lệ!'
    ]);
}

$conn->close();
?>