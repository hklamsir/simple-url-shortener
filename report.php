<!-- /report.php -->
<?php session_start(); ?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>檢舉惡意連結</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- 新增：Font Awesome 圖示庫 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <!-- 新增：檢舉頁面專用樣式 -->
    <style>
        .report-form-group {
            text-align: left;
            margin-bottom: 1.5rem;
        }
        .report-form-group label {
            display: block;
            margin-bottom: 0.5rem;
        }
        .report-form-group input[type="url"],
        .report-form-group textarea {
            width: 100%;
            padding: 12px;
            font-size: 1rem;
            box-sizing: border-box;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        /* 修正：按鈕容器佈局 */
        .report-actions {
            display: flex;
            justify-content: space-between; /* 將按鈕推向兩側 */
            align-items: center;
            gap: 1rem; /* 按鈕間距 */
            margin-top: 1.5rem;
        }
        /* 移除 flex: 1，讓按鈕恢復自然寬度 */
        /* .report-actions > * { flex: 1; } */
        
        .btn-secondary {
            display: inline-flex; /* 使用 inline-flex 以對齊圖示和文字 */
            align-items: center;
            padding: 12px 20px;
            font-size: 1rem;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
            color: white;
            background-color: #6c757d; /* 次要按鈕顏色 */
            border: none;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        /* 新增：為按鈕內的圖示增加間距 */
        .btn-icon {
            margin-right: 0.5rem;
        }
        /* 新增：為標題的圖示增加間距 */
        h1 .fa-shield-halved {
            margin-right: 0.75rem;
            color: #e74c3c;
        }
    </style>
</head>
<body>
    <div class="site-wrapper">
        <main class="main-content">
            <div class="container">
                <!-- 新增：為標題加入圖示 -->
                <h1><i class="fa-solid fa-shield-halved"></i>檢舉惡意連結</h1>
                <p>如果您發現有短網址被用於釣魚、惡意軟體等不當用途，請在此提交檢舉。</p>

                <?php
                    if (isset($_SESSION['report_flash'])) {
                        $message = $_SESSION['report_flash'];
                        $color = (strpos($message, '成功') !== false) ? '#27ae60' : '#e74c3c';
                        echo '<p style="color: ' . $color . '; font-weight: bold;">' . htmlspecialchars($message) . '</p>';
                        unset($_SESSION['report_flash']);
                    }
                ?>

                <form action="submit_report.php" method="POST">
                    <div class="report-form-group">
                        <label for="short_url">要檢舉的短網址：</label>
                        <input type="url" name="short_url" id="short_url" placeholder="例如：http://yourdomain.com/XyZab" required>
                    </div>
                    <div class="report-form-group">
                        <label for="reason">檢舉原因（可選）：</label>
                        <textarea name="reason" id="reason" rows="4"></textarea>
                    </div>
                    
                    <!-- 修正：調整按鈕順序與內容 -->
                    <div class="report-actions">
                        <button type="submit"><i class="fa-solid fa-paper-plane btn-icon"></i>提交檢舉</button>
                        <a href="index.php" class="btn-secondary"><i class="fa-solid fa-house btn-icon"></i>返回主頁</a>
                    </div>
                </form>
            </div>
        </main>
        
        <?php 
            if (file_exists('templates/footer.php')) {
                require_once 'templates/footer.php';
            }
        ?>
    </div>
</body>
</html>