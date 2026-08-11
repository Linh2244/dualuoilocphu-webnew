<?php
// Quản lý nội dung trang chủ: hero + sản phẩm
declare(strict_types=1);
require dirname(__DIR__) . '/includes/config.php';
require dirname(__DIR__) . '/includes/storage.php';

session_start();
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

function esc_home_admin($s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

$home = storage_read('home.data');
if (empty($home)) {
    $home = [
        'hero' => [
            'title'       => 'Dưa Lưới Tươi Sạch',
            'subtitle'    => '100% Từ Nông Trại VietGAP',
            'button_text' => 'Xem chi tiết',
            'button_link' => '#',
        ],
        'products' => [],
    ];
}
$ok  = (string)($_GET['ok'] ?? '');
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    $home['hero'] = is_array($home['hero'] ?? null) ? $home['hero'] : [];
    $home['products'] = is_array($home['products'] ?? null) ? $home['products'] : [];

    if ($action === 'save_hero') {
        $home['hero']['title']       = trim((string)($_POST['title'] ?? ''));
        $home['hero']['subtitle']    = trim((string)($_POST['subtitle'] ?? ''));
        $home['hero']['button_text'] = trim((string)($_POST['button_text'] ?? ''));
        $home['hero']['button_link'] = trim((string)($_POST['button_link'] ?? '#'));
        if ($home['hero']['button_link'] === '') {
            $home['hero']['button_link'] = '#';
        }
        $saved = storage_save('home.data', $home);
        if ($saved) {
            header('Location: home.php?ok=hero');
            exit;
        }
        $err = 'Không ghi được dữ liệu. Kiểm tra quyền ghi thư mục dữ liệu.';
    }

    if ($action === 'add_product') {
        $name = trim((string)($_POST['name'] ?? ''));
        $price = (int)($_POST['price'] ?? 0);
        $old   = (int)($_POST['old_price'] ?? 0);
        $disc  = (int)($_POST['discount'] ?? 0);
        if ($price <= 0) {
            $err = 'Giá mới phải lớn hơn 0.';
        } elseif ($old < 0 || $disc < 0 || $disc > 99) {
            $err = 'Giá cũ hoặc % giảm không hợp lệ.';
        } else {
            $home['products'][] = [
                'name'      => $name,
                'image'     => trim((string)($_POST['image'] ?? '')),
                'old_price' => $old,
                'price'     => $price,
                'discount'  => $disc,
            ];
            $saved = storage_save('home.data', $home);
            if ($saved) {
                header('Location: home.php?ok=add');
                exit;
            }
            $err = 'Không ghi được dữ liệu.';
        }
    }

    if ($action === 'update_product') {
        $idx = (int)($_POST['idx'] ?? -1);
        if (isset($home['products'][$idx])) {
            $name = trim((string)($_POST['name'] ?? ''));
            $price = (int)($_POST['price'] ?? 0);
            $old   = (int)($_POST['old_price'] ?? 0);
            $disc  = (int)($_POST['discount'] ?? 0);
            if ($price <= 0) {
                $err = 'Giá mới phải lớn hơn 0.';
            } elseif ($old < 0 || $disc < 0 || $disc > 99) {
                $err = 'Giá cũ hoặc % giảm không hợp lệ.';
            } else {
                $home['products'][$idx] = [
                    'name'      => $name,
                    'image'     => trim((string)($_POST['image'] ?? '')),
                    'old_price' => $old,
                    'price'     => $price,
                    'discount'  => $disc,
                ];
                $saved = storage_save('home.data', $home);
                if ($saved) {
                    header('Location: home.php?ok=update');
                    exit;
                }
                $err = 'Không ghi được dữ liệu.';
            }
        }
    }

    if ($action === 'delete_product') {
        $idx = (int)($_POST['idx'] ?? -1);
        if (isset($home['products'][$idx])) {
            array_splice($home['products'], $idx, 1);
            $saved = storage_save('home.data', $home);
            if ($saved) {
                header('Location: home.php?ok=delete');
                exit;
            }
            $err = 'Không ghi được dữ liệu.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh trang chủ - Quản trị Dưa Lưới Lộc Phú</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Roboto', Arial, sans-serif; background: #f4f4f4; margin: 0; color: #333; }
        .topbar { background: #27ae60; color: #fff; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .topbar h1 { font-size: 18px; margin: 0; }
        .topbar a { color: #fff; text-decoration: none; font-size: 14px; margin-left: 15px; }
        .wrap { max-width: 900px; margin: 20px auto; padding: 0 15px; }
        .card { background: #fff; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.06); margin-bottom: 25px; overflow: hidden; }
        .card h2 { font-size: 16px; margin: 0; padding: 15px 20px; border-bottom: 1px solid #eee; }
        .card h2 span { color: #f39c12; }
        .card-inner { padding: 20px; }
        .warn { background: #fdecea; color: #c0392b; border: 1px solid #e8b4ad; border-radius: 6px; padding: 12px 15px; margin-bottom: 20px; font-size: 14px; line-height: 1.6; }
        label { display: block; font-size: 14px; color: #555; margin: 14px 0 5px; }
        input[type="text"], input[type="number"] { width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        .row { display: flex; gap: 15px; flex-wrap: wrap; }
        .row .col { flex: 1; min-width: 180px; }
        button { margin-top: 20px; padding: 11px 20px; background: #27ae60; color: #fff; border: none; border-radius: 5px; font-size: 14px; font-weight: 600; cursor: pointer; }
        button:hover { background: #219150; }
        button.gray { background: #7f8c8d; }
        button.gray:hover { background: #6c7a7a; }
        button.del { background: #c0392b; }
        button.del:hover { background: #a93226; }
        .product-item { border-bottom: 1px solid #f0f0f0; padding: 18px 0; }
        .product-item:last-child { border-bottom: none; }
        .product-item .title { font-weight: 600; margin-bottom: 10px; }
        .actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .actions form { margin: 0; }
        .actions button { margin-top: 16px; }
        .empty { padding: 30px; text-align: center; color: #999; }
        @media (max-width: 768px) {
            .wrap { padding: 0 8px; }
        }
    </style>
</head>
<body>
    <div class="topbar">
        <h1>Chỉnh trang chủ</h1>
        <div>
            <a href="index.php">← Bảng quản trị</a>
            <a href="../index.php">Xem website</a>
            <a href="logout.php">Đăng xuất</a>
        </div>
    </div>

    <div class="wrap">
        <?php if ($err !== ''): ?><div class="warn" style="color:#c0392b"><?php echo esc_home_admin($err); ?></div><?php endif; ?>
        <?php if ($ok === 'hero'): ?><div style="background:#e8f8f0;color:#27ae60;padding:10px 15px;border-radius:6px;margin-bottom:20px">Đã lưu hero thành công.</div><?php endif; ?>
        <?php if ($ok === 'add'): ?><div style="background:#e8f8f0;color:#27ae60;padding:10px 15px;border-radius:6px;margin-bottom:20px">Đã thêm sản phẩm thành công.</div><?php endif; ?>
        <?php if ($ok === 'update'): ?><div style="background:#e8f8f0;color:#27ae60;padding:10px 15px;border-radius:6px;margin-bottom:20px">Đã cập nhật sản phẩm thành công.</div><?php endif; ?>
        <?php if ($ok === 'delete'): ?><div style="background:#e8f8f0;color:#27ae60;padding:10px 15px;border-radius:6px;margin-bottom:20px">Đã xóa sản phẩm thành công.</div><?php endif; ?>

        <div class="card">
            <h2>Phần Hero</h2>
            <div class="card-inner">
                <form method="post">
                    <input type="hidden" name="action" value="save_hero">
                    <label>Tiêu đề</label>
                    <input type="text" name="title" value="<?php echo esc_home_admin($home['hero']['title'] ?? ''); ?>">
                    <label>Phụ đề</label>
                    <input type="text" name="subtitle" value="<?php echo esc_home_admin($home['hero']['subtitle'] ?? ''); ?>">
                    <div class="row">
                        <div class="col">
                            <label>Chữ nút bấm</label>
                            <input type="text" name="button_text" value="<?php echo esc_home_admin($home['hero']['button_text'] ?? ''); ?>">
                        </div>
                        <div class="col">
                            <label>Đường dẫn nút bấm</label>
                            <input type="text" name="button_link" value="<?php echo esc_home_admin($home['hero']['button_link'] ?? '#'); ?>">
                        </div>
                    </div>
                    <button type="submit">Lưu hero</button>
                </form>
            </div>
        </div>

        <div class="card">
            <h2>Thêm sản phẩm</h2>
            <div class="card-inner">
                <form method="post">
                    <input type="hidden" name="action" value="add_product">
                    <div class="row">
                        <div class="col">
                            <label>Tên sản phẩm</label>
                            <input type="text" name="name" required>
                        </div>
                        <div class="col">
                            <label>Ảnh (tên file)</label>
                            <input type="text" name="image">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <label>Giá cũ (đồng, để 0 nếu không có)</label>
                            <input type="number" name="old_price" min="0" value="0">
                        </div>
                        <div class="col">
                            <label>Giá mới (đồng)</label>
                            <input type="number" name="price" min="1" required>
                        </div>
                        <div class="col">
                            <label>% giảm (0-99)</label>
                            <input type="number" name="discount" min="0" max="99" value="0">
                        </div>
                    </div>
                    <button type="submit">Thêm sản phẩm</button>
                </form>
            </div>
        </div>

        <div class="card">
            <h2>Danh sách sản phẩm <span>(<?php echo count($home['products']); ?>)</span></h2>
            <?php if (count($home['products']) === 0): ?>
                <div class="empty">Chưa có sản phẩm nào.</div>
            <?php else: ?>
                <div class="card-inner">
                    <?php foreach ($home['products'] as $idx => $p): ?>
                        <div class="product-item">
                            <div class="title">Sản phẩm #<?php echo (int)$idx + 1; ?></div>
                            <form method="post">
                                <input type="hidden" name="action" value="update_product">
                                <input type="hidden" name="idx" value="<?php echo (int)$idx; ?>">
                                <div class="row">
                                    <div class="col">
                                        <label>Tên sản phẩm</label>
                                        <input type="text" name="name" value="<?php echo esc_home_admin($p['name'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col">
                                        <label>Ảnh (tên file)</label>
                                        <input type="text" name="image" value="<?php echo esc_home_admin($p['image'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col">
                                        <label>Giá cũ (đồng)</label>
                                        <input type="number" name="old_price" min="0" value="<?php echo (int)($p['old_price'] ?? 0); ?>">
                                    </div>
                                    <div class="col">
                                        <label>Giá mới (đồng)</label>
                                        <input type="number" name="price" min="1" value="<?php echo (int)($p['price'] ?? 0); ?>" required>
                                    </div>
                                    <div class="col">
                                        <label>% giảm (0-99)</label>
                                        <input type="number" name="discount" min="0" max="99" value="<?php echo (int)($p['discount'] ?? 0); ?>">
                                    </div>
                                </div>
                                <div class="actions">
                                    <button type="submit" class="gray">Cập nhật</button>
                                </div>
                            </form>
                            <form method="post" onsubmit="return confirm('Xóa sản phẩm này?');">
                                <input type="hidden" name="action" value="delete_product">
                                <input type="hidden" name="idx" value="<?php echo (int)$idx; ?>">
                                <button type="submit" class="del">Xóa</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
