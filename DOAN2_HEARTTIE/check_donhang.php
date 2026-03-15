<?php
session_start();
$conn = new mysqli("localhost", "root", "", "qlchdt");
$conn->set_charset("utf8");

$username = $_POST['username'];
$phone    = $_POST['phone'];
$order_id = $_POST['order_id'];

$sql = "
    SELECT dh.MA_DH
    FROM donhang dh
    JOIN khachhang kh ON dh.MA_KH = kh.MA_KH
    WHERE dh.MA_DH = ?
      AND kh.TEN_KH = ?
      AND kh.SDT = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $order_id, $username, $phone);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $_SESSION['MA_DH'] = $order_id;
    header("Location: donhang.php");
} else {
    header("Location: tracuudonhang.php?error=1");
}
