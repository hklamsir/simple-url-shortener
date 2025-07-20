<?php
// /cron_cleanup.php

require_once 'config.php';

// --- 安全性檢查 ---
// 確保 CRON_SECRET_KEY 已在 config.php 中定義
if (!defined('CRON_SECRET_KEY') || empty(CRON_SECRET_KEY)) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => '伺服器未設定密鑰。']);
    exit();
}

// 從 GET 請求中獲取密鑰並進行驗證
$provided_key = $_GET['key'] ?? '';
if ($provided_key !== CRON_SECRET_KEY) {
    http_response_code(403); // Forbidden
    echo json_encode(['status' => 'error', 'message' => '未授權的存取。']);
    exit();
}

// --- 開始清理工作 ---
header('Content-Type: application/json');

$response = [
    'status' => 'error',
    'message' => '清理過程中發生未知錯誤。',
    'deleted_rate_limits' => 0,
    'deleted_urls' => 0,
];

$conn = get_db_connection();
if ($conn) {
    try {
        // 1. 清理超過 24 小時的請求頻率紀錄
        $conn->query("DELETE FROM rate_limits WHERE request_time < NOW() - INTERVAL 24 HOUR");
        $response['deleted_rate_limits'] = $conn->affected_rows;

        // 2. 查找並刪除過期的非自訂短網址
        // 修正：重寫 SQL 查詢以正確使用 HAVING 子句
        $find_query = "
            SELECT su.url_id 
            FROM short_urls su
            LEFT JOIN access_logs al ON su.url_id = al.url_id
            WHERE 
                su.is_custom = 0 
            GROUP BY 
                su.url_id, su.created_at, su.expires_at
            HAVING
                -- 條件 A: 連結已達到設定的過期時間
                (su.expires_at IS NOT NULL AND su.expires_at < NOW())
                OR
                -- 條件 B: 連結超過 100 天未被使用
                (
                    MAX(al.access_time) < NOW() - INTERVAL 100 DAY
                    OR
                    (MAX(al.access_time) IS NULL AND su.created_at < NOW() - INTERVAL 100 DAY)
                )
        ";
        
        $result = $conn->query($find_query);
        $ids_to_delete = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $ids_to_delete[] = $row['url_id'];
            }
        }

        if (!empty($ids_to_delete)) {
            $id_list = implode(',', $ids_to_delete);
            $conn->query("DELETE FROM short_urls WHERE url_id IN ($id_list)");
            $response['deleted_urls'] = $conn->affected_rows;
        }
        
        $response['status'] = 'success';
        $response['message'] = '數據清理完成！';

    } catch (Exception $e) {
        $response['message'] = "清理過程中發生錯誤：" . $e->getMessage();
    } finally {
        $conn->close();
    }
} else {
    $response['message'] = "資料庫連線失敗。";
}

echo json_encode($response);
?>
