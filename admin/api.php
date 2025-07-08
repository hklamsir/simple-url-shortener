<?php
// /admin/api.php

session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => '未授權']);
    exit();
}

$conn = get_db_connection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => '資料庫連線失敗']);
    exit();
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_stats':
        // (此部分保持不變)
        $stats_result = $conn->query("SELECT COUNT(*) as total_links, SUM(click_count) as total_clicks FROM short_urls");
        $stats = $stats_result->fetch_assoc();
        $chart_result = $conn->query("SELECT DATE(access_time) as date, COUNT(*) as clicks FROM access_logs WHERE access_time >= CURDATE() - INTERVAL 6 DAY GROUP BY DATE(access_time) ORDER BY date ASC");
        $chart_data = [];
        while($row = $chart_result->fetch_assoc()) { $chart_data[] = $row; }
        echo json_encode(['success' => true, 'stats' => $stats, 'chart_data' => $chart_data]);
        break;

    case 'get_links':
        // (此部分保持不變)
        $query = "SELECT su.url_id, su.original_url, su.short_code, su.click_count, su.created_at, su.expires_at, COUNT(r.report_id) AS report_count FROM short_urls su LEFT JOIN reports r ON su.url_id = r.url_id WHERE su.is_custom = 0 GROUP BY su.url_id ORDER BY su.created_at DESC";
        $links_result = $conn->query($query);
        $links = [];
        while($row = $links_result->fetch_assoc()) { $links[] = $row; }
        echo json_encode(['success' => true, 'links' => $links]);
        break;
    
    case 'get_report_details':
        // (此部分保持不變)
        if (!isset($_GET['id'])) { echo json_encode(['success' => false, 'message' => '缺少連結 ID']); exit(); }
        $url_id = intval($_GET['id']);
        $stmt = $conn->prepare("SELECT report_id, reason, report_time, reporter_ip FROM reports WHERE url_id = ? ORDER BY report_time DESC");
        $stmt->bind_param("i", $url_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $reports = [];
        while($row = $result->fetch_assoc()) { $reports[] = $row; }
        $stmt->close();
        echo json_encode(['success' => true, 'reports' => $reports]);
        break;

    case 'get_access_stats':
        // (此部分保持不變)
        $type = $_GET['type'] ?? 'short_url';
        $query = '';
        switch ($type) {
            case 'ip': $query = "SELECT ip_address as label, COUNT(log_id) as value FROM access_logs GROUP BY ip_address ORDER BY value DESC LIMIT 10"; break;
            case 'user_agent': $query = "SELECT CASE WHEN user_agent LIKE '%Firefox/%' THEN 'Firefox' WHEN user_agent LIKE '%Chrome/%' AND user_agent NOT LIKE '%Edg/%' THEN 'Chrome' WHEN user_agent LIKE '%Edg/%' THEN 'Edge' WHEN user_agent LIKE '%Safari/%' AND user_agent NOT LIKE '%Chrome/%' THEN 'Safari' WHEN user_agent LIKE '%Trident/%' OR user_agent LIKE '%MSIE%' THEN 'Internet Explorer' ELSE 'Other' END as label, COUNT(log_id) as value FROM access_logs GROUP BY label ORDER BY value DESC"; break;
            case 'os': $query = "SELECT CASE WHEN user_agent LIKE '%Windows NT%' THEN 'Windows' WHEN user_agent LIKE '%Macintosh%' THEN 'macOS' WHEN user_agent LIKE '%Linux%' AND user_agent NOT LIKE '%Android%' THEN 'Linux' WHEN user_agent LIKE '%Android%' THEN 'Android' WHEN user_agent LIKE '%iPhone%' OR user_agent LIKE '%iPad%' THEN 'iOS' ELSE 'Other' END as label, COUNT(log_id) as value FROM access_logs GROUP BY label ORDER BY value DESC"; break;
            default: $query = "SELECT su.short_code as label, COUNT(al.log_id) as value FROM access_logs al LEFT JOIN short_urls su ON al.url_id = su.url_id WHERE su.short_code IS NOT NULL GROUP BY al.url_id ORDER BY value DESC LIMIT 10"; break;
        }
        $result = $conn->query($query);
        $data = [];
        while($row = $result->fetch_assoc()) { $data[] = $row; }
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    case 'get_url_click_details':
        // (此部分保持不變)
        if (!isset($_GET['short_code'])) { echo json_encode(['success' => false, 'message' => '缺少 short_code']); exit(); }
        $short_code = $_GET['short_code'];
        $stmt = $conn->prepare("SELECT al.ip_address, al.user_agent FROM access_logs al JOIN short_urls su ON al.url_id = su.url_id WHERE su.short_code = ? ORDER BY al.access_time DESC LIMIT 50");
        $stmt->bind_param("s", $short_code);
        $stmt->execute();
        $result = $stmt->get_result();
        $details = [];
        while($row = $result->fetch_assoc()) { $details[] = $row; }
        $stmt->close();
        echo json_encode(['success' => true, 'details' => $details]);
        break;

    case 'delete_report':
        // (此部分保持不變)
        $input = json_decode(file_get_contents('php://input'), true);
        if (isset($input['report_id'])) {
            $report_id = intval($input['report_id']);
            $stmt = $conn->prepare("DELETE FROM reports WHERE report_id = ?");
            $stmt->bind_param("i", $report_id);
            if ($stmt->execute()) { echo json_encode(['success' => true]); } 
            else { echo json_encode(['success' => false, 'message' => '刪除檢舉失敗']); }
            $stmt->close();
        } else { echo json_encode(['success' => false, 'message' => '缺少檢舉 ID']); }
        break;

    case 'delete_link':
        // (此部分保持不變)
        $input = json_decode(file_get_contents('php://input'), true);
        if (isset($input['id'])) {
            $id = intval($input['id']);
            $stmt = $conn->prepare("DELETE FROM short_urls WHERE url_id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) { echo json_encode(['success' => true]); } 
            else { echo json_encode(['success' => false, 'message' => '刪除失敗']); }
            $stmt->close();
        } else { echo json_encode(['success' => false, 'message' => '缺少 ID']); }
        break;
    
    // --- 新增操作 ---
    case 'update_password':
        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['current_password'], $input['new_password'])) {
            echo json_encode(['success' => false, 'message' => '缺少必要欄位']);
            break;
        }
        $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = 'admin_password'");
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => '找不到密碼設定']);
            break;
        }
        $row = $result->fetch_assoc();
        $hashed_password = $row['setting_value'];

        if (password_verify($input['current_password'], $hashed_password)) {
            $new_hashed_password = password_hash($input['new_password'], PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'admin_password'");
            $update_stmt->bind_param("s", $new_hashed_password);
            if ($update_stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => '更新密碼失敗']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => '目前密碼不正確']);
        }
        break;

    case 'get_custom_urls':
        $result = $conn->query("SELECT url_id, original_url, short_code, click_count, created_at FROM short_urls WHERE is_custom = 1 ORDER BY created_at DESC");
        $links = [];
        while($row = $result->fetch_assoc()) { $links[] = $row; }
        echo json_encode(['success' => true, 'links' => $links]);
        break;

    case 'add_custom_url':
        $input = json_decode(file_get_contents('php://input'), true);
        $original_url = $input['original_url'];
        $custom_code = $input['custom_code'];

        if (strlen($custom_code) < 2 || strlen($custom_code) > 8 || !preg_match('/^[a-zA-Z0-9-]+$/', $custom_code)) {
            echo json_encode(['success' => false, 'message' => '自訂短碼必須為 2-8 個字元的英數字或連字號']);
            break;
        }

        $stmt = $conn->prepare("SELECT url_id FROM short_urls WHERE short_code = ?");
        $stmt->bind_param("s", $custom_code);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => '此自訂短碼已被使用']);
            break;
        }

        $insert_stmt = $conn->prepare("INSERT INTO short_urls (original_url, short_code, is_custom) VALUES (?, ?, 1)");
        $insert_stmt->bind_param("ss", $original_url, $custom_code);
        if ($insert_stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => '新增失敗']);
        }
        break;
    
    case 'update_custom_url':
        $input = json_decode(file_get_contents('php://input'), true);
        $url_id = $input['url_id'];
        $original_url = $input['original_url'];

        $stmt = $conn->prepare("UPDATE short_urls SET original_url = ? WHERE url_id = ? AND is_custom = 1");
        $stmt->bind_param("si", $original_url, $url_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => '更新失敗']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => '無效的操作']);
        break;
}

$conn->close();
?>
