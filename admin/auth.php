<?php
// /admin/auth.php

session_start();
require_once '../config.php';

// 處理登入
if (isset($_POST['login'])) {
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
                    $_SESSION['loggedin'] = true;
                    header('Location: index.php');
                    exit();
                }
            }
        }
    }
    // 驗證失敗
    header('Location: index.php?error=1');
    exit();
}

// 處理登出
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit();
}
