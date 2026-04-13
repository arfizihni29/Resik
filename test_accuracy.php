<?php
require_once 'config/config.php';
require_once 'config/Database.php';



/*
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}
*/

$currentPage = 'test_accuracy'; 
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uji Akurasi AI - Resik</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    
    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
        .card { border-radius: 12px; border: none; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .btn-upload { background: #14b8a6; color: white; border: none; }
        .btn-upload:hover { background: #0d9488; color: white; }
        .preview-img { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; }
        .accuracy-card { transition: all 0.3s ease; }
        .accuracy-high { background-color: #d1fae5; color: #065f46; border: 1px solid #10b981; }
        .accuracy-med { background-color: #fef3c7; color: #92400e; border: 1px solid #f59e0b; }
        .accuracy-low { background-color: #fee2e2; color: #991b1b; border: 1px solid #ef4444; }
        
        /* Stats Box */
        .stat-box {
            padding: 1.5rem;
            border-radius: 12px;
            background: white;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .stat-value { font-size: 2rem; font-weight: 700; color: #1f2937; }
        .stat-label { font-size: 0.875rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; }
    </style>
</head>
<body>
    <!-- Navbar (Simplified) -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-chevron-left me-2"></i> Kembali ke Beranda
            </a>
            <span class="navbar-text text-white">
                <i class="fas fa-microchip text-info"></i> Tool Uji Validasi Sistem
            </span>
        </div>
    </nav>

    <div class="container mt-4 mb-5">
        <!-- Header & Stats -->
        <div class="row align-items-center mb-4">
            <div class="col-md-6 mb-3 mb-md-0">
                <h2 class="fw-bold text-gray-800">Uji Akurasi Sistem</h2>
                <p class="text-muted">Upload banyak gambar sekaligus untuk menguji performa model klasifikasi.</p>
                <div class="alert alert-info py-2">
                    <small><i class="fas fa-info-circle"></i> Model yang digunakan sama dengan yang ada di halaman pelaporan user.</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="row g-2">
                    <div class="col-6">
                        <div class="stat-box">
                            <div class="stat-value" id="totalImages">0</div>
                            <div class="stat-label">Total Gambar</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-box accuracy-card" id="accuracyCard">
                            <div class="stat-value" id="accuracyScore">0%</div>
                            <div class="stat-label">Akurasi</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Area -->
        <div class="card mb-4">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="d-flex gap-3 align-items-center">
                            <label for="imageInput" class="btn btn-upload btn-lg px-4">
                                <i class="fas fa-images me-2"></i> Pilih Gambar (Batch)
                            </label>
                            <input type="file" id="imageInput" multiple accept="image/*" class="d-none">
                            <button class="btn btn-outline-danger" id="clearBtn" style="display: none;">
                                <i class="fas fa-trash-alt"></i> Reset
                            </button>
                            <div id="loadingModel" style="display: none;">
                                <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                                <span class="text-primary ms-2">Memuat Model AI...</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <div id="progressArea" style="display: none;">
                            <small class="text-muted d-block mb-1">Memproses: <span id="progressText">0/0</span></small>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-info" id="progressBar" role="progressbar" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results Table -->
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th width="5%">#</th>
                                <th width="15%">Preview</th>
                                <th width="25%">Prediksi AI</th>
                                <th width="25%">Kategori Sebenarnya (Manual)</th>
                                <th width="15%">Status</th>
                                <th width="15%">Confidence</th>
                            </tr>
                        </thead>
                        <tbody id="resultsTableBody">
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fas fa-cloud-upload-alt fa-3x mb-3 text-gray-300"></i>
                                    <p>Belum ada gambar yang diupload</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <!-- TensorFlow & Teachable Machine -->
    <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@1.3.1/dist/tf.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@teachablemachine/image@0.8/dist/teachablemachine-image.min.js"></script>
    
    <!-- Classifier Logic -->
    <script src="assets/js/classifier.js"></script>

    <script>

        let results = [];
        let totalProcessed = 0;
        let totalFiles = 0;


        document.addEventListener('DOMContentLoaded', async () => {
            const loadingModel = document.getElementById('loadingModel');
            loadingModel.style.display = 'inline-block';
            

            window.showToast = (msg, type) => console.log(`[Toast ${type}]: ${msg}`);




            /* 
            try {
                await loadModel();
                loadingModel.style.display = 'none';
                console.log("Model Loaded Successfully");
            } catch (e) {
                console.error("Model Load Failed", e);
                loadingModel.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-triangle"></i> Gagal Memuat Model</span>';
            } 
            */
            

            const checkModelInterval = setInterval(() => {
                if (typeof isModelLoaded !== 'undefined' && isModelLoaded) {
                    clearInterval(checkModelInterval);
                    loadingModel.style.display = 'none';
                    console.log("Model (Global) Loaded Successfully");
                }
            }, 500);
        });


        document.getElementById('imageInput').addEventListener('change', async (e) => {
            const files = Array.from(e.target.files);
            if(files.length === 0) return;




            if(results.length === 0) {
                 document.getElementById('resultsTableBody').innerHTML = '';
            }
            
            document.getElementById('clearBtn').style.display = 'inline-block';
            document.getElementById('progressArea').style.display = 'block';
            
            const startIdx = results.length;
            totalFiles += files.length;
            updateStats();


            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const rowId = startIdx + i;
                

                addPlaceholderRow(rowId, file);
                

                processImage(file, rowId);
            }
        });

        document.getElementById('clearBtn').addEventListener('click', () => {
            if(confirm('Hapus semua hasil tes?')) {
                results = [];
                totalProcessed = 0;
                totalFiles = 0;
                document.getElementById('resultsTableBody').innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="fas fa-cloud-upload-alt fa-3x mb-3 text-gray-300"></i>
                            <p>Belum ada gambar yang diupload</p>
                        </td>
                    </tr>
                `;
                document.getElementById('clearBtn').style.display = 'none';
                document.getElementById('imageInput').value = '';
                updateStats();
                updateAccuracyHTML();
            }
        });

        function addPlaceholderRow(id, file) {
            const tbody = document.getElementById('resultsTableBody');
            const tr = document.createElement('tr');
            tr.id = `row-${id}`;
            tr.innerHTML = `
                <td>${id + 1}</td>
                <td>
                    <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                </td>
                <td><span class="text-muted">Memproses...</span></td>
                <td>
                    <select class="form-select form-select-sm" disabled>
                        <option value="">-- Pilih --</option>
                        <option value="organik">Organik</option>
                        <option value="anorganik">Anorganik</option>
                        <option value="b3">B3</option>
                    </select>
                </td>
                <td>-</td>
                <td>-</td>
            `;
            tbody.appendChild(tr);
        }

        async function processImage(file, id) {
            const reader = new FileReader();
            reader.onload = async (e) => {
                const img = new Image();
                img.src = e.target.result;
                img.onload = async () => {

                    const row = document.getElementById(`row-${id}`);
                    if(!row) return;

                    row.querySelector('td:nth-child(2)').innerHTML = `<img src="${img.src}" class="preview-img" alt="Preview">`;

                    try {
                        const predictions = await predictImage(img);
                        const best = getBestPrediction(predictions);
                        const category = mapClassToCategory(best.className);
                        const confidence = (best.probability * 100).toFixed(1);


                        results[id] = {
                            file: file,
                            aiCategory: category,
                            manualCategory: null,
                            confidence: confidence,
                            isAccurate: null
                        };

                        updateRow(id, category, confidence);
                        
                    } catch (err) {
                        console.error(err);
                        row.querySelector('td:nth-child(3)').innerHTML = `<span class="text-danger">Error</span>`;
                    } finally {
                        totalProcessed++;
                        updateProgress();
                    }
                };
            };
            reader.readAsDataURL(file);
        }

        function updateRow(id, category, confidence) {
            const row = document.getElementById(`row-${id}`);
            if(!row) return;


            let badgeClass = 'bg-secondary';
            if(category === 'organik') badgeClass = 'bg-success';
            if(category === 'anorganik') badgeClass = 'bg-primary';
            if(category === 'b3') badgeClass = 'bg-danger';

            row.querySelector('td:nth-child(3)').innerHTML = `
                <span class="badge ${badgeClass} fs-6">${category.toUpperCase()}</span>
                <div class="small text-muted mt-1">Class: ${results[id].aiCategory}</div>
            `;


            row.querySelector('td:nth-child(6)').innerHTML = `${confidence}%`;


            const select = row.querySelector('select');
            select.disabled = false;
            select.onchange = (e) => handleManualChange(id, e.target.value);



        }

        function handleManualChange(id, value) {
            results[id].manualCategory = value;
            const row = document.getElementById(`row-${id}`);
            const statusCell = row.querySelector('td:nth-child(5)');

            if (!value) {
                results[id].isAccurate = null;
                statusCell.innerHTML = '-';
            } else {
                const isCorrect = value === results[id].aiCategory;
                results[id].isAccurate = isCorrect;
                
                if(isCorrect) {
                     statusCell.innerHTML = `<i class="fas fa-check-circle text-success fa-2x"></i>`;
                } else {
                     statusCell.innerHTML = `<i class="fas fa-times-circle text-danger fa-2x"></i>`;
                }
            }
            updateAccuracyHTML();
        }

        function updateProgress() {
            const percent = Math.round((totalProcessed / totalFiles) * 100);
            document.getElementById('progressBar').style.width = `${percent}%`;
            document.getElementById('progressText').innerText = `${totalProcessed}/${totalFiles}`;
            document.getElementById('progressBar').className = percent === 100 ? 'progress-bar bg-success' : 'progress-bar bg-info';
            
            if(percent === 100) {
                 setTimeout(() => {
                      document.getElementById('progressArea').style.display = 'none';
                 }, 2000);
            }
        }

        function updateStats() {
            document.getElementById('totalImages').innerText = totalFiles;
        }

        function updateAccuracyHTML() {
            let correct = 0;
            let totalEvaluated = 0;

            results.forEach(r => {
                if(r && r.manualCategory) {
                    totalEvaluated++;
                    if(r.isAccurate) correct++;
                }
            });

            const score = totalEvaluated === 0 ? 0 : Math.round((correct / totalEvaluated) * 100);
            
            const card = document.getElementById('accuracyCard');
            const scoreEl = document.getElementById('accuracyScore');
            
            scoreEl.innerText = `${score}%`;
            

            card.classList.remove('accuracy-high', 'accuracy-med', 'accuracy-low');
            
            if(totalEvaluated > 0) {
                if(score >= 80) card.classList.add('accuracy-high');
                else if(score >= 50) card.classList.add('accuracy-med');
                else card.classList.add('accuracy-low');
            }
            
            scoreEl.innerHTML = `${score}% <small class="fs-6 text-muted fw-normal">(${correct}/${totalEvaluated})</small>`;
        }
    </script>
</body>
</html>
