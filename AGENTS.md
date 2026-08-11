# AGENTS.md

Vietnamese e-commerce site for "Dưa Lưới Lộc Phú" (melons). Frontend is plain HTML + CSS + vanilla JS (no build step); **backend is plain PHP + JSON files** (no composer, no MySQL).

## Deploy / verify
- Static pages deploy via GitHub Pages (push to `main`, origin `Linh2244/dualuoilocphu-webnew`; `CNAME` → `web.linh.qzz.io`), but the PHP backend **cannot run on GitHub Pages**. Full site (forms + checkout) requires a PHP host (cPanel/shared, XAMPP locally). No build step.
- Upload layout on the PHP host: put everything **except `data/`** inside the web root (`htdocs/`); let PHP create the sibling `data/` folder next to it (or upload `data/` alongside `htdocs/`).
- Verify static layout by opening `.html` files directly; verify backend endpoints by serving the folder from a PHP-capable server (e.g. XAMPP `htdocs`) and visiting `/admin/setup.php`, then the site pages.

## Structure
- Pages: `index.php` (home), `san-pham.html` (products), `thanh-toan.html` (checkout), `gioi-thieu.html` (about), `lien-he.html` (contact). All content is Vietnamese — write new content in Vietnamese.
- `index.php` is a PHP page: the hero + product list are rendered from `data/home.data` (edited via `admin/home.php`); if that file doesn't exist yet, it falls back to a default hero/product set.
- Shared CSS is in `style.css`; every page also has its own inline `<style>` block for page-specific styles. Keep that convention: put page-specific rules in the page's inline block, not `style.css`.
- Each page **duplicates the full header/nav/footer markup inline** (no templating). When adding or editing a page:
  - Copy the `<header>`/`<footer>` blocks from an existing page.
  - Mark the current page's nav link with `class="active"` and remove it from others.
  - Keep all nav `href`s pointing to the other page files.

## PHP backend
- `includes/config.php` (paths, coupon codes, fees) + `includes/storage.php` (JSON read/write helpers with `flock` for concurrent writes). Backend is **duplicated nowhere** — single copy in `includes/`.
- Endpoints (JSON POST, `Content-Type: application/json`):
  - `api/order.php` — saves checkout from `thanh-toan.html` to `data/orders.data`. **Recalculates subtotal/shipping/discount/total server-side** (client values are never trusted; coupon `GIAM10`/`GIAM50K` re-validated). Falls back to generating an order code if client sent none.
  - `api/contact.php` — saves `lien-he.html` messages to `data/messages.data`; has a `website` honeypot field (must be empty).
- Storage: runtime data (`orders.data`, `messages.data`, `admin.data`, `encryption.key`) lives in `DATA_DIR` (web root may be `public_html`, `htdocs`, `www`, ... — resolved via `DOCUMENT_ROOT`). `config.php` **auto-detects**: prefers the sibling outside the web root (`dirname(DOCUMENT_ROOT) . '/data'`), but if the host blocks writing there (e.g. InfinityFree's `open_basedir` only allows `htdocs`), it silently falls back to in-web-root `htdocs/data`. Configurable via `define('DATA_DIR', ...)`. Admin shows a banner when it resolves inside the web root. The dir needs write permission on the host.
- Data protection: PII (order `fullname/phone/email/address/note`; message `name/phone/email/subject/message`) is **encrypted AES-256-GCM** (`enc:` prefix) before writing; `storage_decrypt_field()` falls back to plaintext so pre-encryption records still display. Requires the PHP `openssl` extension. The key is `random_bytes(32)` stored in `encryption.key` (gitignored, outside web root) — **back it up with the data**. Data files use a `.data` extension (not `.json`) and are only ever read by PHP; direct HTTP into `data/` is denied by `data/.htaccess` + `data/index.php`, and `curl data/orders.data` returns 403/404 — PHP is the only door. Defense-in-depth kept in-repo: `data/.htaccess` (`Require all denied` + `Options -Indexes`) and `data/index.php` (404 guard).
- Admin at `admin/`: `setup.php` (one-time admin account creation, auto-disabled once `data/admin.data` exists), `login.php`/`logout.php` (session + `password_verify`), `index.php` (order/message lists, status dropdown, delete), `action.php` (POST actions, session-guarded), `home.php` (edit home page hero + products, saves to `data/home.data`; handles its own POSTs via `storage_save`, images are plain text fields). All admin pages redirect to `login.php` if not authenticated.

## Cart (the non-obvious part)
- Cart state lives in `localStorage` under key `cart`: JSON array of `{name, price, image, quantity}`.
- `addToCart(event, name, price, image)` is **duplicated inline per page**. A behavior change to add-to-cart must be applied to every page's `<script>` that contains it, or it will diverge.
- Prices are VND with Vietnamese formatting: display like `65.000đ / kg`; checkout formats via `Intl.NumberFormat('vi-VN', ...)`. Coupon codes recognized in checkout: `GIAM10`, `GIAM50K`.
- `thanh-toan.html` auto-generates an order code (`DH` + date + time + 3-digit random) via `generateOrderCode()` on page load, shows it in the order summary, pre-fills the QR/bank transfer content with it, and `completeOrder()` reuses it (regenerates only as a fallback if empty). The bank-transfer content also live-updates from the "Họ và tên" field.
- `completeOrder()` POSTs the order to `api/order.php` via `fetch`; cart is cleared only on a successful response. The contact form in `lien-he.html` POSTs to `api/contact.php` the same way. If the site is served without PHP (e.g. file:// or GitHub Pages), these fetches fail gracefully with an alert.

## Notes
- Remaining `href="#"` links are intentional (e.g. `btn-buy` buttons driven by `onclick`, the hero "Xem chi tiết" button), not broken nav links.
