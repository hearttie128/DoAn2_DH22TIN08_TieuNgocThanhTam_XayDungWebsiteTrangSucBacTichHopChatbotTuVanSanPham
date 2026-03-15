<?php 
$conn = mysqli_connect('localhost', 'root', '', 'qlchdt');

if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
} else {
    echo "Kết nối thành công đến database";
}

mysqli_set_charset($conn, 'utf8');
?>
