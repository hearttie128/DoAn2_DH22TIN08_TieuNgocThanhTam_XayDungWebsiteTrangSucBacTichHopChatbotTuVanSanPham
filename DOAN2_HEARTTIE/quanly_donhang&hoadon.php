<?php
session_start();

// Kết nối database
$conn = new mysqli("localhost", "root", "", "qlchdt");
mysqli_set_charset($conn, "utf8mb4");

if ($conn->connect_error) {
    die("Lỗi kết nối: " . $conn->connect_error);
}

// Xử lý cập nhật đơn hàng (gộp trạng thái + thông tin)
if (isset($_POST['update_order'])) {
    $order_id    = (int)$_POST['order_id'];
    $new_status  = $_POST['new_status'];
    $edit_sdt    = trim($_POST['edit_sdt']);
    $edit_dc     = trim($_POST['edit_diachi']);
    $edit_note   = trim($_POST['edit_ghichu']);
    $edit_tt     = (int)str_replace(['.', ',', ' '], '', $_POST['edit_tongtien']);
    $edit_pttt   = trim($_POST['edit_pttt'] ?? '');

    // Cập nhật bảng DONHANG
    $upd  = "UPDATE DONHANG SET TRANG_THAI=?, SDT=?, DIA_CHI_GIAO=?, GHI_CHU=?, TONG_TIEN=?, PTTT=? WHERE MA_DH=?";
    $stmt = $conn->prepare($upd);
    $stmt->bind_param("ssssssi", $new_status, $edit_sdt, $edit_dc, $edit_note, $edit_tt, $edit_pttt, $order_id);
    if ($stmt->execute()) {
        $success_message = "Cập nhật đơn hàng <b>#DH" . str_pad($order_id, 6, '0', STR_PAD_LEFT) . "</b> thành công!";
    } else {
        $error_message = "Lỗi cập nhật: " . $conn->error;
    }
    $stmt->close();

    // Cập nhật tên khách hàng + email nếu có MA_ND
    $edit_ten   = trim($_POST['edit_ten'] ?? '');
    $edit_email = trim($_POST['edit_email'] ?? '');
    $edit_nd    = (int)($_POST['edit_nd'] ?? 0);
    if ($edit_nd > 0 && ($edit_ten || $edit_email)) {
        $upd2  = "UPDATE NGUOIDUNG SET TEN_DAY_DU=?, EMAIL=? WHERE MA_ND=?";
        $stmt2 = $conn->prepare($upd2);
        $stmt2->bind_param("ssi", $edit_ten, $edit_email, $edit_nd);
        $stmt2->execute();
        $stmt2->close();
    }
}

// Xử lý xóa đơn hàng
if (isset($_POST['delete_order'])) {
    $del_id = (int)$_POST['delete_id'];
    // Xóa chi tiết đơn trước (nếu có bảng CHITIETDONHANG)
    $del_ct = $conn->prepare("DELETE FROM CHITIETDONHANG WHERE MA_DH = ?");
    if ($del_ct) { $del_ct->bind_param("i", $del_id); $del_ct->execute(); $del_ct->close(); }
    // Xóa đơn hàng
    $del = $conn->prepare("DELETE FROM DONHANG WHERE MA_DH = ?");
    $del->bind_param("i", $del_id);
    if ($del->execute()) {
        $success_message = "Đã xóa đơn hàng <b>#DH" . str_pad($del_id, 6, '0', STR_PAD_LEFT) . "</b> thành công!";
    } else {
        $error_message = "Lỗi xóa đơn hàng: " . $conn->error;
    }
    $del->close();
}

// Lấy danh sách đơn hàng
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$sql = "SELECT d.*, n.TEN_DN, n.TEN_DAY_DU, n.EMAIL as USER_EMAIL, n.SDT as USER_SDT 
        FROM DONHANG d 
        LEFT JOIN NGUOIDUNG n ON d.MA_ND = n.MA_ND 
        WHERE 1=1";

$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (d.MA_DH LIKE ? OR n.TEN_DN LIKE ? OR n.TEN_DAY_DU LIKE ? OR d.SDT LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    $types .= "ssss";
}

if (!empty($status_filter)) {
    $sql .= " AND d.TRANG_THAI = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$sql .= " ORDER BY d.NGAY_TAO DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Đơn Hàng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        
        .admin-header {
            background: rgb(58, 83, 124);
            color: white;
            padding: 30px;
            margin-bottom: 30px;
            border-radius: 0 0 15px 15px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        
        .admin-header h2 {
            text-align: center;
            margin: 0;
            font-size: 32px;
            font-weight: bold;
        }
        
        .search-section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }
        
        .table-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table th {
            background-color: #3a537c;
            color: white;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .stats-number {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stats-label {
            font-size: 14px;
            opacity: 0.9;
        }
        .back-btn {
    position: fixed;
    bottom: 24px;
    left: 24px;
    text-decoration: none;
    background-color: rgb(58, 83, 124);
    color: white;
    font-weight: bold;
    padding: 10px 18px;
    border-radius: 20px;
    z-index: 999;
    transition: background-color 0.3s ease;
}
.back-btn:hover {
    background-color: rgb(40, 60, 100);
    color: white;
}
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="container-fluid">
            <h2> QUẢN LÝ ĐƠN HÀNG</h2>
            <a href="quantrihethong.php" class="back-btn">&#8592; Dashboard</a>
        </div>
    </div>

    <div class="container-fluid">
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
        <div class="row mb-4">
            <div class="col">
                <div class="stats-card">
                    <div class="stats-number">
                        <?php
                        $count_sql = "SELECT COUNT(*) as total FROM DONHANG";
                        $count_result = $conn->query($count_sql);
                        echo $count_result->fetch_assoc()['total'];
                        ?>
                    </div>
                    <div class="stats-label">Tổng đơn hàng</div>
                </div>
            </div>
            <div class="col">
                <div class="stats-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <div class="stats-number">
                        <?php
                        $pending_sql = "SELECT COUNT(*) as total FROM DONHANG WHERE TRANG_THAI = 'Chờ xử lý'";
                        $pending_result = $conn->query($pending_sql);
                        echo $pending_result->fetch_assoc()['total'];
                        ?>
                    </div>
                    <div class="stats-label">Chờ xử lý</div>
                </div>
            </div>
            <div class="col">
                <div class="stats-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <div class="stats-number">
                        <?php
                        $processing_sql = "SELECT COUNT(*) as total FROM DONHANG WHERE TRANG_THAI = 'Đang xử lý'";
                        $processing_result = $conn->query($processing_sql);
                        echo $processing_result->fetch_assoc()['total'];
                        ?>
                    </div>
                    <div class="stats-label">Đang xử lý</div>
                </div>
            </div>
            <div class="col">
                <div class="stats-card" style="background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%);">
                    <div class="stats-number">
                        <?php
                        $shipping_sql = "SELECT COUNT(*) as total FROM DONHANG WHERE TRANG_THAI = 'Đang giao hàng'";
                        $shipping_result = $conn->query($shipping_sql);
                        echo $shipping_result->fetch_assoc()['total'];
                        ?>
                    </div>
                    <div class="stats-label">Đang giao hàng</div>
                </div>
            </div>
            <div class="col">
                <div class="stats-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                    <div class="stats-number">
                        <?php
                        $completed_sql = "SELECT COUNT(*) as total FROM DONHANG WHERE TRANG_THAI = 'Hoàn thành'";
                        $completed_result = $conn->query($completed_sql);
                        echo $completed_result->fetch_assoc()['total'];
                        ?>
                    </div>
                    <div class="stats-label">Hoàn thành</div>
                </div>
            </div>
            <div class="col">
                <div class="stats-card" style="background: linear-gradient(135deg, #ff6b6b 0%, #c0392b 100%);">
                    <div class="stats-number">
                        <?php
                        $cancelled_sql = "SELECT COUNT(*) as total FROM DONHANG WHERE TRANG_THAI = 'Đã hủy'";
                        $cancelled_result = $conn->query($cancelled_sql);
                        echo $cancelled_result->fetch_assoc()['total'];
                        ?>
                    </div>
                    <div class="stats-label">Đã hủy</div>
                </div>
            </div>
        </div>

        <!-- Tìm kiếm -->
        <div class="search-section">
            <form method="GET" class="row g-3">
                <div class="col-md-5">
                    <label class="form-label"><i class="fas fa-search"></i> Tìm kiếm</label>
                    <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Mã đơn hàng, tên khách hàng, SĐT...">
                </div>
                <div class="col-md-3">
                    <label class="form-label"><i class="fas fa-filter"></i> Trạng thái</label>
                    <select class="form-select" name="status">
                        <option value="">Tất cả trạng thái</option>
                        <option value="Chờ xử lý" <?php echo $status_filter == 'Chờ xử lý' ? 'selected' : ''; ?>>Chờ xử lý</option>
                        <option value="Đang xử lý" <?php echo $status_filter == 'Đang xử lý' ? 'selected' : ''; ?>>Đang xử lý</option>
                        <option value="Đang giao hàng" <?php echo $status_filter == 'Đang giao hàng' ? 'selected' : ''; ?>>Đang giao hàng</option>
                        <option value="Hoàn thành" <?php echo $status_filter == 'Hoàn thành' ? 'selected' : ''; ?>>Hoàn thành</option>
                        <option value="Đã hủy" <?php echo $status_filter == 'Đã hủy' ? 'selected' : ''; ?>>Đã hủy</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="fas fa-search me-1"></i>Tìm kiếm
                        </button>
                        <a href="quanly_donhang.php" class="btn btn-outline-secondary">
                            <i class="fas fa-redo"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Bảng đơn hàng -->
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Mã ĐH</th>
                            <th>Khách hàng</th>
                            <th>Liên hệ</th>
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
                                        <div><strong><?php echo htmlspecialchars($row['TEN_DAY_DU'] ?: $row['TEN_DN'] ?: 'Khách'); ?></strong></div>
                                        <?php if($row['TEN_DN']): ?>
                                        <small class="text-muted">@<?php echo htmlspecialchars($row['TEN_DN']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div><i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($row['SDT']); ?></div>
                                        <?php if (!empty($row['USER_EMAIL'])): ?>
                                        <div><i class="fas fa-envelope me-1"></i><small class="text-muted"><?php echo htmlspecialchars($row['USER_EMAIL']); ?></small></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong class="text-danger"><?php echo number_format($row['TONG_TIEN'], 0, ',', '.'); ?>đ</strong></td>
                                    <td>
                                        <?php
                                        $status_class = '';
                                        switch($row['TRANG_THAI']) {
                                            case 'Chờ xử lý': $status_class = 'status-pending'; break;
                                            case 'Đang xử lý': $status_class = 'status-processing'; break;
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
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-warning btn-sm btn-action" 
                                            onclick="openEditModal(
                                                <?php echo $row['MA_DH']; ?>,
                                                '<?php echo addslashes($row['TRANG_THAI']); ?>',
                                                '<?php echo addslashes($row['TEN_DAY_DU'] ?: $row['TEN_DN'] ?: ''); ?>',
                                                '<?php echo addslashes($row['USER_EMAIL'] ?? ''); ?>',
                                                '<?php echo addslashes($row['SDT']); ?>',
                                                '<?php echo addslashes($row['DIA_CHI_GIAO'] ?? $row['DIA_CHI'] ?? ''); ?>',
                                                '<?php echo addslashes($row['GHI_CHU'] ?? ''); ?>',
                                                '<?php echo $row['TONG_TIEN']; ?>',
                                                '<?php echo addslashes($row['PTTT'] ?? ''); ?>',
                                                <?php echo (int)($row['MA_ND'] ?? 0); ?>
                                            )" title="Sửa / Cập nhật đơn hàng">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-danger btn-sm btn-action" onclick="deleteOrder(<?php echo $row['MA_DH']; ?>)" title="Xóa đơn hàng">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Không có đơn hàng nào</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal gộp: Cập nhật trạng thái + Sửa thông tin đơn hàng -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header text-white" style="background:#3a537c;">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Cập nhật đơn hàng <span id="edit_order_label"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="order_id" id="edit_order_id">
                    <input type="hidden" name="edit_nd"  id="edit_nd">
                    <div class="modal-body p-4">
                        <div class="row g-3">

                            <!-- NHÓM 1: Trạng thái -->
                            <div class="col-12">
                                <div class="p-3 rounded-3" style="background:#f0f4ff;border:1px solid #d0d9f0;">
                                    <label class="form-label fw-bold mb-2" style="color:#3a537c;">
                                        <i class="fas fa-tag me-1" style="color:#3a537c;"></i>Trạng thái đơn hàng
                                    </label>
                                    <select class="form-select" name="new_status" id="new_status" required>
                                        <option value="Chờ xử lý"> Chờ xử lý</option>
                                        <option value="Đang xử lý"> Đang xử lý</option>
                                        <option value="Đang giao hàng"> Đang giao hàng</option>
                                        <option value="Hoàn thành"> Hoàn thành</option>
                                        <option value="Đã hủy"> Đã hủy</option>
                                    </select>
                                </div>
                            </div>

                            <!-- NHÓM 2: Thông tin khách hàng -->
                            <div class="col-12">
                                <p class="fw-bold mb-2" style="color:#3a537c; border-bottom:2px solid #3a537c; padding-bottom:5px;">
                                    <i class="fas fa-user me-1" style="color:#3a537c;"></i>Thông tin khách hàng
                                </p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-user me-1" style="color:#3a537c;"></i>Tên khách hàng
                                </label>
                                <input type="text" class="form-control" name="edit_ten" id="edit_ten" placeholder="Nhập tên khách hàng...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-envelope me-1" style="color:#3a537c;"></i>Email
                                </label>
                                <input type="email" class="form-control" name="edit_email" id="edit_email" placeholder="Nhập email...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-phone me-1" style="color:#3a537c;"></i>Số điện thoại
                                </label>
                                <input type="text" class="form-control" name="edit_sdt" id="edit_sdt" placeholder="Nhập SĐT...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-map-marker-alt me-1" style="color:#3a537c;"></i>Địa chỉ giao hàng
                                </label>
                                <input type="text" class="form-control" name="edit_diachi" id="edit_diachi" placeholder="Nhập địa chỉ...">
                            </div>

                            <!-- NHÓM 3: Thông tin đơn hàng -->
                            <div class="col-12 mt-2">
                                <p class="fw-bold mb-2" style="color:#3a537c; border-bottom:2px solid #3a537c; padding-bottom:5px;">
                                    <i class="fas fa-shopping-bag me-1" style="color:#3a537c;"></i>Thông tin đơn hàng
                                </p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-money-bill-wave me-1" style="color:#3a537c;"></i>Tổng tiền (đ)
                                </label>
                                <input type="text" class="form-control" name="edit_tongtien" id="edit_tongtien" placeholder="Nhập tổng tiền...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-credit-card me-1" style="color:#3a537c;"></i>Phương thức thanh toán
                                </label>
                                <select class="form-select" name="edit_pttt" id="edit_pttt">
                                    <option value="Thanh toán khi nhận hàng">Thanh toán khi nhận hàng</option>
                                    <option value="VietQR">Chuyển khoản ngân hàng</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-comment me-1" style="color:#3a537c;"></i>Ghi chú
                                </label>
                                <textarea class="form-control" name="edit_ghichu" id="edit_ghichu" rows="2" placeholder="Ghi chú đơn hàng..."></textarea>
                            </div>

                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Hủy
                        </button>
                        <button type="submit" name="update_order" class="btn px-4 text-white fw-semibold" style="background:#3a537c;">
                            <i class="fas fa-save me-1"></i>Lưu thay đổi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal xác nhận xóa -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-trash me-2"></i>Xác nhận xóa đơn hàng</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="delete_id" id="delete_id">
                    <div class="modal-body p-4 text-center">
                        <i class="fas fa-exclamation-triangle text-danger fa-3x mb-3"></i>
                        <h5>Bạn có chắc chắn muốn xóa?</h5>
                        <p class="text-muted mb-0">Đơn hàng <strong id="delete_order_label" class="text-danger"></strong> sẽ bị xóa vĩnh viễn.<br><small>Hành động này không thể hoàn tác!</small></p>
                    </div>
                    <div class="modal-footer justify-content-center">
                        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal"><i class="fas fa-arrow-left me-1"></i>Không, giữ lại</button>
                        <button type="submit" name="delete_order" class="btn btn-danger px-4"><i class="fas fa-trash me-1"></i>Xóa đơn hàng</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal xem chi tiết -->
    <div class="modal fade" id="orderModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-file-alt me-2"></i>Chi tiết đơn hàng</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="orderDetails">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Đóng
                    </button>
                    <button type="button" class="btn btn-success" id="btnPrintOrder" onclick="printOrder()">
                        <i class="fas fa-print me-1"></i>Xuất hóa đơn
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Khung in ẩn -->
    <div id="printArea" style="display:none;"></div>

    <style>
        @media print {
            body > *:not(#printOverlay) { display: none !important; }
            #printOverlay {
                display: block !important;
                position: fixed;
                inset: 0;
                background: white;
                z-index: 99999;
                padding: 30px 40px;
                font-family: 'Segoe UI', Arial, sans-serif;
                font-size: 14px;
                color: #222;
            }
        }
        #printOverlay { display: none; }
    </style>

    <!-- Lớp phủ in -->
    <div id="printOverlay"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openEditModal(id, trangThai, ten, email, sdt, diachi, ghichu, tongtien, pttt, maND) {
            document.getElementById('edit_order_id').value          = id;
            document.getElementById('edit_nd').value                = maND;
            document.getElementById('edit_order_label').textContent = '#DH' + String(id).padStart(6, '0');
            document.getElementById('new_status').value             = trangThai;
            document.getElementById('edit_ten').value               = ten;
            document.getElementById('edit_email').value             = email;
            document.getElementById('edit_sdt').value               = sdt;
            document.getElementById('edit_diachi').value            = diachi;
            document.getElementById('edit_ghichu').value            = ghichu;
            document.getElementById('edit_tongtien').value          = Number(tongtien).toLocaleString('vi-VN');
            // Chon dung option phuong thuc thanh toan
            const ptttSel = document.getElementById('edit_pttt');
            for (let i = 0; i < ptttSel.options.length; i++) {
                if (ptttSel.options[i].value === pttt) { ptttSel.selectedIndex = i; break; }
            }
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }

        function deleteOrder(id) {
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_order_label').textContent = '#DH' + String(id).padStart(6, '0');
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }

        function viewOrder(orderId) {
            // Reset nút in
            document.getElementById('btnPrintOrder').disabled = true;
            document.getElementById('btnPrintOrder').innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Đang tải...';

            const modal = new bootstrap.Modal(document.getElementById('orderModal'));
            modal.show();
            
            fetch('chitietdonhang.php?id=' + orderId)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('orderDetails').innerHTML = data;
                    // Kích hoạt nút in sau khi tải xong
                    document.getElementById('btnPrintOrder').disabled = false;
                    document.getElementById('btnPrintOrder').innerHTML = '<i class="fas fa-print me-1"></i>Xuất hóa đơn';
                })
                .catch(error => {
                    document.getElementById('orderDetails').innerHTML = '<div class="alert alert-danger">Có lỗi xảy ra khi tải dữ liệu!</div>';
                    document.getElementById('btnPrintOrder').disabled = true;
                    document.getElementById('btnPrintOrder').innerHTML = '<i class="fas fa-print me-1"></i>Xuất hóa đơn';
                });
        }

        function printOrder() {
            const content = document.getElementById('orderDetails').innerHTML;
            const overlay = document.getElementById('printOverlay');

            // Đưa nội dung vào lớp phủ in với style đẹp
            overlay.innerHTML = `
                <div style="max-width:700px; margin:0 auto;">
                    <div style="text-align:center; margin-bottom:20px; border-bottom:2px solid #4a92e4; padding-bottom:15px;">
                        <h2 style="color:#4a92e4; margin:0; font-size:22px;">🛍️ HEARTTIE - HÓA ĐƠN BÁN HÀNG</h2>
                        <p style="margin:5px 0 0; color:#888; font-size:12px;">Hotline: 0794 385 228 | hearttie128@gmail.com</p>
                    </div>
                    <div>${content}</div>
                    <div style="text-align:center; margin-top:25px; border-top:1px solid #eee; padding-top:15px; color:#aaa; font-size:11px;">
                        Cảm ơn quý khách đã mua hàng tại Hearttie! ❤️<br>
                        In lúc: ${new Date().toLocaleString('vi-VN')}
                    </div>
                </div>`;

            // Ẩn các nút thao tác không cần in (nếu có trong chitietdonhang.php)
            overlay.querySelectorAll('button, .btn, .no-print').forEach(el => el.style.display = 'none');

            window.print();

            // Dọn dẹp sau khi in
            setTimeout(() => { overlay.innerHTML = ''; }, 500);
        }

        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>