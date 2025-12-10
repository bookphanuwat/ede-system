<?php
session_start();
error_reporting(E_ERROR | E_WARNING | E_PARSE);

// ตรวจสอบการ login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'config/db.php';

// สำหรับ page parameter (อนุญาตเฉพาะ alphanumeric, dash, underscore)
$page = isset($_GET['page']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['page']) : '';

define('EDE_VERSION', '1.0.0');
define('EDE_TITLE', 'EDE System - ระบบจัดการเอกสาร');
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="ATH Development Team">
    <meta name="keywords" content="document,management,system,ede,tracking">
    <meta name="description" content="ระบบจัดการเอกสารอิเล็กทรอนิกส์ โรงพยาบาลอ่างทอง">
    <title><?php echo EDE_TITLE; ?></title>

    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>

<body>

    <?php
    // กำหนดตัวแปรสำหรับ JavaScript ที่จะโหลด
    $jsReq = '';
    $jsVars = ''; // ตัวแปร JavaScript ที่ต้องการส่งจาก PHP

    switch ($page) {

        case '':
        case 'main':
            // หน้าเมนูหลัก
            require 'pages/main-menu.php';
            break;

        case 'dashboard':
            // หน้า Dashboard
            require 'pages/dashboard-page.php';
            $jsReq = '_scripts/dashboard.js';
            break;

        case 'register':
            // หน้าลงทะเบียนเอกสาร
            require 'pages/register-page.php';
            $jsReq = '_scripts/register.js';
            $jsVars = "const CURRENT_USER_ID = '" . ($_SESSION['user_id'] ?? '') . "';";
            break;

        case 'tracking':
            // หน้าติดตามเอกสาร
            require 'pages/tracking-page.php';
            break;

        case 'report':
            // หน้ารายงาน
            require 'pages/report-page.php';
            break;

        case 'settings':
            // หน้าตั้งค่า
            require 'pages/settings-page.php';
            break;

        case 'scan-history':
            // หน้าประวัติการสแกน
            require 'pages/scan-history-page.php';
            break;

        case 'workflow-settings':
            // หน้าจัดการสถานะ
            require 'pages/workflow-settings-page.php';
            break;

        default:
            // หน้า 404
            require 'pages/page-not-found.php';
            break;
    }
    ?>

    <!-- Core JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>

    <!-- Global Scripts -->
    <script src="_scripts/global.js"></script>

    <!-- Page Specific Variables & Scripts -->
    <?php if (!empty($jsVars)): ?>
    <script>
        <?php echo $jsVars; ?>
    </script>
    <?php endif; ?>

    <?php if (!empty($jsReq) && file_exists($jsReq)): ?>
    <script src="<?php echo $jsReq; ?>?v=<?php echo filemtime($jsReq); ?>"></script>
    <?php endif; ?>

</body>
</html>
