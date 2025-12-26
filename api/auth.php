<?php
// 1. ตั้งค่า Session Security
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
session_start();

// 2. สร้างรหัสลับ (Nonce) และตั้งค่า Security Header
// กำหนดให้รับ script เฉพาะที่มี nonce และบล็อก object/style ที่ไม่ปลอดภัย
$nonce = base64_encode(random_bytes(16));
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-{$nonce}'; style-src 'self'; object-src 'none'; base-uri 'self';");

require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection: ตรวจสอบ Token
    if (!isset($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        // เพิ่ม nonce="..." ในแท็ก script
        echo "<script nonce=\"{$nonce}\">
            alert('❌ Security Check Failed (CSRF Token mismatch)'); 
            window.location.href='../login.php';
        </script>";
        exit;
    }

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Security: Validate username format
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        echo "<script nonce=\"{$nonce}\">
            alert('❌ ชื่อผู้ใช้งาน หรือ รหัสผ่าน ไม่ถูกต้อง'); 
            window.location.href='../login.php';
        </script>";
        exit;
    }

    try {
        if (isset($pdo)) {
            // 1. ดึงข้อมูล User
            $stmt = $pdo->prepare("
                SELECT u.*, r.role_name 
                FROM users u 
                LEFT JOIN roles r ON u.role_id = r.role_id 
                WHERE u.username = ?
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // 2. ตรวจสอบรหัสผ่าน
            if ($user && password_verify($password, $user['password_hash'])) {
                
                // --- Login สำเร็จ ---
                session_regenerate_id(true);
                
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['fullname'] = $user['fullname'];
                $_SESSION['role'] = $user['role_name'] ?? 'User';
                $_SESSION['department'] = $user['department'];

                header("Location: ../index.php");
                exit;

            } else {
                // --- Login ไม่สำเร็จ ---
                echo "<script nonce=\"{$nonce}\">
                    alert('❌ ชื่อผู้ใช้งาน หรือ รหัสผ่าน ไม่ถูกต้อง'); 
                    window.location.href='../login.php';
                </script>";
            }
        }
    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());   
        echo "<script nonce=\"{$nonce}\">
            alert('❌ เกิดข้อผิดพลาดในการเชื่อมต่อระบบ กรุณาลองใหม่ภายหลัง'); 
            window.location.href='../login.php';
        </script>";
        exit;
    }
} else {
    header("Location: ../login.php");
    exit;
}
?>