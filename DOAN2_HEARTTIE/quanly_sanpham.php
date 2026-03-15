<?php
session_start();

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'qlchdt';

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die('Kết nối thất bại: ' . $conn->connect_error);
}
mysqli_set_charset($conn, "utf8mb4");

// Xử lý xóa sản phẩm
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $sql_delete = "DELETE FROM SANPHAM WHERE MA_SP = ?";
    $stmt = $conn->prepare($sql_delete);
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Xóa sản phẩm thành công!';
    } else {
        $_SESSION['error'] = 'Xóa thất bại: ' . $conn->error;
    }
    header('Location: quanly_sanpham.php');
    exit();
}

// Xử lý thay đổi trạng thái nhanh
if (isset($_GET['toggle_status'])) {
    $product_id = $_GET['toggle_status'];
    $new_status = $_GET['status'];

    $sql_update_status = "UPDATE SANPHAM SET TRANG_THAI = ? WHERE MA_SP = ?";
    $stmt = $conn->prepare($sql_update_status);
    $stmt->bind_param("si", $new_status, $product_id);
    if ($stmt->execute()) {
        $status_text = ($new_status == 'DANG_BAN') ? 'Đang bán' : (($new_status == 'TAM_NGUNG') ? 'Tạm ngừng' : 'Ẩn');
        $_SESSION['success'] = "Đã chuyển trạng thái sản phẩm thành '$status_text'!";
    } else {
        $_SESSION['error'] = 'Cập nhật trạng thái thất bại: ' . $conn->error;
    }
    header('Location: quanly_sanpham.php');
    exit();
}

// Xử lý thêm sản phẩm
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $product_name = $_POST['product_name'];
    $category_id = $_POST['category_id'];
    $price = $_POST['price'];
    $image_url = $_POST['image_url'];
    $description = $_POST['description'];
    $quantity = $_POST['quantity'];
    $status = $_POST['status'];

    $sql = "INSERT INTO SANPHAM (TEN_SP, MA_DM, GIA, HINH_ANH, MO_TA, SO_LUONG, TRANG_THAI) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("siissis", $product_name, $category_id, $price, $image_url, $description, $quantity, $status);
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Thêm sản phẩm thành công!';
        // Chuyển về trang đầu tiên để hiển thị sản phẩm mới
        header('Location: quanly_sanpham.php?page=1');
    } else {
        $_SESSION['error'] = 'Thêm thất bại: ' . $conn->error;
        header('Location: quanly_sanpham.php');
    }
    exit();
}

// Xử lý sửa sản phẩm
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit') {
    $product_id = $_POST['product_id'];
    $product_name = $_POST['product_name'];
    $category_id = $_POST['category_id'];
    $price = $_POST['price'];
    $image_url = $_POST['image_url'];
    $description = $_POST['description'];
    $quantity = $_POST['quantity'];
    $status = $_POST['status'];

    $sql = "UPDATE SANPHAM SET TEN_SP=?, MA_DM=?, GIA=?, HINH_ANH=?, MO_TA=?, SO_LUONG=?, TRANG_THAI=? WHERE MA_SP=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("siissisi", $product_name, $category_id, $price, $image_url, $description, $quantity, $status, $product_id);
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Cập nhật sản phẩm thành công!';
    } else {
        $_SESSION['error'] = 'Cập nhật thất bại: ' . $conn->error;
    }
    header('Location: quanly_sanpham.php');
    exit();
}

// Phân trang
$records_per_page = 5;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Lọc theo trạng thái
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : '';
$where_clause = '';
if ($status_filter != '') {
    $where_clause = "WHERE p.TRANG_THAI = '$status_filter'";
}

// Đếm tổng số sản phẩm
$count_sql = "SELECT COUNT(*) as total FROM SANPHAM p $where_clause";
$count_result = mysqli_query($conn, $count_sql);
$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Thay đổi ORDER BY để sản phẩm mới nhất (ID cao nhất) hiện lên đầu
$sql = "SELECT p.*, d.TEN_DM as CATEGORY_NAME
        FROM SANPHAM p
        JOIN DANHMUC d ON p.MA_DM = d.MA_DM
        $where_clause
        ORDER BY p.MA_SP DESC
        LIMIT $records_per_page OFFSET $offset";
$result = mysqli_query($conn, $sql);

$categories = mysqli_query($conn, "SELECT MA_DM, TEN_DM FROM DANHMUC ORDER BY TEN_DM");
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Quản lý sản phẩm</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f9f9f9;
            margin: 0;
            color: #333;
        }

       header {
            display: flex;
            align-items: center;
            background-color: rgb(58, 83, 124);
            color: white;
            border-radius: 7px;
            margin: 0;
            padding: 15px 20px;
            height: 100px;
            justify-content: center;
            box-sizing: border-box;
        }

        header h1 {
            font-size: 32px;
            font-weight: bold;
            margin: 0;
            text-align: center;
        }
        
        .alert {
            padding: 10px 15px;
            margin: 0;
            border-radius: 20px;
            font-weight: bold;
            position: absolute;
            left: 50%;
            top: 19%;
            transform: translate(-50%, -50%);
            z-index: 10;
            white-space: nowrap;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            animation: slideInFromTop 0.5s ease-out;
        }

        .alert-success {
            background-color:rgb(217, 255, 226);
            color: rgb(15, 157, 48);
            border: 1.5px solid rgb(81, 198, 108);
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 2px solid #f5c6cb;
        }

        @keyframes slideInFromTop {
            0% {
                opacity: 0;
                transform: translate(-50%, -70%);
            }
            100% {
                opacity: 1;
                transform: translate(-50%, -50%);
            }
        }

        .alert.fade-out {
            animation: fadeOutUp 0.5s ease-in forwards;
        }

        @keyframes fadeOutUp {
            0% {
                opacity: 1;
                transform: translate(-50%, -50%);
            }
            100% {
                opacity: 0;
                transform: translate(-50%, -70%);
            }
        }

        .button-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px;
        }

        .filter-section {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .filter-section select {
            padding: 8px 12px;
            border: 2px solid #e1e5e9;
            border-radius: 6px;
            font-size: 14px;
        }

        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-success {
            background-color: rgb(58, 83, 124);
            margin-left: 1px;
            color: white;
            padding: 10px 15px;
            border-radius: 20px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .btn-success:hover {
            background-color: rgb(59, 96, 155);
        }

        .btn-primary {
            background-color: rgb(57, 110, 195);
            color: white;
        }

        .btn-primary:hover {
            background-color: rgb(39, 93, 180);
        }

        .btn-danger {
            background-color: rgb(202, 43, 59);
            color: white;
        }

        .btn-danger:hover {
            background-color: rgb(190, 2, 20);
        }

        .btn-warning {
            background-color: #ffc107;
            color: #212529;
        }

        .btn-warning:hover {
            background-color: #ffca2c;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        .btn-logout {
            background-color: rgb(161, 18, 18);
            color: white;
            font-weight: bold;
            padding: 10px 15px;
            border-radius: 20px;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }

        .btn-logout:hover {
            background-color: rgb(198, 31, 12);
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-active {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-paused {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .status-hidden {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .status-dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #f9f9f9;
            min-width: 120px;
            box-shadow: 0px 8px 16px 0px rgba(0, 0, 0, 0.2);
            z-index: 1;
            border-radius: 4px;
            top: 100%;
            right: 0;
        }

        .dropdown-content a {
            color: black;
            padding: 8px 12px;
            text-decoration: none;
            display: block;
            font-size: 12px;
        }

        .dropdown-content a:hover {
            background-color: #f1f1f1;
        }

        .status-dropdown:hover .dropdown-content {
            display: block;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 3% auto;
            padding: 0;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 30px;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }

        .close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        .close:hover {
            color: #f1f1f1;
            transform: scale(1.1);
        }

        .modal-body {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 14px;
            box-sizing: border-box;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .modal-footer {
            padding: 20px 30px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        table {
            width: calc(100% - 40px);
            margin: 0 20px;
            border-collapse: collapse;
            background-color: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        thead tr {
            background-color: rgb(58, 83, 124);
            text-align: center;
            font-weight: bold;
            font-size: 16px;
            margin-right: 10px;
        }

        thead th {
            color: white;
            padding: 14px 12px;
            border-right: 1.5px solid rgba(235, 229, 229, 0.15);
            font-size: 16px;
        }

        thead th:last-child {
            border-right: none;
        }

        tbody tr {
            border-bottom: 1px solid #ddd;
            transition: background-color 0.2s ease;
            margin-right: 10px;
        }

        tbody tr:hover {
            background-color: #f1f8ff;
        }

        tbody td {
            padding: 12px;
            text-align: center;
            font-size: 14px;
            color: #2c3e50;
            vertical-align: middle;
            word-wrap: break-word;
        }

        img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .actions {
            display: flex;
            gap: 8px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .actions .btn {
            padding: 6px 10px;
            font-size: 12px;
        }

        .price-display {
            font-weight: bold;
            color: #e74c3c;
            font-size: 16px;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 20px 0;
            gap: 10px;
        }

        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border: 1px solid #ddd;
            text-decoration: none;
            color: #333;
            border-radius: 4px;
        }

        .pagination a:hover {
            background-color: #f5f5f5;
        }

        .pagination .current {
            background-color: rgb(58, 83, 124);
            color: white;
            border-color: rgb(58, 83, 124);
        }

        .pagination .disabled {
            color: #ccc;
            cursor: not-allowed;
        }

        /* Thêm hiệu ứng highlight cho sản phẩm mới */
        .new-product {
            background-color: #e8f5e8 !important;
            animation: highlightNew 3s ease-out;
        }

        @keyframes highlightNew {
            0% {
                background-color: #c8e6c9;
                transform: scale(1.02);
            }
            50% {
                background-color: #e8f5e8;
            }
            100% {
                background-color: transparent;
                transform: scale(1);
            }
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
    <header>
        <h1>QUẢN LÝ SẢN PHẨM</h1>
    </header>
    <a href="quantrihethong.php" class="back-btn">&#8592; Dashboard</a>
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

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php echo $_SESSION['success'];
            unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <?php echo $_SESSION['error'];
            unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="button-bar">
        <div style="display: flex; gap: 15px; align-items: center;">
            <button class="btn btn-success" onclick="openAddModal()">+ Thêm sản phẩm</button>

            <div class="filter-section">
                <label for="status_filter" style="font-weight: bold;">Lọc:</label>
                <select id="status_filter" name="status_filter" onchange="filterByStatus()">
                    <option value="">Tất cả</option>
                    <option value="DANG_BAN" <?php echo ($status_filter == 'DANG_BAN') ? 'selected' : ''; ?>>Đang bán</option>
                    <option value="TAM_NGUNG" <?php echo ($status_filter == 'TAM_NGUNG') ? 'selected' : ''; ?>>Tạm ngừng</option>
                    <option value="AN" <?php echo ($status_filter == 'AN') ? 'selected' : ''; ?>>Ẩn</option>
                </select>
            </div>
        </div>

        <!-- <a href="admin_logout.php" class="btn-logout">Đăng xuất</a> -->
    </div>

    <table>
        <thead>
            <tr>
                <th>STT</th>
                <th>Tên sản phẩm</th>
                <th>Loại</th>
                <th>Giá</th>
                <th>Hình ảnh</th>
                <th>Mã sản phẩm</th>
                <th>Số lượng</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if (mysqli_num_rows($result) > 0) {
                $stt = ($page - 1) * $records_per_page + 1;
                while ($row = mysqli_fetch_assoc($result)) {
                    $description = htmlspecialchars($row['MO_TA']);
                    $status = $row['TRANG_THAI'] ?? 'DANG_BAN';

                    // Xác định màu sắc và text cho trạng thái
                    $status_class = '';
                    $status_text = '';
                    switch ($status) {
                        case 'DANG_BAN':
                            $status_class = 'status-active';
                            $status_text = 'Đang bán';
                            break;
                        case 'TAM_NGUNG':
                            $status_class = 'status-paused';
                            $status_text = 'Tạm ngừng';
                            break;
                        case 'AN':
                            $status_class = 'status-hidden';
                            $status_text = 'Ẩn';
                            break;
                        default:
                            $status_class = 'status-active';
                            $status_text = 'Đang bán';
                    }

                    // Escape các giá trị để tránh lỗi JavaScript
                    $escaped_name = htmlspecialchars($row['TEN_SP'], ENT_QUOTES);
                    $escaped_image = htmlspecialchars($row['HINH_ANH'], ENT_QUOTES);
                    $escaped_description = htmlspecialchars($row['MO_TA'], ENT_QUOTES);

                    // Thêm class highlight nếu là sản phẩm mới (ở trang đầu tiên và là item đầu tiên)
                    $highlight_class = ($page == 1 && $stt == 1) ? 'new-product' : '';

                    echo "<tr class='$highlight_class'>
                            <td><strong>$stt</strong></td>
                            <td style='text-align: left; font-weight: 600;'>{$row['TEN_SP']}</td>
                            <td>{$row['CATEGORY_NAME']}</td>
                            <td class='price-display'>" . number_format($row['GIA'], 0, ',', '.') . " đ</td>
                            <td><img src='{$row['HINH_ANH']}' alt='{$row['TEN_SP']}' onerror=\"this.src='https://via.placeholder.com/80x80?text=No+Image'\"></td>
                            <td style='text-align: left; max-width: 200px; overflow: hidden; text-overflow: ellipsis;'>{$row['MO_TA']}</td>
                            <td><strong>{$row['SO_LUONG']}</strong></td>
                            <td>
                                <div class='status-dropdown'>
                                    <span class='status-badge $status_class'>$status_text</span>
                                    <div class='dropdown-content'>
                                        <a href='?toggle_status={$row['MA_SP']}&status=DANG_BAN'>Đang bán</a>
                                        <a href='?toggle_status={$row['MA_SP']}&status=TAM_NGUNG'>Tạm ngừng</a>
                                        <a href='?toggle_status={$row['MA_SP']}&status=AN'>Ẩn</a>
                                    </div>
                                </div>
                            </td>
                            <td class='actions'>
                                <button onclick=\"openEditModal({$row['MA_SP']}, '$escaped_name', {$row['MA_DM']}, {$row['GIA']}, '$escaped_image', '$escaped_description', {$row['SO_LUONG']}, '$status')\" class='btn btn-primary'>
                                    Sửa
                                </button>
                                <a href='?delete_id={$row['MA_SP']}' class='btn btn-danger' onclick=\"return confirm('Bạn chắc chắn muốn xóa sản phẩm này?\\nHành động này không thể hoàn tác!')\">
                                    Xóa
                                </a>
                            </td>
                        </tr>";
                    $stt++;
                }
            } else {
                echo "<tr><td colspan='9' style='padding: 40px; color: #666;'>Không có sản phẩm tương ứng</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <!-- Phân trang -->
    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=1<?php echo ($status_filter ? '&status_filter=' . $status_filter : ''); ?>">Đầu</a>
                <a href="?page=<?php echo $page - 1; ?><?php echo ($status_filter ? '&status_filter=' . $status_filter : ''); ?>">Trước</a>
            <?php else: ?>
                <span class="disabled">Đầu</span>
                <span class="disabled">Trước</span>
            <?php endif; ?>

            <?php
            $start = max(1, $page - 2);
            $end = min($total_pages, $page + 2);

            for ($i = $start; $i <= $end; $i++):
                if ($i == $page): ?>
                    <span class="current"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?><?php echo ($status_filter ? '&status_filter=' . $status_filter : ''); ?>"><?php echo $i; ?></a>
                <?php endif;
            endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?><?php echo ($status_filter ? '&status_filter=' . $status_filter : ''); ?>">Sau</a>
                <a href="?page=<?php echo $total_pages; ?><?php echo ($status_filter ? '&status_filter=' . $status_filter : ''); ?>">Cuối</a>
            <?php else: ?>
                <span class="disabled">Sau</span>
                <span class="disabled">Cuối</span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Modal Thêm sản phẩm -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Thêm sản phẩm mới</h3>
                <span class="close" onclick="closeAddModal()">&times;</span>
            </div>
            <form method="POST" id="addForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">

                    <div class="form-group">
                        <label for="add_product_name">Tên sản phẩm *</label>
                        <input type="text" class="form-control" id="add_product_name" name="product_name" required>
                    </div>

                    <div class="form-group">
                        <label for="add_category_id">Loại *</label>
                        <select class="form-control" id="add_category_id" name="category_id" required>
                            <option value="">Chọn loại</option>
                            <?php
                            mysqli_data_seek($categories, 0);
                            while ($category = mysqli_fetch_assoc($categories)): ?>
                                <option value="<?php echo $category['MA_DM']; ?>">
                                    <?php echo $category['TEN_DM']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="add_price">Giá (VNĐ) *</label>
                        <input type="number" class="form-control" id="add_price" name="price" min="0" required>
                    </div>

                    <div class="form-group">
                        <label for="add_image_url">URL hình ảnh</label>
                        <input type="url" class="form-control" id="add_image_url" name="image_url">
                    </div>

                    <div class="form-group">
                        <label for="add_description">Mã sản phẩm</label>
                        <textarea class="form-control" id="add_description" name="description" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="add_quantity">Số lượng *</label>
                        <input type="number" class="form-control" id="add_quantity" name="quantity" min="0" required>
                    </div>

                    <div class="form-group">
                        <label for="add_status">Trạng thái *</label>
                        <select class="form-control" id="add_status" name="status" required>
                            <option value="DANG_BAN">Đang bán</option>
                            <option value="TAM_NGUNG">Tạm ngừng</option>
                            <option value="AN">Ẩn</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" onclick="closeAddModal()" class="btn" style="background: #6c757d; color: white;">
                        Hủy
                    </button>
                    <button type="submit" class="btn btn-success">
                        Thêm sản phẩm
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Sửa sản phẩm -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Sửa sản phẩm</h3>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <form method="POST" id="editForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" id="edit_product_id" name="product_id">

                    <div class="form-group">
                        <label for="edit_product_name">Tên sản phẩm *</label>
                        <input type="text" class="form-control" id="edit_product_name" name="product_name" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_category_id">Loại *</label>
                        <select class="form-control" id="edit_category_id" name="category_id" required>
                            <option value="">Chọn loại</option>
                            <?php
                            mysqli_data_seek($categories, 0);
                            while ($category = mysqli_fetch_assoc($categories)): ?>
                                <option value="<?php echo $category['MA_DM']; ?>">
                                    <?php echo $category['TEN_DM']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="edit_price">Giá (VNĐ) *</label>
                        <input type="number" class="form-control" id="edit_price" name="price" min="0" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_image_url">URL hình ảnh</label>
                        <input type="url" class="form-control" id="edit_image_url" name="image_url">
                    </div>

                    <div class="form-group">
                        <label for="edit_description">Mô tả</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="edit_quantity">Số lượng *</label>
                        <input type="number" class="form-control" id="edit_quantity" name="quantity" min="0" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_status">Trạng thái *</label>
                        <select class="form-control" id="edit_status" name="status" required>
                            <option value="DANG_BAN">Đang bán</option>
                            <option value="TAM_NGUNG">Tạm ngừng</option>
                            <option value="AN">Ẩn</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" onclick="closeEditModal()" class="btn" style="background: #6c757d; color: white;">
                        Hủy
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Cập nhật sản phẩm
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Filter function
        function filterByStatus() {
            const status = document.getElementById('status_filter').value;
            const currentPage = new URLSearchParams(window.location.search).get('page') || 1;
            let url = '?page=1';
            if (status) {
                url += '&status_filter=' + status;
            }
            window.location.href = url;
        }

        // Alert auto-hide and click-to-close
        document.querySelectorAll('.alert').forEach(alert => {
            setTimeout(() => {
                alert.classList.add('fade-out');
                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.remove();
                    }
                }, 500);
            }, 3000);

            alert.style.cursor = 'pointer';
            alert.title = 'Click để đóng';
            alert.addEventListener('click', () => {
                alert.classList.add('fade-out');
                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.remove();
                    }
                }, 500);
            });
        });
            
        // Modal Thêm sản phẩm
        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }

        function closeAddModal() {
            document.getElementById('addModal').style.display = 'none';
            document.getElementById('addForm').reset();
        }

        // Modal Sửa sản phẩm
        function openEditModal(id, name, categoryId, price, imageUrl, description, quantity, status) {
            document.getElementById('editModal').style.display = 'block';

            document.getElementById('edit_product_id').value = id;
            document.getElementById('edit_product_name').value = name;
            document.getElementById('edit_category_id').value = categoryId;
            document.getElementById('edit_price').value = price;
            document.getElementById('edit_image_url').value = imageUrl;
            document.getElementById('edit_description').value = description;
            document.getElementById('edit_quantity').value = quantity;
            document.getElementById('edit_status').value = status || 'DANG_BAN';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
            document.getElementById('editForm').reset();
        }

        // Đóng modal khi click bên ngoài
        window.onclick = function (event) {
            let addModal = document.getElementById('addModal');
            let editModal = document.getElementById('editModal');

            if (event.target == addModal) {
                closeAddModal();
            }
            if (event.target == editModal) {
                closeEditModal();
            }
        }

        // Đóng modal khi nhấn Esc
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeAddModal();
                closeEditModal();
            }
        });
    </script>
</body>

</html>

<?php
mysqli_close($conn);