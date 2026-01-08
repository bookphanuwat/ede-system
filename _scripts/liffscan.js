var API_BASE = (typeof site_url !== 'undefined') ? site_url : '.';
var MY_LIFF_ID = "2008591805-LlbR2M99";
var userProfile = { userId: "", displayName: "Guest", pictureUrl: "" };
var currentDocCode = "";
var currentDocWorkflowId = "cat_default";
var originalFetch = window.fetch;

// Override fetch เพื่อแก้ปัญหา Caching บน LINE Browser
window.fetch = function(url, options) {
    if (url && url.toString().startsWith("https://liffsdk.line-scdn.net/xlt/") && url.toString().endsWith(".json")) {
        url += "?ts=" + Math.random();
    }
    return originalFetch(url, options);
};

// --- Main Init ---
async function main() {
    try {
        await liff.init({ liffId: MY_LIFF_ID });
        
        if (!liff.isLoggedIn()) {
            liff.login();
            return;
        }
        
        userProfile = await liff.getProfile();
        
        var imgEl = document.getElementById("userImg");
        var nameEl = document.getElementById("userName");
        
        if (imgEl && userProfile.pictureUrl) {
            imgEl.src = userProfile.pictureUrl;
        }
        if (nameEl) {
            nameEl.innerText = userProfile.displayName || "Guest";
        }
        
        if (!liff.isInClient()) {
            Swal.fire("แจ้งเตือน", "กรุณาเปิดผ่านแอปพลิเคชัน LINE บนมือถือเพื่อใช้งานสแกนเนอร์", "warning");
        }

    } catch (err) {
        console.error("LIFF Init Error:", err);
        var nameEl = document.getElementById("userName");
        if (nameEl) nameEl.innerText = "Guest (Error)";
    }
}

// --- Scanner ---
async function openLineScanner() {
    if (liff.isInClient() && liff.getOS() !== "web") {
        try {
            const result = await liff.scanCodeV2();
            if (result.value) loadDocDetail(result.value, true);
        } catch (err) {
            console.error("Scan Error:", err);
        }
    } else {
        Swal.fire({ icon: "error", title: "ไม่รองรับ", text: "ฟีเจอร์นี้ใช้งานได้เฉพาะบนแอป LINE ในมือถือเท่านั้น" });
    }
}

// --- Switch Tab ---
function switchTab(tabName) {
    // ลบ Active Class จากทุกหน้า
    document.querySelectorAll(".page-section").forEach(el => el.classList.remove("active"));
    document.querySelectorAll(".nav-item").forEach(el => el.classList.remove("active"));
    
    // เพิ่ม Active Class ให้หน้าที่เลือก
    var targetPage = document.getElementById("tab-" + tabName);
    if (targetPage) targetPage.classList.add("active");
    
    var targetBtn = document.getElementById("tab-btn-" + tabName);
    if (targetBtn) targetBtn.classList.add("active");
    
    if (tabName === "history") {
        loadHistory();
    }
}

// --- Search (แก้ไข CSP: ลบ onclick, ใช้ data-code แทน) ---
async function searchDocs() {
    const keyword = document.getElementById("searchInput").value;
    if (keyword) {
        const resultArea = document.getElementById("searchResultArea");
        resultArea.innerHTML = '<div class="text-center mt-3"><i class="fas fa-spinner fa-spin"></i> กำลังค้นหา...</div>';
        
        try {
            const res = await fetch(`${API_BASE}/api/index.php?dev=search&keyword=${encodeURIComponent(keyword)}`);
            const json = await res.json();
            
            if (json.data && json.data.length > 0) {
                // ถ้าเจอรายการเดียว ให้โหลดรายละเอียดเลย
                if (json.data.length === 1) {
                    resultArea.innerHTML = ''; 
                    loadDocDetail(json.data[0].document_code, false);
                    return; 
                }

                // ถ้าเจอหลายรายการ
                let html = '<p class="text-muted small ms-2 mb-2">พบ ' + json.data.length + ' รายการ</p>';
                json.data.forEach(doc => {
                    let badgeClass = 'bg-secondary';
                    const st = doc.current_status;
                    if(st === 'Received' || st === 'ได้รับแล้ว' || st === 'อนุมัติ') badgeClass = 'bg-success';
                    else if(st === 'Sent' || st === 'ส่งต่อ' || st === 'รออนุมัติ') badgeClass = 'bg-warning text-dark';
                    else if(st === 'Rejected' || st === 'ไม่อนุมัติ' || st === 'ยกเลิก') badgeClass = 'bg-danger';
                    else if(st === 'Registered' || st === 'ลงทะเบียนใหม่') badgeClass = 'bg-info text-dark';

                    // [แก้ไข] ลบ onclick="..." ออก ใส่ data-code="..." แทน
                    html += `<div class="card shadow-sm border-0 mb-2 clickable-doc-item" data-code="${doc.document_code}" style="cursor:pointer;">
                                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                                    <div style="overflow:hidden; max-width: 65%;">
                                        <div class="fw-bold text-dark text-truncate">${doc.title}</div>
                                        <small class="text-muted"><i class="fas fa-barcode me-1"></i>${doc.document_code}</small>
                                    </div>
                                    <span class="badge rounded-pill ${badgeClass}">${doc.current_status}</span>
                                </div>
                             </div>`;
                });
                resultArea.innerHTML = html;
            } else {
                resultArea.innerHTML = `
                    <div class="text-center text-muted mt-5">
                        <i class="far fa-file-excel fa-2x mb-2"></i>
                        <p>ไม่พบเอกสารที่ค้นหา</p>
                    </div>`;
            }
        } catch (err) {
            console.error(err);
            resultArea.innerHTML = '<p class="text-center text-danger mt-3">เกิดข้อผิดพลาดในการเชื่อมต่อ</p>';
        }
    }
}

// --- History (แก้ไข CSP: ลบ onclick, ใช้ data-code แทน) ---
async function loadHistory() {
    try {
        const res = await fetch(`${API_BASE}/api/index.php?dev=history&line_id=${userProfile.userId}`);
        const json = await res.json();
        let html = "";
        if (json.data && json.data.length > 0) {
            json.data.forEach(log => {
                let statusColor = 'text-secondary';
                if(log.status === 'Received') statusColor = 'text-success';
                else if(log.status === 'Sent') statusColor = 'text-warning';

                // [แก้ไข] ลบ onclick="..." ออก ใส่ data-code="..." แทน
                html += `<div class="card shadow-sm border-0 mb-2 clickable-doc-item" data-code="${log.document_code}" style="cursor:pointer;">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="fw-bold ${statusColor}">${log.status}</span>
                                    <small class="text-muted" style="font-size:0.75rem;">${log.action_time}</small>
                                </div>
                                <small class="d-block text-dark text-truncate">${log.title}</small>
                            </div>
                         </div>`;
            });
        } else {
            html = '<div class="text-center text-muted mt-5"><p>ยังไม่มีประวัติการสแกน</p></div>';
        }
        document.getElementById("historyListArea").innerHTML = html;
    } catch (err) {
        console.error(err);
        document.getElementById("historyListArea").innerHTML = '<p class="text-center text-danger">โหลดประวัติไม่สำเร็จ</p>';
    }
}

// --- Load Detail ---
async function loadDocDetail(code, fromScanner = false) {
    currentDocCode = code;
    if (!fromScanner) Swal.fire({ title: "กำลังโหลด...", allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    
    try {
        let url = `${API_BASE}/api/getdocinfo/${code}/`;
        if (fromScanner) {
            url += "?action=scan";
            url += `&line_id=${encodeURIComponent(userProfile.userId || "")}`;
            url += `&name=${encodeURIComponent(userProfile.displayName || "Guest")}`;
            url += `&pic=${encodeURIComponent(userProfile.pictureUrl || "")}`;
        }
        
        const res = await fetch(url);
        const json = await res.json();
        
        if (json.error || json.status === 'error') throw new Error(json.message || "Error");
        
        const doc = json.doc;
        currentDocWorkflowId = doc.workflow_id || "cat_default";
        
        document.getElementById("detailTitle").innerText = doc.title;
        document.getElementById("detailCode").innerText = doc.document_code;
        
        let badgeClass = 'bg-secondary';
        const st = doc.current_status;
        if(st === 'Received' || st === 'ได้รับแล้ว') badgeClass = 'bg-success';
        else if(st === 'Sent' || st === 'ส่งต่อ') badgeClass = 'bg-warning text-dark';
        else if(st === 'Registered') badgeClass = 'bg-info text-dark';
        
        document.getElementById("detailStatus").innerHTML = `<span class="badge ${badgeClass}">${doc.current_status}</span>`;
        document.getElementById("detailViews").innerText = doc.view_count;
        document.getElementById("detailReceiver").innerText = doc.receiver_name || "-";
        
        let timelineHtml = "";
        if (json.logs && json.logs.length > 0) {
            json.logs.forEach((log, index) => {
                const actor = log.actor_name_snapshot || log.fullname || "Unknown";
                const isLatest = index === 0;
                const circleClass = isLatest ? 'bg-success' : 'bg-secondary';
                
                timelineHtml += `
                    <div class="d-flex mb-3 position-relative">
                        <div class="me-3 d-flex flex-column align-items-center" style="width: 20px;">
                             <div class="rounded-circle ${circleClass} border border-white shadow-sm" style="width: 12px; height: 12px; z-index: 2;"></div>
                             ${index !== json.logs.length - 1 ? '<div class="flex-grow-1 bg-light" style="width: 2px;"></div>' : ''}
                        </div>
                        <div class="pb-3 flex-grow-1 border-bottom border-light">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-dark" style="font-size: 0.9rem;">${log.status}</span>
                                <small class="text-muted" style="font-size: 0.7rem;">${log.action_time}</small>
                            </div>
                            <div class="d-flex align-items-center">
                                <small class="text-secondary me-1">โดย:</small>
                                <small class="fw-bold text-dark">${actor}</small>
                            </div>
                            ${log.location_note ? `<small class="text-muted d-block mt-1 fst-italic"><i class="fas fa-comment-alt me-1"></i>${log.location_note}</small>` : ''}
                        </div>
                    </div>`;
            });
        } else {
            timelineHtml = '<p class="text-center text-muted py-3">ยังไม่มีประวัติ</p>';
        }
        document.getElementById("detailTimeline").innerHTML = timelineHtml;
        
        Swal.close();
        document.getElementById("detailOverlay").style.display = "block";
        document.body.style.overflow = "hidden"; 
        
    } catch (err) {
        Swal.fire("ไม่พบข้อมูล", "ไม่พบเอกสาร หรือ รหัสไม่ถูกต้อง", "error");
    }
}

function closeDetail() {
    document.getElementById("detailOverlay").style.display = "none";
    document.body.style.overflow = "auto";
}

// --- Update Modal ---
async function openUpdateModal() {
    let statusOptions = "";
    try {
        const res = await fetch(`${API_BASE}/api/index.php?dev=get-statuses&workflow_id=${currentDocWorkflowId}`);
        const json = await res.json();
        
        if (json.status === "success" && json.data.length > 0) {
            let currentCategory = "";
            json.data.forEach(s => {
                if (s.category !== currentCategory) {
                    if (currentCategory !== "") statusOptions += "</optgroup>";
                    statusOptions += `<optgroup label="${s.category}">`;
                    currentCategory = s.category;
                }
                statusOptions += `<option value="${s.status_name}">${s.status_name}</option>`;
            });
            if (currentCategory !== "") statusOptions += "</optgroup>";
        } else {
            statusOptions = '<option value="Received">ได้รับแล้ว</option><option value="Sent">ส่งต่อ</option>';
        }
    } catch (e) {
        console.error("Fetch Status Error:", e);
        statusOptions = '<option value="Received">ได้รับแล้ว</option><option value="Sent">ส่งต่อ</option>';
    }

    const { value: formValues } = await Swal.fire({
        title: "อัปเดตสถานะ",
        html: `<div class="text-start">
                 <label class="form-label text-muted small">สถานะใหม่</label>
                 <select id="swal-status" class="form-select mb-3 shadow-none">${statusOptions}</select>
                 <label class="form-label text-muted small">หมายเหตุ / ส่งต่อถึง (ถ้ามี)</label>
                 <input id="swal-receiver" class="form-control shadow-none" placeholder="ระบุข้อความ...">
               </div>`,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: "บันทึกข้อมูล",
        cancelButtonText: "ยกเลิก",
        confirmButtonColor: "#198754",
        reverseButtons: true,
        preConfirm: () => {
            return [
                document.getElementById("swal-status").value,
                document.getElementById("swal-receiver").value
            ];
        }
    });

    if (formValues) {
        const [status, receiver] = formValues;
        const payload = {
            doc_code: currentDocCode,
            status: status,
            receiver_name: receiver,
            line_user_id: userProfile.userId,
            display_name: userProfile.displayName,
            picture_url: userProfile.pictureUrl,
            device_info: liff.getOS() || "Web"
        };

        Swal.fire({ title: 'กำลังบันทึก...', didOpen: () => Swal.showLoading() });

        try {
            const res = await fetch(`${API_BASE}/api/index.php?dev=update-status`, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payload)
            });
            const result = await res.json();
            
            if(result.status === 'success'){
                Swal.fire({
                    title: "เรียบร้อย",
                    text: "อัปเดตสถานะสำเร็จ",
                    icon: "success",
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    loadDocDetail(currentDocCode, false);
                });
            } else {
                throw new Error(result.message);
            }
        } catch(err) {
            Swal.fire("เกิดข้อผิดพลาด", err.message, "error");
        }
    }
}

// --- Event Listeners & Delegation (ส่วนสำคัญ: จัดการคลิกแบบรวมศูนย์) ---
document.addEventListener("DOMContentLoaded", function() {
    var scanBtn = document.getElementById("btn-scan");
    if (scanBtn) scanBtn.addEventListener("click", openLineScanner);
    
    var searchBtn = document.getElementById("btn-search");
    if (searchBtn) searchBtn.addEventListener("click", searchDocs);

    var searchInput = document.getElementById("searchInput");
    if (searchInput) {
        searchInput.addEventListener("keypress", function(event) {
            if (event.key === "Enter") {
                event.preventDefault();
                searchDocs();
            }
        });
    }
    
    var closeDetailBtn = document.getElementById("btn-close-detail");
    if (closeDetailBtn) closeDetailBtn.addEventListener("click", closeDetail);
    
    var openUpdateBtn = document.getElementById("btn-open-update");
    if (openUpdateBtn) openUpdateBtn.addEventListener("click", openUpdateModal);
    
    // Tab Navigation
    var tabScan = document.getElementById("tab-btn-scan");
    if (tabScan) tabScan.addEventListener("click", function() { switchTab("scan"); });
    
    var tabSearch = document.getElementById("tab-btn-search");
    if (tabSearch) tabSearch.addEventListener("click", function() { switchTab("search"); });
    
    var tabHistory = document.getElementById("tab-btn-history");
    if (tabHistory) tabHistory.addEventListener("click", function() { switchTab("history"); });

    // [เพิ่มใหม่] Event Delegation สำหรับรายการ Search และ History
    // แทนที่จะใส่ onclick ใน HTML เรามาดักคลิกที่ container แม่แทน
    
    function handleDocItemClick(event) {
        // หา element ที่มี class 'clickable-doc-item' ที่ถูกคลิก (หรือ parent ของมัน)
        const target = event.target.closest('.clickable-doc-item');
        if (target && target.dataset.code) {
            loadDocDetail(target.dataset.code, false);
        }
    }

    const searchArea = document.getElementById("searchResultArea");
    if (searchArea) searchArea.addEventListener("click", handleDocItemClick);

    const historyArea = document.getElementById("historyListArea");
    if (historyArea) historyArea.addEventListener("click", handleDocItemClick);
    
    main();
});