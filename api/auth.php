<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection: ตรวจสอบ Token
    if (!isset($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
       //echo "<script>alert('❌ Security Check Failed (CSRF Token mismatch)'); window.history.back();</script>";
        exit;

    }

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Security: Validate username format to prevent injection attacks like Path Traversal.
    // Allow only alphanumeric characters and underscores.
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        // Invalid username format, treat as a failed login attempt.
        echo "<script>
            alert('❌ ชื่อผู้ใช้งาน หรือ รหัสผ่าน ไม่ถูกต้อง'); 
            window.location.href='../login.php';
        </script>";
        exit;
    }

    try {
        if (isset($pdo)) {
            // 1. ดึงข้อมูล User จากฐานข้อมูล โดยอ้างอิง Username
            // Join กับตาราง roles เพื่อเอาชื่อตำแหน่งมาด้วย (เช่น Admin, User)
            $stmt = $pdo->prepare("
                SELECT u.*, r.role_name 
                FROM users u 
                LEFT JOIN roles r ON u.role_id = r.role_id 
                WHERE u.username = ?
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // 2. ตรวจสอบว่าพบ User หรือไม่ และรหัสผ่านถูกต้องไหม
            if ($user && password_verify($password, $user['password_hash'])) {
                
                // --- Login สำเร็จ ---
                
                // Security: ป้องกัน Session Fixation Attack โดยการเปลี่ยน Session ID ใหม่เมื่อ Login ผ่าน
                session_regenerate_id(true);
                
                // เก็บข้อมูลลง Session เพื่อนำไปใช้ในหน้าอื่นๆ
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['fullname'] = $user['fullname'];
                $_SESSION['role'] = $user['role_name'] ?? 'User'; // ถ้าไม่มี role ให้เป็น User
                $_SESSION['department'] = $user['department'];

                // ส่งไปหน้า Dashboard
                header("Location: ../index.php");
                exit;

            } else {
                // --- Login ไม่สำเร็จ ---
                echo "<script>
                    alert('❌ ชื่อผู้ใช้งาน หรือ รหัสผ่าน ไม่ถูกต้อง'); 
                    window.location.href='../login.php';
                </script>";
            }
        }
    } catch (PDOException $e) {
    // เก็บ Error ลงไฟล์ log ของ server แทนการ show
    error_log("Database Error: " . $e->getMessage());   
    
    // บอก user แค่นี้พอ
    echo "<script>
        alert('❌ เกิดข้อผิดพลาดในการเชื่อมต่อระบบ กรุณาลองใหม่ภายหลัง'); 
        window.location.href='../login.php';
    </script>";
    exit;
}
} else {
    // ถ้าไม่ใช่ POST request ให้Wกลับไปหน้า login
    header("Location: ../login.php");
    exit;
}
?>