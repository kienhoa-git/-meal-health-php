-- ============================================================
-- DATABASE: meal_health - Web hỗ trợ sức khỏe bữa ăn
-- Tương thích MySQL 8 / MariaDB 10+
-- CÁCH DÙNG:
--   - Local: tạo database meal_health rồi import file này.
--   - InfinityFree: tạo database trong控制面板, chọn database đó
--     trong phpMyAdmin rồi Import file này (không cần CREATE DATABASE).
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS chi_tiet_goi_y;
DROP TABLE IF EXISTS goi_y_bua_an;
DROP TABLE IF EXISTS binh_luan;
DROP TABLE IF EXISTS chi_tiet_don_hang;
DROP TABLE IF EXISTS don_hang;
DROP TABLE IF EXISTS mon_an;
DROP TABLE IF EXISTS nguoi_dung;
DROP TABLE IF EXISTS nguon_cong_thuc;
DROP TABLE IF EXISTS danh_muc;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. Danh mục bữa ăn
CREATE TABLE danh_muc (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ten VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    mo_ta TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Nguồn công thức (tương đương "nhà xuất bản" trong mẫu bookstore)
CREATE TABLE nguon_cong_thuc (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ten VARCHAR(150) NOT NULL,
    mo_ta TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Món ăn / bữa ăn
CREATE TABLE mon_an (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ten VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL UNIQUE,
    id_danh_muc INT NOT NULL,
    id_nguon INT NOT NULL,
    mo_ta TEXT,
    nguyen_lieu TEXT,
    calo INT NOT NULL DEFAULT 0,
    protein DECIMAL(5,1) NOT NULL DEFAULT 0,
    carb DECIMAL(5,1) NOT NULL DEFAULT 0,
    fat DECIMAL(5,1) NOT NULL DEFAULT 0,
    gia DECIMAL(10,2) NOT NULL DEFAULT 0,
    hinh_anh VARCHAR(255) NOT NULL DEFAULT 'images/product/default.svg',
    trang_thai TINYINT(1) NOT NULL DEFAULT 1,
    luot_xem INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_danh_muc) REFERENCES danh_muc(id) ON DELETE CASCADE,
    FOREIGN KEY (id_nguon) REFERENCES nguon_cong_thuc(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Người dùng (user + admin)
CREATE TABLE nguoi_dung (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ho_ten VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    mat_khau VARCHAR(255) NOT NULL,
    gioi_tinh ENUM('nam','nu','khac') NOT NULL DEFAULT 'khac',
    tuoi INT NOT NULL DEFAULT 25,
    chieu_cao DECIMAL(5,2) NOT NULL DEFAULT 165.00,
    can_nang DECIMAL(5,2) NOT NULL DEFAULT 60.00,
    muc_do_hoat_dong ENUM('thap','trung_binh','cao') NOT NULL DEFAULT 'trung_binh',
    muc_tieu ENUM('giam_can','tang_can','duy_tri') NOT NULL DEFAULT 'duy_tri',
    dien_thoai VARCHAR(20) DEFAULT NULL,
    dia_chi TEXT,
    vai_tro ENUM('user','admin') NOT NULL DEFAULT 'user',
    trang_thai TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Đơn hàng
CREATE TABLE don_hang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_nguoi_dung INT NOT NULL,
    ho_ten VARCHAR(150) NOT NULL,
    dien_thoai VARCHAR(20) NOT NULL,
    dia_chi TEXT NOT NULL,
    tong_tien DECIMAL(10,2) NOT NULL,
    tong_calo INT NOT NULL DEFAULT 0,
    trang_thai ENUM('cho_xac_nhan','dang_chuan_bi','dang_giao','hoan_tat','huy') NOT NULL DEFAULT 'cho_xac_nhan',
    ghi_chu TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_nguoi_dung) REFERENCES nguoi_dung(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Chi tiết đơn hàng
CREATE TABLE chi_tiet_don_hang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_don_hang INT NOT NULL,
    id_mon_an INT NOT NULL,
    ten_mon_an VARCHAR(200) NOT NULL,
    so_luong INT NOT NULL,
    gia DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_don_hang) REFERENCES don_hang(id) ON DELETE CASCADE,
    FOREIGN KEY (id_mon_an) REFERENCES mon_an(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Bình luận / đánh giá
CREATE TABLE binh_luan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_mon_an INT NOT NULL,
    id_nguoi_dung INT NOT NULL,
    danh_gia TINYINT NOT NULL DEFAULT 5,
    noi_dung TEXT NOT NULL,
    trang_thai TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_mon_an) REFERENCES mon_an(id) ON DELETE CASCADE,
    FOREIGN KEY (id_nguoi_dung) REFERENCES nguoi_dung(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Gợi ý thực đơn
CREATE TABLE goi_y_bua_an (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_nguoi_dung INT NOT NULL,
    ngay DATETIME NOT NULL,
    muc_tieu VARCHAR(50) NOT NULL,
    tong_calo INT NOT NULL,
    ghi_chu TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_nguoi_dung) REFERENCES nguoi_dung(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Chi tiết gợi ý
CREATE TABLE chi_tiet_goi_y (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_goi_y INT NOT NULL,
    id_mon_an INT NOT NULL,
    bua VARCHAR(20) NOT NULL,
    FOREIGN KEY (id_goi_y) REFERENCES goi_y_bua_an(id) ON DELETE CASCADE,
    FOREIGN KEY (id_mon_an) REFERENCES mon_an(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DỮ LIỆU MẪU
-- ============================================================

INSERT INTO danh_muc (id, ten, slug, mo_ta) VALUES
(1, 'Bữa sáng', 'bua-sang', 'Khởi đầu ngày mới đủ năng lượng'),
(2, 'Bữa trưa', 'bua-trua', 'Bữa chính giàu dinh dưỡng'),
(3, 'Bữa tối', 'bua-toi', 'Nhẹ bụng, dễ tiêu hóa'),
(4, 'Bữa phụ', 'bua-phu', 'Snack lành mạnh giữa các bữa'),
(5, 'Đồ uống', 'do-uong', 'Nước uống tự nhiên, ít đường');

INSERT INTO nguon_cong_thuc (id, ten, mo_ta) VALUES
(1, 'Bếp ăn Healthy', 'Công thức do đầu bếp của chúng tôi xây dựng'),
(2, 'Chuyên gia dinh dưỡng', 'Công thức được tư vấn bởi chuyên gia'),
(3, 'Công thức gia đình', 'Món ăn truyền thống được cải tiến');

INSERT INTO mon_an (id, ten, slug, id_danh_muc, id_nguon, mo_ta, nguyen_lieu, calo, protein, carb, fat, gia, hinh_anh, trang_thai, luot_xem) VALUES
(1, 'Yến mạch chuối & sữa chua', 'yen-mach-chuoi-sua-chua', 1, 2, 'Bữa sáng giàu chất xơ, giúp no lâu và ổn định đường huyết.', 'Yến mạch, chuối, sữa chua không đường, mật ong, quế.', 320, 14.0, 48.0, 8.0, 45000, 'images/product/pro01.svg', 1, 128),
(2, 'Trứng bác cà chua', 'trung-bac-ca-chua', 1, 3, 'Món sáng đơn giản, nhiều protein, ít calo.', 'Trứng gà, cà chua, hành lá, dầu olive, gia vị.', 280, 18.0, 12.0, 16.0, 40000, 'images/product/pro02.svg', 1, 96),
(3, 'Bánh mì ngũ cốc bơ hạt', 'banh-mi-ngu-coc-bo-hat', 1, 1, 'Ngũ cốc nguyên hạt kết hợp bơ hạt tự nhiên.', 'Bánh mì ngũ cốc, bơ hạt, sữa chua, rau xà lách.', 340, 12.0, 45.0, 13.0, 42000, 'images/product/pro03.svg', 1, 74),
(4, 'Cơm gạo lứt gà nướng', 'com-gao-lut-ga-nuong', 2, 1, 'Bữa trưa cân bằng đạm nạc và tinh bột chậm.', 'Gạo lứt, ức gà, rau luộc, dầu olive, gia vị.', 520, 38.0, 62.0, 14.0, 79000, 'images/product/pro04.svg', 1, 210),
(5, 'Salad cá hồi quinoa', 'salad-ca-hoi-quinoa', 2, 2, 'Giàu omega-3, phù hợp cho mục tiêu giảm cân.', 'Cá hồi, quinoa, rau rocket, dưa leo, chanh, dầu olive.', 480, 34.0, 40.0, 20.0, 89000, 'images/product/pro05.svg', 1, 187),
(6, 'Bún chay rau củ', 'bun-chay-rau-cu', 2, 3, 'Bữa trưa thanh đạm, nhiều rau.', 'Bún gạo lứt, đậu phụ, cà rốt, bông cải, nước dùng chay.', 380, 15.0, 60.0, 8.0, 55000, 'images/product/pro06.svg', 1, 143),
(7, 'Cá hồi nướng rau củ', 'ca-hoi-nuong-rau-cu', 3, 2, 'Bữa tối nhẹ bụng, giàu axit béo tốt.', 'Cá hồi, khoai lang, bông cải xanh, chanh, thì là.', 450, 40.0, 25.0, 20.0, 95000, 'images/product/pro07.svg', 1, 165),
(8, 'Ức gà xào rau xanh', 'uc-ga-xao-rau-xanh', 3, 1, 'Protein nạc, ít dầu mỡ, dễ chuẩn bị.', 'Ức gà, rau bina, nấm, tỏi, dầu olive.', 410, 42.0, 18.0, 17.0, 78000, 'images/product/pro08.svg', 1, 152),
(9, 'Đậu phụ sốt cà chua', 'dau-phu-sot-ca-chua', 3, 3, 'Món chay đạm, giàu canxi và đạm thực vật.', 'Đậu phụ, cà chua, hành, tỏi, dầu olive.', 360, 20.0, 28.0, 18.0, 58000, 'images/product/pro09.svg', 1, 88),
(10, 'Sữa chua mật ong hạt chia', 'sua-chua-mat-ong-hat-chia', 4, 2, 'Bữa phụ giúp tiêu hóa tốt và no lâu.', 'Sữa chua không đường, mật ong, hạt chia, dâu tây.', 180, 9.0, 22.0, 7.0, 32000, 'images/product/pro10.svg', 1, 119),
(11, 'Táo nghiền bơ hạt', 'tao-nghien-bo-hat', 4, 1, 'Snack tự nhiên, không đường tinh luyện.', 'Táo, bơ hạt, quế, nước chanh.', 210, 5.0, 30.0, 9.0, 35000, 'images/product/pro11.svg', 1, 67),
(12, 'Nước xanh rau bina & chanh', 'nuoc-xanh-rau-bina-chanh', 5, 2, 'Đồ uống giải khát, giàu vitamin và khoáng chất.', 'Rau bina, chanh, dưa leo, gừng, nước lọc.', 90, 3.0, 18.0, 1.0, 29000, 'images/product/pro12.svg', 1, 94);

-- Mật khẩu mẫu: 123 (admin "admin"), 123456 (các user)
INSERT INTO nguoi_dung (id, ho_ten, email, mat_khau, gioi_tinh, tuoi, chieu_cao, can_nang, muc_do_hoat_dong, muc_tieu, dien_thoai, dia_chi, vai_tro, trang_thai) VALUES
(1, 'admin', 'admin@mealhealth.local', '$2y$12$7HVldA9Lm2MGkRTSezrTQOhh1QVzuLVpgs7sxxfdQeXzYQjMffGKa', 'khac', 30, 170.00, 65.00, 'trung_binh', 'duy_tri', '19001234', 'Hà Nội', 'admin', 1),
(2, 'Nguyễn Văn A', 'user1@example.com', '$2y$12$YfzAOC3SPFkz45B8k.bMuuqtUQJxEWLwPQz0K.kUnuJhsGkcZg0eK', 'nam', 28, 172.00, 78.00, 'trung_binh', 'giam_can', '0911111111', 'Hà Nội', 'user', 1),
(3, 'Trần Thị B', 'user2@example.com', '$2y$12$YfzAOC3SPFkz45B8k.bMuuqtUQJxEWLwPQz0K.kUnuJhsGkcZg0eK', 'nu', 26, 160.00, 50.00, 'cao', 'tang_can', '0922222222', 'TP. Hồ Chí Minh', 'user', 1),
(4, 'Lê Văn C', 'user3@example.com', '$2y$12$YfzAOC3SPFkz45B8k.bMuuqtUQJxEWLwPQz0K.kUnuJhsGkcZg0eK', 'nam', 35, 168.00, 66.00, 'thap', 'duy_tri', '0933333333', 'Đà Nẵng', 'user', 1);

INSERT INTO don_hang (id, id_nguoi_dung, ho_ten, dien_thoai, dia_chi, tong_tien, tong_calo, trang_thai, ghi_chu, created_at) VALUES
(1, 2, 'Nguyễn Văn A', '0911111111', '12 Lê Duẩn, Hoàn Kiếm, Hà Nội', 124000, 800, 'hoan_tat', 'Giao buổi sáng', '2026-11-10 08:30:00'),
(2, 3, 'Trần Thị B', '0922222222', '45 Nguyễn Huệ, Quận 1, TP.HCM', 163000, 1030, 'dang_giao', NULL, '2026-11-18 09:15:00'),
(3, 4, 'Lê Văn C', '0933333333', '8 Trần Phú, Hải Châu, Đà Nẵng', 107000, 590, 'cho_xac_nhan', 'Ít dầu mỡ', '2026-11-20 14:05:00');

INSERT INTO chi_tiet_don_hang (id_don_hang, id_mon_an, ten_mon_an, so_luong, gia) VALUES
(1, 1, 'Yến mạch chuối & sữa chua', 2, 45000),
(1, 10, 'Sữa chua mật ong hạt chia', 1, 34000),
(2, 4, 'Cơm gạo lứt gà nướng', 1, 79000),
(2, 7, 'Cá hồi nướng rau củ', 1, 95000),
(2, 12, 'Nước xanh rau bina & chanh', 3, 29000),
(3, 8, 'Ức gà xào rau xanh', 1, 78000),
(3, 10, 'Sữa chua mật ong hạt chia', 1, 32000);

INSERT INTO binh_luan (id_mon_an, id_nguoi_dung, danh_gia, noi_dung, trang_thai, created_at) VALUES
(1, 2, 5, 'Bữa sáng rất ngon và no lâu, mình đã đặt lại nhiều lần.', 1, '2026-11-11 07:20:00'),
(4, 3, 4, 'Cơm gạo lứt và gà nướng hợp khẩu vị, phần ăn hợp lý.', 1, '2026-11-17 12:10:00'),
(7, 2, 5, 'Cá hồi tươi, rau củ ngọt. Rất ưng ý!', 1, '2026-11-19 19:45:00'),
(10, 4, 3, 'Sữa chua hơi chua một chút nhưng vẫn ổn.', 0, '2026-11-20 15:30:00');

INSERT INTO goi_y_bua_an (id, id_nguoi_dung, ngay, muc_tieu, tong_calo, ghi_chu) VALUES
(1, 2, '2026-11-10 07:00:00', 'giam_can', 1450, 'Gợi ý tự động theo BMI và mục tiêu giảm cân.'),
(2, 3, '2026-11-18 07:30:00', 'tang_can', 2200, 'Gợi ý tăng calo an toàn.');

INSERT INTO chi_tiet_goi_y (id_goi_y, id_mon_an, bua) VALUES
(1, 1, 'Sáng'), (1, 5, 'Trưa'), (1, 7, 'Tối'), (1, 10, 'Phụ'),
(2, 3, 'Sáng'), (2, 4, 'Trưa'), (2, 8, 'Tối'), (2, 10, 'Phụ');

-- Cập nhật lượt xem cho món ăn phổ biến
UPDATE mon_an SET luot_xem = luot_xem + 12 WHERE id IN (1, 4, 7);

SELECT 'Import database meal_health thành công!' AS message;
