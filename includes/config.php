<?php
// Cấu hình chung cho backend Dưa Lưới Lộc Phú
declare(strict_types=1);

// ===== Thư mục dữ liệu =====
// Ưu tiên đặt NGOÀI web root để không truy cập web được.
// Mặc định tự dò theo DOCUMENT_ROOT:
//   - Nếu ghi được ở ngoài web root → /home/user/data (cùng cấp htdocs/) ← an toàn nhất
//   - Nếu host chặn ghi ngoài web root (InfinityFree bị giới hạn open_basedir)
//     → tự động dùng htdocs/data (trong web root, vẫn được .htaccess + data/index.php
//     + mã hóa AES bảo vệ; admin sẽ hiện banner để bạn biết)
// Web root có thể tên public_html, htdocs, www, ... — chương trình tự nhận, không cần sửa.
// Nếu muốn ép buộc một đường dẫn cụ thể, bỏ comment dòng dưới và sửa cho đúng:
// define('DATA_DIR', '/home/user/data');
if (!defined('DATA_DIR')) {
    if (!empty($_SERVER['DOCUMENT_ROOT'])) {
        $docRoot = rtrim(str_replace('\\', '/', (string)$_SERVER['DOCUMENT_ROOT']), '/');
        $outside = dirname($docRoot) . '/data'; // ngoài web root: /home/user/data
        $inside  = dirname(__DIR__) . '/data';  // trong web root: htdocs/data

        $dirWritable = function (string $dir): bool {
            if (is_dir($dir)) {
                return is_writable($dir);
            }
            $parent = dirname($dir);
            return is_dir($parent) && is_writable($parent);
        };

        define('DATA_DIR', $dirWritable($outside) ? $outside : $inside);
        $docNorm = strtolower($docRoot) . '/';
        define('DATA_DIR_INSIDE_WEBROOT', strpos(strtolower(DATA_DIR) . '/', $docNorm) === 0 ? 1 : 0);
    } else {
        // Không có web server (CLI) — fallback vào web root, admin sẽ cảnh báo
        define('DATA_DIR', dirname(__DIR__) . '/data');
        define('DATA_DIR_INSIDE_WEBROOT', 1);
    }
}
define('ADMIN_FILE', DATA_DIR . '/admin.data');

// File khóa mã hóa AES (nằm trong thư mục dữ liệu, ngoài web root)
define('ENCRYPTION_KEY_FILE', DATA_DIR . '/encryption.key');

// Phí vận chuyển cố định
define('SHIPPING_FEE', 30000);

// Các mã giảm giá hợp lệ (backend tính lại tiền, không tin client)
define('COUPONS', [
    'GIAM10' => ['type' => 'percent', 'value' => 10],
    'GIAM50K' => ['type' => 'fixed', 'value' => 50000],
]);

// Trạng thái đơn hàng cho trang quản trị
define('ORDER_STATUSES', ['mới', 'đã xác nhận', 'đang giao', 'đã giao', 'đã hủy']);

define('PAYMENT_LABELS', [
    'cod' => 'Thanh toán khi nhận hàng (COD)',
    'bank' => 'Chuyển khoản ngân hàng',
    'qr' => 'Quét mã QR',
]);

date_default_timezone_set('Asia/Ho_Chi_Minh');
