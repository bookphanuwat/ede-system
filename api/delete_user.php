<?php
// 1. ตั้งค่า Session และ Security
ini_set('display_errors', 0);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
session_start();

// 2. ตรวจสอบสิทธิ์ (Security Check)
if (empty($_SESSION['user_id']) || empty($_SESSION['role']) || stripos($_SESSION['role'], 'admin') === false) {
    header("Location: ../login.php");
    exit;
}

// ✅ 3. ตรวจสอบ CSRF Token ก่อนเริ่มกระบวนการใดๆ
// ตรวจสอบว่ามีค่าส่งมาใน $_GET หรือไม่ และต้องตรงกับค่าใน $_SESSION
if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    header("Location: ../settings/?status=error&msg=" . urlencode("Security Check Failed: Invalid or Missing Token"));
    exit;
}

// 4. เชื่อมต่อฐานข้อมูล
require_once '../config/db.php'; 

$configPath = realpath(__DIR__ . '/../../dv-config.php');
if (file_exists($configPath)) {
    require_once $configPath;
    if (defined('DEV_PATH')) {
        require_once DEV_PATH . '/classes/db.class.v2.php';
        require_once DEV_PATH . '/functions/global.php';
    }
}

// 5. รับค่า ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: ../settings/");
    exit;
}

try {
    // --- ส่วนตรรกะการตรวจสอบ (Logic Checks) ---

    // 5.1 ห้ามลบตัวเอง
    if ($id == $_SESSION['user_id']) {
        throw new Exception("ไม่สามารถลบบัญชีที่กำลังใช้งานอยู่ได้");
    }

    // 5.2 ป้องกันการลบ Admin คนสุดท้าย
    $sqlRole = "SELECT r.role_name FROM users u LEFT JOIN roles r ON u.role_id = r.role_id WHERE u.user_id = ?";
    $resultUser = CON::selectArrayDB([$id], $sqlRole); 
    $targetUser = $resultUser[0] ?? null;

    if ($targetUser && stripos($targetUser['role_name'], 'admin') !== false) {
        $sqlCount = "SELECT COUNT(*) as c FROM users u LEFT JOIN roles r ON u.role_id = r.role_id WHERE r.role_name LIKE '%Admin%'";
        $resultCount = CON::selectArrayDB([], $sqlCount);
        $adminCount = $resultCount[0]['c'] ?? 0;

        if ($adminCount <= 1) {
            throw new Exception("ไม่สามารถลบได้ เนื่องจากเป็นผู้ดูแลระบบ (Admin) คนสุดท้ายของระบบ");
        }
    }

    // --- ส่วนเปลี่ยนแปลงข้อมูล (Write Operations) ---
    $pdo->beginTransaction();

    // 6. ปลดชื่อออกจากประวัติ (Set NULL)
    $stmt = $pdo->prepare("UPDATE document_status_log SET action_by = NULL WHERE action_by = ?");
    $stmt->execute([$id]);

    $stmt = $pdo->prepare("UPDATE documents SET created_by = NULL WHERE created_by = ?");
    $stmt->execute([$id]);

    // 7. ลบ User
    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->execute([$id]);

    $pdo->commit();
    header("Location: ../settings/?status=success&msg=" . urlencode('ลบผู้ใช้งานเรียบร้อยแล้ว'));
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $errorMsg = $e->getMessage();
    if ($e instanceof PDOException && $e->getCode() == '23000') {
        $errorMsg = 'ไม่สามารถลบได้ เนื่องจากข้อมูลนี้ถูกใช้งานอยู่ในระบบ (Foreign Key Constraint)';
    }

    header("Location: ../settings/?status=error&msg=" . urlencode($errorMsg));
    exit;
}
?>