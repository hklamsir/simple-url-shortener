// /assets/js/main.js
document.addEventListener('DOMContentLoaded', function () {
    const copyBtn = document.getElementById('copy-btn');
    const shortUrlLink = document.getElementById('short-url');
    const qrcodeDiv = document.getElementById('qrcode');
    const resultDiv = document.getElementById('result');

    /**
     * 顯示一個 Toast 提示框
     * @param {string} message - 要顯示的訊息
     * @param {string} type - 'success' 或 'error'
     */
    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = 'toast-message';
        toast.textContent = message;
        
        // 移除舊的 toast (如果有的話)
        const existingToast = document.querySelector('.toast-message');
        if (existingToast) {
            existingToast.remove();
        }

        document.body.appendChild(toast);

        // 3 秒後自動移除
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }

    // --- 為結果區塊觸發動畫 ---
    if (resultDiv) {
        // 使用 setTimeout 確保瀏覽器有足夠的時間渲染元素，讓動畫可見
        setTimeout(() => {
            resultDiv.classList.add('animate');
        }, 50);

        // 如果是成功訊息，則顯示 Toast
        if (shortUrlLink) {
            showToast('成功生成！');
        }
    }

    // --- 處理複製按鈕和 QR Code ---
    if (copyBtn && shortUrlLink) {
        copyBtn.addEventListener('click', function () {
            navigator.clipboard.writeText(shortUrlLink.href).then(() => {
                const originalContent = copyBtn.innerHTML;
                copyBtn.innerHTML = '<i class="fa-solid fa-check"></i> 已複製';
                copyBtn.disabled = true;

                setTimeout(() => {
                    copyBtn.innerHTML = originalContent;
                    copyBtn.disabled = false;
                }, 2000);
            }).catch(err => {
                console.error('複製失敗:', err);
                alert('複製失敗，請手動複製。');
            });
        });

        if (qrcodeDiv && typeof QRCode !== 'undefined') {
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
