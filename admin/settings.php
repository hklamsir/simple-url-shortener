<?php
// /admin/settings.php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>設定</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .admin-header h1 { margin: 0; }
        .settings-form { max-width: 500px; margin: auto; text-align: left; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; }
        .form-group input { width: 100%; padding: 10px; box-sizing: border-box; border-radius: 5px; border: 1px solid #ddd; }
        #message-box { margin-top: 1rem; padding: 10px; border-radius: 5px; display: none; }
        .message-success { background-color: #d4edda; color: #155724; }
        .message-error { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body class="admin-page">
    <div class="site-wrapper">
        <main class="main-content">
            <div class="container">
                <div class="admin-header">
                    <h1><i class="fa-solid fa-gears"></i> 設定</h1>
                    <a href="index.php"><button>返回儀表板</button></a>
                </div>
                <div class="settings-form">
                    <h2>更改管理員密碼</h2>
                    <form id="password-form">
                        <div class="form-group">
                            <label for="current_password">目前密碼</label>
                            <input type="password" id="current_password" required>
                        </div>
                        <div class="form-group">
                            <label for="new_password">新密碼</label>
                            <input type="password" id="new_password" required>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">確認新密碼</label>
                            <input type="password" id="confirm_password" required>
                        </div>
                        <button type="submit">更新密碼</button>
                    </form>
                    <div id="message-box"></div>
                </div>
            </div>
        </main>
        <?php if (file_exists('../templates/footer.php')) require_once '../templates/footer.php'; ?>
    </div>
    <script>
        document.getElementById('password-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            const currentPassword = document.getElementById('current_password').value;
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const messageBox = document.getElementById('message-box');

            if (newPassword !== confirmPassword) {
                showMessage('新密碼與確認密碼不相符。', 'error');
                return;
            }

            const response = await fetch('api.php?action=update_password', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    current_password: currentPassword,
                    new_password: newPassword
                })
            });
            const result = await response.json();
            if (result.success) {
                showMessage('密碼已成功更新！', 'success');
                document.getElementById('password-form').reset();
            } else {
                showMessage('錯誤: ' + result.message, 'error');
            }
        });

        function showMessage(text, type) {
            const messageBox = document.getElementById('message-box');
            messageBox.textContent = text;
            messageBox.className = type === 'success' ? 'message-success' : 'message-error';
            messageBox.style.display = 'block';
        }
    </script>
</body>
</html>
