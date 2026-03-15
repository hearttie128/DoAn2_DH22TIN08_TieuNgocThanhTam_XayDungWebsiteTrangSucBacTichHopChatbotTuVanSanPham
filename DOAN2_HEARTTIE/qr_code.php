<?php
session_start();

// Kiểm tra có thông tin đơn hàng không
if (!isset($_SESSION['order_id']) || !isset($_SESSION['total_amount'])) {
    header("Location: giohang.php");
    exit;
}

$order_id = $_SESSION['order_id'];
$total_amount = $_SESSION['total_amount'];
$cart_items = $_SESSION['cart_items'] ?? [];

// Thông tin tài khoản nhận tiền (cấu hình của shop)
$bank_id = "AGRIBANK"; // Mã ngân hàng 
$account_no = "7411794385228"; // Số tài khoản
$account_name = "TIEU NGOC THANH TAM"; // Tên chủ tài khoản
$template = "compact"; // Template QR

// Nội dung chuyển khoản
$content = "DH" . str_pad($order_id, 6, '0', STR_PAD_LEFT);

// Tạo URL QR code từ API VietQR
$qr_url = "https://img.vietqr.io/image/{$bank_id}-{$account_no}-{$template}.png?amount={$total_amount}&addInfo={$content}&accountName=" . urlencode($account_name);

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quét Mã QR Thanh Toán</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #fff;
            min-height: 100vh;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            max-width: 600px;
            width: 100%;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            text-align: center;
            color: white;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .content {
            padding: 30px;
            text-align: center;
        }
        
        .qr-section {
            background: #f9f9f9;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 25px;
        }
        
        .qr-code {
            background: white;
            padding: 20px;
            border-radius: 10px;
            display: inline-block;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .qr-code img {
            width: 300px;
            height: 300px;
            display: block;
        }
        
        .payment-info {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: left;
            margin-top: 20px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
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
        
        .total-amount {
            font-size: 24px;
            color: #667eea;
            font-weight: 700;
        }
        
        .order-details {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .order-details h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .product-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #ddd;
        }
        
        .product-item:last-child {
            border-bottom: none;
        }
        
        .product-name {
            flex: 1;
            color: #555;
        }
        
        .product-quantity {
            color: #666;
            margin: 0 15px;
        }
        
        .product-price {
            color: #667eea;
            font-weight: 600;
        }
        
        .instruction {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: left;
        }
        
        .instruction h4 {
            color: #856404;
            margin-bottom: 10px;
        }
        
        .instruction ol {
            margin-left: 20px;
            color: #856404;
        }
        
        .instruction li {
            margin-bottom: 8px;
        }
        
        .btn-group {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .btn {
            flex: 1;
            padding: 15px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(40, 167, 69, 0.4);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }
        
        .success-message {
            display: none;
            background: #d4edda;
            border: 2px solid #28a745;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .success-message.show {
            display: block;
            animation: slideDown 0.3s ease-out;
        }
        
        .success-message i {
            font-size: 50px;
            color: #28a745;
            margin-bottom: 15px;
        }
        
        .success-message h3 {
            color: #155724;
            margin-bottom: 10px;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @media (max-width: 640px) {
            .qr-code img {
                width: 250px;
                height: 250px;
            }
            
            .btn-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-qrcode"></i> Thanh Toán VietQR</h1>
            <p>Quét mã QR để hoàn tất thanh toán</p>
        </div>
        
        <div class="content">
            <!-- Thông báo thành công (ẩn ban đầu) -->
            <div id="success-message" class="success-message">
                <i class="fas fa-check-circle"></i>
                <h3>Thanh Toán Thành Công!</h3>
                <p>Đơn hàng của bạn đã được xác nhận.</p>
            </div>
            
            <!-- Hướng dẫn -->
            <div class="instruction">
                <h4><i class="fas fa-info-circle"></i> Hướng Dẫn Thanh Toán</h4>
                <ol>
                    <li>Mở ứng dụng Banking trên điện thoại</li>
                    <li>Quét mã QR bên dưới</li>
                    <li>Kiểm tra thông tin và xác nhận thanh toán</li>
                    <li>Nhấn nút "Đã Thanh Toán" sau khi hoàn tất</li>
                </ol>
            </div>
            
            <!-- Chi tiết đơn hàng -->
            <div class="order-details">
                <h3><i class="fas fa-shopping-cart"></i> Chi Tiết Đơn Hàng</h3>
                <?php foreach ($cart_items as $item): 
                    $item_total = $item['GIA'] * $item['SO_LUONG'];
                ?>
                <div class="product-item">
                    <span class="product-name"><?php echo htmlspecialchars($item['TEN_SP']); ?></span>
                    <span class="product-quantity">x<?php echo $item['SO_LUONG']; ?></span>
                    <span class="product-price"><?php echo number_format($item_total, 0, ",", "."); ?>đ</span>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- QR Code -->
            <div class="qr-section">
                <h3 style="margin-bottom: 20px; color: #333;">
                    <i class="fas fa-camera"></i> Quét Mã QR
                </h3>
                <div class="qr-code">
                    <img src="<?php echo $qr_url; ?>" alt="QR Code Thanh Toán">
                </div>
                
                <!-- Thông tin thanh toán -->
                <div class="payment-info">
                    <div class="info-row">
                        <span class="info-label">Ngân hàng:</span>
                        <span class="info-value">Agribank</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Số tài khoản:</span>
                        <span class="info-value"><?php echo $account_no; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Chủ tài khoản:</span>
                        <span class="info-value"><?php echo $account_name; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Nội dung:</span>
                        <span class="info-value"><?php echo $content; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Số tiền:</span>
                        <span class="total-amount"><?php echo number_format($total_amount, 0, ",", "."); ?>đ</span>
                    </div>
                </div>
            </div>
            
            <!-- Nút hành động -->
            <div class="btn-group">
                <button onclick="confirmPayment()" class="btn btn-success" id="confirmBtn">
                    <i class="fas fa-check"></i> Đã Thanh Toán
                </button>
                <a href="giohang.php" class="btn btn-primary">
                    <i class="fas fa-shopping-cart"></i> Quay Lại Giỏ Hàng
                </a>
            </div>
        </div>
    </div>
    
    <script>
        function confirmPayment() {
            // Hiện thông báo thành công
            document.getElementById('success-message').classList.add('show');
            
            // Ẩn QR code section
            document.querySelector('.qr-section').style.display = 'none';
            document.querySelector('.instruction').style.display = 'none';
            
            // Thay đổi nút
            const btnGroup = document.querySelector('.btn-group');
            btnGroup.innerHTML = `
                <a href="xacnhan.php?order_id=<?php echo $order_id; ?>" class="btn btn-success">
                    <i class="fas fa-eye"></i> Xem Chi Tiết Đơn Hàng
                </a>
                <a href="trangchu.php" class="btn btn-primary">
                    <i class="fas fa-home"></i> Về Trang Chủ
                </a>
            `;
            
            // Cập nhật trạng thái thanh toán trong database (có thể gọi AJAX)
            updatePaymentStatus();
        }
        
        function updatePaymentStatus() {
            // Gọi AJAX để cập nhật trạng thái thanh toán
            fetch('update_payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    order_id: <?php echo $order_id; ?>,
                    status: 'Đã thanh toán'
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log('Cập nhật thành công:', data);
            })
            .catch(error => {
                console.error('Lỗi:', error);
            });
        }
    </script>
</body>
</html>
<?php
// Xóa session cart_items sau khi hiển thị
unset($_SESSION['cart_items']);
?>