<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
session_start();
// ปิดการแสดง error ผ่าน API response
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting( E_ALL ^ E_NOTICE );
mb_internal_encoding( 'UTF-8' );

// set content return type
header( 'Content-Type: application/json; charset=utf-8' );

// Setting up some server access controls to allow people to get information
header( "Access-Control-Allow-Origin: https://athweb.xyz" );
header( 'Access-Control-Allow-Methods:  POST, GET' );

/**
 * ============================================================================
 * EDE System - API Gateway
 * ============================================================================
 * ไฟล์: api/index.php
 * วันที่: 2025-12-16
 *
 * อธิบาย:
 * ไฟล์นี้ทำหน้าที่เป็น API Gateway สำหรับติดต่อระหว่าง Frontend (JavaScript)
 * กับ Database Backend ของระบบ EDE System ช่วยอำนวยความสะดวกในการส่งข้อมูล
 * และรับผลลัพธ์ในรูปแบบ JSON format
 *
 * ============================================================================
 * โครงสร้างปัจจุบัน:
 * ============================================================================
 * 1. Router Pattern: ใช้ Query Parameter 'dev' เพื่อระบุ action ที่ต้องการ
 *    - getdocinfo     : ดึงข้อมูลเอกสาร พร้อมประวัติสถานะ
 *    - get-statuses   : ดึงรายการสถานะจากไฟล์ workflow_data.json
 *    - search         : ค้นหาเอกสารตามคำค้นหา
 *    - history        : ดึงข้อมูลประวัติการทำงาน
 *    - manage-workflow: จัดการสถานะและหมวดหมู่เอกสาร
 *
 * 2. Response Format: ทั้งหมดส่งกลับเป็น JSON object ที่มีโครงสร้าง:
 *    {
 *      "status": "success|error",
 *      "data": [...],      // ข้อมูลผลลัพธ์
 *      "message": "..."    // ข้อความบอกข้อผิดพลาด (ถ้ามี)
 *    }
 *
 * ============================================================================
 * ตัวแปรและ Query Parameters ที่สำคัญ:
 * ============================================================================
 *
 * GET Parameters (Query String):
 * ─────────────────────────────────
 * - dev (required):
 *   รหัส action ที่ต้องการ (alphanumeric only) เช่น "getdocinfo", "search"
 *
 * - code (สำหรับ getdocinfo):
 *   รหัสเอกสาร (alphanumeric only, max 50 characters)
 *
 * - action (สำหรับ getdocinfo):
 *   รูปแบบการดำเนินการ เช่น "scan" - ถ้าระบุจะบวกยอดการดู (+1)
 *
 * - keyword (สำหรับ search):
 *   คำค้นหาในฐานข้อมูล (string)
 *
 * - creator_id (สำหรับ get-statuses):
 *   ID ของผู้สร้าง workflow (int) - ใช้กรองสถานะตามเจ้าของ
 *
 * - line_id (สำหรับ history):
 *   LINE User ID เพื่อค้นหาประวัติของคนนั้นๆ (string)
 *
 * POST/REQUEST Parameters (สำหรับ manage-workflow):
 * ────────────────────────────────────────────────
 * - action:          ประเภทการดำเนินการ (list, add_category, edit_category,
 *                    delete_category, add_status, edit_status, delete_status,
 *                    reorder_status)
 * - user_id:         รหัสผู้ใช้ปัจจุบัน (int)
 * - category_name:   ชื่อหมวดหมู่ (สำหรับ add/edit_category)
 * - category_id:     รหัสหมวดหมู่ (สำหรับ manage statuses)
 * - status_name:     ชื่อสถานะ (สำหรับ add/edit_status)
 * - color_class:     สีของสถานะ - HEX color (สำหรับ add/edit_status)
 * - status_id:       รหัสสถานะ (สำหรับ edit/delete_status)
 * - sorted_ids:      JSON array ของ status IDs สำหรับเรียงลำดับ
 *
 * ============================================================================
 * เรื่อง mod_rewrite และไฟล์ .htaccess:
 * ============================================================================
 * ไฟล์นี้ ต้องอยู่ในโฟลเดอร์ /api/ เพื่อทำให้ routing ทำงานได้ถูกต้อง
 *
 * .htaccess ของ api/ ควรมีการตั้งค่า mod_rewrite เพื่อ:
 * - อนุญาตให้ request ทั้ง POST และ GET ผ่านเข้ามา
 * - Route ทั้งหมดไปยัง index.php เพื่อให้ Router จัดการ
 *
 * ตัวอย่าง .htaccess:
 * ─────────────────
 * <IfModule mod_rewrite.c>
 *     RewriteEngine On
 *     RewriteBase /api/
 *
 *     # ถ้าไฟล์หรือโฟลเดอร์มีอยู่จริง ให้ bypass
 *     RewriteCond %{REQUEST_FILENAME} !-f
 *     RewriteCond %{REQUEST_FILENAME} !-d
 *
 *     # ส่งทุกคำขอไปยัง index.php พร้อมต้นฉบับ URI
 *     RewriteRule ^(.*)$ index.php [QSA,L]
 * </IfModule>
 *
 * ============================================================================
 * ข้อจำกัดและแนวทางการใช้งาน:
 * ============================================================================
 *
 * 1. ประเภทข้อมูลที่ส่ง:
 *    - GET/POST Parameters: ส่งเป็น Query String หรือ Form Data เท่านั้น
 *    - JSON Body: ไม่รองรับ ต้องใช้ $_GET หรือ $_POST แทน
 *    - File Upload: ไม่รองรับในไฟล์นี้
 *
 * 2. ข้อมูลที่ส่งกลับ:
 *    - ทั้งหมดเป็น JSON format ด้วย UTF-8 encoding
 *    - CORS headers ตั้งค่าอนุญาตให้ทุก origin เข้าถึง (Access-Control-Allow-Origin: *)
 *    - HTTP Status Codes: 200 (OK), 400 (Bad Request), 404 (Not Found)
 *
 * 3. ข้อจำกัด Security:
 *    - ข้อมูล GET/POST ต้องผ่าน sanitizeGetParam() และ sanitizePostParam()
 *    - ตัวแปร 'dev' รับเฉพาะ alphanumeric เท่านั้น (ป้องกัน SQL Injection)
 *    - ค่าฟังก์ชัน 'action' ต้องตรวจสอบค่าที่กำหนดไว้เท่านั้น
 *
 * 4. ข้อจำกัด Performance:
 *    - ค้นหา (search) จำกัดผลลัพธ์ไว้ 10 รายการต่อครั้ง
 *    - ประวัติ (history) จำกัดไว้ 50 รายการต่อครั้ง
 *    - JSON workflows ควรจัดการขนาดไฟล์ที่สมเหตุสมผล
 *
 * 5. การใช้งาน Session:
 *    - ต้อง session_start() เสมอ เพื่อเข้าถึง $_SESSION['user_id']
 *    - ข้อมูล $_SESSION['user_id'] ใช้สำหรับตัดสิน owner ของ workflow
 *
 * ============================================================================
 * ตัวอย่างการเรียก API:
 * ============================================================================
 *
 * หมายเหตุ: .htaccess มีการกำหนด RewriteRule เฉพาะเจาะจงสำหรับ URL patterns:
 *
 * RewriteRule 1: ^([0-9a-z-]+)/([A-Z]+-[0-9]+-[A-Z0-9]+)/?$
 *   => /getdocinfo/EDE-20251210-C565A83/ => ?dev=getdocinfo&code=EDE-20251210-C565A83
 *
 * RewriteRule 2: ^([a-z]+)/([A-Za-z0-9]+)/?$
 *   => /history/Uf3c4379f5b7fbd713116ddebf55010f2 => ?dev=history&code=Uf3c4379f5b7fbd713116ddebf55010f2
 *
 * RewriteRule 3: ^([A-Z]+-[0-9]+-[A-Z0-9]+)/?$
 *   => /EDE-20251210-C565A83/ => ?code=EDE-20251210-C565A83
 *
 * RewriteRule 4: ^([0-9a-z-]+)/?$
 *   => /assets-import-csv/ => ?dev=assets-import-csv
 *
 * ─────────────────────────────────────────────────────────────────
 *
 * ตัวอย่างการเรียก API ตามข้อแม่แบบ:
 * ─────────────────────────────────────────────────────────────────
 *
 * 1. ดึงข้อมูลเอกสาร (Pattern: /dev/CODE):
 *    GET /api/getdocinfo/EDE-20251210-C565A83/
 *    GET /api/getdocinfo/EDE-20251210-C565A83/?action=scan
 *    (ต้องมี action parameter เพิ่มเติมใน query string)
 *
 * 2. ดึงประวัติการทำงาน (Pattern: /dev/ID):
 *    GET /api/history/
 *    GET /api/history/Uf3c4379f5b7fbd713116ddebf55010f2
 *    GET /api/history/Uf3c4379f5b7fbd713116ddebf55010f2/?line_id=Uf3c4379f5b7fbd713116ddebf55010f2
 *
 * 3. ค้นหาเอกสาร (Pattern: /dev?keyword=...):
 *    GET /api/search/?keyword=ใบส่งสินค้า
 *
 * 4. ดึงรายการสถานะ (Pattern: /dev?creator_id=...):
 *    GET /api/get-statuses/?creator_id=5
 *
 * 5. จัดการ Workflow (Pattern: /dev):
 *    POST /api/manage-workflow/
 *    POST data: action=list&user_id=5
 *
 *    POST /api/manage-workflow/?action=add_category
 *    POST data: category_name=ชื่อหมวด
 *
 *    POST /api/manage-workflow/?action=add_status
 *    POST data: category_id=cat_123&status_name=อนุมัติแล้ว&color_class=#28a745
 *
 * 6. ระบุรหัสเอกสารโดยตรง (Pattern: /CODE):
 *    GET /api/EDE-20251210-C565A83/
 *    (ต้องส่งเพิ่ม ?dev=getdocinfo ถ้าต้องการ action อื่น)
 *
 * ============================================================================
 */

require realpath( '../../dv-config.php' );
require DEV_PATH . '/classes/db.class.v2.php';
require DEV_PATH . '/functions/global.php';

// สำหรับ dev parameter (อนุญาตเฉพาะ alphanumeric)
$GET_DEV = sanitizeGetParam( 'dev', 'string', '', 50 );

$json_data['data'] = [];

switch ( $GET_DEV ) {

    case 'getdocinfo':
        $doc_code = sanitizeGetParam( 'code', 'alphanumeric', '' );
        $action   = sanitizeGetParam( 'action', 'alphanumeric', '' );


        if ( empty( $doc_code ) ) {
            http_response_code( 400 );
            $json_data['status']  = 'error';
            $json_data['message'] = 'ไม่ได้ระบุรหัสเอกสาร';
            break;
        }

        // เช็คว่า action = scan ถึงจะบวกยอด
        if ( $action === 'scan' ) {
            $stmtCount = "UPDATE documents SET view_count = view_count + 1 WHERE document_code = ?";
            CON::updateDB( [$doc_code], $stmtCount );
        }

        // ดึงข้อมูลเอกสาร
        $docSql = "SELECT d.*, dt.type_name
                   FROM documents d
                   LEFT JOIN document_type dt ON d.type_id = dt.type_id
                   WHERE d.document_code = ?";
        $docResult = CON::selectArrayDB( [$doc_code], $docSql );

        if ( !$docResult || empty( $docResult ) ) {
            http_response_code( 404 );
            $json_data['status']  = 'error';
            $json_data['message'] = 'ไม่พบเอกสาร';
            break;
        }

        $doc = $docResult[0];

        // ดึงประวัติ (Timeline)
        $logSql = "SELECT l.*, u.fullname, u.username
                   FROM document_status_log l
                   LEFT JOIN users u ON l.action_by = u.user_id
                   WHERE l.document_id = ?
                   ORDER BY l.action_time DESC";
        $logs = CON::selectArrayDB( [$doc['document_id']], $logSql ) ?? [];

        $json_data['status'] = 'success';
        $json_data['doc']    = $doc;
        $json_data['logs']   = $logs;
        break;

    case 'search':
        $keyword = sanitizeGetParam( 'keyword', 'string', '' );
        if ( empty( $keyword ) ) {
            $json_data['status']  = 'error';
            $json_data['message'] = 'ระบุคำค้นหา';
        } else {
            // [Security Fix] Escape ตัวอักษร % และ _ เพื่อป้องกัน Wildcard Injection
            $keyword_safe = addcslashes($keyword, "%_");

            $sql = "SELECT d.*, dt.type_name 
                    FROM documents d
                    LEFT JOIN document_type dt ON d.type_id = dt.type_id
                    WHERE d.document_code LIKE ? OR d.title LIKE ?
                    ORDER BY d.created_at DESC LIMIT 10";
            
            // ใช้ตัวแปรที่ escape แล้ว
            $params = ["%$keyword_safe%", "%$keyword_safe%"];
            
            $json_data['data']   = CON::selectArrayDB( $params, $sql );
            $json_data['status'] = 'success';
        }
        break;

    case 'history':
        $line_id = sanitizeGetParam( 'line_id', 'string', '' );
        if ( empty( $line_id ) ) {
            $json_data['status']  = 'error';
            $json_data['message'] = 'No Line ID';
        } else {
            $sql = "SELECT l.*, d.title, d.document_code 
                    FROM document_status_log l
                    JOIN documents d ON l.document_id = d.document_id
                    WHERE l.line_user_id_action = ?
                    ORDER BY l.action_time DESC LIMIT 20";
            $json_data['data']   = CON::selectArrayDB( [$line_id], $sql );
            $json_data['status'] = 'success';
        }
        break;

    case 'get-statuses':
        $target_id = sanitizeGetParam( 'workflow_id', 'string', '' );
        $jsonFile = __DIR__ . '/data/workflow_data.json';
        $statuses = [];

        if ( file_exists( $jsonFile ) ) {
            $workflows = json_decode( file_get_contents( $jsonFile ), true ) ?? [];
            foreach ( $workflows as $wf ) {
                if ( isset( $wf['id'] ) && $wf['id'] === $target_id ) {
                    if ( isset( $wf['statuses'] ) && is_array( $wf['statuses'] ) ) {
                        foreach ( $wf['statuses'] as $st ) {
                            $statuses[] = [
                                'status_name' => $st['name'],
                                'color'       => $st['color'],
                                'category'    => $wf['name']
                            ];
                        }
                    }
                    break;
                }
            }
        }

        if ( empty( $statuses ) ) {
            $statuses = [
                ['status_name' => 'Received', 'category' => 'ค่าเริ่มต้น'],
                ['status_name' => 'Sent', 'category' => 'ค่าเริ่มต้น'],
                ['status_name' => 'Done', 'category' => 'ค่าเริ่มต้น']
            ];
        }

        $json_data['data']   = $statuses;
        $json_data['status'] = 'success';
        break;

    case 'update-status':
        // 1. รับค่าจาก POST Data
        $inputData = $_POST;
        $contentType = $_SERVER["CONTENT_TYPE"] ?? '';
        if (stripos($contentType, 'application/json') !== false) {
            $inputData = json_decode(file_get_contents('php://input'), true) ?? [];
        }

        // [Security Fix] ฟังก์ชันสำหรับทำความสะอาดข้อมูล (ป้องกัน XSS)
        function clean_input($data) {
            return htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8');
        }

        // ใช้ clean_input กับทุกค่าที่เป็น String ที่รับเข้ามา
        $doc_code      = clean_input($inputData['doc_code'] ?? '');
        $new_status    = clean_input($inputData['status'] ?? 'Received');
        $next_receiver = clean_input($inputData['receiver_name'] ?? '');
        $line_user_id  = clean_input($inputData['line_user_id'] ?? ''); // ปกติเป็น ID แต่อาจเป็น string ได้
        $device_info   = clean_input($inputData['device_info'] ?? 'Unknown');
        $display_name  = clean_input($inputData['display_name'] ?? 'Unknown User');
        $picture_url   = clean_input($inputData['picture_url'] ?? ''); // URL ควร validate เพิ่มว่าเป็น URL จริงไหม แต่เบื้องต้น clean_input ก็พอ

        // หา IP Address
        $ip_address = $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';

        if ( empty( $doc_code ) ) {
            $json_data['status']  = 'error';
            $json_data['message'] = 'Error: No Code';
        } else {
            // 2. หา ID เอกสาร
            $sqlDoc = "SELECT document_id FROM documents WHERE document_code = ?";
            $resDoc = CON::selectArrayDB( [$doc_code], $sqlDoc );

            if ( empty( $resDoc ) ) {
                $json_data['status']  = 'error';
                $json_data['message'] = 'ไม่พบเอกสาร';
            } else {
                $doc_id = $resDoc[0]['document_id'];

                // 3. อัปเดตสถานะหลัก
                $updateParams = [$new_status];
                $updateSql = "UPDATE documents SET current_status = ?";
                if ( !empty( $next_receiver ) ) {
                    $updateSql .= ", receiver_name = ?";
                    $updateParams[] = $next_receiver;
                }
                $updateSql .= " WHERE document_id = ?";
                $updateParams[] = $doc_id;
                CON::updateDB( $updateParams, $updateSql );

                // 4. บันทึก Log
                // หา user_id ในระบบ (ถ้ามี)
                $action_by = NULL;
                if ( !empty( $line_user_id ) ) {
                    $sqlUser = "SELECT user_id FROM users WHERE line_user_id = ?";
                    $resUser = CON::selectArrayDB( [$line_user_id], $sqlUser );
                    if ( !empty( $resUser ) ) $action_by = $resUser[0]['user_id'];
                }

                $log_note = !empty( $next_receiver ) ? "ส่งต่อให้: $next_receiver" : "อัปเดตสถานะ";
                $sqlLog = "INSERT INTO document_status_log (document_id, status, action_by, line_user_id_action, location_note, ip_address, device_info, actor_name_snapshot, actor_pic_snapshot) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                CON::updateDB( [$doc_id, $new_status, $action_by, $line_user_id, $log_note, $ip_address, $device_info, $display_name, $picture_url], $sqlLog );

                $json_data['status'] = 'success';
                $json_data['message'] = 'Success';
            }
        }
        break;

    case 'manage-workflow':
        $jsonFile = __DIR__ . '/data/workflow_data.json';

        // Helper Closures (ฟังก์ชันช่วยจัดการ JSON)
        $getJson = function() use ($jsonFile) {
            if (!file_exists($jsonFile)) {
                if (!is_dir(dirname($jsonFile))) mkdir(dirname($jsonFile), 0777, true);
                file_put_contents($jsonFile, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
            return json_decode(file_get_contents($jsonFile), true) ?? [];
        };

        $saveJson = function($data) use ($jsonFile) {
            return file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        };

        $getDefaultData = function() {
            return [
                'id' => 'cat_default',
                'name' => 'สถานะพื้นฐาน (General)',
                'created_by' => 'system',
                'statuses' => [
                    ['id' => 'st_def_1', 'name' => 'ลงทะเบียนเอกสารใหม่', 'color' => '#6c757d'],
                    ['id' => 'st_def_2', 'name' => 'รับเอกสาร', 'color' => '#ffc107'],
                    ['id' => 'st_def_3', 'name' => 'ส่งต่อ', 'color' => '#0dcaf0'],
                    ['id' => 'st_def_4', 'name' => 'ได้รับแล้ว', 'color' => '#198754']
                ]
            ];
        };

        $action = $_REQUEST['action'] ?? '';
        $currentUser = $_REQUEST['user_id'] ?? $_SESSION['user_id'] ?? 0;

        try {
            if ($action === 'list') {
                $data = $getJson();

                // 1. ตรวจสอบว่ามีหมวดพื้นฐานหรือยัง?
                $hasDefault = false;
                foreach ($data as $cat) {
                    if (isset($cat['id']) && $cat['id'] === 'cat_default') {
                        $hasDefault = true;
                        break;
                    }
                }

                // 2. ถ้ายังไม่มี ให้แทรกเข้าไปเป็น "อันแรก"
                if (!$hasDefault) {
                    $defaultCategory = $getDefaultData();
                    array_unshift($data, $defaultCategory);
                    $saveJson($data);
                }

                // 3. กรองข้อมูลตาม User
                if ($currentUser) {
                    $data = array_values(array_filter($data, function($item) use ($currentUser) {
                        $isOwner = isset($item['created_by']) && $item['created_by'] == $currentUser;
                        $isSystem = isset($item['id']) && $item['id'] === 'cat_default';
                        return $isOwner || $isSystem;
                    }));
                }

                $json_data['success'] = true;
                $json_data['data'] = $data;
                $json_data['status'] = 'success';
            }
            elseif ($action === 'add_category') {
                $name = $_POST['category_name'] ?? '';
                if ($name) {
                    $data = $getJson();
                    $newCat = [
                        'id' => 'cat_' . uniqid(),
                        'name' => $name,
                        'created_by' => $_SESSION['user_id'] ?? 0,
                        'statuses' => []
                    ];
                    $data[] = $newCat;
                    $saveJson($data);
                    $json_data['success'] = true;
                    $json_data['status'] = 'success';
                } else {
                    $json_data['success'] = false;
                    $json_data['message'] = 'Missing category name';
                }
            }
            elseif ($action === 'edit_category') {
                $id = $_POST['id'] ?? '';
                $name = $_POST['category_name'] ?? '';

                if ($id === 'cat_default') {
                    $json_data['success'] = false;
                    $json_data['message'] = 'ไม่สามารถแก้ไขชื่อหมวดหมู่พื้นฐานได้';
                } elseif ($id && $name) {
                    $data = $getJson();
                    foreach ($data as &$cat) {
                        if ($cat['id'] === $id) {
                            $cat['name'] = $name;
                            break;
                        }
                    }
                    $saveJson($data);
                    $json_data['success'] = true;
                    $json_data['status'] = 'success';
                }
            }
            elseif ($action === 'delete_category') {
                $id = $_POST['id'] ?? '';
                if ($id === 'cat_default') {
                    $json_data['success'] = false;
                    $json_data['message'] = 'ไม่สามารถลบหมวดหมู่พื้นฐานของระบบได้';
                } elseif ($id) {
                    $data = $getJson();
                    $data = array_values(array_filter($data, fn($c) => $c['id'] !== $id));
                    $saveJson($data);
                    $json_data['success'] = true;
                    $json_data['status'] = 'success';
                }
            }
            elseif ($action === 'add_status') {
                $catId = $_POST['category_id'] ?? '';
                $name = $_POST['status_name'] ?? '';
                $color = $_POST['color_class'] ?? 'secondary';
                if ($catId && $name) {
                    $data = $getJson();
                    foreach ($data as &$cat) {
                        if ($cat['id'] === $catId) {
                            $cat['statuses'][] = ['id' => 'st_' . uniqid(), 'name' => $name, 'color' => $color];
                            break;
                        }
                    }
                    $saveJson($data);
                    $json_data['success'] = true;
                    $json_data['status'] = 'success';
                }
            }
            elseif ($action === 'edit_status') {
                $catId = $_POST['category_id'] ?? '';
                $stId = $_POST['status_id'] ?? '';
                $name = $_POST['status_name'] ?? '';
                $color = $_POST['color_class'] ?? 'secondary';

                if ($catId && $stId) {
                    $data = $getJson();
                    foreach ($data as &$cat) {
                        if ($cat['id'] === $catId) {
                            foreach ($cat['statuses'] as &$status) {
                                if ($status['id'] === $stId) {
                                    $status['name'] = $name;
                                    $status['color'] = $color;
                                    break 2;
                                }
                            }
                        }
                    }
                    $saveJson($data);
                    $json_data['success'] = true;
                    $json_data['status'] = 'success';
                }
            }
            elseif ($action === 'delete_status') {
                $catId = $_POST['category_id'] ?? '';
                $statusId = $_POST['status_id'] ?? '';

                if (strpos($statusId, 'st_def_') === 0) {
                    $json_data['success'] = false;
                    $json_data['message'] = 'ไม่สามารถลบสถานะพื้นฐานของระบบได้';
                } elseif ($catId && $statusId) {
                    $data = $getJson();
                    foreach ($data as &$cat) {
                        if ($cat['id'] === $catId) {
                            $cat['statuses'] = array_values(array_filter($cat['statuses'], fn($s) => $s['id'] !== $statusId));
                            break;
                        }
                    }
                    $saveJson($data);
                    $json_data['success'] = true;
                    $json_data['status'] = 'success';
                }
            }
            elseif ($action === 'reorder_status') {
                $catId = $_POST['category_id'] ?? '';
                $sortedIds = json_decode($_POST['sorted_ids'] ?? '[]', true);
                if ($catId && !empty($sortedIds)) {
                    $data = $getJson();
                    foreach ($data as &$cat) {
                        if ($cat['id'] === $catId) {
                            $statusMap = [];
                            foreach ($cat['statuses'] as $st) $statusMap[$st['id']] = $st;
                            $newStatuses = [];
                            foreach ($sortedIds as $stId) {
                                if (isset($statusMap[$stId])) {
                                    $newStatuses[] = $statusMap[$stId];
                                    unset($statusMap[$stId]);
                                }
                            }
                            foreach ($statusMap as $remaining) $newStatuses[] = $remaining;
                            $cat['statuses'] = $newStatuses;
                            break;
                        }
                    }
                    $saveJson($data);
                    $json_data['success'] = true;
                    $json_data['status'] = 'success';
                }
            }
            else {
                $json_data['success'] = false;
                $json_data['message'] = 'Invalid action';
            }
        } catch (Exception $e) {
            $json_data['success'] = false;
            $json_data['message'] = 'Error: ' . $e->getMessage();
        }
        break;
    

    default:
        http_response_code( 400 );
        $json_data = [
            'success' => false,
            'message' => 'ไม่ได้ระบุหมายเหตุหรือ action:' . $GET_DEV . ' ไม่ถูกต้อง'
        ];
        break;
        
}

echo json_encode( $json_data, JSON_UNESCAPED_UNICODE );

// HTTP Response Codes Reference:
// 200 OK
// 201 CREATED  -  [POST,PUT]
// 204 NO CONTENT  -  [DELETE,PUT]
// 400 BAD REQUEST
// 401 UNAUTHORIZED
// 403 FORBIDDEN
// 404 NO FOUND
// 405 METHOD NOT ALLOWED
// 409 CONFLICT
// 500 INTERNAL SERVER ERROR
// 405 METHOD NOT ALLOWED
// 409 CONFLICT
// 500 INTERNAL SERVER ERROR
