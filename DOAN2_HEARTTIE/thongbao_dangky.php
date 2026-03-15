<?php
// register_success.php
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng ký thành công</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .success-box {
            background: #fff;
            padding: 40px;
            width: 420px;
            text-align: center;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .success-icon {
            font-size: 60px;
            color: #2ecc71;
            margin-bottom: 15px;
        }

        h2 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        p {
            color: #555;
            margin-bottom: 25px;
            font-size: 15px;
        }

        .btn {
            display: block;
            width: 100%;
            padding: 12px;
            margin-bottom: 10px;
            border-radius: 6px;
            border: none;
            font-size: 15px;
            cursor: pointer;
            text-decoration: none;
        }

        .btn-login {
            background: #3498db;
            color: #fff;
        }

        .btn-home {
            background: #ecf0f1;
            color: #333;
        }

        .btn:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>

<div class="success-box">
    <div class="success-icon">✔</div>

    <h2>ĐĂNG KÝ THÀNH CÔNG!</h2>

    <p>
        Tài khoản của bạn đã được tạo thành công.  
        Vui lòng đăng nhập để tiếp tục sử dụng hệ thống.
    </p>

    <a href="dangnhap_user.php" class="btn btn-login">
        Đăng nhập ngay
    </a>

    <a href="trangchu.php" class="btn btn-home">
        Về trang chủ
    </a>
</div>

</body>
</html>
