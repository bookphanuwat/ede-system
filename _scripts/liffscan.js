// --- Configuration ---
// ตรวจสอบตัวแปร site_url ว่ามีอยู่จริงไหม ถ้าไม่มีให้ใช้ relative path '.'
var API_BASE = (typeof site_url !== 'undefined') ? site_url : '.';
var MY_LIFF_ID = "2008591805-LlbR2M99";

var userProfile = { userId: "", displayName: "Guest", pictureUrl: "" };
var currentDocCode = "";
var currentDocWorkflowId = "cat_default"; 

// --- Main Init ---
async function main() {
    try {
        await liff.init({ liffId: MY_LIFF_ID });
        
        // ถ้ายังไม่ได้ Login
        if (!liff.isLoggedIn()) { 
            liff.login(); 
            return; 
        }
        
        userProfile = await liff.getProfile();
        
        // อัปเดต UI โปรไฟล์
        var imgEl = document.getElementById("userImg");
        var nameEl = document.getElementById("userName");
        
        if (imgEl && userProfile.pictureUrl) imgEl.src = userProfile.pictureUrl;
        if (nameEl && userProfile.displayName) nameEl.innerText = userProfile.displayName;

    } catch (err) {
        console.error("LIFF Init Error:", err);
    }
}

// --- Setup Event Listeners ---
function setupEventListeners() {
    // 1. Search Result Click
    var searchArea = document.getElementById("searchResultArea");
    if (searchArea) {
        searchArea.addEventListener("click", function(e) {
            var card = e.target.closest(".search-card");
            if (card) {
                var code = card.getAttribute("data-code");
                if (code) loadDocDetail(code, false);
            }
        });
    }

    // 2. History Click
    var historyArea = document.getElementById("historyListArea");
    if (historyArea) {
        historyArea.addEventListener("click", function(e) {
            var card = e.target.closest(".history-card");
            if (card) {
                var code = card.getAttribute("data-code");
                if (code) loadDocDetail(code, false);
            }
        });
    }
    
    // 3. Buttons
    var scanBtn = document.getElementById("btn-scan");
    if(scanBtn) scanBtn.addEventListener("click", openLineScanner);
    
    var searchBtn = document.getElementById("btn-search");
    if(searchBtn) searchBtn.addEventListener("click", searchDocs);

    var closeDetailBtn = document.getElementById("btn-close-detail");
    if(closeDetailBtn) closeDetailBtn.addEventListener("click", closeDetail);

    var openUpdateBtn = document.getElementById("btn-open-update");
    if(openUpdateBtn) openUpdateBtn.addEventListener("click", openUpdateModal);

    // 4. Tabs
    var tabScan = document.getElementById("tab-btn-scan");
    if (tabScan) tabScan.addEventListener("click", function() { switchTab('scan'); });

    var tabSearch = document.getElementById("tab-btn-search");
    if (tabSearch) tabSearch.addEventListener("click", function() { switchTab('search'); });

    var tabHistory = document.getElementById("tab-btn-history");
    if (tabHistory) tabHistory.addEventListener("click", function() { switchTab('history'); });
}

// --- Tabs Logic ---
function switchTab(tabName) {
    // ซ่อนทุกหน้า
    var pages = document.querySelectorAll(".page-section");
    for (var i = 0; i < pages.length; i++) {
        pages[i].classList.remove("active");
    }

    // แสดงหน้าที่เลือก
    var targetPage = document.getElementById("tab-" + tabName);
    if (targetPage) targetPage.classList.add("active");

    // รีเซ็ตปุ่มเมนู
    var navItems = document.querySelectorAll(".nav-item");
    for (var j = 0; j < navItems.length; j++) {
        navItems[j].classList.remove("active");
    }
    
    // ตั้งค่า Active ให้ปุ่มเมนู (แก้ไข: ไม่ใช้ ?. แล้ว)
    var activeBtn = null;
    if (tabName === 'scan') activeBtn = document.getElementById("tab-btn-scan");
    else if (tabName === 'search') activeBtn = document.getElementById("tab-btn-search");
    else if (tabName === 'history') activeBtn = document.getElementById("tab-btn-history");

    if (activeBtn) activeBtn.classList.add("active");

    // ถ้ากด Tab History ให้โหลดข้อมูล
    if (tabName === "history") {
        loadHistory();
    }
}

// --- Scanner ---
async function openLineScanner() {
    if (liff.isInClient() && liff.getOS() !== "web") {
        try {
            const result = await liff.scanCodeV2();
            if (result.value) loadDocDetail(result.value, true);
        } catch (err) { console.error("Scan Error:", err); }
    } else {
        Swal.fire({ icon: "error", title: "ไม่รองรับ", text: "ฟีเจอร์นี้ใช้งานได้เฉพาะบนแอป LINE ในมือถือเท่านั้น" });
    }
}

// --- Search ---
async function searchDocs() {
    var searchInput = document.getElementById("searchInput");
    if (!searchInput) return;
    
    var keyword = searchInput.value;
    var resultArea = document.getElementById("searchResultArea");
    
    if (keyword) {
        resultArea.innerHTML = '<div class="text-center mt-3"><i class="fas fa-spinner fa-spin"></i> กำลังค้นหา...</div>';
        try {
            // ใช้ API_BASE
            const res = await fetch(API_BASE + '/api/index.php?dev=search&keyword=' + encodeURIComponent(keyword));
            const json = await res.json();
            var html = "";
            if (json.data && json.data.length > 0) {
                json.data.forEach(function(doc) {
                    html += `<div class="search-card" data-code="${doc.document_code}" style="cursor:pointer; padding:10px; border-bottom:1px solid #eee;">
                                <div class="fw-bold">${doc.title}</div>
                                <small class="text-muted">${doc.document_code}</small>
                                <span class="badge bg-light text-dark float-end">${doc.current_status}</span>
                             </div>`;
                });
            } else { html = '<p class="text-center text-muted mt-3">ไม่พบข้อมูล</p>'; }
            resultArea.innerHTML = html;
        } catch (err) { 
            console.error(err); 
            resultArea.innerHTML = '<p class="text-center text-danger">เกิดข้อผิดพลาด</p>'; 
        }
    }
}

// --- History ---
async function loadHistory() {
    try {
        var resultArea = document.getElementById("historyListArea");
        const res = await fetch(API_BASE + '/api/index.php?dev=history&line_id=' + userProfile.userId);
        const json = await res.json();
        var html = "";
        if (json.data && json.data.length > 0) {
            json.data.forEach(function(log) {
                html += `<div class="history-card status-${log.status}" data-code="${log.document_code}" style="cursor:pointer; padding:10px; border-bottom:1px solid #eee;">
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold text-dark">${log.status}</span>
                                <small class="text-muted">${log.action_time}</small>
                            </div>
                            <small class="d-block text-truncate">${log.title}</small>
                         </div>`;
            });
        } else { html = '<p class="text-center text-muted mt-5">ยังไม่มีประวัติการสแกน</p>'; }
        resultArea.innerHTML = html;
    } catch (err) { console.error(err); }
}

// --- Load Detail ---
async function loadDocDetail(code, fromScanner) {
    // กำหนดค่า default สำหรับ fromScanner
    if (typeof fromScanner === 'undefined') fromScanner = false;

    currentDocCode = code;
    if (!fromScanner) Swal.fire({ title: "Loading...", didOpen: function() { Swal.showLoading() } });
    
    try {
        var url = API_BASE + '/api/getdocinfo/' + code + '/'; 
        
        if (fromScanner) {
            url += "?action=scan";
            url += "&line_id=" + encodeURIComponent(userProfile.userId || '');
            url += "&name=" + encodeURIComponent(userProfile.displayName || 'Guest');
            url += "&pic=" + encodeURIComponent(userProfile.pictureUrl || '');
        }
        
        const res = await fetch(url);
        const json = await res.json();
        
        if (json.error) throw new Error(json.error);
        const doc = json.doc;

        currentDocWorkflowId = doc.workflow_id || 'cat_default';

        if(document.getElementById("detailTitle")) document.getElementById("detailTitle").innerText = doc.title;
        if(document.getElementById("detailCode")) document.getElementById("detailCode").innerText = doc.document_code;
        if(document.getElementById("detailStatus")) document.getElementById("detailStatus").innerHTML = doc.current_status;
        if(document.getElementById("detailViews")) document.getElementById("detailViews").innerText = doc.view_count;
        var receiverName = doc.receiver_name || "-";
        if(document.getElementById("detailReceiver")) document.getElementById("detailReceiver").innerText = receiverName;

        var timelineHtml = "";
        if (json.logs) {
            json.logs.forEach(function(log) {
                var actor = log.actor_name_snapshot || log.fullname || "Unknown";
                var borderClass = (log.status === "Received") ? "border-success" : "border-warning";
                timelineHtml += `<div class="mb-3 ps-3 border-start border-3 ${borderClass}">
                                    <div class="fw-bold text-dark">${log.status}</div>
                                    <small class="text-muted">${log.action_time}</small><br>
                                    <small>โดย: ${actor}</small>
                                 </div>`;
            });
        }
        if(document.getElementById("detailTimeline")) document.getElementById("detailTimeline").innerHTML = timelineHtml;
        
        Swal.close();
        var overlay = document.getElementById("detailOverlay");
        if(overlay) overlay.style.display = "block";
        
    } catch (err) {
        Swal.fire("Error", "ไม่พบข้อมูลเอกสาร หรือ " + err.message, "error");
    }
}

function closeDetail() { 
    var overlay = document.getElementById("detailOverlay");
    if(overlay) overlay.style.display = "none"; 
}

// --- Update Modal ---
async function openUpdateModal() {
    var statusOptions = "";
    try {
        const res = await fetch(API_BASE + '/api/index.php?dev=get-statuses&workflow_id=' + currentDocWorkflowId);
        const json = await res.json();
        
        if (json.status === "success" && json.data.length > 0) {
            var currentCategory = "";
            json.data.forEach(function(s) {
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

    const swalResult = await Swal.fire({
        title: "อัปเดตสถานะ",
        html: `<div class="text-start">
                 <label for="swal-status" class="form-label">เลือกสถานะ:</label>
                 <select id="swal-status" name="status_update" class="form-select mb-3">${statusOptions}</select>
               
                 <label for="swal-receiver" class="form-label">*หมายเหตุ (ถ้ามี):</label>
                 <input id="swal-receiver" name="note_update" class="form-control" placeholder="ระบุหมายเหตุ">
               </div>`,
        focusConfirm: false, 
        showCancelButton: true, 
        confirmButtonText: "บันทึก", 
        confirmButtonColor: "#00C853",
        preConfirm: function() {
            return [
                document.getElementById("swal-status").value,
                document.getElementById("swal-receiver").value
            ];
        }
    });

    if (swalResult.value) {
        var status = swalResult.value[0];
        var receiver = swalResult.value[1];
        
        var payload = {
            doc_code: currentDocCode,
            status: status,
            receiver_name: receiver,
            line_user_id: userProfile.userId,
            display_name: userProfile.displayName,
            picture_url: userProfile.pictureUrl,
            device_info: liff.getOS()
        };

        await fetch(API_BASE + '/api/index.php?dev=update-status', { 
            method: "POST", 
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload) 
        }); 
        Swal.fire({ title: "สำเร็จ", text: "บันทึกข้อมูลเรียบร้อยแล้ว", icon: "success", timer: 1500, showConfirmButton: false })
        .then(function() { closeDetail(); });
    }
}

// ✅ เริ่มต้นการทำงาน (ปลอดภัยที่สุด)
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        setupEventListeners();
        main();
    });
} else {
    setupEventListeners();
    main();
}