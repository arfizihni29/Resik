<?php
require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../classes/Report.php';

checkLogin();
checkAdmin();

$currentPage = 'peta';

$database = new Database();
$db = $database->getConnection();
$report = new Report($db);

$allReports = $report->getAllReports();

$reportsData = [];
foreach ($allReports as $r) {
    $reportsData[] = [
        'id' => $r['id'],
        'kategori' => $r['kategori'],
        'jenis_sampah' => $r['jenis_sampah'],
        'gambar' => $r['gambar'],
        'status' => $r['status'],
        'alamat_lokasi' => $r['alamat_lokasi'],
        'latitude' => floatval($r['lokasi_latitude']),
        'longitude' => floatval($r['lokasi_longitude']),
        'user_nama' => $r['user_nama'] ?? 'Unknown',
        'created_at' => $r['created_at'],
        'deskripsi' => $r['deskripsi'] ?? ''
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peta Interaktif - Admin</title>
    
    
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <link rel="alternate icon" href="../favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="../favicon.svg">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root {
            --primary: #14b8a6;
            --primary-dark: #0d9488;
            --organik: #10b981;
            --anorganik: #3b82f6;
            --b3: #ef4444;
            --bg: #f8fafc;
            --card: #ffffff;
            --text: #1e293b;
            --text-light: #64748b;
            --border: #e2e8f0;
        }

        body {
            background: var(--bg);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        #mapContainer {
            height: calc(100vh - 120px);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        /* Floating Control Panel */
        .control-float {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 1000;
            background: var(--card);
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            width: 320px;
            max-height: calc(100vh - 140px);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .control-float.collapsed {
            width: 60px;
        }

        .control-header {
            padding: 16px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }

        .control-header h6 {
            margin: 0;
            font-weight: 600;
            color: var(--text);
            font-size: 0.875rem;
        }

        .control-body {
            padding: 16px;
            max-height: calc(100vh - 280px);
            overflow-y: auto;
        }

        .control-float.collapsed .control-body {
            display: none;
        }

        /* Minimal Stats */
        .stat-mini {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            margin-bottom: 16px;
        }

        .stat-item-mini {
            background: var(--bg);
            padding: 12px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-item-mini .number {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 4px;
        }

        .stat-item-mini .label {
            font-size: 0.75rem;
            color: var(--text-light);
        }

        /* Filter Chips */
        .filter-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            border: 1px solid var(--border);
            background: var(--card);
            color: var(--text);
            font-size: 0.8125rem;
            cursor: pointer;
            transition: all 0.2s;
            margin: 4px 2px;
        }

        .filter-chip:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .filter-chip.active {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }

        .filter-chip.organik.active {
            background: var(--organik);
            border-color: var(--organik);
        }

        .filter-chip.anorganik.active {
            background: var(--anorganik);
            border-color: var(--anorganik);
        }

        .filter-chip.b3.active {
            background: var(--b3);
            border-color: var(--b3);
        }

        /* Minimal Form Controls */
        .form-control-mini {
            padding: 8px 12px;
            font-size: 0.8125rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--card);
        }

        .form-control-mini:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.1);
        }

        /* Toggle Switch Minimal */
        .toggle-mini {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid var(--border);
        }

        .toggle-mini:last-child {
            border-bottom: none;
        }

        .toggle-mini label {
            font-size: 0.8125rem;
            color: var(--text);
            margin: 0;
            cursor: pointer;
        }

        .form-switch .form-check-input {
            width: 2.5rem;
            height: 1.25rem;
            cursor: pointer;
        }

        /* Search Box */
        .search-float {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 1000;
            width: 300px;
        }

        .search-float input {
            padding: 10px 16px;
            border: 1px solid var(--border);
            border-radius: 24px;
            background: var(--card);
            font-size: 0.875rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .search-float input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.1);
            outline: none;
        }

        /* Map Controls */
        .map-controls {
            position: absolute;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .map-btn {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: var(--card);
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.2s;
            color: var(--text);
        }

        .map-btn:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        /* Legend Minimal */
        .legend-mini {
            position: absolute;
            bottom: 20px;
            left: 20px;
            z-index: 1000;
            background: var(--card);
            padding: 12px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            font-size: 0.75rem;
        }

        .legend-item-mini {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 6px;
        }

        .legend-item-mini:last-child {
            margin-bottom: 0;
        }

        .legend-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        /* Custom Scrollbar */
        .control-body::-webkit-scrollbar {
            width: 4px;
        }

        .control-body::-webkit-scrollbar-track {
            background: transparent;
        }

        .control-body::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 2px;
        }

        /* Marker Icons */
        .custom-icon {
            transition: transform 0.2s;
        }

        .custom-icon:hover {
            transform: scale(1.1);
        }

        /* Info Panel */
        .info-float {
            position: absolute;
            bottom: 80px;
            left: 20px;
            z-index: 1000;
            background: var(--card);
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            max-width: 320px;
            display: none;
        }

        .info-float.show {
            display: block;
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .control-float {
                width: 100%;
                max-width: calc(100vw - 40px);
                position: relative;
                margin-bottom: 16px;
            }

            .search-float {
                width: calc(100% - 40px);
                position: relative;
                margin-bottom: 16px;
            }

            #mapContainer {
                height: 60vh;
            }

            .map-controls {
                bottom: 10px;
                right: 10px;
            }

            .legend-mini {
                bottom: 60px;
            }
        }

        /* Section Divider */
        .section-divider {
            height: 1px;
            background: var(--border);
            margin: 16px 0;
        }

        /* Badge Count */
        .badge-count {
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 10px;
            background: var(--bg);
            color: var(--text-light);
            margin-left: 4px;
        }

        .filter-chip.active .badge-count {
            background: rgba(255,255,255,0.2);
            color: white;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid mt-3 mb-3">
        
        <div class="search-float">
            <input type="text" class="form-control" id="searchLocation" placeholder="Cari lokasi...">
        </div>

        
        <div id="mapContainer"></div>

        
        <div class="control-float" id="controlPanel">
            <div class="control-header" onclick="toggleControlPanel()">
                <h6><i class="fas fa-sliders-h"></i> Kontrol</h6>
                <i class="fas fa-chevron-down" id="panelToggle"></i>
            </div>
            <div class="control-body">
                
                <div class="stat-mini">
                    <div class="stat-item-mini">
                        <div class="number" id="totalReports"><?php echo count($allReports); ?></div>
                        <div class="label">Total</div>
                    </div>
                    <div class="stat-item-mini">
                        <div class="number" id="count-organik" style="color: var(--organik);">0</div>
                        <div class="label">Organik</div>
                    </div>
                    <div class="stat-item-mini">
                        <div class="number" id="count-anorganik" style="color: var(--anorganik);">0</div>
                        <div class="label">Anorganik</div>
                    </div>
                </div>

                <div class="section-divider"></div>

                
                <div style="margin-bottom: 12px;">
                    <label style="font-size: 0.75rem; color: var(--text-light); margin-bottom: 8px; display: block;">Kategori</label>
                    <div>
                        <span class="filter-chip organik active" data-category="organik">
                            <i class="fas fa-leaf"></i> Organik
                            <span class="badge-count" id="badge-organik">0</span>
                        </span>
                        <span class="filter-chip anorganik" data-category="anorganik">
                            <i class="fas fa-recycle"></i> Anorganik
                            <span class="badge-count" id="badge-anorganik">0</span>
                        </span>
                        <span class="filter-chip b3" data-category="b3">
                            <i class="fas fa-exclamation-triangle"></i> B3
                            <span class="badge-count" id="badge-b3">0</span>
                        </span>
                        <span class="filter-chip" data-category="all">
                            <i class="fas fa-th"></i> Semua
                        </span>
                    </div>
                                </div>

                <div class="section-divider"></div>

                
                <div style="margin-bottom: 12px;">
                    <label style="font-size: 0.75rem; color: var(--text-light); margin-bottom: 8px; display: block;">Status</label>
                    <select class="form-control-mini form-select form-select-sm" id="statusFilter">
                        <option value="all">Semua Status</option>
                        <option value="pending">Pending</option>
                        <option value="diproses">Diproses</option>
                        <option value="selesai">Selesai</option>
                        <option value="ditolak">Ditolak</option>
                    </select>
                            </div>

                <div class="section-divider"></div>

                
                <div>
                    <div class="toggle-mini">
                        <label><i class="fas fa-map-marker-alt"></i> Markers</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="toggleMarkers" checked>
                                </div>
                            </div>
                    <div class="toggle-mini">
                        <label><i class="fas fa-fire"></i> Heatmap</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="toggleHeatmap">
                                </div>
                            </div>
                    <div class="toggle-mini">
                        <label><i class="fas fa-layer-group"></i> Clustering</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="toggleClustering" checked>
                        </div>
                    </div>
                </div>

                <div class="section-divider"></div>

                
                <div style="margin-bottom: 12px;">
                    <label style="font-size: 0.75rem; color: var(--text-light); margin-bottom: 8px; display: block;">Tanggal</label>
                    <input type="date" class="form-control-mini form-control form-control-sm" id="dateFrom" style="margin-bottom: 8px;">
                    <input type="date" class="form-control-mini form-control form-control-sm" id="dateTo">
                </div>
            </div>
        </div>

        
        <div class="map-controls">
            <div class="map-btn" onclick="resetView()" title="Reset View">
                <i class="fas fa-home"></i>
            </div>
            <div class="map-btn" onclick="toggleFullscreen()" title="Fullscreen">
                <i class="fas fa-expand"></i>
            </div>
            <div class="map-btn" onclick="locateUser()" title="Lokasi Saya">
                <i class="fas fa-crosshairs"></i>
            </div>
            <div class="map-btn" onclick="exportData('json')" title="Export JSON">
                <i class="fas fa-download"></i>
            </div>
        </div>

        
        <div class="legend-mini">
            <div class="legend-item-mini">
                <div class="legend-dot" style="background: var(--organik);"></div>
                <span>Organik</span>
            </div>
            <div class="legend-item-mini">
                <div class="legend-dot" style="background: var(--anorganik);"></div>
                <span>Anorganik</span>
            </div>
            <div class="legend-item-mini">
                <div class="legend-dot" style="background: var(--b3);"></div>
                <span>B3</span>
        </div>
    </div>

        
        <div class="info-float" id="infoPanel">
            <div id="infoContent"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/leaflet.heat@0.2.0/dist/leaflet-heat.min.js"></script>
    <script src="../assets/js/navbar.js"></script>
    <script>

        const map = L.map('mapContainer').setView([-6.200000, 106.816666], 11);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap',
            maxZoom: 19
        }).addTo(map);

        const allReports = <?php echo json_encode($reportsData); ?>;


        let currentCategory = 'all';
        let currentStatus = 'all';
        let dateFrom = null;
        let dateTo = null;
        let showMarkers = true;
        let showHeatmap = false;
        let useClustering = true;

        let markerLayer = L.layerGroup();
        let heatmapLayer = null;
        let markers = [];
        let markerCluster = null;


        const createIcon = (color, icon) => L.divIcon({
            html: `<div style="background: ${color}; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 14px; box-shadow: 0 2px 4px rgba(0,0,0,0.2); border: 2px solid white;"><i class="fas ${icon}"></i></div>`,
            iconSize: [32, 32],
            iconAnchor: [16, 16],
            className: 'custom-icon'
        });

        const iconOrganik = createIcon('#10b981', 'fa-leaf');
        const iconAnorganik = createIcon('#3b82f6', 'fa-recycle');
        const iconB3 = createIcon('#ef4444', 'fa-exclamation-triangle');

        const getIcon = (category) => {
            if (category === 'organik') return iconOrganik;
            if (category === 'b3') return iconB3;
            return iconAnorganik;
        };


        const filterReports = () => {
            return allReports.filter(report => {
                if (currentCategory !== 'all' && report.kategori !== currentCategory) return false;
                if (currentStatus !== 'all' && report.status !== currentStatus) return false;
                if (dateFrom && new Date(report.created_at) < new Date(dateFrom)) return false;
                if (dateTo && new Date(report.created_at) > new Date(dateTo + 'T23:59:59')) return false;
                return true;
            });
        };


        const updateMap = () => {
            if (markerCluster) { map.removeLayer(markerCluster); markerCluster = null; }
            map.removeLayer(markerLayer);
            if (heatmapLayer) { map.removeLayer(heatmapLayer); heatmapLayer = null; }
            markers = [];

            const filtered = filterReports();
            updateStats(filtered);

            if (showMarkers) {
                if (useClustering) {
                    markerCluster = L.markerClusterGroup({ maxClusterRadius: 50 });
                    filtered.forEach(r => {
                        const m = L.marker([r.latitude, r.longitude], { icon: getIcon(r.kategori) });
                        m.bindPopup(createPopup(r));
                        m.on('click', () => showInfo(r));
                        markerCluster.addLayer(m);
                        markers.push(m);
                    });
                    markerCluster.addTo(map);
                } else {
                    filtered.forEach(r => {
                        const m = L.marker([r.latitude, r.longitude], { icon: getIcon(r.kategori) });
                        m.bindPopup(createPopup(r));
                        m.on('click', () => showInfo(r));
                        markerLayer.addLayer(m);
                        markers.push(m);
                    });
                    markerLayer.addTo(map);
                }
            }

            if (showHeatmap && filtered.length > 0) {
                heatmapLayer = L.heatLayer(filtered.map(r => [r.latitude, r.longitude, 1]), {
                    radius: 25,
                    blur: 15,
                    maxZoom: 17
                });
                heatmapLayer.addTo(map);
            }

            if (markers.length > 0) {
                map.fitBounds(L.featureGroup(markers).getBounds().pad(0.1));
            }
        };


        const createPopup = (r) => {
            const statusColors = { 'pending': '#f59e0b', 'diproses': '#3b82f6', 'selesai': '#10b981', 'ditolak': '#ef4444' };
            return `
                <div style="max-width: 250px;">
                    <img src="<?php echo rtrim(UPLOAD_URL, '/') . '/'; ?>${r.gambar}" style="width: 100%; border-radius: 8px; margin-bottom: 8px;" onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjUwIiBoZWlnaHQ9IjE1MCIgdmlld0JveD0iMCAwIDI1MCAxNTAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjI1MCIgaGVpZ2h0PSIxNTAiIGZpbGw9IiNGM0Y0RjYiLz48cGF0aCBkPSJNMTEwIDY1SDE0MFY4NUgxMTBWNjVaTTExMCA5NUgxNDBWMTE1SDExMFY5NVoiIGZpbGw9IiM5Q0EzQUYiLz48L3N2Zz4=';">
                    <div style="font-weight: 600; margin-bottom: 6px;">Laporan #${r.id}</div>
                    <div style="display: flex; gap: 4px; margin-bottom: 8px; flex-wrap: wrap;">
                        <span style="background: ${r.kategori === 'organik' ? '#10b981' : r.kategori === 'b3' ? '#ef4444' : '#3b82f6'}; color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem;">${r.kategori.toUpperCase()}</span>
                        <span style="background: ${statusColors[r.status] || '#64748b'}; color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem;">${r.status}</span>
                    </div>
                    <div style="font-size: 0.75rem; color: #64748b; margin-bottom: 8px;">${r.user_nama}</div>
                    <a href="detail.php?id=${r.id}" class="btn btn-sm" style="background: var(--primary); color: white; width: 100%; border-radius: 6px; padding: 4px;">Detail</a>
                </div>
            `;
        };


        const showInfo = (r) => {
            document.getElementById('infoContent').innerHTML = `
                <img src="<?php echo rtrim(UPLOAD_URL, '/') . '/'; ?>${r.gambar}" style="width: 100%; border-radius: 8px; margin-bottom: 12px;" onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjUwIiBoZWlnaHQ9IjE1MCIgdmlld0JveD0iMCAwIDI1MCAxNTAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjI1MCIgaGVpZ2h0PSIxNTAiIGZpbGw9IiNGM0Y0RjYiLz48cGF0aCBkPSJNMTEwIDY1SDE0MFY4NUgxMTBWNjVaTTExMCA5NUgxNDBWMTE1SDExMFY5NVoiIGZpbGw9IiM5Q0EzQUYiLz48L3N2Zz4=';">
                <div style="font-size: 0.875rem;">
                    <div style="font-weight: 600; margin-bottom: 8px;">Laporan #${r.id}</div>
                    <div style="color: #64748b; margin-bottom: 12px;">${r.alamat_lokasi}</div>
                    <a href="detail.php?id=${r.id}" class="btn btn-sm" style="background: var(--primary); color: white; width: 100%; border-radius: 6px;">Lihat Detail</a>
                </div>
            `;
            document.getElementById('infoPanel').classList.add('show');
        };


        const updateStats = (reports) => {
            document.getElementById('totalReports').textContent = reports.length;
            const counts = { organik: 0, anorganik: 0, b3: 0 };
            reports.forEach(r => { if (r.kategori in counts) counts[r.kategori]++; });
            document.getElementById('count-organik').textContent = counts.organik;
            document.getElementById('count-anorganik').textContent = counts.anorganik;
            document.getElementById('badge-organik').textContent = counts.organik;
            document.getElementById('badge-anorganik').textContent = counts.anorganik;
            document.getElementById('badge-b3').textContent = counts.b3;
        };


        const exportData = (format) => {
            const data = filterReports();
            if (format === 'json') {
                const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `reports_${new Date().toISOString().split('T')[0]}.json`;
                a.click();
            }
        };


        let panelCollapsed = false;
        const toggleControlPanel = () => {
            panelCollapsed = !panelCollapsed;
            document.getElementById('controlPanel').classList.toggle('collapsed', panelCollapsed);
            document.getElementById('panelToggle').style.transform = panelCollapsed ? 'rotate(180deg)' : 'rotate(0deg)';
        };


        document.querySelectorAll('.filter-chip').forEach(chip => {
            chip.addEventListener('click', function() {
                document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
                this.classList.add('active');
                currentCategory = this.dataset.category;
                updateMap();
            });
        });

        document.getElementById('statusFilter').addEventListener('change', e => { currentStatus = e.target.value; updateMap(); });
        document.getElementById('toggleMarkers').addEventListener('change', e => { showMarkers = e.target.checked; updateMap(); });
        document.getElementById('toggleHeatmap').addEventListener('change', e => { showHeatmap = e.target.checked; updateMap(); });
        document.getElementById('toggleClustering').addEventListener('change', e => { useClustering = e.target.checked; updateMap(); });
        document.getElementById('dateFrom').addEventListener('change', e => { dateFrom = e.target.value; updateMap(); });
        document.getElementById('dateTo').addEventListener('change', e => { dateTo = e.target.value; updateMap(); });

        document.getElementById('searchLocation').addEventListener('input', e => {
            const term = e.target.value.toLowerCase();
            if (term.length < 2) return;
            const filtered = allReports.filter(r => r.alamat_lokasi.toLowerCase().includes(term));
            if (filtered.length > 0) map.fitBounds(L.latLngBounds(filtered.map(r => [r.latitude, r.longitude])));
        });

        map.on('click', () => document.getElementById('infoPanel').classList.remove('show'));


        const resetView = () => {
            if (markers.length > 0) map.fitBounds(L.featureGroup(markers).getBounds().pad(0.1));
            else map.setView([-6.200000, 106.816666], 11);
        };

        const toggleFullscreen = () => {
            const container = document.getElementById('mapContainer');
            if (!document.fullscreenElement) container.requestFullscreen().then(() => setTimeout(() => map.invalidateSize(), 100));
            else document.exitFullscreen().then(() => setTimeout(() => map.invalidateSize(), 100));
        };

        const locateUser = () => {
            if (navigator.geolocation) {
                map.locate({ setView: true, maxZoom: 16 });
                map.once('locationfound', e => L.marker(e.latlng).addTo(map).bindPopup('Lokasi Anda').openPopup());
            }
        };

        document.addEventListener('fullscreenchange', () => setTimeout(() => map.invalidateSize(), 100));


        updateMap();
    </script>
</body>
</html>
