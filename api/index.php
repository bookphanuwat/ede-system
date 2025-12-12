<?php
session_start();
error_reporting( E_ALL ^ E_NOTICE );
mb_internal_encoding( 'UTF-8' );

// set content return type
header( 'Content-Type: application/json; charset=utf-8' );

// Setting up some server access controls to allow people to get information
header( "Access-Control-Allow-Origin: *" );
header( 'Access-Control-Allow-Methods:  POST, GET' );

require realpath( '../../dv-config.php' );
require DEV_PATH . '/classes/db.class.v2.php';
require DEV_PATH . '/functions/global.php';

// สำหรับ dev parameter (อนุญาตเฉพาะ alphanumeric)
$GET_DEV = sanitizeGetParam( 'dev', 'alphanumeric', '', 50 );

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

     case 'get_statuses':
        // 1. รับค่า creator_id ผ่านฟังก์ชัน sanitize (ปลอดภัยกว่า $_GET โดยตรง)
        // กำหนด default เป็น 0 ถ้าไม่ส่งมา
        $creator_id = sanitizeGetParam('creator_id', 'int', 0); 

        // 2. กำหนด Path ไฟล์ JSON 
        // __DIR__ คือโฟลเดอร์ปัจจุบัน (api) ถอยกลับไป 1 ขั้น (..) แล้วเข้า data
        $jsonFile = __DIR__ . '/../data/workflow_data.json'; 
        
        $statuses = [];

        if (file_exists($jsonFile)) {
            $jsonContent = file_get_contents($jsonFile);
            $workflows = json_decode($jsonContent, true) ?? [];
            
            // 3. วนลูปหาหมวดหมู่
            foreach ($workflows as $wf) {
                // กรองเฉพาะ Workflow ของ Creator คนนี้ (หรือของคนที่ ID=0/Null ถ้าเป็นระบบกลาง)
                // หมายเหตุ: ต้องแก้ตรงนี้ให้ยืดหยุ่น ถ้า workflow ไม่ระบุ created_by ให้ถือว่าเป็นของทุกคน
                $wfCreator = $wf['created_by'] ?? 0;
                
                if ($wfCreator == $creator_id || $wfCreator == 0) {
                    // เช็คว่ามี key 'statuses' และเป็น array ไหม กัน error
                    if (isset($wf['statuses']) && is_array($wf['statuses'])) {
                        foreach ($wf['statuses'] as $st) {
                            $statuses[] = [
                                'status_name' => $st['name'],
                                'color'       => $st['color'],
                                'category'    => $wf['name'] // ชื่อหมวดหมู่
                            ];
                        }
                    }
                }
            }
        }

        // ถ้าไม่มีข้อมูลเลย ให้ใส่ Default
        if (empty($statuses)) {
            $statuses = [
                ['status_name' => 'Received', 'category' => 'ค่าเริ่มต้น'],
                ['status_name' => 'Sent', 'category' => 'ค่าเริ่มต้น'],
                ['status_name' => 'Done', 'category' => 'ค่าเริ่มต้น']
            ];
        }

        // 4. ส่งค่ากลับ
        $json_data['data']   = $statuses;
        $json_data['status'] = 'success';
        break;

    case 'search':
        // ค้นหาเอกสารจาก keyword
        $keyword = sanitizeGetParam( 'keyword', 'string', '' );
        if ( empty( $keyword ) ) {
            $json_data['status']  = 'error';
            $json_data['message'] = 'ระบุคำค้นหา';
        } else {
            $sql = "SELECT d.*, dt.type_name
                    FROM documents d
                    LEFT JOIN document_type dt ON d.type_id = dt.type_id
                    WHERE d.document_code LIKE ? OR d.title LIKE ?
                    ORDER BY d.created_at DESC LIMIT 10";
            $params = ["%{$keyword}%", "%{$keyword}%"];
            $json_data['data'] = CON::selectArrayDB( $params, $sql );
            $json_data['status'] = 'success';
        }
        break;

    case 'history':
        $line_id = sanitizeGetParam('line_id', 'string', '');
        if (empty($line_id)) {
            $sql = "SELECT l.*, d.title, d.document_code, u.fullname
                FROM document_status_log l
                LEFT JOIN documents d ON l.document_id = d.document_id
                LEFT JOIN users u ON l.action_by = u.user_id
                ORDER BY l.action_time DESC LIMIT 50";
            $json_data['data']   = CON::selectArrayDB( [], $sql );
        } else {
            $sql = "SELECT l.*, d.title, d.document_code 
                    FROM document_status_log l
                    JOIN documents d ON l.document_id = d.document_id
                    WHERE l.line_user_id_action = ?
                    ORDER BY l.action_time DESC LIMIT 20";
            $json_data['data']   = CON::selectArrayDB( [$line_id], $sql );
        }
        $json_data['status'] = 'success';
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
