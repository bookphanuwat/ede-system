<?php
    $page_title   = "ติดตามเอกสาร";
    $header_class = "header-tracking";
    include 'includes/topbar.php';

    $search_query = $_GET['search'] ?? '';
    $doc_data = null;       // สำหรับกรณีเจอ 1 รายการ
    $doc_list = [];         // สำหรับกรณีเจอหลายรายการ
    $logs = [];

    // ตรวจสอบสิทธิ์
    $is_admin = ( isset($_SESSION['role']) && stripos( $_SESSION['role'], 'admin' ) !== false );
    $user_id = $_SESSION['user_id'] ?? 0;

    // ฟังก์ชันคำนวณเวลา
    function time_elapsed_string( $datetime, $full = false ) {
        $now = new DateTime;
        $ago = new DateTime( $datetime );
        $diff = $now->diff( $ago );
        $string = array( 'y' => 'ปี', 'm' => 'เดือน', 'd' => 'วัน', 'h' => 'ชั่วโมง', 'i' => 'นาที' );
        foreach ( $string as $k => &$v ) {
            if ( $diff->$k ) $v = $diff->$k . ' ' . $v; else unset( $string[$k] );
        }
        if ( !$full ) $string = array_slice( $string, 0, 2 );
        return $string ? implode( ', ', $string ) : 'เมื่อสักครู่';
    }

    // โหลดข้อมูลสีจาก workflow_data.json
    $workflow_colors = [];
    $json_file = __DIR__ . '/../api/data/workflow_data.json';
    if (file_exists($json_file)) {
        $workflows = json_decode(file_get_contents($json_file), true) ?? [];
        foreach ($workflows as $wf) {
            if (!empty($wf['statuses'])) {
                foreach ($wf['statuses'] as $st) {
                    $workflow_colors[$st['name']] = $st['color'];
                }
            }
        }
    }

    // ฟังก์ชันสร้าง Badge สถานะ (รองรับ Hex Color)
    function getStatusBadge($status, $colors) {
        $c = $colors[$status] ?? '';
        if (!$c) {
            // Fallback ค่าเริ่มต้น
            if ($status === 'Received') $c = 'success';
            elseif ($status === 'Sent') $c = 'warning';
            elseif ($status === 'Registered') $c = 'info';
            elseif ($status === 'Late') $c = 'danger';
            else $c = 'secondary';
        }

        if (strpos($c, '#') === 0) {
            return '<span class="badge rounded-pill text-uppercase px-3 py-2 shadow-sm" style="background-color: ' . $c . '; color: #fff;">' . htmlspecialchars($status) . '</span>';
        } else {
            return '<span class="badge rounded-pill bg-' . $c . ' text-uppercase px-3 py-2">' . htmlspecialchars($status) . '</span>';
        }
    }

    // ค้นหาเอกสาร
    if ( !empty( $search_query ) ) {
        $sql = "SELECT d.*, dt.type_name, u.fullname as creator_name 
                FROM documents d 
                LEFT JOIN document_type dt ON d.type_id = dt.type_id 
                LEFT JOIN users u ON d.created_by = u.user_id 
                WHERE (d.document_code = ? OR d.title LIKE ?)";

        if ( !$is_admin ) {
            // ถ้าไม่ใช่ Admin ค้นหาได้เฉพาะของตัวเอง
            $search_params = [$search_query, "%$search_query%", $user_id];
            $sql .= " AND d.created_by = ?";
        } else {
            $search_params = [$search_query, "%$search_query%"];
        }

        $doc_result = CON::selectArrayDB( $search_params, $sql );

        if ( $doc_result && count( $doc_result ) > 1 ) {
            // กรณีเจอมากกว่า 1 รายการ (เช่น ค้นด้วยชื่อ) ให้เก็บลง doc_list เพื่อไปแสดงรายการเลือก
            $doc_list = $doc_result;
        } elseif ( $doc_result && count( $doc_result ) == 1 ) {
            // กรณีเจอ 1 รายการ ให้ดึงข้อมูลมาแสดง Timeline เลย
            $doc_data = $doc_result[0];
            
            $sql_log = "SELECT l.*, u.fullname as db_user_name 
                        FROM document_status_log l 
                        LEFT JOIN users u ON l.action_by = u.user_id 
                        WHERE l.document_id = ? 
                        ORDER BY l.action_time DESC";
            $logs = CON::selectArrayDB( [$doc_data['document_id']], $sql_log ) ?? [];
        }
    }

    // สร้าง HTML timeline (เฉพาะกรณีมี doc_data)
    $timelineHtml = '';
    if ( $doc_data ) {
        if ( count( $logs ) > 0 ) {
            foreach ( $logs as $index => $log ) {
                $actor_name = !empty( $log['actor_name_snapshot'] ) ? $log['actor_name_snapshot'] : ( $log['db_user_name'] ?? 'Unknown' );
                $actor_pic = $log['actor_pic_snapshot'] ?? '';
                $status = $log['status'] ?? '';
                $action_time = date( 'd/m/Y H:i', strtotime( $log['action_time'] ) );
                $device_info = $log['device_info'] ?? '';
                $active_class = ( $index === 0 ) ? 'active' : '';

                $pic_html = $actor_pic
                    ? "<img src='$actor_pic' class='rounded-circle me-2 border' width='30' height='30'>"
                    : "<div class='bg-secondary text-white rounded-circle me-2 d-flex align-items-center justify-content-center' style='width:30px;height:30px;font-size:12px;'><i class='fas fa-user'></i></div>";

                $device_html = !empty( $device_info )
                    ? "<small class='text-muted' style='font-size: 0.7rem;'><i class='fas fa-mobile-alt me-1'></i>$device_info</small>"
                    : '';

                $timelineHtml .= "<div class='timeline-item'>
                    <div class='timeline-dot $active_class'></div>
                    <div class='ps-3'>
                        <div class='d-flex justify-content-between align-items-start mb-1'>
                            <h6 class='fw-bold text-dark mb-0'>$status</h6>
                            <span class='badge bg-light text-secondary border'><i class='far fa-clock me-1'></i>$action_time</span>
                        </div>
                        <div class='d-flex align-items-center mt-2'>
                            $pic_html
                            <div>
                                <p class='text-muted small mb-0'>ดำเนินการโดย: <strong>$actor_name</strong></p>
                                $device_html
                            </div>
                        </div>
                    </div>
                </div>";
            }
        } else {
            $timelineHtml = '<p class="text-muted ps-3">ยังไม่มีประวัติ</p>';
        }
    }

?>

<style>
    /* CSS สำหรับ Timeline */
    .timeline { border-left: 2px solid #e9ecef; margin-left: 10px; padding-left: 20px; padding-top: 10px; padding-bottom: 10px; }
    .timeline-item { position: relative; margin-bottom: 25px; }
    .timeline-item:last-child { margin-bottom: 0; }
    .timeline-dot {
        width: 12px; height: 12px; background: #adb5bd; border-radius: 50%;
        position: absolute; left: -27px; top: 5px; border: 2px solid #fff; box-shadow: 0 0 0 2px #e9ecef;
    }
    .timeline-dot.active { background: var(--bs-success); box-shadow: 0 0 0 2px #c3e6cb; }
    .animate-fade-in { animation: fadeIn 0.5s ease-in-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    
    /* CSS สำหรับ List เลือกเอกสาร */
    .doc-list-item { transition: all 0.2s; cursor: pointer; }
    .doc-list-item:hover { background-color: #f8f9fa; transform: translateX(5px); }
</style>

<div class="page-content">
    <h5 class="mb-4 fw-bold text-secondary text-center">**🔍 ค้นหาและติดตามเอกสาร**</h5>

    <?php if ( !$is_admin ): ?>
        <div class="text-center text-muted mb-3 small"><i class="fas fa-info-circle"></i> คุณสามารถค้นหาได้เฉพาะเอกสารที่คุณเป็นผู้สร้างเท่านั้น</div>
    <?php endif; ?>

    <form method="GET" action="<?php echo SITE_URL; ?>/index.php" class="row justify-content-center mb-5">
        <input type="hidden" name="dev" value="tracking">
        <div class="col-md-8">
            <div class="input-group shadow-sm rounded-pill overflow-hidden bg-white border p-1">
                <span class="input-group-text border-0 bg-white ps-3 text-muted"><i class="fas fa-search"></i></span>
                <input type="text" name="search" class="form-control border-0 shadow-none" placeholder="ระบุเลขที่ทะเบียน หรือ ชื่อเรื่อง..." value="<?php echo htmlspecialchars( $search_query ); ?>">
                <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold" style="background-color: var(--color-tracking); border:none;">ค้นหา</button>
            </div>
        </div>
    </form>

    <?php if ( !empty($doc_list) ): ?>
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mx-auto animate-fade-in" style="max-width: 900px;">
            <div class="card-header bg-white border-bottom p-3">
                <h6 class="mb-0 text-success fw-bold"><i class="fas fa-list-ul me-2"></i>พบเอกสารที่ตรงกัน <?php echo count($doc_list); ?> รายการ</h6>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach ($doc_list as $item): ?>
                    <a href="<?php echo SITE_URL; ?>/index.php?dev=tracking&search=<?php echo $item['document_code']; ?>" class="list-group-item list-group-item-action p-3 doc-list-item">
                        <div class="d-flex w-100 justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1 fw-bold text-dark"><?php echo htmlspecialchars($item['title']); ?></h6>
                                <small class="text-muted">
                                    <i class="fas fa-barcode me-1"></i><?php echo htmlspecialchars($item['document_code']); ?> 
                                    <span class="mx-2">|</span> 
                                    <i class="far fa-clock me-1"></i><?php echo time_elapsed_string($item['created_at']); ?>
                                </small>
                            </div>
                            <div>
                                <?php echo getStatusBadge($item['current_status'] ?? '', $workflow_colors); ?>
                                <i class="fas fa-chevron-right text-muted ms-3"></i>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

    <?php elseif ( $doc_data ): ?>
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mx-auto animate-fade-in" style="max-width: 900px;">
            <div class="card-header border-0 p-4 d-flex justify-content-between align-items-center" style="background-color: rgba(102, 187, 106, 0.1);">
                <div>
                    <h5 class="mb-1 text-success fw-bold"><i class="far fa-file-alt me-2"></i><?php echo htmlspecialchars( $doc_data['title'] ?? '' ); ?></h5>
                    <small class="text-muted">เลขทะเบียน: <strong><?php echo htmlspecialchars( $doc_data['document_code'] ?? '' ); ?></strong> | ระยะเวลา: <strong><?php echo time_elapsed_string( $doc_data['created_at'] ?? '' ); ?></strong></small>
                </div>
                <?php echo getStatusBadge( $doc_data['current_status'] ?? '', $workflow_colors ); ?>
            </div>
            <div class="card-body p-4">
                <div class="timeline">
                    <?php echo $timelineHtml; ?>
                </div>
            </div>
        </div>

    <?php elseif ( !empty( $search_query ) ): ?>
        <div class="text-center py-5 animate-fade-in">
            <h5 class="text-secondary">ไม่พบเอกสาร</h5>
            <p class="text-muted small">ไม่พบข้อมูลที่ตรงกับคำค้นหา "<?php echo htmlspecialchars($search_query); ?>" หรือคุณอาจไม่มีสิทธิ์เข้าถึง</p>
        </div>
    <?php endif; ?>
</div>