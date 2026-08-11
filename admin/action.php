<?php
// API quản trị: đổi trạng thái đơn / xóa đơn / xóa tin nhắn — chỉ dùng khi đã đăng nhập
declare(strict_types=1);
require dirname(__DIR__) . '/includes/config.php';
require dirname(__DIR__) . '/includes/storage.php';

session_start();
if (empty($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    echo 'Chưa đăng nhập.';
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Phương thức không hợp lệ.';
    exit;
}

$action = (string)($_POST['action'] ?? '');

if ($action === 'update_status') {
    $code   = (string)($_POST['code'] ?? '');
    $status = (string)($_POST['status'] ?? '');
    if ($code === '' || !in_array($status, ORDER_STATUSES, true)) {
        http_response_code(422);
        echo 'Dữ liệu không hợp lệ.';
        exit;
    }
    $orders = storage_read('orders.data');
    foreach ($orders as &$order) {
        if (($order['orderCode'] ?? '') === $code) {
            $order['status'] = $status;
            break;
        }
    }
    unset($order);
    storage_save('orders.data', $orders);
    header('Location: index.php');
    exit;
}

if ($action === 'delete_order') {
    $code   = (string)($_POST['code'] ?? '');
    $orders = storage_read('orders.data');
    $orders = array_values(array_filter($orders, function ($order) use ($code) {
        return ($order['orderCode'] ?? '') !== $code;
    }));
    storage_save('orders.data', $orders);
    header('Location: index.php');
    exit;
}

if ($action === 'delete_message') {
    $id       = (string)($_POST['id'] ?? '');
    $messages = storage_read('messages.data');
    $messages = array_values(array_filter($messages, function ($msg) use ($id) {
        return ($msg['id'] ?? '') !== $id;
    }));
    storage_save('messages.data', $messages);
    header('Location: index.php');
    exit;
}

http_response_code(422);
echo 'Hành động không hợp lệ.';
exit;
