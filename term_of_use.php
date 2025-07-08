<?php
// /term_of_use.php
session_start();
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>服務條款 - 極簡短網址服務</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .terms-container {
            text-align: left;
            line-height: 1.8;
        }
        .terms-container h2 {
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            margin-top: 2rem;
            margin-bottom: 1rem;
        }
        .terms-container ul {
            padding-left: 20px;
            list-style-type: disc;
        }
        .terms-container li {
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="site-wrapper">
        <main class="main-content">
            <div class="container terms-container">
                <h1><i class="fa-solid fa-file-contract" style="margin-right: 0.75rem;"></i>服務條款</h1>
                <p>本服務條款規範了使用「極簡短網址服務」的規則，包括存取和使用網站提供的任何內容、功能及服務。</p>
                <p>在使用本網站前，請務必仔細閱讀服務條款。此頁面說明了「極簡短網址服務」的使用條款，用戶使用本網站即表示同意接受這些使用條款。</p>
                <p>URL縮短服務是一項將網站、部落格、論壇或社交網絡的連結轉換為可分享的短連結的服務。</p>

                <h2>使用條件</h2>
                <p>為了提供免費服務，使用我們的服務時必須同意以下使用條件：</p>
                <ul>
                    <li>每100天至少需有一次點擊的短連結，否則將被停用。</li>
                    <li>已停用的短連結可能會開放給其他用戶使用。</li>
                    <li>禁止建立導向以下內容的短連結：
                        <ul>
                            <li>受版權保護的內容、影片、音頻、圖片、書籍、遊戲或任何材料</li>
                            <li>侵犯第三方知識產權或其他權利的內容</li>
                            <li>未經授權的電影或電視節目串流</li>
                            <li>下載檔案</li>
                            <li>散布釣魚、惡意軟體或病毒的內容</li>
                            <li>濫用或可疑內容</li>
                            <li>色情或性相關內容</li>
                            <li>暴力或歧視性內容</li>
                            <li>與毒品、武器或酒精相關的內容</li>
                            <li>令人不適、露骨或冒犯性內容（可能對用戶造成任何不適）</li>
                            <li>彈出視窗、惡意腳本及代碼</li>
                            <li>重新導向至其他頁面的頁面</li>
                            <li>不存在、空白或已過期的頁面</li>
                        </ul>
                    </li>
                </ul>
                <p>任何符合上述條件的短連結將被停用，所有建立的連結均會由我們的團隊審核。若發現任何違反服務條款的濫用行為，相關短連結將被無預警刪除。</p>

                <h2>免責聲明</h2>
                <p>「極簡短網址服務」的免費服務存在一些限制，因此我們無法保證網站或服務能始終不間斷、安全或無錯誤。本站員工或所有者均不對網站上的任何錯誤、遺漏或您可能遭受的任何損害負責。</p>
                
                <h2>用戶責任</h2>
                <p>使用本網站即表示您需對自身行為結果負責。您同意對因使用本網站服務或其他資源而遭受的任何損害或損失承擔全部責任，並承諾在採取任何行動或實施任何計畫前，應自行判斷並進行盡職調查。</p>

                <h2>錯誤與遺漏</h2>
                <p>本網站提供免費服務，但不保證其內容絕對準確、完整或最新。儘管我們已採取合理措施確保資訊正確性，仍無法宣稱網站完全無誤。使用本網站即表示您同意資訊可能存在誤差，並有責任在採取行動前自行核實。</p>

                <h2>責任限制</h2>
                <p>您同意免除本網站對您或相關人員因使用本站資訊或資源所遭受的任何責任或損失（包括直接、間接、特殊、偶發、衡平或衍生性損害）。</p>
                <p>本站及其開發者不對其資訊、軟體、產品及服務的適用性、可靠性、可用性、時效性與準確性作任何聲明。依據法律允許之最大範圍，所有內容均以「現狀」提供，不附帶任何擔保或條件。本站及其開發者特此排除所有關於適銷性、特定用途適用性、所有權及非侵權的擔保。</p>
                <p>本站開發者不對任何損害（包括使用或數據損失、利潤損失）負責，無論是因服務使用、延遲或無法使用網站、服務提供失敗，或透過本站獲取的任何資訊、軟體、產品及服務所致。此限制適用於所有損害原因（包括契約、侵權、過失、嚴格責任等），但若您所在司法管轄區不允許排除或限制附帶或衍生損害賠償，則此條款可能不適用於您。</p>

                <h2>條款更新</h2>
                <p>「極簡短網址服務」保留隨時更新或變更使用條款的權利，最新版本將始終公布於本頁面。</p>

                <div style="text-align: center; margin-top: 3rem;">
                    <a href="index.php"><button>返回主頁</button></a>
                </div>
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
