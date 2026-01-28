<?php
    $page_title   = "รายงาน";
    $header_class = "header-report";
    include 'includes/topbar.php';
    
    $default_start = date('Y-m-01');
    $default_end   = date('Y-m-t');
    
    $is_admin = ( stripos( $_SESSION['role'] ?? '', 'admin' ) !== false );
    $user_dept = $_SESSION['department'] ?? '';
?>

<div class="page-content">
    <h5 class="mb-4 fw-bold text-secondary">**📄 รายงานรายละเอียดเอกสาร (แยกตามแผนก)** <?php echo $is_admin ? '(ทั้งหมด)' : "(เฉพาะที่เกี่ยวข้องกับ $user_dept)"; ?></h5>

    <form id="searchForm" class="row justify-content-center mb-4">
        <div class="col-md-10">
            <div class="d-flex justify-content-center gap-2 mb-3">
                <button type="button" class="btn btn-outline-secondary rounded-pill btn-sm px-3 js-date-filter" data-filter="today">วันนี้</button>
                <button type="button" class="btn btn-outline-secondary rounded-pill btn-sm px-3 js-date-filter" data-filter="month">เดือนนี้</button>
                <button type="button" class="btn btn-outline-secondary rounded-pill btn-sm px-3 js-date-filter" data-filter="year">ปีนี้</button>
                <button type="button" class="btn btn-outline-secondary rounded-pill btn-sm px-3 js-date-filter" data-filter="all">ทั้งหมด</button>
            </div>

            <div class="d-flex align-items-center justify-content-center gap-2 bg-light p-3 rounded-pill shadow-sm border flex-wrap">
                <span class="fw-bold text-secondary"><i class="far fa-calendar-alt"></i> ช่วงเวลา:</span>
                <input type="date" id="start_date" class="form-control rounded-pill border-0 custom-input py-2" style="max-width: 160px;" value="<?php echo $default_start; ?>">
                <span class="text-muted">ถึง</span>
                <input type="date" id="end_date" class="form-control rounded-pill border-0 custom-input py-2" style="max-width: 160px;" value="<?php echo $default_end; ?>">
                
                <button type="button" id="btnSearch" class="btn btn-danger rounded-circle shadow-sm" style="width: 40px; height: 40px;">
                    <i class="fas fa-search"></i>
                </button>
                <button type="button" id="btnReset" class="btn btn-secondary rounded-circle shadow-sm" style="width: 40px; height: 40px;" title="รีเซ็ตค่าเริ่มต้น">
                    <i class="fas fa-sync-alt"></i>
                </button>
                </button>

                <?php if($is_admin): ?>
                    <div class="ms-2 border-start ps-2">
                        <select id="deptFilter" class="form-select rounded-pill custom-input shadow-sm text-secondary" style="min-width: 180px;">
                            <option value="all" selected>-- ทุกแผนก --</option>
                            </select>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </form>

    <div class="table-responsive rounded-4 shadow-sm border mx-auto bg-white">
        <table id="reportTable" class="table table-hover mb-0 align-middle" style="font-size: 0.95rem;">
            <thead class="table-light">
                <tr class="text-secondary text-uppercase small">
                    <th class="py-3 text-center" width="5%">#</th>
                    <th class="py-3" width="15%">วันที่/เวลา</th>
                    <th class="py-3" width="20%">รหัสเอกสาร</th>
                    <th class="py-3">ชื่อเรื่อง</th>
                    <th class="py-3" width="20%">ผู้ทำรายการล่าสุด (Scanner)</th>
                    <th class="py-3 text-center" width="10%">สถานะ</th>
                </tr>
            </thead>
            <tbody id="reportTableBody">
                <tr><td colspan="6" class="py-5 text-center text-muted">กำลังโหลดข้อมูล...</td></tr>
            </tbody>
        </table>
    </div>
    
    <div class="d-flex justify-content-between align-items-center mt-3 mx-auto px-2">
        <div class="text-muted small">
            พบข้อมูลทั้งหมด <span id="totalCount" class="fw-bold text-dark">0</span> รายการ
        </div>
        <div>
            <button id="btnPrint" class="btn btn-outline-dark border-0 fw-bold rounded-pill px-4">
                <i class="fas fa-print me-2"></i>พิมพ์
            </button>
            <button id="btnExport" class="btn btn-success border-0 fw-bold rounded-pill px-4 ms-2" style="background-color: #1D6F42;">
                <i class="fas fa-file-excel me-2"></i>Export Excel
            </button>
        </div>
    </div>
</div>

<script src="../js/report.min.js?v=<?php echo time(); ?>" nonce="<?php echo $nonce; ?>"></script>