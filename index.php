<?php
// Trang chủ — nội dung hero + sản phẩm render từ data/home.data (admin chỉnh qua admin/home.php)
declare(strict_types=1);
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/storage.php';

$home = storage_read('home.data');
if (empty($home)) {
    $home = [
        'hero' => [
            'title'       => 'Dưa Lưới Tươi Sạch',
            'subtitle'    => '100% Từ Nông Trại VietGAP',
            'button_text' => 'Xem chi tiết',
            'button_link' => '#',
        ],
        'products' => [
            [
                'name'      => 'Dưa lưới Huỳnh Long',
                'image'     => 'dua1.jpg',
                'old_price' => 75000,
                'price'     => 65000,
                'discount'  => 10,
            ],
        ],
    ];
}
$hero    = $home['hero']    ?? [];
$products = is_array($home['products'] ?? null) ? $home['products'] : [];

function esc_home($s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
function fmt_price_home(int $n): string
{
    return number_format($n, 0, ',', '.') . 'đ';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dưa Lưới Lộc Phú - Tươi Sạch Từ Nông Trại</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>

    <header class="site-header">
        <div class="container">
            <div class="logo">
                <h1>Dưa Lưới Lộc Phú</h1>
            </div>
            <div class="search-bar">
                <input type="text" placeholder="Tìm kiếm sản phẩm...">
                <button type="submit">Tìm</button>
            </div>
            <nav class="main-nav">
                <ul>
                    <li><a href="index.html" class="active">Trang chủ</a></li>
                    <li><a href="san-pham.html">Sản phẩm</a></li>
                    <li><a href="thanh-toan.html">Thanh toán</a></li>
                    <li><a href="gioi-thieu.html">Giới thiệu</a></li>
                    <li><a href="lien-he.html">Liên hệ</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
<section class="hero-banner">
    <div class="container">
        <h2><?php echo esc_home($hero['title'] ?? ''); ?></h2>
        <p><?php echo esc_home($hero['subtitle'] ?? ''); ?></p>
        <a href="<?php echo esc_home($hero['button_link'] ?? '#'); ?>" class="btn-primary"><?php echo esc_home($hero['button_text'] ?? 'Xem chi tiết'); ?></a>
    </div>
</section>

        <section class="features">
            <div class="container">
                <div class="feature-box">
                    <img src="ocop.jpg" alt="Giống Nhật Bản">
                    <h3>Chuẩn Ocop</h3>
                </div>
                <div class="feature-box">
                    <img src="vietgap.jpg" alt="Chuẩn VietGAP">
                    <h3>Chuẩn VietGAP</h3>
                </div>
                <div class="feature-box">
                    <img src="giao-hang.jpg" alt="Giao hàng nhanh">
                    <h3>Giao Hàng Nhanh</h3>
                </div>
            </div>
        </section>

        <section class="products-section">
            <div class="container">
                <h2>SẢN PHẨM CỦA CHÚNG TÔI</h2>
                <p>Những trái dưa lưới tươi ngon, mọng nước nhất được tuyển chọn.</p>

                <div class="product-grid">
                    <?php foreach ($products as $p): ?>
                        <?php
                            $pName  = (string)($p['name'] ?? '');
                            $pImg   = (string)($p['image'] ?? '');
                            $pPrice = (int)($p['price'] ?? 0);
                            $pOld   = (int)($p['old_price'] ?? 0);
                            $pDisc  = (int)($p['discount'] ?? 0);
                        ?>
                        <div class="product-card">
                            <div class="product-image">
                                <img src="<?php echo esc_home($pImg); ?>" alt="<?php echo esc_home($pName); ?>">
                                <?php if ($pDisc > 0): ?>
                                    <span class="discount-badge">-<?php echo (int)$pDisc; ?>%</span>
                                <?php endif; ?>
                            </div>
                            <h3 class="product-name"><?php echo esc_home($pName); ?></h3>
                            <div class="product-price">
                                <?php if ($pOld > 0 && $pOld !== $pPrice): ?>
                                    <span class="old-price"><?php echo fmt_price_home($pOld); ?></span>
                                <?php endif; ?>
                                <span class="new-price"><?php echo fmt_price_home($pPrice); ?> / kg</span>
                            </div>
                            <a href="#" class="btn-buy" onclick="addToCart(event, '<?php echo esc_home($pName); ?>', <?php echo $pPrice; ?>, '<?php echo esc_home($pImg); ?>')">Mua ngay</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="container">
            <p>&copy; 2025 Dưa Lưới Lộc Phú. Tất cả quyền được bảo lưu.</p>
        </div>
    </footer>

    <script>
        // Thêm sản phẩm vào giỏ hàng
        function addToCart(event, name, price, image) {
            event.preventDefault();
            
            let cart = JSON.parse(localStorage.getItem('cart')) || [];
            
            // Kiểm tra sản phẩm đã có trong giỏ hàng chưa
            const existingItem = cart.find(item => item.name === name);
            
            if (existingItem) {
                existingItem.quantity += 1;
            } else {
                cart.push({
                    name: name,
                    price: price,
                    image: image,
                    quantity: 1
                });
            }
            
            localStorage.setItem('cart', JSON.stringify(cart));
            
            // Hiển thị thông báo
            alert('Đã thêm ' + name + ' vào giỏ hàng!');
            
            // Hỏi có muốn đến trang thanh toán không
            if (confirm('Bạn có muốn đến trang thanh toán không?')) {
                window.location.href = 'thanh-toan.html';
            }
        }
    </script>

</body>
</html>