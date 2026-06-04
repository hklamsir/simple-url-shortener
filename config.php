<?php
// /config.php

// --- 資料庫設定 ---
// 請根據您在 InfinityFree 上的資料庫資訊修改
define('DB_HOST', 'sql112.infinityfree.com'); // e.g., sql201.infinityfree.com
define('DB_USER', 'if0_35608548');          // e.g., if0_12345678
define('DB_PASS', 'LSTzd8bE15o');         // 您的資料庫密碼
define('DB_NAME', 'if0_35608548_short_url'); // 您的資料庫名稱

// --- 網站設定 ---
// 您的網站主域名，結尾不要加斜線
// 例如：http://yourdomain.epizy.com
define('BASE_URL', 'https://link.fwh.is');

// --- 自動化腳本密鑰 ---
// 請將 'YOUR_VERY_SECRET_KEY' 替換為一個複雜的隨機字串
define('CRON_SECRET_KEY', 'Ping$hun_9750');

// --- 安全性設定 ---
// 請將 'YOUR_GOOGLE_API_KEY' 替換為您自己的 Google Safe Browsing API 金鑰
define('GOOGLE_API_KEY', 'AIzaSyDUFzgKKyw_rtCbcMRlhYcoEquOeGT9kYU');

// 頻率限制：每分鐘最多允許生成的次數
define('RATE_LIMIT_PER_MINUTE', 6);

// --- CSRF 防護 ---
// 生成 CSRF Token（存入 session）
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// 驗證 CSRF Token（恒定時間比較，使用後輪換）
function verify_csrf_token($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    $valid = hash_equals($_SESSION['csrf_token'], $token);
    // 使用後輪換 token，防止重放攻擊
    unset($_SESSION['csrf_token']);
    return $valid;
}

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
 * 生成唯一的短網址後綴 (5位元，排除易混淆字元)
 * @param mysqli $conn 資料庫連線
 * @return string|false
 */
function generate_unique_short_code($conn) {
    $max_attempts = 10; // 防止無限循環

    // 定義允許的字符集，排除易混淆的字符 l, O, I
    // 安全修復：變數重新命名避免混淆，$capital_charset 僅含大寫
    $capital_charset = 'ABCDEFGHJKLMNPQRSTUVWXYZ';   // 25 個大寫字符
    $lower_charset = 'abcdefghijkmnopqrstuvwxyz';     // 25 個小寫字符
    $all_chars = $capital_charset . $lower_charset;   // 50 個字符

    for ($i = 0; $i < $max_attempts; $i++) {
        // 安全修復：使用 random_int() 取代 str_shuffle()，密碼學安全隨機數
        // 1. 從大寫字符中隨機選取 1 個
        $capital_part = $capital_charset[random_int(0, strlen($capital_charset) - 1)];
        
        // 2. 從小寫字符中隨機選取 4 個
        $small_part = '';
        for ($j = 0; $j < 4; $j++) {
            $small_part .= $lower_charset[random_int(0, strlen($lower_charset) - 1)];
        }

        // 3. 組合併以密碼學安全方式打亂順序
        $combined = $capital_part . $small_part;
        $chars = str_split($combined);
        $short_code = '';
        while (count($chars) > 0) {
            $idx = random_int(0, count($chars) - 1);
            $short_code .= $chars[$idx];
            array_splice($chars, $idx, 1);
        }

        // 4. 檢查資料庫中是否已存在
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
    // 如果嘗試多次仍然重複，返回 false
    return false;
}
?>