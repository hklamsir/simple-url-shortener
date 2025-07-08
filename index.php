<?php
// 在頁面最頂部啟用 session
session_start();

// 從 session 中讀取統一的 flash message
$flash_message = null;
if (isset($_SESSION['flash_message'])) {
    $flash_message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']); // 顯示後立即清除，避免重複顯示
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>極簡短網址服務</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <div class="site-wrapper">
        <main class="main-content">
            <div class="container">
                <h1><i class="fa-solid fa-link" style="margin-right: 0.75rem;"></i>極簡短網址服務</h1>
                <p>一個快速、免費且開源的短網址生成器。</p>

                <form id="shorten-form" action="shortener.php" method="POST">
                    <input type="url" name="url" id="original-url" placeholder="請在此貼上您的長網址" required>
                    <div class="form-group">
                        <label for="expiration"><i class="fa-solid fa-calendar-days" style="margin-right: 0.5rem;"></i>有效期限：</label>
                        <select name="expiration" id="expiration">
                            <option value="never" selected>永久</option>
                            <option value="P1D">1 天</option>
                            <option value="P7D">7 天</option>
                            <option value="P1M">1 個月</option>
                            <option value="P3M">3 個月</option>
                            <option value="P1Y">1 年</option>
                        </select>
                    </div>
                    <button type="submit"><i class="fa-solid fa-wand-magic-sparkles" style="margin-right: 0.5rem;"></i>生成短網址</button>
                </form>

                <!-- 結果區塊由 PHP 條件式地渲染 -->
                <?php if ($flash_message): ?>
                    <div id="result">
                        <?php if ($flash_message['type'] === 'success'): ?>
                            <h2>您的短網址：</h2>
                            <div class="short-url-container">
                                <a href="<?php echo htmlspecialchars($flash_message['text']); ?>" id="short-url" target="_blank"><?php echo htmlspecialchars(preg_replace('/^https?:\/\//', '', $flash_message['text'])); ?></a>
                                <button type="button" id="copy-btn"><i class="fa-solid fa-copy"></i> 複製</button>
                            </div>
                            <div id="qrcode-container">
                                <div id="qrcode"></div>
                            </div>
                            <p id="message" style="color: #27ae60; font-weight: bold;">生成成功！</p>
                        <?php else: // 'error' ?>
                            <p id="message" style="color: #e74c3c; font-weight: bold;"><?php echo htmlspecialchars($flash_message['text']); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['error'])): ?>
                    <div class="error-message">
                        <?php
                            if ($_GET['error'] === 'notfound') echo '錯誤：找不到您要訪問的短網址。';
                            if ($_GET['error'] === 'expired') echo '錯誤：您訪問的短網址已過期。';
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>

        <!-- 更新：連結容器 -->
        <div class="sub-links-container">
            <a href="term_of_use.php">服務條款</a>
            <a href="report.php">檢舉惡意連結</a>
        </div>

        <?php 
            // 引入共用的頁尾檔案
            if (file_exists('templates/footer.php')) {
                require_once 'templates/footer.php';
            }
        ?>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
