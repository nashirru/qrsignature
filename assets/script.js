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
        
        if (!modal) {
            console.warn(`Modal dengan ID '${modalId}' tidak ditemukan`);
            return null;
        }
        
        if (!openBtn) {
            console.warn(`Button dengan ID '${openBtnId}' tidak ditemukan`);
            return modal;
        }
        
        const closeBtns = modal.querySelectorAll('.close-modal');

        openBtn.addEventListener('click', (e) => {
            e.preventDefault();
            console.log(`Opening modal: ${modalId}`);
            modal.classList.remove('hidden');
            
            // Trigger resize canvas jika modal tanda tangan dibuka
            if (modalId === 'add-signature-modal') {
                setTimeout(() => {
                    const canvas = document.getElementById('signature-pad');
                    if (canvas && typeof window.resizeCanvas === 'function') {
                        window.resizeCanvas();
                    }
                }, 100);
            }
        });
        
        closeBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                console.log(`Closing modal: ${modalId}`);
                modal.classList.add('hidden');
                
                // Clear form jika modal tanda tangan ditutup
                if (modalId === 'add-signature-modal') {
                    const form = modal.querySelector('form');
                    if (form) {
                        // Reset form kecuali saat edit
                        const idInput = form.querySelector('input[name="id"]');
                        if (!idInput || !idInput.value) {
                            form.reset();
                            const canvas = document.getElementById('signature-pad');
                            if (canvas && window.signaturePad) {
                                window.signaturePad.clear();
                            }
                        }
                    }
                }
            });
        });

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                console.log(`Closing modal by clicking overlay: ${modalId}`);
                modal.classList.add('hidden');
            }
        });

        return modal;
    };
    
    // Inisialisasi semua modal
    initModal('generate-qr-modal', 'open-generate-qr-modal');
    initModal('add-person-modal', 'open-add-person-modal');
    const signatureModal = initModal('add-signature-modal', 'open-add-signature-modal');

    // --- Logika untuk Papan Tanda Tangan ---
    const canvas = document.getElementById('signature-pad');
    if (canvas) {
        console.log('Initializing signature pad...');
        
        // Cek apakah SignaturePad library tersedia
        if (typeof SignaturePad === 'undefined') {
            console.error('SignaturePad library tidak ditemukan! Pastikan script CDN sudah dimuat.');
            alert('Error: Library SignaturePad tidak tersedia. Refresh halaman.');
            return;
        }
        
        let signaturePad;
        try {
            signaturePad = new SignaturePad(canvas, {
                backgroundColor: 'rgb(243, 244, 246)', // Warna bg-gray-100
                penColor: 'rgb(0, 0, 0)',
                minWidth: 0.5,
                maxWidth: 2.5
            });
            
            // Simpan ke global scope untuk akses mudah
            window.signaturePad = signaturePad;
            console.log('SignaturePad initialized successfully');
        } catch (e) {
            console.error("Gagal menginisialisasi SignaturePad:", e);
            alert('Error: Gagal menginisialisasi papan tanda tangan.');
            return;
        }

        const clearButton = document.getElementById('clear-signature-btn');
        if (clearButton) {
            clearButton.addEventListener('click', (e) => {
                e.preventDefault();
                signaturePad.clear();
                console.log('Signature cleared');
            });
        }

        const signatureForm = document.getElementById('signature-form');
        if (signatureForm) {
            signatureForm.addEventListener('submit', function(event) {
                const drawTabContent = document.getElementById('draw-tab-content');
                const uploadTabContent = document.getElementById('upload-tab-content');
                
                // Cek apakah tab gambar langsung yang aktif
                if (drawTabContent && !drawTabContent.classList.contains('hidden')) {
                    if (signaturePad.isEmpty()) {
                        // Jika signature pad kosong dan tidak ada file upload
                        const fileInput = document.getElementById('signature_image');
                        if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                            const idInput = document.querySelector('input[name="id"]');
                            // Hanya validasi jika ini bukan mode edit
                            if (!idInput || !idInput.value) {
                                event.preventDefault();
                                alert('Silakan gambar tanda tangan terlebih dahulu.');
                                return false;
                            }
                        }
                        document.getElementById('signature_data').value = '';
                    } else {
                        document.getElementById('signature_data').value = signaturePad.toDataURL('image/png');
                        console.log('Signature data saved');
                    }
                } else {
                    // Tab upload aktif, kosongkan signature data
                    document.getElementById('signature_data').value = '';
                }
            });
        }
        
        // Fungsi resize canvas
        window.resizeCanvas = function() {
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            const container = canvas.parentElement;
            
            if (container && container.offsetWidth > 0) {
                const oldWidth = canvas.width;
                const oldHeight = canvas.height;
                
                canvas.width = container.offsetWidth * ratio;
                canvas.height = container.offsetHeight * ratio;
                canvas.style.width = container.offsetWidth + 'px';
                canvas.style.height = container.offsetHeight + 'px';
                
                const ctx = canvas.getContext("2d");
                ctx.scale(ratio, ratio);
                
                // Redraw signature jika ada
                if (signaturePad && typeof signaturePad.toData === 'function') {
                    const data = signaturePad.toData();
                    if (data && data.length > 0) {
                        signaturePad.fromData(data);
                    }
                }
                
                console.log('Canvas resized:', canvas.width, 'x', canvas.height);
            }
        };

        // Observer untuk resize
        const resizeObserver = new ResizeObserver(() => {
            if (canvas.offsetWidth > 0 && canvas.offsetHeight > 0) {
                resizeCanvas();
            }
        });
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
                document.getElementById(`${activeTab}-tab`).classList.remove('border-transparent', 'hover:text-gray-600', 'hover:border-gray-300', 'text-gray-500');
                
                document.getElementById(`${inactiveTab}-tab`).classList.add('border-transparent', 'hover:text-gray-600', 'hover:border-gray-300', 'text-gray-500');
                document.getElementById(`${inactiveTab}-tab`).classList.remove('border-blue-500', 'text-blue-600');
                
                // Clear file input ketika pindah ke tab draw
                if (activeTab === 'draw') {
                    const fileInput = document.getElementById('signature_image');
                    if (fileInput) fileInput.value = '';
                }
                
                // Clear signature pad ketika pindah ke tab upload
                if (activeTab === 'upload' && signaturePad) {
                    signaturePad.clear();
                }
                
                console.log('Active tab:', activeTab);
            };

            uploadTab.addEventListener('click', (e) => {
                e.preventDefault();
                setActiveTab('upload');
            });
            
            drawTab.addEventListener('click', (e) => {
                e.preventDefault();
                setActiveTab('draw');
                // Resize canvas setelah tab aktif dan visible
                setTimeout(() => {
                    if (!drawContent.classList.contains('hidden')) {
                        resizeCanvas();
                    }
                }, 50);
            });
            
            // Atur tab default
            setActiveTab('upload');
        }

        // Auto-open modal jika ada parameter edit di URL
        if (window.location.search.includes('action=edit') && signatureModal) {
            console.log('Auto-opening modal for edit mode');
            signatureModal.classList.remove('hidden');
            setTimeout(() => resizeCanvas(), 150);
        }
    } else {
        console.warn('Canvas element with id "signature-pad" not found');
    }
    
    console.log('Script initialization complete');
});