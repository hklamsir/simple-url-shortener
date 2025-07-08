// /assets/js/main.js
document.addEventListener('DOMContentLoaded', function () {
    // 表單提交現在由瀏覽器預設行為處理。
    // JavaScript 只負責在頁面重新載入後，為已存在的元素附加功能。

    const copyBtn = document.getElementById('copy-btn');
    const shortUrlLink = document.getElementById('short-url');
    const qrcodeDiv = document.getElementById('qrcode');

    // 只有在成功生成短網址後，這些元素才會存在
    if (copyBtn && shortUrlLink) {
        // --- 處理複製按鈕 ---
        copyBtn.addEventListener('click', function () {
            // 使用 Clipboard API 複製連結
            navigator.clipboard.writeText(shortUrlLink.href).then(() => {
                copyBtn.textContent = '已複製！';
                setTimeout(() => {
                    copyBtn.textContent = '複製';
                }, 2000);
            }).catch(err => {
                // 如果剪貼簿 API 失敗，提供備用提示
                console.error('複製失敗:', err);
                alert('複製失敗，請手動複製。');
            });
        });

        // --- 新增：處理 QR Code 生成 ---
        // 檢查 QR Code 容器和函式庫是否存在
        if (qrcodeDiv && typeof QRCode !== 'undefined') {
            // 直接使用已存在於頁面上的短網址來生成 QR Code
            new QRCode(qrcodeDiv, {
                text: shortUrlLink.href,
                width: 128,
                height: 128,
                colorDark : "#000000",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.H
            });
        }
    }
});
