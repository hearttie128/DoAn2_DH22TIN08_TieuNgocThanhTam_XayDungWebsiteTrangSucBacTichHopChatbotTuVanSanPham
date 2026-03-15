<?php
session_start();

// Kết nối database
$conn = new mysqli("localhost", "root", "", "qlchdt");
mysqli_set_charset($conn, "utf8mb4");

if ($conn->connect_error) {
    echo "<div class='alert alert-danger'>Lỗi kết nối database!</div>";
    exit();
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($order_id <= 0) {
    echo "<div class='alert alert-danger'>Mã đơn hàng không hợp lệ!</div>";
    exit();
}

// Lấy thông tin đơn hàng
$sql = "SELECT d.*, n.TEN_DN, n.TEN_DAY_DU, n.EMAIL, n.SDT as USER_SDT,
               t.TRANG_THAI as TRANG_THAI_TT, t.MA_GIAO_DICH, t.NGAY_TT
        FROM DONHANG d
        LEFT JOIN NGUOIDUNG n ON d.MA_ND = n.MA_ND
        LEFT JOIN THANHTOAN t ON d.MA_DH = t.MA_DH
        WHERE d.MA_DH = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    echo "<div class='alert alert-danger'>Không tìm thấy đơn hàng!</div>";
    exit();
}

// Lấy chi tiết sản phẩm
$sql_details = "SELECT * FROM CHITIETDONHANG WHERE MA_DH = ?";
$stmt_details = $conn->prepare($sql_details);
$stmt_details->bind_param("i", $order_id);
$stmt_details->execute();
$details = $stmt_details->get_result();
?>

<style>
    .order-info {
        margin-bottom: 20px;
    }
    .info-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    .info-label {
        font-weight: 600;
        color: #666;
    }
    .info-value {
        color: #333;
    }
    .product-table {
        width: 100%;
        margin-top: 20px;
    }
    .product-table th {
        background-color: #f8f9fa;
        padding: 12px;
        text-align: left;
        border-bottom: 2px solid #dee2e6;
    }
    .product-table td {
        padding: 12px;
        border-bottom: 1px solid #dee2e6;
    }
    .total-row {
        font-weight: bold;
        font-size: 18px;
        color: #e74c3c;
    }
    .payment-status {
        display: inline-block;
        padding: 8px 15px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
    }
    .payment-paid {
        background-color: #d4edda;
        color: #155724;
    }
    .payment-pending {
        background-color: #fff3cd;
        color: #856404;
    }
    .transaction-box {
        background: #e3f2fd;
        padding: 15px;
        border-radius: 8px;
        margin: 15px 0;
        border-left: 4px solid #2196F3;
    }
    .transaction-code {
        font-family: 'Courier New', monospace;
        font-weight: bold;
        color: #1976D2;
        font-size: 16px;
    }
</style>

<div class="order-info">
    <h5><i class="fas fa-info-circle"></i> Thông tin đơn hàng #DH<?php echo str_pad($order['MA_DH'], 6, '0', STR_PAD_LEFT); ?></h5>
    
    <div class="info-row">
        <span class="info-label">Khách hàng:</span>
        <span class="info-value"><?php echo htmlspecialchars($order['TEN_DAY_DU'] ?: $order['TEN_DN'] ?: 'Khách'); ?></span>
    </div>
    
    <div class="info-row">
        <span class="info-label">Số điện thoại:</span>
        <span class="info-value"><?php echo htmlspecialchars($order['SDT']); ?></span>
    </div>
    
    <div class="info-row">
        <span class="info-label">Email:</span>
        <span class="info-value"><?php echo htmlspecialchars($order['EMAIL'] ?: 'Chưa có'); ?></span>
    </div>
    
    <div class="info-row">
        <span class="info-label">Địa chỉ giao hàng:</span>
        <span class="info-value"><?php echo htmlspecialchars($order['DIA_CHI_GIAO']); ?></span>
    </div>
    
    <div class="info-row">
        <span class="info-label">Phương thức thanh toán:</span>
        <span class="info-value"><strong><?php echo htmlspecialchars($order['PTTT']); ?></strong></span>
    </div>
    
    <div class="info-row">
        <span class="info-label">Trạng thái thanh toán:</span>
        <span class="info-value">
            <?php if ($order['TRANG_THAI_TT'] === 'Đã thanh toán'): ?>
                <span class="payment-status payment-paid">
                    <i class="fas fa-check-circle"></i> Đã thanh toán
                </span>
            <?php else: ?>
                <span class="payment-status payment-pending">
                    <i class="fas fa-clock"></i> <?php echo htmlspecialchars($order['TRANG_THAI_TT']); ?>
                </span>
            <?php endif; ?>
        </span>
    </div>
    
    <?php if ($order['PTTT'] === 'VietQR' && !empty($order['MA_GIAO_DICH'])): ?>
        <div class="transaction-box">
            <div style="margin-bottom: 8px;">
                <i class="fas fa-qrcode"></i> <strong>Thông tin giao dịch VietQR:</strong>
            </div>
            <div>
                <span style="color: #666;">Mã giao dịch:</span>
                <span class="transaction-code"><?php echo htmlspecialchars($order['MA_GIAO_DICH']); ?></span>
            </div>
            <div style="margin-top: 5px; font-size: 13px; color: #666;">
                <i class="fas fa-calendar-alt"></i> 
                Thanh toán lúc: <?php echo date('d/m/Y H:i:s', strtotime($order['NGAY_TT'])); ?>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="info-row">
        <span class="info-label">Ghi chú:</span>
        <span class="info-value"><?php echo htmlspecialchars($order['GHI_CHU'] ?: 'Không có'); ?></span>
    </div>
    
    <div class="info-row">
        <span class="info-label">Ngày đặt:</span>
        <span class="info-value"><?php echo date('d/m/Y H:i:s', strtotime($order['NGAY_TAO'])); ?></span>
    </div>
    
    <div class="info-row">
        <span class="info-label">Trạng thái đơn hàng:</span>
        <span class="info-value"><strong><?php echo htmlspecialchars($order['TRANG_THAI']); ?></strong></span>
    </div>
</div>

<h5><i class="fas fa-box"></i> Chi tiết sản phẩm</h5>
<table class="product-table">
    <thead>
        <tr>
            <th>Sản phẩm</th>
            <th style="text-align: center;">Số lượng</th>
            <th style="text-align: right;">Đơn giá</th>
            <th style="text-align: right;">Thành tiền</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($item = $details->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['TEN_SP']); ?></td>
                <td style="text-align: center;"><?php echo $item['SO_LUONG']; ?></td>
                <td style="text-align: right;"><?php echo number_format($item['GIA'], 0, ',', '.'); ?>đ</td>
                <td style="text-align: right;"><?php echo number_format($item['THANH_TIEN'], 0, ',', '.'); ?>đ</td>
            </tr>
        <?php endwhile; ?>
        <tr class="total-row">
            <td colspan="3" style="text-align: right;">Tổng cộng:</td>
            <td style="text-align: right;"><?php echo number_format($order['TONG_TIEN'], 0, ',', '.'); ?>đ</td>
        </tr>
    </tbody>
</table>

<?php
$stmt->close();
$stmt_details->close();
$conn->close();
?>