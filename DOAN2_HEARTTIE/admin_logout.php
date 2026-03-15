<?php
session_start();
session_destroy();
header("Location: dangnhap_admin.php");
exit();
