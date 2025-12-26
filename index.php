<?php
    // 1. สร้างรหัสลับ (Nonce)
    $nonce = base64_encode(random_bytes(16));

    // 2. ตั้งค่า Security Header
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-{$nonce}' https://static.line-scdn.net; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com; img-src 'self' data: https://api.qrserver.com https://*.line-scdn.net; connect-src 'self' https://*.line.me https://*.line-scdn.net; frame-ancestors 'self'; base-uri 'self'; form-action 'self'; object-src 'none';");
    
    header("X-Frame-Options: SAMEORIGIN");
    header("X-Content-Type-Options: nosniff");
    header("Referrer-Policy: strict-origin-when-cross-origin");

    ini_set('session.cookie_httponly', 1); 
    ini_set('session.cookie_secure', 1);   
    ini_set('session.use_only_cookies', 1); 
    session_start();
    ob_start();

    ini_set('display_errors', 0); 
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL); 

    // ตรวจสอบ Login
    $dev_mode = isset($_GET['dev']) ? $_GET['dev'] : '';
    
    if ( !isset( $_SESSION['user_id'] ) && $dev_mode !== 'liffscan' ) {
        header( "Location: login.php" );
        exit;
    }

    require realpath( '../dv-config.php' );
    require DEV_PATH . '/classes/db.class.v2.php';
    require DEV_PATH . '/functions/global.php';

    // สำหรับ dev parameter
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
    <title><?php echo Q_TITLE . Q_VERSION; ?> </title>

    <link rel="stylesheet" href="<?php echo ASSET_PATH; ?>/bootstrap/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo ASSET_PATH; ?>/@fortawesome/fontawesome-free/css/all.css">
    <link rel="stylesheet" href="<?php echo ASSET_PATH; ?>/sweetalert2/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="<?php echo ASSET_PATH; ?>/select2/dist/css/select2.css">
    <link rel="stylesheet" href="<?php echo ASSET_PATH; ?>/select2-bootstrap-5-theme/dist/select2-bootstrap-5-theme.min.css">
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
                // กำหนดตัวแปรสำหรับ JavaScript
                $jsReq  = '';
                $jsExt = '';
                $pageFile = '';

                switch ( $GET_DEV ) {

                    case '':
                    case 'main':
                        $pageFile = 'pages/main-menu.php';
                        break;

                    case 'dashboard':
                        // dashboard เราแก้ให้ใช้ JS ในไฟล์ตัวเองแล้ว ไม่ต้องโหลดเพิ่ม
                        $jsReq = ''; 
                        $pageFile = 'pages/dashboard-page.php';
                        break;

                    case 'register':
                        // [แก้ไขสำคัญ] ลบการโหลด jsReq และ jsExt ออก 
                        // เพราะเราย้ายไปเขียนใน register-page.php แล้ว เพื่อไม่ให้ตัวแปรชนกัน
                        $jsReq  = '';
                        $jsExt = ''; 
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

                require $pageFile;
            ?>

        </div>
    </div>

    <script nonce="<?php echo $nonce; ?>">
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
        <?php ( isset( $jsReq ) && !empty($jsReq) ) ? require $jsReq : ''; ?>
    </script>

</body>
</html>