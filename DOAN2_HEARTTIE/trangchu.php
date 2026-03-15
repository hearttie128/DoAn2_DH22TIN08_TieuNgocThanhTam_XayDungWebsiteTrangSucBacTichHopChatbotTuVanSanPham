<?php
session_start();
require_once 'auth_check.php';

$conn = new mysqli("localhost", "root", "", "QLCHDT");
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
$sql = "SELECT * FROM SANPHAM WHERE TRANG_THAI = 'Đang bán'";
$result = $conn->query($sql);
$user_info = getUserInfo();
$is_logged_in = isLoggedIn();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="IE=edg" http-equiv="X-UA-Compatible" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" rel="stylesheet" />
    <link href="styles.css" rel="stylesheet" />
    <title>HEARTTIE</title>
    <style>
        /* ===== COUNTDOWN TIMER - DESIGN ĐẸP ===== */
        .countdown-wrapper {
            background: linear-gradient(135deg, #83c5ff 0%, #ffe5f4 100%);
            padding: 25px;
            border-radius: 20px;
            margin: 30px auto;
            max-width: 700px;
            text-align: center;
            box-shadow: 0 15px 35px rgba(79, 172, 254, 0.3);
            position: relative;
            overflow: hidden;
        }

        .countdown-wrapper::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            animation: shimmer 3s infinite;
        }

        @keyframes shimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        .countdown-header {
            font-family: Arial, sans-serif;
            color: white;
            font-size: 33px;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
            position: relative;
            z-index: 1;
            letter-spacing: 1px;
        }

        .countdown-header i {
            color: #ffc831;
            animation: blink 1.5s infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        .countdown-display {
            display: flex;
            justify-content: center;
            gap: 20px;
            position: relative;
            z-index: 1;
        }

        .time-unit {
            background: linear-gradient(145deg, #ffffff, #f0f9ff);
            padding: 20px 25px;
            border-radius: 15px;
            min-width: 90px;
            box-shadow: 
                0 8px 20px rgba(0,0,0,0.1),
                inset 0 1px 0 rgba(255,255,255,0.6);
            transition: transform 0.3s ease;
        }

        .time-unit:hover {
            transform: translateY(-5px) scale(1.05);
        }

        .time-value {
            font-size: 36px;
            font-weight: 800;
            background: linear-gradient(135deg, #4769ff 0%, #8e86ff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: block;
            line-height: 1;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .time-label {
            font-size: 13px;
            color: #666;
            margin-top: 8px;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        /* ===== HOA RƠI - XANH DƯƠNG NHỎ GỌN ===== */
        .petal {
            position: fixed;
            top: -30px;
            font-size: 16px;
            animation: falling linear infinite;
            z-index: 9999;
            pointer-events: none;
            filter: drop-shadow(0 2px 4px rgba(79, 172, 254, 0.3));
        }

        @keyframes falling {
            0% {
                transform: translateY(-30px) translateX(0) rotate(0deg);
                opacity: 0.8;
            }
            50% {
                transform: translateY(50vh) translateX(30px) rotate(180deg);
                opacity: 0.6;
            }
            100% {
                transform: translateY(100vh) translateX(0) rotate(360deg);
                opacity: 0;
            }
        }

        /* ===== MOBILE NAVIGATION - THIẾT KẾ MỚI ===== */
        .mobile-nav {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, #ffffff 0%, #f8f9fa 100%);
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            padding: 8px 0 12px 0;
            border-top: 1px solid rgba(79, 172, 254, 0.2);
        }

        .mobile-nav-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            max-width: 100%;
            gap: 0;
        }

        .mobile-nav-item {
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            padding: 5px;
        }

        .mobile-nav-item a {
            text-decoration: none;
            color: #6c757d;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            padding: 4px;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .mobile-nav-item.active a {
            color: #4facfe;
            background: rgba(79, 172, 254, 0.1);
        }

        .mobile-nav-item a:hover {
            color: #4facfe;
            transform: translateY(-2px);
        }

        .mobile-nav-icon {
            font-size: 22px;
            transition: transform 0.3s ease;
        }

        .mobile-nav-item.active .mobile-nav-icon {
            transform: scale(1.1);
        }

        .mobile-nav-label {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            body {
                padding-bottom: 75px;
            }

            .mobile-nav {
                display: block;
            }

            .countdown-wrapper {
                margin: 20px 15px;
                padding: 20px 15px;
            }

            .countdown-header {
                font-size: 16px;
            }

            .time-unit {
                min-width: 70px;
                padding: 15px 18px;
            }

            .time-value {
                font-size: 28px;
            }

            .time-label {
                font-size: 11px;
            }

            .countdown-display {
                gap: 12px;
            }
        }

        @media (max-width: 480px) {
            .time-unit {
                min-width: 60px;
                padding: 12px 15px;
            }

            .time-value {
                font-size: 24px;
            }

            .countdown-display {
                gap: 8px;
            }
        }

        /* ===== SMOOTH SCROLL ===== */
        html {
            scroll-behavior: smooth;
        }

        /* ===== ANIMATION KHI LOAD ===== */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .countdown-wrapper {
            animation: fadeInUp 0.6s ease-out;
        }
    </style>
</head>

<body>
    <!-- Container cho hoa rơi -->
    <div id="petals-container"></div>

    <nav>
        <div class="search-container">
            <ul>
                <li><a href=""><img alt="logo" src="Hearttie.png" /></a></li>
                <li>
                    <form action="timkiem.php" method="get" style="position: relative;">
                        <input type="text" name="keyword" placeholder="Tìm sản phẩm..." required>
                        <button type="submit"
                            style="position: absolute; top: 50%; transform: translateY(-50%); right: 10px; background: none; border: none; cursor: pointer;">
                            <i class="fa-solid fa-magnifying-glass fa-lg" style="color: #212121;"></i>
                        </button>
                    </form>
                </li>
                <li><button onclick="window.location.href='giohang.php'"><i
                            class="fa-solid fa-cart-shopping fa-lg"></i>Giỏ hàng</button></li>
                <li><button onclick="window.location.href='tracuudonhang.php'"><i
                            class="fa-solid fa-truck fa-lg"></i>Tra cứu đơn hàng</button></li>
                <li>
                    <?php if ($is_logged_in): ?>
                        <button type="button" class="btn-logout" onclick="showLogoutModal()">
                            <i class="fa-solid fa-arrow-right-from-bracket fa-lg"></i> Đăng xuất
                        </button>
                    <?php else: ?>
                        <button onclick="window.location.href='dangnhap_user.php'" class="btn-login">
                            <i class="fa-solid fa-user fa-lg"></i>Đăng nhập
                        </button>
                    <?php endif; ?>
                </li>
                <!-- Modal Đăng Xuất -->
                <div class="modal-container" id="logoutModal" style="display: none;">
                    <div class="modal-header">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h3>Xác Nhận Đăng Xuất</h3>
                    </div>
                    <div class="modal-body">
                        <p>Bạn có chắc chắn muốn đăng xuất khỏi tài khoản không?</p>
                        <div class="modal-buttons">
                            <form method="POST" action="user_logout.php">
                                <button type="submit" class="modal-btn btn-confirm">
                                    Đăng Xuất
                                </button>
                            </form>
                            <button type="button" class="modal-btn btn-cancel" onclick="hideLogoutModal()">
                                Hủy
                            </button>
                        </div>
                    </div>
                </div>

                <li><button id="btnCategory"><i class="fa-solid fa-bars fa-lg"></i>Danh mục</button></li>
            </ul>
        </div>
    </nav>
    <section class="home" id="home">
        <div class="container">
            <div class="home-container">
                <ul>
                    <li><a href=""><i class="fa-solid fa-house fa-lg"></i>Trang chủ</a></li>
                    <li><a href="lienhe.php"><i class="fa-regular fa-newspaper fa-lg"></i>Liên hệ</a></li>
                    <li><a href="tuvan.php"><i class="fa-solid fa-headset fa-lg"></i>Tư vấn</a></li>
                    <li>
                        <a href="#" onclick="toggleChat(); return false;">
                            <i class="fa-brands fa-facebook-messenger fa-lg"></i> Chatbot
                        </a>
                    </li>

                    <div id="chat-box" style="
                        position: fixed;
                        top: 113px;
                        right: 20px;
                        width: 400px;
                        height: 500px;
                        background-color: #fff;
                        border-radius: 10px;
                        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                        display: none;
                        flex-direction: column;
                        overflow: hidden;
                        z-index: 1000;
                        font-family: Arial, sans-serif;
                    ">

                        <!-- Header -->
                        <div style="
                            background-color: #4A92E4;
                            color: #fff;
                            padding: 12px;
                            font-weight: bold;
                            font-size: 16px;
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                        ">
                            Hỗ trợ trực tuyến
                            <span onclick="toggleChat()" style="cursor: pointer;">✖</span>
                        </div>

                        <!-- Messages -->
                        <div id="chat-messages" style="
                            flex: 1;
                            padding: 10px;
                            overflow-y: auto;
                            font-size: 16px;
                            background-color: #f9f9f9;
                        ">
                            <div style="margin-bottom: 10px;">🤖 Xin chào! Bạn cần hỗ trợ gì không?</div>
                        </div>

                        <!-- Input -->
                        <div style="
                        font-size: 16px;
                            border-top: 1px solid #ddd;
                            display: flex;
                            padding: 8px;
                            background-color: #fff;
                        ">
                            <input type="text" id="chat-input" placeholder="Nhập tin nhắn..."
                                onkeydown="if(event.key==='Enter') sendMessage()"
                                style="flex: 1; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                            <button onclick="sendMessage()" style="
                                background-color: #4A92E4;
                                color: white;
                                border: none;
                                padding: 8px 14px;
                                margin-left: 8px;
                                border-radius: 4px;
                                cursor: pointer;
                            ">
                                <i class="fa-solid fa-location-arrow fa-rotate-by"
                                    style="--fa-rotate-angle: 45deg;"></i>
                            </button>
                        </div>
                    </div>
                </ul>
            </div>
        </div>
    </section>
    <!-- Overlay nền mờ -->
    <div id="overlay"></div>
    <!-- Popup DANH MỤC -->
    <div id="categoryPanel">
        <div class="category-header">
            <h3>DANH MỤC SẢN PHẨM</h3>
            <span class="close-btn" id="closeCategory">
                <i class="fa-solid fa-xmark"></i>
            </span>
        </div>
        <ul class="category-list">
            <li class="category-item">
                <img alt="dây chuyền" class="category-img"
                    src="https://lili.vn/wp-content/uploads/2022/09/Day-chuyen-doi-bac-dinh-da-CZ-hinh-ca-voi-va-buom-Brenna-LILI_123985_4-400x400.jpg" />
                <a class="product-name filter-link" href="javascript:void(0)" data-brand="dây chuyền">Dây chuyền</a>
                <span class="product-desc">Dây chuyền bạc nữ cao cấp, đính đá CZ và kim cương</span>
            </li>
            <li class="category-item">
                <img alt="lắc" class="category-img"
                    src="https://lili.vn/wp-content/uploads/2022/06/Lac-tay-cap-doi-bac-dinh-da-CZ-trai-tim-cua-bien-Erasmus-LILI_199377_3-400x400.jpg" />
                <a class="product-name filter-link" href="javascript:void(0)" data-brand="lắc">Lắc</a>
                <span class="product-desc">Lắc tay, lắc chân bạc nữ sang trọng</span>
            </li>
            <li class="category-item">
                <img alt="nhẫn" class="category-img"
                    src="https://lili.vn/wp-content/uploads/2021/12/Nhan-doi-bac-hiep-si-va-cong-chua-dinh-da-CZ-LILI_819229_1-400x400.jpg" />
                <a class="product-name filter-link" href="javascript:void(0)" data-brand="nhẫn">Nhẫn</a>
                <span class="product-desc">Nhẫn bạc nữ đính đá, nhẫn đôi cặp</span>
            </li>
            <li class="category-item">
                <img alt="bông tai" class="category-img"
                    src="https://lili.vn/wp-content/uploads/2022/03/Bong-tai-bac-nu-dinh-da-CZ-co-4-la-LILI_453329_1-400x400.jpg" />
                <a class="product-name filter-link" href="javascript:void(0)" data-brand="bông tai">Bông tai</a>
                <span class="product-desc">Bông tai bạc nữ thời trang, đính đá CZ</span>
            </li>
        </ul>
    </div>

    <div class="img-container">
        <img alt=""
            src="https://image.donghohaitrieu.com/wp-content/uploads/2025/01/trang-suc-bac.jpg" />
    </div>

    <div class="banner-wrapper">
        <div class="banner" id="shopeeBanner">
            <span class="close-btn" onclick="closeBanner()">✕</span>

            <a href="https://shopee.vn" target="_blank">
                <img src="https://calliesilver.com/wp-content/uploads/2025/09/598x900-Banner-Landscape.zip-2-2-2-1536x768.jpg"
                    alt="Shopee Banner" />
            </a>
        </div>
    </div>

    <!-- Đồng hồ đếm ngược Flash Sale -->
    <div class="countdown-wrapper">
        <div class="countdown-header">
            <i class="fa-solid fa-bolt"></i>
            SALE "SO ĐẬM" 😉
            <i class="fa-solid fa-bolt"></i>
        </div>
        <div class="countdown-display">
            <div class="time-unit">
                <span class="time-value" id="hours">00</span>
                <div class="time-label">Giờ</div>
            </div>
            <div class="time-unit">
                <span class="time-value" id="minutes">00</span>
                <div class="time-label">Phút</div>
            </div>
            <div class="time-unit">
                <span class="time-value" id="seconds">00</span>
                <div class="time-label">Giây</div>
            </div>
        </div>
    </div>

    <section class="flashsale" id="flashsale">
        <h2>Flashsale giá sốc</h2>
        <div class="flashsale-container">
            <article class="flashsale-item">
                <a href="chitietsanpham.php?id=1" class="flashsale-link">
                    <div class="flashsale-img">
                        <button><i class="button-img"><img alt="Dây chuyền bạc nữ đính đá CZ cá tiên"
                                    src="https://lili.vn/wp-content/uploads/2021/12/Day-chuyen-bac-nu-phong-cach-co-trang-CZ-LILI_831944_3.jpg" /></i></button>
                    </div>
                    <h3>Dây chuyền bạc nữ đính đá CZ cá tiên</h3>
                    <p class="description">
                        TIE_831944
                    </p>
                   <div class="price-container-flashsale">
                        <span class="new-price">1.381.600đ</span>
                        <div class="price-row">
                            <span class="old-price"><del>1.570.000đ</del></span>
                            <span class="discount">-12%</span>
                        </div>
                    </div>
                    <div class="btn-container">
                        <button class="btn">Xem ngay</button>
                    </div>
                </a>
            </article>

            <article class="flashsale-item">
                <a href="chitietsanpham.php?id=2" class="flashsale-link">
                    <div class="flashsale-img">
                        <button><i class="button-img"><img alt="Lắc chân bạc nữ đính đá CZ hình cỏ 4 lá Mildred"
                                    src="https://lili.vn/wp-content/uploads/2022/09/Lac-chan-bac-nu-dinh-da-CZ-hinh-co-4-la-Mildred-LILI_763298_2.jpg" /></i></button>
                    </div>
                    <h3>Lắc chân bạc nữ đính đá CZ hình cỏ 4 lá Mildred</h3>
                    <p class="description">
                        TIE_763298
                    </p>
                    <div class="price-container-flashsale">
                        <span class="new-price">1.752.000đ</span>
                        <div class="price-row">
                            <span class="old-price"><del>2.190.000đ</del></span>
                            <span class="discount">-20%</span>
                        </div>
                    </div>
                    <div class="btn-container">
                        <button class="btn">Xem ngay</button>
                    </div>
                </a>
            </article>

           <article class="flashsale-item">
                <a href="chitietsanpham.php?id=3" class="flashsale-link">
                    <div class="flashsale-img">
                        <button><i class="button-img"><img alt="Lắc tay bạc nữ đính pha lê Swarovski trái tim của biển"
                                    src="https://lili.vn/wp-content/uploads/2020/11/vong-tay-bac-925-dinh-pha-le-swarovski-3.jpg" /></i></button>
                    </div>
                    <h3>Lắc tay bạc nữ đính pha lê Swarovski trái tim của biển</h3>
                    <p class="description">
                        TIE_579467
                    </p>
                    <div class="price-container-flashsale">
                        <span class="new-price">1.508.800đ</span>
                        <div class="price-row">
                            <span class="old-price"><del>1.840.000đ</del></span>
                            <span class="discount">-18%</span>
                        </div>
                    </div>
                    <div class="btn-container">
                        <button class="btn">Xem ngay</button>
                    </div>
                </a>
            </article>

           <article class="flashsale-item">
                <a href="chitietsanpham.php?id=4" class="flashsale-link">
                    <div class="flashsale-img">
                        <button><i class="button-img"><img alt="Bông tai bạc Ý S925 nữ mạ bạch kim đính đá CZ"
                                    src="https://lili.vn/wp-content/uploads/2021/12/Bong-tai-bac-Y-S925-nu-ma-bach-kim-dinh-da-CZ-hinh-trai-tim-LILI_991582_10.jpg" /></i></button>
                    </div>
                    <h3>Bông tai bạc Ý S925 nữ mạ bạch kim đính đá CZ</h3>
                    <p class="description">
                        TIE_991582
                    </p>
                    <div class="price-container-flashsale">
                        <span class="new-price">1.255.800đ</span>
                        <div class="price-row">
                            <span class="old-price"><del>1.380.000đ</del></span>
                            <span class="discount">-9%</span>
                        </div>
                    </div>
                    <div class="btn-container">
                        <button class="btn">Xem ngay</button>
                    </div>
                </a>
            </article>
        </div>
    </section>

 <section class="suggestion">
        <h3>Gợi ý cho bạn</h3>
        <div class="suggestion-container">
            <article class="suggestion-item">
                <a href="chitietsanpham.php?id=5">
                    <div class="installments-container">
                        <div class="installments-box">
                            <button class="installments">Trả góp 0%</button>
                        </div>
                    </div>
                    <div class="suggestion-img">
                        <button><i class="button-img"><img alt="Dây chuyền bạc nữ đính kim cương tự nhiên"
                                    src="https://lili.vn/wp-content/uploads/2022/04/Day-chuyen-bac-nu-dinh-kim-cuong-tu-nhieu-co-4-la-LILI_832459_2.jpg" /></i></button>
                    </div>
                    <h4>Dây chuyền bạc nữ đính kim cương tự nhiên</h4>
                    <p class="description">
                        TIE_832459
                    </p>
                    <div class="price-container-suggestion">
                        <span class="new-price">1.210.400đ</span>
                        <div class="price-row">
                            <span class="old-price"><del>1.360.000đ</del></span>
                            <span class="discount">-11%</span>
                        </div>
                    </div>
                    <div class="btn-container">
                        <button class="btn">Xem ngay</button>
                    </div>
                </a>
            </article>

            <article class="suggestion-item">
                <a href="chitietsanpham.php?id=6">
                    <div class="installments-container">
                        <div class="installments-box">
                            <button class="installments">Trả góp 0%</button>
                        </div>
                    </div>
                    <div class="suggestion-img">
                        <button><i class="button-img"><img alt="Lắc tay bạc Ta S999 nữ cỏ 4 lá cách điệu"
                                    src="https://lili.vn/wp-content/uploads/2021/11/Lac-tay-bac-nu-co-4-la-cach-dieu-LILI_661577_6-768x768.jpg" /></i></button>
                    </div>
                    <h4>Lắc tay bạc Ta S999 nữ cỏ 4 lá cách điệu</h4>
                    <p class="description">
                        TIE_661577
                    </p>
                    <div class="price-container-suggestion">
                        <span class="new-price">2.205.000đ</span>
                        <div class="price-row">
                            <span class="old-price"><del>2.450.000đ</del></span>
                            <span class="discount">-10%</span>
                        </div>
                    </div>
                    <div class="btn-container">
                        <button class="btn">Xem ngay</button>
                    </div>
                </a>
            </article>

            <article class="suggestion-item">
                <a href="chitietsanpham.php?id=7">
                    <div class="installments-container">
                        <div class="installments-box">
                            <button class="installments">Trả góp 0%</button>
                        </div>
                    </div>
                    <div class="suggestion-img">
                        <button><i class="button-img"><img alt="Bông tai bạc nữ tròn đính đá CZ hình bông hoa 5 cánh"
                                    src="https://lili.vn/wp-content/uploads/2021/12/Bong-tai-bac-nu-tron-hinh-bong-hoa-5-canh-Cute-LILI_749976_2.jpg" /></i></button>
                    </div>
                    <h4>Bông tai bạc nữ tròn đính đá CZ hình bông hoa 5 cánh</h4>
                    <p class="description">
                       TIE_749976
                    </p>
                    <div class="price-container-suggestion">
                        <span class="new-price">1.148.400đ</span>
                        <div class="price-row">
                            <span class="old-price"><del>1.320.000đ</del></span>
                            <span class="discount">-13%</span>
                        </div>
                    </div>
                    <div class="btn-container">
                        <button class="btn">Xem ngay</button>
                    </div>
                </a>
            </article>

            <article class="suggestion-item">
                <a href="chitietsanpham.php?id=8">
                    <div class="installments-container">
                        <div class="installments-box">
                            <button class="installments">Trả góp 0%</button>
                        </div>
                    </div>
                    <div class="suggestion-img">
                        <button><i class="button-img"><img alt="Nhẫn bạc nữ đính đá CZ hoa bướm"
                                    src="https://lili.vn/wp-content/uploads/2022/08/Nhan-bac-nu-dinh-da-CZ-hoa-buom-LILI_661591_5.jpg" /></i></button>
                    </div>
                    <h4>Nhẫn bạc nữ đính đá CZ hoa bướm</h4>
                    <p class="description">
                        TIE_661591
                    </p>
                    <div class="price-container-suggestion">
                        <span class="new-price">1.179.000đ</span>
                        <div class="price-row">
                            <span class="old-price"><del>1.310.000đ</del></span>
                            <span class="discount">-10%</span>
                        </div>
                    </div>
                    <div class="btn-container">
                        <button class="btn">Xem ngay</button>
                    </div>
                </a>
            </article>
        </div>
    </section>

    <section class="product" id="san-pham">
        <div class="container">
            <h4>Sản phẩm</h4>

            <!-- Form LỌC và sắp xếp - LUÔN HIỂN THỊ -->
            <div class="filter-container">
                <div class="filter">
                    <button class="filter-button"><i class="fa-solid fa-filter" style="color: #74C0FC;"></i>Lọc</button>
                </div>
                <div class="filter-options">
                    <button class="filter-button" onclick="filterProducts('dây chuyền')">Dây chuyền</button>
                    <button class="filter-button" onclick="filterProducts('lắc')">Lắc</button>
                    <button class="filter-button" onclick="filterProducts('nhẫn')">Nhẫn</button>
                    <button class="filter-button" onclick="filterProducts('bông tai')">Bông tai</button>
                </div>
            </div>

            <!-- Danh sách sản phẩm - ẨN BAN ĐẦU -->
            <div class="product-container" id="productList" style="display: none;">
                <?php
                // Reset lại con trỏ nếu đã dùng result trước đó
                if (isset($result)) {
                    $result->data_seek(0);
                } else {
                    $sql = "SELECT * FROM SANPHAM WHERE TRANG_THAI = 'Đang bán'";
                    $result = $conn->query($sql);
                }

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        // ===== XÁC ĐỊNH THƯƠNG HIỆU - CẢI TIẾN =====
                        $brand = 'all'; // Mặc định
                        
                        // Xác định từ TEN_SP
                        if (isset($row['TEN_SP'])) {
                            $productName = strtolower($row['TEN_SP']);
                            
                            if (strpos($productName, 'dây chuyền') !== false) {
                                $brand = 'dây chuyền';
                            } elseif (strpos($productName, 'lắc') !== false) {
                                $brand = 'lắc';
                            } elseif (strpos($productName, 'nhẫn') !== false) {
                                $brand = 'nhẫn';
                            } elseif (strpos($productName, 'bông tai') !== false || strpos($productName, 'redmi') !== false) {
                                $brand = 'bông tai';
                            } 
                        }
                        
                        // Chuẩn hóa brand (bỏ khoảng trắng, viết thường)
                        $brand = strtolower(trim($brand));

                        // ===== TẠO HTML SẢN PHẨM =====
                        echo '<div class="product-card" data-brand="' . htmlspecialchars($brand) . '">';
                        
                        // Link chi tiết sản phẩm
                        $productId = $row['MA_SP'];
                        echo '<a href="chitietsanpham.php?id=' . htmlspecialchars($productId) . '">';

                        // Nhãn trả góp nếu có giảm giá
                        $gia = $row['GIA'];
                        $giamGia = isset($row['GIAM_GIA']) ? $row['GIAM_GIA'] : 0;
                        
                        if ($giamGia > 0) {
                            echo '<div class="installment-badge">Giảm ' . $giamGia . '%</div>';
                        }

                        // Hình ảnh
                        $hinhAnh = $row['HINH_ANH'];
                        
                        // Tên sản phẩm
                        $tenSP = $row['TEN_SP'];
                        
                        // Mô tả
                        $moTa = isset($row['MO_TA']) ? $row['MO_TA'] : '';
                        
                        echo '<img alt="' . htmlspecialchars($tenSP) . '" src="' . htmlspecialchars($hinhAnh) . '" />';
                        echo '<h3>' . htmlspecialchars($tenSP) . '</h3>';
                        
                        if ($moTa) {
                            echo '<p class="description">' . htmlspecialchars($moTa) . '</p>';
                        }
                        
                        echo '<div class="price">';
                        
                        // Tính giá sau giảm
                        if ($giamGia > 0) {
                            $giaSauGiam = $gia - ($gia * $giamGia / 100);
                            echo '<span class="current-price">' . number_format($giaSauGiam, 0, ',', '.') . 'đ</span>';
                            echo '<div class="price-row">';
                            echo '<span class="original-price"><del>' . number_format($gia, 0, ',', '.') . 'đ</del></span>';
                            echo '<span class="discount">-' . $giamGia . '%</span>';
                            echo '</div>';
                        } else {
                            echo '<span class="current-price">' . number_format($gia, 0, ',', '.') . 'đ</span>';
                        }
                        
                        echo '</div>';
                        echo '</a>';
                        echo '</div>';
                    }
                } else {
                    echo '<p style="text-align: center; padding: 40px; color: #999; grid-column: 1 / -1;">Không có sản phẩm nào trong database.</p>';
                }
                ?>
            </div>
        </div>
    </section>

    <div class="product-container">
        <article class="product-item">
            <a href="chitietsanpham.php?id=9">
                <div class="installments-container">
                    <div class="installments-box">
                        <button class="installments">Trả góp 0%</button>
                    </div>
                </div>
                <div class="product-img">
                    <button><img alt="Dây chuyền bạc nữ liền mặt mạ vàng đính đá CZ"
                            src="https://lili.vn/wp-content/uploads/2021/01/Day-chuyen-bac-nu-lien-mat-ma-vang-dinh-da-CZ-trai-tim-Double-LILI_583553_50-768x768.jpg" /></button>
                </div>
                <h5>Dây chuyền bạc nữ liền mặt mạ vàng đính đá CZ</h5>
                <p class="description"> TIE_583553</p>
                <div class="price-container-product">
                    <span class="new-price">1.877.900đ</span>
                    <div class="price-row">
                        <span class="old-price"><del>2.110.000đ</del></span>
                        <span class="discount">-11%</span>
                    </div>
                </div>
                <div class="btn-container">
                    <button class="btn">Xem ngay</button>
                </div>
            </a>
        </article>

        <article class="product-item">
            <a href="chitietsanpham.php?id=10">
                <div class="installments-container">
                    <div class="installments-box">
                        <button class="installments">Trả góp 0%</button>
                    </div>
                </div>
                <div class="product-img">
                    <button><img alt="Bông tai bạc nữ đính đá CZ hình những bông hoa"
                            src="https://lili.vn/wp-content/uploads/2021/01/Bong-tai-bac-dinh-da-Zircon-hinh-nhung-bong-hoa-Luu-ly-LILI_148289-02.jpg" /></button>
                </div>
                <h5>Bông tai bạc nữ đính đá CZ hình những bông hoa</h5>
                <p class="description"> TIE_148289</p>
                <div class="price-container-product">
                    <span class="new-price">1.186.800đ</span>
                    <div class="price-row">
                        <span class="old-price"><del>1.290.000đ</del></span>
                        <span class="discount">-8%</span>
                    </div>
                </div>
                <div class="btn-container">
                    <button class="btn">Xem ngay</button>
                </div>
            </a>
        </article>

        <article class="product-item">
            <a href="chitietsanpham.php?id=11">
                <div class="installments-container">
                    <div class="installments-box">
                        <button class="installments">Trả góp 0%</button>
                    </div>
                </div>
                <div class="product-img">
                    <button><img alt="Lắc tay bạc đính đá pha lê hình trái tim"
                            src="https://lili.vn/wp-content/uploads/2020/12/Vong-tay-bac-dinh-da-pha-le-hinh-trai-tim-LILI_427425-05.jpg" /></button>
                </div>
                <h5>Lắc tay bạc đính đá pha lê hình trái tim</h5>
                <p class="description"> TIE_427425</p>
                <div class="price-container-product">
                    <span class="new-price">2.511.600đ</span>
                    <div class="price-row">
                        <span class="old-price"><del>2.730.000đ</del></span>
                        <span class="discount">-8%</span>
                    </div>
                </div>
                <div class="btn-container">
                    <button class="btn">Xem ngay</button>
                </div>
            </a>
        </article>

        <article class="product-item">
            <a href="chitietsanpham.php?id=12">
                <div class="installments-container">
                    <div class="installments-box">
                        <button class="installments">Trả góp 0%</button>
                    </div>
                </div>
                <div class="product-img">
                    <button><img alt="Nhẫn đôi bạc free size đính đá CZ hiệp sĩ và công chúa"
                            src="https://lili.vn/wp-content/uploads/2021/12/Nhan-doi-bac-hiep-si-va-cong-chua-dinh-da-CZ-LILI_819229_3.jpg" /></button>
                </div>
                <h5>Nhẫn đôi bạc free size đính đá CZ hiệp sĩ và công chúa</h5>
                <p class="description"> TIE_819229</p>
                <div class="price-container-product">
                    <span class="new-price">2.196.000đ</span>
                    <div class="price-row">
                        <span class="old-price"><del>2.440.000đ</del></span>
                        <span class="discount">-10%</span>
                    </div>
                </div>
                <div class="btn-container">
                    <button class="btn">Xem ngay</button>
                </div>
            </a>
        </article>

        <article class="product-item">
            <a href="chitietsanpham.php?id=13">
                <div class="installments-container">
                    <div class="installments-box">
                        <button class="installments">Trả góp 0%</button>
                    </div>
                </div>
                <div class="product-img">
                    <button><img alt="Lắc tay bạc nữ cá tính mắt xích vuông trái tim"
                            src="https://lili.vn/wp-content/uploads/2021/11/Lac-tay-bac-nu-ca-tinh-mat-xich-vuong-trai-tim-Strong-Heart-LILI_414788_3.jpg" /></button>
                </div>
                <h5>Lắc tay bạc nữ cá tính mắt xích vuông trái tim</h5>
                <p class="description">TIE_414788</p>
                <div class="price-container-product">
                    <span class="new-price">2.030.400đ</span>
                    <div class="price-row">
                        <span class="old-price"><del>2.160.000đ</del></span>
                        <span class="discount">-6%</span>
                    </div>
                </div>
                <div class="btn-container">
                    <button class="btn">Xem ngay</button>
                </div>
            </a>
        </article>

        <article class="product-item">
            <a href="chitietsanpham.php?id=14">
                <div class="installments-container">
                    <div class="installments-box">
                        <button class="installments">Trả góp 0%</button>
                    </div>
                </div>
                <div class="product-img">
                    <button><img alt="Lắc chân bạc nữ dạng hạt 2 tầng đính mèo thần tài"
                            src="https://lili.vn/wp-content/uploads/2020/12/Lac-chan-bac-dang-hat-2-lop-dinh-meo-than-tai-LILI_631735-021.jpg" /></button>
                </div>
                <h5>Lắc chân bạc nữ dạng hạt 2 tầng đính mèo thần tài</h5>
                <p class="description">TIE_631735</p>
                <div class="price-container-product">
                    <span class="new-price">3.199.200đ</span>
                    <div class="price-row">
                        <span class="old-price"><del>3.440.000đ</del></span>
                        <span class="discount">-10%</span>
                    </div>
                </div>
                <div class="btn-container">
                    <button class="btn">Xem ngay</button>
                </div>
            </a>
        </article>

        <article class="product-item">
            <a href="chitietsanpham.php?id=15">
                <div class="installments-container">
                    <div class="installments-box">
                        <button class="installments">Trả góp 0%</button>
                    </div>
                </div>
                <div class="product-img">
                    <button><img alt="Dây chuyền Choker bạc nữ Magic"
                            src="https://lili.vn/wp-content/uploads/2021/08/Day-chuyen-Choker-bac-Magic-LILI_366642_2.jpg" /></button>
                </div>
                <h5>Dây chuyền Choker bạc nữ Magic</h5>
                <p class="description">TIE_366642</p>
                <div class="price-container-product">
                    <span class="new-price">1.992.300đ</span>
                    <div class="price-row">
                        <span class="old-price"><del>2.290.000đ</del></span>
                        <span class="discount">-13%</span>
                    </div>
                </div>
                <div class="btn-container">
                    <button class="btn">Xem ngay</button>
                </div>
            </a>
        </article>

        <article class="product-item">
            <a href="chitietsanpham.php?id=16">
                <div class="installments-container">
                    <div class="installments-box">
                        <button class="installments">Trả góp 0%</button>
                    </div>
                </div>
                <div class="product-img">
                    <button><img alt="Dây chuyền đôi bạc đính đá CZ Forever Love"
                            src="https://lili.vn/wp-content/uploads/2021/08/Day-chuyen-doi-bac-hinh-ca-heo-hong-Forever-Love-LILI_528145_1.jpg" /></button>
                </div>
                <h5>Dây chuyền đôi bạc đính đá CZ Forever Love</h5>
                <p class="description">TIE_528145</p>
                <div class="price-container-product">
                    <span class="new-price">2.840.500đ</span>
                    <div class="price-row">
                        <span class="old-price"><del>2.990.000đ</del></span>
                        <span class="discount">-5%</span>
                    </div>
                </div>
                <div class="btn-container">
                    <button class="btn">Xem ngay</button>
                </div>
            </a>
        </article>

        <article class="product-item hidden">
            <a href="chitietsanpham.php?id=iphone-16-pro-max">
                <div class="installments-container">
                    <div class="installments-box">
                        <button class="installments">Trả góp 0%</button>
                    </div>
                </div>
                <div class="product-img">
                    <button><img alt="iPhone 16 Pro Max"
                            src="https://cdn.tgdd.vn/Products/Images/42/305658/iphone-15-pro-max-blue-thumbnew-600x600.jpg" /></button>
                </div>
                <h5>iPhone 16 Pro Max</h5>
                <p class="description">6.7" Super Retina XDR Chip Apple A18 Pro 6 nhân 256GB</p>
                <div class="price-container-product">
                    <span class="new-price">29.990.000đ</span>
                    <div class="price-row">
                        <span class="old-price"><del>30.990.000đ</del></span>
                        <span class="discount">-3%</span>
                    </div>
                </div>
                <div class="btn-container">
                    <button class="btn">Xem ngay</button>
                </div>
            </a>
        </article>

        <article class="product-item hidden">
            <a href="chitietsanpham.php?id=iphone-13">
                <div class="installments-container">
                    <div class="installments-box">
                        <button class="installments">Trả góp 0%</button>
                    </div>
                </div>
                <div class="product-img">
                    <button><img alt="iPhone 13"
                            src="https://cdn.tgdd.vn/Products/Images/42/223602/iphone-13-midnight-2-600x600.jpg" /></button>
                </div>
                <h5>iPhone 13</h5>
                <p class="description">6.1" Super Retina XDR Chip Apple A15 Bionic 128GB</p>
                <div class="price-container-product">
                    <span class="new-price">11.690.000đ</span>
                    <div class="price-row">
                        <span class="old-price"><del>14.990.000đ</del></span>
                        <span class="discount">-22%</span>
                    </div>
                </div>
                <div class="btn-container">
                    <button class="btn">Xem ngay</button>
                </div>
            </a>
        </article>

        <article class="product-item hidden">
            <a href="chitietsanpham.php?id=iphone-15">
                <div class="installments-container">
                    <div class="installments-box">
                        <button class="installments">Trả góp 0%</button>
                    </div>
                </div>
                <div class="product-img">
                    <button><img alt="iPhone 15"
                            src="https://cdn.tgdd.vn/Products/Images/42/281570/iphone-15-vang-thumb-600x600.jpg" /></button>
                </div>
                <h5>iPhone 15</h5>
                <p class="description">6.1" Super Retina XDR Chip Apple A16 Bionic 128GB</p>
                <div class="price-container-product">
                    <span class="new-price">15.890.000đ</span>
                    <div class="price-row">
                        <span class="old-price"><del>19.990.000đ</del></span>
                        <span class="discount">-20%</span>
                    </div>
                </div>
                <div class="btn-container">
                    <button class="btn">Xem ngay</button>
                </div>
            </a>
        </article>

        <article class="product-item hidden">
            <a href="chitietsanpham.php?id=iphone-16-512gb">
                <div class="installments-container">
                    <div class="installments-box">
                        <button class="installments">Trả góp 0%</button>
                    </div>
                </div>
                <div class="product-img">
                    <button><img alt="iPhone 16 512GB"
                            src="https://cdn.tgdd.vn/Products/Images/42/329137/iphone-16-white-600x600.png" /></button>
                </div>
                <h5>iPhone 16</h5>
                <p class="description">6.1" Super Retina XDR Chip Apple A18 6 nhân 512GB</p>
                <div class="price-container-product">
                    <span class="new-price">28.990.000đ</span>
                    <div class="price-row">
                        <span class="old-price"><del>31.990.000đ</del></span>
                        <span class="discount">-9%</span>
                    </div>
                </div>
                <div class="btn-container">
                    <button class="btn">Xem ngay</button>
                </div>
            </a>
        </article>

        <article class="product-item hidden">
            <a href="chitietsanpham.php?id=samsung-galaxy-z-fold6">
                <div class="installments-container">
                    <div class="installments-box">
                        <button class="installments">Trả góp 0%</button>
                    </div>
                </div>
                <div class="product-img">
                    <button><img alt="Samsung Galaxy Z Fold6"
                            src="https://cdn.tgdd.vn/Products/Images/42/333347/samsung-galaxy-s25-ultra-blue-thumbai-600x600.jpg" /></button>
                </div>
                <h5>Samsung Galaxy Z Fold6 5G</h5>
                <p class="description">Chính 7.6" & Phụ 6.3" HQXGA+ 12GB/256GB</p>
                <div class="price-container-product">
                    <span class="new-price">28.990.000đ</span>
                    <div class="price-row">
                        <span class="old-price"><del>33.990.000đ</del></span>
                        <span class="discount">-14%</span>
                    </div>
                </div>
                <div class="btn-container">
                    <button class="btn">Xem ngay</button>
                </div>
            </a>
        </article>

        <article class="product-item hidden">
            <a href="chitietsanpham.php?id=samsung-galaxy-s24-fe">
                <div class="installments-container">
                    <div class="installments-box">
                        <button class="installments">Trả góp 0%</button>
                    </div>
                </div>
                <div class="product-img">
                    <button><img alt="Samsung Galaxy S24 FE"
                            src="https://cdn.tgdd.vn/Products/Images/42/322789/samsung-galaxy-s24-fe-thumb-600x600.jpg" /></button>
                </div>
                <h5>Samsung Galaxy S24 FE 5G</h5>
                <p class="description">6.7" Full HD+ Chip Exynos 2400e 8 nhân 8GB/128GB</p>
                <div class="price-container-product">
                    <span class="new-price">12.890.000đ</span>
                    <div class="price-row">
                        <span class="old-price"><del>16.990.000đ</del></span>
                        <span class="discount">-24%</span>
                    </div>
                </div>
                <div class="btn-container">
                    <button class="btn">Xem ngay</button>
                </div>
            </a>
        </article>

        <article class="product-item hidden">
            <a href="chitietsanpham.php?id=samsung-galaxy-z-flip6-512gb">
                <div class="installments-container">
                    <div class="installments-box">
                        <button class="installments">Trả góp 0%</button>
                    </div>
                </div>
                <div class="product-img">
                    <button><img alt="Samsung Galaxy Z Flip6"
                            src="https://cdn.tgdd.vn/Products/Images/42/333359/samsung-galaxy-s25-plus-blue-thumbai-600x600.jpg" /></button>
                </div>
                <h5>Samsung Galaxy Z Flip6</h5>
                <p class="description">Chính 6.7" & Phụ 3.4" Full HD+ 12GB/512GB</p>
                <div class="price-container-product">
                    <span class="new-price">25.990.000đ</span>
                    <div class="price-row">
                        <span class="old-price"><del>32.990.000đ</del></span>
                        <span class="discount">-21%</span>
                    </div>
                </div>
                <div class="btn-container">
                    <button class="btn">Xem ngay</button>
                </div>
            </a>
        </article>

        <article class="product-item hidden">
            <a href="chitietsanpham.php?id=oppo-reno13-5g">
                <div class="installments-container">
                    <div class="installments-box">
                        <button class="installments">Trả góp 0%</button>
                    </div>
                </div>
                <div class="product-img">
                    <button><img alt="OPPO Reno13 5G"
                            src="https://img.tgdd.vn/imgt/old/f_webp,fit_outside,quality_75/https://cdn.tgdd.vn/Products/Images/42/332934/oppo-reno13-blue-thumbnew-200x200.jpg" /></button>
                </div>
                <h5>OPPO Reno13 5G</h5>
                <p class="description">6.59" 1.5K+ Chip MediaTek Dimensity 9350 5G 8 nhân 256GB</p>
                <div class="price-container-product">
                    <span class="new-price">14.690.000đ</span>
                    <div class="price-row">
                        <span class="old-price"><del>15.990.000đ</del></span>
                        <span class="discount">-8%</span>
                    </div>
                </div>
                <div class="btn-container">
                    <button class="btn">Xem ngay</button>
                </div>
            </a>
        </article>

        <article class="product-item hidden">
            <a href="chitietsanpham.php?id=oppo-find-n3-5g">
                <div class="installments-container">
                    <div class="installments-box">
                        <button class="installments">Trả góp 0%</button>
                    </div>
                </div>
                <div class="product-img">
                    <button><img alt="OPPO Find N3 5G"
                            src="https://cdn.tgdd.vn/Products/Images/42/302953/oppo-find-n3-thumb-600x600.jpg" /></button>
                </div>
                <h5>OPPO Find N3 5G</h5>
                <p class="description">Chính 7.82" & Phụ 6.31" Quad HD+ (2K+) 16GB/512GB</p>
                <div class="price-container-product">
                    <span class="new-price">30.990.000đ</span>
                    <div class="price-row">
                        <span class="old-price"><del>44.990.000đ</del></span>
                        <span class="discount">-31%</span>
                    </div>
                </div>
                <div class="btn-container">
                    <button class="btn">Xem ngay</button>
                </div>
            </a>
        </article>

        <article class="product-item hidden">
            <a href="chitietsanpham.php?id=oppo-find-n5-5g">
                <div class="installments-container">
                    <div class="installments-box">
                        <button class="installments">Trả góp 0%</button>
                    </div>
                </div>
                <div class="product-img">
                    <button><img alt="OPPO Find N5 5G"
                            src="https://cdn.tgdd.vn/Products/Images/42/334938/oppo-find-n5-black-thumb-600x600.jpg" /></button>
                </div>
                <h5>OPPO Find N5 5G</h5>
                <p class="description">Chính 8.12" & Phụ 6.62" QXGA+ 16GB/512GB</p>
                <div class="price-container-product">
                    <span class="new-price">44.490.000đ</span>
                    <div class="price-row">
                        <span class="old-price"><del>44.990.000đ</del></span>
                        <span class="discount">-3%</span>
                    </div>
                </div>
                <div class="btn-container">
                    <button class="btn">Xem ngay</button>
                </div>
            </a>
        </article>

        <article class="product-item hidden">
            <a href="chitietsanpham.php?id=xiaomi-15-ultra-5g">
                <div class="installments-container">
                    <div class="installments-box">
                        <button class="installments">Trả góp 0%</button>
                    </div>
                </div>
                <div class="product-img">
                    <button><img alt="Xiaomi 15 Ultra 5G"
                            src="https://cdn.tgdd.vn/Products/Images/42/334169/xiaomi-15-ultra-black-thumbnew-600x600.jpg" /></button>
                </div>
                <h5>Xiaomi 15 Utra 5G</h5>
                <p class="description">6.76" Quad HD+ (2K+) 16GB/256GB</p>
                <div class="price-container-product">
                    <span class="new-price">32.990.000đ</span>
                    <div class="price-row">
                        <span class="old-price"><del>34.990.000đ</del></span>
                        <span class="discount">-5%</span>
                    </div>
                </div>
                <div class="btn-container">
                    <button class="btn">Xem ngay</button>
                </div>
            </a>
        </article>

        <article class="product-item hidden">
            <a href="chitietsanpham.php?id=oppo-find-n3-flip-5g">
                <div class="installments-container">
                    <div class="installments-box">
                        <button class="installments">Trả góp 0%</button>
                    </div>
                </div>
                <div class="product-img">
                    <button><img alt="OPPO Find N3 Flip 5G"
                            src="https://img.tgdd.vn/imgt/old/f_webp,fit_outside,quality_75/https://cdn.tgdd.vn/Products/Images/42/317981/oppo-find-n3-flip-hong-thumb-200x200.jpg" /></button>
                </div>
                <h5>OPPO Find N3 Flip 5G</h5>
                <p class="description">Chính 6.8" & Phụ 3.26 12GB/256GB</p>
                <div class="price-container-product">
                    <span class="new-price">16.490.000đ</span>
                    <div class="price-row">
                        <span class="old-price"><del>22.990.000đ</del></span>
                        <span class="discount">-28%</span>
                    </div>
                </div>
                <div class="btn-container">
                    <button class="btn">Xem ngay</button>
                </div>
            </a>
        </article>
    </div>

    <!-- <div class="view-more-wrapper">
        <button class="view-more-btn" id="viewMoreBtn">
            Xem thêm 12 điện thoại
            <i class="fa-solid fa-caret-down fa-lg"></i>
        </button>

        <button class="view-more-btn" id="hideMoreBtn" style="display:none">
            Ẩn bớt
            <i class="fa-solid fa-caret-up fa-lg"></i>
        </button>
    </div> -->


    <footer class="footer">
        <div class="footer-container">
            <div class="footer-section">
                <h6>Tổng đài hỗ trợ</h6>
                <ul>
                    <li><a class="phone" href="#">Hotline: 1900.8008</a></li>
                    <li><a class="phone" href="#">CSKH: 1800.2336</a></li>
                    <li><a class="phone" href="#">Khiếu nại: 1800.7003</a></li>
                    <li><a class="phone" href="#">Bảo hành: 1900 255 526</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h6>Thông tin HEARTTIE</h6>
                <ul>
                    <li><a href="gioithieu.html">Giới thiệu HEARTTIE</a></li>
                    <li><a href="dieukiengiaodichchung.html">Điều kiện giao dịch chung</a></li>
                    <li><a href="khuyenmai&uudai.html">Khuyến mãi & ưu đãi</a></li>
                    <li><a href="gopy&khieunai.html">Góp ý & khiếu nại</a></li>

                </ul>
            </div>
            <div class="footer-section">
                <h6>Chính sách chung</h6>
                <ul>
                    <li><a href="chinhsachvanchuyen.html">Chính sách vận chuyển</a></li>
                    <li><a href="chinhsachbaohanh&doitra.html">Chính sách bảo hành & đổi trả</a></li>
                    <li><a href="chinhsachbaomat.html">Chính sách bảo mật</a></li>
                    <li><a href="chinhsachthanhtoan.html">Chính sách thanh toán</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h6>Email liên hệ</h6>
                <ul>
                    <li><a href="#">Hỗ trợ khách hàng</a></li>
                    <li>
                        <a href="mailto:hotro@hearttie.com" style="color: #0071e3; text-decoration: none;"
                            onmouseover="this.style.textDecoration='underline';"
                            onmouseout="this.style.textDecoration='none';">
                            hotro@hearttie.com
                        </a>
                    </li>
                    <li><a href="#">Tư vấn sản phẩm</a></li>
                    <li>
                        <a href="mailto:tuvan@hearttie.com" style="color: #0071e3; text-decoration: none;"
                            onmouseover="this.style.textDecoration='underline';"
                            onmouseout="this.style.textDecoration='none';">
                            tuvan@hearttie.com
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© 2020 Công ty cổ phần HEARTTIE. GPDKKD: 0303217354 do sở Cần Thơ cấp ngày 02/01/2015. GPMXH:
                21/GP-BTTTT do Bộ Thông Tin và Truyền Thông cấp ngày 11/01/2021. Địa chỉ: 128, Nguyễn Văn Cừ (nối dài),
                Phường An Bình, Quận Ninh Kiều, Thành Phố Cần Thơ.
                <br />
                Chịu trách nhiệm nội dung: Tiêu Ngọc Thanh Tâm. Tất cả
                các quyền được bảo
                lưu.
            </p>
        </div>
    </footer>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        ul {
            list-style: none;
        }

        a {
            text-decoration: none;
        }

        /*---------------nav-------------*/
        html,
        body {
            margin: 0;
            width: 100%;
            overflow-x: hidden;
            font-family: Arial, sans-serif;
        }

        nav {
            width: 100%;
            background-color: #afdbf3;
            padding: 10px 20px;
            justify-content: space-between;
        }

        nav img {
            background-color: transparent !important;
            /* Buộc nền ảnh phải trong suốt */
            border: none;
            display: block;
        }

        .search-container {
            position: relative;
            display: flex;
            align-items: baseline;
            /* Vertically center the items */
            justify-content: space-around;
            /* Space between items */
        }

        ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }

        li {
            margin: 0 35px;
            /* Space between items */
        }

        img {
            height: 50px;
            /* Adjust this to fit your logo size */
        }

        input[type="text"] {
            height: 35px;
            /* Adjust height to match logo */
            border: 1.5px solid #87bfff;
            /* Border color */
            border-radius: 20px;
            /* Rounded corners */
            padding: 0 30px 0 10px;
            padding-right: 10px;
            width: 400px;
            box-sizing: border-box;
        }

        input[type="text"]+i {
            position: absolute;
            border-left-width: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #212121;
            left: 625px;
            cursor: pointer;
        }

        input[type="text"]:hover,
        input[type="text"]:focus {
            background-color: #eff5ff;
            outline: #4a9fff solid 1.5px;
        }

        button {
            align-items: center;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }

        .fa-lg {
            margin-right: 10px;
        }

        .search-container button:hover {
            color: #4a92e4;
        }

        /*-----------home--------*/
        .home {
            padding: 10px;
            /* Space for the home section */
            background-color: #d9f2ff;
            /* Light background color */
        }

        .home-container {
            display: flex;
            /* Sắp xếp theo hàng */
            justify-content: center;
            /* Căn giữa các mục */
        }

        .home-container ul {
            display: flex;
            justify-content: center;
            padding: 0;
            /* Align items to the right */
        }

        .home-container li {
            margin: 0 80px;
            display: flex;
            align-items: center
                /* Space between home items */
        }

        .home-container a {
            text-decoration: none;
            /* Remove underline */
            color: #000000;
            /* Text color */
            font-size: 16px;
            display: flex;
            align-items: center;
        }

        .home-container a:hover {
            color: #4a92e4;
        }

        .modal-container {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #fff;
            border-radius: 12px;
            padding: 25px 30px;
            width: 90%;
            max-width: 350px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            animation: slideIn 0.3s ease-out;
            font-family: Arial, sans-serif;
        }

        .modal-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .modal-header i {
            color: rgb(193, 0, 0);
            font-size: 24px;
        }

        .modal-header h3 {
    
            font-size: 20px;
            margin: 0;
            color: #333;
        }

        .modal-body p {
            font-size: 16px;
            color: #555;
            margin-bottom: 20px;
        }

        .modal-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .modal-btn {
            padding: 10px 18px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            cursor: pointer;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-confirm {
            background-color: rgb(0, 115, 164);
            color: white;
        }

        .btn-confirm:hover {
            background-color: rgb(0, 89, 144);
        }

        .btn-cancel {
            background-color: rgb(178, 0, 0);
            color: white;
        }

        .btn-cancel:hover {
            background-color: rgb(194, 0, 0);
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translate(-50%, -60%);
            }

            to {
                opacity: 1;
                transform: translate(-50%, -50%);
            }
        }

        /*--------img--------*/
        .img-container {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }

        .img-container img {
            width: 90%;
            max-width: 1600px;
            height: 600px;          
            object-fit: cover;
            border-radius: 20px;
        }

        /* Wrapper để căn giữa */
        .banner-wrapper {
            display: flex;
            justify-content: center;
            margin: 40px 0;
        }

        /* Banner */
        .banner {
            position: relative;
            display: inline-block;
        }

        /* Phóng to ảnh nhưng vẫn giữ tỉ lệ */
        .banner img {
            width: 100%;
            max-width: 1500px;
            height: 600px;          
            object-fit: cover;
            border-radius: 20px;
        }

        /* Nút X */
        .close-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 32px;
            height: 32px;
            background: #f2f2f2;
            color: #666;
            font-size: 20px;
            line-height: 32px;
            text-align: center;
            border-radius: 50%;
            cursor: pointer;
            z-index: 5;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.25);
        }

        .close-btn:hover {
            background: #e0e0e0;
        }

        /*------------------------flashsale------------------------*/
        .flashsale {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 1260px;
            height: auto;
            padding: 20px;
            background-color: #f8efdf;
            border-radius: 20px;
            margin: 20px auto;
            margin-top: 100px;
            position: relative;
        }

        .flashsale h2 {
            color: #ffab4b;
            font-weight: bold;
            font-size: 32px;
            padding: 15px 25px;
            text-align: left;
            margin: 0;
            position: absolute;
            top: -20%;
            left: 2px;
            background-color: #f8efdf;
            border-radius: 35px;
        }

        .flashsale-container {
            display: flex;
            justify-content: space-between;
            border-radius: 20px;
            align-items: stretch;
            gap: 20px;
            margin-top: 10px auto;
            width: 100%;
            height: 400px;
        }

        .flashsale-item {
            width: calc(25% - 10px);
            background-color: white;
            border-radius: 20px;
            padding: 20px;
            text-align: center;
            display: flex;
            flex-direction: column;
            transition: transform 0.2s;
        }

        .flashsale-item:hover {
            transform: scale(1.02);
        }

        .flashsale-link {
            display: block;
            color: inherit;
            text-decoration: none;
            cursor: pointer;
        }

        .flashsale-img {
            margin-top: 10px;
        }

        .flashsale-img img {
            width: 136px;
            height: 136px;
            transition: none;
        }

        .price-container {
            margin-bottom: 10px;
            text-align: center;
        }

        .price {
            font-size: 16px;
            /* Kích thước chữ cho giá */
            color: #d31515;
            /* Màu đỏ cho giá */
            font-weight: bold;
            /* Đậm */
            margin: 10px 0;
            /* Khoảng cách trên và dưới */
        }

        .description {
            font-size: 16px;
            color: #000;
            /* Màu chữ mô tả */
        }

        .btn-container {
            display: flex;
            /* Sử dụng flexbox cho nút */
            justify-content: center;
            /* Căn giữa các nút */
            margin-top: 10px;
            /* Khoảng cách trên nút */
        }

        .btn {
            background-color: #dfecf8;
            /* Màu nền cho nút */
            color: #4a92e4;
            /* Màu chữ cho nút */
            padding: 10px 20px;
            /* Khoảng cách bên trong */
            border-radius: 20px;
            /* Bo góc cho nút */
            cursor: pointer;
            /* Thay đổi con trỏ khi hover */
            margin: 0 5px;
            transition: background-color 0.3s;
            /* Hiệu ứng chuyển màu */
        }

        .btn:hover {
            background-color: #bbd7ff;
            /* Màu tối hơn khi hover */
        }

        /*---------------------suggestion----------------------*/
        .suggestion {
            display: flex;
            flex-direction: column;
            /* Sắp xếp theo chiều dọc */
            align-items: center;
            /* Căn giữa tất cả các phần tử */
            width: 1260px;
            /* Chiều rộng khung lớn */
            height: auto;
            /* Tự động điều chỉnh chiều cao */
            padding: 20px;
            background-color: #DFECF8;
            border-radius: 20px;
            margin: 20px auto;
            /* Tạo khoảng cách trên và dưới, và căn giữa theo chiều ngang */
            position: relative;
        }

        .suggestion h3 {
            color: #4A92E4;
            font-weight: bold;
            font-size: 32px;
            padding: 10px;
            text-align: left;
            margin: 0;
            position: absolute;
            top: -20%;
            left: -2px;
            background-color: #DFECF8;
            padding-right: 10px;
            border-radius: 20px;
        }

        .suggestion-container {
            display: flex;
            justify-content: space-between;
            align-items: stretch;
            gap: 20px;
            margin-top: 40px auto;
            width: 100%;
            height: 400px;
        }

        .suggestion-item {
            width: calc(25% - 10px);
            /* Chiều rộng mỗi sản phẩm */
            background-color: white;
            border-radius: 20px;
            /* Bo góc */
            padding: 20px;
            /* Khoảng cách bên trong */
            text-align: center;
            display: flex;
            flex-direction: column;
            transition: transform 0.2s;
        }

        .suggestion-item h4 {
            font-size: 18px;
        }

        .suggestion-item:hover {
            transform: scale(1.02);
        }

        .suggestion-img {
            margin-top: 20px;
        }

        .suggestion-img img {
            width: 136px;
            height: 136px;
            transition: none;
        }

        .price-container {
            margin-bottom: 10px;
            text-align: center;
        }

        .price {
            font-size: 16px;
            /* Kích thước chữ cho giá */
            color: #d31515;
            /* Màu đỏ cho giá */
            font-weight: bold;
            /* Đậm */
            margin: 5px 0;
            /* Khoảng cách trên và dưới */
        }

        .description {
            font-size: 16px;
            color: #000;
            /* Màu chữ mô tả */
        }

        .btn-container {
            display: flex;
            /* Sử dụng flexbox cho nút */
            justify-content: center;
            /* Căn giữa các nút */
            margin-top: 10px;
            /* Khoảng cách trên nút */
        }

        .btn {
            background-color: #dfecf8;
            /* Màu nền cho nút */
            color: #4a92e4;
            /* Màu chữ cho nút */
            padding: 10px 20px;
            /* Khoảng cách bên trong */
            border-radius: 20px;
            /* Bo góc cho nút */
            cursor: pointer;
            /* Thay đổi con trỏ khi hover */
            margin: 0 5px;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: #bbd7ff;
        }

        /*-------------------------filter--------------------*/
        .filter {
            border-radius: 20px;
            border: 1.5px solid #4A92E4;
            margin-right: 10px;
        }

        .filter-button {
            background-color: transparent;
            /* Nền trong suốt cho nút */
            color: #4a92e4;
            /* Màu chữ xanh */
            padding: 10px 15px;
            /* Khoảng cách bên trong nút */
            border-radius: 20px;
            /* Bo tròn góc cho nút */
            cursor: pointer;
            /* Con trỏ chuột khi hover */
            transition: background-color 0.3s, color 0.3s;
            /* Hiệu ứng chuyển màu */
            margin-right: 0;
        }

        .filter-container {
            display: flex;
            align-items: center;
            margin: 20px auto 10px auto;
            padding: 0;
            max-width: 1400px; /* Căn với khung sản phẩm */
        }

        .filter-options {
            display: flex;
            flex-wrap: wrap;
            padding: 0;
            margin: 0;
            gap: 10px;
        }

        .filter-options button {
            padding: 10px 25px;
            background-color: #e3f2fd;
            border: 2px solid transparent;
            border-radius: 25px;
            color: #1976d2;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-options button:hover {
            background-color: #bbdefb;
            border-color: #1976d2;
        }

        .filter-options button.active {
            background-color: #1976d2;
            color: white;
            font-weight: 700;
            border-color: #1976d2;
            box-shadow: 0 4px 12px rgba(25, 118, 210, 0.4);
            transform: translateY(-2px);
        }

        .filter-button i {
            margin-right: 5px;
        }

        /*---------------product------------------*/
        .product {
            display: block;
            margin-bottom: 20px;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            border-radius: 20px;
            margin: 20px auto;
        }

        .product h4 {
            color: #4a92e4;
            font-weight: bold;
            font-size: 32px;
            padding: 10px 20px;
            text-align: left;
            margin: 0 auto 20px auto;
            background-color: #DFECF8;
            border-radius: 20px;
            display: inline-block;
            max-width: 1400px; /* Căn với khung sản phẩm */
        }

        .product .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .product-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr); /* 4 sản phẩm trên 1 dòng */
            gap: 20px;
            margin: 20px auto;
            padding: 20px;
            width: 100%;
            max-width: 1400px; /* Tăng width để chứa 4 sản phẩm */
            background-color: #DFECF8;
            border-radius: 20px;
            max-height: 860px;
            overflow: hidden;
            transition: max-height 0.5s ease;
            opacity: 0;
            animation: fadeIn 0.5s ease-in-out forwards;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .product-card {
            background: white;
            border-radius: 15px;
            padding: 15px;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
            position: relative;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(74, 146, 228, 0.3);
        }

        .product-card img {
            width: 100%;
            height: 200px;
            object-fit: contain;
            margin-bottom: 10px;
        }

        .product-card h3 {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin: 10px 0;
            min-height: 40px;
            display: -webkit-box;
            /* -webkit-line-clamp: 2; */
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-card .description {
            font-size: 13px;
            color: #666;
            margin: 8px 0;
            min-height: 36px;
            display: -webkit-box;
            /* -webkit-line-clamp: 2; */
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-card .price {
            margin: 10px 0;
        }

        .product-card .current-price {
            font-size: 18px;
            font-weight: bold;
            color: #D31515;
        }

        .product-card .price-row {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 5px;
        }

        .product-card .original-price {
            font-size: 14px;
            color: #999;
        }

        .product-card .discount {
            font-size: 14px;
            font-weight: bold;
            color: #D31515;
        }

        .product-card .installment-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: #ff6b6b;
            color: white;
            padding: 5px 10px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: bold;
            border-radius: 20px;
    }

        .product-container:not(.expanded) .product-item:nth-child(n+9) {
            display: none;
        }

        .product-item {
            background-color: white;
            border-radius: 20px;
            padding: 20px;
            margin: 0;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 400px;
            transition: transform 0.2s;
            font-size: 16px;
        }

        .product-item a {
            display: block;
            width: 100%;
            height: 100%;
            text-decoration: none;
            color: inherit;
        }

        .product-item h5 {
            font-size: 18px;
        }

        .product-item:hover {
            transform: scale(1.02);
        }

        .product-img {
            margin-top: 20px;
        }

        .product-img img {
            width: 136px;
            height: 136px;
            transition: none;
        }

        .price-container {
            margin-bottom: 10px;
            text-align: center;
        }

        .price {
            font-size: 16px;
            color: #d31515;
            font-weight: bold;
            margin: 10px 0;
        }

        .description {
            font-size: 16px;
            color: #000;
        }

        .btn-container {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            margin-bottom: 10px;
        }

        .btn {
            background-color: #dfecf8;
            color: #4a92e4;
            font-weight: bold;
            padding: 10px 20px;
            border-radius: 20px;
            cursor: pointer;
            margin: 0 5px;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: #cce5ff;
        }

        /*----------------khoangcach-------------*/
        .flashsale,
        .product,
        .suggestion {
            margin-top: 120px;
        }

        /*--------------------price----------------------*/
        .price-container {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .price-container-flashsale,
        .price-container-suggestion,
        .price-container-product {
            margin-top: 30px;
        }

        .new-price {
            color: #D31515;
            font-weight: bold;
            font-size: 20px;
        }

        .old-price {
            color: #000;
            text-decoration: line-through;
        }

        .price-row {
            display: flex;
            justify-content: center;
            margin-top: 5px;
        }

        .discount {
            color: #D31515;
            font-weight: bold;
        }

        .old-price,
        .discount {
            margin: 0 5px;
        }

        .description {
            text-align: center;
            /* Căn giữa nội dung */
            margin: 10px 0;
            /* Khoảng cách trên và dưới */
        }

        .flashsale-item h3 {
            color: #000;
            /* Màu đen */
        }

        .suggestion-item h4 {
            font-size: 18px;
            color: #000;
            /* Màu đen */
        }

        .product-item h5 {
            font-size: 18px;
            color: #000;
            /* Màu đen */
        }

        /*--------------------khungtragop-------------------*/
        .installments-container {
            position: relative;
        }

        .installments-box {
            padding: 5px 10px;
            background-color: #dfdfdf;
            position: absolute;
            top: -10px;
            left: -10px;
            border-radius: 10px;
            z-index: 1;
            display: block;
        }

        .installments {
            font-size: 12px;
            border: none;
            color: #000;
            cursor: pointer;
        }

        /*--------------------view-more-container---------------------*/
        .view-more-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin: 30px auto;
            height: 40px;
            background-color: white;
            font-family: Arial, sans-serif;
            color: #4A92E4;
            padding: 10px 20px;
            border-radius: 20px;
            border: solid 1.5px #4A92E4;
            text-decoration: none;
            font-size: 16px;
            transition: background-color 0.3s;
        }

        .view-more-btn i {
            font-size: 25px;
            vertical-align: middle;
            margin-top: 1px;
            margin-right: 2px;
        }

        .view-more-btn:hover {
            background-color: #f4fbff;
        }

        .view-more-wrapper {
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        /*-----------------footer-----------------*/
        .footer {
            margin-top: 45px;
        }

        .footer-container {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            width: 1450px;
            border: 1px solid rgb(196, 196, 196);
            margin: 50px auto;
            align-items: flex-start;
            background-color: rgb(248, 248, 248);
        }

        .footer-section {
            flex: 1;
            margin: 0 10px;
            min-width: 200px;
            max-width: 300px;
            text-align: center;
        }

        .footer-section h6 {
            font-size: 18px;
            margin: 20px auto 10px;
        }

        .footer-section ul {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 0;
            margin: 0;
        }

        .footer-section ul li {
            margin-bottom: 10px;
            text-align: center;
        }

        .footer-section ul li a {
            text-decoration: none;
            color: #000;
            display: block;
            width: 100%;
        }

        .footer-section ul li a.email {
            color: #4a92e4;
        }

        .footer-section ul li a.phone {
            color: #D31515;
            font-weight: bold;
        }

        .footer-section a:hover {
            color: #4a92e4;
        }

        .footer-bottom {
            text-align: center;
            align-items: baseline;
            font-size: 12px;
            padding: 10px 0;
            background-color: #d9d9d9;
        }

        .footer-bottom p {
            margin: 20px 0;
        }

        /* Overlay nền mờ */
        #overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.4);
            z-index: 999;
        }

        /* Popup danh mục */
        #categoryPanel {
            display: none;
            position: fixed;
            top: 70px;
            right: 0;
            width: 600px;
            height: 600px;
            border-radius: 5px;
            background-color: rgb(234, 234, 234);
            box-shadow: -3px 0 10px rgba(0, 0, 0, 0.2);
            padding: 0;
            overflow-y: auto;
            z-index: 1000;
        }

        .category-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
            height: 50px;
            background-color: #4a92e4;
            color: white;
            border-top-left-radius: 5px;
            border-top-right-radius: 5px;
        }

        .category-header h3 {
            margin-left: 23px;
            color: black;
            font-size: 20px;
        }

        .close-btn {
            font-size: 24px;
            cursor: pointer;
            color: black;
            transition: color 0.2s ease;
        }

        .close-btn:hover {
            color: #c9302c;
        }

        .category-list {
            list-style: none;
            width: 600px;
            padding: 0 10px;
            margin: 20px 0 0 0;
            display: flex;
            flex-direction: column;
            gap: 20px;
            animation: slideDown 0.5s ease-in-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .category-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            padding: 10px 15px;
            background-color: rgb(255, 255, 255);
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(142, 188, 237, 0.1);
            transition: background 0.3s ease;
        }

        .category-img {
            width: 90px;
            height: 90px;
            object-fit: contain;
            margin-right: 20px;
        }

        .product-name {
            font-weight: bold;
            color: #4A92E4;
            text-decoration: none;
            flex-shrink: 0;
            width: 120px;
            font-size: 16px;
        }

        .product-name:hover {
            text-decoration: underline;
        }

        .product-desc {
            text-align: justify;
            color: #333;
            flex: 1;
        }

        .chat-button {
            position: fixed;
            bottom: 70px;
            right: 20px;
            width: 60px;
            height: 60px;
            color: #74b5e8;
            font-size: 70px;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            box-shadow: 0 0 30px rgba(93, 176, 239, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 50px;
            animation: blink-shadow 1s infinite;
        }

        @keyframes blink-shadow {

            0%,
            100% {
                box-shadow: 0 0 10px rgba(48, 165, 255, 0.4);
            }

            50% {
                box-shadow: 0 0 20px rgba(48, 165, 255, 1);
            }
        }

        .chat-container {
            display: none;
            /* Ẩn khung chat */
            width: 500px;
            height: 600px;
            position: fixed;
            top: 20px;
            bottom: 20px;
            right: 20px;
            border: 1px solid #fff;
            border-radius: 10px;
            background-color: whitesmoke;
            box-shadow: 0 0 10px rgba(86, 199, 255, 0.2);
            margin-bottom: 50px;
        }

        .chat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 15px;
            height: 50px;
            background-color: #74C0FC;
            color: white;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }

        .close-btn {
            font-weight: bold;
            cursor: pointer;
            color: #00000055;
            margin-left: auto;
        }

        .close-btn i {
            font-size: 20px;
            color: black;
        }

        .close-btn:hover i {
            color: rgb(255, 54, 54);
        }

        .chat-box {
            height: 500px;
            overflow-y: auto;
            padding: 10px;
            border-bottom: 2px solid #4a92e4;
        }

        .input-area {
            display: flex;
            padding: 10px;
        }

        .input-area input {
            flex: 1;
            padding: 8px;
            border: 1px solid #8ec3ff;
            border-radius: 10px;
            margin-right: 10px;
            font-size: 16px;
            height: 40px;
            outline: none;
            color: black;
        }

        .input-area button {
            display: flex;
            padding: 0;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-weight: bold;
            background-color: transparent;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            outline: none;
            height: 45px;
            width: 45px;
        }

        .input-area button i {
            align-items: center;
            justify-items: center;
            color: #4A92E4;
            font-size: 25px;
        }

        .message {
            display: flex;
            margin: 20px 0;
        }

        .message.user {
            justify-content: flex-end;
        }

        .message.agent {
            justify-content: flex-start;
        }

        .message span {
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 16px;
            max-width: 70%;
            word-break: break-word;
            text-align: justify;
            line-height: 1.5;
        }

        .message.user span {
            background-color: #e4f1ff;
            color: black;
            border-bottom-right-radius: 0;
        }

        .message.agent span {
            background-color: #AFDBF3;
            color: black;
            border-bottom-left-radius: 0;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    <script>
        const btnCategory = document.getElementById('btnCategory');
        const categoryPanel = document.getElementById('categoryPanel');
        const closeBtn = document.getElementById('closeCategory');
        const overlay = document.getElementById('overlay');

        function openCategory() {
            categoryPanel.style.display = 'block';
            overlay.style.display = 'block';
        }

        function closeCategory() {
            categoryPanel.style.display = 'none';
            overlay.style.display = 'none';
        }

        btnCategory.addEventListener('click', openCategory);
        closeBtn.addEventListener('click', closeCategory);
        overlay.addEventListener('click', closeCategory);

        function showLogoutModal() {
            const modal = document.getElementById('logoutModal');

            if (modal) {
                // Hiển thị modal với animation
                modal.style.display = 'flex';
                modal.classList.add('show');

                // Ngăn scroll body khi modal mở
                document.body.style.overflow = 'hidden';

                // Focus vào nút đầu tiên để accessibility
                const firstButton = modal.querySelector('.modal-btn');
                if (firstButton) {
                    setTimeout(() => firstButton.focus(), 100);
                }

                console.log('Modal đăng xuất đã được hiển thị');
            } else {
                console.error('Không tìm thấy modal với ID: logoutModal');
            }
        }

        // 2. Ẩn modal đăng xuất
        function showLogoutModal() {
            const modal = document.getElementById('logoutModal');
            modal.style.display = 'flex';
        }

        function hideLogoutModal() {
            const modal = document.getElementById('logoutModal');
            modal.style.display = 'none';
        }

        // 3. Đóng modal khi click bên ngoài (overlay)
        function handleOutsideClick(event) {
            const modal = document.getElementById('logoutModal');

            // Kiểm tra xem click có phải trên overlay không
            if (event.target === modal) {
                hideLogoutModal();
                console.log('Modal đã đóng do click bên ngoài');
            }
        }

        function toggleChat() {
            const chatBox = document.getElementById('chat-box');
            chatBox.style.display = (chatBox.style.display === 'flex') ? 'none' : 'flex';
        }
        //Function Chatbot
        function sendMessage() {
            const input    = document.getElementById('chat-input');
            const message  = input.value.trim();
            if (!message) return;

            const messages = document.getElementById('chat-messages');

            const userMsg = document.createElement('div');
            userMsg.style.cssText = 'text-align:right;margin-bottom:6px;';
            userMsg.textContent   = '🙋 ' + message;
            messages.appendChild(userMsg);
            input.value = '';

            const typing = document.createElement('div');
            typing.id           = 'cb-typing';
            typing.style.cssText = 'margin-bottom:10px;color:#888;font-style:italic;';
            typing.textContent   = '🤖 Đang nhập...';
            messages.appendChild(typing);
            messages.scrollTop = messages.scrollHeight;

            fetch('chatbot_api.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ message: message })
            })
            .then(r => r.json())
            .then(data => {
                document.getElementById('cb-typing')?.remove();
                const botMsg = document.createElement('div');
                botMsg.style.cssText = 'margin-bottom:10px;line-height:1.55;';
                botMsg.innerHTML     = '🤖 ' + (data.reply || 'Xin lỗi, có lỗi xảy ra!');
                messages.appendChild(botMsg);
                messages.scrollTop = messages.scrollHeight;
            })
            .catch(() => {
                document.getElementById('cb-typing')?.remove();
                const err = document.createElement('div');
                err.style.cssText = 'margin-bottom:10px;color:#e74c3c;';
                err.textContent   = '🤖 Lỗi kết nối, vui lòng thử lại!';
                messages.appendChild(err);
                messages.scrollTop = messages.scrollHeight;
            });
        }
        //Banner Quảng Cáo
        function closeBanner() {
            document.getElementById("shopeeBanner").style.display = "none";
        }

        document.addEventListener('DOMContentLoaded', function () {
            const container = document.querySelector('.product-container');
            const viewBtn = document.getElementById('viewMoreBtn');
            const hideBtn = document.getElementById('hideMoreBtn');

            viewBtn.addEventListener('click', function () {
                container.classList.add('expanded');
                viewBtn.style.display = 'none';
                hideBtn.style.display = 'flex';
            });

            hideBtn.addEventListener('click', function () {
                container.classList.remove('expanded');
                hideBtn.style.display = 'none';
                viewBtn.style.display = 'flex';
                container.scrollIntoView({ behavior: 'smooth' });
            });
        });

        // Xử lý click từ menu danh mục - CẢI TIẾN
        document.addEventListener('DOMContentLoaded', function () {
            const filterLinks = document.querySelectorAll('.filter-link');

            filterLinks.forEach(link => {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    const brand = this.getAttribute('data-brand');

                    // Đóng menu danh mục
                    closeCategory();

                    // Hiển thị danh sách sản phẩm
                    const productList = document.getElementById('productList');
                    if (productList) {
                        productList.style.display = 'grid';
                    }

                    // Chờ animation đóng menu xong rồi mới lọc
                    setTimeout(() => {
                        filterProducts(brand);
                    }, 300);
                });
            });
        });
        
        // Hàm lọc sản phẩm theo hãng - CẢI TIẾN
        function filterProducts(brand) {
            const productCards = document.querySelectorAll('.product-card');
            const filterButtons = document.querySelectorAll('.filter-options .filter-button');
            const productList = document.getElementById('productList');

            // Hiển thị danh sách sản phẩm nếu chưa hiển thị
            if (productList && productList.style.display === 'none') {
                productList.style.display = 'grid';
            }

            // Cập nhật trạng thái nút active
            filterButtons.forEach(btn => {
                btn.classList.remove('active');
                // So sánh nội dung text của button với brand
                const btnText = btn.textContent.toLowerCase().trim();
                if (btnText === brand.toLowerCase()) {
                    btn.classList.add('active');
                }
            });

            // Lọc và hiển thị sản phẩm
            let visibleCount = 0;
            productCards.forEach(card => {
                const productBrand = card.getAttribute('data-brand');

                if (productBrand === brand.toLowerCase()) {
                    card.style.display = 'block';
                    card.classList.remove('hidden');
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                    card.classList.add('hidden');
                }
            });

            // Cuộn mượt đến phần sản phẩm
            const productSection = document.querySelector('.product');
            if (productSection) {
                setTimeout(() => {
                    productSection.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }, 100);
            }

            // Hiển thị thông báo nếu không có sản phẩm
            const container = document.querySelector('.product-container');
            let noResultMsg = container.querySelector('.no-results-message');

            if (visibleCount === 0) {
                if (!noResultMsg) {
                    noResultMsg = document.createElement('div');
                    noResultMsg.className = 'no-results-message';
                    noResultMsg.style.cssText = 'text-align: center; padding: 40px; font-size: 18px; color: #666; grid-column: 1 / -1;';
                    noResultMsg.innerHTML = '<i class="fa-solid fa-box-open" style="font-size: 48px; margin-bottom: 20px; display: block;"></i>Không tìm thấy sản phẩm ' + brand;
                    container.appendChild(noResultMsg);
                }
                noResultMsg.style.display = 'block';
            } else if (noResultMsg) {
                noResultMsg.style.display = 'none';
            }
        }

        // ===== COUNTDOWN TIMER - ĐẾM NGƯỢC THỰC TẾ =====
        function initCountdown() {
            // Đặt thời gian kết thúc - ví dụ: kết thúc sau 6 giờ từ bây giờ
            const endTime = new Date().getTime() + (23 * 60 * 60 * 1000); // 6 giờ
            
            function updateCountdown() {
                const now = new Date().getTime();
                const timeLeft = endTime - now;
                
                if (timeLeft > 0) {
                    const hours = Math.floor(timeLeft / (1000 * 60 * 60));
                    const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
                    
                    document.getElementById('hours').textContent = String(hours).padStart(2, '0');
                    document.getElementById('minutes').textContent = String(minutes).padStart(2, '0');
                    document.getElementById('seconds').textContent = String(seconds).padStart(2, '0');
                } else {
                    document.getElementById('hours').textContent = '00';
                    document.getElementById('minutes').textContent = '00';
                    document.getElementById('seconds').textContent = '00';
                    clearInterval(countdownInterval);
                    document.querySelector('.countdown-header').innerHTML = '<i class="fa-solid fa-check-circle"></i> FLASH SALE ĐÃ KẾT THÚC <i class="fa-solid fa-check-circle"></i>';
                }
            }
            
            updateCountdown();
            const countdownInterval = setInterval(updateCountdown, 1000);
        }

        // ===== HOA RƠI MÀU XANH - TỰ ĐỘNG DỪNG SAU 30S =====
        let petalInterval;
        let petalStartTime;
        
        function createPetal() {
            const bluePetals = ['🌸'];
            const petal = document.createElement('div');
            petal.className = 'petal';
            petal.textContent = bluePetals[Math.floor(Math.random() * bluePetals.length)];
            petal.style.left = Math.random() * window.innerWidth + 'px';
            petal.style.animationDuration = (Math.random() * 3 + 3) + 's'; // 3-6 giây
            petal.style.opacity = Math.random() * 0.4 + 0.3; // 0.3-0.7
            
            const container = document.getElementById('petals-container');
            if (container) {
                container.appendChild(petal);
                setTimeout(() => petal.remove(), 6000);
            }
        }

        function startPetals() {
            petalStartTime = Date.now();
            
            // Tạo hoa rơi mỗi 400ms
            petalInterval = setInterval(() => {
                const elapsed = Date.now() - petalStartTime;
                
                // Dừng sau 30 giây
                if (elapsed >= 30000) {
                    clearInterval(petalInterval);
                    console.log('Hiệu ứng hoa rơi đã dừng sau 30 giây');
                } else {
                    createPetal();
                }
            }, 400);
        }

        // ===== MOBILE NAV ACTIVE STATE =====
        function initMobileNav() {
            const navItems = document.querySelectorAll('.mobile-nav-item');
            
            // Click handler
            navItems.forEach(item => {
                item.addEventListener('click', function() {
                    navItems.forEach(nav => nav.classList.remove('active'));
                    this.classList.add('active');
                });
            });

            // Scroll handler
            const sections = document.querySelectorAll('section[id]');
            
            window.addEventListener('scroll', () => {
                let current = 'home';
                
                sections.forEach(section => {
                    const sectionTop = section.offsetTop;
                    const sectionHeight = section.clientHeight;
                    
                    if (window.pageYOffset >= sectionTop - 250) {
                        current = section.getAttribute('id');
                    }
                });

                navItems.forEach(item => {
                    item.classList.remove('active');
                    const link = item.querySelector('a');
                    if (link && link.getAttribute('href') === '#' + current) {
                        item.classList.add('active');
                    }
                });
            });
        }

        // ===== KHỞI ĐỘNG KHI TRANG LOAD =====
        window.addEventListener('load', function() {
            initCountdown();
            initMobileNav();
            startPetals(); // Bắt đầu hiệu ứng hoa rơi (tự dừng sau 30s)
        });
    </script>

    <!-- Mobile Navigation Bar -->
    <div class="mobile-nav">
        <div class="mobile-nav-grid">
            <div class="mobile-nav-item active">
                <a href="#home">
                    <i class="fa-solid fa-house mobile-nav-icon"></i>
                    <span class="mobile-nav-label">Trang chủ</span>
                </a>
            </div>
            <div class="mobile-nav-item">
                <a href="#flashsale">
                    <i class="fa-solid fa-bolt mobile-nav-icon"></i>
                    <span class="mobile-nav-label">Flash Sale</span>
                </a>
            </div>
            <div class="mobile-nav-item">
                <a href="#goi-y">
                    <i class="fa-solid fa-star mobile-nav-icon"></i>
                    <span class="mobile-nav-label">Gợi ý</span>
                </a>
            </div>
            <div class="mobile-nav-item">
                <a href="#san-pham">
                    <i class="fa-solid fa-mobile-screen-button mobile-nav-icon"></i>
                    <span class="mobile-nav-label">Sản phẩm</span>
                </a>
            </div>
            <div class="mobile-nav-item">
                <a href="javascript:void(0)" onclick="openCategory()">
                    <i class="fa-solid fa-bars mobile-nav-icon"></i>
                    <span class="mobile-nav-label">Menu</span>
                </a>
            </div>
        </div>
    </div>
</body>

</html>