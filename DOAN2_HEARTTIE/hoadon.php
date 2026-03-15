<?php
session_start();

$giohang = isset($_SESSION['giohang']) ? $_SESSION['giohang'] : [];
$khachhang = isset($_SESSION['khachhang']) ? $_SESSION['khachhang'] : [];

$tongtien = 0;
foreach ($giohang as $sp) {
    $tongtien += $sp['gia'] * $sp['soluong'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Hóa đơn thanh toán</title>
</head>
<body>
    <h2>HÓA ĐƠN THANH TOÁN</h2>

    <h3>Thông tin khách hàng</h3>
    <p>Họ tên: <?= htmlspecialchars($khachhang['ten']) ?></p>
    <p>Địa chỉ: <?= htmlspecialchars($khachhang['diachi']) ?></p>
    <p>SĐT: <?= htmlspecialchars($khachhang['sdt']) ?></p>
    <p>Email: <?= htmlspecialchars($khachhang['email']) ?></p>

    <h3>Chi tiết đơn hàng</h3>
    <table border="1" cellspacing="0" cellpadding="5">
        <tr>
            <th>Sản phẩm</th>
            <th>Giá</th>
            <th>Số lượng</th>
            <th>Thành tiền</th>
        </tr>
        <?php foreach ($giohang as $sp): ?>
        <tr>
            <td><?= $sp['ten'] ?></td>
            <td><?= number_format($sp['gia']) ?> VNĐ</td>
            <td><?= $sp['soluong'] ?></td>
            <td><?= number_format($sp['gia'] * $sp['soluong']) ?> VNĐ</td>
        </tr>
        <?php endforeach; ?>
    </table>

    <h3>Tổng tiền: <?= number_format($tongtien) ?> VNĐ</h3>
</body>
</html>
