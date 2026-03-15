<?php
session_start();

// Kết nối CSDL
$conn = new mysqli("localhost", "root", "", "qlchdt");
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
mysqli_set_charset($conn, "utf8mb4");

// Lấy từ khóa tìm kiếm
$keyword = $_GET['keyword'] ?? '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Kết quả tìm kiếm</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
        }

        /* ===== HEADER ===== */
        header {
            background: #afdbf3;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 10px 30px;
        }

        .nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .nav-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .nav-left img {
            height: 50px;
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            padding: 8px 40px 8px 15px;
            border-radius: 20px;
            border: 1px solid #ccc;
            width: 260px;
        }

        .search-box button {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
        }

        .nav-right a {
            margin-left: 20px;
            text-decoration: none;
            color: #333;
            font-weight: 500;
        }

        /* ===== CONTENT ===== */
        h2 {
            text-align: center;
            margin: 30px 0;
        }

        .product-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 25px;
            padding: 0 50px 50px;
        }

        .product-card {
            background: #fff;
            border-radius: 15px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: 0.3s;
        }

        .product-card:hover {
            transform: translateY(-5px);
        }

        .product-card img {
            width: 180px;
            height: 180px;
            object-fit: cover;
            border-radius: 10px;
        }

        .product-name {
            margin: 12px 0 8px;
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }

        .price {
            color: #e53935;
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 10px;
        }

        .btn {
            display: inline-block;
            padding: 8px 18px;
            background: #4a92e4;
            color: #fff;
            border-radius: 20px;
            text-decoration: none;
            font-size: 14px;
        }

        .btn:hover {
            background: #357cd4;
        }

        .no-result {
            text-align: center;
            font-size: 18px;
            color: #777;
        }
    </style>
</head>
<body>

<!-- ===== HEADER ===== -->
<header>
    <div class="nav">
        <div class="nav-left">
            <a href="trangchu.php">
                <img src="Hearttie.png" alt="Logo">
            </a>

            <form class="search-box" action="timkiem.php" method="get">
                <input type="text" name="keyword" placeholder="Tìm sản phẩm..." value="<?= htmlspecialchars($keyword) ?>" required>
                <button type="submit">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </form>
        </div>
<!-- 
        <div class="nav-right">
            <a href="dangnhap_user.php"><i class="fa-solid fa-user"></i> Đăng nhập</a>
            <a href="giohang.php"><i class="fa-solid fa-cart-shopping"></i> Giỏ hàng</a>
        </div> -->
    </div>
</header>

<h2>Kết quả tìm kiếm cho: "<b><?= htmlspecialchars($keyword) ?></b>"</h2>

<div class="product-container">
<?php
if (!empty($keyword)) {
    $sql = "SELECT * FROM SANPHAM WHERE TEN_SP LIKE ?";
    $stmt = $conn->prepare($sql);
    $like = "%$keyword%";
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
?>
        <div class="product-card">
            <a href="chitietsanpham.php?id=<?= $row['MA_SP'] ?>" style="text-decoration:none; color:inherit;">
                <img src="<?= $row['HINH_ANH'] ?>" alt="<?= $row['TEN_SP'] ?>">
                <div class="product-name"><?= $row['TEN_SP'] ?></div>
            </a>

            <div class="price">
                <?= number_format($row['GIA'], 0, ',', '.') ?>đ
            </div>

            <a href="chitietsanpham.php?id=<?= $row['MA_SP'] ?>" class="btn">
                Xem chi tiết
            </a>
        </div>
<?php
        }
    } else {
        echo "<div class='no-result'>Không tìm thấy sản phẩm phù hợp.</div>";
    }
}
?>
</div>

</body>
</html>

<?php $conn->close(); ?>
