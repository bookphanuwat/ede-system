<div class="d-flex justify-content-center w-100 content-padding">
    <div class="col-md-5 col-sm-8 col-12">
        
        <div id="tab-scan" class="page-section active">
            <div class="d-flex align-items-center bg-white p-3 rounded-4 shadow-sm mb-4 mt-3">
                <img id="userImg" 
                     src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI1MCIgaGVpZ2h0PSI1MCIgdmlld0JveD0iMCAwIDUwIDUwIj48cmVjdCB3aWR0aD0iNTAiIGhlaWdodD0iNTAiIGZpbGw9IiNlZWVlZWUiLz48L3N2Zz4=" 
                     class="rounded-circle me-3 border border-2 border-success" 
                     width="60" height="60" style="object-fit: cover;">
                <div style="overflow: hidden;">
                    <small class="text-muted" style="font-size: 0.75rem;">ผู้ใช้งาน:</small>
                    <div id="userName" class="fw-bold text-dark text-truncate" style="font-size: 1.1rem; max-width: 100%;">
                        กำลังโหลด...
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 text-center py-5 bg-white">
                <div class="card-body">
                    <div class="mb-4">
                        <div class="bg-light rounded-circle d-inline-flex justify-content-center align-items-center" style="width: 120px; height: 120px;">
                            <i class="fas fa-qrcode fa-5x text-success opacity-75"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold mb-3">สแกนเอกสาร</h4>
                    <p class="text-muted mb-4 small px-3">
                        กดปุ่มด้านล่างเพื่อเปิดกล้อง<br>สำหรับสแกน QR Code เอกสาร
                    </p>
                    
                    <button id="btn-scan" class="btn btn-success btn-lg rounded-pill px-5 py-3 shadow fw-bold">
                        <i class="fas fa-camera me-2"></i> สแกนเลย
                    </button>
                </div>
            </div>
        </div>

        <div id="tab-search" class="page-section">
            <h4 class="fw-bold mb-3 mt-3 px-2">🔍 ค้นหาเอกสาร</h4>
            <div class="input-group mb-4 shadow-sm bg-white rounded-pill overflow-hidden p-1">
                <input type="text" id="searchInput" class="form-control border-0 ps-4" placeholder="ระบุเลขที่เอกสาร...">
                <button id="btn-search" class="btn btn-success px-4 rounded-pill m-1 fw-bold">ค้นหา</button>
            </div>
            <div id="searchResultArea">
                <div class="text-center text-muted mt-5 opacity-50">
                    <i class="fas fa-search fa-3x mb-3"></i>
                    <p>พิมพ์คำค้นหาด้านบน</p>
                </div>
            </div>
        </div>

        <div id="tab-history" class="page-section">
            <h4 class="fw-bold mb-3 mt-3 px-2">🕒 ประวัติของฉัน</h4>
            <div id="historyListArea">
                <div class="text-center py-5">
                    <div class="spinner-border text-success"></div>
                    <p class="text-muted small mt-2">กำลังดึงข้อมูล...</p>
                </div>
            </div>
        </div>

        <div id="detailOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: #f8f9fa; z-index: 1040; overflow-y: auto;">
            <button id="btn-close-detail" class="btn btn-white shadow-sm rounded-circle position-absolute top-0 end-0 m-3" style="width: 40px; height: 40px; z-index: 1050;">
                <i class="fas fa-times text-secondary"></i>
            </button>
            
            <div class="container pt-5 pb-5">
                <h4 class="fw-bold mb-3 ps-2">📄 รายละเอียด</h4>

                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                    <h5 id="detailTitle" class="fw-bold text-primary mb-1 lh-base">...</h5>
                    <div class="mt-2 mb-3">
                        <span id="detailCode" class="badge bg-light text-secondary border fw-normal">...</span>
                    </div>
                    
                    <div class="d-flex align-items-center justify-content-between pt-3 border-top">
                        <div>
                            <small class="text-muted d-block" style="font-size: 0.7rem;">สถานะปัจจุบัน</small>
                            <span id="detailStatus" class="fw-bold text-dark">...</span>
                        </div>
                        <div class="text-end">
                            <small class="text-muted d-block" style="font-size: 0.7rem;">การเปิดดู</small>
                            <span class="badge bg-light text-dark border">
                                <i class="far fa-eye me-1"></i> <span id="detailViews">0</span>
                            </span>
                        </div>
                    </div>
                    <div class="mt-3 pt-2">
                        <small class="text-muted d-block" style="font-size: 0.7rem;">ผู้รับผิดชอบ</small>
                        <span id="detailReceiver" class="text-dark fw-bold">...</span>
                    </div>
                </div>

                <h6 class="fw-bold text-secondary border-bottom pb-2 mb-3 ps-2">Timeline</h6>
                <div id="detailTimeline" class="small ps-2 pb-5"></div>

                <div class="fixed-bottom p-3 bg-white border-top shadow-lg" style="z-index: 1045;">
                    <button id="btn-open-update" class="btn btn-success w-100 rounded-pill py-3 fw-bold shadow">
                        <i class="fas fa-edit me-2"></i> อัปเดตสถานะ
                    </button>
                </div>
                <div style="height: 80px;"></div>
            </div>
        </div>
    </div>

    <div class="bottom-nav">
        <div id="tab-btn-scan" class="nav-item active">
            <i class="fas fa-qrcode"></i><span>สแกน</span>
        </div>
        <div id="tab-btn-search" class="nav-item">
            <i class="fas fa-search"></i><span>ค้นหา</span>
        </div>
        <div id="tab-btn-history" class="nav-item">
            <i class="fas fa-history"></i><span>ประวัติ</span>
        </div>
    </div>
</div>