<?php
// 1. เช็ค Session และตั้งค่าพื้นฐาน
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    session_start();
    // หา Root Path อัตโนมัติสำหรับ Action Form
    $rootPath = dirname(dirname($_SERVER['PHP_SELF']));
}

// 2. โหลด Config และ Database
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../dv-config.php'; 
}

if (defined('DEV_PATH')) {
    $class_path = realpath(__DIR__ . '/../../classes/db.class.v2.php');
    $func_path  = realpath(__DIR__ . '/../../functions/global.php');

    if ($class_path && file_exists($class_path)) {
        require_once $class_path;
    }
    if ($func_path && file_exists($func_path)) {
        require_once $func_path;
    }
}

// 3. ตรวจสอบสิทธิ์
$is_admin = (isset($_SESSION['role']) && stripos($_SESSION['role'], 'admin') !== false);
$current_user_id = $_SESSION['user_id'] ?? 0;

$user_data = null;
$is_edit = false;
$roles = [];
$target_id = null;

// กำหนด ID ที่จะจัดการ
if ($is_admin) {
    // ถ้าเป็น Admin ให้รับ ID จาก URL (ถ้าไม่มีคือเพิ่มใหม่)
    $target_id = $_GET['id'] ?? null;
} else {
    // ✅ ถ้าเป็น User ทั่วไป บังคับให้แก้แค่ตัวเองเท่านั้น
    $target_id = $current_user_id; 
}

// ดึงข้อมูล Roles (สำหรับ Admin เลือกสิทธิ์)
if ($is_admin && class_exists('CON')) {
    $roles = CON::selectArrayDB([], "SELECT * FROM roles ORDER BY role_id ASC") ?? [];
}

// ดึงข้อมูลผู้ใช้กรณีแก้ไข
if ($target_id && class_exists('CON')) {
    // ดึงข้อมูลตาม Target ID
    $user_result = CON::selectArrayDB([$target_id], "SELECT u.*, r.role_name FROM users u LEFT JOIN roles r ON u.role_id = r.role_id WHERE u.user_id = ?");
    if (!empty($user_result)) {
        $user_data = $user_result[0];
        $is_edit = true;
    }
}

// ตั้งหัวข้อหน้าเว็บ
$page_header = $is_edit ? "แก้ไขข้อมูลผู้ใช้งาน" : "เพิ่มผู้ใช้งานใหม่";
if (!$is_admin && $is_edit) {
    $page_header = "แก้ไขข้อมูลส่วนตัว"; // เปลี่ยนชื่อให้ดูดีสำหรับ User ทั่วไป
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_header; ?></title>
    
    <link rel="stylesheet" href="<?php echo ASSET_PATH; ?>/bootstrap/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo ASSET_PATH; ?>/@fortawesome/fontawesome-free/css/all.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f4f7f6; }
        .main-card { border: none; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .custom-input { border-radius: 10px; padding: 12px; border: 1px solid #e0e0e0; background-color: #fdfdfd; }
        .custom-input:focus { box-shadow: 0 0 0 0.25 row rgba(0, 230, 118, 0.2); border-color: #00E676; }
        .header-section { background: #6f42c1; color: white; padding: 20px; border-radius: 15px 15px 0 0; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="card main-card mx-auto" style="max-width: 850px;">
        <div class="header-section shadow-sm">
            <h4 class="mb-0 fw-bold"><i class="fas fa-user-circle me-2"></i> <?php echo $page_header; ?></h4>
        </div>
        
        <div class="card-body p-4 p-md-5">
            
            <?php if (isset($_GET['status'])): ?>
                <div class="alert alert-<?php echo $_GET['status'] == 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show mb-4">
                    <i class="fas <?php echo $_GET['status'] == 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> me-2"></i>
                    <?php echo htmlspecialchars($_GET['msg']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form action="<?php echo $rootPath; ?>/api/save_user.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <?php if ($is_edit): ?>
                    <input type="hidden" name="user_id" value="<?php echo $user_data['user_id']; ?>">
                <?php endif; ?>

                <div class="row mb-4">
                    <label class="col-md-3 col-form-label fw-bold text-secondary text-md-end">Username</label>
                    <div class="col-md-9">
                        <input type="text" name="username" class="form-control custom-input"
                               value="<?php echo $user_data ? htmlspecialchars($user_data['username']) : ''; ?>"
                               <?php echo $is_edit ? 'readonly style="background-color: #f8f9fa;"' : 'required'; ?>>
                        <?php if($is_edit): ?><small class="text-muted">*Username แก้ไขไม่ได้</small><?php endif; ?>
                    </div>
                </div>

                <div class="row mb-4">
                    <label class="col-md-3 col-form-label fw-bold text-secondary text-md-end">Password</label>
                    <div class="col-md-9">
                        <input type="password" name="password" class="form-control custom-input" 
                               placeholder="<?php echo $is_edit ? 'กรอกเฉพาะเมื่อต้องการเปลี่ยนใหม่' : 'ระบุรหัสผ่าน (12 ตัวขึ้นไป)'; ?>"
                               minlength="12" <?php echo $is_edit ? '' : 'required'; ?>>
                        <?php if($is_edit): ?>
                            <small class="text-muted">ระบุรหัสผ่าน (12 ตัวขึ้นไป โดยมีตัวอักษรอยู่ด้วย)</small>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row mb-4">
                    <label class="col-md-3 col-form-label fw-bold text-secondary text-md-end">ชื่อ-สกุล</label>
                    <div class="col-md-9">
                        <input type="text" name="fullname" class="form-control custom-input"
                               value="<?php echo $user_data ? htmlspecialchars($user_data['fullname']) : ''; ?>" required>
                    </div>
                </div>

                <div class="row mb-4">
                    <label class="col-md-3 col-form-label fw-bold text-secondary text-md-end">แผนก</label>
                    <div class="col-md-9">
                        <input type="text" name="department" class="form-control custom-input"
                               value="<?php echo $user_data ? htmlspecialchars($user_data['department'] ?? '') : ''; ?>">
                    </div>
                </div>

                <div class="row mb-4">
                    <label class="col-md-3 col-form-label fw-bold text-secondary text-md-end">สิทธิ์การใช้งาน</label>
                    <div class="col-md-9">
                        <?php if ($is_admin): ?>
                            <select name="role_id" class="form-select custom-input">
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo $role['role_id']; ?>" <?php echo ($user_data && $user_data['role_id'] == $role['role_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($role['role_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <input type="text" class="form-control-plaintext px-3 border rounded bg-light" 
                                   value="<?php echo htmlspecialchars($user_data['role_name'] ?? 'User'); ?>" readonly>
                            
                            <input type="hidden" name="role_id" value="<?php echo $user_data['role_id'] ?? ''; ?>">
                        <?php endif; ?>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-5 pt-3 border-top">
                    <a href="javascript:history.back()" class="btn btn-light rounded-pill px-4 me-2 border">ยกเลิก</a>
                    <button type="submit" class="btn btn-success rounded-pill px-5 shadow-sm" style="background-color: #00E676; border:none; color: #000; font-weight:600;">
                        <i class="fas fa-save me-2"></i>บันทึกข้อมูล
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?php echo ASSET_PATH; ?>/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>