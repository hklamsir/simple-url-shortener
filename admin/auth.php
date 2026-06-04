<?php
// /admin/auth.php

session_start();
require_once '../config.php';

// 安全修復：登入速率限制 — 5 次失敗後鎖定 15 分鐘
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_SECONDS', 900); // 15 分鐘

function is_login_locked_out() {
    if (!isset($_SESSION['login_attempts'])) return false;
    if ($_SESSION['login_attempts'] < MAX_LOGIN_ATTEMPTS) return false;
    if (!isset($_SESSION['login_lockout_until'])) return false;
    if (time() < $_SESSION['login_lockout_until']) return true;
    // 鎖定時間已過，重置計數
    $_SESSION['login_attempts'] = 0;
    unset($_SESSION['login_lockout_until']);
    return false;
}

function record_failed_login() {
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
    }
    $_SESSION['login_attempts']++;
    if ($_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
        $_SESSION['login_lockout_until'] = time() + LOGIN_LOCKOUT_SECONDS;
    }
}

function reset_login_attempts() {
    unset($_SESSION['login_attempts']);
    unset($_SESSION['login_lockout_until']);
}

// 處理登入
if (isset($_POST['login'])) {
    // 安全修復：驗證 CSRF Token
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        header('Location: index.php?error=1');
        exit();
    }

    // 檢查是否被鎖定
    if (is_login_locked_out()) {
        $remaining = $_SESSION['login_lockout_until'] - time();
        $minutes = ceil($remaining / 60);
        header('Location: index.php?error=locked&minutes=' . $minutes);
        exit();
    }

    if (isset($_POST['password'])) {
        $conn = get_db_connection();
        if ($conn) {
            $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = 'admin_password'");
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $hashed_password = $row['setting_value'];
                if (password_verify($_POST['password'], $hashed_password)) {
                    // 安全修復：登入成功後重新生成 session ID，防止會話固定攻擊
                    session_regenerate_id(true);
                    reset_login_attempts();
                    $_SESSION['loggedin'] = true;
                    header('Location: index.php');
                    exit();
                }
            }
            $stmt->close();
            $conn->close();
        }
    }
    // 驗證失敗 — 記錄失敗次數
    record_failed_login();
    $attempts_left = MAX_LOGIN_ATTEMPTS - $_SESSION['login_attempts'];
    if ($attempts_left <= 0) {
        header('Location: index.php?error=locked&minutes=' . ceil(LOGIN_LOCKOUT_SECONDS / 60));
    } else {
        header('Location: index.php?error=1&attempts_left=' . $attempts_left);
    }
    exit();
}

// 處理登出
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit();
}
