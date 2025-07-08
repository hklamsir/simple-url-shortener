<!-- /admin/index.php -->
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理儀表板</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <!-- 管理頁面專用樣式 -->
    <style>
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; text-align: left; }
        .admin-header h1 { margin: 0; }
        .admin-header .header-actions { display: flex; flex-wrap: wrap; gap: 1rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; }
        .stat-card { background-color: #eaf5ff; border: 1px solid #b8d9f3; padding: 1.5rem; border-radius: 8px; text-align: center;}
        .stat-card h3 { color: #567; margin: 0 0 0.5rem 0; }
        .stat-card p { font-size: 2.2rem; color: #0056b3; margin: 0; font-weight: bold;}
        .table-wrapper { overflow-x: auto; }
        .admin-page table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .admin-page th, .admin-page td { word-wrap: break-word; padding: 12px; border: 1px solid #ddd; text-align: left;}
        .admin-page th { background-color: #f2f2f2; }
        .login-form input[type="password"] { width: 100%; padding: 12px; font-size: 1rem; margin-bottom: 1rem; box-sizing: border-box; border: 1px solid #ddd; border-radius: 5px; }
        .col-url { width: 35%; }
        .col-shortcode { width: 12%; }
        .col-clicks { width: 8%; }
        .col-reports { width: 9%; }
        .col-created { width: 13%; }
        .col-expires { width: 13%; }
        .col-actions { width: 10%; }
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); display: flex; justify-content: center; align-items: center; z-index: 1000; }
        .modal-overlay.hidden { display: none; }
        .modal-content { background-color: #fff; padding: 2rem; border-radius: 8px; width: 90%; max-width: 800px; max-height: 80vh; overflow-y: auto; position: relative; }
        .modal-close { position: absolute; top: 10px; right: 20px; font-size: 2rem; font-weight: bold; cursor: pointer; }
        .modal-content h2 { margin-top: 0; }
        .report-details-table { width: 100%; border-collapse: collapse; }
        .report-details-table th, .report-details-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .view-reports-link { color: #0056b3; text-decoration: underline; cursor: pointer; }
        .view-reports-link:hover { color: #e74c3c; }
        .delete-report-btn { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 4px 8px; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body class="admin-page">
    <div class="site-wrapper">
        <main class="main-content">
            <?php
                session_start();
                require_once '../config.php'; 
                if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
                    // --- 未登入狀態 ---
                    $login_error = isset($_GET['error']) ? '密碼錯誤，請重試。' : '';
                    echo '<div class="container"><h1>管理後台登入</h1><form class="login-form" action="auth.php" method="post"><input type="password" name="password" placeholder="請輸入管理密碼" required autofocus><button type="submit" name="login">登入</button></form>';
                    if ($login_error) echo '<p class="error-message">' . $login_error . '</p>';
                    echo '</div>';
                } else {
                    // --- 已登入狀態 ---
            ?>
                <div class="container">
                    <div class="admin-header">
                        <h1><i class="fa-solid fa-gauge-high"></i> 管理儀表板</h1>
                        <div class="header-actions">
                            <a href="custom_urls.php"><button><i class="fa-solid fa-pen-ruler" style="margin-right: 0.5rem;"></i>自訂短網址</button></a>
                            <a href="access_report.php"><button><i class="fa-solid fa-file-lines" style="margin-right: 0.5rem;"></i>存取匯報</button></a>
                            <a href="cleanup.php"><button style="background-color: #ffc107; color: #333;"><i class="fa-solid fa-broom" style="margin-right: 0.5rem;"></i>數據清理</button></a>
                            <a href="settings.php"><button><i class="fa-solid fa-gears" style="margin-right: 0.5rem;"></i>設定</button></a>
                            <a href="auth.php?logout=true"><button><i class="fa-solid fa-right-from-bracket" style="margin-right: 0.5rem;"></i>登出</button></a>
                        </div>
                    </div>
                    <div class="stats-grid">
                        <div class="stat-card"><h3>總連結數</h3><p id="total-links">0</p></div>
                        <div class="stat-card"><h3>總點擊數</h3><p id="total-clicks">0</p></div>
                    </div>
                    <div class="chart-container" style="position: relative; height:40vh; width:100%; margin: auto; margin-top: 2rem;">
                        <canvas id="clicks-chart"></canvas>
                    </div>
                    <h2 style="margin-top: 2rem;">所有短網址</h2>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th class="col-url">原始網址</th>
                                    <th class="col-shortcode">短網址</th>
                                    <th class="col-clicks">點擊次數</th>
                                    <th class="col-reports">被檢舉次數</th>
                                    <th class="col-created">創建時間</th>
                                    <th class="col-expires">過期時間</th>
                                    <th class="col-actions">操作</th>
                                </tr>
                            </thead>
                            <tbody id="links-table-body"></tbody>
                        </table>
                    </div>
                </div>
            <?php
                }
            ?>
        </main>
        <?php 
            if (file_exists('../templates/footer.php')) {
                require_once '../templates/footer.php';
            }
        ?>
    </div>

    <?php 
        // 只有在登入狀態下，才載入彈出視窗的 HTML 和相關的 JS 檔案
        if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): 
    ?>
        <!-- 檢舉紀錄彈出視窗 -->
        <div id="report-modal" class="modal-overlay hidden">
            <div class="modal-content">
                <span class="modal-close">&times;</span>
                <h2>檢舉紀錄</h2>
                <div id="report-details-content"></div>
            </div>
        </div>

        <script src="https://www.chartjs.org/dist/master/chart.umd.min.js"></script>
        <script src="../assets/js/admin.js"></script>
    <?php endif; ?>
</body>
</html>
