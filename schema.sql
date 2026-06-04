-- Simple Short URL 資料庫 Schema
-- 版本：1.1（含安全修復索引建議）
-- 日期：2025-07-01
-- 說明：從程式碼逆向推導的完整建表語句 + 索引

-- ============================================
-- 建表語句（首次部署時執行）
-- ============================================

CREATE TABLE IF NOT EXISTS short_urls (
    url_id        INT AUTO_INCREMENT PRIMARY KEY,
    original_url  TEXT NOT NULL,
    short_code    VARCHAR(10) NOT NULL,
    click_count   INT DEFAULT 0,
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at    DATETIME DEFAULT NULL,
    is_custom     TINYINT(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS access_logs (
    log_id        INT AUTO_INCREMENT PRIMARY KEY,
    url_id        INT NOT NULL,
    ip_address    VARCHAR(45) NOT NULL,
    referrer      VARCHAR(2048) DEFAULT NULL,
    user_agent    VARCHAR(512) DEFAULT NULL,
    access_time   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (url_id) REFERENCES short_urls(url_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reports (
    report_id     INT AUTO_INCREMENT PRIMARY KEY,
    url_id        INT NOT NULL,
    reason        TEXT,
    reporter_ip   VARCHAR(45) NOT NULL,
    report_time   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (url_id) REFERENCES short_urls(url_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rate_limits (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    ip_address    VARCHAR(45) NOT NULL,
    request_time  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
    setting_key   VARCHAR(100) PRIMARY KEY,
    setting_value TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 索引（效能優化，對現有資料庫可安全重複執行）
-- ============================================

-- #18 修復：rate_limits 表複合索引，加速頻率限制查詢
-- 查詢模式：WHERE ip_address = ? AND request_time > NOW() - INTERVAL 1 MINUTE
ALTER TABLE rate_limits ADD INDEX idx_rate_limits_ip_time (ip_address, request_time);

-- short_code 唯一索引（防止併發時短碼重複）
ALTER TABLE short_urls ADD UNIQUE INDEX idx_short_code (short_code);

-- access_logs 查詢優化
ALTER TABLE access_logs ADD INDEX idx_access_logs_url_id (url_id);
ALTER TABLE access_logs ADD INDEX idx_access_logs_access_time (access_time);

-- rate_limits 過期清理索引
ALTER TABLE rate_limits ADD INDEX idx_rate_limits_request_time (request_time);

-- 管理後台查詢優化
ALTER TABLE short_urls ADD INDEX idx_short_urls_is_custom (is_custom);
ALTER TABLE short_urls ADD INDEX idx_short_urls_expires_at (expires_at);
