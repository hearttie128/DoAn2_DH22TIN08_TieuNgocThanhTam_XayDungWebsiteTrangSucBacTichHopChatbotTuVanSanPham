<?php
$conn = new mysqli('localhost', 'root', '', 'qlchdt');
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $sdt = trim($_POST['sdt'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $dob = !empty($_POST['dob']) ? $_POST['dob'] : null;
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (!$fullname || !$email || !$sdt || !$password || !$confirm_password || !$gender || !$dob || !$address) {
        $error = "Vui lòng điền đầy đủ các trường bắt buộc.";
    } elseif ($password !== $confirm_password) {
        $error = "Mật khẩu xác nhận không khớp.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email không hợp lệ.";
    } elseif (!preg_match('/^\d{10,11}$/', $sdt)) {
        $error = "Số điện thoại phải có 10-11 chữ số.";
    } elseif (!in_array($gender, ['MALE', 'FEMALE'])) {
        $error = "Giới tính không hợp lệ.";
    } elseif (strlen($password) < 4) {
        $error = "Mật khẩu phải từ 4 ký tự trở lên.";
    } elseif (strlen($fullname) < 2 || strlen($fullname) > 50) {
        $error = "Họ và tên phải từ 2-50 ký tự.";
    } elseif (!preg_match('/^[a-zA-ZÀ-ỹ\s]+$/u', $fullname)) {
        $error = "Họ và tên không được chứa số hoặc ký tự đặc biệt.";
    } else {
        // Validate date of birth
        $date_parts = explode('/', $dob);
        if (count($date_parts) != 3) {
            $error = "Ngày sinh không hợp lệ.";
        } else {
            $day = intval($date_parts[0]);
            $month = intval($date_parts[1]);
            $year = intval($date_parts[2]);
            
            if (!checkdate($month, $day, $year)) {
                $error = "Ngày sinh không hợp lệ.";
            } else {
                $dob_date = DateTime::createFromFormat('d/m/Y', $dob);
                $today = new DateTime();
                $age = $today->diff($dob_date)->y;
                
                if ($dob_date > $today) {
                    $error = "Ngày sinh không được lớn hơn hôm nay.";
                } elseif ($age < 16) {
                    $error = "Bạn phải từ 16 tuổi trở lên.";
                }
            }
        }
        
        // If validation passes, check for existing users
        if (!$error) {
            $stmt = $conn->prepare("
                SELECT MA_ND 
                FROM NGUOIDUNG 
                WHERE EMAIL = ? OR SDT = ?
            ");
            $stmt->bind_param("ss", $email, $sdt);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $stmt->close();
                $error = "Email hoặc số điện thoại đã tồn tại.";
            } else {
                $stmt->close();

                $mk_hash = password_hash($password, PASSWORD_DEFAULT);
                
                do {
                    $user_id = uniqid('MA_ND');
                    $check_stmt = $conn->prepare("SELECT MA_ND FROM NGUOIDUNG WHERE MA_ND = ?");
                    $check_stmt->bind_param("s", $user_id);
                    $check_stmt->execute();
                    $check_stmt->store_result();
                    $id_exists = $check_stmt->num_rows > 0;
                    $check_stmt->close();
                } while ($id_exists);

                // Convert date format from d/m/Y to Y-m-d
                $dob_formatted = DateTime::createFromFormat('d/m/Y', $dob)->format('Y-m-d');

                $stmt = $conn->prepare("
                    INSERT INTO NGUOIDUNG
                    (TEN_DN, TEN_DAY_DU, MK_HASH, EMAIL, SDT, DIA_CHI, GIOI_TINH, NGAY_SINH)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
 
                // Create username from email if not provided
                if (!$username) {
                    $username = explode('@', $email)[0];
                }
                
                $stmt->bind_param(
                    "ssssssss",
                    $username,
                    $fullname,
                    $mk_hash,
                    $email,
                    $sdt,
                    $address,
                    $gender,
                    $dob_formatted
                );

                if ($stmt->execute()) {
                    $stmt->close();
                    $conn->close();
                    $success = "Đăng ký thành công!";
                    // Redirect to homepage after successful registration
                    echo "<script>
                        window.location.href = 'thongbao_dangky.php';
                    </script>";
                    exit();
                } else {
                    $error = "Đã xảy ra lỗi khi đăng ký. Vui lòng thử lại.";
                }
                $stmt->close();
            }
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Đăng Ký Tài Khoản</title>
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

        .register-section {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 25px 20px;
            background-color: white;
        }

        .register-container {
            margin: 0;
            background-color: #dcecff;
            padding: 5px 40px 30px;
            border-radius: 10px;
            box-shadow: 0 1px 30px rgba(0, 0, 0, 0.1);
            height: auto;
            width: 450px;
            min-height: 660px;
        }

        .register-container h2 {
            font-size: 32px;
            text-align: center;
            margin-top: 20px;
            margin-bottom: 20px;
            color: #4A92E4;
        }

        .register-form label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
            color: #222;
        }

        .register-form input[type="text"],
        .register-form input[type="email"],
        .register-form input[type="password"],
        .register-form select {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        .register-form button {
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

        .register-form button:hover {
            background-color: #2e73c6;
        }

        .input-icon {
            height: 40px;
            position: relative;
            margin-bottom: 25px;
        }

        .input-icon input:focus,
        .input-icon select:focus,
        .input-icon input:hover,
        .input-icon select:hover {
            outline: #4A92E4 solid 1.5px;
        }

        .input-icon input,
        .input-icon select {
            color: gray;
            width: 100%;
            height: 40px;
            padding: 0 15px 0 45px;
            border: 1px solid #87bfff;
            border-radius: 20px;
            font-size: 15px;
            box-sizing: border-box;
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

        .gender-container {
            margin-bottom: 20px;
            font-size: 15px;
        }

        .gender-container label {
            margin-right: 20px;
        }

        .error {
            color: red;
            font-size: 13px;
            display: block;
            margin-top: 4px;
        }

        .success {
            color: green;
            font-size: 14px;
            display: block;
            margin-top: 10px;
            text-align: center;
            font-weight: bold;
        }

        .server-error {
            color: red;
            font-size: 14px;
            display: block;
            margin-top: 10px;
            text-align: center;
            font-weight: bold;
        }

        button {
            width: 100%;
            padding: 12px;
            margin-top: 10px;
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

        .register-link {
            margin-top: 20px;
            margin-bottom: 20px;
            text-align: center;
        }

        .register-link a {
            color: #4A92E4;
            text-decoration: none;
        }

        .register-link a:hover {
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
            background-color: #7ebaff;
            color: white;
            font-size: 24px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            transition: background-color 0.3s;
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

        #drag-button {
            animation: blinkColorScale 1s infinite;
            transition: transform 0.2s;
        }

        #drag-button:hover {
            background-color: #589eff;
        }

        .button-label {
            margin-top: 8px;
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            color: #7ebaff;
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
    <header>
        <button id="back-button" aria-label="Quay lại trang trước">
            <i class="fa fa-arrow-left"></i>
        </button>
        <h1>HEARTTIE - ĐĂNG KÝ TÀI KHOẢN</h1>
    </header>
    <main>
        <section class="register-section">
            <div class="register-container">
                <h2>Đăng Ký</h2>
                <?php if ($error): ?>
                    <div class="server-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <form id="register-form" method="POST" action="">
                    <div class="input-icon">
                        <i class="fa fa-user"></i>
                        <input type="text" id="fullname" name="fullname" placeholder="Họ Và Tên" value="<?php echo isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : ''; ?>" />
                        <span class="error" id="fullname-error"></span>
                    </div>

                    <div class="input-icon">
                        <i class="fa fa-phone"></i>
                        <input type="text" id="phone" name="sdt" placeholder="Số Điện Thoại" value="<?php echo isset($_POST['sdt']) ? htmlspecialchars($_POST['sdt']) : ''; ?>" />
                        <span class="error" id="phone-error"></span>
                    </div>

                    <div class="input-icon">
                        <i class="fa fa-envelope"></i>
                        <input type="email" id="email" name="email" placeholder="Email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" />
                        <span class="error" id="email-error"></span>
                    </div>

                    <div class="input-icon">
                        <i class="fa fa-calendar-days"></i>
                        <input type="text" id="dob" name="dob" placeholder="Ngày Sinh" value="<?php echo isset($_POST['dob']) ? htmlspecialchars($_POST['dob']) : ''; ?>" />
                        <span class="error" id="dob-error"></span>
                    </div>

                    <div class="input-icon">
                        <i class="fa fa-venus-mars"></i>
                        <select id="gender" name="gender">
                            <option value="" disabled <?php echo !isset($_POST['gender']) || $_POST['gender'] == '' ? 'selected' : ''; ?> hidden>Giới Tính</option>
                            <option value="MALE" <?php echo isset($_POST['gender']) && $_POST['gender'] == 'MALE' ? 'selected' : ''; ?>>Nam</option>
                            <option value="FEMALE" <?php echo isset($_POST['gender']) && $_POST['gender'] == 'FEMALE' ? 'selected' : ''; ?>>Nữ</option>
                        </select>
                        <span class="error" id="gender-error"></span>
                    </div>

                    <div class="input-icon">
                        <i class="fa fa-map-marker"></i>
                        <input type="text" id="address" name="address" placeholder="Địa chỉ" value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>" />
                        <span class="error" id="address-error"></span>
                    </div>

                    <div class="input-icon">
                        <i class="fa fa-key"></i>
                        <input type="password" id="password" name="password" placeholder="Mật Khẩu" />
                        <span class="error" id="password-error"></span>
                    </div>

                    <div class="input-icon">
                        <i class="fa fa-check"></i>
                        <input type="password" id="confirm-password" name="confirm_password"
                            placeholder="Xác Nhận Mật Khẩu" />
                        <span class="error" id="confirm-password-error"></span>
                    </div>
                    <button type="submit">Đăng Ký</button>
                    <p class="register-link">Bạn đã có tài khoản? <a href="dangnhap_user.php">Đăng Nhập</a></p>
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

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        flatpickr("#dob", {
            dateFormat: "d/m/Y",
            maxDate: "today"
        });

        document.getElementById('back-button').addEventListener('click', function () {
            history.back();
        });

        document.getElementById("register-form").addEventListener("submit", function (e) {
            // Clear previous errors
            document.querySelectorAll(".error").forEach(el => el.textContent = "");

            let isValid = true;

            const fullname = document.getElementById("fullname").value.trim();
            const phone = document.getElementById("phone").value.trim();
            const email = document.getElementById("email").value.trim();
            const dob = document.getElementById("dob").value.trim();
            const gender = document.getElementById("gender").value;
            const address = document.getElementById("address").value.trim();
            const password = document.getElementById("password").value;
            const confirmPassword = document.getElementById("confirm-password").value;

            // Validate fullname
            const fullnameRegex = /^[a-zA-ZÀ-ỹ\s]+$/u;
            if (fullname === "") {
                isValid = false;
                document.getElementById("fullname-error").textContent = "Vui lòng nhập họ và tên.";
            } else if (fullname.length < 2 || fullname.length > 50) {
                isValid = false;
                document.getElementById("fullname-error").textContent = "Họ và tên phải từ 2-50 ký tự.";
            } else if (!fullnameRegex.test(fullname)) {
                isValid = false;
                document.getElementById("fullname-error").textContent = "Họ và tên không được chứa số hoặc ký tự đặc biệt.";
            }

            // Validate phone
            const phoneRegex = /^\d{10,11}$/;
            if (phone === "") {
                isValid = false;
                document.getElementById("phone-error").textContent = "Vui lòng nhập số điện thoại.";
            } else if (!phoneRegex.test(phone)) {
                isValid = false;
                document.getElementById("phone-error").textContent = "Số điện thoại phải từ 10-11 chữ số.";
            }

            // Validate email
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (email === "") {
                isValid = false;
                document.getElementById("email-error").textContent = "Vui lòng nhập email.";
            } else if (!emailRegex.test(email)) {
                isValid = false;
                document.getElementById("email-error").textContent = "Email không hợp lệ.";
            }

            // Validate date of birth
            if (dob === "") {
                isValid = false;
                document.getElementById("dob-error").textContent = "Vui lòng chọn ngày sinh.";
            } else {
                const parts = dob.split("/");
                if (parts.length !== 3) {
                    isValid = false;
                    document.getElementById("dob-error").textContent = "Ngày sinh không hợp lệ.";
                } else {
                    const day = parseInt(parts[0], 10);
                    const month = parseInt(parts[1], 10) - 1; 
                    const year = parseInt(parts[2], 10);
                    const dobDate = new Date(year, month, day);
                    const today = new Date();

                    if (dobDate > today) {
                        isValid = false;
                        document.getElementById("dob-error").textContent = "Ngày sinh không được lớn hơn hôm nay.";
                    } else {
                        let age = today.getFullYear() - dobDate.getFullYear();
                        const m = today.getMonth() - dobDate.getMonth();
                        if (m < 0 || (m === 0 && today.getDate() < dobDate.getDate())) {
                            age--;
                        }
                        if (age < 14) {
                            isValid = false;
                            document.getElementById("dob-error").textContent = "Bạn phải từ 14 tuổi trở lên.";
                        }
                    }
                }
            }

            // Validate gender
            if (gender === "") {
                isValid = false;
                document.getElementById("gender-error").textContent = "Vui lòng chọn giới tính.";
            }

            // Validate address
            if (address === "") {
                isValid = false;
                document.getElementById("address-error").textContent = "Vui lòng nhập địa chỉ.";
            }

            // Validate password
            if (password === "") {
                isValid = false;
                document.getElementById("password-error").textContent = "Vui lòng nhập mật khẩu.";
            } else if (password.length < 4) {
                isValid = false;
                document.getElementById("password-error").textContent = "Mật khẩu phải có ít nhất 4 ký tự.";
            }

            // Validate confirm password
            if (confirmPassword === "") {
                isValid = false;
                document.getElementById("confirm-password-error").textContent = "Vui lòng xác nhận mật khẩu.";
            } else if (password !== confirmPassword) {
                isValid = false;
                document.getElementById("confirm-password-error").textContent = "Mật khẩu không khớp.";
            }

            // If validation fails, prevent form submission
            if (!isValid) {
                e.preventDefault();
            }
            // If validation passes, let the form submit normally to PHP
        });

        // Clear errors on input
        document.querySelectorAll("#register-form input, #register-form select").forEach(el => {
            el.addEventListener("input", () => {
                const errorSpan = document.getElementById(el.id + "-error");
                if (errorSpan) errorSpan.textContent = "";
            });
        });

        function goToHomePage() {
            window.location.href = 'trangchu.php';
        }

        // Drag functionality
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
    </script>
</body>

</html>