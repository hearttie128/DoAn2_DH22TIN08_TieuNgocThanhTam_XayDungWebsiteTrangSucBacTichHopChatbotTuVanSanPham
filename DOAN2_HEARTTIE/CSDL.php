<?php
ob_start();
session_start();

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'qlchdt';
$port = 3306;

try {
    $conn = new mysqli($host, $username, $password, "", $port);
    if ($conn->connect_error) {
        throw new Exception('Kết nối MySQL thất bại: ' . $conn->connect_error);
    }

    // Xóa database cũ hoàn toàn và tạo mới
    $conn->query("DROP DATABASE IF EXISTS $database");
    $conn->query("CREATE DATABASE $database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->select_db($database);
    mysqli_set_charset($conn, "utf8mb4");

    // Tạo bảng NGUOIDUNG
    $create_nguoidung = "
    CREATE TABLE NGUOIDUNG (
        MA_ND INT AUTO_INCREMENT PRIMARY KEY,
        TEN_DN VARCHAR(30) UNIQUE NOT NULL,
        TEN_DAY_DU VARCHAR(100),
        MK_HASH VARCHAR(255) NOT NULL,
        VAI_TRO VARCHAR(20) NOT NULL DEFAULT 'User',
        EMAIL VARCHAR(100) UNIQUE NOT NULL,
        SDT VARCHAR(20),
        DIA_CHI VARCHAR(200),
        GIOI_TINH ENUM('Nam', 'Nữ') NOT NULL,
        NGAY_SINH DATE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    // Tạo bảng DANHMUC
    $create_danhmuc = "
    CREATE TABLE DANHMUC (
        MA_DM INT AUTO_INCREMENT PRIMARY KEY,
        TEN_DM VARCHAR(100) UNIQUE NOT NULL,
        MO_TA VARCHAR(255)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    // Tạo bảng SANPHAM
    $create_sanpham = "
    CREATE TABLE SANPHAM (
        MA_SP INT AUTO_INCREMENT PRIMARY KEY,
        TEN_SP VARCHAR(255) NOT NULL,
        MA_DM INT NOT NULL,
        GIA INT NOT NULL,
        HINH_ANH VARCHAR(512),
        MO_TA VARCHAR(255),
        SO_LUONG INT NOT NULL DEFAULT 0,
        TRANG_THAI VARCHAR(20) DEFAULT 'Đang bán',
        MAN_HINH VARCHAR(100),
        CAMERA_SAU VARCHAR(100),
        CAMERA_TRUOC VARCHAR(50),
        CHIP VARCHAR(50),
        RAM VARCHAR(20),
        BO_NHO VARCHAR(20),
        PIN VARCHAR(20),
        HE_DIEU_HANH VARCHAR(50),
        GIAM_GIA INT DEFAULT 0,
        FOREIGN KEY (MA_DM) REFERENCES DANHMUC(MA_DM) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    // Tạo bảng GIOHANG
    $create_giohang = "
    CREATE TABLE GIOHANG (
        MA_GH INT AUTO_INCREMENT PRIMARY KEY,
        MA_ND INT NOT NULL,
        MA_SP INT NOT NULL,
        GIA INT NOT NULL,
        SO_LUONG INT NOT NULL CHECK (SO_LUONG > 0),
        UNIQUE KEY uk_nd_sp (MA_ND, MA_SP),
        FOREIGN KEY (MA_ND) REFERENCES NGUOIDUNG(MA_ND) ON DELETE CASCADE,
        FOREIGN KEY (MA_SP) REFERENCES SANPHAM(MA_SP) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    // Tạo bảng DONHANG
    $create_donhang = "
    CREATE TABLE DONHANG (
        MA_DH INT AUTO_INCREMENT PRIMARY KEY,
        MA_ND INT NOT NULL,
        TONG_TIEN INT NOT NULL,
        TRANG_THAI VARCHAR(50) DEFAULT 'Chờ xử lý',
        PTTT VARCHAR(50),
        DIA_CHI_GIAO VARCHAR(255),
        SDT VARCHAR(20),
        GHI_CHU VARCHAR(300),
        NGAY_TAO DATETIME DEFAULT CURRENT_TIMESTAMP,
        NGAY_CAP_NHAT DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (MA_ND) REFERENCES NGUOIDUNG(MA_ND) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    // Tạo bảng CHITIETDONHANG
    $create_chitietdonhang = "
    CREATE TABLE CHITIETDONHANG (
        MA_CTDH INT AUTO_INCREMENT PRIMARY KEY,
        MA_DH INT NOT NULL,
        MA_SP INT NOT NULL,
        TEN_SP VARCHAR(255),
        SO_LUONG INT NOT NULL CHECK (SO_LUONG > 0),
        GIA INT NOT NULL,
        THANH_TIEN INT NOT NULL,
        FOREIGN KEY (MA_DH) REFERENCES DONHANG(MA_DH) ON DELETE CASCADE,
        FOREIGN KEY (MA_SP) REFERENCES SANPHAM(MA_SP) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    // Tạo bảng THANHTOAN - Thêm cột MA_GIAO_DICH cho VietQR
    $create_thanhtoan = "
    CREATE TABLE THANHTOAN (
        MA_TT INT AUTO_INCREMENT PRIMARY KEY,
        MA_DH INT NOT NULL, 
        PTTT VARCHAR(50) NOT NULL, 
        SO_TIEN INT NOT NULL,
        NGAY_TT DATETIME DEFAULT CURRENT_TIMESTAMP,
        TRANG_THAI VARCHAR(50) DEFAULT 'Chưa thanh toán',
        MA_GIAO_DICH VARCHAR(100) DEFAULT NULL,
        THONG_TIN_TK TEXT NULL COMMENT 'Thông tin tài khoản ngân hàng',
        FOREIGN KEY (MA_DH) REFERENCES DONHANG(MA_DH) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    // Tạo bảng HOADON
    $create_hoadon = "
    CREATE TABLE HOADON (
        MA_HD INT AUTO_INCREMENT PRIMARY KEY,
        MA_DH INT NOT NULL,
        MA_ND INT NOT NULL,
        NGAY_XUAT DATETIME DEFAULT CURRENT_TIMESTAMP,
        THUE INT NOT NULL,
        TONG_TIEN INT NOT NULL,
        PTTT VARCHAR(50),
        TRANG_THAI VARCHAR(50) DEFAULT 'Chưa thanh toán',
        FOREIGN KEY (MA_DH) REFERENCES DONHANG(MA_DH) ON DELETE CASCADE,
        FOREIGN KEY (MA_ND) REFERENCES NGUOIDUNG(MA_ND) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    if (!$conn->query($create_nguoidung)) throw new Exception("Lỗi tạo bảng NGUOIDUNG");
    if (!$conn->query($create_danhmuc)) throw new Exception("Lỗi tạo bảng DANHMUC");
    if (!$conn->query($create_sanpham)) throw new Exception("Lỗi tạo bảng SANPHAM");
    if (!$conn->query($create_giohang)) throw new Exception("Lỗi tạo bảng GIOHANG");
    if (!$conn->query($create_donhang)) throw new Exception("Lỗi tạo bảng DONHANG");
    if (!$conn->query($create_chitietdonhang)) throw new Exception("Lỗi tạo bảng CHITIETDONHANG");
    if (!$conn->query($create_thanhtoan)) throw new Exception("Lỗi tạo bảng THANHTOAN");
    if (!$conn->query($create_hoadon)) throw new Exception("Lỗi tạo bảng HOADON");

    // Thêm admin mặc định
    $admin_password = password_hash("admin1208", PASSWORD_DEFAULT);
    $insert_admin = "INSERT INTO NGUOIDUNG (TEN_DN, TEN_DAY_DU, MK_HASH, VAI_TRO, EMAIL, SDT, GIOI_TINH, NGAY_SINH) 
                    VALUES ('admin', 'Quản Trị Viên', ?, 'Admin', 'hearttie128@gmail.com', '0794385228', 'Nữ', '2004-08-12')";
    $stmt = $conn->prepare($insert_admin);
    $stmt->bind_param("s", $admin_password);
    if (!$stmt->execute()) throw new Exception("Lỗi thêm admin");

    // Thêm danh mục
    $insert_danhmuc = "
        INSERT INTO DANHMUC (TEN_DM, MO_TA) VALUES
        ('Dây chuyền', 'Dây chuyền bạc nữ cao cấp, đính đá CZ và kim cương'),
        ('Lắc', 'Lắc tay, lắc chân bạc nữ sang trọng'),
        ('Nhẫn', 'Nhẫn bạc nữ đính đá, nhẫn đôi cặp'),
        ('Bông tai', 'Bông tai bạc nữ thời trang, đính đá CZ')
    ";
    if (!$conn->query($insert_danhmuc)) throw new Exception("Lỗi thêm danh mục");

    // Lấy ID danh mục
    $categoryDayChuyen = 1;
    $categoryLac = 2;
    $categoryNhan = 3;
    $categoryBongTai = 4;

    // Thêm sản phẩm trang sức bạc
    $insert_sanpham = "
        INSERT INTO SANPHAM (TEN_SP, MA_DM, GIA, HINH_ANH, MO_TA, SO_LUONG, TRANG_THAI, MAN_HINH, CAMERA_SAU, CAMERA_TRUOC, CHIP, RAM, BO_NHO, PIN, HE_DIEU_HANH, GIAM_GIA) VALUES
        ('Dây chuyền bạc nữ đính đá CZ cá tiên', $categoryDayChuyen, 1570000, 'https://lili.vn/wp-content/uploads/2021/12/Day-chuyen-bac-nu-phong-cach-co-trang-CZ-LILI_831944_3.jpg', 'TIE_831944', 15, 'Đang bán', '', '', '', '', '', '', '', '', 12),
        ('Lắc chân bạc nữ đính đá CZ hình cỏ 4 lá Mildred', $categoryLac, 2190000, 'https://lili.vn/wp-content/uploads/2022/09/Lac-chan-bac-nu-dinh-da-CZ-hinh-co-4-la-Mildred-LILI_763298_2.jpg', 'TIE_763298', 12, 'Đang bán', '', '', '', '', '', '', '', '', 20),
        ('Lắc tay bạc nữ đính pha lê Swarovski trái tim của biển', $categoryLac, 1840000, 'https://lili.vn/wp-content/uploads/2020/11/vong-tay-bac-925-dinh-pha-le-swarovski-3.jpg', 'TIE_579467', 10, 'Đang bán', '', '', '', '', '', '', '', '', 18),
        ('Bông tai bạc Ý S925 nữ mạ bạch kim đính đá CZ', $categoryBongTai, 1380000, 'https://lili.vn/wp-content/uploads/2021/12/Bong-tai-bac-Y-S925-nu-ma-bach-kim-dinh-da-CZ-hinh-trai-tim-LILI_991582_10.jpg', 'TIE_991582', 20, 'Đang bán', '', '', '', '', '', '', '', '', 9),
        ('Dây chuyền bạc nữ đính kim cương tự nhiên', $categoryDayChuyen, 1360000, 'https://lili.vn/wp-content/uploads/2022/04/Day-chuyen-bac-nu-dinh-kim-cuong-tu-nhieu-co-4-la-LILI_832459_2.jpg', 'TIE_832459', 18, 'Đang bán', '', '', '', '', '', '', '', '', 11),
        ('Lắc tay bạc Ta S999 nữ cỏ 4 lá cách điệu', $categoryLac, 2450000, 'https://lili.vn/wp-content/uploads/2021/11/Lac-tay-bac-nu-co-4-la-cach-dieu-LILI_661577_6-768x768.jpg', 'TIE_661577', 8, 'Đang bán', '', '', '', '', '', '', '', '', 10),
        ('Bông tai bạc nữ tròn đính đá CZ hình bông hoa 5 cánh', $categoryBongTai, 1320000, 'https://lili.vn/wp-content/uploads/2021/12/Bong-tai-bac-nu-tron-hinh-bong-hoa-5-canh-Cute-LILI_749976_2.jpg', 'TIE_749976', 25, 'Đang bán', '', '', '', '', '', '', '', '', 13),
        ('Nhẫn bạc nữ đính đá CZ hoa bướm', $categoryNhan, 1310000, 'https://lili.vn/wp-content/uploads/2022/08/Nhan-bac-nu-dinh-da-CZ-hoa-buom-LILI_661591_5.jpg', 'TIE_661591', 22, 'Đang bán', '', '', '', '', '', '', '', '', 10),
        ('Dây chuyền bạc nữ liền mặt mạ vàng đính đá CZ', $categoryDayChuyen, 2110000, 'https://lili.vn/wp-content/uploads/2021/01/Day-chuyen-bac-nu-lien-mat-ma-vang-dinh-da-CZ-trai-tim-Double-LILI_583553_50-768x768.jpg', 'TIE_583553', 14, 'Đang bán', '', '', '', '', '', '', '', '', 11),
        ('Bông tai bạc nữ đính đá CZ hình những bông hoa', $categoryBongTai, 1290000, 'https://lili.vn/wp-content/uploads/2021/01/Bong-tai-bac-dinh-da-Zircon-hinh-nhung-bong-hoa-Luu-ly-LILI_148289-02.jpg', 'TIE_148289', 30, 'Đang bán', '', '', '', '', '', '', '', '', 8),
        ('Lắc tay bạc đính đá pha lê hình trái tim', $categoryLac, 2730000, 'https://lili.vn/wp-content/uploads/2020/12/Vong-tay-bac-dinh-da-pha-le-hinh-trai-tim-LILI_427425-05.jpg', 'TIE_427425', 12, 'Đang bán', '', '', '', '', '', '', '', '', 8),
        ('Nhẫn đôi bạc free size đính đá CZ hiệp sĩ và công chúa', $categoryNhan, 2440000, 'https://lili.vn/wp-content/uploads/2021/12/Nhan-doi-bac-hiep-si-va-cong-chua-dinh-da-CZ-LILI_819229_3.jpg', 'TIE_819229', 16, 'Đang bán', '', '', '', '', '', '', '', '', 10),
        ('Lắc tay bạc nữ cá tính mắt xích vuông trái tim', $categoryLac, 2160000, 'https://lili.vn/wp-content/uploads/2021/11/Lac-tay-bac-nu-ca-tinh-mat-xich-vuong-trai-tim-Strong-Heart-LILI_414788_3.jpg', 'TIE_414788', 18, 'Đang bán', '', '', '', '', '', '', '', '', 6),
        ('Lắc chân bạc nữ dạng hạt 2 tầng đính mèo thần tài', $categoryLac, 3440000, 'https://lili.vn/wp-content/uploads/2020/12/Lac-chan-bac-dang-hat-2-lop-dinh-meo-than-tai-LILI_631735-021.jpg', 'TIE_631735', 10, 'Đang bán', '', '', '', '', '', '', '', '', 7),
        ('Dây chuyền Choker bạc nữ Magic', $categoryDayChuyen, 2290000, 'https://lili.vn/wp-content/uploads/2021/08/Day-chuyen-Choker-bac-Magic-LILI_366642_2.jpg', 'TIE_366642', 15, 'Đang bán', '', '', '', '', '', '', '', '', 13),
        ('Dây chuyền đôi bạc đính đá CZ Forever Love', $categoryDayChuyen, 2990000, 'https://lili.vn/wp-content/uploads/2021/08/Day-chuyen-doi-bac-hinh-ca-heo-hong-Forever-Love-LILI_528145_1.jpg', 'TIE_528145', 12, 'Đang bán', '', '', '', '', '', '', '', '', 5)
    ";

    if (!$conn->query($insert_sanpham)) throw new Exception("Lỗi thêm sản phẩm");

    $conn->close();
    $_SESSION['db_setup_success'] = true;

    // Hiển thị thông báo thành công
    echo "<!DOCTYPE html>
    <html lang='vi'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Kết nối thành công</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
                background: linear-gradient(135deg,rgb(231, 238, 255),rgb(213, 236, 255));
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                padding: 20px;
            }
            .success-box {
                background: white;
                padding: 50px;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                text-align: center;
                max-width: 500px;
                animation: fadeIn 0.5s ease;
            }
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(-20px); }
                to { opacity: 1; transform: translateY(0); }
            }
            .icon {
                width: 80px;
                height: 80px;
                background: #4A92E4;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 30px;
                animation: scaleIn 0.5s ease 0.2s both;
            }
            @keyframes scaleIn {
                from { transform: scale(0); }
                to { transform: scale(1); }
            }
            .icon::before {
                content: '✓';
                color: white;
                font-size: 50px;
                font-weight: bold;
            }
            h1 {
                color: #333;
                font-size: 28px;
                margin-bottom: 15px;
            }
            p {
                color: #666;
                font-size: 16px;
                line-height: 1.6;
            }
        </style>
    </head>
    <body>
        <div class='success-box'>
            <div class='icon'></div>
            <h1>Kết nối thành công</h1>
            <p>Chuyển đến trang đăng nhập</p>
        </div>
    </body>
    </html>";

    header("refresh:2; url=dangnhap_admin.php");
    exit();

} catch (Exception $e) {
    echo "<!DOCTYPE html>
    <html lang='vi'>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #f5f5f5; padding: 20px; }
            .error-box { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); max-width: 500px; border-left: 5px solid #dc3545; }
            h2 { color: #dc3545; margin-bottom: 15px; }
            p { color: #666; line-height: 1.6; }
        </style>
    </head>
    <body>
        <div class='error-box'>
            <h2>❌ Lỗi khởi tạo CSDL</h2>
            <p>" . $e->getMessage() . "</p>
        </div>
    </body>
    </html>";
    if (isset($conn)) {
        $conn->close();
    }
}

ob_end_flush();
?>