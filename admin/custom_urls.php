<?php
// /admin/custom_urls.php
session_start();
// 權限檢查
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
    <title>自訂短網址管理</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .admin-header h1 { margin: 0; }
        .form-section { background-color: #f8f9fa; padding: 2rem; border-radius: 8px; margin-bottom: 2rem; border: 1px solid #dee2e6;}
        .form-section h2 { margin-top: 0; }
        .form-row { display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end; }
        .form-row .form-group { flex: 1; min-width: 200px; }
        .form-group label { display: block; text-align: left; margin-bottom: 0.5rem; }
        .form-group input { width: 100%; padding: 10px; box-sizing: border-box; border-radius: 5px; border: 1px solid #ddd; }
        .form-row button { height: 42px; }
        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #f2f2f2; }
        .btn-secondary { background-color: #6c757d; }
    </style>
</head>
<body class="admin-page">
    <div class="site-wrapper">
        <main class="main-content">
            <div class="container">
                <div class="admin-header">
                    <h1><i class="fa-solid fa-pen-ruler"></i> 自訂短網址管理</h1>
                    <a href="index.php"><button>返回儀表板</button></a>
                </div>

                <div class="form-section">
                    <h2 id="form-title">新增自訂短網址</h2>
                    <form id="custom-url-form">
                        <input type="hidden" id="url_id" name="url_id">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="original_url">原始長網址</label>
                                <input type="url" id="original_url" name="original_url" placeholder="https://..." required>
                            </div>
                            <div class="form-group">
                                <label for="custom_code">自訂短碼 (2-8 字元)</label>
                                <input type="text" id="custom_code" name="custom_code" pattern="[a-zA-Z0-9-]{2,8}" title="請輸入 2-8 個英文字母、數字或連字號" required>
                            </div>
                            <button type="submit" id="submit-btn">儲存</button>
                            <button type="button" id="cancel-edit" class="hidden btn-secondary">取消編輯</button>
                        </div>
                    </form>
                </div>

                <h2>所有自訂短網址</h2>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>自訂短碼</th>
                                <th>原始網址</th>
                                <th>點擊次數</th>
                                <th>創建時間</th>
                                <th style="width: 15%;">操作</th>
                            </tr>
                        </thead>
                        <tbody id="custom-urls-table-body">
                            <!-- 資料將由 JavaScript 載入 -->
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
        <?php if (file_exists('../templates/footer.php')) require_once '../templates/footer.php'; ?>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('custom-url-form');
            const tableBody = document.getElementById('custom-urls-table-body');
            const urlIdInput = document.getElementById('url_id');
            const originalUrlInput = document.getElementById('original_url');
            const customCodeInput = document.getElementById('custom_code');
            const cancelEditBtn = document.getElementById('cancel-edit');
            const formTitle = document.getElementById('form-title');
            const submitBtn = document.getElementById('submit-btn');

            async function getCustomUrls() {
                try {
                    const response = await fetch('api.php?action=get_custom_urls');
                    const result = await response.json();
                    if (result.success) {
                        tableBody.innerHTML = '';
                        result.links.forEach(link => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td><a href="../${escapeHtml(link.short_code)}" target="_blank">${escapeHtml(link.short_code)}</a></td>
                                <td title="${escapeHtml(link.original_url)}" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${escapeHtml(link.original_url)}</td>
                                <td>${link.click_count}</td>
                                <td>${link.created_at}</td>
                                <td>
                                    <button class="edit-btn" data-id="${link.url_id}" data-url="${escapeHtml(link.original_url)}" data-code="${escapeHtml(link.short_code)}">編輯</button>
                                    <button class="delete-btn" data-id="${link.url_id}" style="background-color: #e74c3c;">刪除</button>
                                </td>
                            `;
                            tableBody.appendChild(row);
                        });
                    }
                } catch (error) {
                    console.error("獲取自訂網址列表失敗:", error);
                    tableBody.innerHTML = '<tr><td colspan="5">載入失敗，請稍後再試。</td></tr>';
                }
            }

            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                const urlId = urlIdInput.value;
                const action = urlId ? 'update_custom_url' : 'add_custom_url';
                const payload = {
                    original_url: originalUrlInput.value,
                    custom_code: customCodeInput.value,
                    url_id: urlId
                };
                
                try {
                    const response = await fetch(`api.php?action=${action}`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    const result = await response.json();
                    if (result.success) {
                        resetForm();
                        await getCustomUrls();
                    } else {
                        alert('錯誤: ' + result.message);
                    }
                } catch (error) {
                    alert('操作失敗，請檢查網路連線。');
                }
            });

            tableBody.addEventListener('click', async function(e) {
                if (e.target.classList.contains('edit-btn')) {
                    const btn = e.target;
                    urlIdInput.value = btn.dataset.id;
                    originalUrlInput.value = btn.dataset.url;
                    customCodeInput.value = btn.dataset.code;
                    customCodeInput.readOnly = true; // 編輯模式下不允許修改短碼
                    cancelEditBtn.classList.remove('hidden');
                    formTitle.textContent = '編輯自訂短網址';
                    submitBtn.textContent = '更新';
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
                if (e.target.classList.contains('delete-btn')) {
                    if (confirm('您確定要刪除這個自訂短網址嗎？')) {
                        const urlId = e.target.dataset.id;
                        try {
                            const response = await fetch('api.php?action=delete_link', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ id: urlId })
                            });
                            const result = await response.json();
                            if (result.success) {
                                await getCustomUrls();
                            } else {
                                alert('刪除失敗: ' + result.message);
                            }
                        } catch (error) {
                            alert('操作失敗，請檢查網路連線。');
                        }
                    }
                }
            });

            cancelEditBtn.addEventListener('click', resetForm);

            function resetForm() {
                form.reset();
                urlIdInput.value = '';
                customCodeInput.readOnly = false;
                cancelEditBtn.classList.add('hidden');
                formTitle.textContent = '新增自訂短網址';
                submitBtn.textContent = '儲存';
            }

            function escapeHtml(unsafe) {
                return unsafe.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
            }

            getCustomUrls();
        });
    </script>
</body>
</html>
