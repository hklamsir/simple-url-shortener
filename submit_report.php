<?php
// /submit_report.php

session_start();
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['short_url']) || empty(trim($_POST['short_url']))) {
        $_SESSION['report_flash'] = '錯誤：請填寫要檢舉的短網址。';
        header("Location: report.php");
        exit();
    }

    $short_url = trim($_POST['short_url']);
    $reason = trim($_POST['reason']);
    $reporter_ip = $_SERVER['REMOTE_ADDR'];

    // 從完整的 URL 中解析出 short_code
    $path = parse_url($short_url, PHP_URL_PATH);
    $short_code = basename($path);

    if (empty($short_code)) {
        $_SESSION['report_flash'] = '錯誤：無效的短網址格式。';
        header("Location: report.php");
        exit();
    }

    $conn = get_db_connection();
    if (!$conn) {
        $_SESSION['report_flash'] = '錯誤：無法連線至資料庫。';
        header("Location: report.php");
        exit();
    }

    // 根據 short_code 查找 url_id
    $stmt = $conn->prepare("SELECT url_id FROM short_urls WHERE short_code = ?");
    $stmt->bind_param("s", $short_code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['report_flash'] = '錯誤：找不到您要檢舉的短網址。';
        $stmt->close();
        $conn->close();
        header("Location: report.php");
        exit();
    }

    $row = $result->fetch_assoc();
    $url_id = $row['url_id'];
    $stmt->close();

    // 插入檢舉紀錄
    $stmt = $conn->prepare("INSERT INTO reports (url_id, reason, reporter_ip) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $url_id, $reason, $reporter_ip);
    
    if ($stmt->execute()) {
        $_SESSION['report_flash'] = '成功：感謝您的檢舉，我們將會盡快審核。';
    } else {
        $_SESSION['report_flash'] = '錯誤：提交檢舉失敗，請稍後再試。';
    }

    $stmt->close();
    $conn->close();
}

header("Location: report.php");
exit();
?>
