<?php
session_start();

// Kiểm tra đăng nhập khách hàng
if (!isset($_SESSION['MA_ND'])) {
    header("Location: dangnhap_user.php");
    exit();
}

// Kết nối database
$conn = new mysqli("localhost", "root", "", "qlchdt");
mysqli_set_charset($conn, "utf8mb4");

if ($conn->connect_error) {
    die("Lỗi kết nối: " . $conn->connect_error);
}

$ma_nd = $_SESSION['MA_ND'];

// Xử lý hủy đơn hàng
if (isset($_POST['cancel_order'])) {
    $cancel_id     = (int)$_POST['cancel_id'];
    $cancel_reason = trim($_POST['cancel_reason'] ?? '');

    // Chỉ cho hủy đơn thuộc về khách hàng này và đang "Chờ xử lý"
    $check_sql  = "SELECT MA_DH, TRANG_THAI FROM DONHANG WHERE MA_DH = ? AND MA_ND = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $cancel_id, $ma_nd);
    $check_stmt->execute();
    $check_row = $check_stmt->get_result()->fetch_assoc();
    $check_stmt->close();

    if ($check_row && in_array($check_row['TRANG_THAI'], ['Chờ xử lý', 'Đang xử lý'])) {
        $note = 'Đã hủy' . ($cancel_reason ? ' — Lý do: ' . $cancel_reason : '');
        $upd_sql  = "UPDATE DONHANG SET TRANG_THAI = 'Đã hủy', GHI_CHU = ? WHERE MA_DH = ? AND MA_ND = ?";
        $upd_stmt = $conn->prepare($upd_sql);
        $upd_stmt->bind_param("sii", $note, $cancel_id, $ma_nd);
        if ($upd_stmt->execute()) {
            $success_message = "Đơn hàng <b>#DH" . str_pad($cancel_id, 6, '0', STR_PAD_LEFT) . "</b> đã được hủy thành công.";
        } else {
            $error_message = "Có lỗi xảy ra, vui lòng thử lại!";
        }
        $upd_stmt->close();
    } else {
        $error_message = "Không thể hủy đơn này. Chỉ hủy được đơn đang <b>Chờ xử lý</b> hoặc <b>Đang xử lý</b>.";
    }
}

// Lấy thông tin khách hàng
$sql_user = "SELECT TEN_DAY_DU, EMAIL, SDT FROM NGUOIDUNG WHERE MA_ND = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $ma_nd);
$stmt_user->execute();
$user_info = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

// Lấy danh sách đơn hàng của khách hàng
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$sql = "SELECT d.* FROM DONHANG d WHERE d.MA_ND = ?";

$params = [$ma_nd];
$types = "i";

if (!empty($search)) {
    $sql .= " AND (d.MA_DH LIKE ? OR d.SDT LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param]);
    $types .= "ss";
}

if (!empty($status_filter)) {
    $sql .= " AND d.TRANG_THAI = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$sql .= " ORDER BY d.NGAY_TAO DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Đếm số đơn hàng theo trạng thái
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN TRANG_THAI = 'Chờ xử lý' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN TRANG_THAI = 'Đang xử lý' THEN 1 ELSE 0 END) as processing,
    SUM(CASE WHEN TRANG_THAI = 'Đang giao hàng' THEN 1 ELSE 0 END) as shipping,
    SUM(CASE WHEN TRANG_THAI = 'Hoàn thành' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN TRANG_THAI = 'Đã hủy' THEN 1 ELSE 0 END) as cancelled
FROM DONHANG WHERE MA_ND = ?";
$stmt_stats = $conn->prepare($stats_sql);
$stmt_stats->bind_param("i", $ma_nd);
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();
$stmt_stats->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tra Cứu Đơn Hàng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        
        .customer-header {
            background: #4a92e4;
            color: #fff;
            padding: 30px;
            margin-bottom: 30px;
            border-radius: 0 0 15px 15px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        
        .customer-header h2 {
            text-align: center;
            margin: 0 0 10px 0;
            font-size: 32px;
            font-weight: bold;
        }

        .customer-info {
            text-align: center;
            font-size: 16px;
            opacity: 0.85;
        }
        
        .table-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table th {
            background-color: #4a92e4;
            color: #fff;
            border: none;
            font-weight: 600;
            padding: 15px;
        }
        
        .table td {
            padding: 15px;
            vertical-align: middle;
        }
        
        .status-badge {
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-processing {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .status-shipping {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .btn-action {
            padding: 6px 12px;
            font-size: 13px;
            margin: 2px;
        }
        
        .stats-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .stats-number {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stats-label {
            font-size: 14px;
            color: #666;
        }

        .stats-card.total { border-top: 4px solid #afdbf3; }
        .stats-card.total .stats-number { color: #5cb3d1; }
        
        .stats-card.pending { border-top: 4px solid #ffc107; }
        .stats-card.pending .stats-number { color: #ffc107; }
        
        .stats-card.processing { border-top: 4px solid #0dcaf0; }
        .stats-card.processing .stats-number { color: #0dcaf0; }
        
        .stats-card.shipping { border-top: 4px solid #f7971e; }
        .stats-card.shipping .stats-number { color: #f7971e; }
        
        .stats-card.completed { border-top: 4px solid #198754; }
        .stats-card.completed .stats-number { color: #198754; }
        
        .stats-card.cancelled { border-top: 4px solid #dc3545; }
        .stats-card.cancelled .stats-number { color: #dc3545; }

        .cancel-reason-select {
            border: 2px solid #dc3545;
            border-radius: 8px;
        }
        .cancel-reason-select:focus {
            box-shadow: 0 0 0 0.2rem rgba(220,53,69,.25);
            border-color: #dc3545;
        }

        .back-btn {
            margin-bottom: 20px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state i {
            font-size: 80px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <div class="customer-header">
        <div class="container-fluid">
            <h2><i class="fas fa-box"></i> ĐƠN HÀNG CỦA TÔI</h2>
            <div class="customer-info">
                <i class="fas fa-user"></i> <?php echo htmlspecialchars($user_info['TEN_DAY_DU']); ?>
                <?php if (!empty($user_info['EMAIL'])): ?>
                    | <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user_info['EMAIL']); ?>
                <?php endif; ?>
                <?php if (!empty($user_info['SDT'])): ?>
                    | <i class="fas fa-phone"></i> <?php echo htmlspecialchars($user_info['SDT']); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <!-- Nút quay lại -->
        <div class="back-btn">
            <a href="trangchu.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left"></i> Quay Lại Trang Chủ
            </a>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Thống kê -->
        <div class="row flex-nowrap mb-4 g-3">
            <div class="col">
                <div class="stats-card total">
                    <div class="stats-number"><?php echo $stats['total']; ?></div>
                    <div class="stats-label">Tổng đơn hàng</div>
                </div>
            </div>
            <div class="col">
                <div class="stats-card pending">
                    <div class="stats-number"><?php echo $stats['pending']; ?></div>
                    <div class="stats-label">Chờ xử lý</div>
                </div>
            </div>
            <div class="col">
                <div class="stats-card processing">
                    <div class="stats-number"><?php echo $stats['processing']; ?></div>
                    <div class="stats-label">Đang xử lý</div>
                </div>
            </div>
            <div class="col">
                <div class="stats-card shipping">
                    <div class="stats-number"><?php echo $stats['shipping']; ?></div>
                    <div class="stats-label">Đang giao hàng</div>
                </div>
            </div>
            <div class="col">
                <div class="stats-card completed">
                    <div class="stats-number"><?php echo $stats['completed']; ?></div>
                    <div class="stats-label">Hoàn thành</div>
                </div>
            </div>
            <div class="col">
                <div class="stats-card cancelled">
                    <div class="stats-number"><?php echo $stats['cancelled']; ?></div>
                    <div class="stats-label">Đã hủy</div>
                </div>
            </div>
        </div>

        <!-- Bảng đơn hàng -->
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Mã ĐH</th>
                            <th>Số điện thoại</th>
                            <th>Địa chỉ</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th>Ngày đặt</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><strong>#DH<?php echo str_pad($row['MA_DH'], 6, '0', STR_PAD_LEFT); ?></strong></td>
                                    <td>
                                        <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($row['SDT']); ?>
                                    </td>
                                    <td>
                                        <?php 
                                        // Tu dong tim cot dia chi dung
                                        $dia_chi = '';
                                        foreach (['DIA_CHI_GIAO', 'DIA_CHI_GIAO_HANG', 'DIA_CHI', 'DIACHI', 'ADDRESS'] as $col) {
                                            if (isset($row[$col]) && !empty($row[$col])) {
                                                $dia_chi = $row[$col];
                                                break;
                                            }
                                        }
                                        if (!empty($dia_chi)): ?>
                                            <i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($dia_chi); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Chưa có</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong class="text-danger"><?php echo number_format($row['TONG_TIEN'], 0, ',', '.'); ?>đ</strong></td>
                                    <td>
                                        <?php
                                        $status_class = '';
                                        switch($row['TRANG_THAI']) {
                                            case 'Chờ xử lý': $status_class = 'status-pending'; break;
                                            case 'Đang xử lý': $status_class = 'status-processing'; break;
                                            case 'Đang giao hàng': $status_class = 'status-shipping'; break;
                                            case 'Hoàn thành': $status_class = 'status-completed'; break;
                                            case 'Đã hủy': $status_class = 'status-cancelled'; break;
                                            default: $status_class = 'status-pending';
                                        }
                                        ?>
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <?php echo htmlspecialchars($row['TRANG_THAI']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($row['NGAY_TAO'])); ?></td>
                                    <td>
                                        <button class="btn btn-info btn-sm btn-action" onclick="viewOrder(<?php echo $row['MA_DH']; ?>)" title="Xem chi tiết">
                                            <i class="fas fa-eye"></i> Chi tiết
                                        </button>
                                        <?php if (in_array($row['TRANG_THAI'], ['Chờ xử lý', 'Đang xử lý'])): ?>
                                        <button class="btn btn-danger btn-sm btn-action"
                                            onclick="openCancelModal(<?php echo $row['MA_DH']; ?>)"
                                            title="Hủy đơn">
                                            <i class="fas fa-times-circle"></i> Hủy đơn
                                        </button>
                                        <?php elseif ($row['TRANG_THAI'] === 'Đã hủy'): ?>
                                        <button class="btn btn-secondary btn-sm btn-action" disabled>
                                            <i class="fas fa-ban"></i> Đã hủy
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="empty-state">
                                        <i class="fas fa-shopping-bag"></i>
                                        <h5>Chưa có đơn hàng nào</h5>
                                        <p class="text-muted">Bạn chưa có đơn hàng nào. Hãy đặt hàng ngay!</p>
                                        <a href="trangchu.php" class="btn btn-primary mt-3">
                                            <i class="fas fa-shopping-cart"></i> Mua sắm ngay
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal xem chi tiết -->
    <div class="modal fade" id="orderModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-file-invoice"></i> Chi tiết đơn hàng</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="orderDetails">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-3 text-muted">Đang tải thông tin...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal xác nhận hủy đơn -->
    <div class="modal fade" id="cancelModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Xác nhận hủy đơn hàng</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4">
                        <input type="hidden" name="cancel_id" id="cancel_id">

                        <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
                            <i class="fas fa-info-circle me-2 fs-5"></i>
                            <div>Bạn đang yêu cầu hủy đơn hàng <strong id="cancel_order_label"></strong>.<br>
                            <small class="text-muted">Hành động này không thể hoàn tác!</small></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-comment-alt me-1 text-danger"></i>Lý do hủy đơn <span class="text-danger">*</span>
                            </label>
                            <select class="form-select cancel-reason-select" name="cancel_reason" id="cancel_reason" required>
                                <option value="">-- Chọn lý do --</option>
                                <option value="Đặt nhầm sản phẩm">Đặt nhầm sản phẩm</option>
                                <option value="Muốn thay đổi địa chỉ giao hàng">Muốn thay đổi địa chỉ giao hàng</option>
                                <option value="Tìm được nơi mua rẻ hơn">Tìm được nơi mua rẻ hơn</option>
                                <option value="Không còn nhu cầu mua">Không còn nhu cầu mua</option>
                                <option value="Đặt trùng đơn">Đặt trùng đơn</option>
                                <option value="Khác">Lý do khác...</option>
                            </select>
                        </div>

                        <div class="mb-1" id="other_reason_box" style="display:none;">
                            <label class="form-label fw-semibold">Ghi rõ lý do</label>
                            <textarea class="form-control" name="cancel_reason_other" id="cancel_reason_other"
                                rows="2" placeholder="Nhập lý do của bạn..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-arrow-left me-1"></i>Không, giữ lại
                        </button>
                        <button type="submit" name="cancel_order" class="btn btn-danger px-4">
                            <i class="fas fa-times-circle me-1"></i>Xác nhận hủy đơn
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewOrder(orderId) {
            const modal = new bootstrap.Modal(document.getElementById('orderModal'));
            modal.show();
            
            // Reset modal content
            document.getElementById('orderDetails').innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3 text-muted">Đang tải thông tin...</p>
                </div>
            `;
            
            fetch('chitietdonhang.php?id=' + orderId)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('orderDetails').innerHTML = data;
                })
                .catch(error => {
                    document.getElementById('orderDetails').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> 
                            Có lỗi xảy ra khi tải dữ liệu! Vui lòng thử lại.
                        </div>
                    `;
                });
        }

        function openCancelModal(orderId) {
            document.getElementById('cancel_id').value = orderId;
            document.getElementById('cancel_order_label').textContent =
                '#DH' + String(orderId).padStart(6, '0');
            document.getElementById('cancel_reason').value = '';
            document.getElementById('other_reason_box').style.display = 'none';
            new bootstrap.Modal(document.getElementById('cancelModal')).show();
        }

        // Hiện ô nhập lý do khác nếu chọn "Khác"
        document.getElementById('cancel_reason').addEventListener('change', function () {
            const box = document.getElementById('other_reason_box');
            const other = document.getElementById('cancel_reason_other');
            if (this.value === 'Khác') {
                box.style.display = 'block';
                other.required = true;
            } else {
                box.style.display = 'none';
                other.required = false;
                other.value = '';
            }
        });

        // Trước khi submit: nếu chọn "Khác" thì thay value select bằng nội dung textarea
        document.querySelector('#cancelModal form').addEventListener('submit', function (e) {
            const sel   = document.getElementById('cancel_reason');
            const other = document.getElementById('cancel_reason_other');
            if (sel.value === 'Khác') {
                if (!other.value.trim()) {
                    e.preventDefault();
                    other.focus();
                    other.classList.add('is-invalid');
                    return;
                }
                sel.value = other.value.trim();
            }
        });
    </script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>