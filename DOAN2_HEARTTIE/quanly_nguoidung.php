<?php
session_start();
$ketnoi = new mysqli("localhost", "root", "", "qlchdt");
if ($ketnoi->connect_error) {
    die("Kết nối thất bại: " . $ketnoi->connect_error);
}

if (isset($_GET['delete'])) {
    $user_id = $_GET['delete'];
    $delete_stmt = $ketnoi->prepare("DELETE FROM NGUOIDUNG WHERE MA_ND = ?");
    $delete_stmt->bind_param("s", $user_id);

    if ($delete_stmt->execute()) {
        $_SESSION['success'] = "Xóa người dùng thành công!";
    } else {
        $_SESSION['error'] = "Lỗi khi xóa người dùng: " . $delete_stmt->error;
    }
    $delete_stmt->close();

    header("Location: quanly_nguoidung.php");
    exit();
}

if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $user_id = trim($_POST['user_id']);
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $gender = trim($_POST['gender']);
    $birthday = trim($_POST['birthday']);
    $admin_code = trim($_POST['admin_code']);

    if (empty($user_id) || empty($fullname) || empty($email)) {
        $_SESSION['error'] = "Vui lòng điền đầy đủ các trường bắt buộc!";
        header("Location: quanly_nguoidung.php");
        exit();
    }

    $check_email_stmt = $ketnoi->prepare("SELECT EMAIL FROM NGUOIDUNG WHERE EMAIL = ? AND MA_ND != ?");
    $check_email_stmt->bind_param("ss", $email, $user_id);
    $check_email_stmt->execute();
    $check_email_result = $check_email_stmt->get_result();

    if ($check_email_result->num_rows > 0) {
        $_SESSION['error'] = "Email đã tồn tại ở người dùng khác!";
        $check_email_stmt->close();
        header("Location: quanly_nguoidung.php");
        exit();
    }
    $check_email_stmt->close();

    $get_role_stmt = $ketnoi->prepare("SELECT VAI_TRO FROM NGUOIDUNG WHERE MA_ND = ?");
    $get_role_stmt->bind_param("s", $user_id);
    $get_role_stmt->execute();
    $role_result = $get_role_stmt->get_result();
    $current_role = 'User';

    if ($role_result->num_rows > 0) {
        $role_row = $role_result->fetch_assoc();
        $current_role = $role_row['VAI_TRO'];
    }
    $get_role_stmt->close();

    if (!empty($admin_code)) {
        $admin_code_valid = "admin1208";
        if ($admin_code === $admin_code_valid) {
            $current_role = 'Admin';
        } else {
            $_SESSION['error'] = "Mã xác nhận admin không hợp lệ!";
            header("Location: quanly_nguoidung.php");
            exit();
        }
    }

    try {
        $update_stmt = $ketnoi->prepare("UPDATE NGUOIDUNG SET 
            TEN_DAY_DU = ?, EMAIL = ?, SDT = ?, DIA_CHI = ?, 
            GIOI_TINH = ?, NGAY_SINH = ?, VAI_TRO = ? 
            WHERE MA_ND = ?");

        $update_stmt->bind_param(
            "ssssssss",
            $fullname,
            $email,
            $phone,
            $address,
            $gender,
            $birthday,
            $current_role,
            $user_id
        );

        if ($update_stmt->execute()) {
            if ($update_stmt->affected_rows > 0) {
                $_SESSION['success'] = "Cập nhật thông tin người dùng thành công!";
            } else {
                $_SESSION['error'] = "Không có thay đổi nào được thực hiện!";
            }
        } else {
            $_SESSION['error'] = "Lỗi khi cập nhật thông tin người dùng: " . $update_stmt->error;
        }
        $update_stmt->close();

    } catch (Exception $e) {
        $_SESSION['error'] = "Lỗi database: " . $e->getMessage();
    }

    header("Location: quanly_nguoidung.php");
    exit();
}

if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $username = trim($_POST['username']);
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $gender = trim($_POST['gender']);
    $birthday = trim($_POST['birthday']);
    $password = $_POST['password'];
    $admin_code = trim($_POST['admin_code']);

    if (empty($username) || empty($fullname) || empty($email) || empty($password)) {
        $_SESSION['error'] = "Vui lòng điền đầy đủ các trường bắt buộc!";
        header("Location: quanly_nguoidung.php");
        exit();
    }

    $check_stmt = $ketnoi->prepare("SELECT TEN_DN FROM NGUOIDUNG WHERE TEN_DN = ?");
    $check_stmt->bind_param("s", $username);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $_SESSION['error'] = "Tên đăng nhập đã tồn tại!";
        $check_stmt->close();
        header("Location: quanly_nguoidung.php");
        exit();
    }
    $check_stmt->close();

    $check_email_stmt = $ketnoi->prepare("SELECT EMAIL FROM NGUOIDUNG WHERE EMAIL = ?");
    $check_email_stmt->bind_param("s", $email);
    $check_email_stmt->execute();
    $check_email_result = $check_email_stmt->get_result();

    if ($check_email_result->num_rows > 0) {
        $_SESSION['error'] = "Email đã tồn tại!";
        $check_email_stmt->close();
        header("Location: quanly_nguoidung.php");
        exit();
    }
    $check_email_stmt->close();

    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $role = 'User';

    if (!empty($admin_code)) {
        $admin_code_valid = "ABC123";
        if ($admin_code === $admin_code_valid) {
            $role = 'Admin';
        } else {
            $_SESSION['error'] = "Mã xác nhận admin không hợp lệ!";
            header("Location: quanly_nguoidung.php");
            exit();
        }
    }

    try {
        $stmt = $ketnoi->prepare("INSERT INTO NGUOIDUNG 
            (TEN_DN, TEN_DAY_DU, MK_HASH, VAI_TRO, EMAIL, SDT, DIA_CHI, GIOI_TINH, NGAY_SINH) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param(
            "sssssssss",
            $username,
            $fullname,
            $password_hash,
            $role,
            $email,
            $phone,
            $address,
            $gender,
            $birthday
        );

        if ($stmt->execute()) {
            $_SESSION['success'] = "Thêm người dùng thành công!";
        } else {
            $_SESSION['error'] = "Lỗi khi thêm người dùng: " . $stmt->error;
        }
        $stmt->close();

    } catch (Exception $e) {
        $_SESSION['error'] = "Lỗi database: " . $e->getMessage();
    }

    header("Location: quanly_nguoidung.php");
    exit();
}

$sql = "SELECT * FROM NGUOIDUNG ORDER BY MA_ND";
$result = $ketnoi->query($sql);
if (!$result) {
    die("Lỗi truy vấn: " . $ketnoi->error);
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8" />
    <title>Quản lý người dùng</title>
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
        .button-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px;
            position: relative;
        }

        .add-user-btn {
            background-color: rgb(58, 83, 124);
            color: white;
            padding: 10px 15px;
            border-radius: 20px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .add-user-btn:hover {
            background-color: rgb(59, 96, 155);
        }

        .logout-btn {
            text-decoration: none;
            background-color: rgb(161, 18, 18);
            color: white;
            font-weight: bold;
            padding: 10px 15px;
            border-radius: 20px;
            transition: background-color 0.3s ease;
        }

        .logout-btn:hover {
            background-color: rgb(198, 31, 12);
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

        tbody td.actions {
            display: flex;
            justify-content: center;
            gap: 12px;
        }

        .gender-display {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 14px;
        }

        .gender-male {
            background-color: #e3f2fd;
            color: rgba(116, 169, 255, 1);
            border: 1px solid #bbdefb;
        }

        .gender-female {
            background-color: #fce4ec;
            color: rgb(255, 59, 137);
            border: 1px solid #f8bbd9;
        }

        .gender-icon {
            font-size: 18px;
        }

        a.edit-btn,
        a.delete-link {
            padding: 6px 12px;
            border-radius: 20px;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }

        a.edit-btn {
            background-color: rgb(57, 127, 220);
            color: white;
        }

        a.edit-btn:hover {
            background-color: rgb(22, 101, 154);
        }

        a.delete-link {
            background-color: rgb(196, 0, 0);
            color: white;
        }

        a.delete-link:hover {
            background-color: rgb(151, 15, 0);
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5); /* Nền mờ vừa phải */
            justify-content: center;
            align-items: center;
            padding: 20px;
            box-sizing: border-box;
        }

        .modal-content {
            background-color: #fff;
            padding: 25px 35px;
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            width: 600px;
            max-width: 95%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            animation: modalAppear 0.3s ease forwards; /* Hiệu ứng mượt hơn */
            border: 1px solid #e0e0e0;
        }

        @keyframes modalAppear {
            0% {
                opacity: 0;
                transform: scale(0.9) translateY(-20px);
            }
            100% {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .modal h3 {
            text-align: center;
            margin: 0 0 25px 0;
            font-size: 1.8rem;
            color: rgb(58, 83, 124);
            font-weight: bold;
        }

        .close-btn {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 28px;
            font-weight: bold;
            color: #999;
            cursor: pointer;
            transition: color 0.3s ease;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .close-btn:hover {
            color: #e74c3c;
            background-color: #f8f9fa;
        }

        .modal form {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .form-row {
            display: flex;
            margin-bottom: 6px;
            align-items: center;
            gap: 10px;
        }

        .form-row label {
            flex: 0 0 160px;
            font-weight: bold;
            color: #34495e;
            font-size: 16px;
            text-align: left;
            margin: 0;
        }

        .form-row .form-input {
            flex: 1;
            height: 40px;
            padding: 12px 18px;
            border: 1.5px solid #e1e8ed;
            border-radius: 20px;
            font-size: 14px;
            color: #2c3e50;
            transition: all 0.3s ease;
            box-sizing: border-box;
            background-color: #fafbfc;
        }

        .form-row .form-input:focus {
            border-color: rgb(58, 83, 124);
            outline: none;
            box-shadow: 0 0 0 3px rgba(58, 83, 124, 0.1);
            background-color: #fff;
        }

        .form-row.required label::after {
            content: " *";
            color: #e74c3c;
            font-weight: bold;
        }

        .form-buttons {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .form-buttons button {
            padding: 10px 20px;
            font-size: 15px;
            border: none;
            border-radius: 20px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 120px;
        }

        .btn-primary {
            background: linear-gradient(135deg, rgb(58, 83, 124), rgb(45, 65, 95));
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, rgb(45, 65, 95), rgb(35, 50, 75));
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(58, 83, 124, 0.3);
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
            transform: translateY(-1px);
        }

        .btn-danger {
            background-color: #e74c3c;
            color: white;
        }

        .btn-danger:hover {
            background-color: #c0392b;
            transform: translateY(-1px);
        }

        #deleteConfirmModal .modal-content {
            width: 400px;
            text-align: center;
            animation: modalAppear 0.3s ease forwards; 
        }

        #deleteConfirmModal h3 {
            font-size: 20px;
            font-weight: bold;
            color: #e74c3c;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        #deleteConfirmModal p {
            font-size: 16px;
            margin-bottom: 25px;
            color: #555;
            line-height: 1.6;
        }

        #deleteConfirmModal .form-buttons {
            justify-content: center;
            gap: 20px;
        }

        @media (max-width: 768px) {
            .button-bar {
                flex-direction: column;
                gap: 15px;
                min-height: 100px;
                padding: 10px 0;
            }

            .alert {
                position: static;
                transform: none;
                margin: 10px 0;
                text-align: center;
            }

            .add-user-btn,
            .logout-btn {
                width: 100%;
                text-align: center;
                margin: 0;
            }

            .modal {
                padding: 10px;
            }

            .modal-content {
                width: 100%;
                padding: 20px;
                max-height: 95vh;
            }

            .form-row {
                flex-direction: column;
                align-items: stretch;
                gap: 8px;
            }

            .form-row label {
                flex: none;
                text-align: left;
                font-size: 0.9rem;
            }

            .form-buttons {
                flex-direction: column;
                gap: 10px;
            }

            .form-buttons button {
                width: 100%;
                min-width: auto;
            }
        }

        .modal-content::-webkit-scrollbar {
            width: 6px;
        }

        .modal-content::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .modal-content::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }

        .modal-content::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
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
        <h1>QUẢN LÝ NGƯỜI DÙNG</h1>
    </header>
    <a href="quantrihethong.php" class="back-btn">&#8592; Dashboard</a>

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
        <a href="javascript:void(0)" id="openAddUser" class="add-user-btn">+ Thêm người dùng</a>
        <!-- <a href="admin_logout.php" class="logout-btn">Đăng xuất</a> -->
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Tên đăng nhập</th>
                <th>Họ và tên</th>
                <th>Email</th>
                <th>Số điện thoại</th>
                <th>Địa chỉ</th>
                <th>Giới tính</th>
                <th>Ngày sinh</th>
                <th>Chức năng</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr data-user='<?php echo json_encode($row, JSON_HEX_APOS | JSON_UNESCAPED_UNICODE); ?>'>
                    <td><?php echo htmlspecialchars($row['MA_ND']); ?></td>
                    <td><?php echo htmlspecialchars($row['TEN_DN']); ?></td>
                    <td><?php echo htmlspecialchars($row['TEN_DAY_DU']); ?></td>
                    <td><?php echo htmlspecialchars($row['EMAIL']); ?></td>
                    <td><?php echo htmlspecialchars($row['SDT'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['DIA_CHI'] ?? ''); ?></td>
                    <td>
                        <?php
                        $gender = strtolower(trim($row['GIOI_TINH'] ?? ''));
    
                        if ($gender === 'nam' || $gender === 'male') {
                            echo '<span class="gender-display gender-male"><span class="gender-icon">♂</span>Nam</span>';
                        } elseif ($gender === 'nữ' || $gender === 'female') {
                            echo '<span class="gender-display gender-female"><span class="gender-icon">♀</span>Nữ</span>';
                        } else {
                            echo '<span class="gender-display">' . htmlspecialchars($row['GIOI_TINH'] ?? 'Chưa xác định') . '</span>';
                        }
                        ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['NGAY_SINH'] ?? ''); ?></td>
                    <td class="actions">
                        <a href="javascript:void(0)" class="edit-btn">Sửa</a>
                        <a href="javascript:void(0)" class="delete-link"
                            data-user-id="<?php echo urlencode($row['MA_ND']); ?>">Xóa</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div id="deleteConfirmModal" class="modal">
        <div class="modal-content" style="width: 400px; text-align: center;">
            <span class="close-btn" id="closeDeleteConfirm">&times;</span>
            <h3 style="color: #e74c3c; margin-bottom: 16px;">⚠️ Xác nhận xóa người dùng</h3>
            <p style="font-size: 16px; margin-bottom: 25px; color: #555;">
                Bạn có chắc chắn muốn xóa người dùng này không?<br>
                <strong id="deleteUserName" style="color: #2c3e50;"></strong>
            </p>
            <div class="form-buttons" style="justify-content: center; gap: 20px;">
                <button type="button" class="btn-secondary" id="cancelDelete">Hủy</button>
                <button type="button" class="btn-danger" id="confirmDelete">Xóa</button>
            </div>
        </div>
    </div>

    <div id="modalAddUser" class="modal">
        <div class="modal-content">
            <span class="close-btn" id="closeAddUser">&times;</span>
            <h3>Thêm người dùng mới</h3>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add" />

                <div class="form-row required">
                    <label>Tên đăng nhập:</label>
                    <input type="text" name="username" class="form-input" required />
                </div>

                <div class="form-row required">
                    <label>Họ và tên:</label>
                    <input type="text" name="fullname" class="form-input" required />
                </div>

                <div class="form-row required">
                    <label>Email:</label>
                    <input type="email" name="email" class="form-input" required />
                </div>

                <div class="form-row">
                    <label>Số điện thoại:</label>
                    <input type="text" name="phone" class="form-input" />
                </div>

                <div class="form-row">
                    <label>Địa chỉ:</label>
                    <input type="text" name="address" class="form-input" />
                </div>

                <div class="form-row">
                    <label>Giới tính:</label>
                    <select name="gender" class="form-input" required>
                        <option value="Nam">Nam</option>
                        <option value="Nữ">Nữ</option>
                    </select>
                </div>

                <div class="form-row">
                    <label>Ngày sinh:</label>
                    <input type="date" name="birthday" class="form-input" />
                </div>

                <div class="form-row required">
                    <label>Mật khẩu:</label>
                    <input type="password" name="password" class="form-input" required />
                </div>

                <div class="form-row">
                    <label>Mã Admin:</label>
                    <input type="text" name="admin_code" class="form-input" placeholder="Nhập mã để cấp quyền ADMIN" />
                </div>

                <div class="form-buttons">
                    <button type="button" class="btn-secondary" id="cancelAdd">Hủy</button>
                    <button type="submit" class="btn-primary">Thêm người dùng</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalEditUser" class="modal">
        <div class="modal-content">
            <span class="close-btn" id="closeEditUser">&times;</span>
            <h3>Sửa thông tin người dùng</h3>
            <form method="POST" action="">
                <input type="hidden" name="action" value="edit" />
                <input type="hidden" name="user_id" id="edit_user_id" />

                <div class="form-row required">
                    <label>Họ và tên:</label>
                    <input type="text" name="fullname" id="edit_fullname" class="form-input" required />
                </div>

                <div class="form-row required">
                    <label>Email:</label>
                    <input type="email" name="email" id="edit_email" class="form-input" required />
                </div>

                <div class="form-row">
                    <label>Số điện thoại:</label>
                    <input type="text" name="phone" id="edit_phone" class="form-input" />
                </div>

                <div class="form-row">
                    <label>Địa chỉ:</label>
                    <input type="text" name="address" id="edit_address" class="form-input" />
                </div>

                <div class="form-row">
                    <label>Giới tính:</label>
                    <select name="gender" id="edit_gender_select" class="form-input">
                        <option value="Nam">Nam</option>
                        <option value="Nữ">Nữ</option>
                    </select>
                </div>

                <div class="form-row">
                    <label>Ngày sinh:</label>
                    <input type="date" name="birthday" id="edit_birthday" class="form-input" />
                </div>

                <div class="form-row">
                    <label>Mã Admin:</label>
                    <input type="text" id="edit_admin_code" name="admin_code" class="form-input"
                        placeholder="Chỉ nhập nếu tạo tài khoản Admin" />
                </div>

                <div class="form-buttons">
                    <button type="button" class="btn-secondary" id="cancelEdit">Hủy</button>
                    <button type="submit" class="btn-primary">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            console.log('DOM loaded, initializing...'); // Debug log

            const modalAddUser = document.getElementById('modalAddUser');
            const modalEditUser = document.getElementById('modalEditUser');
            const openAddUserBtn = document.getElementById('openAddUser');
            const deleteConfirmModal = document.getElementById('deleteConfirmModal');
            const deleteUserNameSpan = document.getElementById('deleteUserName');
            const confirmDeleteBtn = document.getElementById('confirmDelete');
            const cancelDeleteBtn = document.getElementById('cancelDelete');
            const closeDeleteConfirmBtn = document.getElementById('closeDeleteConfirm');

            let deleteUserId = null; // Lưu ID user cần xóa
            let deleteUserName = null; // Lưu tên user cần xóa

            // Hàm đóng modal xác nhận xóa
            const closeDeleteModal = () => {
                deleteConfirmModal.style.display = 'none';
                document.body.style.overflow = 'auto';
                deleteUserId = null;
                deleteUserName = null;
            };

            // Xử lý tất cả các link xóa
            document.querySelectorAll('.delete-link').forEach(deleteLink => {
                deleteLink.addEventListener('click', function (e) {
                    e.preventDefault(); // Ngăn chặn redirect ngay lập tức

                    // Lấy thông tin user từ row
                    const row = this.closest('tr');
                    const userData = JSON.parse(row.getAttribute('data-user'));

                    deleteUserId = userData.MA_ND;
                    deleteUserName = userData.TEN_DAY_DU || userData.TEN_DN;

                    // Hiển thị tên user trong modal
                    deleteUserNameSpan.textContent = deleteUserName;

                    // Hiển thị modal
                    deleteConfirmModal.style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                });
            });

            // Xử lý nút xác nhận xóa
            confirmDeleteBtn.addEventListener('click', () => {
                if (deleteUserId) {
                    // Hiển thị loading
                    confirmDeleteBtn.innerHTML = '<i class="loading-spinner"></i> Đang xóa...';
                    confirmDeleteBtn.disabled = true;

                    // Redirect đến URL xóa
                    window.location.href = `?delete=${encodeURIComponent(deleteUserId)}`;
                }
            });

            // Xử lý nút hủy
            cancelDeleteBtn.addEventListener('click', closeDeleteModal);

            // Xử lý nút đóng
            closeDeleteConfirmBtn.addEventListener('click', closeDeleteModal);

            // Đóng modal khi click outside
            deleteConfirmModal.addEventListener('click', (e) => {
                if (e.target === deleteConfirmModal) {
                    closeDeleteModal();
                }
            });

            // Đóng modal bằng phím Escape
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && deleteConfirmModal.style.display === 'flex') {
                    closeDeleteModal();
                }
            });

            console.log('Elements found:', {
                modalAddUser: !!modalAddUser,
                modalEditUser: !!modalEditUser,
                openAddUserBtn: !!openAddUserBtn
            });

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

            // Hàm đóng modal
            const closeModal = (modal) => {
                if (modal) {
                    modal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                    const form = modal.querySelector('form');
                    if (form && form.reset) {
                        form.reset();
                    }
                }
            };

            // Mở modal thêm người dùng
            if (openAddUserBtn) {
                openAddUserBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    console.log('Add user button clicked'); // Debug log
                    if (modalAddUser) {
                        modalAddUser.style.display = 'flex';
                        document.body.style.overflow = 'hidden';
                        console.log('Modal should be visible now'); // Debug log
                    }
                });
            }

            // Đóng modal thêm người dùng
            const closeAddUserBtn = document.getElementById('closeAddUser');
            if (closeAddUserBtn) {
                closeAddUserBtn.addEventListener('click', () => {
                    closeModal(modalAddUser);
                });
            }

            const cancelAddBtn = document.getElementById('cancelAdd');
            if (cancelAddBtn) {
                cancelAddBtn.addEventListener('click', () => {
                    closeModal(modalAddUser);
                });
            }

            // Click outside modal để đóng
            if (modalAddUser) {
                modalAddUser.addEventListener('click', (e) => {
                    if (e.target === modalAddUser) {
                        closeModal(modalAddUser);
                    }
                });
            }

            // Xử lý nút sửa
            document.querySelectorAll('.edit-btn').forEach(btn => {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    console.log('Edit button clicked'); // Debug log

                    try {
                        const row = this.closest('tr');
                        const dataUser = row.getAttribute('data-user');
                        const data = JSON.parse(dataUser);

                        console.log('User data:', data); // Debug log

                        // Điền dữ liệu vào form
                        document.getElementById('edit_user_id').value = data.MA_ND || '';
                        document.getElementById('edit_fullname').value = data.TEN_DAY_DU || '';
                        document.getElementById('edit_email').value = data.EMAIL || '';
                        document.getElementById('edit_phone').value = data.SDT || '';
                        document.getElementById('edit_address').value = data.DIA_CHI || '';
                        document.getElementById('edit_birthday').value = data.NGAY_SINH || '';

                        // Set giới tính
                        const genderSelect = document.getElementById('edit_gender_select');
                        if (genderSelect) {
                            const gender = (data.GIOI_TINH || '').toLowerCase();
                            genderSelect.value = ['nam', 'male'].includes(gender) ? 'Nam' : 'Nữ';
                        }

                        // Hiển thị modal
                        if (modalEditUser) {
                            modalEditUser.style.display = 'flex';
                            document.body.style.overflow = 'hidden';
                            console.log('Edit modal should be visible now'); // Debug log
                        }
                    } catch (error) {
                        console.error('Error parsing user data:', error);
                        alert('Có lỗi khi tải thông tin người dùng');
                    }
                });
            });

            // Đóng modal sửa người dùng
            const closeEditUserBtn = document.getElementById('closeEditUser');
            if (closeEditUserBtn) {
                closeEditUserBtn.addEventListener('click', () => {
                    closeModal(modalEditUser);
                });
            }

            const cancelEditBtn = document.getElementById('cancelEdit');
            if (cancelEditBtn) {
                cancelEditBtn.addEventListener('click', () => {
                    closeModal(modalEditUser);
                });
            }

            // Click outside modal sửa để đóng
            if (modalEditUser) {
                modalEditUser.addEventListener('click', (e) => {
                    if (e.target === modalEditUser) {
                        closeModal(modalEditUser);
                    }
                });
            }

            // Đóng modal bằng phím Escape
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    if (modalAddUser && modalAddUser.style.display === 'flex') {
                        closeModal(modalAddUser);
                    }
                    if (modalEditUser && modalEditUser.style.display === 'flex') {
                        closeModal(modalEditUser);
                    }
                }
            });

            // Validation form
            const validateForm = (isEditMode = false) => {
                let email, phone, name;

                if (isEditMode) {
                    email = document.getElementById('edit_email')?.value || '';
                    phone = document.getElementById('edit_phone')?.value || '';
                    name = document.getElementById('edit_fullname')?.value || '';
                } else {
                    email = document.querySelector('input[name="email"]')?.value || '';
                    phone = document.querySelector('input[name="phone"]')?.value || '';
                    name = document.querySelector('input[name="fullname"]')?.value || '';
                }

                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                const phoneRegex = /^(0|\+84)[3|5|7|8|9][0-9]{8}$/;

                if (email && !emailRegex.test(email)) {
                    alert('Email không hợp lệ!');
                    return false;
                }
                if (phone && !phoneRegex.test(phone)) {
                    alert('Số điện thoại không hợp lệ!');
                    return false;
                }
                if (!name.trim()) {
                    alert('Vui lòng nhập họ tên!');
                    return false;
                }

                return true;
            };

            // Validation khi submit form
            const addForm = modalAddUser?.querySelector('form');
            if (addForm) {
                addForm.addEventListener('submit', (e) => {
                    if (!validateForm(false)) {
                        e.preventDefault();
                    }
                });
            }

            const editForm = modalEditUser?.querySelector('form');
            if (editForm) {
                editForm.addEventListener('submit', (e) => {
                    if (!validateForm(true)) {
                        e.preventDefault();
                    }
                });
            }

            console.log('User management initialized successfully');
        });
    </script>
</body>

</html>