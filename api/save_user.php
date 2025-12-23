<?php
session_start();
require_once '../config/db.php'; // ตรวจสอบ path ให้ถูก ต้องถอยกลับ 1 ขั้น

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection: ตรวจสอบ Token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Error: Invalid CSRF Token (Security Check Failed)");
    }

    // รับค่าจากฟอร์ม
    $user_id = $_POST['user_id'] ?? null;
    
    // Sanitization: ป้องกัน XSS โดยการลบ HTML Tags และตัดช่องว่าง
    $username = trim(strip_tags($_POST['username'] ?? ''));
    $fullname = trim(strip_tags($_POST['fullname'] ?? ''));
    $department = trim(strip_tags($_POST['department'] ?? ''));

    // Security: ป้องกัน Path Traversal (ห้ามมี .. ในข้อมูล)
    if (strpos($fullname, '..') !== false || strpos($department, '..') !== false) {
        echo "<script>alert('❌ ข้อมูลไม่ถูกต้อง (ห้ามมี ..)'); window.history.back();</script>";
        exit;
    }

    $password = $_POST['password'] ?? '';
    $role_id = $_POST['role_id'];

    try {
        if ($user_id) {
            // --- กรณีแก้ไข (Update) ---
            
            // ถ้ามีการกรอก Password ใหม่ ให้ Update Password ด้วย
            if (!empty($password)) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET fullname=?, department=?, role_id=?, password_hash=? WHERE user_id=?");
                $stmt->execute([$fullname, $department, $role_id, $password_hash, $user_id]);
            } else {
                // ถ้าไม่แก้ Password (เว้นว่างไว้) ก็ไม่ต้องอัปเดตฟิลด์นี้
                $stmt = $pdo->prepare("UPDATE users SET fullname=?, department=?, role_id=? WHERE user_id=?");
                $stmt->execute([$fullname, $department, $role_id, $user_id]);
            }
            
            echo "<script>alert('✅ แก้ไขข้อมูลเรียบร้อย'); window.location.href='../settings/';</script>";

        } else {
            // --- กรณีเพิ่มใหม่ (Insert) ---
            
            // Validation: ตรวจสอบ Username (อนุญาตเฉพาะ A-Z, a-z, 0-9 และ _)
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
                echo "<script>alert('❌ Username ต้องประกอบด้วยตัวอักษรภาษาอังกฤษ ตัวเลข หรือ _ เท่านั้น'); window.history.back();</script>";
                exit;
            }

            // 1. เช็คก่อนว่า username ซ้ำไหม?
            $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $check->execute([$username]);
            if ($check->fetchColumn() > 0) {
                echo "<script>alert('❌ Username นี้มีอยู่ในระบบแล้ว กรุณาเปลี่ยนใหม่'); window.history.back();</script>";
                exit;
            }

            // 2. ต้องมีรหัสผ่านเสมอสำหรับการสร้างใหม่
            if (empty($password)) {
                echo "<script>alert('❌ กรุณากำหนดรหัสผ่าน'); window.history.back();</script>";
                exit;
            }

            // 3. บันทึกลงฐานข้อมูล
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, fullname, department, role_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$username, $password_hash, $fullname, $department, $role_id]);

            echo "<script>alert('✅ เพิ่มผู้ใช้งานเรียบร้อย'); window.location.href='../settings/';</script>";
        }

    } catch (PDOException $e) {
        // แสดง Error ชัดเจนเพื่อการ Debug
        echo "<h3>เกิดข้อผิดพลาดทางฐานข้อมูล:</h3>";
        echo "Error: " . $e->getMessage();
        echo "<br><br><a href='../settings/'>กลับไปหน้าตั้งค่า</a>";
    }
} else {
    // ถ้าเข้าไฟล์นี้ตรงๆ โดยไม่ได้ Submit Form
    header("Location: ../settings/");
    exit;
}
?>