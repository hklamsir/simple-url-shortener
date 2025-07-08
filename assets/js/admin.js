// /assets/js/admin.js
document.addEventListener('DOMContentLoaded', function() {
    const totalLinksEl = document.getElementById('total-links');
    const linksTableBody = document.getElementById('links-table-body');
    let clicksChart;
    let currentModalUrlId = null; // 新增：用於追蹤當前彈出視窗顯示的是哪個 URL 的檢舉

    if (totalLinksEl && linksTableBody) {
        const reportModal = document.getElementById('report-modal');
        const modalCloseBtn = reportModal.querySelector('.modal-close');
        const reportDetailsContent = document.getElementById('report-details-content');

        // 主事件監聽器
        document.body.addEventListener('click', async function(e) {
            // 查看檢舉紀錄
            if (e.target.classList.contains('view-reports-link')) {
                e.preventDefault();
                currentModalUrlId = e.target.dataset.urlId; // 記錄當前 URL ID
                await openReportModal(currentModalUrlId);
            }

            // 刪除主列表中的連結
            if (e.target.classList.contains('delete-btn')) {
                const linkId = e.target.dataset.id;
                if (confirm('您確定要刪除這個短網址嗎？所有相關的點擊和檢舉紀錄將一併刪除。')) {
                    await deleteLink(linkId);
                }
            }

            // 刪除彈出視窗中的檢舉紀錄
            if (e.target.classList.contains('delete-report-btn')) {
                const reportId = e.target.dataset.reportId;
                if (confirm('您確定要刪除這條檢舉紀錄嗎？')) {
                    await deleteReport(reportId);
                }
            }

            // 關閉彈出視窗
            if (e.target.classList.contains('modal-close') || e.target.classList.contains('modal-overlay')) {
                closeModal();
            }
        });

        // 開啟並載入檢舉紀錄彈出視窗
        async function openReportModal(urlId) {
            reportDetailsContent.innerHTML = '<p>載入中...</p>';
            reportModal.classList.remove('hidden');
            try {
                const response = await fetch(`api.php?action=get_report_details&id=${urlId}`);
                const data = await response.json();
                if (data.success) {
                    renderReportDetails(data.reports);
                } else { throw new Error(data.message); }
            } catch (error) {
                reportDetailsContent.innerHTML = `<p style="color: red;">載入失敗：${error.message}</p>`;
            }
        }

        // 刪除主連結
        async function deleteLink(linkId) {
            try {
                const response = await fetch('api.php?action=delete_link', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: linkId })
                });
                const result = await response.json();
                if (result.success) {
                    fetchData();
                } else { alert('刪除失敗：' + result.message); }
            } catch (error) { alert('發生錯誤：' + error.message); }
        }
        
        // 刪除單筆檢舉
        async function deleteReport(reportId) {
            try {
                const response = await fetch('api.php?action=delete_report', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ report_id: reportId })
                });
                const result = await response.json();
                if (result.success) {
                    await openReportModal(currentModalUrlId); // 重新載入當前彈出視窗的內容
                    await fetchData(); // 重新載入主列表以更新檢舉次數
                } else { alert('刪除檢舉失敗：' + result.message); }
            } catch (error) { alert('發生錯誤：' + error.message); }
        }

        // 渲染檢舉紀錄表格
        function renderReportDetails(reports) {
            if (reports.length === 0) {
                reportDetailsContent.innerHTML = '<p>沒有檢舉紀錄。</p>';
                return;
            }
            let tableHtml = '<table class="report-details-table"><thead><tr><th>檢舉時間</th><th>檢舉者 IP</th><th>原因</th><th>操作</th></tr></thead><tbody>';
            reports.forEach(report => {
                tableHtml += `
                    <tr>
                        <td>${report.report_time}</td>
                        <td>${escapeHtml(report.reporter_ip)}</td>
                        <td>${escapeHtml(report.reason) || '<i>未提供</i>'}</td>
                        <td><button class="delete-report-btn" data-report-id="${report.report_id}">刪除</button></td>
                    </tr>
                `;
            });
            tableHtml += '</tbody></table>';
            reportDetailsContent.innerHTML = tableHtml;
        }

        function closeModal() {
            reportModal.classList.add('hidden');
            currentModalUrlId = null; // 關閉時清除 ID
        }

        async function fetchData() {
            // (此函式保持不變)
            try {
                const statsResponse = await fetch('api.php?action=get_stats');
                const statsData = await statsResponse.json();
                if (statsData.success) {
                    document.getElementById('total-clicks').textContent = statsData.stats.total_clicks || 0;
                    totalLinksEl.textContent = statsData.stats.total_links || 0;
                    renderChart(statsData.chart_data);
                }
                const linksResponse = await fetch('api.php?action=get_links');
                const linksData = await linksResponse.json();
                if (linksData.success) { renderTable(linksData.links); }
            } catch (error) { console.error('獲取數據失敗:', error); }
        }

        function renderChart(data) {
            // (此函式保持不變)
            const labels = [], clicks = [], dateMap = new Map();
            for (let i = 6; i >= 0; i--) { const d = new Date(); d.setDate(d.getDate() - i); dateMap.set(d.toISOString().split('T')[0], 0); }
            data.forEach(item => dateMap.set(item.date, parseInt(item.clicks)));
            dateMap.forEach((value, key) => { labels.push(key); clicks.push(value); });
            if (clicksChart) clicksChart.destroy();
            clicksChart = new Chart(document.getElementById('clicks-chart'), { type: 'line', data: { labels, datasets: [{ label: '每日點擊次數', data: clicks, borderColor: '#3498db', fill: true }] }, options: { responsive: true, maintainAspectRatio: false } });
        }

        function renderTable(links) {
            // (此函式保持不變)
            linksTableBody.innerHTML = '';
            if (links.length === 0) { linksTableBody.innerHTML = '<tr><td colspan="7">目前沒有任何短網址。</td></tr>'; return; }
            links.forEach(link => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="col-url"><span class="original-url" title="${escapeHtml(link.original_url)}">${escapeHtml(link.original_url)}</span></td>
                    <td class="col-shortcode"><a href="../${link.short_code}" target="_blank">${link.short_code}</a></td>
                    <td class="col-clicks">${link.click_count}</td>
                    <td class="col-reports">${link.report_count > 0 ? `<a href="#" class="view-reports-link" data-url-id="${link.url_id}">${link.report_count}</a>` : '0'}</td>
                    <td class="col-created">${link.created_at}</td>
                    <td class="col-expires">${link.expires_at || '永久'}</td>
                    <td class="col-actions"><button class="delete-btn" data-id="${link.url_id}">刪除</button></td>
                `;
                linksTableBody.appendChild(row);
            });
        }

        function escapeHtml(unsafe) {
            return unsafe.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }

        fetchData();
    }
});
