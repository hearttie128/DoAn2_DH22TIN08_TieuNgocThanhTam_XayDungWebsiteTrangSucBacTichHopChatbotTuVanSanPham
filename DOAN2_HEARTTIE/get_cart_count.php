<?php
session_start();
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "qlchdt");
mysqli_set_charset($conn, "utf8mb4");

$count = 0;

if (isset($_SESSION['MA_ND'])) {
    $ma_nd = (int)$_SESSION['MA_ND'];
    
    $stmt = $conn->prepare("SELECT SUM(SO_LUONG) as total FROM GIOHANG WHERE MA_ND = ?");
    $stmt->bind_param("i", $ma_nd);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $count = $row['total'] ? (int)$row['total'] : 0;
}

echo json_encode(['count' => $count]);

$conn->close();
?>