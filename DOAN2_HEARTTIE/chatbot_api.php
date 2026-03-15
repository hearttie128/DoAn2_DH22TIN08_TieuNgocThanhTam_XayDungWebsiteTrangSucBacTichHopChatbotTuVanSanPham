<?php
/**
 * chatbot_api.php - HEARTTIE Chatbot v4.0
 * Nâng cấp: tích hợp Claude AI, hiểu ngôn ngữ tự nhiên nâng cao,
 * nhớ ngữ cảnh thông minh, tư vấn cá nhân hóa, so sánh sản phẩm,
 * hỗ trợ hỏi về size/cách bảo quản/chất liệu bạc
 */

// ==== CẤU HÌNH CLAUDE AI ====
// Đặt API key của bạn tại đây hoặc trong biến môi trường
define('CLAUDE_API_KEY', getenv('ANTHROPIC_API_KEY') ?: 'YOUR_ANTHROPIC_API_KEY');
define('CLAUDE_MODEL',   'claude-haiku-4-5-20251001'); // Haiku nhanh, rẻ, phù hợp chatbot
define('USE_CLAUDE_AI',  CLAUDE_API_KEY !== 'YOUR_ANTHROPIC_API_KEY');

header('Content-Type: application/json; charset=utf-8');

$input       = json_decode(file_get_contents('php://input'), true);
$userMessage = isset($input['message']) ? trim($input['message']) : '';
// Lịch sử hội thoại (mảng [{role, content}] từ frontend)
$history     = isset($input['history']) && is_array($input['history']) ? $input['history'] : [];

if (empty($userMessage)) {
    echo json_encode(['reply' => 'Vui lòng nhập tin nhắn.']);
    exit;
}

/* KET NOI DB */
$conn = @new mysqli('localhost', 'root', '', 'qlchdt', 3306);
$dbOk = !$conn->connect_error;
if ($dbOk) mysqli_set_charset($conn, 'utf8mb4');

/* ══════════════════════════════════════════
   HAM HO TRO HIEN THI
══════════════════════════════════════════ */
function cbPrice(int $p): string {
    return number_format($p, 0, ',', '.') . ' ₫';
}
function cbFinal(int $p, int $d): int {
    return $d > 0 ? (int)round($p * (100 - $d) / 100) : $p;
}
function cbCard(array $sp): string {
    $p    = (int)$sp['GIA'];
    $d    = (int)($sp['GIAM_GIA'] ?? 0);
    $f    = cbFinal($p, $d);
    $maSp = (int)$sp['MA_SP'];
    $link = "chitietsanpham.php?ma_sp={$maSp}";

    $img  = !empty($sp['HINH_ANH'])
        ? "<a href='{$link}'><img src='{$sp['HINH_ANH']}' style='width:64px;height:64px;object-fit:cover;border-radius:8px;float:left;margin-right:10px;border:1px solid #f0d0d0;'></a>"
        : '<div style="width:64px;height:64px;background:#fce8e8;border-radius:8px;float:left;margin-right:10px;display:flex;align-items:center;justify-content:center;font-size:20px;color:#c0392b;">&#128142;</div>';

    $badge = $d > 0
        ? " <span style='background:#e74c3c;color:#fff;padding:1px 6px;border-radius:4px;font-size:11px;'>-{$d}%</span>"
        : '';
    $price = $d > 0
        ? "<b style='color:#c0392b;'>".cbPrice($f)."</b> <s style='color:#bbb;font-size:11px;'>".cbPrice($p)."</s>"
        : "<b style='color:#c0392b;'>".cbPrice($p)."</b>";
    $sl    = (int)$sp['SO_LUONG'];
    $stock = $sl > 0
        ? "<span style='color:#27ae60;font-size:12px;'>Còn {$sl} sản phẩm</span>"
        : "<span style='color:#e74c3c;font-size:12px;'>Hết hàng</span>";
    $dm = !empty($sp['TEN_DM']) ? "<span style='color:#888;font-size:11px;'>{$sp['TEN_DM']}</span><br>" : '';

    return "<div style='background:#fff;border:1px solid #f5dada;border-radius:10px;padding:9px;margin:5px 0;overflow:hidden;line-height:1.6;'>"
         . $img
         . "<div style='overflow:hidden;'>"
         . "<a href='{$link}' style='text-decoration:none;'><b style='font-size:13px;color:#2c1810;'>{$sp['TEN_SP']}</b></a>{$badge}<br>"
         . $dm
         . $price . "<br>" . $stock . "<br>"
         . "<a href='{$link}' style='display:inline-block;margin-top:4px;font-size:12px;color:#c0392b;text-decoration:underline;'>Xem chi tiết</a>"
         . "</div></div>";
}

function cbOrderStatus(array $dh): string {
    $statusColor = [
        'Chờ xử lý'      => '#f39c12',
        'Đang xử lý'     => '#3498db',
        'Đang giao hàng' => '#8e44ad',
        'Hoàn thành'     => '#27ae60',
        'Đã hủy'         => '#e74c3c',
    ];
    $ts   = $dh['TRANG_THAI'] ?? 'Chờ xử lý';
    $col  = $statusColor[$ts] ?? '#888';
    $tong = number_format((int)$dh['TONG_TIEN'], 0, ',', '.') . ' ₫';
    $ngay = date('d/m/Y H:i', strtotime($dh['NGAY_TAO']));
    return "<div style='background:#fff;border:1px solid #e0d0f0;border-radius:10px;padding:10px;margin:5px 0;line-height:1.7;'>"
         . "Đơn <b>#{$dh['MA_DH']}</b> &nbsp;"
         . "<span style='background:{$col};color:#fff;padding:2px 8px;border-radius:4px;font-size:12px;'>{$ts}</span><br>"
         . "Tổng tiền: <b style='color:#c0392b;'>{$tong}</b><br>"
         . "Ngày đặt: {$ngay}<br>"
         . "Giao đến: " . htmlspecialchars($dh['DIA_CHI_GIAO'] ?? 'Chưa cập nhật')
         . "</div>";
}

/* QUERY HELPER */
$q = function(string $sql) use ($conn): array {
    $r    = $conn->query($sql);
    $rows = [];
    if ($r) while ($row = $r->fetch_assoc()) $rows[] = $row;
    return $rows;
};

/* ══════════════════════════════════════════
   CLAUDE AI HELPER
══════════════════════════════════════════ */
function askClaude(string $userMsg, array $history, string $context = ''): string {
    if (!USE_CLAUDE_AI) return '';

    $systemPrompt = "Bạn là trợ lý tư vấn trang sức cho shop Hearttie - chuyên bán trang sức bạc cao cấp cho nữ (dây chuyền, lắc tay, lắc chân, nhẫn, bông tai).

Thông tin shop:
- Hotline/Zalo: 0794 385 228 | Email: hearttie128@gmail.com | Hỗ trợ: 8:00–20:00
- Bảo hành 12 tháng, đổi trả 7 ngày, giao hàng toàn quốc, miễn ship từ 500.000đ
- Chất liệu: Bạc S925, bạc Ta, bạc Ý, mạ vàng, đính đá CZ/Swarovski
- Size nhẫn phổ biến: 14–17 (nữ VN), trung bình size 15-16. Đo bằng thước dây vòng quanh ngón tay rồi tra bảng
- Size lắc tay: 15–18cm, phổ biến 16–17cm. Đo cổ tay + thêm 1–1.5cm
- Bảo quản bạc: tránh nước hoa/hóa chất, lau bằng vải mềm, đựng trong túi kín chống oxi hóa, dùng kem đánh răng để đánh bóng
- Bạc S925 = 92.5% bạc nguyên chất, bền hơn bạc ta, ít bị xỉn hơn

{$context}

Quy tắc trả lời:
1. Trả lời ngắn gọn, thân thiện, bằng tiếng Việt
2. Nếu câu hỏi liên quan đến sản phẩm cụ thể mà bạn không biết, gợi ý khách hỏi thêm hoặc xem catalog
3. KHÔNG bịa đặt giá hay sản phẩm cụ thể - chỉ tư vấn chung
4. Kết thúc bằng câu hỏi gợi mở để tiếp tục tư vấn
5. Dùng HTML cơ bản (<b>, <br>) để định dạng nếu cần
6. Tối đa 150 từ";

    // Chuan bi lich su cho API
    $messages = [];
    foreach (array_slice($history, -8) as $turn) {
        if (!empty($turn['role']) && !empty($turn['content'])) {
            $messages[] = ['role' => $turn['role'], 'content' => $turn['content']];
        }
    }
    $messages[] = ['role' => 'user', 'content' => $userMsg];

    $payload = json_encode([
        'model'      => CLAUDE_MODEL,
        'max_tokens' => 400,
        'system'     => $systemPrompt,
        'messages'   => $messages,
    ]);

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-api-key: ' . CLAUDE_API_KEY,
            'anthropic-version: 2023-06-01',
        ],
    ]);
    $res  = curl_exec($ch);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($err || !$res) return '';
    $data = json_decode($res, true);
    return $data['content'][0]['text'] ?? '';
}

/* ══════════════════════════════════════════
   CHUAN HOA TIN NHAN - MO RONG
══════════════════════════════════════════ */
$msg = mb_strtolower($userMessage, 'UTF-8');

$normMap = [
    // Danh muc san pham (viet tat / gõ sai pho bien)
    'day chuyen'    => 'dây chuyền', 'day chuyen bac' => 'dây chuyền bạc',
    'dc'            => 'dây chuyền', 'choker'          => 'dây chuyền',
    'lac tay'       => 'lắc tay',   'vong tay'         => 'lắc tay',
    'lac chan'      => 'lắc chân',  'vong chan'         => 'lắc chân',
    'bong tai'      => 'bông tai',  'hoa tai'           => 'bông tai',
    'khuyen tai'    => 'bông tai',  'bong tai'          => 'bông tai',
    'nhan doi'      => 'nhẫn đôi',  'nhan cap'          => 'nhẫn đôi',
    'nhan'          => 'nhẫn',      'ring'              => 'nhẫn',
    // Y dinh mua / tang
    'qua tang'      => 'quà tặng',  'sinh nhat'         => 'sinh nhật',
    'ban gai'       => 'bạn gái',   'nguoi yeu'         => 'người yêu',
    'ny'            => 'người yêu', 'gf'                => 'bạn gái',
    'vo'            => 'vợ',        'me'                => 'mẹ',
    'ba'            => 'mẹ',        'cap doi'           => 'cặp đôi',
    'valentine'     => 'valentine', 'ky niem'           => 'kỷ niệm',
    '8/3'           => 'sinh nhật', '20/10'             => 'sinh nhật',
    // Gia / khuyen mai
    'giam gia'      => 'giảm giá',  'khuyen mai'        => 'khuyến mãi',
    'sale'          => 'giảm giá',  're nhat'           => 'rẻ nhất',
    'gia re'        => 'giá rẻ',    'binh dan'          => 'giá rẻ',
    'tam tien'      => 'tầm tiền',
    // Chat lieu / da
    'dinh da'       => 'đính đá',   'da cz'             => 'đá CZ',
    'pha le'        => 'pha lê',    'kim cuong'         => 'kim cương',
    'ma vang'       => 'mạ vàng',   'bac ta'            => 'bạc ta',
    'bac y'         => 'bạc Ý',     'co 4 la'           => 'cỏ 4 lá',
    'trai tim'      => 'trái tim',  'buom'              => 'bướm',
    // Phong cach
    'de thuong'     => 'dễ thương', 'cute'              => 'dễ thương',
    'sang trong'    => 'sang trọng','ca tinh'           => 'cá tính',
    'don gian'      => 'đơn giản',  'thanh lich'        => 'thanh lịch',
    'lang man'      => 'lãng mạn',
    // Don hang
    'don hang'      => 'đơn hàng',  'kiem tra don'      => 'kiểm tra đơn',
    'trang thai'    => 'trạng thái','theo doi'          => 'theo dõi',
    'da mua'        => 'đã mua',    'lich su mua'       => 'lịch sử mua',
    // So sanh
    'cai nao tot hon' => 'so sánh', 'nen mua cai nao'  => 'so sánh',
    'khac nhau'     => 'so sánh',   'uu diem'           => 'so sánh',
    'chon cai nao'  => 'so sánh',
    // Chung
    'tat ca'        => 'tất cả',    'toan bo'           => 'tất cả',
    'co gi'         => 'có gì',     'ban gi'            => 'bán gì',
    'xem them'      => 'xem thêm',  'show'              => 'xem thêm',
    // Hoi hang / bao hanh
    'bao hanh'      => 'bảo hành',  'doi tra'           => 'đổi trả',
    'chinh sach'    => 'chính sách','hang that'         => 'hàng thật',
    'chat luong'    => 'chất lượng',
];

arsort($normMap); // xu ly tu khoa dai truoc
foreach ($normMap as $k => $v) {
    $msg = str_replace($k, $v, $msg);
}

$reply = '';

/* ══════════════════════════════════════════
   HELPERS TRICH XUAT THAM SO
══════════════════════════════════════════ */
function parseVND(string $raw): int {
    $raw = str_replace([' ', '.', ','], ['', '', '.'], $raw);
    preg_match('/([\d]+(?:\.\d+)?)\s*(triệu|tr|k|nghìn|ngàn)?/u', $raw, $m);
    if (!$m) return 0;
    $num  = (float)$m[1];
    $unit = strtolower($m[2] ?? '');
    if (in_array($unit, ['triệu', 'tr'])) return (int)($num * 1_000_000);
    if (in_array($unit, ['k', 'nghìn', 'ngàn'])) return (int)($num * 1_000);
    return (int)$num;
}

function extractPriceRange(string $msg): array {
    $min = 0; $max = 0;
    if (preg_match('/từ\s*([\d,.]+\s*(?:triệu|tr|k|nghìn|ngàn)?)\s*(?:đến|tới|den)\s*([\d,.]+\s*(?:triệu|tr|k|nghìn|ngàn)?)/u', $msg, $m)) {
        $min = parseVND($m[1]); $max = parseVND($m[2]);
    } elseif (preg_match('/([\d,.]+)\s*(?:triệu|tr)?\s*[-~]\s*([\d,.]+)\s*(triệu|tr|k|nghìn|ngàn)/u', $msg, $m)) {
        $unit = $m[3];
        $min  = parseVND($m[1] . $unit); $max = parseVND($m[2] . $unit);
    } elseif (preg_match('/(?:dưới|duoi|tối đa|không quá)\s*([\d,.]+\s*(?:triệu|tr|k|nghìn|ngàn)?)/u', $msg, $m)) {
        $max = parseVND($m[1]);
    } elseif (preg_match('/(?:trên|từ|ít nhất|tầm)\s*([\d,.]+\s*(?:triệu|tr|k|nghìn|ngàn)?)/u', $msg, $m)) {
        $min = parseVND($m[1]);
    }
    // Xu ly "tam X trieu" - vi du "tam 1 trieu"
    if ($min === 0 && $max === 0) {
        if (preg_match('/tầm\s*([\d,.]+\s*(?:triệu|tr|k|nghìn|ngàn)?)/u', $msg, $m)) {
            $center = parseVND($m[1]);
            $min = (int)($center * 0.8);
            $max = (int)($center * 1.2);
        }
    }
    return ['min' => $min, 'max' => $max];
}

function extractMaterial(string $msg): string {
    $mats = [
        'đá CZ' => 'CZ', 'pha lê' => 'pha lê', 'swarovski' => 'Swarovski',
        'kim cương' => 'kim cương', 'mạ vàng' => 'mạ vàng',
        'bạc ta' => 'bạc Ta', 'bạc Ý' => 'bạc Ý',
        'trái tim' => 'trái tim', 'hoa' => 'hoa',
        'cỏ 4 lá' => 'cỏ 4 lá', 'bướm' => 'bướm',
        'cá tiên' => 'cá tiên',
    ];
    foreach ($mats as $kw => $label) {
        if (mb_strpos($msg, $kw) !== false) return $label;
    }
    return '';
}

function extractCategories(string $msg): array {
    $cats = [];
    $map = [
        'dây chuyền' => 'Dây chuyền', 'choker' => 'Dây chuyền',
        'lắc tay'    => 'Lắc tay',    'lắc chân' => 'Lắc chân',
        'lắc'        => 'Lắc',
        'nhẫn'       => 'Nhẫn',       'ring' => 'Nhẫn',
        'bông tai'   => 'Bông tai',
    ];
    foreach ($map as $kw => $label) {
        if (mb_strpos($msg, $kw) !== false && !in_array($label, $cats)) {
            $cats[] = $label;
        }
    }
    return $cats;
}

function extractStyle(string $msg): string {
    $styles = [
        'dễ thương' => 'cute',    'sang trọng'  => 'sang',
        'cá tính'   => 'ca tinh', 'đơn giản'    => 'don gian',
        'thanh lịch' => 'thanh lich', 'lãng mạn' => 'lang man',
    ];
    foreach ($styles as $kw => $label) {
        if (mb_strpos($msg, $kw) !== false) return $label;
    }
    return '';
}

// Trich xuat nguoi nhan (ai tang cho)
function extractRecipient(string $msg): string {
    $recipients = ['bạn gái', 'người yêu', 'vợ', 'mẹ', 'chị', 'bạn'];
    foreach ($recipients as $r) {
        if (mb_strpos($msg, $r) !== false) return $r;
    }
    return '';
}

// Lay ngu canh tu lich su (3 tin nhan gan nhat)
function getContextFromHistory(array $history): array {
    $ctx = ['cats' => [], 'range' => ['min' => 0, 'max' => 0], 'recipient' => '', 'material' => ''];
    $recent = array_slice($history, -6); // lay 6 tin nhan gan nhat
    foreach ($recent as $turn) {
        if (($turn['role'] ?? '') !== 'user') continue;
        $m = mb_strtolower($turn['content'] ?? '', 'UTF-8');
        $cats = extractCategories($m);
        if ($cats && empty($ctx['cats'])) $ctx['cats'] = $cats;
        $range = extractPriceRange($m);
        if (($range['min'] > 0 || $range['max'] > 0) && $ctx['range']['min'] === 0 && $ctx['range']['max'] === 0) {
            $ctx['range'] = $range;
        }
        $r = extractRecipient($m);
        if ($r && !$ctx['recipient']) $ctx['recipient'] = $r;
        $mat = extractMaterial($m);
        if ($mat && !$ctx['material']) $ctx['material'] = $mat;
    }
    return $ctx;
}

/* XAY DUNG QUERY SAN PHAM */
function buildProductQuery(mysqli $conn, array $opts): array {
    $where = ["sp.TRANG_THAI = 'Đang bán'"];
    $binds = []; $types = '';

    if (!empty($opts['categories'])) {
        $catConds = [];
        foreach ($opts['categories'] as $cat) {
            if ($cat === 'Lắc') {
                $catConds[] = "dm.TEN_DM LIKE '%Lắc%'";
            } elseif ($cat === 'Lắc tay') {
                $catConds[] = "sp.TEN_SP LIKE '%Lắc tay%'";
            } elseif ($cat === 'Lắc chân') {
                $catConds[] = "sp.TEN_SP LIKE '%Lắc chân%'";
            } else {
                $catConds[] = "dm.TEN_DM LIKE '%" . $conn->real_escape_string($cat) . "%'";
            }
        }
        $where[] = '(' . implode(' OR ', $catConds) . ')';
    }

    if (!empty($opts['material'])) {
        $where[] = "sp.TEN_SP LIKE ?";
        $binds[] = '%' . $opts['material'] . '%';
        $types .= 's';
    }

    if (!empty($opts['keyword'])) {
        $where[] = "(sp.TEN_SP LIKE ? OR dm.TEN_DM LIKE ? OR sp.MO_TA LIKE ?)";
        $kw = '%' . $opts['keyword'] . '%';
        $binds[] = $kw; $binds[] = $kw; $binds[] = $kw;
        $types .= 'sss';
    }

    if (!empty($opts['discount'])) {
        $where[] = "sp.GIAM_GIA > 0";
    }

    if (!empty($opts['style'])) {
        // Map style -> tu khoa trong ten san pham
        $styleKeywords = [
            'cute'      => ['hoa', 'bướm', 'cỏ 4 lá', 'cá tiên'],
            'sang'      => ['swarovski', 'kim cương', 'mạ vàng', 'bạc Ý'],
            'ca tinh'   => ['mắt xích', 'cá tính', 'vuông'],
            'lang man'  => ['trái tim', 'đôi', 'love'],
            'don gian'  => ['đơn giản', 'basic', 'mảnh'],
        ];
        $kws = $styleKeywords[$opts['style']] ?? [];
        if ($kws) {
            $styleConds = [];
            foreach ($kws as $sk) {
                $styleConds[] = "sp.TEN_SP LIKE '%" . $conn->real_escape_string($sk) . "%'";
            }
            $where[] = '(' . implode(' OR ', $styleConds) . ')';
        }
    }

    $priceExpr = "ROUND(sp.GIA * (100 - IFNULL(sp.GIAM_GIA,0)) / 100)";
    if (!empty($opts['min']) && $opts['min'] > 0) {
        $where[] = "{$priceExpr} >= ?";
        $binds[] = $opts['min']; $types .= 'i';
    }
    if (!empty($opts['max']) && $opts['max'] > 0) {
        $where[] = "{$priceExpr} <= ?";
        $binds[] = $opts['max']; $types .= 'i';
    }

    $limit = (int)($opts['limit'] ?? 6);
    $order = !empty($opts['discount']) || !empty($opts['gift'])
        ? "sp.GIAM_GIA DESC, sp.GIA DESC"
        : "sp.GIAM_GIA DESC";

    $sql = "SELECT sp.*, dm.TEN_DM
            FROM SANPHAM sp
            JOIN DANHMUC dm ON sp.MA_DM = dm.MA_DM
            WHERE " . implode(' AND ', $where) . "
            ORDER BY {$order}
            LIMIT {$limit}";

    if (!$types) {
        $r = $conn->query($sql);
        $rows = [];
        if ($r) while ($row = $r->fetch_assoc()) $rows[] = $row;
        return $rows;
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$binds);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    return $rows;
}

/* ══════════════════════════════════════════
   LAY NGU CANH TU LICH SU
══════════════════════════════════════════ */
$ctx = getContextFromHistory($history);

/* ══════════════════════════════════════════
   PHAN TICH INTENT & TAO REPLY
══════════════════════════════════════════ */

// INTENT 1: Chao hoi
if (preg_match('/\b(xin chào|chào|hello|hi|hey|alo|chao|bắt đầu)\b/u', $msg)) {
    $reply = 'Xin chào! Chào mừng bạn đến với <b>Hearttie</b> — trang sức bạc cao cấp.<br><br>'
           . 'Mình có thể giúp bạn:<br>'
           . '— Tìm <b>dây chuyền, lắc tay, lắc chân, nhẫn, bông tai</b><br>'
           . '— Lọc theo <b>khoảng giá</b> (vd: <i>dưới 2 triệu</i>)<br>'
           . '— Gợi ý theo <b>phong cách</b>: dễ thương, sang trọng, cá tính<br>'
           . '— Tư vấn <b>quà tặng</b> phù hợp cho bạn gái, mẹ, vợ...<br>'
           . '— Hỏi về <b>size nhẫn/lắc, cách bảo quản bạc</b>, chất liệu<br>'
           . '— Tra cứu <b>đơn hàng</b> của bạn<br><br>'
           . 'Bạn đang tìm món gì hôm nay?';
}

// INTENT 2: Tam biet / Cam on
elseif (preg_match('/\b(tạm biệt|bye|goodbye|cảm ơn|thank|cam on|tam biet)\b/u', $msg)) {
    $reply = 'Cảm ơn bạn đã ghé Hearttie! Hy vọng bạn tìm được món trang sức ưng ý. Nếu cần tư vấn thêm, cứ nhắn mình nhé!';
}

// INTENT 3: Bao hanh / Chinh sach
elseif (preg_match('/\b(bảo hành|đổi trả|chính sách|hàng thật|chất lượng|giao hàng|ship)\b/u', $msg)) {
    $reply = "<div style='background:#fff;border:1px solid #f5dada;border-radius:10px;padding:12px;margin:6px 0;line-height:2;'>"
           . "<b>Chính sách của Hearttie:</b><br>"
           . "— Bảo hành <b>12 tháng</b> cho tất cả sản phẩm<br>"
           . "— Đổi trả trong <b>7 ngày</b> nếu lỗi từ nhà sản xuất<br>"
           . "— Giao hàng toàn quốc, miễn ship đơn từ <b>500.000đ</b><br>"
           . "— Tất cả sản phẩm đều có <b>giấy chứng nhận</b> chất lượng<br>"
           . "— Hàng chính hãng 100%, cam kết không bán hàng nhái"
           . "</div>"
           . 'Bạn còn câu hỏi nào khác không?';
}

// INTENT 3b: Hoi ve size nhan / lac
elseif (preg_match('/\b(size|cỡ|kích cỡ|kích thước|số nhẫn|cỡ nhẫn|chu vi|đo size)\b/u', $msg)) {
    if (preg_match('/\b(nhẫn|ring)\b/u', $msg)) {
        $reply = "<b>Hướng dẫn chọn size nhẫn:</b><br>"
               . "<div style='background:#fff;border:1px solid #f5dada;border-radius:10px;padding:12px;margin:6px 0;line-height:2;'>"
               . "1. Dùng thước dây (hoặc sợi chỉ) đo vòng quanh ngón đeo nhẫn<br>"
               . "2. Tra bảng: <b>44mm=14</b> | <b>47mm=15</b> | <b>50mm=16</b> | <b>53mm=17</b> | <b>56mm=18</b><br>"
               . "3. Nữ VN thường dùng <b>size 15–16</b> (ngón áp út), size 14–15 (ngón út)<br>"
               . "4. Nên đo vào <b>buổi chiều</b> (ngón tay hơi to hơn buổi sáng)<br>"
               . "5. Nếu không chắc, chọn <b>size to hơn</b> — dễ chỉnh hơn size nhỏ"
               . "</div>"
               . 'Bạn muốn tìm nhẫn phong cách gì? Mình gợi ý ngay!';
    } elseif (preg_match('/\b(lắc|vòng|bracelet)\b/u', $msg)) {
        $reply = "<b>Hướng dẫn chọn size lắc tay:</b><br>"
               . "<div style='background:#fff;border:1px solid #f5dada;border-radius:10px;padding:12px;margin:6px 0;line-height:2;'>"
               . "1. Đo vòng cổ tay bằng thước dây<br>"
               . "2. Cộng thêm <b>1–1.5cm</b> để vừa thoải mái<br>"
               . "3. Size phổ biến nữ VN: <b>15–17cm</b> (trung bình 16cm)<br>"
               . "4. Lắc có khóa tăng giảm: linh hoạt ±1–2cm<br>"
               . "5. Cổ tay nhỏ (dưới 14cm) → chọn lắc mảnh thanh lịch"
               . "</div>"
               . 'Muốn xem lắc tay đang có không?';
    } else {
        $reply = "<b>Hearttie hỗ trợ tư vấn size:</b><br>"
               . "— <b>Nhẫn</b>: đo chu vi ngón tay, size 14–18, phổ biến <b>15–16</b><br>"
               . "— <b>Lắc tay</b>: đo cổ tay + 1–1.5cm, phổ biến <b>15–17cm</b><br>"
               . "— <b>Lắc chân</b>: đo cổ chân + 2–3cm, phổ biến <b>21–24cm</b><br>"
               . "— <b>Dây chuyền</b>: dài 40–45cm (ngắn), 50–60cm (dài)<br><br>"
               . 'Bạn đang cần tư vấn size loại nào?';
    }
}

// INTENT 3c: Hoi ve bao quan / chat lieu bac
elseif (preg_match('/\b(bảo quản|vệ sinh|đánh bóng|xỉn|đen|oxi hóa|bạc S925|s925|bạc ta|bạc ý|chất liệu bạc|bạc thật|bị đen|làm sạch)\b/u', $msg)) {
    if (preg_match('/\b(s925|bạc S925|bạc ta|bạc ý|chất liệu|loại bạc|bạc thật|khác nhau)\b/u', $msg)) {
        $reply = "<b>Phân biệt các loại bạc tại Hearttie:</b><br>"
               . "<div style='background:#fff;border:1px solid #f5dada;border-radius:10px;padding:12px;margin:6px 0;line-height:1.8;'>"
               . "<b>🥈 Bạc S925</b>: 92.5% bạc nguyên chất + 7.5% hợp kim. Bền, ít xỉn, tiêu chuẩn quốc tế. <i>Giá cao hơn nhưng chất lượng tốt nhất.</i><br>"
               . "<b>🥈 Bạc Ta</b>: Bạc truyền thống Việt Nam, màu trắng sáng, dễ tạo hình. Có thể xỉn theo thời gian.<br>"
               . "<b>🥈 Bạc Ý</b>: Nhập khẩu Ý, độ bóng cao, thiết kế tinh xảo, giá cao cấp.<br>"
               . "<b>✨ Mạ vàng</b>: Bạc S925 phủ lớp vàng 18K, màu vàng sang trọng."
               . "</div>"
               . 'Bạn muốn xem sản phẩm chất liệu nào?';
    } else {
        $reply = "<b>Cách bảo quản trang sức bạc:</b><br>"
               . "<div style='background:#fff;border:1px solid #f5dada;border-radius:10px;padding:12px;margin:6px 0;line-height:2;'>"
               . "— Tránh tiếp xúc <b>nước hoa, kem dưỡng, hóa chất</b> (tháo ra khi tắm/bơi)<br>"
               . "— Lau bằng <b>vải mềm khô</b> sau khi đeo, tránh vải thô<br>"
               . "— Đựng trong <b>túi zip kín</b> hoặc hộp riêng để chống oxi hóa<br>"
               . "— Nếu bị xỉn đen: đánh nhẹ bằng <b>kem đánh răng + bàn chải mềm</b>, rửa sạch, lau khô<br>"
               . "— Sản phẩm mạ vàng: tránh đánh bóng mạnh vì có thể mòn lớp mạ"
               . "</div>"
               . 'Bạn đang cần tư vấn thêm về sản phẩm bạc nào không?';
    }
}

// INTENT 4: Tra cuu don hang
elseif (preg_match('/\b(đơn hàng|kiểm tra đơn|trạng thái|theo dõi|lịch sử mua|đã mua|mã đơn)\b/u', $msg)) {
    preg_match('/(?:#|mã|ma|đơn số|don so|don)\s*(\d+)/u', $msg, $orderMatch);
    $specificId = !empty($orderMatch[1]) ? (int)$orderMatch[1] : 0;

    if ($dbOk) {
        if ($specificId > 0) {
            $stmt = $conn->prepare(
                "SELECT dh.*, nd.TEN_DAY_DU
                 FROM DONHANG dh
                 JOIN NGUOIDUNG nd ON dh.MA_ND = nd.MA_ND
                 WHERE dh.MA_DH = ? LIMIT 1"
            );
            $stmt->bind_param('i', $specificId);
            $stmt->execute();
            $res  = $stmt->get_result();
            $rows = [];
            while ($r = $res->fetch_assoc()) $rows[] = $r;

            if ($rows) {
                $dh    = $rows[0];
                $reply = "Thông tin <b>Đơn hàng #{$specificId}</b>:<br>" . cbOrderStatus($dh);
                $stmt2 = $conn->prepare(
                    "SELECT ct.TEN_SP, ct.SO_LUONG, ct.GIA, ct.THANH_TIEN
                     FROM CHITIETDONHANG ct WHERE ct.MA_DH = ?"
                );
                $stmt2->bind_param('i', $specificId);
                $stmt2->execute();
                $res2  = $stmt2->get_result();
                $items = [];
                while ($r = $res2->fetch_assoc()) $items[] = $r;
                if ($items) {
                    $reply .= '<br><b>Sản phẩm trong đơn:</b><br>';
                    $reply .= "<div style='background:#fafafa;border:1px solid #eee;border-radius:8px;padding:8px;'>";
                    foreach ($items as $item) {
                        $tt     = number_format((int)$item['THANH_TIEN'], 0, ',', '.') . ' ₫';
                        $reply .= "— {$item['TEN_SP']} x{$item['SO_LUONG']} — <b>{$tt}</b><br>";
                    }
                    $reply .= "</div>";
                }
            } else {
                $reply = "Mình không tìm thấy đơn <b>#{$specificId}</b>. Bạn kiểm tra lại mã đơn giúp mình nhé!";
            }
        } else {
            $rows = $q("SELECT dh.*, nd.TEN_DAY_DU
                        FROM DONHANG dh
                        JOIN NGUOIDUNG nd ON dh.MA_ND = nd.MA_ND
                        ORDER BY dh.NGAY_TAO DESC LIMIT 5");
            if ($rows) {
                $reply = '<b>Các đơn hàng gần nhất:</b><br>'
                       . implode('', array_map('cbOrderStatus', $rows))
                       . '<br>Nhập <b>"đơn #[mã]"</b> để xem chi tiết sản phẩm trong đơn nhé!';
            } else {
                $reply = 'Chưa có đơn hàng nào. Bạn muốn xem sản phẩm để đặt hàng không?';
            }
        }
    }
}

// INTENT 5: So sanh san pham
elseif (preg_match('/\b(so sánh|cái nào tốt|nên mua cái nào|khác nhau|chọn cái nào|ưu điểm|tốt hơn)\b/u', $msg)) {
    if ($dbOk) {
        // Lay 2 san pham de so sanh (uu tien cung danh muc voi ngu canh)
        $cats      = !empty($cats = extractCategories($msg)) ? $cats : $ctx['cats'];
        $material  = extractMaterial($msg) ?: $ctx['material'];
        $opts      = ['categories' => $cats, 'material' => $material, 'limit' => 2];
        $rows      = buildProductQuery($conn, $opts);

        if (count($rows) >= 2) {
            $a = $rows[0]; $b = $rows[1];
            $fa = cbFinal((int)$a['GIA'], (int)$a['GIAM_GIA']);
            $fb = cbFinal((int)$b['GIA'], (int)$b['GIAM_GIA']);
            $cheaper = $fa < $fb ? $a['TEN_SP'] : $b['TEN_SP'];
            $higherDiscount = ((int)$a['GIAM_GIA'] >= (int)$b['GIAM_GIA']) ? $a['TEN_SP'] : $b['TEN_SP'];

            $reply = "<b>So sánh 2 sản phẩm:</b><br>"
                   . cbCard($a) . cbCard($b)
                   . "<div style='background:#fff3f3;border-left:3px solid #e74c3c;padding:8px 12px;margin:6px 0;font-size:13px;line-height:1.8;'>"
                   . "<b>Nhận xét của mình:</b><br>"
                   . "— Giá tốt hơn: <b>{$cheaper}</b><br>"
                   . "— Đang giảm nhiều hơn: <b>{$higherDiscount}</b><br>"
                   . "— Cả hai đều là bạc S925 cao cấp, bền đẹp theo thời gian<br>"
                   . "— Tùy vào sở thích: thích nổi bật thì chọn kiểu cầu kỳ hơn, thích đơn giản thì chọn kiểu thanh lịch"
                   . "</div>"
                   . 'Bạn thích phong cách nào hơn: <b>đơn giản</b> hay <b>cầu kỳ, lấp lánh</b>?';
        } elseif (count($rows) === 1) {
            $reply = 'Mình chỉ tìm được một sản phẩm phù hợp:<br>' . cbCard($rows[0])
                   . '<br>Bạn muốn so sánh thêm loại nào khác không?';
        } else {
            $reply = 'Bạn muốn so sánh loại trang sức nào? Ví dụ: <b>"so sánh lắc tay"</b> hoặc <b>"so sánh dây chuyền đá CZ"</b>';
        }
    }
}

// INTENT 6: Loc nang cao (khoang gia)
elseif (
    preg_match('/từ\s*[\d,.]+/u', $msg) ||
    preg_match('/\b(dưới|trên|tầm)\s*[\d,.]+/u', $msg) ||
    (preg_match('/[\d,.]+\s*(triệu|tr|k|nghìn|ngàn)/u', $msg) && !preg_match('/đơn|mã/u', $msg))
) {
    $range    = extractPriceRange($msg);
    $cats     = !empty($c = extractCategories($msg)) ? $c : $ctx['cats'];
    $material = extractMaterial($msg) ?: $ctx['material'];
    $style    = extractStyle($msg);

    $opts = [
        'categories' => $cats,
        'min'        => $range['min'],
        'max'        => $range['max'],
        'material'   => $material,
        'style'      => $style,
        'limit'      => 8,
    ];

    if ($dbOk) {
        $rows = buildProductQuery($conn, $opts);

        $filterDesc = '';
        if ($cats)            $filterDesc .= ' | Loại: '     . implode(', ', $cats);
        if ($material)        $filterDesc .= ' | Chất liệu: ' . $material;
        if ($style)           $filterDesc .= ' | Phong cách: ' . $style;
        if ($range['min'] > 0 && $range['max'] > 0) {
            $filterDesc .= ' | Giá: ' . cbPrice($range['min']) . ' – ' . cbPrice($range['max']);
        } elseif ($range['max'] > 0) {
            $filterDesc .= ' | Dưới ' . cbPrice($range['max']);
        } elseif ($range['min'] > 0) {
            $filterDesc .= ' | Trên ' . cbPrice($range['min']);
        }

        if ($rows) {
            $reply = "<b>Kết quả</b>"
                   . ($filterDesc ? " <span style='color:#888;font-size:12px;'>({$filterDesc})</span>" : '')
                   . ":<br>"
                   . implode('', array_map('cbCard', $rows));
            if (count($rows) >= 8) {
                $reply .= '<br>Có khá nhiều lựa chọn đấy! Bạn muốn lọc thêm theo <b>chất liệu</b> hay <b>phong cách</b> không?';
            }
        } else {
            $reply = 'Mình chưa tìm thấy sản phẩm phù hợp với bộ lọc này. '
                   . 'Thử nới rộng khoảng giá hoặc bỏ bớt điều kiện nhé!<br>'
                   . 'Ví dụ: <b>"dây chuyền dưới 2 triệu"</b> hoặc <b>"nhẫn đá CZ"</b>';
        }
    }
}

// INTENT 7: Goi y phong cach
elseif (preg_match('/\b(dễ thương|cute|sang trọng|cá tính|đơn giản|thanh lịch|lãng mạn|trẻ trung|nữ tính)\b/u', $msg)) {
    $style    = extractStyle($msg);
    $cats     = !empty($c = extractCategories($msg)) ? $c : $ctx['cats'];
    $range    = extractPriceRange($msg);
    if ($range['min'] === 0 && $range['max'] === 0) $range = $ctx['range'];

    $opts = [
        'categories' => $cats,
        'style'      => $style,
        'min'        => $range['min'],
        'max'        => $range['max'],
        'limit'      => 5,
    ];

    $styleDesc = [
        'cute'      => 'dễ thương, ngọt ngào',
        'sang'      => 'sang trọng, quý phái',
        'ca tinh'   => 'cá tính, mạnh mẽ',
        'lang man'  => 'lãng mạn, tinh tế',
        'don gian'  => 'đơn giản, thanh lịch',
    ];

    if ($dbOk) {
        $rows = buildProductQuery($conn, $opts);
        if (!$rows) {
            // Fallback: lay san pham theo danh muc neu khong co theo style
            $opts['style'] = '';
            $rows = buildProductQuery($conn, $opts);
        }

        if ($rows) {
            $desc  = $styleDesc[$style] ?? $style;
            $reply = "Dưới đây là những món trang sức phong cách <b>{$desc}</b> đang được yêu thích:<br>"
                   . implode('', array_map('cbCard', $rows))
                   . '<br>Bạn muốn lọc thêm theo khoảng giá không?';
        } else {
            $reply = 'Mình chưa tìm được sản phẩm phù hợp với phong cách này. Thử mô tả thêm để mình tư vấn nhé!';
        }
    }
}

// INTENT 8: Khuyen mai / Giam gia
elseif (preg_match('/\b(giảm giá|khuyến mãi|sale|ưu đãi|rẻ nhất|tiết kiệm|hot|đang sale|flash sale)\b/u', $msg)) {
    $cats     = !empty($c = extractCategories($msg)) ? $c : [];
    $material = extractMaterial($msg);
    $opts     = ['categories' => $cats, 'material' => $material, 'discount' => true, 'limit' => 6];

    if ($dbOk) {
        $rows = buildProductQuery($conn, $opts);
        if ($rows) {
            $catLabel = $cats ? ' (' . implode(', ', $cats) . ')' : '';
            $reply    = "<b>Đang giảm giá{$catLabel}:</b><br>"
                      . implode('', array_map('cbCard', $rows))
                      . '<br>Bạn muốn lọc theo khoảng giá cụ thể không?';
        } else {
            $reply = 'Hiện chưa có chương trình giảm giá mới. Để mình thông báo ngay khi có nhé — liên hệ Zalo <b>0794 385 228</b>!';
        }
    }
}

// INTENT 9: Goi y qua tang
elseif (preg_match('/\b(quà tặng|bạn gái|sinh nhật|valentine|kỷ niệm|cặp đôi|người yêu|vợ|mẹ|chị)\b/u', $msg)) {
    $recipient = extractRecipient($msg) ?: $ctx['recipient'];
    $range     = extractPriceRange($msg);
    if ($range['min'] === 0 && $range['max'] === 0) $range = $ctx['range'];

    $recipientTips = [
        'bạn gái'   => 'Cho bạn gái thì dây chuyền trái tim, nhẫn đôi hoặc lắc tay đính đá CZ sẽ rất ý nghĩa.',
        'người yêu' => 'Cho người yêu, nhẫn cặp hoặc dây chuyền đôi là lựa chọn lãng mạn và đáng nhớ nhất.',
        'vợ'        => 'Cho vợ thì nên chọn những món sang trọng hơn: dây chuyền mạ vàng, nhẫn đính kim cương.',
        'mẹ'        => 'Cho mẹ nên chọn thiết kế nhẹ nhàng, tinh tế: dây chuyền mảnh, nhẫn bạc Ý, bông tai nhỏ.',
        'chị'       => 'Cho chị thì bông tai thời trang hoặc lắc tay thanh lịch sẽ rất phù hợp.',
    ];
    $tip = $recipient ? ($recipientTips[$recipient] ?? '') : '';

    if ($dbOk) {
        // Uu tien san pham mang y nghia tang
        $rows = $q("SELECT sp.*, dm.TEN_DM FROM SANPHAM sp
                    JOIN DANHMUC dm ON sp.MA_DM = dm.MA_DM
                    WHERE sp.TRANG_THAI = 'Đang bán'
                    AND (sp.TEN_SP LIKE '%đôi%' OR sp.TEN_SP LIKE '%trái tim%' OR sp.TEN_SP LIKE '%love%' OR sp.TEN_SP LIKE '%Forever%')
                    ORDER BY sp.GIAM_GIA DESC LIMIT 4");
        if (!$rows) {
            $rows = buildProductQuery($conn, ['gift' => true, 'min' => $range['min'], 'max' => $range['max'], 'limit' => 4]);
        }

        $reply = '<b>Gợi ý quà tặng ý nghĩa:</b><br>';
        if ($tip) {
            $reply .= "<div style='background:#fff3f3;border-left:3px solid #e74c3c;padding:6px 10px;margin:5px 0;font-size:12px;color:#555;'>{$tip}</div>";
        }
        $reply .= implode('', array_map('cbCard', $rows));
        if ($range['max'] === 0) {
            $reply .= '<br>Ngân sách bạn dự kiến khoảng bao nhiêu? Mình sẽ tư vấn chính xác hơn!';
        }
    }
}

// INTENT 10: Tim theo danh muc
elseif (!empty($cats = extractCategories($msg))) {
    $material = extractMaterial($msg) ?: $ctx['material'];
    $style    = extractStyle($msg);
    $range    = extractPriceRange($msg);
    if ($range['min'] === 0 && $range['max'] === 0) $range = $ctx['range'];

    $opts = [
        'categories' => $cats,
        'material'   => $material,
        'style'      => $style,
        'min'        => $range['min'],
        'max'        => $range['max'],
        'limit'      => 6,
    ];

    if ($dbOk) {
        $rows    = buildProductQuery($conn, $opts);
        $catName = implode(' & ', $cats);

        if ($rows) {
            $reply = "<b>{$catName} bạc nữ</b> đang có:<br>"
                   . implode('', array_map('cbCard', $rows));
            if (in_array('Nhẫn', $cats))           $reply .= '<br>Bạn muốn nhẫn đơn hay nhẫn đôi?';
            if (in_array('Lắc', $cats) && count($cats) === 1) $reply .= '<br>Bạn muốn lắc tay hay lắc chân?';
            if (in_array('Dây chuyền', $cats))     $reply .= '<br>Bạn muốn dây chuyền đơn hay dây chuyền cặp (đôi)?';
        } else {
            $reply = "Hiện chưa có sản phẩm <b>{$catName}</b> phù hợp với bộ lọc này. "
                   . "Thử bỏ bớt điều kiện chất liệu xem nhé, hoặc hỏi mình để tư vấn thêm!";
        }
    }
}

// INTENT 11: Xem tat ca san pham
elseif (preg_match('/\b(tất cả|xem hết|có gì|bán gì|sản phẩm|danh sách|xem thêm)\b/u', $msg)) {
    if ($dbOk) {
        $rows  = $q("SELECT sp.*, dm.TEN_DM FROM SANPHAM sp
                     JOIN DANHMUC dm ON sp.MA_DM = dm.MA_DM
                     WHERE sp.TRANG_THAI = 'Đang bán'
                     ORDER BY sp.GIAM_GIA DESC LIMIT 8");
        $stats = $q("SELECT dm.TEN_DM, COUNT(*) AS so_luong
                     FROM SANPHAM sp JOIN DANHMUC dm ON sp.MA_DM = dm.MA_DM
                     WHERE sp.TRANG_THAI = 'Đang bán' GROUP BY dm.TEN_DM");
        $statStr = implode(' — ', array_map(fn($r) => "<b>{$r['TEN_DM']}</b>: {$r['so_luong']} sản phẩm", $stats));

        $reply = "<b>Sản phẩm nổi bật của Hearttie:</b><br>"
               . "<div style='background:#fff3f3;padding:6px 10px;border-radius:6px;font-size:12px;color:#555;margin:4px 0;'>Kho hàng: {$statStr}</div>"
               . implode('', array_map('cbCard', $rows))
               . '<br>Muốn lọc theo danh mục, giá hay phong cách? Cứ hỏi mình nhé!';
    }
}

// INTENT 12: Ho tro / Lien he
elseif (preg_match('/\b(hotline|liên hệ|hỗ trợ|tư vấn|shop|cửa hàng|địa chỉ|zalo|facebook|fb)\b/u', $msg)) {
    $reply = "<b>Thông tin liên hệ Hearttie:</b><br>"
           . "<div style='background:#fff;border:1px solid #f5dada;border-radius:10px;padding:12px;margin:6px 0;line-height:2;'>"
           . 'Hotline/Zalo: <b>0794 385 228</b><br>'
           . 'Email: <b>hearttie128@gmail.com</b><br>'
           . 'Thời gian hỗ trợ: <b>8:00 – 20:00</b> hàng ngày<br>'
           . 'Giao hàng toàn quốc — Đổi trả 7 ngày'
           . '</div>'
           . 'Hoặc bạn cứ chat trực tiếp ở đây, mình sẽ tư vấn ngay!';
}

// INTENT 13: Tim kiem tu do (fallback thong minh)
else {
    $reply = '';
    if ($dbOk && mb_strlen($userMessage) >= 2) {
        $material = extractMaterial($msg) ?: $ctx['material'];
        $cats     = !empty($c = extractCategories($msg)) ? $c : $ctx['cats'];
        $range    = extractPriceRange($msg);
        if ($range['min'] === 0 && $range['max'] === 0) $range = $ctx['range'];
        $style    = extractStyle($msg);

        if ($cats || $material || $style) {
            $opts = [
                'categories' => $cats,
                'material'   => $material,
                'style'      => $style,
                'min'        => $range['min'],
                'max'        => $range['max'],
                'limit'      => 5,
            ];
            $rows = buildProductQuery($conn, $opts);
        } else {
            // Tim kiem full-text
            $kw   = '%' . $userMessage . '%';
            $stmt = $conn->prepare(
                "SELECT sp.*, dm.TEN_DM FROM SANPHAM sp
                 JOIN DANHMUC dm ON sp.MA_DM = dm.MA_DM
                 WHERE sp.TRANG_THAI = 'Đang bán'
                 AND (sp.TEN_SP LIKE ? OR dm.TEN_DM LIKE ? OR sp.MO_TA LIKE ?)
                 ORDER BY sp.GIAM_GIA DESC LIMIT 5"
            );
            $stmt->bind_param('sss', $kw, $kw, $kw);
            $stmt->execute();
            $res  = $stmt->get_result();
            $rows = [];
            while ($r = $res->fetch_assoc()) $rows[] = $r;
        }

        if ($rows) {
            $reply = 'Mình tìm được một số sản phẩm phù hợp:<br>'
                   . implode('', array_map('cbCard', $rows));
        }
    }

    // Khong co ket qua -> goi y tu nhien hoac dung Claude AI
    if (!$reply) {
        // Thu dung Claude AI de tra loi thong minh hon
        if (USE_CLAUDE_AI) {
            // Lay danh sach san pham lam context cho Claude
            $contextProducts = '';
            if ($dbOk) {
                $sampleRows = $q("SELECT TEN_SP, GIA, GIAM_GIA, SO_LUONG FROM SANPHAM WHERE TRANG_THAI='Đang bán' ORDER BY RAND() LIMIT 10");
                if ($sampleRows) {
                    $contextProducts = "Một số sản phẩm hiện có: " . implode(', ', array_map(fn($r) => $r['TEN_SP'] . ' (' . cbPrice(cbFinal((int)$r['GIA'], (int)$r['GIAM_GIA'])) . ')', $sampleRows));
                }
            }
            $aiReply = askClaude($userMessage, $history, $contextProducts);
            if ($aiReply) {
                $reply = $aiReply;
            }
        }

        // Neu van khong co (Claude chua duoc cau hinh hoac loi)
        if (!$reply) {
            if (!empty($ctx['cats']) || $ctx['recipient']) {
                $ctxHint = '';
                if (!empty($ctx['cats'])) $ctxHint .= ' ' . implode(', ', $ctx['cats']);
                if ($ctx['recipient'])    $ctxHint .= ' cho ' . $ctx['recipient'];
                $reply = "Mình chưa hiểu rõ câu hỏi lắm. Bạn đang tìm{$ctxHint} phải không? "
                       . "Bạn có thể nói thêm về ngân sách hoặc phong cách mong muốn không?";
            } else {
                $reply = 'Mình chưa hiểu câu hỏi của bạn lắm. Thử hỏi theo cách sau nhé:<br><br>'
                       . "<div style='background:#f9f0f0;border:1px solid #f0d0d0;border-radius:10px;padding:10px;line-height:2;'>"
                       . '<b>Danh mục:</b> dây chuyền · lắc tay · lắc chân · nhẫn · bông tai<br>'
                       . '<b>Khoảng giá:</b> "từ 1 triệu đến 3 triệu" · "dưới 2 triệu" · "tầm 1.5 triệu"<br>'
                       . '<b>Phong cách:</b> "dễ thương" · "sang trọng" · "cá tính" · "lãng mạn"<br>'
                       . '<b>Chất liệu:</b> "đá CZ" · "pha lê" · "mạ vàng" · "kim cương"<br>'
                       . '<b>Quà tặng:</b> "quà tặng bạn gái dưới 2 triệu" · "mua cho mẹ"<br>'
                       . '<b>Tư vấn:</b> "size nhẫn bao nhiêu" · "cách bảo quản bạc" · "bạc S925 là gì"<br>'
                       . '<b>So sánh:</b> "so sánh lắc tay" · "cái nào tốt hơn"<br>'
                       . '<b>Đơn hàng:</b> "kiểm tra đơn #15" · "lịch sử mua hàng"'
                       . '</div>';
            }
        }
    }
}

if ($dbOk) $conn->close();
echo json_encode(['reply' => $reply], JSON_UNESCAPED_UNICODE);