<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
session_start();
require_once '../config/db.php'; 

// ใหม่: อนุญาตให้ User เข้ามาได้ แต่ต้องมี Logic ป้องกัน
$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'Administrator');
$current_user_id = $_SESSION['user_id'] ?? 0;

// รับค่า user_id ที่ส่งมาจากฟอร์ม
$post_user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

// Security Check: ถ้าไม่ใช่ Admin แต่พยายามแก้ ID ของคนอื่น หรือพยายามเพิ่ม ID ใหม่
if (!$is_admin && ($post_user_id !== $current_user_id)) {
     header('Location: ../user_form.php?status=error&msg=คุณไม่มีสิทธิ์แก้ไขข้อมูลผู้อื่น');
     exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 2. CSRF Protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Error: Invalid CSRF Token");
    }

    // รับค่าและแปลง Type
    $user_id = isset($_POST['user_id']) && $_POST['user_id'] !== '' ? (int)$_POST['user_id'] : null;
    
    // ฟังก์ชันทำความสะอาดข้อมูล
    function sanitizeInput($data) {
        $data = str_replace(chr(0), '', $data);
        $data = trim(strip_tags($data ?? ''));
        return $data;
    }

    $username   = sanitizeInput($_POST['username']);
    $fullname   = sanitizeInput($_POST['fullname']);
    $department = sanitizeInput($_POST['department']);
    $password   = $_POST['password'] ?? '';
    $role_id    = isset($_POST['role_id']) ? (int)$_POST['role_id'] : 0;

    // 3. ตรวจสอบ Path Traversal
    if (preg_match('/(\.\.|[\/\\\\])/', $fullname) || preg_match('/(\.\.|[\/\\\\])/', $department)) {
        error_log("Security Warning: Path Traversal attempt from " . $_SERVER['REMOTE_ADDR']);
        echo "<script>alert('❌ ข้อมูลไม่ถูกต้อง: ห้ามใช้อักขระพิเศษ'); window.history.back();</script>";
        exit;
    }

    if (empty($role_id)) {
        echo "<script>alert('❌ กรุณาเลือกสิทธิ์การใช้งาน (Role)'); window.history.back();</script>";
        exit;
    }

    // 4. ตรวจสอบรหัสผ่าน (แก้บั๊ก: บังคับตรวจถ้าเป็นการเพิ่มใหม่ หรือถ้ามีการกรอกมาตอนแก้ไข)
    if (!empty($password) || empty($user_id)) {
        // ถ้าเป็นเพิ่มใหม่ (Insert) ต้องไม่ว่าง
        if (empty($user_id) && empty($password)) {
             echo "<script>alert('❌ กรุณากำหนดรหัสผ่านสำหรับการสร้างบัญชีใหม่'); window.history.back();</script>";
             exit;
        }
        
        // ถ้ามีการกรอกรหัสผ่าน (ไม่ว่าจะเพิ่มหรือแก้) ต้องผ่านกฎ
        if (!empty($password)) {
            if (strlen($password) < 12) {
                echo "<script>alert('❌ รหัสผ่านต้องมีความยาวอย่างน้อย 12 ตัวอักษร'); window.history.back();</script>";
                exit;
            }
            if (!preg_match('/[a-zA-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
                echo "<script>alert('❌ รหัสผ่านต้องประกอบด้วยตัวอักษรภาษาอังกฤษและตัวเลขผสมกัน'); window.history.back();</script>";
                exit;
            }
        }
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
            
            header("Location: ../settings/?status=success&msg=" . urlencode('แก้ไขข้อมูลเรียบร้อย'));
            exit;

        } else {
            // --- กรณีเพิ่มใหม่ (Insert) ---
            
            // ตรวจสอบ Username ซ้ำ
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

            // บันทึกข้อมูล
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, fullname, department, role_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$username, $password_hash, $fullname, $department, $role_id]);

            header("Location: ../settings/?status=success&msg=" . urlencode('เพิ่มผู้ใช้งานเรียบร้อย'));
            exit;
        }

    } catch (PDOException $e) {
        error_log("Database Error in save_user.php: " . $e->getMessage());
        echo "<script>alert('❌ เกิดข้อผิดพลาดทางฐานข้อมูล'); window.history.back();</script>";
    }

} else {
    // ถ้าไม่ใช่ POST ให้ดีดกลับไปหน้า Settings
    header("Location: ../settings/");
    exit;
}
?>