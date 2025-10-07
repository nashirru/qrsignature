document.addEventListener('DOMContentLoaded', function() {
    // --- Logika Hamburger Menu ---
    const hamburgerBtn = document.getElementById('hamburger-btn');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebar-overlay');

    if (hamburgerBtn && sidebar && sidebarOverlay) {
        hamburgerBtn.addEventListener('click', () => {
            sidebar.classList.toggle('-translate-x-full');
            sidebarOverlay.classList.toggle('hidden');
        });

        sidebarOverlay.addEventListener('click', () => {
            sidebar.classList.add('-translate-x-full');
            sidebarOverlay.classList.add('hidden');
        });
    }

    // --- Logika Modal Generik ---
    const initModal = (modalId, openBtnId) => {
        const modal = document.getElementById(modalId);
        const openBtn = document.getElementById(openBtnId);
        if (!modal || !openBtn) return;
        
        const closeBtns = modal.querySelectorAll('.close-modal');

        openBtn.addEventListener('click', () => modal.classList.remove('hidden'));
        
        closeBtns.forEach(btn => {
            btn.addEventListener('click', () => modal.classList.add('hidden'));
        });

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.add('hidden');
            }
        });
    };
    
    // Inisialisasi semua modal
    initModal('generate-qr-modal', 'open-generate-qr-modal');
    initModal('add-person-modal', 'open-add-person-modal');
    initModal('add-signature-modal', 'open-add-signature-modal');


    // --- Logika untuk Papan Tanda Tangan ---
    const canvas = document.getElementById('signature-pad');
    if (canvas) {
        let signaturePad;
        try {
            signaturePad = new SignaturePad(canvas, {
                backgroundColor: 'rgb(243, 244, 246)' // Warna bg-gray-100
            });
        } catch (e) {
            console.error("Gagal menginisialisasi SignaturePad:", e);
            return;
        }

        const clearButton = document.getElementById('clear-signature-btn');
        if (clearButton) {
            clearButton.addEventListener('click', () => signaturePad.clear());
        }

        const signatureForm = document.getElementById('signature-form');
        if (signatureForm) {
            signatureForm.addEventListener('submit', function(event) {
                const drawTabContent = document.getElementById('draw-tab-content');
                if (drawTabContent && !drawTabContent.classList.contains('hidden')) {
                     if (signaturePad.isEmpty()) {
                        document.getElementById('signature_data').value = '';
                    } else {
                        document.getElementById('signature_data').value = signaturePad.toDataURL('image/png');
                    }
                }
            });
        }
        
        function resizeCanvas() {
            const ratio =  Math.max(window.devicePixelRatio || 1, 1);
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            canvas.getContext("2d").scale(ratio, ratio);
            signaturePad.fromData(signaturePad.toData()); // Redraw signature
        }

        const resizeObserver = new ResizeObserver(() => resizeCanvas());
        resizeObserver.observe(canvas.parentElement);
        
        // --- Logika Tab Tanda Tangan ---
        const uploadTab = document.getElementById('upload-tab');
        const drawTab = document.getElementById('draw-tab');
        const uploadContent = document.getElementById('upload-tab-content');
        const drawContent = document.getElementById('draw-tab-content');

        if (uploadTab && drawTab && uploadContent && drawContent) {
            const setActiveTab = (activeTab) => {
                const inactiveTab = activeTab === 'upload' ? 'draw' : 'upload';
                
                document.getElementById(`${activeTab}-tab-content`).classList.remove('hidden');
                document.getElementById(`${inactiveTab}-tab-content`).classList.add('hidden');
                
                document.getElementById(`${activeTab}-tab`).classList.add('border-blue-500', 'text-blue-600');
                document.getElementById(`${activeTab}-tab`).classList.remove('border-transparent', 'hover:text-gray-600', 'hover:border-gray-300');
                
                document.getElementById(`${inactiveTab}-tab`).classList.add('border-transparent', 'hover:text-gray-600', 'hover:border-gray-300');
                document.getElementById(`${inactiveTab}-tab`).classList.remove('border-blue-500', 'text-blue-600');
            };

            uploadTab.addEventListener('click', () => setActiveTab('upload'));
            drawTab.addEventListener('click', () => {
                setActiveTab('draw');
                setTimeout(() => resizeCanvas(), 10); // Resize setelah tab aktif
            });
            
            // Atur tab default
            setActiveTab('upload');
        }
    }
});