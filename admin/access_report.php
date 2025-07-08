<?php
// /admin/access_report.php

session_start();
require_once '../config.php';

// 權限檢查
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit();
}

$conn = get_db_connection();
if (!$conn) {
    die("資料庫連線失敗。");
}

// 分頁邏輯
$records_per_page = 50;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

$total_records_result = $conn->query("SELECT COUNT(*) FROM access_logs");
$total_records = $total_records_result->fetch_row()[0];
$total_pages = ceil($total_records / $records_per_page);

$query = "SELECT al.log_id, al.access_time, al.ip_address, al.referrer, al.user_agent, su.short_code, su.original_url FROM access_logs al LEFT JOIN short_urls su ON al.url_id = su.url_id ORDER BY al.access_time DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $records_per_page, $offset);
$stmt->execute();
$logs = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>存取紀錄匯報</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; text-align: left; }
        .admin-header h1 { margin: 0; }
        .table-wrapper { overflow-x: auto; }
        .admin-page table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .admin-page th, .admin-page td { word-wrap: break-word; padding: 12px; border: 1px solid #ddd; text-align: left; }
        .admin-page th { background-color: #f2f2f2; }
        .pagination { margin-top: 2rem; text-align: center; }
        .pagination a { margin: 0 5px; text-decoration: none; padding: 8px 12px; border: 1px solid #ddd; color: #3498db; }
        .pagination a.active { background-color: #3498db; color: white; }
        .pagination a:hover { background-color: #f2f2f2; }
        .chart-section { padding: 2rem; border: 1px solid #ddd; border-radius: 8px; margin-top: 2rem; background-color: #fff; }
        .chart-controls { margin-bottom: 1.5rem; text-align: center; }
        .chart-controls label { margin-right: 10px; font-weight: bold; }
        .chart-controls select { padding: 8px; border-radius: 5px; }
        .chart-container { position: relative; height: 50vh; width: 100%; max-width: 900px; margin: auto; }
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); display: flex; justify-content: center; align-items: center; z-index: 1000; }
        .modal-overlay.hidden { display: none; }
        .modal-content { background-color: #fff; padding: 2rem; border-radius: 8px; width: 90%; max-width: 800px; max-height: 80vh; overflow-y: auto; position: relative; }
        .modal-close { position: absolute; top: 10px; right: 20px; font-size: 2rem; font-weight: bold; cursor: pointer; color: #aaa; }
        .modal-close:hover { color: #000; }
        .modal-content h2 { margin-top: 0; }
        .details-table { width: 100%; border-collapse: collapse; }
        .details-table th, .details-table td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 0.9rem; }
    </style>
</head>
<body class="admin-page">
    <div class="site-wrapper">
        <main class="main-content">
            <div class="container">
                <div class="admin-header">
                    <h1><i class="fa-solid fa-chart-line"></i> 存取紀錄匯報</h1>
                    <a href="index.php"><button>返回儀表板</button></a>
                </div>

                <div class="chart-section">
                    <div class="chart-controls">
                        <label for="chart-type-select">選擇統計圖表類型：</label>
                        <select id="chart-type-select">
                            <option value="short_url">熱門短網址 (Top 10)</option>
                            <option value="ip">訪問者 IP 來源 (Top 10)</option>
                            <option value="user_agent">瀏覽器分佈</option>
                            <option value="os">作業系統分佈</option>
                        </select>
                    </div>
                    <div class="chart-container">
                        <canvas id="access-chart"></canvas>
                    </div>
                </div>

                <h2 style="margin-top: 2rem;">詳細存取日誌</h2>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 15%;">訪問時間</th>
                                <th style="width: 10%;">短網址</th>
                                <th style="width: 25%;">原始網址</th>
                                <th style="width: 10%;">訪問者 IP</th>
                                <th style="width: 40%;">來源頁面 / User Agent</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($logs->num_rows > 0): ?>
                                <?php while($log = $logs->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($log['access_time']); ?></td>
                                        <td><?php echo htmlspecialchars($log['short_code']); ?></td>
                                        <td title="<?php echo htmlspecialchars($log['original_url']); ?>" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($log['original_url']); ?></td>
                                        <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                        <td title="<?php echo htmlspecialchars($log['user_agent']); ?>" style="font-size: 0.8rem;">
                                            <strong>來源:</strong> <?php echo htmlspecialchars($log['referrer'] ?: 'N/A'); ?><br>
                                            <strong>UA:</strong> <?php echo htmlspecialchars($log['user_agent'] ?: 'N/A'); ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">沒有任何存取紀錄。</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" class="<?php if ($page == $i) echo 'active'; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
            </div>
        </main>
        <?php 
            if (file_exists('../templates/footer.php')) {
                require_once '../templates/footer.php';
            }
        ?>
    </div>
    
    <div id="click-details-modal" class="modal-overlay hidden">
        <div class="modal-content">
            <span class="modal-close">&times;</span>
            <h2>點擊詳情: <span id="click-details-shortcode"></span></h2>
            <div id="click-details-content"></div>
        </div>
    </div>

    <script src="https://www.chartjs.org/dist/master/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const select = document.getElementById('chart-type-select');
            const ctx = document.getElementById('access-chart').getContext('2d');
            const modal = document.getElementById('click-details-modal');
            const modalCloseBtn = modal.querySelector('.modal-close');
            const modalShortcodeSpan = document.getElementById('click-details-shortcode');
            const modalContentDiv = document.getElementById('click-details-content');
            let accessChart = null;

            async function updateChart(type) {
                try {
                    const response = await fetch(`api.php?action=get_access_stats&type=${type}`);
                    const result = await response.json();

                    if (result.success) {
                        const labels = result.data.map(item => item.label);
                        const values = result.data.map(item => item.value);
                        
                        const isPieChart = (type === 'user_agent' || type === 'os');
                        const chartType = isPieChart ? 'pie' : 'bar';

                        if (accessChart) {
                            accessChart.destroy();
                        }

                        const chartOptions = {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: isPieChart,
                                    position: 'top',
                                },
                                // 修正：美化並修正 Tooltip 顯示
                                tooltip: {
                                    backgroundColor: '#333',
                                    titleFont: { size: 14 },
                                    bodyFont: { size: 12 },
                                    padding: 10,
                                    cornerRadius: 4,
                                    callbacks: {
                                        label: function(context) {
                                            let label = context.label || '';
                                            if (label) {
                                                label += ': ';
                                            }
                                            let value;
                                            if (isPieChart) {
                                                value = context.parsed;
                                            } else {
                                                value = context.parsed.x;
                                            }
                                            
                                            if (value !== null) {
                                                label += value + ' 次';
                                            }
                                            return label;
                                        }
                                    }
                                }
                            }
                        };

                        if (!isPieChart) {
                            chartOptions.indexAxis = 'y';
                            chartOptions.scales = { x: { beginAtZero: true } };
                            chartOptions.onClick = async (evt) => {
                                const points = accessChart.getElementsAtEventForMode(evt, 'nearest', { intersect: true }, true);
                                if (points.length) {
                                    const firstPoint = points[0];
                                    const label = accessChart.data.labels[firstPoint.index];
                                    await showClickDetails(label);
                                }
                            };
                        }

                        accessChart = new Chart(ctx, {
                            type: chartType,
                            data: {
                                labels: labels,
                                datasets: [{
                                    label: select.options[select.selectedIndex].text,
                                    data: values,
                                    backgroundColor: isPieChart ? [
                                        'rgba(255, 99, 132, 0.7)', 'rgba(54, 162, 235, 0.7)',
                                        'rgba(255, 206, 86, 0.7)', 'rgba(75, 192, 192, 0.7)',
                                        'rgba(153, 102, 255, 0.7)', 'rgba(255, 159, 64, 0.7)',
                                        'rgba(99, 255, 132, 0.7)', 'rgba(235, 54, 162, 0.7)'
                                    ] : 'rgba(52, 152, 219, 0.5)',
                                    borderColor: isPieChart ? '#fff' : 'rgba(52, 152, 219, 1)',
                                    borderWidth: 1
                                }]
                            },
                            options: chartOptions
                        });
                    } else {
                        console.error('API 請求失敗:', result.message);
                    }
                } catch (error) {
                    console.error('獲取圖表數據時發生錯誤:', error);
                }
            }

            async function showClickDetails(shortCode) {
                modalShortcodeSpan.textContent = shortCode;
                modalContentDiv.innerHTML = '<p>載入中...</p>';
                modal.classList.remove('hidden');

                try {
                    const response = await fetch(`api.php?action=get_url_click_details&short_code=${shortCode}`);
                    const result = await response.json();
                    if (result.success && result.details.length > 0) {
                        let tableHtml = '<table class="details-table"><thead><tr><th>IP 位址</th><th>User Agent</th></tr></thead><tbody>';
                        result.details.forEach(detail => {
                            tableHtml += `<tr><td>${detail.ip_address}</td><td>${detail.user_agent}</td></tr>`;
                        });
                        tableHtml += '</tbody></table>';
                        modalContentDiv.innerHTML = tableHtml;
                    } else {
                        modalContentDiv.innerHTML = '<p>沒有找到相關的點擊紀錄。</p>';
                    }
                } catch (error) {
                    modalContentDiv.innerHTML = `<p style="color: red;">載入詳情失敗: ${error.message}</p>`;
                }
            }

            function closeModal() {
                modal.classList.add('hidden');
            }

            select.addEventListener('change', function() {
                updateChart(this.value);
            });
            
            modalCloseBtn.addEventListener('click', closeModal);
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeModal();
                }
            });

            updateChart(select.value);
        });
    </script>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>
