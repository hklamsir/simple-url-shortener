<?php
// /admin/cleanup.php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit();
}

require_once '../config.php';

$message = '';
$deleted_rate_limits = 0;
$deleted_urls = 0;
$deleted_access_logs = "自動處理"; // 預設訊息

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['start_cleanup'])) {
    $conn = get_db_connection();
    if ($conn) {
        try {
            // 1. 清理超過 24 小時的請求頻率紀錄
            $conn->query("DELETE FROM rate_limits WHERE request_time < NOW() - INTERVAL 24 HOUR");
            $deleted_rate_limits = $conn->affected_rows;

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
                $deleted_urls = $conn->affected_rows;
            }
            
            $message = "數據清理完成！";

        } catch (Exception $e) {
            $message = "清理過程中發生錯誤：" . $e->getMessage();
        } finally {
            $conn->close();
        }
    } else {
        $message = "資料庫連線失敗。";
    }
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>數據清理</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .admin-header h1 { margin: 0; }
        .cleanup-section { background-color: #f8f9fa; padding: 2rem; border-radius: 8px; border: 1px solid #dee2e6; }
        .cleanup-rules { text-align: left; margin-bottom: 2rem; }
        .cleanup-rules ul { padding-left: 20px; }
        .cleanup-rules li { margin-bottom: 0.5rem; }
        .result-box { margin-top: 2rem; padding: 1.5rem; border-radius: 8px; background-color: #eaf5ff; border: 1px solid #b8d9f3; }
    </style>
</head>
<body class="admin-page">
    <div class="site-wrapper">
        <main class="main-content">
            <div class="container">
                <div class="admin-header">
                    <h1><i class="fa-solid fa-broom"></i> 數據清理</h1>
                    <a href="index.php"><button>返回儀表板</button></a>
                </div>

                <div class="cleanup-section">
                    <div class="cleanup-rules">
                        <h3>清理規則說明：</h3>
                        <ul>
                            <li><b>短網址：</b>將刪除所有符合以下任一條件的**非自訂**短網址：
                                <ul style="margin-top: 0.5rem;">
                                    <li>已達到您設定的「有效期限」。</li>
                                    <li>超過 100 天未被使用。</li>
                                </ul>
                            </li>
                            <li><b>存取日誌：</b>當相關的短網址被刪除時，其對應的存取日誌將會被自動清除。</li>
                            <li><b>請求頻率紀錄：</b>刪除所有超過 24 小時的請求紀錄。</li>
                        </ul>
                        <p><strong>注意：</strong>此操作將會永久刪除資料且無法復原，請謹慎操作。</p>
                    </div>
                    <form method="POST">
                        <button type="submit" name="start_cleanup" style="background-color: #dc3545;">開始清理</button>
                    </form>
                </div>

                <?php if ($_SERVER["REQUEST_METHOD"] == "POST"): ?>
                <div class="result-box">
                    <h2>清理結果</h2>
                    <p><?php echo htmlspecialchars($message); ?></p>
                    <ul>
                        <li>已刪除的請求頻率紀錄： <strong><?php echo $deleted_rate_limits; ?></strong> 筆</li>
                        <li>已刪除的過期短網址： <strong><?php echo $deleted_urls; ?></strong> 筆</li>
                        <li>相關的存取日誌： <strong><?php echo $deleted_access_logs; ?></strong></li>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </main>
        <?php if (file_exists('../templates/footer.php')) require_once '../templates/footer.php'; ?>
    </div>
</body>
</html>
