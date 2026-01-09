<?php
    $page_title   = "Dashboard (ภาพรวม)";
    $header_class = "header-dashboard";
    include 'includes/topbar.php';

    // เริ่มจับเวลาโหลดหน้า
    $start_time = microtime( true );
    $time_logs  = [];

    // ตรวจสอบสิทธิ์การเข้าใช้งาน
    if ( !isset( $_SESSION['user_id'] ) ) {
        header( "Location: login.php" );
        exit;
    }

    $is_admin = ( stripos( $_SESSION['role'], 'admin' ) !== false );
    $user_id  = $_SESSION['user_id'];

    // --------------------------------------------------------------------------------
    // ส่วนที่ 1: โหลดข้อมูลสีสถานะ
    // --------------------------------------------------------------------------------
    $workflow_colors = [];
    $success_colors  = ['#198754', 'success', '#28a745', '#659806', 'green', '#20c997', 'teal'];
    $danger_colors   = ['#dc3545', 'danger', '#e57373', 'red', '#d63384'];

    // รายชื่อสี Custom ของคุณที่มีใน main.scss
    $custom_theme_colors = ['dashboard', 'register', 'tracking', 'report', 'settings', 'status', 'scan-history'];

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

    // ฟังก์ชันแปลงชื่อสีเป็น CSS Variable หรือ Hex Code
    function getColorValue($color_name, $custom_theme_colors) {
        // กรณีเป็น Hex Code (#xxxxxx)
        if (strpos($color_name, '#') === 0) {
            return $color_name;
        }
        
        // กรณีเป็นสี Custom ของคุณ (--color-...)
        if (in_array($color_name, $custom_theme_colors)) {
            return "var(--color-$color_name)";
        }

        // กรณีเป็นสีมาตรฐาน Bootstrap (--bs-...)
        return "var(--bs-$color_name)";
    }

    // ฟังก์ชันช่วยจัดกลุ่มสถานะ
    function getStatusCategory($status_name, $color_code, $success_colors, $danger_colors) {
        $st_lower = mb_strtolower($status_name);
        $cl_lower = mb_strtolower($color_code);

        if (in_array($status_name, ['Late', 'ล่าช้า', 'สาย'])) return 'late';
        if (in_array($status_name, ['Received', 'ได้รับแล้ว', 'จบ', 'Success', 'Done', 'อนุมัติ', 'อนุมัติแล้ว'])) return 'success';

        if (in_array($cl_lower, $success_colors)) return 'success';
        foreach ($success_colors as $sc) {
            if (strpos($cl_lower, $sc) !== false) return 'success';
        }

        if (in_array($cl_lower, $danger_colors)) return 'late';
        
        return 'pending';
    }

    // ฟังก์ชันเลือกไอคอนตามหมวดหมู่
    function getStatusIcon($category) {
        switch ($category) {
            case 'success': return 'fa-check-circle';
            case 'late':    return 'fa-exclamation-circle';
            default:        return 'fa-clock'; // pending
        }
    }

    // --------------------------------------------------------------------------------
    // ส่วนที่ 2: ดึงข้อมูลสถิติ
    // --------------------------------------------------------------------------------
    $time_logs['stats_queries'] = microtime( true );
    
    $stats = ['total' => 0, 'success' => 0, 'pending' => 0, 'late' => 0];
    $status_breakdown = []; 

    $sql_stats = "SELECT current_status, COUNT(*) as count FROM documents";
    $params_stats = [];

    if ( !$is_admin ) {
        $sql_stats .= " WHERE created_by = ?";
        $params_stats = [$user_id];
    }
    
    $sql_stats .= " GROUP BY current_status ORDER BY count DESC";

    $raw_stats = CON::selectArrayDB( $params_stats, $sql_stats );

    if ($raw_stats) {
        foreach ($raw_stats as $row) {
            $st_name  = $row['current_status'];
            $st_count = (int)$row['count'];
            $st_raw_color = $workflow_colors[$st_name] ?? 'secondary';
            
            // แปลงสีให้ถูกต้อง
            $st_color_css = getColorValue($st_raw_color, $custom_theme_colors);

            $stats['total'] += $st_count;
            $category = getStatusCategory($st_name, $st_raw_color, $success_colors, $danger_colors);
            
            if ($category == 'success') {
                $stats['success'] += $st_count;
            } elseif ($category == 'late') {
                $stats['late'] += $st_count;
            } else {
                $stats['pending'] += $st_count;
            }

            $status_breakdown[] = [
                'name' => $st_name,
                'count' => $st_count,
                'color' => $st_color_css,
                'category' => $category
            ];
        }
    }
    
    $time_logs['stats_queries'] = microtime( true ) - $time_logs['stats_queries'];

    // --------------------------------------------------------------------------------
    // ส่วนที่ 3: ดึงเอกสารล่าสุด
    // --------------------------------------------------------------------------------
    $time_logs['recent_docs_query'] = microtime( true );
    $sql_recent     = "SELECT d.*, dt.type_name FROM documents d LEFT JOIN document_type dt ON d.type_id = dt.type_id";
    $recent_params  = [];
    if ( !$is_admin ) {
        $sql_recent .= " WHERE d.created_by = ?";
        $recent_params = [$user_id];
    }
    $sql_recent .= " ORDER BY d.created_at DESC LIMIT 10";
    $recent_docs    = CON::selectArrayDB( $recent_params, $sql_recent ) ?? [];
    $time_logs['recent_docs_query'] = microtime( true ) - $time_logs['recent_docs_query'];

    $total_time = microtime( true ) - $start_time;

    // Helper Function สร้าง Badge ในตาราง (ปรับให้สวยงามและอ่านง่าย)
    function getStatusBadgeHTML($status, $color_css) {
        // ใช้พื้นหลังสีเทาจางๆ และตัวหนังสือสีเข้มตามสถานะ (อ่านง่ายกว่า)
        return '<span class="badge rounded-pill shadow-sm" style="background-color: #f8f9fa; color: ' . $color_css . '; border: 1px solid rgba(0,0,0,0.1);">' . htmlspecialchars($status) . '</span>';
    }

    $docsRows = '';
    if ( count( $recent_docs ) > 0 ) {
        foreach ( $recent_docs as $doc ) {
            $doc_code     = htmlspecialchars($doc['document_code'] ?? '', ENT_QUOTES, 'UTF-8');
            $title        = htmlspecialchars($doc['title'] ?? '', ENT_QUOTES, 'UTF-8');
            $type_name    = htmlspecialchars($doc['type_name'] ?? '-', ENT_QUOTES, 'UTF-8');
            $created_at   = date( 'd/m/Y H:i', strtotime( $doc['created_at'] ?? '' ) );
            $view_count   = number_format( $doc['view_count'] ?? 0 );
            
            $st_name      = $doc['current_status'] ?? '';
            $st_raw_color = $workflow_colors[$st_name] ?? 'secondary';
            $st_color_css = getColorValue($st_raw_color, $custom_theme_colors);
            
            $status_badge = getStatusBadgeHTML($st_name, $st_color_css);

            $docsRows .= "<tr>
                <td>
                    <a href=\"#\" data-code=\"$doc_code\" class=\"doc-link shadow-sm js-open-detail\">
                        <i class=\"fas fa-qrcode me-1\"></i> $doc_code
                    </a>
                </td>
                <td class=\"text-start\">
                    $title
                    <br><small class=\"text-muted\">$type_name</small>
                </td>
                <td class=\"text-muted small\">$created_at</td>
                <td>
                    $status_badge
                    <div class=\"mt-1 text-muted small\">
                        <i class=\"far fa-eye\"></i> $view_count ครั้ง
                    </div>
                </td>
                <td>
                    <button type=\"button\" data-code=\"$doc_code\" data-title=\"$title\" class=\"btn btn-sm btn-light border rounded-pill shadow-sm text-dark js-show-qr\"><i class=\"fas fa-qrcode text-success\"></i> QR</button>
                    <a target=\"_blank\" href=\"../print/$doc_code\" class=\"btn btn-sm btn-light border rounded-circle shadow-sm ms-1\"><i class=\"fas fa-print\"></i></a>
                </td>
            </tr>";
        }
    } else {
        $docsRows = '<tr><td colspan="5" class="py-4 text-muted">ยังไม่มีข้อมูลเอกสาร</td></tr>';
    }
?>

<div class="page-content">
    
    <div class="d-flex justify-content-end mb-2">
        <button class="btn btn-sm text-muted" type="button" data-bs-toggle="collapse" data-bs-target="#timeDetails">
            <i class="fas fa-stopwatch me-1"></i>Server: <?php echo number_format($total_time * 1000, 2); ?> ms
        </button>
    </div>
    <div class="collapse mb-3" id="timeDetails">
        <div class="card card-body rounded-4 border-0 shadow-sm bg-light small">
             Queries: <?php echo number_format($time_logs['stats_queries'] * 1000, 2); ?> ms (Stats) + <?php echo number_format($time_logs['recent_docs_query'] * 1000, 2); ?> ms (Recent)
        </div>
    </div>

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-0">Dashboard</h4>
            <small class="text-muted">ภาพรวมสถานะเอกสาร <?php echo $is_admin ? '(ทั้งหมด)' : '(ของคุณ)'; ?></small>
        </div>
        <div class="bg-white px-3 py-2 rounded-pill shadow-sm border">
            <i class="fas fa-file-alt text-primary me-2"></i>
            รวมทั้งหมด: <strong><?php echo number_format($stats['total']); ?></strong> รายการ
        </div>
    </div>

    <?php if (count($status_breakdown) > 0): ?>
        <div class="row g-3 mb-5">
            <?php foreach($status_breakdown as $st): 
                $pct = ($stats['total'] > 0) ? ($st['count'] / $stats['total']) * 100 : 0;
                $color_css = $st['color']; // ใช้ค่าที่แปลงแล้ว
                $icon_class = getStatusIcon($st['category']);
            ?>
            <div class="col-6 col-md-4 col-xl-3">
                <div class="card h-100 shadow-sm status-card-modern">
                    <div class="status-color-strip" style="background-color: <?php echo $color_css; ?>;"></div>
                    
                    <i class="fas <?php echo $icon_class; ?> status-icon-bg" style="color: <?php echo $color_css; ?>;"></i>

                    <div class="card-body p-3 ps-4 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge rounded-pill me-2" style="background-color: #f8f9fa; color: <?php echo $color_css; ?>; border: 1px solid rgba(0,0,0,0.05); padding: 5px 10px;">
                                    <i class="fas <?php echo $icon_class; ?> me-1"></i> <?php echo ucfirst($st['category']); ?>
                                </span>
                            </div>
                            <h6 class="fw-bold text-secondary mb-1 text-truncate" title="<?php echo htmlspecialchars($st['name']); ?>">
                                <?php echo htmlspecialchars($st['name']); ?>
                            </h6>
                        </div>
                        
                        <div class="mt-3">
                            <div class="d-flex align-items-end justify-content-between mb-1">
                                <h2 class="fw-bold mb-0" style="color: #333; line-height: 1;"><?php echo number_format($st['count']); ?></h2>
                                <small class="text-muted fw-bold" style="font-size: 0.75rem;"><?php echo number_format($pct, 1); ?>%</small>
                            </div>
                            <div class="progress" style="height: 4px; background-color: #f1f1f1;">
                                <div class="progress-bar rounded-pill" role="progressbar" 
                                     style="width: <?php echo $pct; ?>%; background-color: <?php echo $color_css; ?>;" 
                                     aria-valuenow="<?php echo $pct; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-light text-center rounded-4 shadow-sm mb-5 text-muted py-5 border-0">
            <div class="mb-3"><i class="fas fa-folder-open fa-3x opacity-25"></i></div>
            <h6>ยังไม่มีข้อมูลสถานะเอกสาร</h6>
            <small>เมื่อมีเอกสารในระบบ สถานะต่างๆ จะปรากฏขึ้นที่นี่</small>
        </div>
    <?php endif; ?>

    <h5 class="mb-3 fw-bold text-secondary">
        <i class="fas fa-history me-2"></i>รายการเอกสารล่าสุด
    </h5>
    <div class="table-responsive rounded-4 shadow-sm border bg-white">
        <table class="table table-hover mb-0 align-middle text-center">
            <thead class="table-light">
                <tr>
                    <th class="py-3 bg-light border-bottom-0">เลขทะเบียน</th>
                    <th class="py-3 bg-light border-bottom-0 text-start">เรื่อง</th>
                    <th class="py-3 bg-light border-bottom-0">วันที่สร้าง</th>
                    <th class="py-3 bg-light border-bottom-0">สถานะ / การเข้าชม</th>
                    <th class="py-3 bg-light border-bottom-0">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php echo $docsRows; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="qrModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 bg-light rounded-top-4">
                <h5 class="modal-title fw-bold text-secondary"><i class="fas fa-qrcode me-2"></i>QR Code</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <h5 id="modalDocTitle" class="fw-bold mb-1 text-primary">...</h5>
                <small id="modalDocCode" class="text-muted d-block mb-3">...</small>
                <div id="qrcode" class="d-flex justify-content-center my-3"></div>
                <p class="small text-muted mt-3">ใช้แอปพลิเคชันสแกนเพื่ออัปเดตสถานะ</p>
            </div>
            <div class="modal-footer border-0 justify-content-center pb-4">
                <a id="btnPrintLink" href="#" class="btn btn-primary rounded-pill px-4"><i class="fas fa-print me-2"></i>พิมพ์ใบปะหน้า</a>
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-0 bg-primary text-white rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="fas fa-file-alt me-2"></i>รายละเอียดเอกสาร</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <div id="modalLoading" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>

                <div id="modalContent" style="display:none;">
                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <h4 id="d_title" class="fw-bold text-primary mb-3">...</h4>
                                <span class="view-count-badge shadow-sm">
                                    <i class="far fa-eye text-primary"></i> ถูกสแกน: <strong id="d_views" class="text-dark">0</strong> ครั้ง
                                </span>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6"><small class="text-muted d-block">เลขทะเบียน</small><strong id="d_code" class="fs-5">...</strong></div>
                                <div class="col-md-6"><small class="text-muted d-block">สถานะปัจจุบัน</small><span id="d_status" class="badge bg-secondary">...</span></div>
                                <div class="col-md-6"><small class="text-muted d-block">ประเภท</small><span id="d_type">...</span></div>
                                <div class="col-md-6"><small class="text-muted d-block">วันที่สร้าง</small><span id="d_date">...</span></div>
                                <div class="col-md-6"><small class="text-muted d-block">ผู้ส่ง</small><span id="d_sender">...</span></div>
                                <div class="col-md-6"><small class="text-muted d-block">ผู้รับ</small><span id="d_receiver">...</span></div>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold text-secondary ps-2 border-start border-4 border-primary mb-3">ประวัติการดำเนินงาน (Timeline)</h6>
                    <div id="d_timeline" class="timeline ms-2"></div>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light"><button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">ปิดหน้าต่าง</button></div>
        </div>
    </div>
</div>