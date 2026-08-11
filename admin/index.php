<?php
// Bảng quản trị: danh sách đơn hàng và tin nhắn
declare(strict_types=1);
require dirname(__DIR__) . '/includes/config.php';
require dirname(__DIR__) . '/includes/storage.php';

session_start();
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
$orders   = array_reverse(storage_read('orders.data'));

$messages = array_reverse(storage_read('messages.data'));

$revenue = 0;
foreach ($orders as $order) {
    if (($order['status'] ?? '') !== 'đã hủy') {
        $revenue += (int)($order['total'] ?? 0);
    }
}

function fmt_vnd($n): string
{
    return number_format((int)$n, 0, ',', '.') . 'đ';
}
function esc($s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản trị - Dưa Lưới Lộc Phú</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Roboto', Arial, sans-serif; background: #f4f4f4; margin: 0; color: #333; }
        .topbar { background: #27ae60; color: #fff; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .topbar h1 { font-size: 18px; margin: 0; }
        .topbar a { color: #fff; text-decoration: none; font-size: 14px; margin-left: 15px; }
        .warn { background: #fdecea; color: #c0392b; border: 1px solid #e8b4ad; border-radius: 6px; padding: 12px 15px; margin-bottom: 20px; font-size: 14px; line-height: 1.6; }
        .warn code { background: #fff; padding: 1px 5px; border-radius: 3px; }
        .wrap { max-width: 1200px; margin: 20px auto; padding: 0 15px; }
        .stats { display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 25px; }
        .stat { background: #fff; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.06); padding: 15px 20px; flex: 1; min-width: 160px; }
        .stat .num { font-size: 22px; font-weight: 700; color: #27ae60; }
        .stat .lbl { font-size: 13px; color: #777; }
        .card { background: #fff; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.06); margin-bottom: 25px; overflow: hidden; }
        .card h2 { font-size: 16px; margin: 0; padding: 15px 20px; border-bottom: 1px solid #eee; }
        .card h2 span { color: #f39c12; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { text-align: left; padding: 10px 12px; border-bottom: 1px solid #f0f0f0; vertical-align: top; }
        th { background: #fafafa; color: #777; font-weight: 600; white-space: nowrap; }
        tr:hover td { background: #fefdfa; }
        .tag { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .tag-new { background: #fdecea; color: #c0392b; }
        .tag-done { background: #e8f8f0; color: #27ae60; }
        select { padding: 5px 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; }
        .btn-del { color: #c0392b; text-decoration: none; font-size: 13px; border: none; background: none; cursor: pointer; padding: 0; }
        .btn-del:hover { text-decoration: underline; }
        .empty { padding: 30px; text-align: center; color: #999; }
        .item-line { display: block; }
        .msg-box { padding: 10px 20px; }
        .msg-item { border-bottom: 1px solid #f0f0f0; padding: 12px 0; }
        .msg-item:last-child { border-bottom: none; }
        .msg-item .head { font-weight: 600; font-size: 14px; }
        .msg-item .meta { color: #999; font-size: 12px; margin: 2px 0 6px; }
        .msg-item .body { color: #555; }
        .msg-actions { margin-top: 6px; }
        @media (max-width: 768px) {
            .wrap { padding: 0 8px; }
            table, thead { display: block; }
            thead { display: none; }
            tbody { display: block; }
            tr { display: block; margin-bottom: 12px; background: #fff; border: 1px solid #eee; border-radius: 6px; padding: 8px; }
            td { display: block; border: none; padding: 4px 8px; }
            td::before { content: attr(data-label); font-weight: 600; color: #777; display: block; font-size: 11px; }
        }
    </style>
</head>
<body>
    <div class="topbar">
        <h1>Quản trị Dưa Lưới Lộc Phú</h1>
        <div>
            <a href="home.php">Chỉnh trang chủ</a>
            <a href="../index.php">Xem website</a>
            <a href="logout.php">Đăng xuất</a>
        </div>
    </div>

    <div class="wrap">
        <?php if (DATA_DIR_INSIDE_WEBROOT): ?>
            <div class="warn">
                <strong>Lưu ý bảo mật:</strong> dữ liệu đang nằm <strong>TRONG web root</strong> — thường do host không cho PHP ghi ngoài web root (như InfinityFree). Dữ liệu vẫn được <code>data/.htaccess</code> + <code>data/index.php</code> + mã hóa AES bảo vệ; nếu host cho phép, nên đưa ra ngoài web root bằng cách sửa <code>DATA_DIR</code> trong <code>includes/config.php</code>.
            </div>
        <?php endif; ?>

        <div class="stats">
            <div class="stat"><div class="num"><?php echo count($orders); ?></div><div class="lbl">Tổng đơn hàng</div></div>
            <div class="stat"><div class="num"><?php echo fmt_vnd($revenue); ?></div><div class="lbl">Doanh thu (không tính đã hủy)</div></div>
            <div class="stat"><div class="num"><?php echo count($messages); ?></div><div class="lbl">Tin nhắn liên hệ</div></div>
        </div>

        <div class="card">
            <h2>Đơn hàng <span>(<?php echo count($orders); ?>)</span></h2>
            <?php if (count($orders) === 0): ?>
                <div class="empty">Chưa có đơn hàng nào.</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Mã đơn</th>
                            <th>Khách hàng</th>
                            <th>Sản phẩm</th>
                            <th>Đơn giá</th>
                            <th>Thanh toán</th>
                            <th>Ngày đặt</th>
                            <th>Trạng thái</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <?php
                                $code     = (string)($order['orderCode'] ?? '');
                                $customer = $order['customer'] ?? [];
                                $name     = storage_decrypt_field($customer['fullname'] ?? null);
                                $phone    = storage_decrypt_field($customer['phone'] ?? null);
                                $address  = storage_decrypt_field($customer['address'] ?? null);
                                $note     = storage_decrypt_field($customer['note'] ?? null);
                                $items    = is_array($order['items'] ?? null) ? $order['items'] : [];
                                $itemText = implode('; ', array_map(function ($it) {
                                    return esc($it['name'] ?? '') . ' x' . (int)($it['quantity'] ?? 1);
                                }, $items));
                                $status = (string)($order['status'] ?? 'mới');
                                $pm     = (string)($order['paymentMethod'] ?? 'cod');
                                $disc   = (int)($order['discount'] ?? 0);
                            ?>
                            <tr>
                                <td data-label="Mã đơn"><strong><?php echo esc($code); ?></strong></td>
                                <td data-label="Khách hàng">
                                    <?php echo esc($name); ?><br>
                                    <a href="tel:<?php echo esc($phone); ?>"><?php echo esc($phone); ?></a>
                                    <?php if ($address !== ''): ?><br><span style="color:#999"><?php echo esc($address . ', ' . $customer['district'] . ', ' . $customer['city']); ?></span><?php endif; ?>
                                    <?php if ($note !== ''): ?><br><span style="color:#f39c12">Ghi chú: <?php echo esc($note); ?></span><?php endif; ?>
                                </td>
                                <td data-label="Sản phẩm"><?php echo $itemText; ?></td>
                                <td data-label="Đơn giá">
                                    Tạm tính: <?php echo fmt_vnd($order['subtotal'] ?? 0); ?><br>
                                    Ship: <?php echo fmt_vnd($order['shipping'] ?? 0); ?>
                                    <?php if ($disc > 0): ?><br><span style="color:#c0392b">Giảm: -<?php echo fmt_vnd($disc); ?></span><?php endif; ?>
                                    <br><strong><?php echo fmt_vnd($order['total'] ?? 0); ?></strong>
                                </td>
                                <td data-label="Thanh toán"><?php echo esc(PAYMENT_LABELS[$pm] ?? $pm); ?></td>
                                <td data-label="Ngày đặt"><?php echo esc($order['created_at'] ?? ''); ?></td>
                                <td data-label="Trạng thái">
                                    <form method="post" action="action.php">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="code" value="<?php echo esc($code); ?>">
                                        <select name="status" onchange="this.form.submit()">
                                            <?php foreach (ORDER_STATUSES as $s): ?>
                                                <option value="<?php echo esc($s); ?>" <?php echo $s === $status ? 'selected' : ''; ?>><?php echo esc($s); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                </td>
                                <td data-label="">
                                    <form method="post" action="action.php" onsubmit="return confirm('Xóa đơn hàng này?');">
                                        <input type="hidden" name="action" value="delete_order">
                                        <input type="hidden" name="code" value="<?php echo esc($code); ?>">
                                        <button type="submit" class="btn-del">Xóa</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Tin nhắn liên hệ <span>(<?php echo count($messages); ?>)</span></h2>
            <?php if (count($messages) === 0): ?>
                <div class="empty">Chưa có tin nhắn nào.</div>
            <?php else: ?>
                <div class="msg-box">
                    <?php foreach ($messages as $msg): ?>
                        <?php
                            $msgName    = storage_decrypt_field($msg['name'] ?? null);
                            $msgPhone   = storage_decrypt_field($msg['phone'] ?? null);
                            $msgEmail   = storage_decrypt_field($msg['email'] ?? null);
                            $msgSubject = storage_decrypt_field($msg['subject'] ?? null);
                            $msgBody    = storage_decrypt_field($msg['message'] ?? null);
                        ?>
                        <div class="msg-item">
                            <div class="head"><?php echo esc($msgSubject); ?></div>
                            <div class="meta">
                                <?php echo esc($msgName); ?> • <?php echo esc($msgPhone); ?>
                                <?php if ($msgEmail !== ''): ?> • <?php echo esc($msgEmail); ?><?php endif; ?>
                                • <?php echo esc($msg['created_at'] ?? ''); ?>
                            </div>
                            <div class="body"><?php echo nl2br(esc($msgBody)); ?></div>
                            <div class="msg-actions">
                                <form method="post" action="action.php" style="display:inline" onsubmit="return confirm('Xóa tin nhắn này?');">
                                    <input type="hidden" name="action" value="delete_message">
                                    <input type="hidden" name="id" value="<?php echo esc($msg['id'] ?? ''); ?>">
                                    <button type="submit" class="btn-del">Xóa</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
