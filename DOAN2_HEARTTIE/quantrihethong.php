<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: dangnhap_admin.php");
    exit();
}

$adminName = !empty($_SESSION['fullname']) ? $_SESSION['fullname'] : $_SESSION['user'];

// ── Kết nối CSDL ──
$conn = new mysqli('localhost', 'root', '', 'qlchdt', 3306);
if ($conn->connect_error) die('Lỗi kết nối: ' . $conn->connect_error);
mysqli_set_charset($conn, 'utf8mb4');

// Doanh thu hôm nay (đơn đã hoàn thành hoặc đã giao)
$r = $conn->query("SELECT COALESCE(SUM(TONG_TIEN),0) AS val FROM DONHANG WHERE DATE(NGAY_TAO) = CURDATE() AND TRANG_THAI IN ('Hoàn thành','Đã giao')");
$doanhThuHomNay = (int)$r->fetch_assoc()['val'];

// Doanh thu tháng này
$r = $conn->query("SELECT COALESCE(SUM(TONG_TIEN),0) AS val FROM DONHANG WHERE MONTH(NGAY_TAO)=MONTH(NOW()) AND YEAR(NGAY_TAO)=YEAR(NOW()) AND TRANG_THAI IN ('Hoàn thành','Đã giao')");
$doanhThuThangNay = (int)$r->fetch_assoc()['val'];

// Tổng đơn hàng
$r = $conn->query("SELECT COUNT(*) AS val FROM DONHANG");
$tongDonHang = (int)$r->fetch_assoc()['val'];

// Đơn chờ xử lý
$r = $conn->query("SELECT COUNT(*) AS val FROM DONHANG WHERE TRANG_THAI = 'Chờ xử lý'");
$donChoXuLy = (int)$r->fetch_assoc()['val'];

// Tổng người dùng (trừ admin)
$r = $conn->query("SELECT COUNT(*) AS val FROM NGUOIDUNG WHERE VAI_TRO != 'Admin'");
$tongNguoiDung = (int)$r->fetch_assoc()['val'];

// Sản phẩm hết hàng
$r = $conn->query("SELECT COUNT(*) AS val FROM SANPHAM WHERE SO_LUONG = 0");
$sanPhamHetHang = (int)$r->fetch_assoc()['val'];

$stats = [
    'doanhThuHomNay'   => $doanhThuHomNay,
    'doanhThuThangNay' => $doanhThuThangNay,
    'tongDonHang'      => $tongDonHang,
    'tongNguoiDung'    => $tongNguoiDung,
    'donChoXuLy'       => $donChoXuLy,
    'sanPhamHetHang'   => $sanPhamHetHang,
];

// Dữ liệu biểu đồ doanh thu 7 ngày gần nhất (thực tế)
$chartLabels = [];
$chartData   = [];
$r = $conn->query("
    SELECT DATE(NGAY_TAO) AS ngay, COALESCE(SUM(TONG_TIEN),0) AS tong
    FROM DONHANG
    WHERE DATE(NGAY_TAO) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
      AND DATE(NGAY_TAO) <= CURDATE()
      AND TRANG_THAI IN ('Hoàn thành','Đã giao')
    GROUP BY DATE(NGAY_TAO)
    ORDER BY ngay ASC
");
$chartMap = [];
while ($row = $r->fetch_assoc()) {
    $chartMap[$row['ngay']] = (int)$row['tong'];
}
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chartLabels[] = date('d/m', strtotime($date));
    $chartData[]   = $chartMap[$date] ?? 0;
}

$conn->close();

// ── Dữ liệu bổ sung cho xuất Excel ──
$conn2 = new mysqli('localhost', 'root', '', 'qlchdt', 3306);
mysqli_set_charset($conn2, 'utf8mb4');

// Doanh thu 12 tháng gần nhất
$doanhThuThang = [];
$r2 = $conn2->query("
    SELECT DATE_FORMAT(NGAY_TAO,'%m/%Y') AS thang,
           DATE_FORMAT(NGAY_TAO,'%Y-%m') AS thang_sort,
           COALESCE(SUM(TONG_TIEN),0) AS tong,
           COUNT(*) AS so_don
    FROM DONHANG
    WHERE DATE_FORMAT(NGAY_TAO,'%Y-%m') >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 11 MONTH),'%Y-%m')
      AND DATE_FORMAT(NGAY_TAO,'%Y-%m') <= DATE_FORMAT(CURDATE(),'%Y-%m')
      AND TRANG_THAI IN ('Hoàn thành','Đã giao')
    GROUP BY thang_sort, thang
    ORDER BY thang_sort ASC
");
while ($row = $r2->fetch_assoc()) $doanhThuThang[] = $row;

// Thống kê đơn hàng theo trạng thái
$thongKeTrangThai = [];
$r2 = $conn2->query("
    SELECT TRANG_THAI, COUNT(*) AS so_don, COALESCE(SUM(TONG_TIEN),0) AS tong_tien
    FROM DONHANG
    GROUP BY TRANG_THAI
    ORDER BY so_don DESC
");
while ($row = $r2->fetch_assoc()) $thongKeTrangThai[] = $row;

// Top 10 sản phẩm bán chạy
$topSanPham = [];
$r2 = $conn2->query("
    SELECT sp.TEN_SP, dm.TEN_DM,
           SUM(ct.SO_LUONG) AS tong_sl,
           SUM(ct.THANH_TIEN) AS tong_dt
    FROM CHITIETDONHANG ct
    JOIN SANPHAM sp ON ct.MA_SP = sp.MA_SP
    JOIN DANHMUC dm ON sp.MA_DM = dm.MA_DM
    JOIN DONHANG dh ON ct.MA_DH = dh.MA_DH
    WHERE dh.TRANG_THAI IN ('Hoàn thành','Đã giao')
    GROUP BY sp.MA_SP, sp.TEN_SP, dm.TEN_DM
    ORDER BY tong_sl DESC
    LIMIT 10
");
while ($row = $r2->fetch_assoc()) $topSanPham[] = $row;

// Đơn hàng gần nhất 30 ngày
$donHangGanDay = [];
$r2 = $conn2->query("
    SELECT dh.MA_DH, n.TEN_DAY_DU, n.EMAIL,
           dh.TONG_TIEN, dh.TRANG_THAI, dh.PTTT,
           DATE_FORMAT(dh.NGAY_TAO,'%d/%m/%Y %H:%i') AS ngay_dat
    FROM DONHANG dh
    LEFT JOIN NGUOIDUNG n ON dh.MA_ND = n.MA_ND
    WHERE DATE(dh.NGAY_TAO) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ORDER BY dh.NGAY_TAO DESC
    LIMIT 100
");
while ($row = $r2->fetch_assoc()) $donHangGanDay[] = $row;

$conn2->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Trị Hearttie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        :root {
            --navy:        rgb(58, 83, 124);
            --navy-dark:   rgb(40, 60, 100);
            --navy-light:  rgba(58, 83, 124, 0.08);
            --accent:      rgb(235, 87, 87);
            --accent-dark: rgb(203, 26, 26);
            --gold:        #f0a500;
            --green:       #27ae60;
            --bg:          #f0f3f8;
            --surface:     #ffffff;
            --text:        #1a2340;
            --muted:       #6b7a99;
            --border:      #e2e8f4;
            --sidebar-w:   260px;
            --radius:      12px;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Be Vietnam Pro', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
        }

        /* ─── SIDEBAR ─── */
        .sidebar {
            width: var(--sidebar-w);
            background: var(--navy);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0;
            z-index: 100;
            box-shadow: 4px 0 24px rgba(58,83,124,0.18);
        }

        .sidebar-brand {
            padding: 28px 24px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.12);
        }

        .sidebar-brand .logo-text {
            font-size: 22px;
            font-weight: 800;
            color: var(--gold);
            letter-spacing: 0.5px;
        }

        .sidebar-brand .logo-text span {
            color: var(--gold);
        }

        .sidebar-brand .logo-sub {
            font-size: 11px;
            color: rgba(255,255,255,0.5);
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-top: 2px;
        }

        .sidebar-nav {
            padding: 16px 0;
            flex: 1;
        }

        .nav-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: rgba(255,255,255,0.35);
            padding: 12px 24px 6px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 24px;
            color: rgba(255,255,255,0.72);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            border-left: 3px solid transparent;
            transition: all 0.2s ease;
        }

        .nav-item:hover, .nav-item.active {
            background: rgba(255,255,255,0.1);
            color: #f0efefff;
            border-left-color: var(--gold);
        }

        .nav-item .icon {
            width: 18px;
            text-align: center;
            font-size: 16px;
            opacity: 0.85;
        }

        .sidebar-footer {
            padding: 20px 24px;
            border-top: 1px solid rgba(255,255,255,0.12);
        }

        .logout-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 10px 16px;
            background: rgba(235,87,87,0.15);
            color: #ff9090;
            border: 1px solid rgba(235,87,87,0.3);
            border-radius: 8px;
            font-family: 'Be Vietnam Pro', sans-serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .logout-btn:hover {
            background: var(--accent-dark);
            color: #fff;
            border-color: var(--accent-dark);
        }

        /* ─── MAIN CONTENT ─── */
        .main {
            margin-left: var(--sidebar-w);
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        /* ─── TOPBAR ─── */
        .topbar {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 16px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .topbar-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text);
        }

        .topbar-title span {
            font-weight: 400;
            color: var(--muted);
            font-size: 14px;
            margin-left: 8px;
        }

        .admin-badge {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .avatar {
            width: 38px; height: 38px;
            background: var(--navy);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: #fff;
            font-weight: 700;
            font-size: 15px;
        }

        .admin-info .name {
            font-size: 14px;
            font-weight: 600;
            color: var(--text);
            line-height: 1.2;
        }

        .admin-info .role {
            font-size: 11px;
            color: var(--muted);
        }

        /* ─── CONTENT AREA ─── */
        .content {
            padding: 28px 32px;
        }

        /* ─── SECTION HEADING ─── */
        .section-heading {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--muted);
            margin-bottom: 14px;
        }

        /* ─── STAT CARDS ─── */
        .stat-card {
            background: var(--surface);
            border-radius: var(--radius);
            padding: 22px 24px;
            border: 1px solid var(--border);
            position: relative;
            overflow: hidden;
            transition: box-shadow 0.25s, transform 0.25s;
        }

        .stat-card:hover {
            box-shadow: 0 8px 28px rgba(58,83,124,0.12);
            transform: translateY(-2px);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
        }

        .stat-card.navy::before  { background: var(--navy); }
        .stat-card.gold::before  { background: var(--gold); }
        .stat-card.green::before { background: var(--green); }
        .stat-card.accent::before { background: var(--accent); }

        .stat-label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--muted);
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 26px;
            font-weight: 800;
            color: var(--text);
            line-height: 1;
            margin-bottom: 6px;
        }

        .stat-value.small { font-size: 20px; }

        .stat-sub {
            font-size: 12px;
            color: var(--muted);
        }

        .stat-icon {
            position: absolute;
            right: 20px; top: 20px;
            font-size: 28px;
            opacity: 0.1;
        }

        /* ─── CHART CARD ─── */
        .chart-card {
            background: var(--surface);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            padding: 24px 28px;
        }

        .chart-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .chart-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--text);
        }

        .chart-badge {
            font-size: 11px;
            font-weight: 600;
            background: var(--navy-light);
            color: var(--navy);
            padding: 4px 10px;
            border-radius: 20px;
        }

        .export-btn {
            font-size: 12px;
            font-weight: 600;
            font-family: 'Be Vietnam Pro', sans-serif;
            background: var(--green);
            color: #fff;
            border: none;
            padding: 5px 14px;
            border-radius: 20px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .export-btn:hover { background: #1e8449; }

        /* ─── MODULE CARDS ─── */
        .module-card {
            background: var(--surface);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            padding: 24px;
            text-align: center;
            text-decoration: none;
            color: var(--text);
            display: block;
            transition: all 0.25s ease;
        }

        .module-card:hover {
            box-shadow: 0 8px 28px rgba(58,83,124,0.12);
            transform: translateY(-3px);
            color: var(--text);
            border-color: var(--navy);
        }

        .module-icon {
            width: 56px; height: 56px;
            border-radius: 14px;
            background: var(--navy-light);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 14px;
            font-size: 24px;
            transition: background 0.2s;
        }

        .module-card:hover .module-icon {
            background: var(--navy);
        }

        .module-card:hover .module-icon .mi {
            filter: invert(1) brightness(10);
        }

        .module-title {
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .module-desc {
            font-size: 12px;
            color: var(--muted);
            line-height: 1.5;
        }

        .module-link {
            display: inline-block;
            margin-top: 14px;
            font-size: 12px;
            font-weight: 700;
            color: var(--navy);
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        /* Alert badges */
        .alert-dot {
            display: inline-block;
            width: 8px; height: 8px;
            background: var(--accent);
            border-radius: 50%;
            margin-left: 6px;
            animation: pulse 1.8s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50%       { opacity: 0.5; transform: scale(1.4); }
        }

        /* ─── LOGOUT MODAL ─── */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            backdrop-filter: blur(4px);
            z-index: 999;
            align-items: center;
            justify-content: center;
            animation: fadeOverlay 0.2s ease;
        }
        @keyframes fadeOverlay { from { opacity: 0; } to { opacity: 1; } }

        .modal-box {
            background: var(--surface);
            border-radius: 16px;
            padding: 36px 32px 28px;
            width: 100%;
            max-width: 380px;
            box-shadow: 0 24px 60px rgba(0,0,0,0.18);
            text-align: center;
            animation: popIn 0.25s cubic-bezier(.34,1.56,.64,1);
        }
        @keyframes popIn { from { opacity: 0; transform: scale(0.88); } to { opacity: 1; transform: scale(1); } }

        .modal-icon {
            width: 60px; height: 60px;
            background: rgba(235,87,87,0.1);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 18px;
            font-size: 26px;
        }
        .modal-title { font-size: 18px; font-weight: 700; color: var(--text); margin-bottom: 8px; }
        .modal-desc  { font-size: 14px; color: var(--muted); line-height: 1.6; margin-bottom: 28px; }
        .modal-actions { display: flex; gap: 10px; }

        .btn-cancel {
            flex: 1; padding: 11px;
            border: 1.5px solid var(--border); background: transparent;
            border-radius: 8px; font-family: 'Be Vietnam Pro', sans-serif;
            font-size: 14px; font-weight: 600; color: var(--muted); cursor: pointer;
            transition: all 0.2s;
        }
        .btn-cancel:hover { background: var(--bg); color: var(--text); }

        .btn-confirm {
            flex: 1; padding: 11px;
            border: none; background: var(--accent);
            border-radius: 8px; font-family: 'Be Vietnam Pro', sans-serif;
            font-size: 14px; font-weight: 600; color: #fff; cursor: pointer;
            transition: all 0.2s;
        }
        .btn-confirm:hover { background: var(--accent-dark); }
    </style>
</head>
<body>

<!-- ═══════════════════ SIDEBAR ═══════════════════ -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="logo-text">Heart<span>tie</span></div>
        <div class="logo-sub">Admin Panel</div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-label">Tổng quan</div>
        <a href="quantrihethong.php" class="nav-item active">
            Dashboard
        </a>

        <div class="nav-label">Quản lý</div>
        <a href="quanly_nguoidung.php" class="nav-item">
            Người Dùng
        </a>
        <a href="quanly_sanpham.php" class="nav-item">
            Sản Phẩm
        </a>
        <a href="quanly_donhang&hoadon.php" class="nav-item">
            Đơn Hàng & Hóa Đơn
        </a>
        <a href="quanly_danhmuc.php" class="nav-item">
            Danh Mục
        </a>
    </nav>

    <div class="sidebar-footer">
        <button onclick="document.getElementById('logoutModal').style.display='flex'" class="logout-btn">
            Đăng xuất
        </button>
    </div>
</aside>

<!-- ═══════════════════ MAIN ═══════════════════ -->
<div class="main">

    <!-- TOPBAR -->
    <div class="topbar">
        <div>
            <div class="topbar-title">
                Dashboard
                <span>/ Tổng quan hệ thống</span>
            </div>
        </div>
        <div class="admin-badge">
            <div class="avatar">Q</div>
            <div class="admin-info">
                <div class="name">Quản Trị Viên</div>
            </div>
        </div>
    </div>

    <!-- CONTENT -->
    <div class="content">

        <!-- KPI STATS -->
        <div class="section-heading">Thống kê nhanh</div>
        <div class="row g-3 mb-4">

            <div class="col-xl-3 col-md-6">
                <div class="stat-card navy">
                    <div class="stat-label">Doanh thu hôm nay</div>
                    <div class="stat-value small">
                        <?php echo number_format($stats['doanhThuHomNay'], 0, ',', '.'); ?> đ
                    </div>
                    <div class="stat-sub">Cập nhật lúc <?php echo date('H:i'); ?></div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="stat-card gold">
                    <div class="stat-label">Doanh thu tháng này</div>
                    <div class="stat-value small">
                        <?php echo number_format($stats['doanhThuThangNay'], 0, ',', '.'); ?> đ
                    </div>
                    <div class="stat-sub">Tháng <?php echo date('m/Y'); ?></div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="stat-card green">
                    <div class="stat-label">Tổng đơn hàng</div>
                    <div class="stat-value">
                        <?php echo number_format($stats['tongDonHang']); ?>
                    </div>
                    <div class="stat-sub">
                        <?php echo $stats['donChoXuLy']; ?> đơn chờ xử lý
                        <?php if ($stats['donChoXuLy'] > 0): ?>
                            <span class="alert-dot"></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="stat-card accent">
                    <div class="stat-label">Người dùng</div>
                    <div class="stat-value">
                        <?php echo number_format($stats['tongNguoiDung']); ?>
                    </div>
                    <div class="stat-sub">Tài khoản đã đăng ký</div>
                </div>
            </div>

        </div>

        <!-- CHART -->
        <div class="row g-3 mb-4">
            <div class="col-12">
                <div class="chart-card">
                    <div class="chart-header">
                        <div class="chart-title">Doanh thu 7 ngày gần nhất</div>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <div class="chart-badge">7 ngày qua</div>
                            <button onclick="xuatExcel()" class="export-btn">
                                ⬇ Xuất Excel
                            </button>
                        </div>
                    </div>
                    <canvas id="revenueChart" height="90"></canvas>
                </div>
            </div>
        </div>

        <!-- MODULE CARDS -->
        <div class="section-heading">Quản lý hệ thống</div>
        <div class="row g-3">

            <div class="col-xl-3 col-md-6">
                <a href="quanly_nguoidung.php" class="module-card">
                    <div class="module-title">Người Dùng</div>
                    <div class="module-desc">Xem, chỉnh sửa và phân quyền tài khoản người dùng</div>
                    <div class="module-link">Truy cập →</div>
                </a>
            </div>

            <div class="col-xl-3 col-md-6">
                <a href="quanly_sanpham.php" class="module-card">
                    <div class="module-title">Sản Phẩm</div>
                    <div class="module-desc">
                        Thêm, cập nhật kho hàng và giá sản phẩm
                        <?php if ($stats['sanPhamHetHang'] > 0): ?>
                            <br><strong style="color:var(--accent)"><?php echo $stats['sanPhamHetHang']; ?> sản phẩm hết hàng</strong>
                        <?php endif; ?>
                    </div>
                    <div class="module-link">Truy cập →</div>
                </a>
            </div>

            <div class="col-xl-3 col-md-6">
                <a href="quanly_donhang&hoadon.php" class="module-card">
                    <div class="module-title">Đơn Hàng & Hóa Đơn</div>
                    <div class="module-desc">
                        Theo dõi trạng thái, thống kê và xuất hóa đơn
                        <?php if ($stats['donChoXuLy'] > 0): ?>
                            <br><strong style="color:var(--accent)"><?php echo $stats['donChoXuLy']; ?> đơn chờ xử lý<span class="alert-dot"></span></strong>
                        <?php endif; ?>
                    </div>
                    <div class="module-link">Truy cập →</div>
                </a>
            </div>

            <div class="col-xl-3 col-md-6">
                <a href="quanly_danhmuc.php" class="module-card">
                    <div class="module-title">Danh Mục</div>
                    <div class="module-desc">Tổ chức và phân loại danh mục sản phẩm</div>
                    <div class="module-link">Truy cập →</div>
                </a>
            </div>

        </div>
        <!-- end module cards -->

    </div><!-- end .content -->
</div><!-- end .main -->

<script>
const _rawLabels = <?php echo json_encode($chartLabels); ?>;
const _rawData   = <?php echo json_encode($chartData); ?>;

// Đảm bảo đủ 7 ngày kể cả hôm nay
const today = new Date();
const labels = [];
const data   = [];
for (let i = 6; i >= 0; i--) {
    const d = new Date(today);
    d.setDate(today.getDate() - i);
    const dd  = String(d.getDate()).padStart(2,'0');
    const mm  = String(d.getMonth()+1).padStart(2,'0');
    const lbl = dd + '/' + mm;
    labels.push(lbl);
    const idx = _rawLabels.indexOf(lbl);
    data.push(idx >= 0 ? _rawData[idx] / 1000000 : 0);
}

const ctx = document.getElementById('revenueChart').getContext('2d');

const gradient = ctx.createLinearGradient(0, 0, 0, 260);
gradient.addColorStop(0,   'rgba(58,83,124,0.22)');
gradient.addColorStop(1,   'rgba(58,83,124,0)');

new Chart(ctx, {
    type: 'line',
    data: {
        labels,
        datasets: [{
            label: 'Doanh thu (triệu đồng)',
            data,
            borderColor: 'rgb(58,83,124)',
            backgroundColor: gradient,
            borderWidth: 2.5,
            pointBackgroundColor: 'rgb(58,83,124)',
            pointRadius: 4,
            pointHoverRadius: 7,
            tension: 0.4,
            fill: true,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => ` ${ctx.parsed.y.toFixed(1)} triệu đồng`
                }
            }
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { font: { family: 'Be Vietnam Pro', size: 12 }, color: '#6b7a99' }
            },
            y: {
                grid: { color: 'rgba(0,0,0,0.05)' },
                ticks: {
                    font: { family: 'Be Vietnam Pro', size: 12 },
                    color: '#6b7a99',
                    callback: v => v + ' tr'
                }
            }
        }
    }
});
</script>

<!-- ═══════════════════ LOGOUT MODAL ═══════════════════ -->
<div id="logoutModal" class="modal-overlay" onclick="if(event.target===this)this.style.display='none'">
    <div class="modal-box">
        <div class="modal-icon">⚠️</div>
        <div class="modal-title">Xác nhận đăng xuất</div>
        <div class="modal-desc">Bạn có chắc chắn muốn đăng xuất khỏi trang quản trị không?</div>
        <div class="modal-actions">
            <button class="btn-cancel" onclick="document.getElementById('logoutModal').style.display='none'">Huỷ</button>
            <a href="admin_logout.php" class="btn-confirm" style="display:flex;align-items:center;justify-content:center;text-decoration:none;">Đăng xuất</a>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
// ── Dữ liệu từ PHP ──
const _7ngay = {
    labels: <?php echo json_encode($chartLabels); ?>,
    data:   <?php echo json_encode($chartData); ?>
};
const _thang  = <?php echo json_encode($doanhThuThang); ?>;
const _tt     = <?php echo json_encode($thongKeTrangThai); ?>;
const _topSP  = <?php echo json_encode($topSanPham); ?>;
const _donhang= <?php echo json_encode($donHangGanDay); ?>;
const _stats  = {
    doanhThuHomNay:   <?php echo $stats['doanhThuHomNay']; ?>,
    doanhThuThangNay: <?php echo $stats['doanhThuThangNay']; ?>,
    tongDonHang:      <?php echo $stats['tongDonHang']; ?>,
    tongNguoiDung:    <?php echo $stats['tongNguoiDung']; ?>,
    donChoXuLy:       <?php echo $stats['donChoXuLy']; ?>,
    sanPhamHetHang:   <?php echo $stats['sanPhamHetHang']; ?>
};

// ── Helper ──
function fmtVND(n) {
    return new Intl.NumberFormat('vi-VN').format(n) + ' đ';
}
function fmtNum(n) {
    return new Intl.NumberFormat('vi-VN').format(n);
}

// Áp style cho 1 cell (ghi vào ws object)
function styleCell(ws, addr, style) {
    if (!ws[addr]) ws[addr] = { t: 's', v: '' };
    ws[addr].s = style;
}

// Style constants
const S = {
    mainTitle: {
        font: { bold: true, sz: 16, color: { rgb: 'FFFFFF' } },
        fill: { fgColor: { rgb: '3A537C' } },
        alignment: { horizontal: 'center', vertical: 'center' },
        border: { bottom: { style: 'medium', color: { rgb: 'FFFFFF' } } }
    },
    subTitle: {
        font: { bold: false, sz: 11, color: { rgb: '6B7A99' } },
        fill: { fgColor: { rgb: 'F0F3F8' } },
        alignment: { horizontal: 'center' }
    },
    header: {
        font: { bold: true, sz: 11, color: { rgb: 'FFFFFF' } },
        fill: { fgColor: { rgb: '3A537C' } },
        alignment: { horizontal: 'center', vertical: 'center', wrapText: true },
        border: {
            top:    { style: 'thin', color: { rgb: 'B0BED4' } },
            bottom: { style: 'thin', color: { rgb: 'B0BED4' } },
            left:   { style: 'thin', color: { rgb: 'B0BED4' } },
            right:  { style: 'thin', color: { rgb: 'B0BED4' } }
        }
    },
    rowEven: {
        font: { sz: 10 },
        fill: { fgColor: { rgb: 'F7F9FC' } },
        border: {
            top:    { style: 'thin', color: { rgb: 'E2E8F4' } },
            bottom: { style: 'thin', color: { rgb: 'E2E8F4' } },
            left:   { style: 'thin', color: { rgb: 'E2E8F4' } },
            right:  { style: 'thin', color: { rgb: 'E2E8F4' } }
        }
    },
    rowOdd: {
        font: { sz: 10 },
        fill: { fgColor: { rgb: 'FFFFFF' } },
        border: {
            top:    { style: 'thin', color: { rgb: 'E2E8F4' } },
            bottom: { style: 'thin', color: { rgb: 'E2E8F4' } },
            left:   { style: 'thin', color: { rgb: 'E2E8F4' } },
            right:  { style: 'thin', color: { rgb: 'E2E8F4' } }
        }
    },
    totalRow: {
        font: { bold: true, sz: 11, color: { rgb: 'FFFFFF' } },
        fill: { fgColor: { rgb: '27AE60' } },
        alignment: { horizontal: 'right' },
        border: {
            top:    { style: 'medium', color: { rgb: '1E8449' } },
            bottom: { style: 'medium', color: { rgb: '1E8449' } },
            left:   { style: 'thin',  color: { rgb: '1E8449' } },
            right:  { style: 'thin',  color: { rgb: '1E8449' } }
        }
    },
    moneyCell: {
        font: { sz: 10, color: { rgb: '1A5276' } },
        alignment: { horizontal: 'right' },
        fill: { fgColor: { rgb: 'EBF5FB' } },
        border: {
            top:    { style: 'thin', color: { rgb: 'E2E8F4' } },
            bottom: { style: 'thin', color: { rgb: 'E2E8F4' } },
            left:   { style: 'thin', color: { rgb: 'E2E8F4' } },
            right:  { style: 'thin', color: { rgb: 'E2E8F4' } }
        }
    },
    statLabel: {
        font: { bold: true, sz: 11, color: { rgb: '3A537C' } },
        fill: { fgColor: { rgb: 'EBF0F8' } },
        border: { bottom: { style: 'thin', color: { rgb: 'B0BED4' } } }
    },
    statValue: {
        font: { bold: true, sz: 13, color: { rgb: '1A2340' } },
        alignment: { horizontal: 'right' },
        fill: { fgColor: { rgb: 'FFFFFF' } },
        border: { bottom: { style: 'thin', color: { rgb: 'B0BED4' } } }
    },
    footer: {
        font: { italic: true, sz: 9, color: { rgb: 'AAAAAA' } },
        alignment: { horizontal: 'right' }
    }
};

function applyRangeBorder(ws, r1, c1, r2, c2, isEven) {
    for (let r = r1; r <= r2; r++) {
        for (let c = c1; c <= c2; c++) {
            const addr = XLSX.utils.encode_cell({ r, c });
            if (!ws[addr]) ws[addr] = { t: 's', v: '' };
            ws[addr].s = (r % 2 === 0) ? { ...S.rowEven } : { ...S.rowOdd };
        }
    }
}

// ════════════════════════════════════════════
// SHEET 1: TỔNG QUAN
// ════════════════════════════════════════════
function makeSheetTongQuan() {
    const now = new Date().toLocaleString('vi-VN');

    // Đảm bảo đủ 7 ngày kể cả hôm nay, ngày thiếu data thì = 0
    const today = new Date();
    const full7Labels = [];
    const full7Data   = [];
    for (let i = 6; i >= 0; i--) {
        const d = new Date(today);
        d.setDate(today.getDate() - i);
        const dd   = String(d.getDate()).padStart(2,'0');
        const mm   = String(d.getMonth()+1).padStart(2,'0');
        const label = dd + '/' + mm;
        full7Labels.push(label);
        // Tìm trong _7ngay (label dạng dd/mm)
        const idx = _7ngay.labels.indexOf(label);
        full7Data.push(idx >= 0 ? _7ngay.data[idx] : 0);
    }

    const rows = [
        ['BÁO CÁO TỔNG QUAN HỆ THỐNG - HEARTTIE', '', ''],
        ['Xuất lúc: ' + now, '', ''],
        [''],
        ['CHỈ SỐ', 'GIÁ TRỊ', 'GHI CHÚ'],
        ['Doanh thu hôm nay',   fmtVND(_stats.doanhThuHomNay),   'Đơn hoàn thành/đã giao'],
        ['Doanh thu tháng này', fmtVND(_stats.doanhThuThangNay), 'Tháng ' + new Date().toLocaleString('vi-VN',{month:'2-digit',year:'numeric'})],
        ['Tổng đơn hàng',       fmtNum(_stats.tongDonHang),      'Tất cả trạng thái'],
        ['Đơn chờ xử lý',       fmtNum(_stats.donChoXuLy),       'Cần xử lý'],
        ['Tổng người dùng',     fmtNum(_stats.tongNguoiDung),    'Không tính Admin'],
        ['Sản phẩm hết hàng',   fmtNum(_stats.sanPhamHetHang),   'Cần nhập thêm'],
        [''],
        ['DOANH THU 7 NGÀY GẦN NHẤT', '', ''],
        ['Ngày', 'Doanh thu (đồng)', 'Doanh thu (triệu đồng)'],
    ];

    let tong7 = 0;
    full7Labels.forEach((n, i) => {
        const v = full7Data[i];
        tong7 += v;
        rows.push([n, fmtVND(v), parseFloat((v/1000000).toFixed(2)) + ' tr']);
    });
    rows.push(['TỔNG CỘNG 7 NGÀY', fmtVND(tong7), parseFloat((tong7/1000000).toFixed(2)) + ' tr']);

    const ws = XLSX.utils.aoa_to_sheet(rows);
    ws['!cols'] = [{ wch: 30 }, { wch: 26 }, { wch: 26 }];
    ws['!merges'] = [
        { s:{r:0,c:0}, e:{r:0,c:2} },
        { s:{r:1,c:0}, e:{r:1,c:2} },
        { s:{r:11,c:0}, e:{r:11,c:2} },
    ];

    // Style title
    styleCell(ws, 'A1', S.mainTitle);
    styleCell(ws, 'A2', S.subTitle);
    // Style headers
    ['A4','B4','C4'].forEach(a => styleCell(ws, a, S.header));
    // Style stat rows
    for (let r = 4; r <= 9; r++) {
        styleCell(ws, XLSX.utils.encode_cell({r, c:0}), S.statLabel);
        styleCell(ws, XLSX.utils.encode_cell({r, c:1}), S.statValue);
        styleCell(ws, XLSX.utils.encode_cell({r, c:2}), S.rowEven);
    }
    // Style section heading
    styleCell(ws, 'A12', S.mainTitle);
    // Style chart headers
    ['A13','B13','C13'].forEach(a => styleCell(ws, a, S.header));
    // Style chart rows
    for (let r = 13; r <= 13 + _7ngay.labels.length - 1; r++) {
        const s = (r % 2 === 0) ? S.rowEven : S.rowOdd;
        [0,1,2].forEach(c => styleCell(ws, XLSX.utils.encode_cell({r, c}), s));
        styleCell(ws, XLSX.utils.encode_cell({r, c:1}), S.moneyCell);
    }
    // Total row
    const totalR = 13 + _7ngay.labels.length;
    [0,1,2].forEach(c => styleCell(ws, XLSX.utils.encode_cell({r:totalR, c}), S.totalRow));

    ws['!rows'] = [{hpt:30},{hpt:18}];
    return ws;
}

// ════════════════════════════════════════════
// SHEET 2: DOANH THU THEO THÁNG
// ════════════════════════════════════════════
function makeSheetDoanhThuThang() {
    const rows = [
        ['DOANH THU 12 THÁNG GẦN NHẤT - HEARTTIE', '', '', ''],
        [''],
        ['Tháng', 'Số đơn', 'Doanh thu (đồng)', 'Doanh thu (triệu đồng)'],
    ];

    let tongTien = 0, tongDon = 0;
    _thang.forEach(t => {
        tongTien += parseInt(t.tong);
        tongDon  += parseInt(t.so_don);
        rows.push([
            t.thang,
            fmtNum(t.so_don),
            fmtVND(parseInt(t.tong)),
            parseFloat((parseInt(t.tong)/1000000).toFixed(2)) + ' tr'
        ]);
    });
    rows.push(['TỔNG CỘNG', fmtNum(tongDon), fmtVND(tongTien), parseFloat((tongTien/1000000).toFixed(2)) + ' tr']);
    rows.push(['']);
    rows.push(['', '', 'Trung bình/tháng:', fmtVND(Math.round(tongTien / Math.max(_thang.length,1)))]);

    const ws = XLSX.utils.aoa_to_sheet(rows);
    ws['!cols'] = [{ wch: 14 }, { wch: 12 }, { wch: 26 }, { wch: 24 }];
    ws['!merges'] = [{ s:{r:0,c:0}, e:{r:0,c:3} }];

    styleCell(ws, 'A1', S.mainTitle);
    ['A3','B3','C3','D3'].forEach(a => styleCell(ws, a, S.header));
    for (let r = 3; r < 3 + _thang.length; r++) {
        const s = (r % 2 === 0) ? S.rowEven : S.rowOdd;
        [0,1,2,3].forEach(c => styleCell(ws, XLSX.utils.encode_cell({r,c}), s));
        styleCell(ws, XLSX.utils.encode_cell({r,c:2}), S.moneyCell);
    }
    const tr = 3 + _thang.length;
    [0,1,2,3].forEach(c => styleCell(ws, XLSX.utils.encode_cell({r:tr,c}), S.totalRow));

    return ws;
}

// ════════════════════════════════════════════
// SHEET 3: TOP SẢN PHẨM
// ════════════════════════════════════════════
function makeSheetTopSanPham() {
    const rows = [
        ['TOP 10 SẢN PHẨM BÁN CHẠY - HEARTTIE', '', '', ''],
        [''],
        ['#', 'Tên sản phẩm', 'Danh mục', 'Số lượng bán', 'Doanh thu (đồng)'],
    ];

    _topSP.forEach((sp, i) => {
        rows.push([
            i + 1,
            sp.TEN_SP,
            sp.TEN_DM,
            fmtNum(sp.tong_sl),
            fmtVND(parseInt(sp.tong_dt))
        ]);
    });

    const ws = XLSX.utils.aoa_to_sheet(rows);
    ws['!cols'] = [{ wch: 5 }, { wch: 40 }, { wch: 16 }, { wch: 16 }, { wch: 26 }];
    ws['!merges'] = [{ s:{r:0,c:0}, e:{r:0,c:4} }];

    styleCell(ws, 'A1', S.mainTitle);
    ['A3','B3','C3','D3','E3'].forEach(a => styleCell(ws, a, S.header));
    for (let r = 3; r < 3 + _topSP.length; r++) {
        const s = (r % 2 === 0) ? S.rowEven : S.rowOdd;
        [0,1,2,3,4].forEach(c => styleCell(ws, XLSX.utils.encode_cell({r,c}), s));
        styleCell(ws, XLSX.utils.encode_cell({r,c:4}), S.moneyCell);
    }
    return ws;
}

// ════════════════════════════════════════════
// SHEET 4: TRẠNG THÁI ĐƠN HÀNG
// ════════════════════════════════════════════
function makeSheetTrangThai() {
    const rows = [
        ['THỐNG KÊ ĐƠN HÀNG THEO TRẠNG THÁI - HEARTTIE', '', ''],
        [''],
        ['Trạng thái', 'Số đơn', 'Tổng tiền (đồng)'],
    ];

    let tongDon = 0, tongTien = 0;
    _tt.forEach(t => {
        tongDon  += parseInt(t.so_don);
        tongTien += parseInt(t.tong_tien);
        rows.push([t.TRANG_THAI, fmtNum(t.so_don), fmtVND(parseInt(t.tong_tien))]);
    });
    rows.push(['TỔNG CỘNG', fmtNum(tongDon), fmtVND(tongTien)]);

    const ws = XLSX.utils.aoa_to_sheet(rows);
    ws['!cols'] = [{ wch: 22 }, { wch: 14 }, { wch: 26 }];
    ws['!merges'] = [{ s:{r:0,c:0}, e:{r:0,c:2} }];

    styleCell(ws, 'A1', S.mainTitle);
    ['A3','B3','C3'].forEach(a => styleCell(ws, a, S.header));
    for (let r = 3; r < 3 + _tt.length; r++) {
        const s = (r % 2 === 0) ? S.rowEven : S.rowOdd;
        [0,1,2].forEach(c => styleCell(ws, XLSX.utils.encode_cell({r,c}), s));
        styleCell(ws, XLSX.utils.encode_cell({r,c:2}), S.moneyCell);
    }
    const tr = 3 + _tt.length;
    [0,1,2].forEach(c => styleCell(ws, XLSX.utils.encode_cell({r:tr,c}), S.totalRow));

    return ws;
}

// ════════════════════════════════════════════
// SHEET 5: CHI TIẾT ĐƠN HÀNG 30 NGÀY
// ════════════════════════════════════════════
function makeSheetDonHang() {
    const rows = [
        ['CHI TIẾT ĐƠN HÀNG 30 NGÀY GẦN NHẤT - HEARTTIE', '', '', '', '', ''],
        [''],
        ['Mã đơn', 'Khách hàng', 'Email', 'Tổng tiền (đồng)', 'Trạng thái', 'Thanh toán', 'Ngày đặt'],
    ];

    _donhang.forEach(dh => {
        rows.push([
            '#DH' + String(dh.MA_DH).padStart(6,'0'),
            dh.TEN_DAY_DU || 'Khách',
            dh.EMAIL || '',
            fmtVND(parseInt(dh.TONG_TIEN)),
            dh.TRANG_THAI,
            dh.PTTT,
            dh.ngay_dat
        ]);
    });

    const ws = XLSX.utils.aoa_to_sheet(rows);
    ws['!cols'] = [{ wch: 12 }, { wch: 22 }, { wch: 26 }, { wch: 22 }, { wch: 16 }, { wch: 16 }, { wch: 20 }];
    ws['!merges'] = [{ s:{r:0,c:0}, e:{r:0,c:6} }];

    styleCell(ws, 'A1', S.mainTitle);
    ['A3','B3','C3','D3','E3','F3','G3'].forEach(a => styleCell(ws, a, S.header));
    for (let r = 3; r < 3 + _donhang.length; r++) {
        const s = (r % 2 === 0) ? S.rowEven : S.rowOdd;
        [0,1,2,3,4,5,6].forEach(c => styleCell(ws, XLSX.utils.encode_cell({r,c}), s));
        styleCell(ws, XLSX.utils.encode_cell({r,c:3}), S.moneyCell);
    }
    return ws;
}

// ════════════════════════════════════════════
// XUẤT FILE
// ════════════════════════════════════════════
function xuatExcel() {
    const btn = document.querySelector('.export-btn');
    btn.textContent = ' Đang xuất...';
    btn.disabled = true;

    setTimeout(() => {
        try {
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, makeSheetTongQuan(),      ' Tổng Quan');
            XLSX.utils.book_append_sheet(wb, makeSheetDoanhThuThang(),  ' Theo Tháng');
            XLSX.utils.book_append_sheet(wb, makeSheetTopSanPham(),     ' Top Sản Phẩm');
            XLSX.utils.book_append_sheet(wb, makeSheetTrangThai(),      ' Trạng Thái');
            XLSX.utils.book_append_sheet(wb, makeSheetDonHang(),        ' Chi Tiết Đơn');

            const fileName = 'BaoCao_Hearttie' + '.xlsx';
            XLSX.writeFile(wb, fileName);
        } catch(e) {
            alert('Lỗi xuất file: ' + e.message);
        }
        btn.textContent = '⬇ Xuất Excel';
        btn.disabled = false;
    }, 100);
}
</script>

</body>
</html>