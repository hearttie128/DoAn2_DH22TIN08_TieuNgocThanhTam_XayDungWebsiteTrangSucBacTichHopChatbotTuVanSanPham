<?php
session_start();
$conn = new mysqli("localhost", "root", "", "qlchdt");
mysqli_set_charset($conn, "utf8mb4");

// Lấy mã sản phẩm từ URL (hỗ trợ cả id slug và ma_sp)
if (isset($_GET['id'])) {
  // Chuyển đổi slug thành ma_sp
  $slug = $_GET['id'];

  // Mapping slug sang ma_sp (theo đúng thứ tự INSERT database)
  $slug_map = [

    //Flash sale
    '1'            => 1,
    '2'    => 2,
    '3'  => 3,
    '4'       => 4,

    //Gợi ý cho bạn
    '5'        => 5,
    '6'       => 6,
    '7'    => 7,
    '8'      => 8,

    // Sản phẩm
    '9'           => 9,
    '10'           => 10,
    '11'                => 11,
    '12'        => 12,
    '13'                => 13,
    '14'                => 14,
    '15'          => 15,
    '16'          => 16,
];

  $ma_sp = isset($slug_map[$slug]) ? $slug_map[$slug] : 1;
} else {
  $ma_sp = isset($_GET['ma_sp']) ? intval($_GET['ma_sp']) : 1;
}

// Lấy dữ liệu sản phẩm từ CSDL
$stmt = $conn->prepare("SELECT * FROM SANPHAM WHERE MA_SP = ?");
$stmt->bind_param("i", $ma_sp);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
  die("Sản phẩm không tồn tại!");
}

// Lấy sản phẩm liên quan (cùng danh mục)
$stmt_related = $conn->prepare("SELECT * FROM SANPHAM WHERE MA_DM = ? AND MA_SP != ? LIMIT 4");
$stmt_related->bind_param("ii", $product['MA_DM'], $ma_sp);
$stmt_related->execute();
$related_products = $stmt_related->get_result();

// Kiểm tra đăng nhập
$isLoggedIn = isset($_SESSION['MA_ND']);

// Mô tả chi tiết sản phẩm
$product_descriptions = [
  1 => [ // Dây chuyền bạc nữ đính đá CZ cá tiên - 1.381.600đ (giảm 12%)
    'highlight' => 'Dây chuyền bạc nữ phong cách cổ trang đính đá CZ lấp lánh, thiết kế hình cá tiên độc đáo, sang trọng và nữ tính - món trang sức hoàn hảo cho phái đẹp.',
    'features' => [
      'Chất liệu bạc S925 cao cấp, không gây dị ứng',
      'Đính đá CZ (Cubic Zirconia) lấp lánh như kim cương',
      'Thiết kế hình cá tiên cổ trang tinh xảo',
      'Dây chuyền mảnh mai, thanh lịch',
      'Phù hợp đi làm, dự tiệc, làm quà tặng',
      'Bảo hành chính hãng 12 tháng'
    ]
  ],
  2 => [ // Lắc chân bạc nữ đính đá CZ hình cỏ 4 lá Mildred - 1.752.000đ (giảm 20%)
    'highlight' => 'Lắc chân bạc nữ Mildred với họa tiết cỏ 4 lá may mắn đính đá CZ lấp lánh, thiết kế tinh tế, trendy - điểm nhấn hoàn hảo cho đôi chân thon gọn.',
    'features' => [
      'Chất liệu bạc S925 nguyên chất',
      'Đính đá CZ cao cấp, lấp lánh bền màu',
      'Thiết kế cỏ 4 lá may mắn độc đáo',
      'Dây lắc mềm mại, điều chỉnh được kích thước',
      'Phong cách trẻ trung, thời trang',
      'Tặng kèm hộp đựng sang trọng'
    ]
  ],
  3 => [ // Lắc tay bạc nữ đính pha lê Swarovski trái tim của biển - 1.508.800đ (giảm 18%)
    'highlight' => 'Lắc tay bạc cao cấp đính pha lê Swarovski chính hãng hình trái tim của biển, lung linh ánh sáng, thiết kế lãng mạn - món quà ý nghĩa cho người thương.',
    'features' => [
      'Pha lê Swarovski chính hãng từ Áo',
      'Chất liệu bạc S925 cao cấp',
      'Thiết kế trái tim của biển độc đáo',
      'Ánh sáng khúc xạ đa chiều lung linh',
      'Dây lắc điều chỉnh được, vừa mọi cỡ tay',
      'Hộp quà cao cấp kèm giấy chứng nhận'
    ]
  ],
  4 => [ // Bông tai bạc Ý S925 nữ mạ bạch kim đính đá CZ - 1.255.800đ (giảm 9%)
    'highlight' => 'Bông tai bạc Ý S925 mạ bạch kim cao cấp đính đá CZ hình trái tim, sang trọng quý phái, bền màu vượt trội - điểm nhấn hoàn hảo cho gương mặt.',
    'features' => [
      'Bạc Ý S925 chuẩn quốc tế',
      'Mạ bạch kim bền màu, chống oxy hóa',
      'Đính đá CZ cao cấp hình trái tim',
      'Thiết kế tinh xảo từ Ý',
      'Khóa cài chắc chắn, an toàn',
      'Phù hợp mọi lứa tuổi, mọi dịp'
    ]
  ],
  5 => [ // Dây chuyền bạc nữ đính kim cương tự nhiên - 1.210.400đ (giảm 11%)
    'highlight' => 'Dây chuyền bạc nữ đính kim cương tự nhiên hình cỏ 4 lá, sang trọng đẳng cấp, tỏa sáng rực rỡ - món trang sức quý giá cho người phụ nữ hiện đại.',
    'features' => [
      'Đính kim cương tự nhiên chính hãng',
      'Chất liệu bạc S925 cao cấp',
      'Thiết kế cỏ 4 lá tinh tế',
      'Ánh sáng kim cương lấp lánh tự nhiên',
      'Kèm giấy chứng nhận kim cương',
      'Hộp đựng sang trọng, phù hợp làm quà'
    ]
  ],
  6 => [ // Lắc tay bạc Ta S999 nữ cỏ 4 lá cách điệu - 2.205.000đ (giảm 10%)
    'highlight' => 'Lắc tay bạc Ta S999 nguyên chất 99.9% với họa tiết cỏ 4 lá cách điệu hiện đại, sang trọng quý phái, độ bền cao - biểu tượng may mắn và phú quý.',
    'features' => [
      'Bạc Ta S999 nguyên chất 99.9%',
      'Thiết kế cỏ 4 lá cách điệu độc đáo',
      'Trọng lượng bạc thật, giá trị cao',
      'Bề mặt bóng láng, không bị đen',
      'Mang lại may mắn, tài lộc',
      'Bảo hành trọn đời, đổi size miễn phí'
    ]
  ],
  7 => [ // Bông tai bạc nữ tròn đính đá CZ hình bông hoa 5 cánh - 1.148.400đ (giảm 13%)
    'highlight' => 'Bông tai bạc nữ thiết kế tròn dạng hoa 5 cánh đính đá CZ lấp lánh, phong cách Cute ngọt ngào, nhẹ nhàng - hoàn hảo cho phái đẹp yêu sự dễ thương.',
    'features' => [
      'Thiết kế hoa 5 cánh tinh tế, đáng yêu',
      'Đính đá CZ cao cấp, lấp lánh',
      'Chất liệu bạc S925 an toàn',
      'Kiểu dáng tròn nhỏ xinh, dễ phối đồ',
      'Trọng lượng nhẹ, đeo cả ngày không đau tai',
      'Phù hợp mọi lứa tuổi, đặc biệt bạn trẻ'
    ]
  ],
  8 => [ // Nhẫn bạc nữ đính đá CZ hoa bướm - 1.179.000đ (giảm 10%)
    'highlight' => 'Nhẫn bạc nữ thiết kế hoa bướm tinh xảo đính đá CZ lấp lánh, nữ tính và quyến rũ, vừa cổ điển vừa hiện đại - điểm nhấn cho đôi tay thon gọn.',
    'features' => [
      'Thiết kế hoa bướm độc đáo, nghệ thuật',
      'Đính đá CZ cao cấp lấp lánh',
      'Chất liệu bạc S925 nguyên chất',
      'Có thể điều chỉnh size phù hợp',
      'Phong cách nữ tính, thanh lịch',
      'Thích hợp làm quà tặng bạn gái, vợ'
    ]
  ],
  9 => [ // Dây chuyền bạc nữ liền mặt mạ vàng đính đá CZ - 1.877.900đ (giảm 11%)
    'highlight' => 'Dây chuyền bạc nữ liền mặt mạ vàng cao cấp hình trái tim Double đính đá CZ, thiết kế 2in1 tiện lợi, sang trọng lấp lánh - món quà tặng ý nghĩa.',
    'features' => [
      'Mạ vàng 14K bền màu, cao cấp',
      'Thiết kế trái tim đôi độc đáo',
      'Đính đá CZ lấp lánh khắp mặt',
      'Dây liền mặt tiện lợi, không lo gãy',
      'Màu vàng sang trọng, quý phái',
      'Phù hợp dự tiệc, sự kiện quan trọng'
    ]
  ],
  10 => [ // Bông tai bạc nữ đính đá CZ hình những bông hoa - 1.186.800đ (giảm 8%)
    'highlight' => 'Bông tai bạc Lưu ly đính đá Zircon thiết kế những bông hoa tinh xảo, lấp lánh như pha lê, nữ tính và duyên dáng - tạo điểm nhấn rạng rỡ cho gương mặt.',
    'features' => [
      'Thiết kế nhiều bông hoa nhỏ xinh',
      'Đính đá Zircon cao cấp lấp lánh',
      'Chất liệu bạc S925 chống dị ứng',
      'Phong cách Lưu ly thanh lịch',
      'Nhẹ nhàng, dễ phối với nhiều trang phục',
      'Phù hợp đi làm, dạo phố, dự tiệc'
    ]
  ],
  11 => [ // Lắc tay bạc đính đá pha lê hình trái tim - 2.511.600đ (giảm 8%)
    'highlight' => 'Lắc tay bạc cao cấp đính đá pha lê Austria hình trái tim lớn, lung linh đa sắc, thiết kế sang trọng lãng mạn - món quà tình yêu hoàn hảo.',
    'features' => [
      'Đá pha lê Austria cao cấp chính hãng',
      'Hình trái tim lớn nổi bật',
      'Ánh sáng khúc xạ lung linh đa chiều',
      'Chất liệu bạc S925 bền đẹp',
      'Thiết kế lãng mạn, đầy cảm xúc',
      'Tặng kèm túi nhung cao cấp'
    ]
  ],
  12 => [ // Nhẫn đôi bạc free size đính đá CZ hiệp sĩ và công chúa - 2.196.000đ (giảm 10%)
    'highlight' => 'Nhẫn đôi bạc cặp tình nhân free size với thiết kế hiệp sĩ và công chúa đính đá CZ, lãng mạn và ý nghĩa - biểu tượng tình yêu vĩnh cửu cho các cặp đôi.',
    'features' => [
      'Thiết kế hiệp sĩ - công chúa độc đáo',
      'Nhẫn đôi matching hoàn hảo',
      'Đính đá CZ cao cấp lấp lánh',
      'Free size, điều chỉnh được kích thước',
      'Chất liệu bạc S925 bền đẹp',
      'Quà tặng ý nghĩa cho ngày lễ tình nhân, kỷ niệm'
    ]
  ],
  13 => [ // Lắc tay bạc nữ cá tính mắt xích vuông trái tim - 2.030.400đ (giảm 6%)
    'highlight' => 'Lắc tay bạc nữ Strong Heart với thiết kế mắt xích vuông cá tính kết hợp trái tim, phong cách hiện đại mạnh mẽ - dành cho cô nàng cá tính, độc lập.',
    'features' => [
      'Thiết kế mắt xích vuông độc đáo',
      'Điểm nhấn trái tim Strong Heart',
      'Phong cách cá tính, hiện đại',
      'Chất liệu bạc S925 cao cấp',
      'Khóa cài chắc chắn, an toàn',
      'Thích hợp cho các bạn nữ năng động, tự tin'
    ]
  ],
  14 => [ // Lắc chân bạc nữ dạng hạt 2 tầng đính mèo thần tài - 3.199.200đ (giảm 7%)
    'highlight' => 'Lắc chân bạc nữ cao cấp dạng hạt 2 tầng đính mèo thần tài Maneki-neko, mang lại may mắn tài lộc, thiết kế sang trọng độc đáo - món quà phong thủy ý nghĩa.',
    'features' => [
      'Thiết kế 2 tầng hạt bạc độc đáo',
      'Đính mèo thần tài Maneki-neko may mắn',
      'Mang ý nghĩa phong thủy tốt lành',
      'Chất liệu bạc S925 cao cấp',
      'Trọng lượng bạc thật, giá trị cao',
      'Thích hợp làm quà khai trương, sinh nhật'
    ]
  ],
  15 => [ // Dây chuyền Choker bạc nữ Magic - 1.991.700đ (giảm 13%)
    'highlight' => 'Dây chuyền Choker bạc nữ Magic với thiết kế ngắn ôm cổ phong cách Gothic-Punk, cá tính và bí ẩn, thu hút mọi ánh nhìn - dành cho cô nàng thích nổi bật.',
    'features' => [
      'Thiết kế Choker ôm cổ trendy',
      'Phong cách Magic bí ẩn, cuốn hút',
      'Chất liệu bạc S925 chất lượng',
      'Điều chỉnh được độ dài phù hợp',
      'Cá tính, thời trang, khác biệt',
      'Phù hợp đi chơi, dự event, chụp ảnh'
    ]
  ],
  16 => [ // Dây chuyền đôi bạc đính đá CZ Forever Love - 2.840.500đ (giảm 5%)
    'highlight' => 'Dây chuyền đôi bạc Forever Love hình cá heo hồng đính đá CZ lấp lánh, biểu tượng tình yêu vĩnh cửu và may mắn - món quà tặng ý nghĩa nhất cho người thương yêu.',
    'features' => [
      'Thiết kế cá heo hồng đôi Forever Love',
      'Đính đá CZ cao cấp lấp lánh',
      'Dây chuyền đôi matching hoàn hảo',
      'Chất liệu bạc S925 cao cấp',
      'Ý nghĩa: tình yêu vĩnh cửu, may mắn',
      'Quà tặng hoàn hảo cho Valentine, kỷ niệm tình yêu'
    ]
  ],
];

// Màu sắc cho từng sản phẩm (theo thứ tự database)
// $product_colors = [
//   1 => [ // iPhone 16 Pro Max (SA MẠC)
//     ['name' => 'Sa Mạc', 'gradient' => 'linear-gradient(135deg, #d4a574 0%, #a67c52 100%)'],
//     ['name' => 'Titan Tự Nhiên', 'gradient' => 'linear-gradient(135deg, #bdc3c7 0%, #2c3e50 100%)'],
//     ['name' => 'Titan Trắng', 'gradient' => 'linear-gradient(135deg, #ecf0f1 0%, #95a5a6 100%)'],
//     ['name' => 'Titan Đen', 'gradient' => 'linear-gradient(135deg, #373737 0%, #000000 100%)']
//   ],
//   2 => [ // iPhone 16 Plus (XANH)
//     ['name' => 'Xanh Lá', 'gradient' => 'linear-gradient(135deg, #a8e063 0%, #56ab2f 100%)'],
//     ['name' => 'Đen', 'gradient' => 'linear-gradient(135deg, #434343 0%, #000000 100%)'],
//     ['name' => 'Trắng', 'gradient' => 'linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%)'],
//     ['name' => 'Hồng', 'gradient' => 'linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%)'],
//     ['name' => 'Xanh Dương', 'gradient' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)']
//   ],
//   3 => [ // iPhone 16 (XANH DƯƠNG)
//     ['name' => 'Xanh Dương', 'gradient' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'],
//     ['name' => 'Đen', 'gradient' => 'linear-gradient(135deg, #434343 0%, #000000 100%)'],
//     ['name' => 'Trắng', 'gradient' => 'linear-gradient(135deg, #ffffff 0%, #e0e0e0 100%)'],
//     ['name' => 'Hồng', 'gradient' => 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)'],
//     ['name' => 'Xanh Lá', 'gradient' => 'linear-gradient(135deg, #89f7fe 0%, #66a6ff 100%)']
//   ],
//   4 => [ // iPhone 14 Plus (XANH)
//     ['name' => 'Xanh', 'gradient' => 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)'],
//     ['name' => 'Đen', 'gradient' => 'linear-gradient(135deg, #2c3e50 0%, #000000 100%)'],
//     ['name' => 'Vàng', 'gradient' => 'linear-gradient(135deg, #f7b733 0%, #fc4a1a 100%)'],
//     ['name' => 'Đỏ', 'gradient' => 'linear-gradient(135deg, #eb3349 0%, #f45c43 100%)'],
//     ['name' => 'Tím', 'gradient' => 'linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%)']
//   ],
//   5 => [ // iPhone 16 Pro Max bản 2 (XANH)
//     ['name' => 'Titan Xanh', 'gradient' => 'linear-gradient(135deg, #2980b9 0%, #6dd5fa 100%)'],
//     ['name' => 'Titan Tự Nhiên', 'gradient' => 'linear-gradient(135deg, #bdc3c7 0%, #2c3e50 100%)'],
//     ['name' => 'Titan Trắng', 'gradient' => 'linear-gradient(135deg, #ecf0f1 0%, #95a5a6 100%)'],
//     ['name' => 'Titan Đen', 'gradient' => 'linear-gradient(135deg, #373737 0%, #000000 100%)']
//   ],
//   6 => [ // iPhone 13 (ĐEN)
//     ['name' => 'Đen', 'gradient' => 'linear-gradient(135deg, #2c3e50 0%, #000000 100%)'],
//     ['name' => 'Xanh', 'gradient' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'],
//     ['name' => 'Hồng', 'gradient' => 'linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%)'],
//     ['name' => 'Đỏ', 'gradient' => 'linear-gradient(135deg, #f857a6 0%, #ff5858 100%)'],
//     ['name' => 'Trắng', 'gradient' => 'linear-gradient(135deg, #fdfbfb 0%, #ebedee 100%)']
//   ],
//   7 => [ // iPhone 15 (VÀNG)
//     ['name' => 'Vàng', 'gradient' => 'linear-gradient(135deg, #ffd89b 0%, #ffb347 100%)'],
//     ['name' => 'Đen', 'gradient' => 'linear-gradient(135deg, #434343 0%, #000000 100%)'],
//     ['name' => 'Xanh', 'gradient' => 'linear-gradient(135deg, #89f7fe 0%, #66a6ff 100%)'],
//     ['name' => 'Hồng', 'gradient' => 'linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%)'],
//     ['name' => 'Xanh Lá', 'gradient' => 'linear-gradient(135deg, #a1c4fd 0%, #c2e9fb 100%)']
//   ],
//   8 => [ // Samsung Galaxy S24 5G
//     ['name' => 'Đen', 'gradient' => 'linear-gradient(135deg, #2c3e50 0%, #000000 100%)'],
//     ['name' => 'Tím', 'gradient' => 'linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%)'],
//     ['name' => 'Vàng', 'gradient' => 'linear-gradient(135deg, #f8cdda 0%, #f093fb 100%)'],
//     ['name' => 'Xám', 'gradient' => 'linear-gradient(135deg, #bdc3c7 0%, #7f8c8d 100%)']
//   ],
//   9 => [ // Samsung S24+ 5G (TÍM)
//     ['name' => 'Tím', 'gradient' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'],
//     ['name' => 'Xám', 'gradient' => 'linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%)'],
//     ['name' => 'Vàng', 'gradient' => 'linear-gradient(135deg, #fbc2eb 0%, #a6c1ee 100%)'],
//     ['name' => 'Đen', 'gradient' => 'linear-gradient(135deg, #373737 0%, #000000 100%)']
//   ],
//   10 => [ // Samsung Galaxy Z Flip6 5G (XANH)
//     ['name' => 'Xanh Mint', 'gradient' => 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)'],
//     ['name' => 'Vàng', 'gradient' => 'linear-gradient(135deg, #f6d365 0%, #fda085 100%)'],
//     ['name' => 'Xanh Dương', 'gradient' => 'linear-gradient(135deg, #30cfd0 0%, #330867 100%)'],
//     ['name' => 'Bạc', 'gradient' => 'linear-gradient(135deg, #e0e0e0 0%, #bdbdbd 100%)']
//   ],
//   11 => [ // Samsung Galaxy Z Fold6 5G (XANH DƯƠNG)
//     ['name' => 'Xanh Dương', 'gradient' => 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)'],
//     ['name' => 'Xám', 'gradient' => 'linear-gradient(135deg, #757f9a 0%, #d7dde8 100%)'],
//     ['name' => 'Hồng', 'gradient' => 'linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%)'],
//     ['name' => 'Đen', 'gradient' => 'linear-gradient(135deg, #232526 0%, #414345 100%)']
//   ],
//   12 => [ // Samsung Galaxy S24 FE 5G
//     ['name' => 'Xanh Mint', 'gradient' => 'linear-gradient(135deg, #a8e6cf 0%, #56ab2f 100%)'],
//     ['name' => 'Xanh Dương', 'gradient' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'],
//     ['name' => 'Vàng', 'gradient' => 'linear-gradient(135deg, #ffd89b 0%, #ff9a56 100%)'],
//     ['name' => 'Xám', 'gradient' => 'linear-gradient(135deg, #bdc3c7 0%, #7f8c8d 100%)']
//   ],
//   13 => [ // Samsung Galaxy Z Flip6 512GB (XANH DƯƠNG)
//     ['name' => 'Xanh Dương', 'gradient' => 'linear-gradient(135deg, #30cfd0 0%, #330867 100%)'],
//     ['name' => 'Xanh Mint', 'gradient' => 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)'],
//     ['name' => 'Vàng Ánh Kim', 'gradient' => 'linear-gradient(135deg, #f6d365 0%, #fda085 100%)'],
//     ['name' => 'Bạc Lấp Lánh', 'gradient' => 'linear-gradient(135deg, #e0e0e0 0%, #bdbdbd 100%)']
//   ],
//   14 => [ // OPPO Reno12 5G (BẠC)
//     ['name' => 'Bạc', 'gradient' => 'linear-gradient(135deg, #e0e0e0 0%, #bdbdbd 100%)'],
//     ['name' => 'Vàng', 'gradient' => 'linear-gradient(135deg, #f8cdda 0%, #f093fb 100%)'],
//     ['name' => 'Xanh', 'gradient' => 'linear-gradient(135deg, #89f7fe 0%, #66a6ff 100%)'],
//     ['name' => 'Đen', 'gradient' => 'linear-gradient(135deg, #2c3e50 0%, #000000 100%)']
//   ],
//   15 => [ // OPPO Reno11F 5G (XANH DƯƠNG)
//     ['name' => 'Xanh Dương', 'gradient' => 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)'],
//     ['name' => 'Xanh Lá', 'gradient' => 'linear-gradient(135deg, #56ab2f 0%, #a8e063 100%)'],
//     ['name' => 'Cam', 'gradient' => 'linear-gradient(135deg, #ff9966 0%, #ff5e62 100%)'],
//     ['name' => 'Đen', 'gradient' => 'linear-gradient(135deg, #434343 0%, #000000 100%)']
//   ],
//   16 => [ // OPPO Reno13 5G (XANH DƯƠNG)
//     ['name' => 'Xanh Dương', 'gradient' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'],
//     ['name' => 'Hồng', 'gradient' => 'linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%)'],
//     ['name' => 'Đen', 'gradient' => 'linear-gradient(135deg, #2c3e50 0%, #000000 100%)'],
//     ['name' => 'Trắng', 'gradient' => 'linear-gradient(135deg, #ffffff 0%, #e0e0e0 100%)']
//   ],
// ];


// Lấy mô tả sản phẩm
$product_desc = isset($product_descriptions[$ma_sp]) ? $product_descriptions[$ma_sp] : [
  'highlight' => htmlspecialchars($product['MO_TA']),
  'features' => [
    'Sản phẩm chính hãng, bảo hành 12 tháng',
    'Thiết kế đẹp mắt, chất lượng cao cấp',
    'Hiệu năng mạnh mẽ, đáp ứng mọi nhu cầu',
    'Pin bền bỉ, sử dụng lâu dài'
  ]
];

// Danh sách đánh giá mẫu cho mỗi sản phẩm
$reviews_data = [
  1 => [ // Dây chuyền bạc nữ đính đá CZ cá tiên
    ['name' => 'Nguyễn Thị Mai', 'avatar' => 'M', 'color' => '#ff6b6b', 'date' => '10/02/2026', 'rating' => 5, 'content' => 'Dây chuyền đẹp lắm! Đá CZ lấp lánh như kim cương thật. Thiết kế cá tiên cổ trang rất độc đáo, đeo lên sang trọng. Giảm 12% nữa, quá hời!'],
    ['name' => 'Trần Hương Lan', 'avatar' => 'L', 'color' => '#4a92e4', 'date' => '08/02/2026', 'rating' => 5, 'content' => 'Chất lượng bạc S925 tốt, không bị đen hay gây dị ứng. Đeo đi làm hoặc dự tiệc đều đẹp. Giá 1.38 triệu rất hợp lý!'],
    ['name' => 'Lê Thị Hoa', 'avatar' => 'H', 'color' => '#51cf66', 'date' => '05/02/2026', 'rating' => 4, 'content' => 'Sản phẩm đẹp, đóng gói cẩn thận. Chỉ tiếc là dây hơi mỏng, phải cẩn thận khi đeo.'],
  ],
  2 => [ // Lắc chân bạc nữ đính đá CZ hình cỏ 4 lá Mildred
    ['name' => 'Phạm Thị Ngọc', 'avatar' => 'N', 'color' => '#ff6b6b', 'date' => '11/02/2026', 'rating' => 5, 'content' => 'Lắc chân xinh xắn lắm! Họa tiết cỏ 4 lá may mắn đẹp, đá CZ lấp lánh. Đeo rất nhẹ nhàng, không bị cộm. Giảm 20% quá hời!'],
    ['name' => 'Hoàng Minh Anh', 'avatar' => 'A', 'color' => '#4a92e4', 'date' => '09/02/2026', 'rating' => 5, 'content' => 'Chất lượng bạc tốt, thiết kế tinh xảo. Mua làm quà tặng bạn gái, cô ấy rất thích! Giá 1.75 triệu rất ổn.'],
    ['name' => 'Nguyễn Văn Tuấn', 'avatar' => 'T', 'color' => '#51cf66', 'date' => '07/02/2026', 'rating' => 4, 'content' => 'Đẹp, chỉ cần cẩn thận khi đeo để không bị móp méo. Tổng thể rất hài lòng!'],
  ],
  3 => [ // Lắc tay bạc nữ đính pha lê Swarovski trái tim của biển
    ['name' => 'Vũ Thị Hằng', 'avatar' => 'H', 'color' => '#ff6b6b', 'date' => '12/02/2026', 'rating' => 5, 'content' => 'Pha lê Swarovski lấp lánh cực đẹp! Trái tim của biển thiết kế lãng mạn. Đeo lên tay sang trọng lắm. Giảm 18% nữa, rất đáng mua!'],
    ['name' => 'Trần Quốc Khánh', 'avatar' => 'K', 'color' => '#4a92e4', 'date' => '10/02/2026', 'rating' => 5, 'content' => 'Mua tặng vợ nhân dịp kỷ niệm, vợ mình rất thích. Chất lượng cao cấp, có giấy chứng nhận Swarovski chính hãng. Recommend!'],
    ['name' => 'Lê Thị Thu', 'avatar' => 'T', 'color' => '#51cf66', 'date' => '08/02/2026', 'rating' => 5, 'content' => 'Lắc tay đẹp xuất sắc, ánh sáng khúc xạ rất lung linh. Giá 1.5 triệu xứng đáng!'],
  ],
  4 => [ // Bông tai bạc Ý S925 nữ mạ bạch kim đính đá CZ
    ['name' => 'Đỗ Thị Linh', 'avatar' => 'L', 'color' => '#ff6b6b', 'date' => '11/02/2026', 'rating' => 5, 'content' => 'Bông tai bạc Ý chất lượng cao! Mạ bạch kim bền màu, đá CZ hình trái tim xinh xắn. Đeo cả ngày không đau tai. Giảm 9% rất ổn!'],
    ['name' => 'Nguyễn Minh Tâm', 'avatar' => 'T', 'color' => '#4a92e4', 'date' => '09/02/2026', 'rating' => 5, 'content' => 'Thiết kế từ Ý rất tinh tế. Khóa cài chắc chắn, an toàn. Giá 1.25 triệu rất hợp lý cho bạc Ý cao cấp!'],
    ['name' => 'Phạm Văn Long', 'avatar' => 'L', 'color' => '#51cf66', 'date' => '07/02/2026', 'rating' => 4, 'content' => 'Sản phẩm đẹp, mua tặng mẹ rất ưng. Chất lượng tốt, giá cả phải chăng.'],
  ],
  5 => [ // Dây chuyền bạc nữ đính kim cương tự nhiên
    ['name' => 'Nguyễn Thị Hạnh', 'avatar' => 'H', 'color' => '#ff6b6b', 'date' => '11/02/2026', 'rating' => 5, 'content' => 'Kim cương tự nhiên lấp lánh cực đẹp! Thiết kế cỏ 4 lá tinh tế, sang trọng. Có giấy chứng nhận kim cương chính hãng. Giảm 11% quá hời, giá 1.21 triệu rất xứng đáng!'],
    ['name' => 'Trần Minh Tú', 'avatar' => 'T', 'color' => '#4a92e4', 'date' => '08/02/2026', 'rating' => 5, 'content' => 'Mua tặng vợ rất hài lòng! Kim cương nhỏ nhưng tỏa sáng rực rỡ. Bạc S925 chất lượng cao, không bị đen. Đóng gói sang trọng kèm hộp quà. Recommend!'],
    ['name' => 'Lê Thị Phương', 'avatar' => 'P', 'color' => '#51cf66', 'date' => '05/02/2026', 'rating' => 5, 'content' => 'Dây chuyền đẹp xuất sắc, kim cương thật sự khác hẳn đá CZ. Ánh sáng tự nhiên rất lung linh. Món quà đầu tư xứng đáng!'],
  ],
  6 => [ // Lắc tay bạc Ta S999 nữ cỏ 4 lá cách điệu
    ['name' => 'Phạm Thị Ngân', 'avatar' => 'N', 'color' => '#ff6b6b', 'date' => '10/02/2026', 'rating' => 5, 'content' => 'Bạc Ta S999 nguyên chất 99.9% rất nặng tay, chất lượng! Họa tiết cỏ 4 lá cách điệu sang trọng, hiện đại. Giảm 10% còn 2.2 triệu rất đáng mua!'],
    ['name' => 'Trần Quốc Huy', 'avatar' => 'H', 'color' => '#4a92e4', 'date' => '07/02/2026', 'rating' => 5, 'content' => 'Mua tặng mẹ rất hài lòng! Bạc sáng bóng, không bị đen như bạc thường. Trọng lượng thật, bảo hành trọn đời. Biểu tượng may mắn và phú quý!'],
    ['name' => 'Nguyễn Thị Mai', 'avatar' => 'M', 'color' => '#51cf66', 'date' => '04/02/2026', 'rating' => 5, 'content' => 'Lắc tay đẹp, thiết kế tinh xảo. Có thể đổi size miễn phí rất tiện. Giá trị bạc thật cao, đầu tư xứng đáng!'],
  ],
  7 => [ // Bông tai bạc nữ tròn đính đá CZ hình bông hoa 5 cánh
    ['name' => 'Lê Thị Thảo', 'avatar' => 'T', 'color' => '#ff6b6b', 'date' => '09/02/2026', 'rating' => 5, 'content' => 'Bông tai hoa 5 cánh xinh xắn quá! Đá CZ lấp lánh, kiểu dáng cute ngọt ngào. Đeo cả ngày không đau tai, rất nhẹ. Giảm 13% còn 1.14 triệu quá hời!'],
    ['name' => 'Vũ Minh Châu', 'avatar' => 'C', 'color' => '#4a92e4', 'date' => '06/02/2026', 'rating' => 5, 'content' => 'Mua cho em gái rất thích! Thiết kế tròn nhỏ xinh, dễ phối đồ. Bạc S925 an toàn, không gây dị ứng. Giá rẻ mà đẹp!'],
    ['name' => 'Trần Thị Hương', 'avatar' => 'H', 'color' => '#51cf66', 'date' => '03/02/2026', 'rating' => 5, 'content' => 'Bông tai đáng yêu lắm! Phù hợp mọi lứa tuổi, đặc biệt các bạn trẻ. Trọng lượng nhẹ, đeo thoải mái!'],
  ],
  8 => [ // Nhẫn bạc nữ đính đá CZ hoa bướm
    ['name' => 'Đỗ Thị Linh', 'avatar' => 'L', 'color' => '#ff6b6b', 'date' => '12/02/2026', 'rating' => 5, 'content' => 'Nhẫn hoa bướm xinh lung linh! Thiết kế tinh xảo, đá CZ lấp lánh. Có thể điều chỉnh size rất tiện. Giảm 10% còn 1.17 triệu, quà tặng hoàn hảo!'],
    ['name' => 'Nguyễn Văn Tùng', 'avatar' => 'T', 'color' => '#4a92e4', 'date' => '09/02/2026', 'rating' => 5, 'content' => 'Mua tặng bạn gái rất ưng! Nhẫn nữ tính, thanh lịch. Bạc S925 chất lượng cao. Đóng gói đẹp, ship nhanh!'],
    ['name' => 'Lê Thị Nga', 'avatar' => 'N', 'color' => '#51cf66', 'date' => '06/02/2026', 'rating' => 5, 'content' => 'Thiết kế độc đáo, vừa cổ điển vừa hiện đại. Đeo lên tay rất xinh, tôn da trắng. Recommend!'],
  ],
  9 => [ // Dây chuyền bạc nữ liền mặt mạ vàng đính đá CZ
    ['name' => 'Trần Thị Hồng', 'avatar' => 'H', 'color' => '#ff6b6b', 'date' => '11/02/2026', 'rating' => 5, 'content' => 'Dây chuyền mạ vàng 14K rất đẹp! Thiết kế trái tim đôi lãng mạn, đá CZ lấp lánh khắp mặt. Dây liền mặt tiện lợi, không lo gãy. Giảm 11% còn 1.87 triệu quá xứng đáng!'],
    ['name' => 'Lê Văn Khải', 'avatar' => 'K', 'color' => '#4a92e4', 'date' => '08/02/2026', 'rating' => 5, 'content' => 'Mua tặng vợ nhân dịp sinh nhật, vợ mê lắm! Màu vàng sang trọng, quý phái. Phù hợp dự tiệc hoặc sự kiện quan trọng. Recommend!'],
    ['name' => 'Nguyễn Thị Lan', 'avatar' => 'L', 'color' => '#51cf66', 'date' => '05/02/2026', 'rating' => 5, 'content' => 'Dây chuyền 2in1 tiện lợi, mạ vàng bền màu. Thiết kế trái tim Double độc đáo. Món quà ý nghĩa!'],
  ],
  10 => [ // Bông tai bạc nữ đính đá CZ hình những bông hoa
    ['name' => 'Phạm Thị Thúy', 'avatar' => 'T', 'color' => '#ff6b6b', 'date' => '10/02/2026', 'rating' => 5, 'content' => 'Bông tai Lưu ly xinh xắn! Nhiều bông hoa nhỏ đính đá Zircon lấp lánh như pha lê. Nhẹ nhàng, dễ phối đồ. Giảm 8% còn 1.18 triệu rất đáng!'],
    ['name' => 'Vũ Minh Đức', 'avatar' => 'Đ', 'color' => '#4a92e4', 'date' => '07/02/2026', 'rating' => 5, 'content' => 'Mua cho mẹ rất hài lòng! Thiết kế thanh lịch, nữ tính. Bạc S925 không gây dị ứng. Đeo đi làm, dạo phố đều đẹp!'],
    ['name' => 'Trần Thị Mai', 'avatar' => 'M', 'color' => '#51cf66', 'date' => '04/02/2026', 'rating' => 5, 'content' => 'Bông tai đẹp lung linh, phong cách Lưu ly rất sang. Duyên dáng và tao nhã. Recommend!'],
  ],

  11 => [ // Lắc tay bạc đính đá pha lê hình trái tim
    ['name' => 'Ngô Thị Hương', 'avatar' => 'H', 'color' => '#ff6b6b', 'date' => '12/02/2026', 'rating' => 5, 'content' => 'Lắc tay pha lê Austria cực đẹp! Trái tim lớn lung linh đa sắc, thiết kế lãng mạn. Giảm 8% còn 2.51 triệu, món quà tình yêu hoàn hảo!'],
    ['name' => 'Trần Quốc Tuấn', 'avatar' => 'T', 'color' => '#4a92e4', 'date' => '09/02/2026', 'rating' => 5, 'content' => 'Mua tặng bạn gái dịp Valentine rất ưng! Pha lê chính hãng, ánh sáng khúc xạ lung linh. Bạc S925 bền đẹp. Kèm túi nhung sang trọng!'],
    ['name' => 'Lê Thị Nga', 'avatar' => 'N', 'color' => '#51cf66', 'date' => '06/02/2026', 'rating' => 5, 'content' => 'Lắc tay đẹp xuất sắc, đầy cảm xúc. Trái tim lớn rất nổi bật. Đầu tư xứng đáng cho người thương!'],
  ],
  12 => [ // Nhẫn đôi bạc free size đính đá CZ hiệp sĩ và công chúa
    ['name' => 'Đỗ Văn Hùng', 'avatar' => 'H', 'color' => '#ff6b6b', 'date' => '11/02/2026', 'rating' => 5, 'content' => 'Nhẫn đôi cực kỳ ý nghĩa! Thiết kế hiệp sĩ - công chúa lãng mạn, đá CZ lấp lánh. Free size tiện lợi. Giảm 10% còn 2.19 triệu, quà kỷ niệm hoàn hảo!'],
    ['name' => 'Nguyễn Thị Linh', 'avatar' => 'L', 'color' => '#4a92e4', 'date' => '08/02/2026', 'rating' => 5, 'content' => 'Mua cho cặp đôi bạn thân rất thích! Nhẫn matching đẹp, biểu tượng tình yêu vĩnh cửu. Bạc S925 bền đẹp. Recommend!'],
    ['name' => 'Trần Thị Hoa', 'avatar' => 'H', 'color' => '#51cf66', 'date' => '05/02/2026', 'rating' => 5, 'content' => 'Nhẫn đôi đẹp lắm! Thiết kế độc đáo, ý nghĩa. Quà tặng ngày lễ tình nhân cực chuẩn!'],
  ],
  13 => [ // Lắc tay bạc nữ cá tính mắt xích vuông trái tim
    ['name' => 'Võ Thị Trang', 'avatar' => 'T', 'color' => '#ff6b6b', 'date' => '10/02/2026', 'rating' => 5, 'content' => 'Lắc tay Strong Heart cá tính lắm! Mắt xích vuông độc đáo kết hợp trái tim, phong cách hiện đại mạnh mẽ. Giảm 6% còn 2.03 triệu, dành cho cô nàng cá tính!'],
    ['name' => 'Lê Văn Nam', 'avatar' => 'N', 'color' => '#4a92e4', 'date' => '07/02/2026', 'rating' => 5, 'content' => 'Mua tặng em gái rất thích! Thiết kế mắt xích vuông chắc chắn, khóa cài an toàn. Bạc S925 cao cấp. Recommend!'],
    ['name' => 'Phạm Thị Hương', 'avatar' => 'H', 'color' => '#51cf66', 'date' => '04/02/2026', 'rating' => 5, 'content' => 'Lắc tay đẹp, cá tính. Thích hợp cho các bạn nữ năng động, tự tin. Chất lượng tốt!'],
  ],
  14 => [ // Lắc chân bạc nữ dạng hạt 2 tầng đính mèo thần tài
    ['name' => 'Nguyễn Thị Hoa', 'avatar' => 'H', 'color' => '#ff6b6b', 'date' => '11/02/2026', 'rating' => 5, 'content' => 'Lắc chân bạc nữ dạng hạt 2 tầng đính mèo thần tài đẹp lắm! Mèo thần tài xinh xắn, đính đá lấp lánh. Giảm 7% rất đáng mua!'],
    ['name' => 'Trần Văn Minh', 'avatar' => 'M', 'color' => '#4a92e4', 'date' => '09/02/2026', 'rating' => 5, 'content' => 'Mua tặng vợ rất hài lòng! Mèo thần tài dễ thương, chất lượng tốt. Recommend!'],
    ['name' => 'Phạm Thị Lan', 'avatar' => 'L', 'color' => '#51cf66', 'date' => '07/02/2026', 'rating' => 5, 'content' => 'Lắc chân xinh, phong cách Hàn Quốc hiện đại. Chất lượng tốt, đính đá lấp lánh!'],
  ],
  15 => [ // Dây chuyền Choker bạc nữ Magic
    ['name' => 'Lưu Thị Thảo', 'avatar' => 'T', 'color' => '#ff6b6b', 'date' => '08/02/2026', 'rating' => 5, 'content' => 'Dây chuyền Choker bạc nữ Magic xinh xắn! Cỏ 4 lá may mắn đính đá lấp lánh. Dễ dàng thêm charm khác. Giá tốt, chất lượng cao!'],
    ['name' => 'Đỗ Văn Tuấn', 'avatar' => 'T', 'color' => '#4a92e4', 'date' => '06/02/2026', 'rating' => 5, 'content' => 'Mua cho em gái rất thích! Thiết kế trẻ trung, có thể tùy chỉnh charm. Bạc S925 an toàn. Recommend!'],
    ['name' => 'Võ Thị Mai', 'avatar' => 'M', 'color' => '#51cf66', 'date' => '04/02/2026', 'rating' => 5, 'content' => 'Dây chuyền đẹp, dễ phối đồ. Charm cỏ 4 lá may mắn ý nghĩa. Quà tặng hoàn hảo!'],
  ],
  16 => [ // Dây chuyền đôi bạc đính đá CZ Forever Love
    ['name' => 'Ngô Thị Linh', 'avatar' => 'L', 'color' => '#ff6b6b', 'date' => '12/02/2026', 'rating' => 5, 'content' => 'Dây chuyền đôi bạc đính đá CZ Forever Love xinh xắn! Đá CZ nhỏ lấp lánh, kiểu dáng tối giản nhưng sang trọng. Giá rẻ mà chất lượng cao!'],
    ['name' => 'Trịnh Văn Hải', 'avatar' => 'H', 'color' => '#4a92e4', 'date' => '10/02/2026', 'rating' => 5, 'content' => 'Mua cho con gái rất hài lòng! Dây chuyền đẹp, chất lượng tốt. Recommend!'],
    ['name' => 'Lê Thị Thu', 'avatar' => 'T', 'color' => '#51cf66', 'date' => '08/02/2026', 'rating' => 5, 'content' => 'Dây chuyền đẹp, phù hợp mọi lứa tuổi. Thiết kế tối giản, thanh lịch. Chất lượng tốt!'],
  ],
];

// Lấy đánh giá của sản phẩm hiện tại
$current_reviews = isset($reviews_data[$ma_sp]) ? $reviews_data[$ma_sp] : [
  ['name' => 'Nguyễn Văn A', 'avatar' => 'A', 'color' => '#4a92e4', 'date' => '15/01/2026', 'rating' => 5, 'content' => 'Sản phẩm rất tốt, mình rất hài lòng với chất lượng. Shop giao hàng nhanh, đóng gói cẩn thận. Recommend!'],
  ['name' => 'Trần Thị B', 'avatar' => 'B', 'color' => '#ff6b6b', 'date' => '12/01/2026', 'rating' => 4, 'content' => 'Máy đẹp, cấu hình mạnh. Tuy nhiên thời gian giao hàng hơi lâu. Nhìn chung vẫn ok, đáng tiền.'],
  ['name' => 'Lê Minh C', 'avatar' => 'C', 'color' => '#51cf66', 'date' => '10/01/2026', 'rating' => 5, 'content' => 'Mình đã dùng được 1 tuần rồi, cảm giác rất tốt. Performance ổn định, pin khỏe. Recommend!'],
];
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Chi tiết sản phẩm - <?php echo htmlspecialchars($product['TEN_SP']); ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
      background: #f5f5f5;
      color: #333;
      line-height: 1.6;
    }

    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
    }

    /* Navigation breadcrumb */
    .breadcrumb {
      background: white;
      padding: 15px 0;
      margin-bottom: 20px;
    }

    .breadcrumb a {
      color: #666;
      text-decoration: none;
      margin: 0 5px;
    }

    .breadcrumb a:hover {
      color: #4a92e4;
    }

    .breadcrumb span {
      color: #333;
      font-weight: 500;
    }

    /* Product Detail Section */
    .product-detail {
      background: white;
      border-radius: 12px;
      padding: 30px;
      margin-bottom: 20px;
      display: grid;
      grid-template-columns: 450px 1fr;
      gap: 40px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .product-images {
      position: sticky;
      top: 20px;
      height: fit-content;
    }

    .main-image {
      width: 100%;
      border-radius: 12px;
      margin-bottom: 15px;
      border: 1px solid #eee;
      padding: 20px;
      background: #fafafa;
      position: relative;
      overflow: hidden;
    }

    .main-image::before {
      content: '';
      position: absolute;
      top: 10px;
      right: 10px;
      padding: 6px 12px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      font-size: 11px;
      font-weight: 600;
      border-radius: 20px;
      z-index: 1;
    }

    .main-image.new::before {
      content: 'MỚI';
    }

    .main-image.hot::before {
      content: 'HOT';
      background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }

    .main-image img {
      width: 100%;
      height: auto;
      max-height: 500px;
      object-fit: contain;
      display: block;
      transition: transform 0.3s ease;
    }

    .main-image:hover img {
      transform: scale(1.05);
    }

    .thumbnail-images {
      display: flex;
      gap: 10px;
      overflow-x: auto;
    }

    .thumbnail {
      width: 80px;
      height: 80px;
      border: 2px solid #eee;
      border-radius: 8px;
      cursor: pointer;
      overflow: hidden;
      flex-shrink: 0;
    }

    .thumbnail:hover,
    .thumbnail.active {
      border-color: #4a92e4;
    }

    .thumbnail img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    /* Product Info */
    .product-info h1 {
      font-size: 24px;
      font-weight: 600;
      margin-bottom: 15px;
      color: #1a1a1a;
      line-height: 1.4;
    }

    .product-sku {
      display: flex;
      align-items: center;
      gap: 15px;
      margin-bottom: 15px;
      font-size: 14px;
      color: #666;
    }

    .sku-code {
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .badge {
      display: inline-block;
      padding: 4px 10px;
      border-radius: 4px;
      font-size: 12px;
      font-weight: 600;
    }

    .badge-new {
      background: #e3f2fd;
      color: #1976d2;
    }

    .badge-genuine {
      background: #e8f5e9;
      color: #388e3c;
    }

    .product-rating {
      display: flex;
      align-items: center;
      gap: 15px;
      margin-bottom: 20px;
      padding-bottom: 20px;
      border-bottom: 1px solid #eee;
    }

    .rating-stars {
      color: #ffa500;
      font-size: 14px;
      display: flex;
      align-items: center;
      gap: 2px;
    }

    .rating-count {
      color: #666;
      font-size: 14px;
    }

    .rating-count a {
      color: #4a92e4;
      text-decoration: none;
    }

    .rating-count a:hover {
      text-decoration: underline;
    }

    .product-price {
      background: linear-gradient(135deg, #fff5f5 0%, #ffe8e8 100%);
      padding: 20px;
      border-radius: 12px;
      margin-bottom: 25px;
      border: 2px solid #ffe0e0;
    }

    .price-row {
      display: flex;
      align-items: baseline;
      margin-bottom: 10px;
    }

    .current-price {
      font-size: 32px;
      font-weight: 700;
      color: #d31515;
      margin-right: 15px;
    }

    .original-price {
      font-size: 20px;
      color: #999;
      text-decoration: line-through;
      margin-right: 10px;
    }

    .discount-badge {
      display: inline-block;
      background: #d31515;
      color: white;
      padding: 4px 12px;
      border-radius: 4px;
      font-size: 14px;
      font-weight: 600;
    }

    /* Color Options */
    .color-options {
      margin-bottom: 25px;
    }

    .section-title {
      font-size: 16px;
      font-weight: 600;
      margin-bottom: 12px;
      color: #1a1a1a;
    }

    .color-buttons {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    .color-btn {
      padding: 12px 20px;
      border: 2px solid #e0e0e0;
      background: white;
      border-radius: 10px;
      cursor: pointer;
      transition: all 0.3s;
      font-size: 14px;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 10px;
      position: relative;
    }

    .color-btn .color-circle {
      width: 24px;
      height: 24px;
      border-radius: 50%;
      border: 2px solid #fff;
      box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.1), 0 2px 4px rgba(0, 0, 0, 0.1);
      flex-shrink: 0;
    }

    .color-btn .color-name {
      color: #333;
    }

    .color-btn:hover {
      border-color: #4a92e4;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(74, 146, 228, 0.2);
    }

    .color-btn.active {
      border-color: #4a92e4;
      background: #f0f8ff;
      box-shadow: 0 4px 12px rgba(74, 146, 228, 0.3);
    }

    .color-btn.active .color-name {
      color: #4a92e4;
      font-weight: 600;
    }

    /* Action Buttons */
    .action-buttons {
      display: flex;
      gap: 15px;
      margin-bottom: 25px;
    }

    .btn-buy,
    .btn-cart {
      flex: 1;
      padding: 16px;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      text-decoration: none;
    }

    .btn-buy {
      background: #d31515;
      color: white;
    }

    .btn-buy:hover {
      background: #b01010;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(211, 21, 21, 0.3);
    }

    .btn-cart {
      background: white;
      color: #4a92e4;
      border: 2px solid #4a92e4;
    }

    .btn-cart:hover {
      background: #4a92e4;
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(74, 146, 228, 0.3);
    }

    /* Promotions */
    .promotions {
      background: #f9f9f9;
      padding: 20px;
      border-radius: 8px;
      margin-bottom: 25px;
    }

    .promotions h3 {
      font-size: 16px;
      font-weight: 600;
      margin-bottom: 15px;
      color: #1a1a1a;
    }

    .promo-item {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      margin-bottom: 10px;
      font-size: 14px;
      color: #555;
    }

    .promo-item i {
      color: #4caf50;
      margin-top: 3px;
    }

    /* Product Description */
    .product-description {
      background: #f0f8ff;
      padding: 20px;
      border-radius: 8px;
      margin-bottom: 25px;
    }

    .product-description h3 {
      font-size: 16px;
      font-weight: 600;
      margin-bottom: 15px;
      color: #1a1a1a;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .product-description h3 i {
      color: #4a92e4;
    }

    .desc-highlight {
      font-size: 15px;
      line-height: 1.6;
      color: #333;
      margin-bottom: 15px;
      font-weight: 500;
    }

    .feature-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .feature-list li {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      margin-bottom: 10px;
      font-size: 14px;
      color: #555;
      line-height: 1.5;
    }

    .feature-list li i {
      color: #4caf50;
      margin-top: 3px;
      flex-shrink: 0;
    }

    /* Specifications Tab */
    .product-tabs {
      background: white;
      border-radius: 12px;
      margin-bottom: 20px;
      overflow: hidden;
    }

    .tab-buttons {
      display: flex;
      border-bottom: 2px solid #f0f0f0;
    }

    .tab-btn {
      flex: 1;
      padding: 18px;
      border: none;
      background: white;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      color: #666;
      transition: all 0.3s;
      border-bottom: 3px solid transparent;
    }

    .tab-btn:hover {
      color: #4a92e4;
    }

    .tab-btn.active {
      color: #4a92e4;
      border-bottom-color: #4a92e4;
    }

    .tab-content {
      display: none;
      padding: 30px;
    }

    .tab-content.active {
      display: block;
    }

    .specs-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 20px;
    }

    .spec-item {
      display: flex;
      padding: 15px;
      background: #f9f9f9;
      border-radius: 8px;
    }

    .spec-label {
      font-weight: 600;
      color: #666;
      min-width: 140px;
    }

    .spec-value {
      color: #1a1a1a;
    }

    /* Reviews Section */
    .reviews-summary {
      display: flex;
      gap: 40px;
      margin-bottom: 30px;
      padding-bottom: 30px;
      border-bottom: 1px solid #eee;
    }

    .rating-overview {
      text-align: center;
    }

    .rating-score {
      font-size: 48px;
      font-weight: 700;
      color: #1a1a1a;
    }

    .rating-stars-large {
      color: #ffa500;
      font-size: 24px;
      margin: 10px 0;
    }

    .rating-bars {
      flex: 1;
    }

    .rating-bar {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 10px;
    }

    .bar-label {
      min-width: 70px;
      font-size: 14px;
      color: #666;
    }

    .bar {
      flex: 1;
      height: 8px;
      background: #f0f0f0;
      border-radius: 4px;
      overflow: hidden;
    }

    .bar-fill {
      height: 100%;
      background: #ffa500;
    }

    .bar-count {
      min-width: 40px;
      text-align: right;
      font-size: 14px;
      color: #666;
    }

    .review-item {
      padding: 20px 0;
      border-bottom: 1px solid #eee;
    }

    .review-header {
      display: flex;
      align-items: center;
      gap: 15px;
      margin-bottom: 10px;
    }

    .reviewer-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: #4a92e4;
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 600;
    }

    .reviewer-name {
      font-weight: 600;
      color: #1a1a1a;
    }

    .review-date {
      color: #999;
      font-size: 14px;
    }

    .review-rating {
      color: #ffa500;
      font-size: 14px;
      margin-bottom: 10px;
    }

    .review-content {
      color: #555;
      line-height: 1.6;
    }

    /* Related Products */
    .related-products {
      background: white;
      border-radius: 12px;
      padding: 30px;
      margin-bottom: 20px;
    }

    .section-header {
      font-size: 22px;
      font-weight: 700;
      margin-bottom: 25px;
      color: #1a1a1a;
    }

    .products-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 20px;
      max-width: 100%;
    }

    .product-card {
      background: white;
      border: 1px solid #eee;
      border-radius: 12px;
      padding: 15px;
      transition: all 0.3s;
      cursor: pointer;
      text-decoration: none;
      color: inherit;
      display: flex;
      flex-direction: column;
      height: 100%;
      overflow: hidden;
    }

    .product-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
      border-color: #4a92e4;
    }

    .product-card-img {
      width: 100%;
      aspect-ratio: 1;
      object-fit: contain;
      margin-bottom: 15px;
      background: #fafafa;
      border-radius: 8px;
      padding: 10px;
    }

    .product-card-name {
      font-size: 15px;
      font-weight: 600;
      margin-bottom: 8px;
      color: #1a1a1a;
      line-height: 1.4;
      min-height: 42px;
      display: -webkit-box;
      /* -webkit-line-clamp: 2; */
      -webkit-box-orient: vertical;
      overflow: hidden;
      text-overflow: ellipsis;
      word-wrap: break-word;
      white-space: normal;
    }

    .product-card-price {
      font-size: 18px;
      font-weight: 700;
      color: #d31515;
    }

    .product-card-old-price {
      font-size: 14px;
      color: #999;
      text-decoration: line-through;
      margin-left: 8px;
    }

    /* Toast */
    .toast {
      position: fixed;
      top: 24px;
      right: 24px;
      background: white;
      border-radius: 14px;
      box-shadow: 0 8px 32px rgba(74,146,228,0.18), 0 2px 8px rgba(0,0,0,0.08);
      z-index: 9999;
      min-width: 300px;
      max-width: 360px;
      overflow: hidden;
      animation: slideIn 0.35s cubic-bezier(.34,1.56,.64,1);
      border: 1px solid #e8f2fd;
    }
    .toast.hide {
      animation: slideOut 0.3s ease-in forwards;
    }
    .toast-header {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 14px 16px 10px;
    }
    .toast-icon {
      width: 36px; height: 36px;
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
      font-size: 16px;
    }
    .toast-icon.success { background: #e8f5e9; color: #22c55e; }
    .toast-icon.error   { background: #fff0f0; color: #e53935; }
    .toast-text { flex: 1; }
    .toast-title {
      font-size: 14px;
      font-weight: 700;
      color: #1a1a2e;
      margin-bottom: 2px;
    }
    .toast-msg {
      font-size: 12px;
      color: #6b7280;
    }
    .toast-close {
      background: none; border: none;
      color: #bbb; font-size: 16px;
      cursor: pointer; padding: 2px 4px;
      border-radius: 4px; line-height: 1;
      transition: color 0.15s;
    }
    .toast-close:hover { color: #555; }
    .toast-actions {
      display: flex;
      gap: 8px;
      padding: 0 16px 14px;
    }
    .toast-btn {
      flex: 1; padding: 8px 10px;
      border-radius: 8px; border: none;
      font-size: 12px; font-weight: 700;
      cursor: pointer; font-family: inherit;
      transition: all 0.18s;
    }
    .toast-btn-outline {
      background: #f0f4f8;
      color: #555;
    }
    .toast-btn-outline:hover { background: #e2e8f0; }
    .toast-btn-solid {
      background: #4A92E4;
      color: white;
      box-shadow: 0 2px 8px rgba(74,146,228,0.3);
    }
    .toast-btn-solid:hover { background: #2f7ed4; }
    .toast-progress {
      height: 3px;
      background: #4A92E4;
      animation: progress 30s linear forwards;
      transform-origin: left;
    }
    .toast.error-toast .toast-progress { background: #e53935; }
    @keyframes progress {
      from { transform: scaleX(1); }
      to   { transform: scaleX(0); }
    }
    @keyframes slideIn {
      from { transform: translateX(420px); opacity: 0; }
      to   { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
      from { transform: translateX(0); opacity: 1; }
      to   { transform: translateX(420px); opacity: 0; }
    }

    @media (max-width: 768px) {
      .product-detail {
        grid-template-columns: 1fr;
      }

      .specs-grid {
        grid-template-columns: 1fr;
      }

      .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 15px;
      }

      .action-buttons {
        flex-direction: column;
      }
    }
  </style>
</head>

<body>

  <div class="breadcrumb">
    <div class="container">
      <a href="trangchu.php"><i class="fas fa-home"></i> Trang chủ</a>
      <i class="fas fa-chevron-right" style="font-size: 12px; color: #ccc;"></i>
      <span><?php echo htmlspecialchars($product['TEN_SP']); ?></span>
    </div>
  </div>

  <div class="container">
    <div class="product-detail">
      <div class="product-images">
        <div class="main-image">
          <img src="<?php echo htmlspecialchars($product['HINH_ANH']); ?>"
            alt="<?php echo htmlspecialchars($product['TEN_SP']); ?>">
        </div>
        <div class="thumbnail-images">
          <div class="thumbnail active">
            <img src="<?php echo htmlspecialchars($product['HINH_ANH']); ?>" alt="Thumbnail 1">
          </div>
          <div class="thumbnail">
            <img src="<?php echo htmlspecialchars($product['HINH_ANH']); ?>" alt="Thumbnail 2">
          </div>
          <div class="thumbnail">
            <img src="<?php echo htmlspecialchars($product['HINH_ANH']); ?>" alt="Thumbnail 3">
          </div>
        </div>
      </div>

      <div class="product-info">
        <h1><?php echo htmlspecialchars($product['TEN_SP']); ?> | Chính hãng VN/A</h1>

        <div class="product-rating">
          <div class="rating-stars">
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star-half-alt"></i>
          </div>
          <span class="rating-count">4.5 (<?php echo count($current_reviews); ?> đánh giá)</span>
          <span class="rating-count">|</span>
          <span class="rating-count">Đã bán <?php echo rand(100, 2000); ?></span>
        </div>

        <div class="product-price">
          <?php
          $giaGoc = $product['GIA'];
          $giamGia = isset($product['GIAM_GIA']) ? $product['GIAM_GIA'] : 0;

          if ($giamGia > 0) {
            // Tính giá sau giảm = Giá gốc * (100 - phần trăm giảm) / 100
            $giaSauGiam = $giaGoc * (100 - $giamGia) / 100;
            echo '<span class="current-price">' . number_format($giaSauGiam, 0, ",", ".") . 'đ</span>';
            echo '<span class="original-price">' . number_format($giaGoc, 0, ",", ".") . 'đ</span>';
            echo '<span class="discount-badge">-' . $giamGia . '%</span>';
          } else {
            $giaSauGiam = $giaGoc;
            echo '<span class="current-price">' . number_format($giaGoc, 0, ",", ".") . 'đ</span>';
          }
          ?>
        </div>

        <div class="color-options">
          <div class="section-title">Chọn màu sắc:</div>
          <div class="color-buttons">
            <?php
            $colors = isset($product_colors[$ma_sp]) ? $product_colors[$ma_sp] : [];
            if (!empty($colors)) {
              foreach ($colors as $index => $color) {
                $activeClass = $index === 0 ? 'active' : '';
                echo '<button class="color-btn ' . $activeClass . '" data-color="' . strtolower(str_replace(' ', '-', $color['name'])) . '">';
                echo '<span class="color-circle" style="background: ' . $color['gradient'] . ';"></span>';
                echo '<span class="color-name">' . htmlspecialchars($color['name']) . '</span>';
                echo '</button>';
              }
            } else {
              // Màu mặc định nếu không có trong mảng
              echo '<button class="color-btn active" data-color="default">';
              echo '<span class="color-circle" style="background: linear-gradient(135deg, #f3f4f9ff 0%, #e0dde2ff 100%);"></span>';
              echo '<span class="color-name">Màu Chuẩn</span>';
              echo '</button>';
            }
            ?>
          </div>
        </div>

        <div class="action-buttons">
          <?php if ($isLoggedIn): ?>
            <a href="them_giohang.php?ma_sp=<?php echo $ma_sp; ?>&type=buy" class="btn-buy">
              <i class="fas fa-shopping-bag"></i> Mua ngay
            </a>
            <button onclick="addToCart(<?php echo $ma_sp; ?>)" class="btn-cart">
              <i class="fas fa-cart-plus"></i> Thêm giỏ hàng
            </button>
          <?php else: ?>
            <a href="dangnhap_user.php?redirect=chitietsanpham.php?ma_sp=<?php echo $ma_sp; ?>" class="btn-buy">
              <i class="fas fa-shopping-bag"></i> Mua ngay
            </a>
            <a href="dangnhap_user.php?redirect=chitietsanpham.php?ma_sp=<?php echo $ma_sp; ?>" class="btn-cart">
              <i class="fas fa-cart-plus"></i> Thêm giỏ hàng
            </a>
          <?php endif; ?>
        </div>

        <div class="promotions">
          <h3><i class="fas fa-gift"></i> Ưu đãi đặc biệt</h3>
          <div class="promo-item">
            <i class="fas fa-check-circle"></i>
            <span>Giảm giá <?php echo isset($product['GIAM_GIA']) && $product['GIAM_GIA'] > 0 ? $product['GIAM_GIA'] . '%' : '0%'; ?> - Tiết kiệm <?php echo isset($product['GIAM_GIA']) && $product['GIAM_GIA'] > 0 ? number_format($giaGoc * $product['GIAM_GIA'] / 100, 0, ',', '.') : '0'; ?>đ</span>
          </div>
          <div class="promo-item">
            <i class="fas fa-check-circle"></i>
            <span>Tặng hộp đựng cao cấp + Túi nhung sang trọng</span>
          </div>
          <div class="promo-item">
            <i class="fas fa-check-circle"></i>
            <span>Miễn phí làm sạch và bảo dưỡng trọn đời</span>
          </div>
          <div class="promo-item">
            <i class="fas fa-check-circle"></i>
            <span>Đổi size miễn phí trong 30 ngày đầu</span>
          </div>
          <div class="promo-item">
            <i class="fas fa-check-circle"></i>
            <span>Bảo hành 12 tháng chính hãng - Đổi mới trong 7 ngày</span>
          </div>
          <div class="promo-item">
            <i class="fas fa-check-circle"></i>
            <span>Tích điểm thành viên - Đổi quà hấp dẫn</span>
          </div>
        </div>

        <!-- Mô tả chi tiết sản phẩm -->
        <div class="product-description">
          <h3><i class="fas fa-info-circle"></i> Thông tin sản phẩm</h3>
          <p class="desc-highlight"><?php echo $product_desc['highlight']; ?></p>
          <ul class="feature-list">
            <?php foreach ($product_desc['features'] as $feature): ?>
              <li><i class="fas fa-check"></i> <?php echo $feature; ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="product-tabs">
      <div class="tab-buttons">
        <button class="tab-btn active" onclick="openTab(event, 'specs')">Thông số chi tiết</button>
        <button class="tab-btn" onclick="openTab(event, 'reviews')">Đánh giá
          (<?php echo count($current_reviews); ?>)</button>
      </div>

      <div id="specs" class="tab-content active">
        <div class="specs-grid">
          <div class="spec-item">
            <span class="spec-label"><i class="fas fa-tag"></i> Mã sản phẩm:</span>
            <span class="spec-value"><?php echo htmlspecialchars($product['MO_TA']); ?></span>
          </div>

          <div class="spec-item">
            <span class="spec-label"><i class="fas fa-gem"></i> Chất liệu:</span>
            <span class="spec-value">Bạc S925 cao cấp</span>
          </div>

          <div class="spec-item">
            <span class="spec-label"><i class="fas fa-certificate"></i> Xuất xứ:</span>
            <span class="spec-value">Việt Nam</span>
          </div>

          <div class="spec-item">
            <span class="spec-label"><i class="fas fa-shield-alt"></i> Bảo hành:</span>
            <span class="spec-value">12 tháng chính hãng</span>
          </div>

          <div class="spec-item">
            <span class="spec-label"><i class="fas fa-boxes"></i> Số lượng:</span>
            <span class="spec-value"><?php echo $product['SO_LUONG']; ?> sản phẩm có sẵn</span>
          </div>

          <div class="spec-item">
            <span class="spec-label"><i class="fas fa-star"></i> Đá đính:</span>
            <span class="spec-value">Đá CZ (Cubic Zirconia) cao cấp</span>
          </div>

          <div class="spec-item">
            <span class="spec-label"><i class="fas fa-money-bill-wave"></i> Giá gốc:</span>
            <span class="spec-value"><?php echo number_format($giaGoc, 0, ',', '.'); ?>đ</span>
          </div>

          <div class="spec-item">
            <span class="spec-label"><i class="fas fa-percentage"></i> Giảm giá:</span>
            <span class="spec-value"><?php echo isset($product['GIAM_GIA']) && $product['GIAM_GIA'] > 0 ? $product['GIAM_GIA'] . '%' : 'Không giảm'; ?></span>
          </div>

          <div class="spec-item">
            <span class="spec-label"><i class="fas fa-dollar-sign"></i> Giá sau giảm:</span>
            <span class="spec-value" style="color: #e74c3c; font-weight: bold;"><?php echo number_format($giaSauGiam, 0, ',', '.'); ?>đ</span>
          </div>

          <div class="spec-item">
            <span class="spec-label"><i class="fas fa-gift"></i> Quà tặng kèm:</span>
            <span class="spec-value">Hộp đựng + Túi nhung + Giấy bảo hành</span>
          </div>
        </div>
      </div>

      <div id="reviews" class="tab-content">
        <div class="reviews-summary">
          <div class="rating-overview">
            <div class="rating-score">4.5</div>
            <div class="rating-stars-large">
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star-half-alt"></i>
            </div>
            <div class="rating-count"><?php echo count($current_reviews); ?> đánh giá</div>
          </div>

          <div class="rating-bars">
            <div class="rating-bar">
              <span class="bar-label">5 <i class="fas fa-star"></i></span>
              <div class="bar">
                <div class="bar-fill" style="width: 70%"></div>
              </div>
              <span class="bar-count"><?php echo floor(count($current_reviews) * 0.7); ?></span>
            </div>
            <div class="rating-bar">
              <span class="bar-label">4 <i class="fas fa-star"></i></span>
              <div class="bar">
                <div class="bar-fill" style="width: 20%"></div>
              </div>
              <span class="bar-count"><?php echo floor(count($current_reviews) * 0.2); ?></span>
            </div>
            <div class="rating-bar">
              <span class="bar-label">3 <i class="fas fa-star"></i></span>
              <div class="bar">
                <div class="bar-fill" style="width: 6%"></div>
              </div>
              <span class="bar-count"><?php echo floor(count($current_reviews) * 0.06); ?></span>
            </div>
            <div class="rating-bar">
              <span class="bar-label">2 <i class="fas fa-star"></i></span>
              <div class="bar">
                <div class="bar-fill" style="width: 3%"></div>
              </div>
              <span class="bar-count"><?php echo floor(count($current_reviews) * 0.03); ?></span>
            </div>
            <div class="rating-bar">
              <span class="bar-label">1 <i class="fas fa-star"></i></span>
              <div class="bar">
                <div class="bar-fill" style="width: 1%"></div>
              </div>
              <span class="bar-count"><?php echo floor(count($current_reviews) * 0.01); ?></span>
            </div>
          </div>
        </div>

        <?php foreach ($current_reviews as $review): ?>
          <div class="review-item">
            <div class="review-header">
              <div class="reviewer-avatar" style="background: <?php echo $review['color']; ?>">
                <?php echo $review['avatar']; ?></div>
              <div>
                <div class="reviewer-name"><?php echo htmlspecialchars($review['name']); ?></div>
                <div class="review-date"><?php echo $review['date']; ?></div>
              </div>
            </div>
            <div class="review-rating">
              <?php for ($i = 1; $i <= 5; $i++): ?>
                <?php if ($i <= $review['rating']): ?>
                  <i class="fas fa-star"></i>
                <?php else: ?>
                  <i class="far fa-star"></i>
                <?php endif; ?>
              <?php endfor; ?>
            </div>
            <div class="review-content">
              <?php echo htmlspecialchars($review['content']); ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Related Products -->
    <div class="related-products">
      <h2 class="section-header">
        <i class="fas fa-fire" style="color: #ff6b35;"></i> Sản phẩm tương tự
      </h2>
      <div class="products-grid">
        <?php while ($row = $related_products->fetch_assoc()): ?>
          <a href="chitietsanpham.php?ma_sp=<?php echo $row['MA_SP']; ?>" class="product-card">
            <img src="<?php echo htmlspecialchars($row['HINH_ANH']); ?>"
              alt="<?php echo htmlspecialchars($row['TEN_SP']); ?>" class="product-card-img">
            <div class="product-card-name"><?php echo htmlspecialchars($row['TEN_SP']); ?></div>
            <div>
              <?php
              $giaGoc = $row['GIA'];
              $giamGia = isset($row['GIAM_GIA']) ? $row['GIAM_GIA'] : 0;

              if ($giamGia > 0) {
                // Tính giá sau giảm = Giá gốc * (100 - phần trăm giảm) / 100
                $giaSauGiam = $giaGoc * (100 - $giamGia) / 100;
                echo '<span class="product-card-price">' . number_format($giaSauGiam, 0, ",", ".") . 'đ</span>';
                echo '<span class="product-card-old-price">' . number_format($giaGoc, 0, ",", ".") . 'đ</span>';
              } else {
                echo '<span class="product-card-price">' . number_format($giaGoc, 0, ",", ".") . 'đ</span>';
              }
              ?>
            </div>
          </a>
        <?php endwhile; ?>
      </div>
    </div>
  </div>

  <script>
// ===== CHỨC NĂNG CHUYỂN ĐỔI TAB =====
function openTab(evt, tabName) {
    // Ẩn tất cả tab content
    const tabContents = document.getElementsByClassName('tab-content');
    for (let i = 0; i < tabContents.length; i++) {
        tabContents[i].classList.remove('active');
    }
    
    // Xóa active từ tất cả tab buttons
    const tabButtons = document.getElementsByClassName('tab-btn');
    for (let i = 0; i < tabButtons.length; i++) {
        tabButtons[i].classList.remove('active');
    }
    
    // Hiển thị tab được chọn và đánh dấu button
    document.getElementById(tabName).classList.add('active');
    evt.currentTarget.classList.add('active');
}

// ===== CHỨC NĂNG CHUYỂN ĐỔI MÀU SẮC =====
document.addEventListener('DOMContentLoaded', function() {
    const colorOptions = document.querySelectorAll('.color-option');
    const productImage = document.getElementById('main-product-image');
    
    colorOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Xóa class active từ tất cả các option
            colorOptions.forEach(opt => opt.classList.remove('active'));
            
            // Thêm class active cho option được chọn
            this.classList.add('active');
            
            // Lấy URL hình ảnh từ data attribute
            const newImageUrl = this.getAttribute('data-image');
            
            // Chỉ thay đổi hình ảnh nếu có URL
            if (newImageUrl && newImageUrl !== '') {
                // Thêm hiệu ứng fade
                productImage.style.opacity = '0.3';
                
                setTimeout(() => {
                    productImage.src = newImageUrl;
                    productImage.style.opacity = '1';
                }, 200);
            }
        });
    });
});

// ===== CHỨC NĂNG NÚT MUA NGAY =====
function buyNow(masp) {
    // Lấy số lượng được chọn
    const quantityInput = document.querySelector('input[name="quantity"]');
    const quantity = quantityInput ? parseInt(quantityInput.value) : 1;
    
    // Kiểm tra số lượng hợp lệ
    if (quantity < 1) {
        showToast({ title: 'Số lượng không hợp lệ', msg: 'Vui lòng chọn ít nhất 1 sản phẩm.', type: 'error' });
        return;
    }
    
    // Lấy giá khuyến mãi hiện tại (giá đang hiển thị)
    const currentPriceElement = document.querySelector('.current-price');
    const priceText = currentPriceElement ? currentPriceElement.textContent.replace(/[^\d]/g, '') : '0';
    const giaKhuyenMai = parseInt(priceText);
    
    // Tạo form để submit
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'thanhtoan.php'; // Chuyển trực tiếp đến trang thanh toán
    
    // Thêm input ẩn cho mã sản phẩm
    const inputMaSP = document.createElement('input');
    inputMaSP.type = 'hidden';
    inputMaSP.name = 'ma_sp';
    inputMaSP.value = masp;
    form.appendChild(inputMaSP);
    
    // Thêm input ẩn cho số lượng
    const inputQuantity = document.createElement('input');
    inputQuantity.type = 'hidden';
    inputQuantity.name = 'quantity';
    inputQuantity.value = quantity;
    form.appendChild(inputQuantity);
    
    // Thêm input ẩn cho giá khuyến mãi
    const inputPrice = document.createElement('input');
    inputPrice.type = 'hidden';
    inputPrice.name = 'gia';
    inputPrice.value = giaKhuyenMai;
    form.appendChild(inputPrice);
    
    // Thêm input ẩn để đánh dấu là mua ngay
    const inputBuyNow = document.createElement('input');
    inputBuyNow.type = 'hidden';
    inputBuyNow.name = 'buy_now';
    inputBuyNow.value = '1';
    form.appendChild(inputBuyNow);
    
    // Thêm form vào body và submit
    document.body.appendChild(form);
    form.submit();
}

// ===== TOAST NOTIFICATION =====
function showToast({ title, msg, type = 'success', actions = [] }) {
    const old = document.querySelector('.toast');
    if (old) { old.classList.add('hide'); setTimeout(() => old.remove(), 300); }

    const iconMap = { success: 'fa-check', error: 'fa-exclamation-circle' };
    const actionsHTML = actions.map(a =>
        `<button class="toast-btn ${a.style}" onclick="${a.onclick}">${a.label}</button>`
    ).join('');

    const t = document.createElement('div');
    t.className = `toast${type === 'error' ? ' error-toast' : ''}`;
    t.innerHTML = `
        <div class="toast-header">
            <div class="toast-icon ${type}"><i class="fas ${iconMap[type]}"></i></div>
            <div class="toast-text">
                <div class="toast-title">${title}</div>
                ${msg ? `<div class="toast-msg">${msg}</div>` : ''}
            </div>
            <button class="toast-close" onclick="this.closest('.toast').classList.add('hide'); setTimeout(()=>this.closest('.toast').remove(),300)">
                <i class="fas fa-times"></i>
            </button>
        </div>
        ${actionsHTML ? `<div class="toast-actions">${actionsHTML}</div>` : ''}
        <div class="toast-progress"></div>
    `;
    document.body.appendChild(t);

    const timer = setTimeout(() => {
        t.classList.add('hide');
        setTimeout(() => t.remove(), 300);
    }, 30000);

    t.querySelector('.toast-close').addEventListener('click', () => clearTimeout(timer));
}

// ===== CHỨC NĂNG THÊM VÀO GIỎ HÀNG =====
function addToCart(masp) {
    const quantityInput = document.querySelector('input[name="quantity"]');
    const quantity = quantityInput ? parseInt(quantityInput.value) : 1;

    if (quantity < 1) {
        showToast({ title: 'Số lượng không hợp lệ', msg: 'Vui lòng chọn ít nhất 1 sản phẩm.', type: 'error' });
        return;
    }

    const formData = new FormData();
    formData.append('ma_sp', masp);
    formData.append('quantity', quantity);
    formData.append('action', 'add_to_cart');

    fetch('cart_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast({
                title: 'Đã thêm vào giỏ hàng!',
                msg: 'Sản phẩm đã được thêm thành công.',
                type: 'success',
                actions: [
                    { label: 'Tiếp tục mua', style: 'toast-btn-outline', onclick: "this.closest('.toast').classList.add('hide')" },
                    { label: '🛒 Xem giỏ hàng', style: 'toast-btn-solid', onclick: "window.location.href='giohang.php'" }
                ]
            });
            updateCartCount();
        } else {
            showToast({ title: 'Không thể thêm!', msg: data.message || 'Có lỗi xảy ra, vui lòng thử lại.', type: 'error' });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast({ title: 'Lỗi kết nối', msg: 'Không thể kết nối đến máy chủ.', type: 'error' });
    });
}

// ===== CẬP NHẬT SỐ LƯỢNG GIỎ HÀNG =====
function updateCartCount() {
    fetch('get_cart_count.php')
        .then(response => response.json())
        .then(data => {
            const cartBadge = document.querySelector('.cart-badge');
            if (cartBadge && data.count) {
                cartBadge.textContent = data.count;
                cartBadge.style.display = data.count > 0 ? 'block' : 'none';
            }
        })
        .catch(error => console.error('Error:', error));
}
</script>
  <?php
  $stmt->close();
  $stmt_related->close();
  $conn->close();
  ?>
</body>
</html>