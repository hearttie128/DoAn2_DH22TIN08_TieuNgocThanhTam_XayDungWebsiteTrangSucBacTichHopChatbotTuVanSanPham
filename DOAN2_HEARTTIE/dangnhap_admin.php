<?php
session_start();

$showSuccessModal = false;

if (isset($_POST['user'], $_POST['password'])) {
    $user = trim($_POST['user']);
    $pass = trim($_POST['password']);

    if ($user === '' || $pass === '') {
        $error = "Tên đăng nhập hoặc mật khẩu không được để trống!";
    } else {

        $host = 'localhost';
        $username = 'root';
        $password = '';
        $database = 'qlchdt';

        $ketnoi = new mysqli($host, $username, $password, $database);
        if ($ketnoi->connect_error) {
            $error = "Lỗi kết nối cơ sở dữ liệu: " . $ketnoi->connect_error . " (Mã lỗi: " . $ketnoi->connect_errno . ")";
        } else {
            $ketnoi->set_charset("utf8");

            $stmt = $ketnoi->prepare("SELECT * FROM NGUOIDUNG WHERE TEN_DN = ? AND MK_HASH = ?");
            if ($stmt === false) {
                die("Lỗi prepare(): " . $ketnoi->error);
            }

            $stmt->bind_param("ss", $user, $pass);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                $_SESSION['user'] = $user;
                $showSuccessModal = true;
            } else {
                $error = "Tên đăng nhập hoặc mật khẩu không đúng!";
            }

            $stmt->close();
            $ketnoi->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" rel="stylesheet" />
    <title>Đăng nhập</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f2f1f1ff;
            height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-container {
            background-color: #fff;
            padding: 40px 30px;
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
            min-height: 400px;
            width: 450px;
        }

        h2 {
            font-size: 32px;
            color: #3a537c;
            text-align: center;
            margin-bottom: 30px;
            margin-top: 5px;
        }

        .form-group {
            margin-bottom: 5px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        label {
            font-weight: bold;
            color: #444;
            margin-bottom: 6px;
            align-self: flex-start;
            padding-left: 5%;
        }

        input[type="text"],
        input[type="password"] {
            width: 90%;
            padding: 10px;
            border: 1px solid #87bfff;
            border-radius: 20px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        input[type="text"]:hover,
        input[type="password"]:hover,
        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: #3a537c solid 1.5px;
        }

        input[type="text"]::placeholder,
        input[type="password"]::placeholder {
            color: #999;
            font-style: italic;
        }

        .error-container {
            min-height: 25px;
            margin-bottom: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .error {
            color: #ff4757;
            text-align: center;
            font-size: 14px;
            opacity: 1;
            transition: opacity 0.3s ease;
            margin: 0;
        }

        .error.hidden {
            opacity: 0;
        }

        .field-error {
            color: #ff4757;
            font-size: 14px;
            margin-top: 3px;
            text-align: left;
            width: 90%;
            min-height: 15px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .field-error.show {
            opacity: 1;
        }

        .button-container {
            display: flex;
            justify-content: center;
            margin-top: 5px;
        }

        button {
            width: 90%;
            padding: 12px;
            background-color: #3a537c;
            border: none;
            color: white;
            border-radius: 20px;
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.2s;
        }

        button:hover {
            background-color: #3685d6;
            transform: scale(1.01);
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .modal-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        .modal-content {
            background-color: white;
            padding: 40px 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            text-align: center;
            max-width: 450px;
            width: 90%;
            transform: scale(0.7);
            transition: transform 0.3s ease;
        }

        .modal-overlay.show .modal-content {
            transform: scale(1);
        }

        .success-icon {
            font-size: 30px;
            color: #4A92E4;
            margin-bottom: 20px;
        }

        .success-title {
            font-size: 24px;
            color: #4A92E4;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .success-message {
            font-size: 16px;
            color: #4A92E4;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .countdown {
            font-size: 16px;
            color: #4A92E4;
        }

        .spinner {
            width: 20px;
            height: 20px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #4A92E4;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            display: inline-block;
            margin-left: 10px;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        @keyframes fadeInError {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>
    <div class="login-container">
        <h2>Admin Login</h2>
        <form action="" method="post" id="loginForm" autocomplete="off">
            <!-- input giả để chặn autofill trình duyệt -->
            <input type="text" name="fakeuser" style="display:none">
            <input type="password" name="fakepass" style="display:none">

            <div class="form-group" id="userGroup">
                <label for="user">Username:</label>
                <input type="text" name="user" id="user" placeholder="" autocomplete="off">
                <div class="field-error" id="userError">Vui lòng nhập tên đăng nhập</div>
            </div>

            <div class="form-group" id="passwordGroup">
                <label for="password">Password:</label>
                <input type="password" name="password" id="password" autocomplete="new-password">
                <div class="field-error" id="passwordError">Vui lòng nhập mật khẩu</div>
            </div>

            <div class="error-container">
                <?php if (isset($error))
                    echo "<div class='error' id='errorMessage'>$error</div>"; ?>
            </div>

            <div class="button-container">
                <button type="submit">Đăng nhập</button>
            </div>
        </form>
    </div>

    <!-- Success Modal -->
    <div class="modal-overlay" id="successModal">
        <div class="modal-content">
            <div class="success-icon"><i class="fa-solid fa-user-check fa-lg"></i></div>
            <div class="success-title">Đăng nhập thành công!</div>
            <div class="success-message">
                Đang chuyển hướng đến trang quản trị hệ thống.<br>
            </div>
            <div class="countdown">
                <span id="countdownText">2</span> s
                <div class="spinner"></div>
            </div>
        </div>
    </div>

    <?php if ($showSuccessModal): ?>
        <script>
            // Hiển thị modal ngay lập tức
            document.addEventListener('DOMContentLoaded', function () {
                const modal = document.getElementById('successModal');
                const countdownElement = document.getElementById('countdownText');

                // Hiển thị modal
                modal.classList.add('show');

                let countdown = 2;

                // Cập nhật countdown mỗi giây
                const countdownInterval = setInterval(function () {
                    countdown--;
                    countdownElement.textContent = countdown;

                    if (countdown <= 0) {
                        clearInterval(countdownInterval);
                        // Chuyển hướng đến trang quản trị
                        window.location.href = 'quantrihethong.php';
                    }
                }, 1000);
            });
        </script>
    <?php endif; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('loginForm');
            const userInput = document.getElementById('user');
            const passwordInput = document.getElementById('password');
            const userGroup = document.getElementById('userGroup');
            const passwordGroup = document.getElementById('passwordGroup');
            const userError = document.getElementById('userError');
            const passwordError = document.getElementById('passwordError');
            const errorMessage = document.getElementById('errorMessage');

            // Ẩn thông báo lỗi từ server khi bắt đầu nhập
            function hideServerError() {
                if (errorMessage) {
                    errorMessage.classList.add('hidden');
                }
            }

            // Xóa lỗi validation cho từng field
            function clearFieldError(errorElement) {
                errorElement.classList.remove('show');
            }

            // Hiển thị lỗi validation cho từng field
            function showFieldError(errorElement) {
                errorElement.classList.add('show');
            }

            // Validation khi submit form
            form.addEventListener('submit', function (e) {
                let hasError = false;

                // Reset tất cả lỗi
                clearFieldError(userError);
                clearFieldError(passwordError);

                // Kiểm tra username
                if (userInput.value.trim() === '') {
                    showFieldError(userError);
                    hasError = true;
                }

                // Kiểm tra password
                if (passwordInput.value.trim() === '') {
                    showFieldError(passwordError);
                    hasError = true;
                }

                // Ngăn submit nếu có lỗi
                if (hasError) {
                    e.preventDefault();
                }
            });

            // Ẩn lỗi server và field error khi nhập
            userInput.addEventListener('input', function () {
                hideServerError();
                if (this.value.trim() !== '') {
                    clearFieldError(userError);
                }
            });

            userInput.addEventListener('focus', hideServerError);

            passwordInput.addEventListener('input', function () {
                hideServerError();
                if (this.value.trim() !== '') {
                    clearFieldError(passwordError);
                }
            });

            passwordInput.addEventListener('focus', hideServerError);
        });
    </script>
</body>

</html>