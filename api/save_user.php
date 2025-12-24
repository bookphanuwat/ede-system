<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
session_start();
require_once '../config/db.php'; 

// ✅ FIX 2: เพิ่มการตรวจสอบสิทธิ์ Admin ก่อนเริ่มทำงาน
if (empty($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'Administrator') {
    die("❌ Access Denied: คุณไม่มีสิทธิ์จัดการข้อมูลผู้ใช้งาน");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Error: Invalid CSRF Token");
    }

    // ✅ FIX 1: Type Casting (บังคับเป็นตัวเลข)
    $user_id = isset($_POST['user_id']) && $_POST['user_id'] !== '' ? (int)$_POST['user_id'] : null;
    
    // Helper Function: ทำความสะอาดข้อมูลและป้องกัน Path Traversal
    function sanitizeInput($data) {
        // 1. ลบ Null Bytes (%00) ที่อาจใช้หลบเลี่ยงการตรวจสอบนามสกุลไฟล์
        $data = str_replace(chr(0), '', $data);
        // 2. ลบ HTML Tags และช่องว่างหัวท้าย
        $data = trim(strip_tags($data ?? ''));
        return $data;
    }

    // รับค่าและ Sanitize
    $username   = sanitizeInput($_POST['username']);
    $fullname   = sanitizeInput($_POST['fullname']);
    $department = sanitizeInput($_POST['department']);

    // ✅ FIX 2: Security check for Path Traversal (High Severity Fix)
    // ตรวจสอบว่ามีอักขระอันตรายที่ใช้ระบุ Path หรือไม่ (.. หรือ / หรือ \)
    // การใช้ preg_match จะครอบคลุมกว่า strpos และป้องกันการอ้างอิง directory
    if (preg_match('/(\.\.|[\/\\\\])/', $fullname) || preg_match('/(\.\.|[\/\\\\])/', $department)) {
        // บันทึก Log เพื่อติดตามผู้พยายามโจมตี (Optional)
        error_log("Security Warning: Path Traversal attempt detected from IP " . $_SERVER['REMOTE_ADDR']);
        
        echo "<script>
            alert('❌ ข้อมูลไม่ถูกต้อง: ห้ามใช้อักขระพิเศษที่เกี่ยวข้องกับ Path (เช่น / หรือ \\ )'); 
            window.history.back();
        </script>";
        exit;
    }

    $password = $_POST['password'] ?? '';
    
    // ✅ FIX 3: Type Casting role_id
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
            
            // Validation Username (Allow List: A-Z, 0-9, _)
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
        // ไม่แสดง Error จริงหน้าเว็บ
        error_log("Database Error in save_user.php: " . $e->getMessage());
        echo "<script>alert('❌ เกิดข้อผิดพลาดทางฐานข้อมูล กรุณาติดต่อผู้ดูแลระบบ'); window.history.back();</script>";
    }
} else {
    header("Location: ../settings/");
    exit;
}
?>