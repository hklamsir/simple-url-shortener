<?php
// /shortener.php

session_start(); 
require_once 'config.php';

// 統一的訊息處理函式
function set_flash_message($type, $message) {
    $_SESSION['flash_message'] = ['type' => $type, 'text' => $message];
}

// 統一的重導向函式
function redirect_home() {
    header("Location: index.php");
    exit();
}

// --- 1. 檢查請求 ---
if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST['url']) || empty(trim($_POST['url']))) {
    set_flash_message('error', '無效的請求或未提供網址。');
    redirect_home();
}

// 安全修復：驗證 CSRF Token
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    set_flash_message('error', '安全驗證失敗，請重新提交。');
    redirect_home();
}

$original_url = trim($_POST['url']);
$expiration = $_POST['expiration'] ?? 'never';

if (!filter_var($original_url, FILTER_VALIDATE_URL)) {
    set_flash_message('error', '請輸入有效的 URL。');
    redirect_home();
}

// 安全修復 #13：URL 長度限制（RFC 7230 建議不超過 8000，此處更保守）
define('MAX_URL_LENGTH', 2048);
if (strlen($original_url) > MAX_URL_LENGTH) {
    set_flash_message('error', 'URL 過長，請輸入少於 2048 個字元的網址。');
    redirect_home();
}

// 安全修復 #30：協議白名單，防止 javascript:/data:/vbscript: 協議
if (!preg_match('/^https?:\/\//i', $original_url)) {
    set_flash_message('error', '僅允許 HTTP/HTTPS 協議的 URL。');
    redirect_home();
}

// --- 2. 資料庫連線 ---
$conn = get_db_connection();
if (!$conn) {
    set_flash_message('error', '資料庫連線失敗，請檢查 config.php 中的設定。');
    redirect_home();
}

// --- 3. 將主要邏輯包裹在 try...catch...finally 中 ---
try {
    // 頻率限制檢查
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    // 安全修復 #17：5% 機率清理過期 rate_limits，加入 LIMIT 防止一次刪除過多記錄
    if (random_int(1, 100) <= 5) {
        $conn->query("DELETE FROM rate_limits WHERE request_time < NOW() - INTERVAL 1 MINUTE LIMIT 1000");
    }

    $stmt_rate_check = $conn->prepare("SELECT COUNT(*) as count FROM rate_limits WHERE ip_address = ? AND request_time > NOW() - INTERVAL 1 MINUTE");
    if ($stmt_rate_check === false) throw new Exception("資料庫錯誤 (rate limit select)");
    
    $stmt_rate_check->bind_param("s", $ip_address);
    $stmt_rate_check->execute();
    $result = $stmt_rate_check->get_result()->fetch_assoc();
    $stmt_rate_check->close();

    if ($result['count'] >= RATE_LIMIT_PER_MINUTE) {
        throw new Exception('您的請求過於頻繁，請稍後再試。');
    }

    $stmt_rate_log = $conn->prepare("INSERT INTO rate_limits (ip_address) VALUES (?)");
    if ($stmt_rate_log === false) throw new Exception("資料庫錯誤 (rate limit insert)");

    $stmt_rate_log->bind_param("s", $ip_address);
    $stmt_rate_log->execute();
    $stmt_rate_log->close();

    // Google Safe Browsing API 檢查
    // 安全修復 #14：API Key 改用 HTTP header 傳遞，避免出現在 URL 和伺服器日誌中
    if (defined('GOOGLE_API_KEY') && GOOGLE_API_KEY !== 'YOUR_GOOGLE_API_KEY' && function_exists('curl_init')) {
        $apiUrl = 'https://safebrowsing.googleapis.com/v4/threatMatches:find';
        $payload = [
            'client' => ['clientId' => 'your-company-name', 'clientVersion' => '1.0.0'],
            'threatInfo' => [
                'threatTypes' => ['MALWARE', 'SOCIAL_ENGINEERING', 'UNWANTED_SOFTWARE', 'POTENTIALLY_HARMFUL_APPLICATION'],
                'platformTypes' => ['ANY_PLATFORM'],
                'threatEntryTypes' => ['URL'],
                'threatEntries' => [['url' => $original_url]]
            ]
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'x-goog-api-key: ' . GOOGLE_API_KEY]);
        // 安全修復 #24：設定 cURL 超時，防止 API 無回應時 PHP 進程長時間阻塞
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        $api_response = curl_exec($ch);
        // 安全修復 #25：檢查 cURL 執行錯誤，失敗時跳過安全檢查而非阻斷
        if ($api_response === false) {
            curl_close($ch);
            // API 呼叫失敗，跳過安全檢查（日誌可記錄 curl_error($ch)）
        } else {
            curl_close($ch);
            $response_data = json_decode($api_response, true);

            if (!empty($response_data['matches'])) {
                throw new Exception('錯誤：您提供的網址經檢測可能為不安全連結，已拒絕生成。');
            }
        }
    }

    // 生成與儲存短網址
    $expires_at = null;
    if ($expiration !== 'never') {
        try {
            $date = new DateTime();
            $date->add(new DateInterval($expiration));
            $expires_at = $date->format('Y-m-d H:i:s');
        } catch (Exception $e) { $expires_at = null; }
    }

    $short_code = generate_unique_short_code($conn);
    if (!$short_code) {
        throw new Exception('無法生成唯一的短網址，請稍後再試。');
    }

    $stmt_insert = $conn->prepare("INSERT INTO short_urls (original_url, short_code, expires_at) VALUES (?, ?, ?)");
    if ($stmt_insert === false) throw new Exception("資料庫錯誤 (short url insert)");

    $stmt_insert->bind_param("sss", $original_url, $short_code, $expires_at);

    if ($stmt_insert->execute()) {
        $full_short_url = rtrim(BASE_URL, '/') . '/' . $short_code;
        set_flash_message('success', $full_short_url); 
    } else {
        throw new Exception('無法儲存短網址，請稍後再試。');
    }
    $stmt_insert->close();

} catch (Exception $e) {
    // 捕捉所有例外錯誤，並設定一個友善的 flash message
    set_flash_message('error', $e->getMessage());
} finally {
    // 無論成功或失敗，都確保關閉資料庫連線並重導向
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
    redirect_home();
}
?>
