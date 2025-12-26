<?php
// 1. ตั้งค่า Session Security
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
session_start();

// 2. สร้างรหัสลับ (Nonce) และตั้งค่า Security Header
$nonce = base64_encode(random_bytes(16));
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-{$nonce}'; style-src 'self'; object-src 'none'; base-uri 'self';");

require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo "<script nonce=\"{$nonce}\">alert('❌ Security Check Failed (CSRF Token mismatch)'); window.history.back();</script>";
        exit;
    }

    // Sanitization
    $fullname = trim(strip_tags($_POST['fullname'] ?? ''));
    $department = trim(strip_tags($_POST['department'] ?? ''));
    $username = trim(strip_tags($_POST['username'] ?? ''));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Security: Path Traversal
    if (strpos($fullname, '..') !== false || strpos($department, '..') !== false) {
        echo "<script nonce=\"{$nonce}\">alert('❌ ข้อมูลไม่ถูกต้อง (ห้ามมี ..)'); window.history.back();</script>";
        exit;
    }

    // Validation: Username
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        echo "<script nonce=\"{$nonce}\">alert('❌ Username ต้องประกอบด้วยตัวอักษรภาษาอังกฤษ ตัวเลข หรือ _ เท่านั้น'); window.history.back();</script>";
        exit;
    }

    // 1. ตรวจสอบรหัสผ่าน
    if ($password !== $confirm_password) {
        echo "<script nonce=\"{$nonce}\">alert('❌ รหัสผ่านไม่ตรงกัน'); window.history.back();</script>";
        exit;
    }

    try {
        if (isset($pdo)) {
            // 2. ตรวจสอบ Username ซ้ำ
            $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $check->execute([$username]);
            if ($check->fetchColumn() > 0) {
                echo "<script nonce=\"{$nonce}\">alert('❌ Username นี้มีผู้ใช้งานแล้ว'); window.history.back();</script>";
                exit;
            }

            // 3. หา role_id
            $stmtRole = $pdo->prepare("SELECT role_id FROM roles WHERE role_name LIKE '%User%' LIMIT 1");
            $stmtRole->execute();
            $role = $stmtRole->fetch(PDO::FETCH_ASSOC);
            $default_role_id = $role ? $role['role_id'] : 2; 

            // 4. บันทึกข้อมูล
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO users (username, password_hash, fullname, department, role_id) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$username, $password_hash, $fullname, $department, $default_role_id]);

            // สมัครเสร็จแล้ว
            echo "<script nonce=\"{$nonce}\">
                alert('✅ สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ'); 
                window.location.href = '../login.php';
            </script>";
        }
    } catch (PDOException $e) {
        error_log("Register Error: " . $e->getMessage());
        echo "<script nonce=\"{$nonce}\">
            alert('❌ เกิดข้อผิดพลาดในการบันทึกข้อมูล กรุณาลองใหม่ภายหลัง'); 
            window.history.back();
        </script>";
    }
} else {
    header("Location: ../login.php");
}
?>