<?php 
// CSRF Protection: สร้าง Token ถ้ายังไม่มี
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ตัวแปรสำหรับแจ้งเตือน (Bootstrap Alert)
$alert_message = '';
$alert_type = '';

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
                $sql = "UPDATE users SET fullname=?, department=?, role_id=?, password_hash=? WHERE user_id=?";
                CON::updateDB([$fullname, $department, $role_id, $password_hash, $user_id], $sql);
            } else {
                $sql = "UPDATE users SET fullname=?, department=?, role_id=? WHERE user_id=?";
                CON::updateDB([$fullname, $department, $role_id, $user_id], $sql);
            }
            
            header("Location: settings/?status=success&msg=" . urlencode('แก้ไขข้อมูลเรียบร้อย'));
            exit;
        } else {
            // --- กรณีเพิ่มใหม่ (Insert) ---
            
            // Validation Username (Allow List: A-Z, 0-9, _)
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
                echo "<script>alert('❌ Username ไม่ถูกต้อง (A-Z, 0-9, _)'); window.history.back();</script>";
                exit;
            }

            $sqlCheck = "SELECT COUNT(*) as c FROM users WHERE username = ?";
            $resCheck = CON::selectArrayDB([$username], $sqlCheck);
            if ($resCheck && $resCheck[0]['c'] > 0) {
                echo "<script>alert('❌ Username นี้มีอยู่ในระบบแล้ว'); window.history.back();</script>";
                exit;
            }

            if (empty($password)) {
                echo "<script>alert('❌ กรุณากำหนดรหัสผ่าน'); window.history.back();</script>";
                exit;
            }

            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username, password_hash, fullname, department, role_id) VALUES (?, ?, ?, ?, ?)";
            CON::updateDB([$username, $password_hash, $fullname, $department, $role_id], $sql);

            header("Location: settings/?status=success&msg=" . urlencode('เพิ่มผู้ใช้งานเรียบร้อย'));
            exit;
        }

    } catch (Exception $e) {
        // ไม่แสดง Error จริงหน้าเว็บ
        error_log("Database Error in user-page.php: " . $e->getMessage());
        echo "<script>alert('❌ เกิดข้อผิดพลาดทางฐานข้อมูล กรุณาติดต่อผู้ดูแลระบบ'); window.history.back();</script>";
    }
} 

// เตรียมตัวแปร
$user_data = null;
$is_edit = false;
$roles = [];

// 1. ดึงรายการสิทธิ์ (Roles) ทั้งหมดจากฐานข้อมูล มาใส่ Dropdown
$sql_roles = "SELECT * FROM roles ORDER BY role_id ASC";
$roles = CON::selectArrayDB([], $sql_roles) ?? [];

// 2. ถ้ามี ID ส่งมา ให้ดึงข้อมูลผู้ใช้นั้นมาแสดง (โหมดแก้ไข)
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql_user = "SELECT * FROM users WHERE user_id = ?";
    $user_result = CON::selectArrayDB([$id], $sql_user);
    
    if (!empty($user_result)) {
        $user_data = $user_result[0];
        $is_edit = true;
    }
}
?>

        <!-- เรียกใช้ Header กลาง -->
        <?php 
            $page_title = $is_edit ? "แก้ไขผู้ใช้งาน" : "เพิ่มผู้ใช้งานใหม่"; 
            $header_class = "header-settings"; 
            include 'includes/topbar.php'; 
        ?>

        <div class="page-content">
            <h5 class="mb-5 fw-bold text-secondary">**จัดการข้อมูลผู้ใช้งาน**</h5>

            <form action="" method="POST" class="mx-auto" style="max-width: 800px;">
                <!-- ถ้าแก้ไข ต้องส่ง ID ไปด้วย -->
                <?php if ($is_edit): ?>
                    <input type="hidden" name="user_id" value="<?php echo $user_data['user_id']; ?>">
                <?php endif; ?>

                <!-- CSRF Token Field -->
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <!-- 1. Username (ห้ามแก้ถ้ามีอยู่แล้ว) -->
                <div class="row mb-4 align-items-center">
                    <div class="col-md-3 text-md-end"><label class="fw-bold text-secondary">Username</label></div>
                    <div class="col-md-9">
                        <input type="text" name="username" class="form-control custom-input" 
                               value="<?php echo $user_data ? htmlspecialchars($user_data['username']) : ''; ?>" 
                               <?php echo $is_edit ? 'readonly style="background-color: #e9ecef !important;"' : 'required'; ?>>
                        <?php if($is_edit): ?>
                            <small class="text-muted ms-3">*Username ไม่สามารถแก้ไขได้</small>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 2. Password (เว้นว่างได้ถ้าแก้ไข) -->
                <div class="row mb-4 align-items-center">
                    <div class="col-md-3 text-md-end"><label class="fw-bold text-secondary">Password</label></div>
                    <div class="col-md-9">
                        <input type="password" name="password" class="form-control custom-input" 
                               placeholder="<?php echo $is_edit ? 'กรอกเฉพาะเมื่อต้องการเปลี่ยนรหัสผ่านใหม่' : 'กำหนดรหัสผ่าน...'; ?>"
                               <?php echo $is_edit ? '' : 'required'; ?>>
                    </div>
                </div>

                <!-- 3. ชื่อ-สกุล -->
                <div class="row mb-4 align-items-center">
                    <div class="col-md-3 text-md-end"><label class="fw-bold text-secondary">ชื่อ-สกุล</label></div>
                    <div class="col-md-9">
                        <input type="text" name="fullname" class="form-control custom-input" 
                               value="<?php echo $user_data ? htmlspecialchars($user_data['fullname']) : ''; ?>" required>
                    </div>
                </div>

                <!-- 4. แผนก -->
                <div class="row mb-4 align-items-center">
                    <div class="col-md-3 text-md-end"><label class="fw-bold text-secondary">แผนก/ฝ่าย</label></div>
                    <div class="col-md-9">
                        <input type="text" name="department" class="form-control custom-input" 
                               value="<?php echo $user_data ? htmlspecialchars($user_data['department'] ?? '') : ''; ?>">
                    </div>
                </div>

                <!-- 5. สิทธิ์การใช้งาน (Dropdown จาก DB) -->
                <div class="row mb-4 align-items-center">
                    <div class="col-md-3 text-md-end"><label class="fw-bold text-secondary">สิทธิ์การใช้งาน</label></div>
                    <div class="col-md-9">
                        <select name="role_id" class="form-select custom-input">
                            <?php if (!empty($roles)): ?>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo $role['role_id']; ?>" 
                                        <?php echo ($user_data && $user_data['role_id'] == $role['role_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($role['role_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <!-- Fallback กรณีดึง DB ไม่ได้ -->
                                <option value="2">User</option>
                                <option value="1">Admin</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>

                <!-- ปุ่มกด -->
                <div class="d-flex justify-content-end mt-5 pt-3 border-top">
                    <a href="settings/" class="btn btn-danger rounded-pill px-4 me-2 shadow-sm text-decoration-none">
                        <i class="fas fa-times me-2"></i>ยกเลิก
                    </a>
                    <button type="submit" class="btn btn-success rounded-pill px-5 shadow-sm" style="background-color: #00E676; border:none; color: #000; font-weight:600;">
                        <i class="fas fa-save me-2"></i><?php echo $is_edit ? 'บันทึกการแก้ไข' : 'บันทึกข้อมูล'; ?>
                    </button>
                </div>
            </form>
        </div>
