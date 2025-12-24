<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
session_start();
require_once '../config/db.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Error: Invalid CSRF Token");
    }

    // ✅ FIX 1: Type Casting (บังคับเป็นตัวเลข) ป้องกัน SQL Injection ทุกรูปแบบในตัวแปรนี้
    // ถ้าส่ง script หรือ sql มา จะถูกเปลี่ยนเป็น 0 ทันที
    $user_id = isset($_POST['user_id']) && $_POST['user_id'] !== '' ? (int)$_POST['user_id'] : null;
    
    // Sanitization
    $username = trim(strip_tags($_POST['username'] ?? ''));
    $fullname = trim(strip_tags($_POST['fullname'] ?? ''));
    $department = trim(strip_tags($_POST['department'] ?? ''));

    // Security: Path Traversal
    if (strpos($fullname, '..') !== false || strpos($department, '..') !== false) {
        echo "<script>alert('❌ ข้อมูลไม่ถูกต้อง'); window.history.back();</script>";
        exit;
    }

    $password = $_POST['password'] ?? '';
    
    // ✅ FIX 2: Type Casting role_id ต้องเป็นตัวเลขเท่านั้น
    $role_id = isset($_POST['role_id']) ? (int)$_POST['role_id'] : 0;

    if (empty($role_id)) {
        echo "<script>alert('❌ กรุณาเลือกสิทธิ์การใช้งาน (Role)'); window.history.back();</script>";
        exit;
    }

    try {
        if ($user_id) {
            // --- กรณีแก้ไข (Update) ---
            if (!empty($password)) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET fullname=?, department=?, role_id=?, password_hash=? WHERE user_id=?");
                $stmt->execute([$fullname, $department, $role_id, $password_hash, $user_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET fullname=?, department=?, role_id=? WHERE user_id=?");
                $stmt->execute([$fullname, $department, $role_id, $user_id]);
            }
            
            echo "<script>alert('✅ แก้ไขข้อมูลเรียบร้อย'); window.location.href='../settings/';</script>";

        } else {
            // --- กรณีเพิ่มใหม่ (Insert) ---
            
            // Validation Username
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
                echo "<script>alert('❌ Username ไม่ถูกต้อง (A-Z, 0-9, _)'); window.history.back();</script>";
                exit;
            }

            $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $check->execute([$username]);
            if ($check->fetchColumn() > 0) {
                echo "<script>alert('❌ Username นี้มีอยู่ในระบบแล้ว'); window.history.back();</script>";
                exit;
            }

            if (empty($password)) {
                echo "<script>alert('❌ กรุณากำหนดรหัสผ่าน'); window.history.back();</script>";
                exit;
            }

            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, fullname, department, role_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$username, $password_hash, $fullname, $department, $role_id]);

            echo "<script>alert('✅ เพิ่มผู้ใช้งานเรียบร้อย'); window.location.href='../settings/';</script>";
        }

    } catch (PDOException $e) {
        // ✅ FIX 3: ไม่แสดง Error จริงหน้าเว็บ (ป้องกัน Information Disclosure)
        error_log("Database Error in save_user.php: " . $e->getMessage());
        echo "<script>alert('❌ เกิดข้อผิดพลาดทางฐานข้อมูล กรุณาติดต่อผู้ดูแลระบบ'); window.history.back();</script>";
    }
} else {
    header("Location: ../settings/");
    exit;
}
?>