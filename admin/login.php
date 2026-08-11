<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/config.php';
require dirname(__DIR__) . '/includes/storage.php';

session_start();

// Chưa tạo tài khoản admin → bắt buộc chạy setup trước
if (empty(storage_read('admin.data'))) {
    header('Location: setup.php');
    exit;
}
if (!empty($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    // Chống brute-force: thêm độ trễ nhẹ mỗi lần thử
    usleep(500000);

    $admin = storage_read('admin.data');
    if (
        is_array($admin)
        && isset($admin['username'], $admin['password_hash'])
        && hash_equals($admin['username'], $username)
        && password_verify($password, $admin['password_hash'])
    ) {
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        header('Location: index.php');
        exit;
    }
    $error = 'Sai tên đăng nhập hoặc mật khẩu.';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập quản trị - Dưa Lưới Lộc Phú</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Roboto', Arial, sans-serif; background: #fefdfa; margin: 0; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .box { background: #fff; padding: 40px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); width: 100%; max-width: 380px; }
        h1 { font-size: 20px; color: #27ae60; margin: 0 0 20px; }
        label { display: block; font-size: 14px; color: #333; margin: 12px 0 5px; }
        input { width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 15px; }
        .err { background: #fdecea; color: #c0392b; padding: 10px 12px; border-radius: 5px; margin-bottom: 10px; font-size: 14px; }
        button { width: 100%; margin-top: 20px; padding: 12px; background: #f39c12; color: #fff; border: none; border-radius: 5px; font-size: 16px; font-weight: 600; cursor: pointer; }
        button:hover { background: #e67e22; }
        a { color: #27ae60; text-decoration: none; font-size: 14px; display: block; margin-top: 15px; text-align: center; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Đăng nhập quản trị</h1>
        <?php if ($error !== ''): ?><div class="err"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <form method="post">
            <label>Tên đăng nhập</label>
            <input type="text" name="username" required autofocus>
            <label>Mật khẩu</label>
            <input type="password" name="password" required>
            <button type="submit">Đăng nhập</button>
        </form>
        <a href="../index.html">← Về trang chủ</a>
    </div>
</body>
</html>
