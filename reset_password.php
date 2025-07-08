<?php
// /reset_password.php

$new_hash = '';
$sql_command = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['new_password'])) {
    $new_password = $_POST['new_password'];
    // 使用 PHP 內建的、最安全的方式來產生密碼雜湊值
    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
    
    // 產生可以直接複製使用的 SQL 指令
    $sql_command = "UPDATE `settings` SET `setting_value` = '" . addslashes($new_hash) . "' WHERE `setting_key` = 'admin_password';";
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理員密碼重設工具</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .result-box {
            margin-top: 2rem;
            padding: 1.5rem;
            background-color: #eaf5ff;
            border: 1px solid #b8d9f3;
            border-radius: 8px;
            text-align: left;
            word-wrap: break-word;
        }
        .result-box h3 {
            margin-top: 0;
        }
        .result-box code {
            display: block;
            background-color: #333;
            color: #fff;
            padding: 1rem;
            border-radius: 5px;
            margin-top: 1rem;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="site-wrapper">
        <main class="main-content">
            <div class="container">
                <h1>管理員密碼重設工具</h1>
                <p>請在此輸入您想設定的新密碼，然後將產生的 SQL 指令複製到您的資料庫管理工具 (如 phpMyAdmin) 中執行。</p>

                <form method="POST">
                    <div class="form-group" style="flex-direction: column; align-items: flex-start; text-align: left;">
                        <label for="new_password">輸入新密碼：</label>
                        <input type="text" name="new_password" id="new_password" required style="width: 100%; padding: 10px; box-sizing: border-box;">
                    </div>
                    <button type="submit" style="margin-top: 1rem;">產生 SQL 指令</button>
                </form>

                <?php if ($sql_command): ?>
                    <div class="result-box">
                        <h3>產生成功！</h3>
                        <p>請複製以下完整的 SQL 指令，並在您的資料庫中執行它，以更新您的管理員密碼。</p>
                        <code><?php echo htmlspecialchars($sql_command); ?></code>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
