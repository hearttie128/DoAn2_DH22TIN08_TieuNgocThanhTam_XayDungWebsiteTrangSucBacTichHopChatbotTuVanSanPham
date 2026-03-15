<?php
session_start();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tư vấn</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
        }

        header {
            background-color: #4a92e4;
            color: white;
            padding: 10px;
            text-align: center;
        }

        nav ul {
            list-style-type: none;
            padding: 0;
            transition: color 0.3s;
        }

        nav ul li {
            font-size: 18px;
            display: inline;
            margin: 0 10px;
        }

        nav ul li a {
            color: rgb(255, 255, 255);
            text-decoration: none;
            font-weight: bold;
        }

        nav ul li a:hover,
        nav ul li a:active {
            color: #343639;
        }

        main {
            padding: 20px;
        }

        h2 {
            color: #4a92e4;
        }

        .faq,
        .contact-info,
        .info-box {
            margin-bottom: 20px;
            margin: 15px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: white;
        }

        .question {
            cursor: pointer;
            color: #000;
            margin: 10px 0;
            transition: color 0.3s;
        }

        .question:hover,
        .question.active {
            color: #4a92e4;
        }

        .answer {
            display: none;
            margin: 10px 0 20px 0;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }

        section {
            margin-bottom: 20px;
            background-color: white;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
        }

        a[href^="mailto:"]:hover {
            text-decoration: underline !important;
        }

        footer {
            text-align: center;
            padding: 10px 0;
            background-color: #d9d9d9;
            color: black;
            position: relative;
            bottom: 0;
            width: 100%;
            font-size: 12px;
            align-items: baseline;
        }

        button {
            background-color: #4a92e4;
            color: white;
            border: none;
            padding: 10px 15px;
            margin: 20px;
            cursor: pointer;
            font-weight: bold;
        }

        footer p {
            margin: 20px 0;
        }

        button:hover {
            background-color: #3d98ff;
            transform: scale(1.01);
        }

        html {
            scroll-behavior: smooth;
        }
    </style>
</head>

<body>
    <header>
        <h1>TƯ VẤN TRANG SỨC BẠC</h1>
        <nav>
            <ul>
                <li><a href="trangchu.php">Trang chủ</a></li>
                <li><a href="#faq">Câu hỏi thường gặp</a></li>
                <li><a href="#contact">Liên hệ tư vấn</a></li>
                <li><a href="#contact-info">Hỗ trợ khách hàng</a></li>
                <li><a href="#services">Dịch vụ</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <section class="faq" id="faq">
            <h2>Câu hỏi thường gặp</h2>
            <div class="faq">
                <div class="question">1. Các loại trang sức bạc nào bạn đang cung cấp?</div>
                <div class="answer">Chúng tôi cung cấp đa dạng trang sức bạc 925 cao cấp bao gồm: nhẫn bạc, dây chuyền, vòng tay, bông tai, lắc tay và nhiều phụ kiện trang sức khác với thiết kế hiện đại và truyền thống.</div>

                <div class="question">2. Làm thế nào để phân biệt bạc thật với bạc giả?</div>
                <div class="answer">Bạc thật 925 sẽ có dấu tem "925" hoặc "S925" được khắc trên sản phẩm. Bạc thật không bị từ tính hút, có độ bóng tự nhiên và không gây kích ứng da. Tất cả sản phẩm của chúng tôi đều có giấy chứng nhận nguồn gốc.</div>

                <div class="question">3. Thời gian làm việc của cửa hàng?</div>
                <div class="answer">Chúng tôi làm việc từ 8h đến 20h từ thứ Hai đến Chủ Nhật để phục vụ quý khách.</div>

                <div class="question">4. Có bảo hành cho trang sức bạc không? Thời gian bảo hành là bao lâu?</div>
                <div class="answer">Tất cả sản phẩm trang sức bạc đều được bảo hành trọn đời về lỗi kỹ thuật, xi mạ và đánh bóng miễn phí trong 6 tháng đầu. Chúng tôi cam kết đổi mới nếu sản phẩm bị lỗi do nhà sản xuất.</div>

                <div class="question">5. Làm sao để bảo quản trang sức bạc không bị xỉn màu?</div>
                <div class="answer">Nên bảo quản trang sức bạc trong hộp kín, tránh tiếp xúc với nước, mồ hôi, nước hoa và hóa chất. Sau khi sử dụng nên lau khô bằng vải mềm. Chúng tôi cung cấp dịch vụ làm sạch và đánh bóng miễn phí.</div>

                <div class="question">6. Chính sách đổi trả sản phẩm như thế nào?</div>
                <div class="answer">Chúng tôi hỗ trợ đổi trả trong vòng 7 ngày nếu sản phẩm còn nguyên tem, hộp và chưa qua sử dụng. Sản phẩm bị lỗi kỹ thuật sẽ được đổi mới hoặc hoàn tiền 100%.</div>

                <div class="question">7. Có dịch vụ thiết kế trang sức theo yêu cầu không?</div>
                <div class="answer">Có, chúng tôi nhận thiết kế và chế tác trang sức bạc theo yêu cầu riêng của khách hàng. Thời gian hoàn thành từ 7-14 ngày tùy độ phức tạp của thiết kế.</div>

                <div class="question">8. Tôi có thể thanh toán bằng hình thức nào?</div>
                <div class="answer">Bạn có thể thanh toán bằng tiền mặt, chuyển khoản ngân hàng, thẻ tín dụng/ghi nợ, hoặc ví điện tử (Momo, ZaloPay, VNPay). Chúng tôi cũng hỗ trợ trả góp 0% qua thẻ tín dụng.</div>

                <div class="question">9. Có dịch vụ giao hàng tận nơi không?</div>
                <div class="answer">Có, chúng tôi giao hàng toàn quốc, miễn phí vận chuyển cho đơn hàng từ 500.000đ. Sản phẩm được đóng gói cẩn thận và bảo hiểm 100% giá trị.</div>

                <div class="question">10. Làm sao để chọn size nhẫn phù hợp?</div>
                <div class="answer">Bạn có thể đến cửa hàng để được đo size trực tiếp hoặc liên hệ qua hotline để được hướng dẫn đo size tại nhà. Chúng tôi cũng hỗ trợ chỉnh size miễn phí trong vòng 30 ngày.</div>
            </div>
        </section>

        <section id="contact">
            <h2>Liên hệ tư vấn</h2>
            <div class="contact-info">
                <p>Để được tư vấn về sản phẩm trang sức bạc và dịch vụ, bạn có thể liên hệ với chúng tôi qua:</p>
                <p><strong>Hotline:</strong> 0368-496-773</p>
                <p><strong>Email:</strong>
                    <a href="mailto:tuvan@hearttie.vn" style="color: #0071e3; text-decoration: none;" onmouseover="this.style.textDecoration='underline';" onmouseout="this.style.textDecoration='none';">
                        tuvan@hearttie.vn
                    </a>
                </p>
                <p><strong>Giờ làm việc:</strong> 8h đến 20h từ thứ Hai đến Chủ Nhật</p>
                <button onclick="window.location.href='tel:0368496773'">Gọi ngay</button>
                <button onclick="alert('Vui lòng liên hệ qua Zalo: 0368-496-773')">Chat trực tuyến</button>
            </div>
        </section>

        <section id="contact-info">
            <h2>Hỗ trợ khách hàng</h2>
            <div class="contact-info">
                <p>Chúng tôi cung cấp dịch vụ hỗ trợ khách hàng toàn diện:</p>
                <ul>
                    <li>Tư vấn chọn mẫu trang sức phù hợp</li>
                    <li>Hướng dẫn bảo quản và vệ sinh trang sức bạc</li>
                    <li>Hỗ trợ chỉnh size và sửa chữa trang sức</li>
                    <li>Tư vấn phong thủy và ý nghĩa trang sức</li>
                </ul>
                <p>Để được hỗ trợ, bạn có thể liên hệ qua:</p>
                <p><strong>Điện thoại hỗ trợ:</strong> 0823-456-693</p>
                <p><strong>Email:</strong>
                    <a href="mailto:hotro@hearttie.vn" style="color: #0071e3; text-decoration: none;" onmouseover="this.style.textDecoration='underline';" onmouseout="this.style.textDecoration='none';">
                        hotro@hearttie.vn
                    </a>
                </p>
                <button onclick="alert('Yêu cầu hỗ trợ của bạn đã được ghi nhận. Chúng tôi sẽ liên hệ trong thời gian sớm nhất!')">Gửi yêu cầu hỗ trợ</button>
            </div>
        </section>

        <section id="services" class="services">
            <h2>Dịch vụ</h2>
            <div class="info-box">
                <div class="service">
                    <h3>1. Tư vấn chọn trang sức</h3>
                    <p>Đội ngũ chuyên gia trang sức giàu kinh nghiệm sẽ tư vấn tận tình giúp bạn chọn lựa sản phẩm phù hợp với phong cách, sở thích và ngân sách của mình.</p>
                </div>
            </div>
            <div class="info-box">
                <div class="service">
                    <h3>2. Thiết kế theo yêu cầu</h3>
                    <p>Nhận thiết kế và chế tác trang sức bạc độc đáo theo ý tưởng riêng của bạn, từ nhẫn cưới, dây chuyền tên, đến những món trang sức mang ý nghĩa đặc biệt.</p>
                </div>
            </div>
            <div class="info-box">
                <div class="service">
                    <h3>3. Dịch vụ làm sạch và đánh bóng</h3>
                    <p>Cung cấp dịch vụ làm sạch, đánh bóng và bảo dưỡng trang sức bạc miễn phí trọn đời cho khách hàng, giúp trang sức luôn sáng bóng như mới.</p>
                </div>
            </div>
            <div class="info-box">
                <div class="service">
                    <h3>4. Sửa chữa và chỉnh size</h3>
                    <p>Dịch vụ sửa chữa, chỉnh size nhẫn, nối dây chuyền, thay móc khóa với mức giá ưu đãi và thời gian xử lý nhanh chóng, bảo hành dịch vụ.</p>
                </div>
            </div>
            <div class="info-box">
                <div class="service">
                    <h3>5. Khắc chữ và khắc tên</h3>
                    <p>Cung cấp dịch vụ khắc chữ, khắc tên, khắc ngày tháng ý nghĩa lên trang sức bạc, tạo nên món quà độc đáo và riêng biệt.</p>
                </div>
            </div>
            <div class="info-box">
                <div class="service">
                    <h3>6. Chương trình khuyến mãi</h3>
                    <p>Thường xuyên có các chương trình ưu đãi hấp dẫn: giảm giá, tặng kèm, tích điểm đổi quà cho khách hàng thân thiết và nhiều ưu đãi đặc biệt.</p>
                </div>
            </div>
            <div class="info-box">
                <div class="service">
                    <h3>7. Tư vấn phong thủy</h3>
                    <p>Tư vấn chọn trang sức bạc phù hợp theo tuổi, mệnh và phong thủy, giúp bạn chọn được món trang sức mang lại may mắn và tài lộc.</p>
                </div>
            </div>
            <div class="info-box">
                <div class="service">
                    <h3>8. Thanh toán linh hoạt</h3>
                    <p>Hỗ trợ đa dạng hình thức thanh toán: tiền mặt, chuyển khoản, thẻ tín dụng, ví điện tử và trả góp 0% qua thẻ tín dụng.</p>
                </div>
            </div>
            <div class="info-box">
                <div class="service">
                    <h3>9. Giao hàng toàn quốc</h3>
                    <p>Miễn phí giao hàng toàn quốc cho đơn từ 500.000đ, đóng gói cẩn thận, bảo hiểm hàng hóa 100% giá trị sản phẩm.</p>
                </div>
            </div>
            <div class="info-box">
                <div class="service">
                    <h3>10. Thu đổi trang sức cũ</h3>
                    <p>Nhận thu đổi trang sức bạc cũ với giá ưu đãi, hỗ trợ nâng cấp lên sản phẩm mới với chính sách đổi trả linh hoạt và minh bạch.</p>
                </div>
            </div>
        </section>
    </main>

    <script>
        const questions = document.querySelectorAll('.question');
        questions.forEach(question => {
            question.addEventListener('click', () => {
                const answer = question.nextElementSibling;
                answer.style.display = answer.style.display === 'block' ? 'none' : 'block';
                question.classList.toggle('active');
            });
        });
    </script>

    <footer>
         <p>© 2020 Công ty cổ phần HEARTTIE. GPDKKD: 0303217354 do sở Cần Thơ cấp ngày 02/01/2015. GPMXH:
                21/GP-BTTTT do Bộ Thông Tin và Truyền Thông cấp ngày 11/01/2021. Địa chỉ: 128, Nguyễn Văn Cừ (nối dài),
                Phường An Bình, Quận Ninh Kiều, Thành Phố Cần Thơ.
                <br />
                Chịu trách nhiệm nội dung: Tiêu Ngọc Thanh Tâm. Tất cả
                các quyền được bảo
                lưu.
            </p>
    </footer>
</body>

</html>