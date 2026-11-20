# Ăn Lành Mạnh - Web hỗ trợ sức khỏe bữa ăn

Website PHP thuần + MySQL với đầy đủ chức năng khách hàng và quản trị, lấy cảm hứng từ cấu trúc mẫu OpenCart/bookstore nhưng chuyển sang chủ đề **bữa ăn lành mạnh**.

## 🌐 Website chính thức

[**https://kienhoa.infinityfree.io**](https://kienhoa.infinityfree.io)

- Admin: `admin` / `123` → trang quản trị
- Khách: `user1@example.com` / `123456` → trang khách hàng

> 🔒 **Toàn bộ website bị khóa cho đến khi đăng nhập.** Truy cập bất kỳ URL nào cũng tự động chuyển về `dangnhap.php`; chỉ trang đăng nhập và đăng ký là mở. Sau khi đăng nhập, hệ thống tự điều hướng theo vai trò: `admin` → `admin/index.php`, `user` → `index.php`.

## 1. Chức năng chính

### Khách hàng
- Trang chủ, thực đơn theo danh mục, tìm kiếm & lọc theo calo.
- Xem chi tiết món ăn: thành phần, dinh dưỡng, lượt xem, đánh giá.
- Đăng ký / đăng nhập / đăng xuất; hồ sơ cá nhân có chiều cao, cân nặng, mục tiêu.
- Tính **BMI** và **nhu cầu calo/ngày**, gợi ý thực đơn 4 bữa (Sáng/Trưa/Tối/Phụ).
- Giỏ hàng (session), cập nhật số lượng, tổng tiền + tổng calo.
- Đặt bữa, lịch sử đơn hàng, đánh giá/bình luận (chờ admin duyệt).

### Quản trị (`/admin`)
- Dashboard: món ăn, đơn hàng, doanh thu, khách hàng, bình luận chờ.
- CRUD: danh mục, nguồn công thức, món ăn (upload ảnh ≤ 2 MB lưu vào `images/product/`).
- Quản lý đơn hàng: xem chi tiết, đổi trạng thái (Chờ → Chuẩn bị → Đang giao → Hoàn tất/Hủy).
- Quản lý khách hàng: tìm kiếm, khóa/mở tài khoản.
- Quản lý bình luận: duyệt, ẩn, xóa.
- Thống kê: doanh thu 6 tháng, trạng thái đơn, top món bán chạy.

## 2. Cấu trúc thư mục

```
php_DoAn/
├── config.php                # Cấu hình DB (bị .gitignore, do CI tạo khi deploy)
├── config.example.php        # Mẫu cấu hình
├── index.php, monan.php, danhmuc.php, timkiem.php, goiy.php
├── giohang.php, datban.php, dangky.php, dangnhap.php, logout.php
├── taikhoan.php, binhluan.php
├── includes/                 # db, functions, auth, header, footer
├── css/, js/, images/        # images/product chứa ảnh món ăn
├── sql/meal_health.sql       # Schema + dữ liệu mẫu
├── admin/                    # Giao diện quản trị
└── .github/workflows/deploy.yml
```

## 3. Chạy local bằng XAMPP

1. Copy thư mục vào `htdocs/` (ví dụ `htdocs/meal_health`). Đảm bảo PHP đã bật extension `pdo_mysql` (XAMPP mặc định có).
2. Tạo database `meal_health`: mở **phpMyAdmin** → New database → sau đó **Import** file `sql/meal_health.sql`.
3. Đổi `config.php` nếu cần (mặc định `root` / password rỗng):
   ```php
   return [
       'db_host' => 'localhost',
       'db_name' => 'meal_health',
       'db_user' => 'root',
       'db_pass' => '',
   ];
   ```
4. Truy cập: `http://localhost/meal_health`

## 3b. Chạy local bằng `php -S` (Arch / CachyOS)

Trên Arch/CachyOS, extension MySQL đã có trong package `php` (không cần cài `php-mysql`), chỉ cần bật bằng file `php.dev.ini` có sẵn trong project.

```bash
# 1. Khởi tạo MariaDB (chỉ cần làm 1 lần)
sudo mariadb-install-db --user=mysql --basedir=/usr --datadir=/var/lib/mysql
sudo systemctl enable --now mariadb

# 2. Tạo database + user riêng
sudo mariadb -e "CREATE DATABASE IF NOT EXISTS meal_health CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mariadb -e "CREATE USER IF NOT EXISTS 'mealuser'@'localhost' IDENTIFIED BY 'mealpass'; GRANT ALL ON meal_health.* TO 'mealuser'@'localhost'; FLUSH PRIVILEGES;"

# 3. Import dữ liệu mẫu
sudo mariadb meal_health < sql/meal_health.sql
```

`config.php` đã được set sẵn như sau (bạn có thể đổi nếu muốn):

```php
return [
    'db_host' => 'localhost',
    'db_name' => 'meal_health',
    'db_user' => 'mealuser',
    'db_pass' => 'mealpass',
];
```

Chạy server (file `php.dev.ini` đã bật `pdo_mysql`):

```bash
cd /home/kienhoa/Projects/php_DoAn
php -c php.dev.ini -S localhost:8000
```

Mở <http://localhost:8000>. Form đăng nhập duy nhất là <http://localhost:8000/dangnhap.php>.

### Tài khoản mẫu

| Vai trò | Email | Mật khẩu |
|---|---|---|
| Admin | `admin` (hoặc `admin@mealhealth.local`) | `123` |
| Khách | `user1@example.com` | `123456` |
| Khách | `user2@example.com` | `123456` |

Đăng nhập tại `http://localhost/meal_health/dangnhap.php` bằng `amin / 123` → tự động vào admin panel (`admin/index.php`).

## 4. Deploy lên InfinityFree (free forever, không hết hạn)

### 4.1. Chuẩn bị trên InfinityFree
1. Đăng ký tại <https://infinityfree.com> → tạo **subdomain** (ví dụ `tentoi.infinityfreeapp.com`).
2. Vào **MySQL Databases** → tạo database + user, lưu lại host/db/user/pass.
3. Vào **phpMyAdmin** → chọn database vừa tạo → **Import** `sql/meal_health.sql`.
4. Vào **FTP Details** → lấy FTP hostname, username, password (hostname thường là `ftpupload.net`).
   - FTP server dir thường là `/htdocs/`.

### 4.2. Thiết lập GitHub Secrets
Trong repo GitHub → **Settings → Secrets and variables → Actions → New repository secret**, tạo 8 secrets:

| Secret | Giá trị |
|---|---|
| `FTP_SERVER` | `ftpupload.net` |
| `FTP_USERNAME` | FTP username từ InfinityFree |
| `FTP_PASSWORD` | FTP password từ InfinityFree |
| `FTP_SERVER_DIR` | `/htdocs/` |
| `DB_HOST` | MySQL host từ InfinityFree |
| `DB_NAME` | Tên database |
| `DB_USER` | User database |
| `DB_PASS` | Password database |

### 4.3. Deploy
- Push code lên nhánh `main`.
- GitHub Actions sẽ:
  1. Tạo `config.php` từ 4 secrets `DB_*`.
  2. FTP upload toàn bộ source lên `/htdocs/` (bỏ qua `.git`, `.github`, `sql/`, `README.md`).
- Mở subdomain để dùng. Mỗi lần `git push` sẽ tự cập nhật website.

> Nếu FTP báo lỗi `451`, chỉ cần vào Actions → **Re-run jobs**.

## 5. Ghi chú

- Ảnh món ăn lưu trực tiếp trên host tại `images/product/` (giới hạn 2 MB/file, InfinityFree cho phép tối đa 10 MB/file).
- Session dùng file mặc định của PHP (shared hosting có disk).
- Free tier InfinityFree: 5 GB disk, free SSL, không quảng cáo, **không có ngày hết hạn**. Nếu không có truy cập ~20–60 ngày, tài khoản có thể bị tạm ngưng — chỉ cần vào lại là kích hoạt.
- Thông tin dinh dưỡng mang tính tham khảo, không thay thế tư vấn y tế.
