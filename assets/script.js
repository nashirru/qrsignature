// assets/script.js

document.addEventListener('DOMContentLoaded', function() {
    const canvas = document.getElementById('signature-pad');
    if (!canvas) return;

    const signaturePad = new SignaturePad(canvas, {
        backgroundColor: 'rgb(255, 255, 255)'
    });

    // Adjust canvas size on window resize
    function resizeCanvas() {
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = canvas.offsetHeight * ratio;
        canvas.getContext("2d").scale(ratio, ratio);
        signaturePad.clear(); // otherwise strokes will be resized
    }
    window.addEventListener("resize", resizeCanvas);
    resizeCanvas();


    const clearButton = document.getElementById('clear-signature');
    if (clearButton) {
        clearButton.addEventListener('click', function() {
            signaturePad.clear();
        });
    }

    const form = document.getElementById('signatureForm');
    const saveButton = document.getElementById('save-btn');
    if (form && saveButton) {
        saveButton.addEventListener('click', function(event) {
            if (signaturePad.isEmpty()) {
                // If the user wants to upload, it's fine if the pad is empty
                const fileInput = document.getElementById('signature_file');
                if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                     // But if they are in the "draw" tab and it's empty, we might want to alert them.
                     // For simplicity, we'll allow submitting if an existing signature is already there.
                }
            } else {
                // If something is drawn, convert to Base64 and put it in the hidden field
                const dataURL = signaturePad.toDataURL('image/png');
                document.getElementById('signature_base64').value = dataURL;
            }
        });
    }
});