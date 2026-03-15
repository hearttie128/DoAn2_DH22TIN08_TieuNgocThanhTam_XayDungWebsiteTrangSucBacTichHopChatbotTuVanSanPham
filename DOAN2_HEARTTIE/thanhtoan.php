<?php
session_start();

$conn = new mysqli("localhost", "root", "", "qlchdt");
mysqli_set_charset($conn, "utf8mb4");

// Kiểm tra kết nối database
if ($conn->connect_error) {
    die("Lỗi kết nối database: " . $conn->connect_error);
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['MA_ND'])) {
    header("Location: dangnhap_user.php?redirect=thanhtoan.php");
    exit;
}

$ma_nd = (int)$_SESSION['MA_ND'];

// ===== XỬ LÝ "MUA NGAY" =====
// Kiểm tra xem có phải mua ngay không (từ trang chi tiết sản phẩm)
$is_buy_now = isset($_POST['buy_now']) && $_POST['buy_now'] == '1';

if ($is_buy_now && isset($_POST['ma_sp']) && isset($_POST['quantity']) && isset($_POST['gia'])) {
    // Lấy thông tin sản phẩm từ POST
    $ma_sp = (int)$_POST['ma_sp'];
    $quantity = (int)$_POST['quantity'];
    $gia = (float)$_POST['gia']; // Giá khuyến mãi đã tính từ trang chi tiết
    
    // Lấy thông tin sản phẩm từ database
    $stmt = $conn->prepare("SELECT TEN_SP, HINH_ANH FROM SANPHAM WHERE MA_SP = ?");
    $stmt->bind_param("i", $ma_sp);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        
        // Tạo mảng giỏ hàng tạm thời cho "Mua ngay"
        $cart_items = [[
            'MA_SP' => $ma_sp,
            'TEN_SP' => $product['TEN_SP'],
            'HINH_ANH' => $product['HINH_ANH'],
            'GIA' => $gia,  // Giá khuyến mãi
            'SO_LUONG' => $quantity
        ]];
        $total_amount = $gia * $quantity;
    } else {
        $_SESSION['cart_error'] = "Sản phẩm không tồn tại!";
        header("Location: trangchu.php");
        exit;
    }
} else {
    // ===== LẤY GIỎ HÀNG TỪ DATABASE (THANH TOÁN BÌNH THƯỜNG) =====
    // Lấy giỏ hàng từ database
    $sql = "
    SELECT g.MA_SP, s.TEN_SP, s.HINH_ANH, g.GIA, g.SO_LUONG
    FROM GIOHANG g
    JOIN SANPHAM s ON g.MA_SP = s.MA_SP
    WHERE g.MA_ND = ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $ma_nd);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Kiểm tra giỏ hàng có sản phẩm không
    if ($result->num_rows == 0) {
        $_SESSION['cart_error'] = "Giỏ hàng trống! Vui lòng thêm sản phẩm trước khi thanh toán.";
        header("Location: giohang.php");
        exit;
    }
    
    // Lưu giỏ hàng vào mảng
    $cart_items = [];
    $total_amount = 0;
    while ($row = $result->fetch_assoc()) {
        $cart_items[] = $row;
        $total_amount += $row['GIA'] * $row['SO_LUONG'];
    }
}

// Xử lý khi submit form thanh toán
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_order'])) {
    
    $customer_name = trim($_POST['customer_name']);
    $customer_phone = trim($_POST['customer_phone']);
    $customer_address = trim($_POST['customer_address']);
    $payment_method = $_POST['payment_method'];
    $note = trim($_POST['note']);
    
    // Validate dữ liệu
    $errors = [];
    if (empty($customer_name)) $errors[] = "Vui lòng nhập họ tên";
    if (empty($customer_phone)) $errors[] = "Vui lòng nhập số điện thoại";
    if (empty($customer_address)) $errors[] = "Vui lòng nhập địa chỉ";
    if (strlen($customer_address) < 10) $errors[] = "Địa chỉ phải có ít nhất 10 ký tự";
    

    if (empty($errors)) {
        
        // Kiểm tra tồn kho
        $stock_errors = [];
        foreach ($cart_items as $item) {
            $ma_sp = $item['MA_SP'];
            $qty = $item['SO_LUONG'];
            
            $sql = "SELECT TEN_SP, SO_LUONG FROM SANPHAM WHERE MA_SP = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $ma_sp);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                if ($qty > $row['SO_LUONG']) {
                    $stock_errors[] = "Sản phẩm {$row['TEN_SP']} chỉ còn {$row['SO_LUONG']} cái.";
                }
            }
        }
        
        if (!empty($stock_errors)) {
            $_SESSION['error'] = implode("<br>", $stock_errors);
        } else {
            // Bắt đầu transaction
            $conn->begin_transaction();
            
            try {
                // Lưu đơn hàng vào database
                $sql_order = "INSERT INTO DONHANG (MA_ND, TONG_TIEN, TRANG_THAI, PTTT, DIA_CHI_GIAO, SDT, GHI_CHU, NGAY_TAO, NGAY_CAP_NHAT) 
                             VALUES (?, ?, 'Chờ xử lý', ?, ?, ?, ?, NOW(), NOW())";
                $stmt = $conn->prepare($sql_order);
                $stmt->bind_param("idssss", $ma_nd, $total_amount, $payment_method, $customer_address, $customer_phone, $note);
                
                if (!$stmt->execute()) {
                    throw new Exception("Lỗi khi tạo đơn hàng: " . $stmt->error);
                }
                
                $order_id = $conn->insert_id;

                // ===== LƯU THÔNG TIN THANH TOÁN =====
                if ($payment_method === 'VietQR') {
                    // Lưu thông tin thanh toán VietQR
                    $sql_tt = "INSERT INTO THANHTOAN (MA_DH, PTTT, SO_TIEN, NGAY_TT, TRANG_THAI)
                            VALUES (?, ?, ?, NOW(), 'Chờ thanh toán')";
                    $stmt_tt = $conn->prepare($sql_tt);
                    $stmt_tt->bind_param("isd", $order_id, $payment_method, $total_amount);

                    if (!$stmt_tt->execute()) {
                        throw new Exception("Lỗi khi tạo thanh toán: " . $stmt_tt->error);
                    }
                } else {
                    // COD hoặc phương thức khác
                    $sql_tt = "INSERT INTO THANHTOAN (MA_DH, PTTT, SO_TIEN, NGAY_TT, TRANG_THAI)
                            VALUES (?, ?, ?, NOW(), 'Chưa thanh toán')";
                    $stmt_tt = $conn->prepare($sql_tt);
                    $stmt_tt->bind_param("isd", $order_id, $payment_method, $total_amount);

                    if (!$stmt_tt->execute()) {
                        throw new Exception("Lỗi khi tạo thanh toán: " . $stmt_tt->error);
                    }
                }
                
                // Lưu chi tiết đơn hàng và trừ kho
                foreach ($cart_items as $item) {
                    $ma_sp = $item['MA_SP'];
                    $ten_sp = $item['TEN_SP'];
                    $qty = $item['SO_LUONG'];
                    $price = $item['GIA']; // ⭐ Đây là GIÁ KHUYẾN MÃI (đã giảm giá)
                    $item_total = $price * $qty;
                    
                    // Lưu chi tiết đơn hàng (với giá khuyến mãi)
                    $sql_detail = "INSERT INTO CHITIETDONHANG (MA_DH, MA_SP, TEN_SP, SO_LUONG, GIA, THANH_TIEN) 
                                   VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt_detail = $conn->prepare($sql_detail);
                    $stmt_detail->bind_param("iisiii", $order_id, $ma_sp, $ten_sp, $qty, $price, $item_total);
                    
                    if (!$stmt_detail->execute()) {
                        throw new Exception("Lỗi khi lưu chi tiết đơn hàng: " . $stmt_detail->error);
                    }
                    
                    // Trừ kho
                    $sql_update = "UPDATE SANPHAM SET SO_LUONG = SO_LUONG - ? WHERE MA_SP = ?";
                    $stmt_update = $conn->prepare($sql_update);
                    $stmt_update->bind_param("ii", $qty, $ma_sp);
                    
                    if (!$stmt_update->execute()) {
                        throw new Exception("Lỗi khi cập nhật kho: " . $stmt_update->error);
                    }
                }
                
                // Xóa giỏ hàng sau khi đặt hàng thành công
                // Chỉ xóa giỏ hàng khi KHÔNG PHẢI "Mua ngay"
                if (!$is_buy_now) {
                    $sql_clear = "DELETE FROM GIOHANG WHERE MA_ND = ?";
                    $stmt_clear = $conn->prepare($sql_clear);
                    $stmt_clear->bind_param("i", $ma_nd);
                    
                    if (!$stmt_clear->execute()) {
                        throw new Exception("Lỗi khi xóa giỏ hàng: " . $stmt_clear->error);
                    }
                }
                
                // Commit transaction
                $conn->commit();
                
                $_SESSION['order_id'] = $order_id;
                $_SESSION['total_amount'] = $total_amount;
                $_SESSION['cart_items'] = $cart_items;

                // Chuyển hướng dựa vào phương thức thanh toán
                if ($payment_method === 'VietQR') {
                    header("Location: qr_code.php");
                    exit;
                } else {
                    $_SESSION['success'] = "Đặt hàng thành công! Mã đơn hàng: DH" . str_pad($order_id, 6, '0', STR_PAD_LEFT);
                    header("Location: xacnhan.php");
                    exit;
                }
                
            } catch (Exception $e) {
                // Rollback nếu có lỗi
                $conn->rollback();
                $_SESSION['error'] = "Có lỗi xảy ra: " . $e->getMessage();
            }
        }
    } else {
        $_SESSION['error'] = implode("<br>", $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh Toán Đơn Hàng</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #ffffff;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #4A92E4 0%, #4A92E4 100%);
            padding: 30px;
            text-align: center;
            color: white;
        }
        
        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            background: #4A92E4;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
            margin-bottom: 20px;
        }
        
        .back-btn:hover {
            background: #4A92E4;
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background: #fee;
            border-left: 4px solid #e00;
            color: #c00;
        }
        
        .content {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
            padding: 30px;
        }
        
        .form-section, .order-summary {
            background: #f9f9f9;
            padding: 25px;
            border-radius: 15px;
        }
        
        .form-section h2, .order-summary h2 {
            margin-bottom: 20px;
            color: #333;
            font-size: 22px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        
        .form-group input[type="text"],
        .form-group input[type="tel"],
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #4A92E4;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .payment-methods {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 10px;
        }
        
        .payment-option {
            position: relative;
        }
        
        .payment-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }
        
        .payment-option label {
            display: block;
            padding: 20px;
            border: 2px solid #ddd;
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
        }
        
        .payment-option input[type="radio"]:checked + label {
            border-color: #4A92E4;
            background: #f0f3ff;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .payment-option label i {
            font-size: 30px;
            color: #4A92E4;
            margin-bottom: 8px;
        }
        

        .cart-item {
            display: flex;
            gap: 15px;
            padding: 15px;
            background: white;
            border-radius: 10px;
            margin-bottom: 15px;
            align-items: center;
        }
        
        .cart-item img {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .item-info {
            flex: 1;
        }
        
        .item-info h4 {
            color: #333;
            margin-bottom: 5px;
        }
        
        .item-price {
            color: #4A92E4;
            font-weight: 600;
        }
        
        .total-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #ddd;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 16px;
        }
        
        .total-final {
            font-size: 20px;
            font-weight: 700;
            color: #4A92E4;
            border-top: 2px solid #4A92E4;
            padding-top: 15px;
            margin-top: 10px;
        }
        
        .submit-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #4A92E4, #4A92E4);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
            transition: all 0.3s;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }
        
        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        @media (max-width: 968px) {
            .content {
                grid-template-columns: 1fr;
            }
            
            .payment-methods {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Thanh Toán Đơn hàng</h1>
            <p>Vui lòng điền đầy đủ thông tin để hoàn tất đơn hàng</p>
        </div>
        
        <div style="padding: 20px;">
            <a href="giohang.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Quay lại giỏ hàng
            </a>
            
            <?php if(isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
        </div>
        
        <form method="POST" id="checkout-form">
            <div class="content">
                <!-- Form thông tin khách hàng -->
                <div class="form-section">
                    <h2><i class="fas fa-user"></i> Thông Tin Khách Hàng</h2>
                    
                    <div class="form-group">
                        <label for="customer_name">Họ và Tên *</label>
                        <input type="text" id="customer_name" name="customer_name" required 
                               value="<?php echo isset($_POST['customer_name']) ? htmlspecialchars($_POST['customer_name']) : ''; ?>"
                               placeholder="Nguyễn Văn A">
                    </div>
                    
                    <div class="form-group">
                        <label for="customer_phone">Số Điện Thoại *</label>
                        <input type="tel" id="customer_phone" name="customer_phone" required
                               value="<?php echo isset($_POST['customer_phone']) ? htmlspecialchars($_POST['customer_phone']) : ''; ?>"
                               placeholder="0123456789">
                    </div>
                    
                    <div class="form-group">
                        <label for="customer_address">Địa Chỉ Giao Hàng * <span style="color: #666; font-size: 12px; font-weight: normal;">(Tối thiểu 10 ký tự)</span></label>
                        <textarea id="customer_address" name="customer_address" rows="3" required 
                                  placeholder="Ví dụ: 123 Lê Lợi, Phường Bến Thành, Quận 1, TP.HCM" 
                                  minlength="10"><?php echo isset($_POST['customer_address']) ? htmlspecialchars($_POST['customer_address']) : ''; ?></textarea>
                        <small style="color: #666; font-size: 12px; display: block; margin-top: 5px;">
                            <i class="fas fa-info-circle"></i> Vui lòng nhập địa chỉ chi tiết để đảm bảo giao hàng chính xác
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label>Phương Thức Thanh Toán *</label>
                        <div class="payment-methods">
                            <div class="payment-option">
                                <input type="radio" id="cod" name="payment_method" value="Thanh toán khi nhận hàng" checked>
                                <label for="cod">
                                    <i class="fas fa-money-bill-wave"></i><br>
                                    Thanh toán khi nhận hàng
                                </label>
                            </div>
                            <div class="payment-option">
                                <input type="radio" id="vietqr" name="payment_method" value="VietQR">
                                <label for="vietqr">
                                    <i class="fas fa-qrcode"></i><br>
                                    VietQR
                                </label>
                            </div>

                        </div>
                    </div>
                    

                    <div class="form-group">
                        <label for="note">Ghi Chú (Tùy chọn)</label>
                        <textarea id="note" name="note" rows="3" placeholder="Ghi chú thêm cho đơn hàng..."><?php echo isset($_POST['note']) ? htmlspecialchars($_POST['note']) : ''; ?></textarea>
                    </div>
                </div>
                
                <!-- Tóm tắt đơn hàng -->
                <div class="order-summary">
                    <h2>Tóm Tắt Đơn Hàng</h2>
                    <div id="order-items">
                        <?php foreach ($cart_items as $item): 
                            $item_total = $item['GIA'] * $item['SO_LUONG'];
                        ?>
                        <div class="cart-item">
                            <img src="<?php echo htmlspecialchars($item['HINH_ANH']); ?>" alt="<?php echo htmlspecialchars($item['TEN_SP']); ?>">
                            <div class="item-info">
                                <h4><?php echo htmlspecialchars($item['TEN_SP']); ?></h4>
                                <p>Số lượng: <?php echo $item['SO_LUONG']; ?></p>
                                <p class="item-price"><?php echo number_format($item['GIA'], 0, ",", "."); ?>đ</p>
                            </div>
                            <div>
                                <strong><?php echo number_format($item_total, 0, ",", "."); ?>đ</strong>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="total-section">
                        <div class="total-row">
                            <span>Tạm tính:</span>
                            <span><?php echo number_format($total_amount, 0, ",", "."); ?>đ</span>
                        </div>
                        <div class="total-row">
                            <span>Phí vận chuyển:</span>
                            <span>Miễn phí</span>
                        </div>
                        <div class="total-row total-final">
                            <span>Tổng cộng:</span>
                            <span><?php echo number_format($total_amount, 0, ",", "."); ?>đ</span>
                        </div>
                    </div>
                    
                    <!-- Hidden field để đảm bảo form được xử lý -->
                    <input type="hidden" name="submit_order" value="1">
                    
                    <button type="submit" class="submit-btn" id="submitBtn">
                        <i class="fas fa-check-circle"></i> Đặt Hàng
                    </button>
                </div>
            </div>
        </form>
    </div>
    
    <script>
        
        // Form validation
        document.getElementById('checkout-form').addEventListener('submit', function(e) {
            let name = document.getElementById('customer_name').value.trim();
            let phone = document.getElementById('customer_phone').value.trim();
            let address = document.getElementById('customer_address').value.trim();
            
            if (!name || !phone || !address) {
                e.preventDefault();
                alert('Vui lòng điền đầy đủ thông tin bắt buộc!');
                return false;
            }
            
            // Validate địa chỉ phải trên 10 ký tự
            if (address.length < 10) {
                e.preventDefault();
                alert('Địa chỉ giao hàng phải có ít nhất 10 ký tự!\nVí dụ: 123 Lê Lợi, Quận 1, TP.HCM');
                return false;
            }
            
            // Validate phone number
            let phonePattern = /^[0-9]{10,11}$/;
            if (!phonePattern.test(phone)) {
                e.preventDefault();
                alert('Số điện thoại không hợp lệ! Vui lòng nhập 10-11 số.');
                return false;
            }
            
            
            // Đợi một chút trước khi disable button
            setTimeout(function() {
                const submitBtn = document.getElementById('submitBtn');
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
                submitBtn.disabled = true;
            }, 100);
            
            return true;
        });
    </script>
</body>
</html>