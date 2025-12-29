<?php
    // 1. สร้างรหัสลับ (Nonce) สำหรับการโหลดหน้านี้โดยเฉพาะ
    $nonce = base64_encode(random_bytes(16));

    // 2. ตั้งค่า Security Header ผ่าน PHP (ใช้ Nonce แทน unsafe-inline)
    // สังเกต: เราลบ 'unsafe-inline' และ 'unsafe-eval' ออกแล้ว
    // สังเกต: เราลบ https://cdn.jsdelivr.net ออก แล้วใช้ 'self' สำหรับไฟล์ Local
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-{$nonce}' https://static.line-scdn.net; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com; img-src 'self' data: https://api.qrserver.com https://*.line-scdn.net; connect-src 'self' https://*.line.me https://*.line-scdn.net; frame-ancestors 'self'; base-uri 'self'; form-action 'self'; object-src 'none';");    
    // Header ความปลอดภัยอื่นๆ
    header("X-Frame-Options: SAMEORIGIN");
    header("X-Content-Type-Options: nosniff");
    header("Referrer-Policy: strict-origin-when-cross-origin");

    // การตั้งค่า Session เดิม
    ini_set('session.cookie_httponly', 1); 
    ini_set('session.cookie_secure', 1);   
    ini_set('session.use_only_cookies', 1); 
    session_start();
    ob_start();


    
    ini_set('display_errors', 0); 
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL); 

    // ------------------------------------------------------------------------
    // [แก้ไข] ส่วนตรวจสอบการ Login (เปิดใช้งานและปรับปรุงโค้ด)
    // ------------------------------------------------------------------------
    // ตรวจสอบว่ามี user_id ใน Session หรือไม่ 
    // และยกเว้นการตรวจสอบถ้าเข้ามาผ่านโหมด 'liffscan' (ถ้าจำเป็นต้องเปิดสาธารณะ)
    $dev_mode = isset($_GET['dev']) ? $_GET['dev'] : '';
    
    if ( !isset( $_SESSION['user_id'] ) && $dev_mode !== 'liffscan' ) {
        header( "Location: login.php" );
        exit;
    }
    // ------------------------------------------------------------------------

    require realpath( '../dv-config.php' );
    require DEV_PATH . '/classes/db.class.v2.php';
    require DEV_PATH . '/functions/global.php';

    // ... ส่วนที่เหลือคงเดิม ...

    // สำหรับ dev parameter (อนุญาตเฉพาะ alphanumeric)
    $GET_DEV = sanitizeGetParam( 'dev', 'alphanumeric', '', 50 );

    define( 'Q_VERSION', '1.0.0' );
    define( 'Q_TITLE', 'EDE System - ระบบจัดการเอกสาร' );
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
    <title><?php echo Q_TITLE . Q_VERSION; ?> </title>

    <!-- CSS -->
    <link rel="stylesheet" href="<?php echo ASSET_PATH; ?>/bootstrap/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo ASSET_PATH; ?>/@fortawesome/fontawesome-free/css/all.css">
    <link rel="stylesheet" href="<?php echo ASSET_PATH; ?>/sweetalert2/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="<?php echo ASSET_PATH; ?>/select2/dist/css/select2.css">
    <link rel="stylesheet" href="<?php echo ASSET_PATH; ?>/select2-bootstrap-5-theme/dist/select2-bootstrap-5-theme.min.css">
    <!-- <link rel="stylesheet" href="<?php echo ASSET_PATH; ?>/fonts/maledpan/maledpan.css">
    <link rel="stylesheet" href="<?php echo ASSET_PATH; ?>/fonts/chatthai/chatthai.css"> -->
    <link href="<?php echo SITE_URL;?>/css/main.min.css" rel="stylesheet">
</head>

<body>

    <div class="d-flex">
        <?php
        if ( $GET_DEV !== 'liffscan' ) {
            include 'includes/sidebar.php';
        }
        ?>


        <div class="<?php echo ( $GET_DEV === 'liffscan' ) ? 'container-fluid' : 'content-wrapper'; ?>">

            <?php
                // กำหนดตัวแปรสำหรับ JavaScript ที่จะโหลด และโหลด page content
                $jsReq  = '';
                $jsExt = '';
                $pageFile = '';

                switch ( $GET_DEV ) {

                    case '':
                    case 'main':
                        $jsReq = '';
                        $pageFile = 'pages/main-menu.php';
                        break;

                    case 'dashboard':
                        $jsReq = 'js/dashboard.min.js';
                        $pageFile = 'pages/dashboard-page.php';
                        break;

                    case 'register':
                        $jsReq  = 'js/register.min.js';
                        $jsExt = "const CURRENT_USER_ID = '" . ( $_SESSION['user_id'] ?? '' ) . "';";
                        $pageFile = 'pages/register-page.php';
                        break;

                    case 'tracking':
                        $pageFile = 'pages/tracking-page.php';
                        break;

                    case 'report':
                        $pageFile = 'pages/report-page.php';
                        break;

                    case 'settings':
                        $pageFile = 'pages/settings-page.php';
                        break;

                    case 'scan-history':
                        $pageFile = 'pages/scan-history-page.php';
                        break;

                    case 'workflow-settings':
                        $pageFile = 'pages/workflow-settings-page.php';
                        break;

                    case 'liffscan':
                        $pageFile = 'pages/liff-scan.php';
                        $jsReq = 'js/liffscan.min.js';
                        break;

                    default:
                        $pageFile = 'pages/page-not-found.php';
                        break;
                }

                // โหลด page content (แต่ละ page จะตั้งค่า $page_title และ $header_class เอง)
                require $pageFile;

            ?>

        </div><!-- .content-wrapper -->
    </div><!-- .d-flex -->

    <!-- Core JavaScript -->
    <<script nonce="<?php echo $nonce; ?>">
        const site_url = '<?php echo SITE_URL; ?>';
    </script>

    <script src="<?php echo ASSET_PATH; ?>/bootstrap/dist/js/bootstrap.bundle.min.js" nonce="<?php echo $nonce; ?>"></script>
    <script src="<?php echo ASSET_PATH; ?>/sweetalert2/dist/sweetalert2.min.js" nonce="<?php echo $nonce; ?>"></script>
    
    <script src="<?php echo SITE_URL; ?>/js/qrcode.min.js" nonce="<?php echo $nonce; ?>"></script>
    <script src="https://static.line-scdn.net/liff/edge/versions/2.22.3/sdk.js" nonce="<?php echo $nonce; ?>"></script>
    <script src="<?php echo SITE_URL; ?>/js/Sortable.min.js" nonce="<?php echo $nonce; ?>"></script>

   <script src="<?php echo SITE_URL; ?>/js/global.min.js?v=<?php echo filemtime( 'js/global.min.js' ); ?>" nonce="<?php echo $nonce; ?>"></script>

    <script async nonce="<?php echo $nonce; ?>">
        'use strict';
        <?php echo( isset( $jsExt ) ) ? $jsExt : ''; ?>
        <?php ( isset( $jsReq ) ) ? require $jsReq : ''; ?>
    </script>

</body>
</html>
