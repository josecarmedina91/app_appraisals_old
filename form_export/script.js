document.addEventListener('DOMContentLoaded', function () {
    const autoResizeElements = document.querySelectorAll('.auto-resize');
    autoResizeElements.forEach(element => {
        element.style.height = 'auto';
        element.style.height = element.scrollHeight + 'px';
    });
});

let signaturePad;

function openSignatureModal() {
    document.getElementById('signature-modal').classList.remove('hidden');
    const canvas = document.getElementById('signature-pad');
    const ratio = Math.max(window.devicePixelRatio || 1, 1);
    canvas.width = canvas.offsetWidth * ratio;
    canvas.height = canvas.offsetHeight * ratio;
    canvas.getContext('2d').scale(ratio, ratio);
    signaturePad = new SignaturePad(canvas, {
        backgroundColor: 'rgba(255, 255, 255, 1)',
        penColor: 'rgb(0, 0, 0)'
    });
}

function closeSignatureModal() {
    document.getElementById('signature-modal').classList.add('hidden');
    signaturePad.clear();
}

function confirmSignature() {
    if (signaturePad.isEmpty()) {
        alert('Please provide a signature first.');
        return;
    }

    if (confirm('By pressing the submit button, you are assuming full responsibility for delivering a quality report.')) {
        saveSignature();
    }
}

function saveSignature() {
    const canvas = document.getElementById('signature-pad');
    const dataURL = canvas.toDataURL('image/png');
    
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        if (xhr.status >= 200 && xhr.status < 300) {
            closeSignatureModal();
            window.location.href = `upload.document.php?id=${inspectionId}`;
        } else {
            alert('Failed to save the signature.');
        }
    };

    xhr.onerror = function() {
        alert('Failed to save the signature.');
    };

    xhr.send('signature=' + encodeURIComponent(dataURL));
}
