<?php
// /config.sample.php

// --- 資料庫設定 ---
// 請根據您自己的資料庫資訊修改
define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'your_db_name');

// --- 網站設定 ---
// 您的網站主域名，結尾不要加斜線
define('BASE_URL', 'http://localhost/your_project_folder');

// --- 安全性設定 ---
// 請將 'YOUR_GOOGLE_API_KEY' 替換為您自己的 Google Safe Browsing API 金鑰
define('GOOGLE_API_KEY', 'YOUR_GOOGLE_API_KEY');

// 頻率限制：每分鐘最多允許生成的次數
define('RATE_LIMIT_PER_MINUTE', 5);

// --- 自動化腳本密鑰 ---
// 請將 'YOUR_VERY_SECRET_KEY' 替換為一個複雜的隨機字串
define('CRON_SECRET_KEY', 'YOUR_VERY_SECRET_KEY');


// --- 其他設定 ---
date_default_timezone_set('Asia/Taipei');

/**
 * 資料庫連線函式
 * @return mysqli|false
 */
function get_db_connection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        return false;
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}

/**
 * 生成唯一的短網址後綴
 * @param mysqli $conn 資料庫連線
 * @return string|false
 */
function generate_unique_short_code($conn) {
    $max_attempts = 10;
    $lowercase_chars = 'abcdefghijkmnopqrstuvwxyz';
    $uppercase_chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ';

    for ($i = 0; $i < $max_attempts; $i++) {
        $capital_part = substr(str_shuffle($uppercase_chars), 0, 1);
        $small_part = substr(str_shuffle($lowercase_chars), 0, 4);
        $short_code = str_shuffle($capital_part . $small_part);

        $stmt = $conn->prepare("SELECT url_id FROM short_urls WHERE short_code = ?");
        $stmt->bind_param("s", $short_code);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows === 0) {
            $stmt->close();
            return $short_code;
        }
        $stmt->close();
    }
    return false;
}
?>
