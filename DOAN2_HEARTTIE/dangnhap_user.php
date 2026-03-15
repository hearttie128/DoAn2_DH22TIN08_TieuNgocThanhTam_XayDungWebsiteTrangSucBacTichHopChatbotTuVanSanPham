<?php
session_start();

$errors = [
    'phoneorEmail' => '',
    'password' => '',
    'confirm_password' => '',
];

$error_message = '';
if (isset($_SESSION['error'])) {
    $error_message = $_SESSION['error'];
    unset($_SESSION['error']);
}

$success_message = '';
if (isset($_SESSION['success'])) {
    $success_message = $_SESSION['success'];
    unset($_SESSION['success']);
}

if (!isset($_SESSION['previous_page']) && isset($_SERVER['HTTP_REFERER'])) {
    $referer = $_SERVER['HTTP_REFERER'];
    if (
        strpos($referer, 'dangnhap_user.php') === false &&
        strpos($referer, 'dangky_user.php') === false
    ) {
        $_SESSION['previous_page'] = $referer;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $phoneorEmail = trim($_POST['phoneorEmail'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm-password'] ?? '');

    if (empty($phoneorEmail)) {
        $errors['phoneorEmail'] = "Vui lòng nhập email hoặc số điện thoại.";
    }
    if (empty($password)) {
        $errors['password'] = "Vui lòng nhập mật khẩu.";
    }
    if (empty($confirm_password)) {
        $errors['confirm_password'] = "Vui lòng xác nhận mật khẩu.";
    } elseif ($password !== $confirm_password) {
        $errors['confirm_password'] = "Mật khẩu không khớp.";
    }

    if (!array_filter($errors)) {
        $conn = new mysqli("localhost", "root", "", "qlchdt"); // Changed to lowercase to match CSDL.php
        if ($conn->connect_error) {
            die("Kết nối thất bại: " . $conn->connect_error);
        }

        // Set charset to match CSDL.php
        mysqli_set_charset($conn, "utf8mb4");

        // Fixed query to use correct table and column names
        $stmt = $conn->prepare("SELECT * FROM NGUOIDUNG WHERE EMAIL = ? OR SDT = ?");
        if ($stmt) {
            $stmt->bind_param("ss", $phoneorEmail, $phoneorEmail);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                $user = $result->fetch_assoc();

                // Fixed password comparison using correct column name
                // Also handle both plain text and hashed passwords
                $stored_password = $user['MK_HASH'];
                $password_correct = false;

                // Check if password matches directly (plain text)
                if ($password === $stored_password) {
                    $password_correct = true;
                }
                // If not, check if it's a hashed password
                elseif (password_verify($password, $stored_password)) {
                    $password_correct = true;
                }

                if ($password_correct) {
                    // Fixed session variables to use correct column names
                    $_SESSION['user'] = $user['EMAIL'];
                    $_SESSION['user_id'] = $user['MA_ND'];  // Fixed: MA_ND instead of ID
                    $_SESSION['user_email'] = $user['EMAIL'];
                    $_SESSION['user_phone'] = $user['SDT'] ?? '';  // Fixed: SDT instead of PHONE_NUMBER
                    $_SESSION['user_name'] = $user['TEN_DAY_DU'] ?? $user['EMAIL'];  // Fixed: TEN_DAY_DU instead of NAME
                    $_SESSION['user_role'] = $user['VAI_TRO'] ?? 'User';  // Fixed: VAI_TRO instead of ROLE
                    $_SESSION['is_logged_in'] = true;
                    $_SESSION['login_time'] = time();
                    $_SESSION['MA_ND'] = $user['MA_ND'];   

                    ini_set('session.gc_maxlifetime', 86400);
                    session_set_cookie_params(86400);

                    $redirect_url = 'trangchu.php';
                    if (isset($_SESSION['previous_page'])) {
                        $redirect_url = $_SESSION['previous_page'];
                        unset($_SESSION['previous_page']);
                    }

                    $stmt->close();
                    $conn->close();

                    $_SESSION['login_success'] = "Đăng nhập thành công! Đang chuyển hướng...";
                    $_SESSION['redirect_url'] = $redirect_url;

                    header("Location: dangnhap_user.php?success=1");
                    exit();
                } else {
                    $_SESSION['error'] = "Mật khẩu không chính xác.";
                }
            } else {
                $_SESSION['error'] = "Tài khoản không tồn tại.";
            }
            $stmt->close();
        } else {
            $_SESSION['error'] = "Lỗi chuẩn bị truy vấn.";
        }
        $conn->close();

        header("Location: dangnhap_user.php");
        exit();
    }
}

// Check if user is already logged in
if (isset($_SESSION['user']) && isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true && !isset($_GET['success'])) {
    $redirect_url = 'trangchu.php';
    if (isset($_SESSION['previous_page'])) {
        $redirect_url = $_SESSION['previous_page'];
        unset($_SESSION['previous_page']);
    }
    header("Location: " . $redirect_url);
    exit();
}

$show_success = false;
$login_success_message = '';
$redirect_url = 'trangchu.php';

if (isset($_GET['success']) && isset($_SESSION['login_success'])) {
    $show_success = true;
    $login_success_message = $_SESSION['login_success'];
    $redirect_url = $_SESSION['redirect_url'] ?? 'trangchu.php';

    unset($_SESSION['login_success']);
    unset($_SESSION['redirect_url']);
}
?>

<!-- Keep the same HTML code as before -->

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Đăng Nhập Tài Khoản</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
        }

        header {
            font-family: Arial, sans-serif;
            position: relative;
            display: flex;
            align-items: center;
            background-color: #4a92e4;
            color: white;
            padding: 15px 20px;
            height: 100px;
            justify-content: center;
            box-sizing: border-box;
        }

        #back-button {
            position: absolute;
            transform: translateY(-50%);
            top: 40%;
            left: 20px;
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            margin-right: 15px;
            display: flex;
            align-items: center;
            height: 100%;
            padding: 0 10px;
            width: 40px;
            height: 40px;
            justify-content: center;
            border-radius: 50%;
            transition: background-color 0.3s ease;
        }

        #back-button:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        h1 {
            flex-grow: 1;
            font-size: 32px;
            font-weight: bold;
            margin: 0;
            text-align: center;
        }

        main {
            flex: 1;
        }

        .login-section {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 25px 20px;
            background-color: white;
        }

        .login-container {
            margin: 50px auto;
            background-color: #dcecff;
            padding: 5px 40px 30px;
            border-radius: 10px;
            box-shadow: 0 1px 30px rgba(0, 0, 0, 0.1);
            height: 450px;
            width: 450px;
        }

        .login-container h2 {
            font-size: 32px;
            text-align: center;
            margin-top: 30px;
            margin-bottom: 30px;
            color: #4A92E4;
        }

        .login-form label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
            color: #222;
        }

        .login-form input[type="text"],
        .login-form input[type="email"],
        .login-form input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        .login-form button {
            width: 100%;
            padding: 12px;
            margin-top: 25px;
            border: none;
            background-color: #4a92e4;
            color: white;
            font-size: 16px;
            border-radius: 6px;
            cursor: pointer;
        }

        .login-form button:hover {
            background-color: #2e73c6;
        }

        .input-icon {
            height: 40px;
            position: relative;
            margin-bottom: 25px;
        }

        .input-icon:first-of-type {
            margin-bottom: 55px;
        }

        .input-icon input {
            color: gray;
            width: 100%;
            height: 40px;
            padding: 0 15px 0 45px;
            border: 1px solid #87bfff;
            border-radius: 20px;
            font-size: 15px;
            box-sizing: border-box;
        }

        .input-icon input:hover,
        .input-icon input:focus {
            outline: #4A92E4 solid 1.5px;
        }

        .input-icon i {
            position: absolute;
            top: 50%;
            left: 15px;
            transform: translateY(-50%);
            color: #4A92E4;
            font-size: 18px;
            pointer-events: none;
        }

        .input-icon .error {
            color: red;
            font-size: 13px;
            margin-top: 5px;
            display: block;
            min-height: 18px;
        }

        .error {
            color: red;
            font-size: 13px;
            display: block;
            margin-top: 5px;
        }

        /* Thêm style cho thông báo lỗi chung */
        .error-message {
            background-color: #ffe6e6;
            color: #d00;
            padding: 10px;
            border-radius: 5px;
            margin-top: -8px;
            margin-bottom: 5px;
            border: 1px solid #ffcccc;
            text-align: center;
            font-size: 14px;
            font-weight: bold;
        }

        /* Style cho thông báo thành công */
        .success-message {
            background-color: #e6ffe6;
            color: #008000;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            border: 1px solid #ccffcc;
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .success-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .success-popup {
            background-color: white;
            padding: 40px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            max-width: 400px;
            width: 90%;
        }

        .success-popup .icon {
            font-size: 60px;
            color: #4CAF50;
            margin-bottom: 20px;
        }

        .success-popup h3 {
            color: #4CAF50;
            font-size: 24px;
            margin-bottom: 15px;
        }

        .success-popup p {
            color: #666;
            font-size: 16px;
            margin-bottom: 20px;
        }

        .loading-bar {
            width: 100%;
            height: 4px;
            background-color: #e0e0e0;
            border-radius: 2px;
            overflow: hidden;
            margin-top: 20px;
        }

        .loading-progress {
            height: 100%;
            background-color: #4CAF50;
            border-radius: 2px;
            animation: loadingProgress 1.5s ease-out;
        }

        @keyframes loadingProgress {
            from {
                width: 0%;
            }

            to {
                width: 100%;
            }
        }

        button {
            width: 100%;
            margin-top: 10px;
            padding: 12px;
            background-color: #4A92E4;
            border: none;
            border-radius: 20px;
            font-weight: bold;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background-color: #3685d6;
            transform: scale(1.01);
        }

        .login-mode-text {
            font-family: Arial, sans-serif;
            display: block;
            color: #4A92E4;
            font-size: 14px;
            margin-right: 10px;
            margin-top: 7px;
            margin-bottom: 8px;
            cursor: pointer;
            text-decoration: none;
        }

        .login-mode-text:hover {
            text-decoration: none;
        }

        .login-mode-detect {
            display: block;
            font-size: 12px;
            color: #888;
            margin-top: -10px;
            margin-bottom: 10px;
        }

        .login-link {
            margin-top: 20px;
            margin-bottom: 20px;
            text-align: center;
        }

        .login-link a {
            color: #4A92E4;
            text-decoration: none;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        #drag-button-wrapper {
            width: 150px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: fixed;
            bottom: 400px;
            right: 150px;
            text-align: center;
            cursor: move;
            z-index: 1000;
        }

        #drag-button {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: none;
            background-color: #0c7ae8;
            color: white;
            font-size: 24px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            transition: background-color 0.3s;
            animation: blinkColorScale 1s infinite;
            transition: transform 0.2s;
        }

        @keyframes blinkColorScale {

            0%,
            100% {
                color: rgb(209, 237, 255);
                transform: scale(1);
            }

            50% {
                color: rgb(209, 237, 255);
                transform: scale(1.2);
            }
        }

        #drag-button:hover {
            background-color: #589eff;
        }

        .button-label {
            margin-top: 8px;
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            color: #0c7ae8;
            word-wrap: break-word;
            line-height: 1.2;
        }

        .footer-bottom {
            text-align: center;
            align-items: baseline;
            font-size: 12px;
            font-family: Arial, sans-serif;
            padding: 10px 0;
            background-color: #d9d9d9;
        }

        .footer-bottom p {
            margin: 20px 0;
        }
    </style>
</head>

<body>
    <?php if ($show_success): ?>
        <div class="success-overlay" id="success-overlay">
            <div class="success-popup">
                <div class="icon">
                    <i class="fa fa-check-circle"></i>
                </div>
                <h3>Đăng nhập thành công!</h3>
                <p>Chào mừng bạn quay trở lại. Đang chuyển hướng...</p>
                <div class="loading-bar">
                    <div class="loading-progress"></div>
                </div>
            </div>
        </div>

        <script>
            setTimeout(function () {
                window.location.href = '<?php echo $redirect_url; ?>';
            }, 1500);
        </script>
    <?php endif; ?>
    <header>
        <button id="back-button" aria-label="Quay lại trang trước">
            <i class="fa fa-arrow-left"></i>
        </button>
        <h1>HEARTTIE - ĐĂNG NHẬP TÀI KHOẢN</h1>
    </header>
    <main>
        <section class="login-section">
            <div class="login-container">
                <h2>Đăng Nhập</h2>
                <form id="login-form" method="POST" action="" <?php echo $show_success ? 'style="display:none;"' : ''; ?>>
                    <div class="input-icon">
                        <i class="fa fa-phone" id="login-icon"></i>
                        <input type="text" id="phoneorEmail" name="phoneorEmail" placeholder="Nhập số điện thoại"
                            value="<?php echo htmlspecialchars($phoneorEmail ?? ''); ?>" />
                        <span class="error" id="phoneorEmail-error"><?php echo $errors['phoneorEmail']; ?></span>
                        <div style="text-align: right; margin-top: -10px;">
                            <span class="login-mode-text" id="login-mode-text">Đăng nhập bằng Email</span><br>
                            <span class="login-mode-detect" id="login-mode-detect"></span>
                        </div>
                    </div>

                    <div class="toggle-login-method" style="text-align: right; margin-top: -15px; margin-bottom: 10px;">
                        <a href="#" id="toggle-login"
                            style="font-size: 13px; color: #4A92E4; text-decoration: none;"></a>
                    </div>

                    <div class="input-icon">
                        <i class="fa fa-key"></i>
                        <input type="password" id="password" name="password" placeholder="Mật Khẩu" />
                        <span class="error" id="password-error"><?php echo $errors['password']; ?></span>
                    </div>

                    <div class="input-icon">
                        <i class="fa fa-check"></i>
                        <input type="password" id="confirm-password" name="confirm-password"
                            placeholder="Xác Nhận Mật Khẩu" />
                        <span class="error"
                            id="confirm-password-error"><?php echo $errors['confirm_password']; ?></span>
                    </div>

                    <?php if (!empty($error_message)): ?>
                        <div class="error-message">
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>

                    <button type="submit">Đăng Nhập</button>
                    <p class="login-link">Chưa có tài khoản? <a href="dangky_user.php">Đăng Ký</a></p>
                </form>
            </div>
            <div id="drag-button-wrapper">
                <button id="drag-button" onclick="goToHomePage()">
                    <i class="fa fa-home"></i>
                </button>
                <div class="button-label">Nhấn vào đây để<br>quay về trang chủ nhé😉</div>
            </div>
        </section>
    </main>

    <footer>
        <div class="footer-bottom">
            <p>© 2020 Công ty cổ phần HEARTTIE. GPDKKD: 0303217354 do sở Cần Thơ cấp ngày 02/01/2015. GPMXH:
                21/GP-BTTTT do Bộ Thông Tin và Truyền Thông cấp ngày 11/01/2021. Địa chỉ: 128, Nguyễn Văn Cừ (nối dài),
                Phường An Bình, Quận Ninh Kiều, Thành Phố Cần Thơ.
                <br />
                Chịu trách nhiệm nội dung: Tiêu Ngọc Thanh Tâm. Tất cả các quyền được bảo
                lưu.
            </p>
        </div>
    </footer>

    <script>
        document.getElementById('back-button').addEventListener('click', function () {
            history.back();
        });

        function goToHomePage() {
            window.location.href = 'trangchu.php';
        }

        const dragWrapper = document.getElementById("drag-button-wrapper");
        let isDragging = false;
        let offsetX = 0;
        let offsetY = 0;

        dragWrapper.addEventListener("mousedown", function (e) {
            isDragging = true;
            offsetX = e.clientX - dragWrapper.offsetLeft;
            offsetY = e.clientY - dragWrapper.offsetTop;
        });

        document.addEventListener("mousemove", function (e) {
            if (isDragging) {
                dragWrapper.style.left = (e.clientX - offsetX) + "px";
                dragWrapper.style.top = (e.clientY - offsetY) + "px";
                dragWrapper.style.bottom = "auto";
                dragWrapper.style.right = "auto";
            }
        });

        document.addEventListener("mouseup", function () {
            isDragging = false;
        });

        let isEmailMode = false;
        const usernameInput = document.getElementById("phoneorEmail");
        const icon = document.getElementById("login-icon");
        const switchText = document.getElementById("login-mode-text");
        const detectText = document.getElementById("login-mode-detect");

        function updateInputMode() {
            if (isEmailMode) {
                usernameInput.placeholder = "Nhập email";
                icon.className = "fa fa-envelope";
                switchText.textContent = "Đăng nhập bằng số điện thoại";
            } else {
                usernameInput.placeholder = "Nhập số điện thoại";
                icon.className = "fa fa-phone";
                switchText.textContent = "Đăng nhập bằng email";
            }
            detectText.textContent = "";
        }

        switchText.addEventListener("click", () => {
            isEmailMode = !isEmailMode;
            updateInputMode();
        });

        usernameInput.addEventListener("input", () => {
            const value = usernameInput.value.trim();
            if (value.includes("@")) {
                detectText.textContent = "Đăng nhập bằng email";
            } else if (/^\d+$/.test(value)) {
                detectText.textContent = "Đăng nhập bằng số điện thoại";
            } else {
                detectText.textContent = "";
            }
        });

        updateInputMode();

        document.querySelectorAll("#login-form input").forEach(el => {
            el.addEventListener("input", () => {
                const errorSpan = document.getElementById(el.id + "-error");
                if (errorSpan) errorSpan.textContent = "";
            });
        });

        const errorMessage = document.querySelector('.error-message');
        if (errorMessage) {
            document.querySelectorAll("#login-form input").forEach(el => {
                el.addEventListener("input", () => {
                    errorMessage.style.display = 'none';
                });
            });
        }
    </script>
</body>

</html>