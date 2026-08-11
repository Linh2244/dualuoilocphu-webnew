<?php
// Nhận tin nhắn từ form liên hệ (lien-he.html) và lưu vào data/messages.data
declare(strict_types=1);
require dirname(__DIR__) . '/includes/config.php';
require dirname(__DIR__) . '/includes/storage.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    storage_respond_json(['success' => false, 'error' => 'Phương thức không hợp lệ.'], 405);
}

$input = storage_get_post_json();

// Honeypot chống spam: trường ẩn "website" do bot điền, người dùng thật luôn bỏ trống
if (!empty($input['website'])) {
    storage_respond_json(['success' => true, 'message' => 'Cảm ơn bạn đã liên hệ!']);
}

$name    = trim((string)($input['name'] ?? ''));
$phone   = trim((string)($input['phone'] ?? ''));
$email   = trim((string)($input['email'] ?? ''));
$subject = trim((string)($input['subject'] ?? ''));
$message = trim((string)($input['message'] ?? ''));

if ($name === '' || $phone === '' || $subject === '' || $message === '') {
    storage_respond_json(['success' => false, 'error' => 'Vui lòng điền đầy đủ các trường bắt buộc.'], 422);
}

$entry = [
    'id'         => uniqid('msg_', true),
    'name'       => storage_encrypt_field($name),
    'phone'      => storage_encrypt_field($phone),
    'email'      => storage_encrypt_field($email),
    'subject'    => storage_encrypt_field($subject),
    'message'    => storage_encrypt_field($message),
    'created_at' => date('Y-m-d H:i:s'),
];

if (!storage_append('messages.data', $entry)) {
    storage_respond_json(['success' => false, 'error' => 'Không thể gửi tin nhắn. Vui lòng thử lại.'], 500);
}

storage_respond_json(['success' => true, 'message' => 'Cảm ơn bạn đã liên hệ! Chúng tôi sẽ phản hồi trong thời gian sớm nhất.']);
