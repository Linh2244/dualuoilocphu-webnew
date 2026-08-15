# Admin chỉnh sửa trang chủ (hero + sản phẩm) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Cho admin chỉnh sửa hero banner + danh sách sản phẩm trên trang chủ qua `admin/home.php`, trang chủ render nội dung từ `data/home.data`.

**Architecture:** Đổi `index.html` thành `index.php`; hero + vùng sản phẩm render bằng PHP từ mảng đọc qua `storage_read('home.data')`, có fallback mặc định khi file chưa tồn tại. `admin/home.php` mới (session-guarded) cung cấp form sửa hero và quản lý sản phẩm (thêm/sửa/xoá), ghi bằng `storage_save('home.data', ...)`.

**Tech Stack:** PHP 7+ (không composer, không MySQL), JSON file storage, HTML/CSS/vanilla JS. Không có test framework — bước verify dùng kiểm tra cú pháp PHP + đọc lại cấu trúc file.

## Global Constraints

- Backend chỉ PHP + JSON files; không thêm thư viện.
- Toàn bộ nội dung mới viết bằng tiếng Việt.
- `index.php` phải giữ nguyên 100% HTML/CSS/JS của `index.html` hiện tại (header/nav/footer, `addToCart`, style inline) — chỉ PHP hoá 2 vùng: hero và sản phẩm.
- `admin/home.php` phải redirect về `login.php` nếu `$_SESSION['admin_logged_in']` rỗng (giống các admin page khác).
- Dữ liệu ghi bằng `storage_save('home.data', ...)`, đọc bằng `storage_read('home.data')`.
- File dữ liệu có `.data` extension (không phải `.json`).
- Price lưu dạng số nguyên VND; hiển thị `65.000đ / kg`.
- Nếu `old_price` = 0 hoặc bằng `price` → ẩn giá cũ. Nếu `discount` = 0 → ẩn badge.
- Fallback mặc định khi `home.data` chưa tồn tại: hero `{title: "Dưa Lưới Tươi Sạch", subtitle: "100% Từ Nông Trại VietGAP", button_text: "Xem chi tiết", button_link: "#"}` và 1 sản phẩm `{name: "Dưa lưới Huỳnh Long", image: "dua1.jpg", old_price: 75000, price: 65000, discount: 10}`.

---

### Task 1: Tạo `index.php` từ `index.html` và PHP hoá vùng hero

**Files:**
- Rename: `index.html` → `index.php`
- Modify: `index.php`

**Interfaces:**
- Consumes: `storage.php` (`storage_read`, `storage_decrypt_field` không dùng ở đây), `config.php` (qua require).
- Produces: Trang chủ render hero từ `$home['hero']`; giữ nguyên mọi phần còn lại.

- [ ] **Step 1: Đổi tên file**

Run: `git mv index.html index.php`
Expected: file mới tồn tại, git nhận rename.

- [ ] **Step 2: Thêm require + load dữ liệu đầu file**

Sau `<!DOCTYPE html>`... thực tế PHP phải ở đầu file. Sửa phần mở đầu thành:

```php
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
```

(Lưu ý: nếu file đã có `<!DOCTYPE html>` ở dòng 1, thay thế 2 dòng đầu `<!DOCTYPE html>\n<html lang="vi">` bằng khối PHP trên + phần khai báo còn lại.)

- [ ] **Step 3: PHP hoá vùng hero**

Thay thế khối:

```html
<section class="hero-banner">
    <div class="container">
        <h2>Dưa Lưới Tươi Sạch</h2>
        <p>100% Từ Nông Trại VietGAP</p>
        <a href="#" class="btn-primary">Xem chi tiết</a>
    </div>
</section>
```

bằng:

```php
<section class="hero-banner">
    <div class="container">
        <h2><?php echo esc_home($hero['title'] ?? ''); ?></h2>
        <p><?php echo esc_home($hero['subtitle'] ?? ''); ?></p>
        <a href="<?php echo esc_home($hero['button_link'] ?? '#'); ?>" class="btn-primary"><?php echo esc_home($hero['button_text'] ?? 'Xem chi tiết'); ?></a>
    </div>
</section>
```

- [ ] **Step 4: Xoá file `index.html` cũ (nếu còn)**

Run: `if (Test-Path index.html) { Remove-Item index.html }`
Expected: chỉ còn `index.php`.

- [ ] **Step 5: Verify cú pháp**

Run: `php -l index.php` (hoặc so khớp cặp `<?php`/`?>` / dấu `{}`/`()`).
Expected: Không lỗi cú pháp. Hero banner render được.

- [ ] **Step 6: Commit**

```bash
git add index.php
git commit -m "feat: render home page hero from data/home.data"
```

---

### Task 2: PHP hoá vùng sản phẩm trong `index.php`

**Files:**
- Modify: `index.php` (phần `.product-grid`)

**Interfaces:**
- Consumes: `$products` từ Task 1, hàm `esc_home()`, `fmt_price_home()`, `addToCart` JS (giữ nguyên).
- Produces: Grid sản phẩm render động từ `$products`.

- [ ] **Step 1: Thay khối product-grid tĩnh bằng vòng lặp**

Thay thế:

```html
<div class="product-grid">
    <div class="product-card">
        <div class="product-image">
            <img src="dua1.jpg" alt="Dưa lưới Huỳnh Long">
            <span class="discount-badge">-10%</span>
        </div>
        <h3 class="product-name">Dưa lưới Huỳnh Long</h3>
        <div class="product-price">
            <span class="old-price">75.000đ</span>
            <span class="new-price">65.000đ / kg</span>
        </div>
        <a href="#" class="btn-buy" onclick="addToCart(event, 'Dưa lưới Huỳnh Long', 65000, 'dua1.jpg')">Mua ngay</a>
    </div>
</div>
```

bằng:

```php
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
```

- [ ] **Step 2: Verify cú pháp**

Run: `php -l index.php`
Expected: Không lỗi. Với `home.data` chưa tồn tại, trang render đúng 1 sản phẩm Huỳnh Long như cũ.

- [ ] **Step 3: Commit**

```bash
git add index.php
git commit -m "feat: render home page products dynamically"
```

---

### Task 3: Đồng bộ link `index.html` → `index.php`

**Files:**
- Modify: `san-pham.html:208`, `gioi-thieu.html:235`, `lien-he.html:305`, `thanh-toan.html:390`, `thanh-toan.html:767`, `admin/index.php:88`, `admin/login.php:72`

**Interfaces:**
- Consumes: không.
- Produces: Mọi đường dẫn trỏ về trang chủ dùng `index.php`.

- [ ] **Step 1: Đổi `href="index.html"` → `href="index.php"` trong nav các trang**

Tìm và thay trong `san-pham.html`, `gioi-thieu.html`, `lien-he.html`, `thanh-toan.html` (mỗi file 1 chỗ, trong `<nav class="main-nav">`): `href="index.html"` → `href="index.php"`.

- [ ] **Step 2: Đổi JS redirect trong `thanh-toan.html:767`**

Thay `window.location.href = 'index.html';` → `window.location.href = 'index.php';`

- [ ] **Step 3: Đổi link admin**

- `admin/index.php:88`: `<a href="../index.html">Xem website</a>` → `href="../index.php"`.
- `admin/login.php:72`: `<a href="../index.html">← Về trang chủ</a>` → `href="../index.php"`.

- [ ] **Step 4: Verify**

Run: `rg -n "index\.html"` — kết quả: **không còn** match nào ngoài file spec `docs/` và `AGENTS.md` (nếu còn, sửa nốt).
Expected: không còn link tới `index.html` trong code.

- [ ] **Step 5: Commit**

```bash
git add san-pham.html gioi-thieu.html lien-he.html thanh-toan.html admin/index.php admin/login.php
git commit -m "refactor: point home links to index.php"
```

---

### Task 4: Tạo `admin/home.php` — form hero + quản lý sản phẩm

**Files:**
- Create: `admin/home.php`

**Interfaces:**
- Consumes: `storage_read('home.data')`, `storage_save('home.data', ...)`, `$_SESSION['admin_logged_in']`, `esc()` (định nghĩa riêng trong file), schema từ spec.
- Produces: Trang quản lý tự xử lý POST: hành động `save_hero`, `add_product`, `update_product`, `delete_product`. Sau xử lý redirect `header('Location: home.php')` (PRG) rồi hiển thị thông báo qua query `?ok=...`.

- [ ] **Step 1: Viết logic PHP xử lý POST + đọc dữ liệu**

Tạo `admin/home.php` với cấu trúc:

```php
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
```

- [ ] **Step 2: Viết phần HTML — form hero**

Tiếp nối file, thêm HTML với style tương tự `admin/index.php` (topbar + `.wrap`). Form hero có 4 ô input (`title`, `subtitle`, `button_text`, `button_link`) + nút lưu, `name="action" value="save_hero"`. Hiển thị `$ok`/`$err`:

```php
<?php if ($err !== ''): ?><div class="warn" style="color:#c0392b"><?php echo esc_home_admin($err); ?></div><?php endif; ?>
<?php if ($ok === 'hero'): ?><div style="background:#e8f8f0;color:#27ae60;padding:10px 15px;border-radius:6px;margin-bottom:20px">Đã lưu hero thành công.</div><?php endif; ?>
```

- [ ] **Step 3: Viết phần HTML — form thêm sản phẩm + bảng sản phẩm**

- Form "Thêm sản phẩm": 5 ô (`name`, `image`, `old_price`, `price`, `discount`) + nút, `action=add_product`.
- Bảng: mỗi sản phẩm là một form riêng hàng dọc, `action=update_product` kèm `<input type="hidden" name="idx" value="N">`, các ô input điền giá trị hiện tại (`value="<?php echo esc_home_admin($p['name'] ?? ''); ?>"`), nút "Cập nhật".
- Mỗi dòng thêm form `action=delete_product` với `<input type="hidden" name="idx" value="N">` + nút "Xoá" (onsubmit confirm).
- Thông báo `$ok` cho `add`/`update`/`delete` tương ứng.
- Nếu `$home['products']` rỗng → hiện "Chưa có sản phẩm nào."

- [ ] **Step 4: Verify cú pháp + cấu trúc**

Run: `php -l admin/home.php`
Expected: Không lỗi cú pháp; file chứa đủ 4 hành động POST và redirect PRG.

- [ ] **Step 5: Thêm link "Trang chủ" vào topbar admin**

Modify `admin/index.php:88-90` — thêm trước link "Xem website":

```php
<a href="home.php">Chỉnh trang chủ</a>
```

- [ ] **Step 6: Verify toàn bộ cú pháp PHP**

Run: vòng lặp `php -l` qua tất cả file `.php` trong `admin/`, `api/`, `includes/`, `index.php`.
Expected: Tất cả OK.

- [ ] **Step 7: Commit**

```bash
git add admin/home.php admin/index.php
git commit -m "feat: add admin page to edit home page content"
```

---

### Task 5: Cập nhật tài liệu

**Files:**
- Modify: `AGENTS.md`

**Interfaces:**
- Consumes: không.
- Produces: Tài liệu khớp với hành vi mới.

- [ ] **Step 1: Cập nhật AGENTS.md**

- Dòng "Pages:" đổi `index.html` (home) → `index.php` (home).
- Thêm ghi chú: `index.php` render hero + sản phẩm từ `data/home.data` (admin chỉnh qua `admin/home.php`); nếu file chưa tồn tại dùng fallback mặc định.
- Thêm dòng về `admin/home.php` trong mục Admin.

- [ ] **Step 2: Verify**

Run: `rg -n "index\.html"` — không còn match trong code (chỉ có thể còn trong spec `docs/`).
Expected: tài liệu khớp code.

- [ ] **Step 3: Commit**

```bash
git add AGENTS.md
git commit -m "docs: document home page content editing"
```

---

## Self-Review

**Spec coverage:**
- hero editable (`admin/home.php` form hero) → Task 4 ✓
- products CRUD → Task 4 ✓
- render từ `data/home.data` → Task 1-2 ✓
- fallback mặc định → Task 1 ✓
- link đồng bộ `index.php` → Task 3 ✓
- `.data` extension, storage helpers → đúng ✓
- AGENTS.md → Task 5 ✓
- Ảnh là text field (không upload) → Task 4 ✓

**Placeholder scan:** Không có TBD/TODO. Mọi bước có code/giá trị cụ thể.

**Type consistency:** `$home['hero']`, `$home['products']`, `storage_read('home.data')`/`storage_save('home.data', ...)`, `esc_home()`/`esc_home_admin()`, `fmt_price_home()` nhất quán giữa các task. Schema sản phẩm dùng đúng tên `name/image/old_price/price/discount`.
