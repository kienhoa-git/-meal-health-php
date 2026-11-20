<?php
/**
 * Trang này chỉ còn làm nhiệm vụ chuyển hướng.
 * Ứng dụng dùng DUY NHẤT 1 form đăng nhập tại /dangnhap.php.
 * Sau khi đăng nhập, hệ thống tự điều hướng theo vai trò:
 *   - admin -> admin/index.php
 *   - user  -> index.php
 */
header('Location: ../dangnhap.php');
exit;
