<?php
session_start();
if (!isset($_SESSION['MA_DH'])) {
    header("Location: tracuudonhang.php");
    exit;
}

$ma_dh = $_SESSION['MA_DH'];
