<?php
// Nhận đơn hàng từ thanh-toan.html và lưu vào data/orders.data
declare(strict_types=1);
require dirname(__DIR__) . '/includes/config.php';
require dirname(__DIR__) . '/includes/storage.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    storage_respond_json(['success' => false, 'error' => 'Phương thức không hợp lệ.'], 405);
}

$input = storage_get_post_json();

$customer = is_array($input['customer'] ?? null) ? $input['customer'] : [];
$fullname = trim((string)($customer['fullname'] ?? ''));
$phone    = trim((string)($customer['phone'] ?? ''));
$email    = trim((string)($customer['email'] ?? ''));
$address  = trim((string)($customer['address'] ?? ''));
$city     = trim((string)($customer['city'] ?? ''));
$district = trim((string)($customer['district'] ?? ''));
$note     = trim((string)($customer['note'] ?? ''));

$items = $input['items'] ?? [];
if (!is_array($items) || count($items) === 0) {
    storage_respond_json(['success' => false, 'error' => 'Giỏ hàng trống.'], 422);
}
if ($fullname === '' || $phone === '' || $address === '' || $city === '' || $district === '') {
    storage_respond_json(['success' => false, 'error' => 'Vui lòng điền đầy đủ thông tin giao hàng.'], 422);
}

// Tính lại tiền phía server — không tin số liệu client
$subtotal = 0;
foreach ($items as $item) {
    if (!is_array($item)) {
        continue;
    }
    $price = max(0, (int)($item['price'] ?? 0));
    $qty   = max(1, (int)($item['quantity'] ?? 1));
    $subtotal += $price * $qty;
}

$coupon   = strtoupper(trim((string)($input['coupon'] ?? '')));
$discount = 0;
if ($coupon !== '' && isset(COUPONS[$coupon])) {
    $c        = COUPONS[$coupon];
    $discount = $c['type'] === 'percent' ? (int)round($subtotal * $c['value'] / 100) : $c['value'];
}
$shipping = SHIPPING_FEE;
$total    = $subtotal + $shipping - $discount;

// Mã đơn hàng: ưu tiên mã client đã tự gen, nếu trống thì tạo lại
$orderCode = trim((string)($input['orderCode'] ?? ''));
if ($orderCode === '') {
    $orderCode = 'DH' . date('YmdHis') . random_int(100, 999);
}

$paymentMethod = (string)($input['paymentMethod'] ?? 'cod');
if (!in_array($paymentMethod, ['cod', 'bank', 'qr'], true)) {
    $paymentMethod = 'cod';
}

$order = [
    'orderCode'     => $orderCode,
    'customer'      => [
        'fullname' => storage_encrypt_field($fullname),
        'phone'    => storage_encrypt_field($phone),
        'email'    => storage_encrypt_field($email),
        'address'  => storage_encrypt_field($address),
        'city'     => $city,
        'district' => $district,
        'note'     => storage_encrypt_field($note),
    ],
    'items'         => $items,
    'subtotal'      => $subtotal,
    'shipping'      => $shipping,
    'discount'      => $discount,
    'total'         => $total,
    'paymentMethod' => $paymentMethod,
    'status'        => 'mới',
    'created_at'    => date('Y-m-d H:i:s'),
];

if (!storage_append('orders.data', $order)) {
    storage_respond_json(['success' => false, 'error' => 'Không thể lưu đơn hàng. Kiểm tra quyền ghi thư mục data/.'], 500);
}

storage_respond_json(['success' => true, 'orderCode' => $orderCode, 'total' => $total]);
