<?php
session_start();
$conn = new mysqli("localhost", "root", "", "qlchdt");
mysqli_set_charset($conn, "utf8mb4");

// Kiểm tra xem có đơn hàng vừa đặt không
if (!isset($_SESSION['order_id'])) {
    header("Location: index.php");
    exit;
}

$order_id = (int)$_SESSION['order_id'];

// Lấy thông tin đơn hàng
$sql = "SELECT * FROM DONHANG WHERE MA_DH = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header("Location: index.php");
    exit;
}

// Lấy chi tiết đơn hàng
$sql_details = "SELECT * FROM CHITIETDONHANG WHERE MA_DH = ?";
$stmt_details = $conn->prepare($sql_details);
$stmt_details->bind_param("i", $order_id);
$stmt_details->execute();
$details = $stmt_details->get_result();

// Xóa order_id khỏi session sau khi hiển thị
$success_message = $_SESSION['success'] ?? '';
unset($_SESSION['order_id']);
unset($_SESSION['success']);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt hàng thành công</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #cdd6ff 0%, #efdfff 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        
        .success-header {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        
        .success-icon {
            font-size: 80px;
            margin-bottom: 20px;
            animation: scaleIn 0.5s ease-out;
        }
        
        @keyframes scaleIn {
            from {
                transform: scale(0);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        .success-header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .order-info {
            padding: 40px;
        }
        
        .info-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .info-section h3 {
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #4a92e4;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: #666;
            font-weight: 500;
        }
        
        .info-value {
            color: #333;
            font-weight: 600;
        }
        
        .order-item {
            display: flex;
            align-items: center;
            padding: 15px;
            background: white;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        
        .item-info {
            flex: 1;
            margin-left: 15px;
        }
        
        .item-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .item-price {
            color: #e74c3c;
            font-weight: bold;
        }
        
        .total-section {
            background: #e8f4f8;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 18px;
        }
        
        .total-final {
            border-top: 2px solid #4a92e4;
            padding-top: 15px;
            margin-top: 10px;
            font-size: 24px;
            font-weight: bold;
            color: #4a92e4;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn {
            flex: 1;
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #4a92e4 0%, #3685d6 100%);
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .status-badge {
            display: inline-block;
            padding: 8px 15px;
            background: #fff3cd;
            color: #856404;
            border-radius: 20px;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
            }
            
            .success-header h1 {
                font-size: 24px;
            }
            
            .success-icon {
                font-size: 60px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-header">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1>Đặt hàng thành công!</h1>
            <p><?php echo htmlspecialchars($success_message); ?></p>
        </div>
        
        <div class="order-info">
            <div class="info-section">
                <h3><i class="fas fa-file-invoice"></i> Thông tin đơn hàng</h3>
                <div class="info-row">
                    <span class="info-label">Mã đơn hàng:</span>
                    <span class="info-value">#DH<?php echo str_pad($order['MA_DH'], 6, '0', STR_PAD_LEFT); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Ngày đặt:</span>
                    <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($order['NGAY_TAO'])); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Trạng thái:</span>
                    <span class="status-badge"><?php echo htmlspecialchars($order['TRANG_THAI']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Phương thức thanh toán:</span>
                    <span class="info-value"><?php echo htmlspecialchars($order['PTTT']); ?></span>
                </div>
            </div>
            
            <div class="info-section">
                <h3><i class="fas fa-map-marker-alt"></i> Thông tin giao hàng</h3>
                <div class="info-row">
                    <span class="info-label">Địa chỉ:</span>
                    <span class="info-value"><?php echo htmlspecialchars($order['DIA_CHI_GIAO']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Số điện thoại:</span>
                    <span class="info-value"><?php echo htmlspecialchars($order['SDT']); ?></span>
                </div>
                <?php if (!empty($order['GHI_CHU'])): ?>
                <div class="info-row">
                    <span class="info-label">Ghi chú:</span>
                    <span class="info-value"><?php echo htmlspecialchars($order['GHI_CHU']); ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="info-section">
                <h3><i class="fas fa-shopping-bag"></i> Chi tiết sản phẩm</h3>
                <?php while ($item = $details->fetch_assoc()): ?>
                <div class="order-item">
                    <div class="item-info">
                        <div class="item-name"><?php echo htmlspecialchars($item['TEN_SP']); ?></div>
                        <div>Số lượng: <?php echo $item['SO_LUONG']; ?> x <?php echo number_format($item['GIA'], 0, ",", "."); ?>đ</div>
                    </div>
                    <div class="item-price">
                        <?php echo number_format($item['THANH_TIEN'], 0, ",", "."); ?>đ
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            
            <div class="total-section">
                <div class="total-row">
                    <span>Tạm tính:</span>
                    <span><?php echo number_format($order['TONG_TIEN'], 0, ",", "."); ?>đ</span>
                </div>
                <div class="total-row">
                    <span>Phí vận chuyển:</span>
                    <span>Miễn phí</span>
                </div>
                <div class="total-row total-final">
                    <span>Tổng cộng:</span>
                    <span><?php echo number_format($order['TONG_TIEN'], 0, ",", "."); ?>đ</span>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="trangchu.php" class="btn btn-primary">
                    <i class="fas fa-home"></i> Về trang chủ
                </a>
                <a href="javascript:window.print()" class="btn btn-secondary">
                    <i class="fas fa-print"></i> In đơn hàng
                </a>
            </div>
        </div>
    </div>
    
    <script>
        // Tự động chuyển về trang chủ sau 30 giây
        setTimeout(function() {
            if (confirm('Bạn có muốn quay về trang chủ không?')) {
                window.location.href = 'trangchu.php';
            }
        }, 30000);
    </script>
</body>
</html>