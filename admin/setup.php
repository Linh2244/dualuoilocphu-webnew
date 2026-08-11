<?php
// Chạy MỘT LẦN để tạo tài khoản quản trị. Sau khi tạo xong, trang này bị vô hiệu.
declare(strict_types=1);
require dirname(__DIR__) . '/includes/config.php';
require dirname(__DIR__) . '/includes/storage.php';

if (!extension_loaded('openssl')) {
    die('Backend yêu cầu extension PHP "openssl" để mã hóa dữ liệu khách hàng. Hãy bật openssl trong PHP.ini trên hosting.');
}

session_start();

$alreadySetUp = is_file(ADMIN_FILE);
if ($alreadySetUp) {
    header('Location: login.php');
    exit;
}

$error = '';
$done  = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirm  = (string)($_POST['confirm'] ?? '');

    if (strlen($username) < 3) {
        $error = 'Tên đăng nhập phải có ít nhất 3 ký tự.';
    } elseif (strlen($password) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự.';
    } elseif ($password !== $confirm) {
        $error = 'Mật khẩu xác nhận không khớp.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        if (storage_save('admin.data', [
            'username'      => $username,
            'password_hash' => $hash,
            'created_at'    => date('Y-m-d H:i:s'),
        ])) {
            $done = true;
        } else {
            $error = 'Không thể tạo tài khoản — PHP không ghi được vào thư mục dữ liệu.'
                . '<br>Cách sửa: dùng File Manager/FTP tạo sẵn thư mục dữ liệu rồi set quyền 755 hoặc 775. '
                . 'Nếu vẫn không được, host bị giới hạn ghi ngoài web root — sửa dòng <code>define(\'DATA_DIR\', ...)</code> trong <code>includes/config.php</code> thành đường dẫn trong htdocs.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cài đặt quản trị - Dưa Lưới Lộc Phú</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Roboto', Arial, sans-serif; background: #fefdfa; margin: 0; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .box { background: #fff; padding: 40px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); width: 100%; max-width: 400px; }
        h1 { font-size: 20px; color: #27ae60; margin: 0 0 8px; }
        p { color: #777; font-size: 14px; margin: 0 0 20px; }
        label { display: block; font-size: 14px; color: #333; margin: 12px 0 5px; }
        input { width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 15px; }
        .msg { padding: 10px 12px; border-radius: 5px; margin-bottom: 10px; font-size: 14px; }
        .err { background: #fdecea; color: #c0392b; }
        .ok { background: #e8f8f0; color: #27ae60; }
        button { width: 100%; margin-top: 20px; padding: 12px; background: #f39c12; color: #fff; border: none; border-radius: 5px; font-size: 16px; font-weight: 600; cursor: pointer; }
        button:hover { background: #e67e22; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Cài đặt quản trị</h1>
        <?php if ($done): ?>
            <div class="msg ok">Tạo tài khoản thành công!</div>
            <p><a href="login.php">Đăng nhập ngay</a></p>
        <?php else: ?>
            <p>Trang này chỉ cần chạy một lần. Sau khi tạo xong, nó sẽ tự bị vô hiệu.</p>
            <?php if ($error !== ''): ?><div class="msg err"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
            <form method="post">
                <label>Tên đăng nhập</label>
                <input type="text" name="username" required minlength="3" autofocus>
                <label>Mật khẩu</label>
                <input type="password" name="password" required minlength="6">
                <label>Nhập lại mật khẩu</label>
                <input type="password" name="confirm" required minlength="6">
                <button type="submit">Tạo tài khoản</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
