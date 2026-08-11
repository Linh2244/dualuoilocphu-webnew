# Thiết kế: Admin chỉnh sửa trang chủ (hero + sản phẩm)

Ngày: 2026-08-11
Trạng thái: Được duyệt (bước brainstorm)

## Mục tiêu

Cho phép admin (trang `admin/home.php`) chỉnh sửa **phần hero banner** và **danh sách sản phẩm** trên trang chủ. Trang chủ chạy trên PHP host, nội dung được render từ file dữ liệu `data/home.data`.

Ngoài phạm vi (YAGNI): không upload ảnh, không chỉnh header/nav/footer/feature-box, không chỉnh các trang khác, không phân quyền nhiều admin.

## Kiến trúc

- `data/home.data` (JSON) — nguồn sự thật cho nội dung trang chủ. Đọc/ghi bằng `storage_read('home.data')` / `storage_save('home.data', ...)` như các dữ liệu khác. Nằm trong `data/` nên đã được `.htaccess` + `index.php` bảo vệ. Nội dung không nhạy cảm nên **không** mã hóa field.
- `index.html` → **đổi tên thành `index.php`**: giữ nguyên 100% HTML/CSS/JS hiện có (header/nav/footer, `addToCart`, style inline). Chỉ 2 vùng được PHP hoá:
  - Vùng hero: `title`, `subtitle`, `button_text`, `button_link` echo từ `$home['hero']`.
  - Vùng sản phẩm: vòng lặp `foreach` render card sản phẩm từ `$home['products']`, đúng format card hiện tại.
- **Fallback mặc định:** nếu `home.data` chưa tồn tại hoặc rỗng, dùng mảng hằng số trong PHP với giá trị = nội dung đang có (hero hiện tại + 1 sản phẩm "Dưa lưới Huỳnh Long"). Trang chủ không bao giờ trắng trước khi admin chỉnh.
- `admin/home.php` — trang quản lý mới, yêu cầu đăng nhập (redirect `login.php` nếu `$_SESSION['admin_logged_in']` rỗng). Xử lý POST ngay trong file:
  - Form **Hero**: tiêu đề, dòng phụ, chữ nút, link nút → lưu toàn bộ `home`.
  - Bảng **Sản phẩm**: mỗi dòng = form sửa (tên, giá cũ, giá mới, ảnh, % giảm) + nút Xoá; form Thêm sản phẩm.
  - Hiển thị thông báo thành công/lỗi, tự load dữ liệu hiện tại.
- Không đụng `admin/action.php` — các form ở `home.php` tự xử lý POST.

## Dữ liệu (schema)

```json
{
  "hero": {
    "title": "Dưa Lưới Tươi Sạch",
    "subtitle": "100% Từ Nông Trại VietGAP",
    "button_text": "Xem chi tiết",
    "button_link": "#"
  },
  "products": [
    {
      "name": "Dưa lưới Huỳnh Long",
      "image": "dua1.jpg",
      "old_price": 75000,
      "price": 65000,
      "discount": 10
    }
  ]
}
```

- `old_price` / `price`: số nguyên VND.
- Nếu `old_price` = 0 hoặc bằng `price` → không hiện giá cũ.
- `discount` = phần trăm trên badge (`-10%`); nhập 0 → ẩn badge.
- Link nút hero được phép là `#` (mặc định hiện tại) hoặc link thật.

## Hiển thị giá

- Giá mới: `65.000đ / kg` (như hiện tại).
- Giá cũ: `75.000đ`.
- `addToCart` nhận `price` số nguyên từ dữ liệu → giỏ hàng/thanh toán hoạt động nguyên vẹn (không đổi cơ chế cart).

## Xử lý lỗi

- Validate: `price` phải là số > 0; `old_price` ≥ 0; `discount` ≥ 0 (trong khoảng 0–99); các trường text không bắt buộc (cho phép rỗng). Lỗi hiển thị ngay trên `home.php`.
- Nếu không ghi được `home.data` → báo lỗi kèm gợi ý quyền thư mục (giống cách `setup.php` báo).

## Ảnh

- Nhập **đường dẫn/tên file ảnh** qua ô text (vd `dua1.jpg`). Không upload file.

## Danh sách file thay đổi

- `index.html` → đổi tên `index.php` + PHP hoá hero & vùng sản phẩm.
- Đồng bộ link trỏ tới trang chủ (đổi `index.html` → `index.php`):
  - Nav "Trang chủ" trong `san-pham.html`, `gioi-thieu.html`, `lien-he.html`, `thanh-toan.html`.
  - `thanh-toan.html` — `window.location.href = 'index.html'` sau khi đặt hàng thành công.
  - `admin/index.php` — link "Xem website".
  - `admin/login.php` — link "← Về trang chủ".
- `admin/home.php` — mới (form hero + quản lý sản phẩm, session-guarded).
- `AGENTS.md` — cập nhật: `index.php` thay `index.html` (trang chủ), ghi chú dữ liệu `home.data`.
- `data/home.data` — file runtime, không commit (mẫu `data/*.data` đã có trong `.gitignore`).
