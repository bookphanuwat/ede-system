// =========================================================
// [วางโค้ดนี้ไว้บนสุด บรรทัดแรกสุดของไฟล์เลยครับ]
const originalFetch = window.fetch;
window.fetch = function (url, options) {
    // ดักจับการเรียก config ของ LIFF
    if (url.toString().startsWith('https://liffsdk.line-scdn.net/xlt/') && url.toString().endsWith('.json')) {
        // เติมเลขสุ่ม ?ts=... เพื่อบังคับให้โหลดใหม่ (แก้ Cache)
        console.log('Bypassing LIFF Cache:', url);
        url = url + '?ts=' + Math.random();
    }
    return originalFetch(url, options);
};
// =========================================================

const MY_LIFF_ID = "2008591805-LlbR2M99"; // LIFF ID เดิมของคุณ

// ตัวแปร global
let userProfile = { userId: '', displayName: 'Guest', pictureUrl: '' };
// ... (โค้ดส่วนที่เหลือเหมือนเดิม)
        let currentDocCode = '';
        let currentDocCreator = 0; 
        
        // --- Init ---
        async function main() {
            try {
                await liff.init({ liffId: MY_LIFF_ID });
                
                // ถ้ายังไม่ Login ให้ Login ก่อน
                if (!liff.isLoggedIn()) { 
                    liff.login(); 
                    return; 
                }

                // ดึงข้อมูลโปรไฟล์
                userProfile = await liff.getProfile();
                document.getElementById('userImg').src = userProfile.pictureUrl;
                document.getElementById('userName').innerText = userProfile.displayName;

                // เช็คว่าเปิดใน LINE App หรือไม่ (ถ้าเปิดในคอมจะแจ้งเตือน)
                if (!liff.isInClient()) {
                   Swal.fire('แจ้งเตือน', 'กรุณาเปิดผ่านแอปพลิเคชัน LINE บนมือถือเพื่อใช้งานสแกนเนอร์', 'warning');
                }

            } catch (err) {
                console.error('LIFF Init Error:', err);
                Swal.fire('Error', 'LIFF Init Failed: ' + err, 'error');
            }
        }

        // --- LINE Scanner Logic (ใหม่) ---
        async function openLineScanner() {
            // เช็คว่าเป็น LINE App บนมือถือหรือไม่
            if (liff.isInClient() && liff.getOS() !== 'web') {
                try {
                    const result = await liff.scanCodeV2();
                    
                    if (result.value) {
                        // เมื่อสแกนสำเร็จ ได้ค่า QR Code มา
                        loadDocDetail(result.value, true);
                    }
                } catch (err) {
                    console.error("Scan Error:", err);
                    // กรณี User กดยกเลิก หรือสแกนไม่ได้ ไม่ต้องทำอะไร หรือแจ้งเตือนก็ได้
                }
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'ไม่รองรับ',
                    text: 'ฟีเจอร์นี้ใช้งานได้เฉพาะบนแอป LINE ในมือถือเท่านั้น'
                });
            }
        }

        // --- Navigation ---
        function switchTab(tabName) {
            // จัดการ Class active
            document.querySelectorAll('.page-section').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
            
            document.getElementById('tab-' + tabName).classList.add('active');
            
            // หาปุ่ม Nav ที่กดมา แล้วใส่ active (แก้ error กรณีไม่มี event)
            if(event && event.currentTarget) {
                event.currentTarget.classList.add('active');
            }

            // Logic ของแต่ละแท็บ
            if(tabName === 'scan') {
                // เรียก Scan ของ LINE ทันที
                openLineScanner();
            } else if(tabName === 'history') {
                loadHistory();
            }
        }

        // --- API Functions ---
        async function searchDocs() {
            const keyword = document.getElementById('searchInput').value;
            if(!keyword) return;
            
            // เพิ่ม Loading
            document.getElementById('searchResultArea').innerHTML = '<div class="text-center mt-3"><i class="fas fa-spinner fa-spin"></i> กำลังค้นหา...</div>';

            try {
                const res = await fetch(`api/liff_api.php?action=search&keyword=${keyword}`);
                const json = await res.json();

                let html = '';
                if(json.data && json.data.length > 0) {
                    json.data.forEach(doc => {
                        html += `<div class="search-card" onclick="loadDocDetail('${doc.document_code}', false)">
                                    <div class="fw-bold">${doc.title}</div>
                                    <small class="text-muted">${doc.document_code}</small>
                                    <span class="badge bg-light text-dark float-end">${doc.current_status}</span>
                                 </div>`;
                    });
                } else { html = '<p class="text-center text-muted mt-3">ไม่พบข้อมูล</p>'; }
                document.getElementById('searchResultArea').innerHTML = html;
            } catch (err) {
                console.error(err);
                document.getElementById('searchResultArea').innerHTML = '<p class="text-center text-danger">เกิดข้อผิดพลาด</p>';
            }
        }

        async function loadHistory() {
            try {
                const res = await fetch(`api/liff_api.php?action=history&line_id=${userProfile.userId}`);
                const json = await res.json();

                let html = '';
                if(json.data && json.data.length > 0) {
                    json.data.forEach(log => {
                        html += `<div class="history-card status-${log.status}" onclick="loadDocDetail('${log.document_code}', false)">
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-bold text-dark">${log.status}</span>
                                        <small class="text-muted">${log.action_time}</small>
                                    </div>
                                    <small class="d-block text-truncate">${log.title}</small>
                                 </div>`;
                    });
                } else { html = '<p class="text-center text-muted mt-5">ยังไม่มีประวัติการสแกน</p>'; }
                document.getElementById('historyListArea').innerHTML = html;
            } catch (err) {
                console.error(err);
            }
        }

        async function loadDocDetail(code, fromScanner = false) {
            currentDocCode = code;
            if(!fromScanner) Swal.fire({ title: 'Loading...', didOpen: () => Swal.showLoading() });

            try {
                let url = `${site_url}/api/getdocinfo/${code}/`; // ตรวจสอบ site_url ว่าประกาศไว้ในหน้า index.php แล้ว
                if (fromScanner) url += '?action=scan';

                const res = await fetch(url);
                const json = await res.json();

                if(json.error) throw new Error(json.error);

                const doc = json.doc;
                currentDocCreator = doc.created_by;

                document.getElementById('detailTitle').innerText = doc.title;
                document.getElementById('detailCode').innerText = doc.document_code;
                document.getElementById('detailStatus').innerHTML = `${doc.current_status} <span class="badge bg-light text-dark ms-2">👁️ ${doc.view_count}</span>`;
                document.getElementById('detailReceiver').innerText = doc.receiver_name || '-';

                let timelineHtml = '';
                if(json.logs) {
                    json.logs.forEach(log => {
                        const actor = log.actor_name_snapshot || log.fullname || 'Unknown';
                        timelineHtml += `<div class="mb-3 ps-3 border-start border-3 ${log.status === 'Received' ? 'border-success' : 'border-warning'}">
                                            <div class="fw-bold text-dark">${log.status}</div>
                                            <small class="text-muted">${log.action_time}</small><br>
                                            <small>โดย: ${actor}</small>
                                         </div>`;
                    });
                }
                document.getElementById('detailTimeline').innerHTML = timelineHtml;

                Swal.close();
                document.getElementById('detailOverlay').style.display = 'block';

            } catch (err) {
                Swal.fire('Error', 'ไม่พบข้อมูลเอกสาร หรือ ' + err.message, 'error');
            }
        }

        function closeDetail() {
            document.getElementById('detailOverlay').style.display = 'none';
            // ถ้าอยู่ในหน้า Scan แล้วปิด Popup ให้เรียกสแกนใหม่ (Optional)
            // if(document.getElementById('tab-scan').classList.contains('active')) {
            //     openLineScanner();
            // }
        }

        // --- Status Update Modal ---
        async function openUpdateModal() {
            let statusOptions = '';
            try {
                const res = await fetch(`api/liff_api.php?action=get_statuses&creator_id=${currentDocCreator}`);
                const json = await res.json();

                if(json.status === 'success' && json.data.length > 0) {
                    let currentCategory = '';
                    json.data.forEach(s => {
                        if (s.category !== currentCategory) {
                            if (currentCategory !== '') statusOptions += '</optgroup>';
                            statusOptions += `<optgroup label="${s.category}">`;
                            currentCategory = s.category;
                        }
                        statusOptions += `<option value="${s.status_name}">${s.status_name}</option>`;
                    });
                    if (currentCategory !== '') statusOptions += '</optgroup>';

                } else {
                    statusOptions = '<option value="Received">ได้รับแล้ว</option><option value="Sent">ส่งต่อ</option>';
                }
            } catch (e) {
                console.error("Fetch Status Error:", e);
                statusOptions = '<option value="Received">ได้รับแล้ว</option><option value="Sent">ส่งต่อ</option>';
            }

            const { value: formValues } = await Swal.fire({
                title: 'อัปเดตสถานะ',
                html:
                    `<label class="form-label text-start w-100">เลือกสถานะ:</label>
                     <select id="swal-status" class="form-select mb-3">${statusOptions}</select>` +
                    `<label class="form-label text-start w-100">ส่งต่อให้ (ถ้ามี):</label>
                     <input id="swal-receiver" class="form-control" placeholder="ระบุชื่อผู้รับคนถัดไป">`,
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: 'บันทึก',
                confirmButtonColor: '#00C853',
                preConfirm: () => {
                    return [
                        document.getElementById('swal-status').value,
                        document.getElementById('swal-receiver').value
                    ]
                }
            });

            if (formValues) {
                const [status, receiver] = formValues;

                const formData = new FormData();
                formData.append('doc_code', currentDocCode);
                formData.append('status', status);
                formData.append('receiver_name', receiver);
                formData.append('line_user_id', userProfile.userId);
                formData.append('display_name', userProfile.displayName);
                formData.append('picture_url', userProfile.pictureUrl);
                formData.append('device_info', liff.getOS());

                await fetch('api/update_status.php', { method: 'POST', body: formData });
                Swal.fire({
                    title: 'สำเร็จ',
                    text: 'บันทึกข้อมูลเรียบร้อยแล้ว',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    closeDetail();
                });
            }
        }

        // เริ่มต้นการทำงาน
        main();