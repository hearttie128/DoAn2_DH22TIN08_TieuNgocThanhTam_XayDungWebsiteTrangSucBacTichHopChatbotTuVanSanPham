<?php
session_start();

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'qlchdt';

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
mysqli_set_charset($conn, "utf8mb4");
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liên hệ</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
        }

        header {
            background-color: #4a92e4;
            color: white;
            padding: 10px;
            text-align: center;
        }

        nav ul {
            list-style-type: none;
            padding: 0;
        }

        nav ul li {
            font-size: 18px;
            display: inline;
            margin: 0 10px;
        }

        nav ul li a {
            color: white;
            text-decoration: none;
            font-weight: bold;
        }

        nav ul li a:hover {
            color: #343639;
        }

        main {
            padding: 20px;
        }

        h2 {
            color: #4a92e4;
        }

        section {
            background-color: white;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
        }

        .contact-info p {
            margin: 8px 0;
        }

        iframe {
            width: 100%;
            height: 400px;
            border: 0;
            border-radius: 5px;
        }

        form input,
        form textarea {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button {
            background-color: #4a92e4;
            color: white;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            font-weight: bold;
            border-radius: 5px;
        }

        button:hover {
            background-color: #3d98ff;
        }

        footer {
            text-align: center;
            padding: 10px 0;
            background-color: #d9d9d9;
            font-size: 12px;
        }
    </style>
</head>

<body>

    <header>
        <h1>LIÊN HỆ VỚI CHÚNG TÔI</h1>
        <nav>
            <ul>
                <li><a href="trangchu.php">Trang chủ</a></li>
                <li><a href="#contact">Thông tin liên hệ</a></li>
                <li><a href="#map">Bản đồ</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <!-- Thông tin liên hệ -->
        <section id="contact">
            <h2>Thông tin liên hệ</h2>
            <div class="contact-info">
                <p><strong>Công ty:</strong> Công ty cổ phần HEARTTIE</p>
                <p><strong>Địa chỉ:</strong> 128 Nguyễn Văn Cừ (nối dài), Phường An Bình, Quận Ninh Kiều, TP. Cần Thơ</p>
                <p><strong>Điện thoại:</strong> 0794 385 228</p>
                <p><strong>Email:</strong>
                    <a href="mailto:tuvan@hearttie.vn">tuvan@hearttie.vn</a>
                </p>
                <p><strong>Giờ làm việc:</strong> 8h – 20h (Thứ Hai – Chủ Nhật)</p>
            </div>
        </section>

        <!-- Form liên hệ -->
        <section>
            <h2>Gửi yêu cầu liên hệ</h2>
            <form onsubmit="alert('Gửi liên hệ thành công!'); return false;">
                <input type="text" placeholder="Họ và tên" required>
                <input type="email" placeholder="Email" required>
                <input type="tel" placeholder="Số điện thoại">
                <textarea rows="5" placeholder="Nội dung liên hệ..." required></textarea>
                <button type="submit">Gửi liên hệ</button>
            </form>
        </section>

        <!-- Google Map -->
        <section id="map">
            <h2>Bản đồ chỉ đường</h2>
            <iframe
                src="https://www.google.com/maps?q=128%20Nguyễn%20Văn%20Cừ%20nối%20dài%20Cần%20Thơ&output=embed"
                loading="lazy">
            </iframe>
        </section>
    </main>

    <footer>
        <p>© 2020 Công ty cổ phần HEARTTIE. GPDKKD: 0303217354 do sở Cần Thơ cấp ngày 02/01/2015. GPMXH:
                21/GP-BTTTT do Bộ Thông Tin và Truyền Thông cấp ngày 11/01/2021. Địa chỉ: 128, Nguyễn Văn Cừ (nối dài),
                Phường An Bình, Quận Ninh Kiều, Thành Phố Cần Thơ.
                <br />
                Chịu trách nhiệm nội dung: Tiêu Ngọc Thanh Tâm. Tất cả
                các quyền được bảo
                lưu.
            </p>
    </footer>
</body>

</html>
