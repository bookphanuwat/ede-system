// --- Configuration ---
const MY_LIFF_ID = "2008591805-LlbR2M99";
// ❌ ไม่ต้องประกาศ site_url แล้ว

let userProfile = { userId: "", displayName: "Guest", pictureUrl: "" };
let currentDocCode = "";
let currentDocWorkflowId = "cat_default"; 

// ฟังก์ชัน Bypass Cache
const originalFetch = window.fetch;
window.fetch = function(url, options) {
    if (url && url.toString().startsWith("https://liffsdk.line-scdn.net/xlt/") && url.toString().endsWith(".json")) {
        url = url + "?ts=" + Math.random();
    }
    return originalFetch(url, options);
};

// --- Main Init ---
async function main() {
    try {
        await liff.init({ liffId: MY_LIFF_ID });
        setupEventListeners(); // เรียกใช้ตัวดักจับปุ่ม

        if (!liff.isLoggedIn()) { liff.login(); return; }
        
        userProfile = await liff.getProfile();
        
        const imgEl = document.getElementById("userImg");
        const nameEl = document.getElementById("userName");
        if (imgEl) imgEl.src = userProfile.pictureUrl;
        if (nameEl) nameEl.innerText = userProfile.displayName;

    } catch (err) {
        console.error("LIFF Init Error:", err);
    }
}

// --- Setup Event Listeners ---
function setupEventListeners() {
    const searchArea = document.getElementById("searchResultArea");
    if (searchArea) {
        searchArea.addEventListener("click", function(e) {
            const card = e.target.closest(".search-card");
            if (card) {
                const code = card.getAttribute("data-code");
                if (code) loadDocDetail(code, false);
            }
        });
    }

    const historyArea = document.getElementById("historyListArea");
    if (historyArea) {
        historyArea.addEventListener("click", function(e) {
            const card = e.target.closest(".history-card");
            if (card) {
                const code = card.getAttribute("data-code");
                if (code) loadDocDetail(code, false);
            }
        });
    }
    
    const scanBtn = document.getElementById("btn-scan");
    if(scanBtn) scanBtn.addEventListener("click", openLineScanner);
    
    const searchBtn = document.getElementById("btn-search");
    if(searchBtn) searchBtn.addEventListener("click", searchDocs);

    const closeDetailBtn = document.getElementById("btn-close-detail");
    if(closeDetailBtn) closeDetailBtn.addEventListener("click", closeDetail);

    const openUpdateBtn = document.getElementById("btn-open-update");
    if(openUpdateBtn) openUpdateBtn.addEventListener("click", openUpdateModal);

    const tabScan = document.getElementById("tab-btn-scan");
    if (tabScan) tabScan.addEventListener("click", () => switchTab('scan'));

    const tabSearch = document.getElementById("tab-btn-search");
    if (tabSearch) tabSearch.addEventListener("click", () => switchTab('search'));

    const tabHistory = document.getElementById("tab-btn-history");
    if (tabHistory) tabHistory.addEventListener("click", () => switchTab('history'));
}

// --- Tabs Logic ---
function switchTab(tabName) {
    document.querySelectorAll(".page-section").forEach(el => el.classList.remove("active"));
    const targetPage = document.getElementById("tab-" + tabName);
    if (targetPage) targetPage.classList.add("active");

    document.querySelectorAll(".nav-item").forEach(el => el.classList.remove("active"));
    if (tabName === 'scan') document.getElementById("tab-btn-scan")?.classList.add("active");
    else if (tabName === 'search') document.getElementById("tab-btn-search")?.classList.add("active");
    else if (tabName === 'history') document.getElementById("tab-btn-history")?.classList.add("active");

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
    const searchInput = document.getElementById("searchInput");
    if (!searchInput) return;
    
    const keyword = searchInput.value;
    const resultArea = document.getElementById("searchResultArea");
    
    if (keyword) {
        resultArea.innerHTML = '<div class="text-center mt-3"><i class="fas fa-spinner fa-spin"></i> กำลังค้นหา...</div>';
        try {
            // ✅ แก้ไข: ตัด ${site_url} ออก ใช้ ./ แทน (Relative Path)
            const res = await fetch(`./api/index.php?dev=search&keyword=${keyword}`);
            const json = await res.json();
            let html = "";
            if (json.data && json.data.length > 0) {
                json.data.forEach(doc => {
                    html += `<div class="search-card" data-code="${doc.document_code}" style="cursor:pointer;">
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
        const resultArea = document.getElementById("historyListArea");
        // ✅ แก้ไข: ตัด ${site_url} ออก ใช้ ./ แทน
        const res = await fetch(`./api/index.php?dev=history&line_id=${userProfile.userId}`);
        const json = await res.json();
        let html = "";
        if (json.data && json.data.length > 0) {
            json.data.forEach(log => {
                html += `<div class="history-card status-${log.status}" data-code="${log.document_code}" style="cursor:pointer;">
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
async function loadDocDetail(code, fromScanner = false) {
    currentDocCode = code;
    if (!fromScanner) Swal.fire({ title: "Loading...", didOpen: () => Swal.showLoading() });
    
    try {
        // ✅ แก้ไข: ตัด ${site_url} ออก ใช้ ./ แทน
        let url = `./api/getdocinfo/${code}/`; 
        
        if (fromScanner) {
            url += "?action=scan";
            url += `&line_id=${encodeURIComponent(userProfile.userId || '')}`;
            url += `&name=${encodeURIComponent(userProfile.displayName || 'Guest')}`;
            url += `&pic=${encodeURIComponent(userProfile.pictureUrl || '')}`;
        }
        
        const res = await fetch(url);
        const json = await res.json();
        
        if (json.error) throw new Error(json.error);
        const doc = json.doc;

        currentDocWorkflowId = doc.workflow_id || 'cat_default';

        if(document.getElementById("detailTitle")) document.getElementById("detailTitle").innerText = doc.title;
        if(document.getElementById("detailCode")) document.getElementById("detailCode").innerText = doc.document_code;
        if(document.getElementById("detailStatus")) document.getElementById("detailStatus").innerHTML = `${doc.current_status} <span class="badge bg-light text-dark ms-2">👁️ ${doc.view_count}</span>`;
        if(document.getElementById("detailReceiver")) document.getElementById("detailReceiver").innerText = doc.receiver_name || "-";

        let timelineHtml = "";
        if (json.logs) {
            json.logs.forEach(log => {
                const actor = log.actor_name_snapshot || log.fullname || "Unknown";
                timelineHtml += `<div class="mb-3 ps-3 border-start border-3 ${log.status === "Received" ? "border-success" : "border-warning"}">
                                    <div class="fw-bold text-dark">${log.status}</div>
                                    <small class="text-muted">${log.action_time}</small><br>
                                    <small>โดย: ${actor}</small>
                                 </div>`;
            });
        }
        if(document.getElementById("detailTimeline")) document.getElementById("detailTimeline").innerHTML = timelineHtml;
        
        Swal.close();
        const overlay = document.getElementById("detailOverlay");
        if(overlay) overlay.style.display = "block";
        
    } catch (err) {
        Swal.fire("Error", "ไม่พบข้อมูลเอกสาร หรือ " + err.message, "error");
    }
}

function closeDetail() { 
    const overlay = document.getElementById("detailOverlay");
    if(overlay) overlay.style.display = "none"; 
}

// --- Update Modal ---
async function openUpdateModal() {
    let statusOptions = "";
    try {
        // ✅ แก้ไข: ตัด ${site_url} ออก ใช้ ./ แทน
        const res = await fetch(`./api/index.php?dev=get-statuses&workflow_id=${currentDocWorkflowId}`);
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
                 <label for="swal-status" class="form-label">เลือกสถานะ:</label>
                 <select id="swal-status" name="status_update" class="form-select mb-3">${statusOptions}</select>
               
                 <label for="swal-receiver" class="form-label">*หมายเหตุ (ถ้ามี):</label>
                 <input id="swal-receiver" name="note_update" class="form-control" placeholder="ระบุหมายเหตุ">
               </div>`,
        focusConfirm: false, showCancelButton: true, confirmButtonText: "บันทึก", confirmButtonColor: "#00C853",
        preConfirm: () => [document.getElementById("swal-status").value, document.getElementById("swal-receiver").value]
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
            device_info: liff.getOS()
        };

        // ✅ แก้ไข: ตัด ${site_url} ออก ใช้ ./ แทน
        await fetch(`./api/index.php?dev=update-status`, { 
            method: "POST", 
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload) 
        }); 
        Swal.fire({ title: "สำเร็จ", text: "บันทึกข้อมูลเรียบร้อยแล้ว", icon: "success", timer: 1500, showConfirmButton: false })
        .then(() => { closeDetail(); });
    }
}

// Start
main();