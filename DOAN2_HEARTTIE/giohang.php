<?php
session_start();
$conn = new mysqli("localhost", "root", "", "qlchdt");
mysqli_set_charset($conn, "utf8mb4");

// Kiểm tra đăng nhập
if (!isset($_SESSION['MA_ND'])) {
    header("Location: dangnhap_user.php?redirect=giohang.php");
    exit;
}

$ma_nd = (int)$_SESSION['MA_ND'];

/* ==== XỬ LÝ HÀNH ĐỘNG (+, -, xóa) ==== */
if (isset($_GET['act'], $_GET['ma_sp'])) {
    $ma_sp = (int)$_GET['ma_sp'];
    $act = $_GET['act'];

    if ($act == 'plus') {
        $sql = "UPDATE GIOHANG SET SO_LUONG = SO_LUONG + 1 
                WHERE MA_ND = ? AND MA_SP = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $ma_nd, $ma_sp);
        $stmt->execute();
        $_SESSION['cart_success'] = " Đã tăng số lượng sản phẩm!";
        
    } elseif ($act == 'minus') {
        // Kiểm tra số lượng hiện tại
        $check_stmt = $conn->prepare("SELECT SO_LUONG FROM GIOHANG WHERE MA_ND = ? AND MA_SP = ?");
        $check_stmt->bind_param("ii", $ma_nd, $ma_sp);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['SO_LUONG'] > 1) {
            $sql = "UPDATE GIOHANG SET SO_LUONG = SO_LUONG - 1 
                    WHERE MA_ND = ? AND MA_SP = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $ma_nd, $ma_sp);
            $stmt->execute();
            $_SESSION['cart_success'] = " Đã giảm số lượng sản phẩm!";
        } else {
            $_SESSION['cart_error'] = " Số lượng tối thiểu là 1!";
        }
        
    } elseif ($act == 'delete') {
        $sql = "DELETE FROM GIOHANG WHERE MA_ND = ? AND MA_SP = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $ma_nd, $ma_sp);
        $stmt->execute();
        $_SESSION['cart_success'] = "✅ Đã xóa sản phẩm khỏi giỏ hàng!";
    }

    header("Location: giohang.php");
    exit;
}

/* ==== LẤY DANH SÁCH GIỎ HÀNG ==== */
// Lưu ý: g.GIA là giá KHUYẾN MÃI (đã giảm giá), không phải giá gốc
// Giá này được tính và lưu vào database khi thêm sản phẩm vào giỏ
$sql = "
SELECT g.MA_SP, s.TEN_SP, s.HINH_ANH, g.GIA, g.SO_LUONG
FROM GIOHANG g
JOIN SANPHAM s ON g.MA_SP = s.MA_SP
WHERE g.MA_ND = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $ma_nd);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Giỏ Hàng</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --blue: #4A92E4;
      --blue-dark: #2f7ed4;
      --blue-light: #e8f2fd;
      --red: #e53935;
      --text: #1a1a2e;
      --text-muted: #6b7280;
      --border: #e5e9f0;
      --bg: #f5f8ff;
      --white: #ffffff;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      font-family: 'Be Vietnam Pro', Arial, sans-serif;
      background: var(--bg);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    /* ── HEADER ── */
    .site-header {
      background: var(--blue);
      padding: 0 32px;
      height: 62px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      box-shadow: 0 3px 14px rgba(74,146,228,0.3);
      flex-shrink: 0;
    }
    .header-left {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .back-link {
      color: rgba(255,255,255,0.85);
      font-size: 18px;
      text-decoration: none;
      transition: color 0.2s;
    }
    .back-link:hover { color: white; }
    .header-logo {
      color: white;
      font-size: 20px;
      font-weight: 800;
      letter-spacing: 2px;
      text-transform: uppercase;
    }
    .header-logo span {
      display: inline-block;
      background: rgba(255,255,255,0.22);
      border-radius: 6px;
      padding: 2px 10px;
    }
    .header-right {
      display: flex;
      align-items: center;
      gap: 6px;
      color: white;
      font-size: 14px;
      font-weight: 600;
      opacity: 0.9;
    }
    .header-right i { font-size: 18px; }

    /* ── MAIN ── */
    .main-wrapper {
      flex: 1;
      padding: 32px 24px 48px;
      max-width: 1160px;
      margin: 0 auto;
      width: 100%;
    }

    .page-heading {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 24px;
      padding-bottom: 16px;
      border-bottom: 2px solid var(--border);
    }
    .page-heading i { color: var(--blue); font-size: 22px; }
    .page-heading h1 {
      font-size: 22px;
      font-weight: 700;
      color: var(--text);
    }
    .item-count {
      margin-left: auto;
      background: var(--blue-light);
      color: var(--blue);
      font-size: 13px;
      font-weight: 600;
      padding: 4px 12px;
      border-radius: 20px;
    }

    /* ── 2-COLUMN LAYOUT ── */
    .cart-grid {
      display: grid;
      grid-template-columns: 1fr 340px;
      gap: 24px;
      align-items: start;
    }

    /* ── ITEMS PANEL ── */
    .items-panel {
      background: var(--white);
      border-radius: 16px;
      border: 1px solid var(--border);
      overflow: hidden;
    }

    /* table header */
    .items-thead {
      display: grid;
      grid-template-columns: 2fr 1fr 1fr auto;
      padding: 12px 20px;
      background: var(--blue-light);
      font-size: 12px;
      font-weight: 700;
      color: var(--blue);
      text-transform: uppercase;
      letter-spacing: 0.6px;
      border-bottom: 1px solid var(--border);
    }
    .thead-qty, .thead-total { text-align: center; }

    /* cart row */
    .cart-item {
      display: grid;
      grid-template-columns: 2fr 1fr 1fr auto;
      align-items: center;
      padding: 16px 20px;
      border-bottom: 1px solid var(--border);
      transition: background 0.15s;
    }
    .cart-item:last-child { border-bottom: none; }
    .cart-item:hover { background: #fafcff; }

    .item-product {
      display: flex;
      align-items: center;
      gap: 14px;
      min-width: 0;
    }
    .item-product img {
      width: 72px; height: 72px;
      object-fit: cover;
      border-radius: 10px;
      border: 1px solid var(--border);
      flex-shrink: 0;
    }
    .item-meta { min-width: 0; }
    .item-meta h3 {
      font-size: 15px;
      font-weight: 600;
      color: var(--text);
      margin-bottom: 4px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    .item-price {
      font-size: 14px;
      font-weight: 700;
      color: var(--red);
    }

    /* qty */
    .item-qty { display: flex; justify-content: center; }
    .qty-controls {
      display: inline-flex;
      align-items: center;
      border: 1.5px solid var(--border);
      border-radius: 8px;
      overflow: hidden;
    }
    .qty-btn {
      width: 32px; height: 32px;
      background: var(--blue-light);
      color: var(--blue);
      border: none;
      display: flex; align-items: center; justify-content: center;
      cursor: pointer;
      font-size: 12px;
      text-decoration: none;
      transition: background 0.15s, color 0.15s;
    }
    .qty-btn:hover { background: var(--blue); color: white; }
    .qty-display {
      min-width: 36px;
      text-align: center;
      font-size: 14px;
      font-weight: 700;
      color: var(--text);
      background: white;
      line-height: 32px;
    }

    /* subtotal */
    .item-subtotal {
      text-align: center;
      font-size: 15px;
      font-weight: 700;
      color: var(--text);
    }

    /* delete */
    .remove-btn {
      background: none;
      border: none;
      color: #ccc;
      font-size: 16px;
      cursor: pointer;
      padding: 6px 8px;
      border-radius: 6px;
      transition: color 0.2s, background 0.2s;
      line-height: 1;
    }
    .remove-btn:hover { color: var(--red); background: #fff0f0; }

    /* ── SUMMARY PANEL ── */
    .summary-panel {
      background: var(--white);
      border-radius: 16px;
      border: 1px solid var(--border);
      overflow: hidden;
      position: sticky;
      top: 20px;
    }
    .summary-header {
      background: var(--blue);
      color: white;
      padding: 14px 20px;
      font-size: 15px;
      font-weight: 700;
      letter-spacing: 0.4px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .summary-body { padding: 20px; }

    .summary-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 14px;
      color: var(--text-muted);
      padding: 9px 0;
      border-bottom: 1px dashed var(--border);
    }
    .summary-row:last-of-type { border-bottom: none; }
    .free-ship { color: #22c55e; font-weight: 600; }

    .summary-total {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin: 16px 0;
      padding: 14px 16px;
      background: var(--blue-light);
      border-radius: 10px;
    }
    .summary-total .lbl {
      font-size: 15px;
      font-weight: 700;
      color: var(--text);
    }
    .summary-total .amt {
      font-size: 22px;
      font-weight: 800;
      color: var(--red);
    }

    .checkout-btn {
      width: 100%;
      background: var(--blue);
      color: white;
      border: none;
      border-radius: 10px;
      padding: 14px;
      font-size: 15px;
      font-weight: 700;
      font-family: inherit;
      cursor: pointer;
      display: flex; align-items: center; justify-content: center; gap: 8px;
      transition: background 0.2s, transform 0.15s;
      box-shadow: 0 4px 14px rgba(74,146,228,0.35);
    }
    .checkout-btn:hover {
      background: var(--blue-dark);
      transform: translateY(-1px);
    }

    .back-shop-link {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      margin-top: 12px;
      color: var(--blue);
      font-size: 13px;
      font-weight: 500;
      text-decoration: none;
      transition: opacity 0.2s;
    }
    .back-shop-link:hover { opacity: 0.7; }

    /* ── EMPTY STATE ── */
    .empty-wrap {
      grid-column: 1 / -1;
    }
    .empty-cart {
      text-align: center;
      padding: 80px 20px;
      background: white;
      border-radius: 16px;
      border: 1px solid var(--border);
    }
    .empty-cart i { font-size: 60px; color: #c8dcf5; margin-bottom: 16px; display: block; }
    .empty-cart h3 { font-size: 20px; font-weight: 700; color: var(--text); margin-bottom: 8px; }
    .empty-cart p { color: var(--text-muted); font-size: 14px; }
    .continue-shopping {
      display: inline-flex; align-items: center; gap: 8px;
      margin-top: 24px;
      padding: 12px 28px;
      background: var(--blue);
      color: white;
      text-decoration: none;
      border-radius: 10px;
      font-weight: 600;
      font-size: 15px;
      transition: background 0.2s;
    }
    .continue-shopping:hover { background: var(--blue-dark); }

    /* ── TOAST ── */
    .toast {
      position: fixed; top: 20px; right: 20px;
      padding: 13px 20px; border-radius: 10px;
      box-shadow: 0 4px 18px rgba(0,0,0,0.13);
      z-index: 9999;
      display: flex; align-items: center; gap: 10px;
      font-size: 14px; font-weight: 500;
      animation: slideIn 0.3s ease-out;
    }
    .toast.success { background: #22c55e; color: white; }
    .toast.error   { background: var(--red); color: white; }
    .toast.hide    { animation: slideOut 0.3s ease-out forwards; }
    @keyframes slideIn { from { transform: translateX(380px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    @keyframes slideOut { from { transform: translateX(0); opacity: 1; } to { transform: translateX(380px); opacity: 0; } }

    /* ── DELETE MODAL ── */
    .modal-overlay {
      position: fixed; inset: 0;
      background: rgba(26,26,46,0.45);
      backdrop-filter: blur(4px);
      z-index: 10000;
      display: flex; align-items: center; justify-content: center;
      opacity: 0; pointer-events: none;
      transition: opacity 0.25s;
    }
    .modal-overlay.show {
      opacity: 1; pointer-events: all;
    }
    .modal-box {
      background: white;
      border-radius: 20px;
      padding: 36px 32px 28px;
      width: 360px;
      max-width: 92vw;
      text-align: center;
      box-shadow: 0 20px 60px rgba(74,146,228,0.18);
      transform: scale(0.88) translateY(16px);
      transition: transform 0.28s cubic-bezier(.34,1.56,.64,1);
    }
    .modal-overlay.show .modal-box {
      transform: scale(1) translateY(0);
    }
    .modal-icon {
      width: 64px; height: 64px;
      background: #fff0f0;
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 18px;
    }
    .modal-icon i { font-size: 26px; color: var(--red); }
    .modal-box h3 {
      font-size: 18px; font-weight: 700;
      color: var(--text); margin-bottom: 8px;
    }
    .modal-box p {
      font-size: 14px; color: var(--text-muted); line-height: 1.6;
      margin-bottom: 26px;
    }
    .modal-actions {
      display: flex; gap: 10px;
    }
    .modal-btn {
      flex: 1; padding: 12px;
      border-radius: 10px; border: none;
      font-size: 14px; font-weight: 700;
      font-family: inherit; cursor: pointer;
      transition: all 0.18s;
    }
    .modal-btn-cancel {
      background: var(--blue-light);
      color: var(--blue);
    }
    .modal-btn-cancel:hover { background: #d4e8fa; }
    .modal-btn-delete {
      background: var(--red);
      color: white;
      box-shadow: 0 4px 12px rgba(229,57,53,0.3);
    }
    .modal-btn-delete:hover {
      background: #c62828;
      transform: translateY(-1px);
      box-shadow: 0 6px 16px rgba(229,57,53,0.38);
    }

    /* ── FOOTER ── */
    .site-footer {
      background: #d6d6d6;
      color: #555;
      text-align: center;
      padding: 16px 20px;
      font-size: 12.5px;
      line-height: 1.8;
      flex-shrink: 0;
    }

    /* ── RESPONSIVE ── */
    @media (max-width: 860px) {
      .cart-grid { grid-template-columns: 1fr; }
      .summary-panel { position: static; }
      .items-thead { display: none; }
      .cart-item {
        grid-template-columns: 1fr;
        gap: 12px;
      }
      .item-qty, .item-subtotal { justify-content: flex-start; text-align: left; }
    }
  </style>
</head>
<body>

<!-- HEADER -->
<header class="site-header">
  <div class="header-left">
    <a href="trangchu.php" class="back-link"><i class="fas fa-arrow-left"></i></a>
    <!-- <div class="header-logo"><span>HEARTTIE</span></div> -->
  </div>
  <div class="header-right">
    <i class="fas fa-shopping-cart"></i>
    <!-- <span>Giỏ hàng</span> -->
  </div>
</header>

<!-- MAIN -->
<div class="main-wrapper">
  <div class="page-heading">
    <i class="fas fa-shopping-bag"></i>
    <h1>Giỏ hàng của bạn</h1>
    <?php if ($result->num_rows > 0): ?>
      <span class="item-count"><?php echo $result->num_rows; ?> sản phẩm</span>
    <?php endif; ?>
  </div>

  <div class="cart-grid">

  <?php if ($result->num_rows == 0): ?>
    <div class="empty-wrap">
      <div class="empty-cart">
        <i class="fas fa-shopping-cart"></i>
        <h3>Giỏ hàng đang trống</h3>
        <p>Hãy thêm sản phẩm vào giỏ hàng để tiếp tục mua sắm!</p>
        <a href="trangchu.php" class="continue-shopping">
          <i class="fas fa-arrow-left"></i> Tiếp tục mua sắm
        </a>
      </div>
    </div>
  <?php else: ?>
    <!-- ITEMS PANEL -->
    <div class="items-panel">
      <div class="items-thead">
        <span>Sản phẩm</span>
        <span class="thead-qty">Số lượng</span>
        <span class="thead-total">Thành tiền</span>
        <span></span>
      </div>
      <?php
      $tong = 0;
      while($row = $result->fetch_assoc()):
          $thanhtien = $row['GIA'] * $row['SO_LUONG'];
          $tong += $thanhtien;
      ?>
      <div class="cart-item">
        <div class="item-product">
          <img src="<?php echo htmlspecialchars($row['HINH_ANH']); ?>"
               alt="<?php echo htmlspecialchars($row['TEN_SP']); ?>">
          <div class="item-meta">
            <h3><?php echo htmlspecialchars($row['TEN_SP']); ?></h3>
            <p class="item-price"><?php echo number_format($row['GIA'],0,",","."); ?>đ</p>
          </div>
        </div>
        <div class="item-qty">
          <div class="qty-controls">
            <a href="giohang.php?act=minus&ma_sp=<?php echo $row['MA_SP']; ?>" class="qty-btn">
              <i class="fas fa-minus"></i>
            </a>
            <span class="qty-display"><?php echo $row['SO_LUONG']; ?></span>
            <a href="giohang.php?act=plus&ma_sp=<?php echo $row['MA_SP']; ?>" class="qty-btn">
              <i class="fas fa-plus"></i>
            </a>
          </div>
        </div>
        <div class="item-subtotal"><?php echo number_format($thanhtien,0,",","."); ?>đ</div>
        <button onclick="confirmDelete(<?php echo $row['MA_SP']; ?>)" class="remove-btn" title="Xóa">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <?php endwhile; ?>
    </div>

    <!-- SUMMARY PANEL -->
    <div class="summary-panel">
      <div class="summary-header">
        <i class="fas fa-receipt"></i> Tóm tắt đơn hàng
      </div>
      <div class="summary-body">
        <div class="summary-row">
          <span>Tạm tính</span>
          <span><?php echo number_format($tong,0,",","."); ?>đ</span>
        </div>
        <div class="summary-row">
          <span>Phí vận chuyển</span>
          <span class="free-ship"><i class="fas fa-check-circle"></i> Miễn phí</span>
        </div>
        <div class="summary-total">
          <span class="lbl">Tổng cộng</span>
          <span class="amt"><?php echo number_format($tong,0,",","."); ?>đ</span>
        </div>
        <button onclick="checkout()" class="checkout-btn">
          <i class="fas fa-lock"></i> Thanh toán ngay
        </button>
        <a href="trangchu.php" class="back-shop-link">
          <i class="fas fa-arrow-left"></i> Tiếp tục mua sắm
        </a>
      </div>
    </div>
  <?php endif; ?>

  </div><!-- /.cart-grid -->
</div><!-- /.main-wrapper -->

<!-- DELETE MODAL -->
<div class="modal-overlay" id="deleteModal">
  <div class="modal-box">
    <div class="modal-icon"><i class="fas fa-trash-alt"></i></div>
    <h3>Xóa sản phẩm?</h3>
    <p>Bạn có chắc muốn xóa sản phẩm này khỏi giỏ hàng không?<br>Hành động này không thể hoàn tác.</p>
    <div class="modal-actions">
      <button class="modal-btn modal-btn-cancel" onclick="closeModal()">
        <i class="fas fa-times"></i> Hủy
      </button>
      <button class="modal-btn modal-btn-delete" id="confirmDeleteBtn">
        <i class="fas fa-trash-alt"></i> Xóa
      </button>
    </div>
  </div>
</div>

<!-- FOOTER -->
<footer class="site-footer">
  <p>© 2020 Công ty cổ phần HEARTTIE. GPDKKD: 0303217354 do Sở Cần Thơ cấp ngày 02/01/2015. GPMXH: 21/GP-BTTTT do Bộ Thông Tin và Truyền Thông cấp ngày 11/01/2021.</p>
  <p>Địa chỉ: 128, Nguyễn Văn Cừ (nối dài), Phường An Bình, Quận Ninh Kiều, Thành Phố Cần Thơ.</p>
  <p>Chịu trách nhiệm nội dung: Tiêu Ngọc Thanh Tâm. Tất cả các quyền được bảo lưu.</p>
</footer>

<script>
function showToast(message, type = 'success') {
  const old = document.querySelector('.toast');
  if (old) old.remove();
  const t = document.createElement('div');
  t.className = `toast ${type}`;
  t.innerHTML = `<i class="fas ${type==='success'?'fa-check-circle':'fa-exclamation-circle'}"></i><span>${message}</span>`;
  document.body.appendChild(t);
  setTimeout(() => { t.classList.add('hide'); setTimeout(() => t.remove(), 300); }, 3000);
}

let _pendingDeleteId = null;

function confirmDelete(ma_sp) {
  _pendingDeleteId = ma_sp;
  document.getElementById('deleteModal').classList.add('show');
}

function closeModal() {
  _pendingDeleteId = null;
  document.getElementById('deleteModal').classList.remove('show');
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
  if (_pendingDeleteId) {
    window.location.href = 'giohang.php?act=delete&ma_sp=' + _pendingDeleteId;
  }
});

// Đóng modal khi click nền
document.getElementById('deleteModal').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});

// Đóng modal khi nhấn Escape
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeModal();
});

function checkout() { window.location.href = 'thanhtoan.php'; }

<?php if(isset($_SESSION['cart_success'])): ?>
  showToast('<?php echo $_SESSION['cart_success']; ?>', 'success');
  <?php unset($_SESSION['cart_success']); ?>
<?php endif; ?>
<?php if(isset($_SESSION['cart_error'])): ?>
  showToast('<?php echo $_SESSION['cart_error']; ?>', 'error');
  <?php unset($_SESSION['cart_error']); ?>
<?php endif; ?>
</script>

</body>
</html>