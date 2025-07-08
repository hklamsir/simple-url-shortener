<?php
// /redirect.php

require_once 'config.php';

// 檢查是否有 'code' 參數
if (!isset($_GET['code']) || empty($_GET['code'])) {
    header("Location: " . BASE_URL);
    exit();
}

// 清理輸入
$short_code = trim($_GET['code']);

$conn = get_db_connection();
if (!$conn) {
    // 資料庫連線失敗，重定向到主頁
    header("Location: " . BASE_URL);
    exit();
}

// 查詢原始 URL
$stmt = $conn->prepare("SELECT url_id, original_url, expires_at FROM short_urls WHERE short_code = ?");
$stmt->bind_param("s", $short_code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $url_id = $row['url_id'];
    $original_url = $row['original_url'];
    $expires_at = $row['expires_at'];

    // 檢查是否過期
    if ($expires_at !== null && strtotime($expires_at) < time()) {
        // 連結已過期，可以選擇刪除或標記為過期
        // 此處我們直接重定向到主頁並帶上錯誤訊息
        header("Location: " . BASE_URL . "?error=expired");
        exit();
    }

    // 更新點擊次數
    $update_stmt = $conn->prepare("UPDATE short_urls SET click_count = click_count + 1 WHERE url_id = ?");
    $update_stmt->bind_param("i", $url_id);
    $update_stmt->execute();
    $update_stmt->close();

    // 記錄訪問日誌
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;

    $log_stmt = $conn->prepare("INSERT INTO access_logs (url_id, ip_address, referrer, user_agent) VALUES (?, ?, ?, ?)");
    $log_stmt->bind_param("isss", $url_id, $ip_address, $referrer, $user_agent);
    $log_stmt->execute();
    $log_stmt->close();

    // 執行 301 永久重定向
    header("Location: " . $original_url, true, 301);
    exit();

} else {
    // 找不到對應的短網址
    header("Location: " . BASE_URL . "?error=notfound");
    exit();
}

$stmt->close();
$conn->close();
?>
