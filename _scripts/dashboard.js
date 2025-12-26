// dashboard.js - JavaScript สำหรับหน้า Dashboard

// คำนวณและแสดงเวลาโหลด (รวม Server + Client)
window.addEventListener('load', function() {
    const navTiming = performance.getEntriesByType('navigation')[0];
    const serverTimeMs = (typeof SERVER_TIME_MS !== 'undefined') ? SERVER_TIME_MS : 0;
    const clientRenderTime = navTiming ? navTiming.domInteractive - navTiming.fetchStart : 0;
    const totalLoadTime = (performance.now() / 1000).toFixed(3);

    const loadTimeElement = document.getElementById('loadTime');
    if (loadTimeElement) {
        loadTimeElement.textContent = totalLoadTime;
    }
});

// [แก้ไข 1] รับค่า triggerBtn เพิ่มเข้ามา
function showQRModal(docCode, docTitle, triggerBtn) {
    document.getElementById('modalDocCode').innerText = "รหัส: " + docCode;
    document.getElementById('modalDocTitle').innerText = docTitle;
    document.getElementById('btnPrintLink').href = '../print/' + docCode;
    document.getElementById('btnPrintLink').target = '_blank';

    const qrContainer = document.getElementById("qrcode");
    qrContainer.innerHTML = "";

    // สร้าง QR Code
    if (typeof QRCode !== 'undefined') {
        new QRCode(qrContainer, {
            text: docCode,
            width: 180,
            height: 180
        });
    }

    // [แก้ไข 2] ใช้ getOrCreateInstance และส่ง triggerBtn ไปที่ .show()
    // เพื่อให้ Bootstrap รู้ว่าจะคืน Focus ไปที่ปุ่มไหนตอนปิด
    const modalEl = document.getElementById('qrModal');
    const qrModal = bootstrap.Modal.getOrCreateInstance(modalEl);
    qrModal.show(triggerBtn); 
}

// ... (ส่วนบนของไฟล์ ฟังก์ชัน showQRModal คงเดิม) ...

// [แก้ไข 1] เพิ่ม triggerBtn ในพารามิเตอร์
async function openDetailModal(code, triggerBtn) {
    // ใช้ getOrCreateInstance แทน new Modal เพื่อป้องกันการสร้างซ้ำ
    const modalEl = document.getElementById('detailModal');
    const detailModal = bootstrap.Modal.getOrCreateInstance(modalEl);
    
    // ส่ง triggerBtn ไปที่ .show() เพื่อจัดการ Focus ตอนปิด
    detailModal.show(triggerBtn);

    document.getElementById('modalLoading').style.display = 'block';
    document.getElementById('modalContent').style.display = 'none';

    try {
        const res = await fetch(`${site_url}/api/getdocinfo/${code}/`);
        const data = await res.json();

        if(data.error) throw new Error(data.error);

        const doc = data.doc;

        // แสดงข้อมูลเอกสาร
        document.getElementById('d_title').innerText = doc.title;
        document.getElementById('d_code').innerText = doc.document_code;
        document.getElementById('d_status').innerText = doc.current_status;
        document.getElementById('d_type').innerText = doc.type_name || '-';
        document.getElementById('d_date').innerText = doc.created_at;
        document.getElementById('d_sender').innerText = doc.sender_name;
        document.getElementById('d_receiver').innerText = doc.receiver_name;

        // ใส่ตัวเลขยอดวิวลงไป
        const viewsElement = document.getElementById('d_views');
        if (viewsElement) {
            viewsElement.innerText = doc.view_count || 0;
        }

        // สร้าง Timeline
        let html = '';
        if(data.logs && data.logs.length > 0) {
            data.logs.forEach((log, index) => {
                const activeClass = index === 0 ? 'active' : '';
                const actor = log.actor_name_snapshot || log.fullname || 'Unknown';
                const actorPic = log.actor_pic_snapshot ?
                    `<img src="${log.actor_pic_snapshot}" class="rounded-circle me-1" width="20">` :
                    '<i class="fas fa-user-circle me-1"></i>';

                html += `
                    <div class="timeline-item">
                        <div class="timeline-dot ${activeClass}"></div>
                        <div class="ps-4">
                            <div class="d-flex justify-content-between">
                                <strong class="text-dark">${log.status}</strong>
                                <small class="text-muted">${log.action_time}</small>
                            </div>
                            <small class="text-secondary d-flex align-items-center mt-1">
                                โดย: ${actorPic} ${actor}
                            </small>
                            ${log.location_note ? `<br><small class="text-danger"><i class="fas fa-map-marker-alt"></i> ${log.location_note}</small>` : ''}
                        </div>
                    </div>`;
            });
        } else {
            html = '<p class="text-muted ms-4">ยังไม่มีประวัติ</p>';
        }

        document.getElementById('d_timeline').innerHTML = html;

        // แสดงเนื้อหา
        document.getElementById('modalLoading').style.display = 'none';
        document.getElementById('modalContent').style.display = 'block';

    } catch (err) {
        alert("ไม่สามารถโหลดข้อมูลได้: " + err.message);
    }
}

// [แก้ไข 2] อัปเดต Event Listener ด้านล่าง
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. ดักจับคลิกปุ่มเปิด QR Code (โค้ดเดิมที่แก้ไปแล้ว)
    document.body.addEventListener('click', function(e) {
        const btn = e.target.closest('.js-show-qr');
        if (btn) {
            e.preventDefault();
            const docCode = btn.getAttribute('data-code');
            const docTitle = btn.getAttribute('data-title');
            showQRModal(docCode, docTitle, btn); // ส่ง btn ไปด้วย
        }
    });

    // 2. ดักจับคลิกดูรายละเอียดเอกสาร
    document.body.addEventListener('click', function(e) {
        const link = e.target.closest('.js-open-detail');
        if (link) {
            e.preventDefault();
            const docCode = link.getAttribute('data-code');
            
            // [จุดสำคัญ] ส่ง link (ตัวแปรที่เก็บ Element <a>) ไปเป็น Argument ที่ 2
            openDetailModal(docCode, link); 
        }
    });

    // Code แถมสำหรับปิด Focus
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('hide.bs.modal', function() {
            if (document.activeElement && modal.contains(document.activeElement)) {
                document.activeElement.blur();
            }
        });
    });
});