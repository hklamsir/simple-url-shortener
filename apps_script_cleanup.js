// Google Apps Script

// 請將下面的 URL 和密鑰替換為您自己的設定
const CLEANUP_URL = "http://您的網域/cron_cleanup.php";
const SECRET_KEY = "您在 config.php 中設定的密鑰";

/**
 * 主要的執行函式
 */
function runCleanup() {
  const sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName("清理日誌") || SpreadsheetApp.getActiveSpreadsheet().insertSheet("清理日誌");
  
  // 檢查並寫入標題列
  if (sheet.getLastRow() === 0) {
    sheet.appendRow(["執行時間", "狀態", "訊息", "已刪除的請求紀錄", "已刪除的短網址"]);
    sheet.getRange("A1:E1").setFontWeight("bold");
  }
  
  const fullUrl = `${CLEANUP_URL}?key=${encodeURIComponent(SECRET_KEY)}`;
  
  try {
    const options = {
      'method' : 'get',
      'muteHttpExceptions': true // 即使發生錯誤，也繼續執行以記錄錯誤訊息
    };
    
    const response = UrlFetchApp.fetch(fullUrl, options);
    const responseCode = response.getResponseCode();
    const responseText = response.getContentText();
    
    let status = '失敗';
    let message = `HTTP 狀態碼: ${responseCode}`;
    let deletedRateLimits = 0;
    let deletedUrls = 0;

    if (responseCode === 200) {
      const jsonResponse = JSON.parse(responseText);
      status = jsonResponse.status;
      message = jsonResponse.message;
      deletedRateLimits = jsonResponse.deleted_rate_limits || 0;
      deletedUrls = jsonResponse.deleted_urls || 0;
    } else {
      // 嘗試解析錯誤訊息
      try {
        const errorJson = JSON.parse(responseText);
        message += ` - ${errorJson.message}`;
      } catch (e) {
        // 如果回應的不是 JSON，則直接記錄
        message += ` - ${responseText}`;
      }
    }
    
    // 將結果寫入試算表
    sheet.appendRow([new Date(), status, message, deletedRateLimits, deletedUrls]);
    
  } catch (e) {
    // 處理網路連線等更嚴重的錯誤
    sheet.appendRow([new Date(), "執行失敗", e.toString(), 0, 0]);
  }
}

/**
 * 建立觸發器
 * 執行一次此函式，即可設定好每小時自動執行的排程。
 */
function createTrigger() {
  // 在執行前，先刪除所有舊的同名觸發器，避免重複執行
  const triggers = ScriptApp.getProjectTriggers();
  for (const trigger of triggers) {
    if (trigger.getHandlerFunction() === "runCleanup") {
      ScriptApp.deleteTrigger(trigger);
    }
  }
  
  // 建立一個新的觸發器，每小時執行一次 runCleanup 函式
  ScriptApp.newTrigger("runCleanup")
    .timeBased()
    .everyHours(1)
    .create();
  
  SpreadsheetApp.getUi().alert("自動清理觸發器已成功建立！將會每小時執行一次。");
}
