


let isProcessing = false;


function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    const bgColor = type === 'success' ? '#14b8a6' : type === 'error' ? '#ef4444' : '#3b82f6';
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${bgColor};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 9999;
        animation: slideIn 0.3s ease;
        max-width: 350px;
    `;
    toast.innerHTML = message;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}


function optimizeImageForPrediction(imgElement, maxSize = 800) {
    return new Promise((resolve) => {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');

        let width = imgElement.naturalWidth || imgElement.width;
        let height = imgElement.naturalHeight || imgElement.height;

        if (width > maxSize || height > maxSize) {
            const ratio = Math.min(maxSize / width, maxSize / height);
            width = Math.round(width * ratio);
            height = Math.round(height * ratio);
        }

        canvas.width = width;
        canvas.height = height;

        ctx.fillStyle = '#FFFFFF';
        ctx.fillRect(0, 0, width, height);
        ctx.drawImage(imgElement, 0, 0, width, height);

        resolve(canvas.toDataURL('image/jpeg', 0.85));
    });
}


async function predictImage(imageElement) {
    if (isProcessing) {
        showToast('⏳ Sedang memproses...', 'warning');
        return;
    }

    isProcessing = true;
    showToast('🤖 Mengirim ke Teachable Machine...', 'info');

    try {
        const base64Image = await optimizeImageForPrediction(imageElement);


        let apiPath = 'api/analyze_image.php';
        if (window.location.pathname.includes('/user/') || window.location.pathname.includes('/admin/')) {
            apiPath = '../api/analyze_image.php';
        }

        console.log("☁️ Sending request to:", apiPath);

        const response = await fetch(apiPath, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ image: base64Image })
        });

        const data = await response.json();

        if (!response.ok || data.error) {
            throw new Error(data.error || 'Server Error');
        }

        const result = {
            category: (data.category || 'anorganik').toLowerCase(),
            confidence: parseFloat(data.confidence || 0.95),
            item_name: data.item_name || '',
            objects: data.objects || [],
            reason: data.reason || 'AI Analysis Successful'
        };

        console.log("✅ AI Result:", result);

        const predictions = [{
            className: result.category,
            probability: result.confidence,
            objects: result.objects,
            reason: result.reason,
            itemName: result.item_name
        }];


        if (typeof displayClassificationResult === 'function') {
            displayClassificationResult(predictions[0], result.reason);
        }

        isProcessing = false;
        return predictions;

    } catch (error) {
        console.error('❌ AI Error:', error);
        showToast('❌ Gagal: ' + error.message, 'error');
        isProcessing = false;

        const resultDiv = document.getElementById('classificationResult');
        if (resultDiv) {
            resultDiv.innerHTML = `<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <strong>Error:</strong> ${error.message}</div>`;
            resultDiv.style.display = 'block';
        }
        return null;
    }
}


function displayClassificationResult(prediction, reason = '') {
    const resultDiv = document.getElementById('classificationResult');
    const kategoriInput = document.getElementById('kategori');
    const confidenceInput = document.getElementById('confidence');
    const aiPredictionInput = document.getElementById('ai_prediction');
    const isCorrectionInput = document.getElementById('is_corrected');

    let category = prediction.className;


    if (category.includes('anorganik') || category.includes('recycle')) category = 'anorganik';
    else if (category.includes('organik') || category.includes('compost')) category = 'organik';
    else if (category.includes('b3') || category.includes('hazardous')) category = 'b3';

    if (kategoriInput) kategoriInput.value = category;
    if (confidenceInput) confidenceInput.value = (prediction.probability * 100).toFixed(0);
    if (aiPredictionInput) aiPredictionInput.value = category;
    if (isCorrectionInput) isCorrectionInput.value = '0'; 

    let categoryColor = '#4caf50';
    let categoryIcon = 'leaf';
    let categoryName = 'Organik';

    if (category === 'anorganik') {
        categoryColor = '#2196f3';
        categoryIcon = 'recycle';
        categoryName = 'Anorganik';
    } else if (category === 'b3') {
        categoryColor = '#f44336';
        categoryIcon = 'exclamation-triangle';
        categoryName = 'B3 (Berbahaya)';
    }


    if ((!reason || typeof reason !== 'string') && prediction.reason) {
        reason = prediction.reason;
    }

    if (!resultDiv) return;

    let html = `
        <div class="classification-result fadeIn">
            <h5 class="mb-3"><i class="fas fa-magic"></i> Analisis AI</h5>
            
            <div class="alert" style="background-color: ${categoryColor}22; border-left: 5px solid ${categoryColor}; border-radius: 12px;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 style="color: ${categoryColor}; margin: 0; font-weight: 800;">
                        <i class="fas fa-${categoryIcon}"></i> ${categoryName.toUpperCase()}
                    </h4>
                    <span class="badge bg-dark rounded-pill">
                        ${(prediction.probability * 100).toFixed(0)}% Akurat
                    </span>
                </div>
                
                <div class="p-3 bg-white rounded-3 shadow-sm border border-light">
                    <!-- Object Tags Removed -->
                </div>
            </div>
        </div>
    `;

    resultDiv.innerHTML = html;
    resultDiv.style.display = 'block';

    resultDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    showToast(`✅ Selesai: ${categoryName}`, 'success');
}
