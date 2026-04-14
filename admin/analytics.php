<?php
require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../classes/Report.php';

checkLogin();
checkAdmin();

$currentPage = 'analytics';

$database = new Database();
$db = $database->getConnection();
$report = new Report($db);


$allReports = $report->getAllReports();
$stats = $report->getStatistics();


$totalReports = count($allReports);
$organikCount = $stats['organik'];
$anorganikCount = $stats['anorganik'];
$b3Count = $stats['b3'];


$organikPercent = $totalReports > 0 ? round(($organikCount / $totalReports) * 100, 1) : 0;
$anorganikPercent = $totalReports > 0 ? round(($anorganikCount / $totalReports) * 100, 1) : 0;
$b3Percent = $totalReports > 0 ? round(($b3Count / $totalReports) * 100, 1) : 0;


$thirtyDaysAgo = date('Y-m-d', strtotime('-30 days'));
$recentReports = array_filter($allReports, function($r) use ($thirtyDaysAgo) {
    return $r['created_at'] >= $thirtyDaysAgo;
});
$recentCount = count($recentReports);


$locationData = [];
foreach ($allReports as $r) {
    $key = round($r['lokasi_latitude'], 3) . ',' . round($r['lokasi_longitude'], 3);
    if (!isset($locationData[$key])) {
        $locationData[$key] = [
            'lat' => $r['lokasi_latitude'],
            'lng' => $r['lokasi_longitude'],
            'count' => 0,
            'address' => $r['alamat_lokasi'] ?? 'Lokasi tidak diketahui',
            'organik' => 0,
            'anorganik' => 0,
            'b3' => 0
        ];
    }
    $locationData[$key]['count']++;
    $locationData[$key][$r['kategori']]++;
}


usort($locationData, function($a, $b) {
    return $b['count'] - $a['count'];
});
$hotspots = array_slice($locationData, 0, 5);


$wasteTypes = [];
$wasteTypesByCategory = [
    'organik' => [],
    'anorganik' => [],
    'b3' => []
];

foreach ($allReports as $r) {
    $jenis = $r['jenis_sampah'];
    

    if (empty(trim($jenis))) {
        $desc = $r['deskripsi'] ?? '';

        if (preg_match('/Item:\s*([^.,]+)/i', $desc, $matches)) {
            $jenis = trim($matches[1]);
        } else {
            $jenis = (!empty($r['kategori'])) ? ucfirst($r['kategori']) : 'Lainnya';
        }
    }
    $kategori = $r['kategori'];
    

    if (!isset($wasteTypes[$jenis])) {
        $wasteTypes[$jenis] = [
            'name' => $jenis,
            'count' => 0,
            'kategori' => $kategori,
            'percent' => 0
        ];
    }
    $wasteTypes[$jenis]['count']++;
    

    if (!isset($wasteTypesByCategory[$kategori][$jenis])) {
        $wasteTypesByCategory[$kategori][$jenis] = 0;
    }
    $wasteTypesByCategory[$kategori][$jenis]++;
}


foreach ($wasteTypes as $key => $waste) {
    $wasteTypes[$key]['percent'] = $totalReports > 0 ? round(($waste['count'] / $totalReports) * 100, 1) : 0;
}


usort($wasteTypes, function($a, $b) {
    return $b['count'] - $a['count'];
});


$topWasteTypes = array_slice($wasteTypes, 0, 10);


foreach ($wasteTypesByCategory as $kategori => $types) {
    arsort($wasteTypesByCategory[$kategori]);
}


function prepareChartData($reports, $months) {
    $data = [];
    $labels = [];
    
    for ($i = $months - 1; $i >= 0; $i--) {
        $monthLabel = date('M Y', strtotime("-$i months"));
        $labels[] = $monthLabel;
        $data[$monthLabel] = [
            'organik' => 0,
            'anorganik' => 0,
            'b3' => 0,
            'total' => 0
        ];
    }
    
    foreach ($reports as $r) {
        $monthLabel = date('M Y', strtotime($r['created_at']));
        if (isset($data[$monthLabel])) {
            $data[$monthLabel][$r['kategori']]++;
            $data[$monthLabel]['total']++;
        }
    }
    
    $organik = [];
    $anorganik = [];
    $b3 = [];
    $total = [];
    
    foreach ($labels as $label) {
        $organik[] = $data[$label]['organik'];
        $anorganik[] = $data[$label]['anorganik'];
        $b3[] = $data[$label]['b3'];
        $total[] = $data[$label]['total'];
    }
    
    return [
        'labels' => $labels,
        'organik' => $organik,
        'anorganik' => $anorganik,
        'b3' => $b3,
        'total' => $total
    ];
}


$data1Month = prepareChartData($allReports, 1);
$data3Months = prepareChartData($allReports, 3);
$data6Months = prepareChartData($allReports, 6);
$data12Months = prepareChartData($allReports, 12);


$monthLabels = $data6Months['labels'];
$monthlyOrganik = $data6Months['organik'];
$monthlyAnorganik = $data6Months['anorganik'];
$monthlyB3 = $data6Months['b3'];
$monthlyTotal = $data6Months['total'];


function calculatePeriodStats($reports, $months) {
    $cutoffDate = date('Y-m-d', strtotime("-$months months"));
    $periodReports = array_filter($reports, function($r) use ($cutoffDate) {
        return $r['created_at'] >= $cutoffDate;
    });
    
    $organik = 0;
    $anorganik = 0;
    $b3 = 0;
    
    foreach ($periodReports as $r) {
        if ($r['kategori'] == 'organik') $organik++;
        elseif ($r['kategori'] == 'anorganik') $anorganik++;
        elseif ($r['kategori'] == 'b3') $b3++;
    }
    
    $total = count($periodReports);
    
    return [
        'total' => $total,
        'organik' => $organik,
        'anorganik' => $anorganik,
        'b3' => $b3,
        'organik_percent' => $total > 0 ? round(($organik / $total) * 100, 1) : 0,
        'anorganik_percent' => $total > 0 ? round(($anorganik / $total) * 100, 1) : 0,
        'b3_percent' => $total > 0 ? round(($b3 / $total) * 100, 1) : 0
    ];
}

$stats1Month = calculatePeriodStats($allReports, 1);
$stats3Months = calculatePeriodStats($allReports, 3);
$stats6Months = calculatePeriodStats($allReports, 6);
$stats12Months = calculatePeriodStats($allReports, 12);


function generateAIRecommendations($organik, $anorganik, $b3, $total) {
    $recommendations = [];
    

    if ($organik > 0) {
        $organikPercent = round(($organik / $total) * 100);
        

            $recommendations[] = [
                'type' => 'organik',
                'priority' => 'high',
            'icon' => 'flask',
            'title' => '🧪 Enzim & Bakteri Pengurai Sampah',
            'description' => "Teknologi modern menggunakan enzim dan bakteri dapat mempercepat penguraian {$organik} unit sampah organik hingga 10x lebih cepat!",
            'actions' => [
                '✅ <strong>EM4</strong>: Bakteri pengurai alami (Rp 25.000/liter) - Campur 1 tutup EM4 + 1L air + 1 sdm gula merah',
                '✅ <strong>MOL</strong>: Buat sendiri dari buah busuk + gula (GRATIS) - Fermentasi 14 hari',
                '✅ <strong>Orgadec</strong>: Pengurai cepat 7-14 hari',
                '✅ <strong>Bio-Activator Promi</strong>: Hilangkan bau kompos',
                '✅ <strong>Trichoderma</strong>: Jamur pengurai + pupuk hayati'
            ],
            'impact' => "⚡ Pengomposan 10x lebih cepat. Hemat Rp " . number_format($organik * 5000, 0, ',', '.') . "/bulan",
            'color' => 'success'
        ];
        

        if ($organik >= 20) {
            $maggot_revenue = $organik * 50000;
            $recommendations[] = [
                'type' => 'organik',
                'priority' => 'high',
                'icon' => 'bug',
                'title' => '🐛 Budidaya Maggot BSF - Bisnis dari Sampah',
                'description' => "Maggot Black Soldier Fly mengolah sampah jadi pakan ternak + UANG! 10kg sampah → 1kg maggot (Rp 25-40rb/kg) dalam 2 minggu.",
                'actions' => [
                    '💰 Modal: Rp 500.000 (bibit + kandang)',
                    '📦 Jual: 1kg maggot kering = Rp 25-40rb',
                    '🐔 Pakan ayam, ikan, burung (protein 40%)',
                    '💩 Kasgot = pupuk premium',
                    '📱 Beli bibit di Tokopedia/Shopee'
                ],
                'impact' => "💵 Potensi Rp " . number_format($maggot_revenue, 0, ',', '.') . "/bulan + Pupuk gratis!",
                'color' => 'warning'
            ];
        }
        

        $recommendations[] = [
            'type' => 'organik',
            'priority' => 'medium',
                'icon' => 'leaf',
            'title' => '🍋 Eco-Enzyme: Pembersih Gratis dari Sampah',
            'description' => "Kulit buah/sayur → cairan pembersih ramah lingkungan! Resep: 1 gula : 3 kulit buah : 10 air, fermentasi 3 bulan.",
                'actions' => [
                '🧴 Gunakan: Pembersih lantai, cuci piring, pengharum ruangan, pupuk cair',
                '💰 1L eco-enzyme = pengganti deterjen Rp 15.000',
                '🌱 Ampas bisa jadi kompos',
                '💡 Bisa dijual Rp 10-20rb/liter'
            ],
            'impact' => "💰 Hemat Rp " . number_format($organik * 3000, 0, ',', '.') . "/bulan untuk pembersih",
            'color' => 'info'
        ];
        

        $recommendations[] = [
            'type' => 'organik',
            'priority' => 'medium',
            'icon' => 'mountain',
            'title' => '🪱 Vermicomposting: Kompos Super dengan Cacing',
            'description' => "Cacing tanah (Lumbricus rubellus) olah sampah jadi vermicompost (kascing = Rp 50rb/kg) + cacing untuk dijual (Rp 80rb/kg).",
            'actions' => [
                '📦 Modal: Rp 200.000 (box + 1kg cacing)',
                '⚡ 1kg cacing olah 0.5kg sampah/hari',
                '💎 Kascing = pupuk termahal',
                '🔄 Cacing berkembang biak cepat (double tiap 2 bulan)'
            ],
            'impact' => "📈 Income Rp 500rb-1jt/bulan dari kompos + jual cacing",
                'color' => 'success'
        ];
        

        if ($organikPercent > 40) {
            $recommendations[] = [
                'type' => 'organik',
                'priority' => 'high',
                'icon' => 'box',
                'title' => '📦 Komposter Takakura: Kompos Tanpa Bau',
                'description' => "Metode Jepang: kompos di rumah TANPA BAU. Alat: Keranjang bolong 3 susun (Rp 50rb) + sekam + EM4. Matang 3-4 minggu.",
                'actions' => [
                    '🧺 Keranjang 1: Sampah baru + sekam',
                    '🧺 Keranjang 2: Proses penguraian',
                    '🧺 Keranjang 3: Kompos matang',
                    '📚 Pelatihan gratis dari Dinas LH'
                ],
                'impact' => "🏡 {$organik} rumah = {$organik} ton kompos/bulan!",
                'color' => 'primary'
            ];
        }
        

        if ($organik > 50) {
            $biogas_potential = round($organik * 0.6);
            $lpg_saving = $organik * 12000;
            $recommendations[] = [
                'type' => 'organik',
                'priority' => 'high',
                'icon' => 'fire',
                'title' => '🔥 Biogas: Gas Masak Gratis dari Sampah',
                'description' => "Biogas portable (Rp 5-15jt, subsidi 50%) ubah sampah jadi gas memasak. 1 kubik biogas = 0.5L LPG.",
                'actions' => [
                    '🐄 Input: Kotoran ternak (80%) + sampah organik (20%)',
                    '⚡ 1 digester untuk 2-3 keluarga',
                    '💩 Slurry (ampas) = pupuk cair premium',
                    '💰 Daftar subsidi di Kementerian ESDM'
                ],
                'impact' => "💰 Hemat LPG Rp " . number_format($lpg_saving, 0, ',', '.') . "/bulan/unit",
                'color' => 'danger'
            ];
        }
    }
    

    if ($anorganik > 0) {
        $anorganikPercent = round(($anorganik / $total) * 100);
        

        $bank_sampah_income = $anorganik * 18000;
            $recommendations[] = [
                'type' => 'anorganik',
                'priority' => 'high',
            'icon' => 'piggy-bank',
            'title' => '🏦 Bank Sampah: Sampah Jadi Uang',
            'description' => "Warga nabung sampah → dapat uang! Harga: Plastik Rp 2rb/kg, Kertas Rp 1.5rb/kg, Botol Rp 3rb/kg, Kaleng Rp 5rb/kg.",
                'actions' => [
                '💳 Sistem tabungan digital (app gratis: Simbanku, Bank Sampah Indonesia)',
                '🤝 Partner dengan pengepul/industri daur ulang',
                '🎁 Reward: Poin bisa ditukar sembako/pulsa',
                '💰 Modal: Rp 2-5jt (timbangan + tempat sortir)'
            ],
            'impact' => "💵 Pendapatan warga Rp " . number_format($bank_sampah_income, 0, ',', '.') . "/bulan",
                'color' => 'info'
            ];
        

        $recommendations[] = [
            'type' => 'anorganik',
            'priority' => 'medium',
            'icon' => 'cubes',
            'title' => '🧱 Ecobrick: Plastik Jadi Bata Bangunan',
            'description' => "Isi botol plastik dengan sampah plastik bersih sampai padat (500g/botol). Bisa untuk dinding, furniture, gazebo.",
            'actions' => [
                '♻️ Standar: Botol 600ml berisi min 200g plastik',
                '🏗️ 1000 ecobrick = 1 bangunan kecil',
                '🎨 Bisa dicat warna-warni',
                '🌍 Daftar di ecobricks.org'
            ],
            'impact' => "🌱 Kurangi 30-50% plastik ke TPA",
            'color' => 'success'
        ];
        

        $craft_revenue = $anorganik * 8000;
        $recommendations[] = [
            'type' => 'anorganik',
            'priority' => 'medium',
            'icon' => 'palette',
            'title' => '🎨 Kerajinan: Sampah Jadi Seni Bernilai',
            'description' => "Tas dari bungkus kopi (Rp 50-150rb), dompet dari tetra pak (Rp 25-75rb). Pelatihan gratis dari Dekranasda.",
            'actions' => [
                '🏪 Jual online: Shopee, Tokopedia, Instagram',
                '📚 Pelatihan: Dinas Koperasi UMKM, Dekranasda',
                '🏆 Ikut lomba kerajinan (hadiah jutaan)'
            ],
            'impact' => "💰 UMKM omzet Rp " . number_format($craft_revenue, 0, ',', '.') . "/bulan",
            'color' => 'warning'
        ];
        

        $recommendations[] = [
            'type' => 'anorganik',
            'priority' => 'medium',
            'icon' => 'water',
            'title' => '🚰 Refill Station: Stop Sampah dari Sumbernya',
            'description' => "Sediakan isi ulang air minum (Rp 500/L), deterjen (Rp 2rb/L), sabun cuci piring (Rp 3rb/L) di 5-10 warung.",
            'actions' => [
                '🏪 Ajak warung jadi refill station',
                '🎁 Beri tumbler/wadah gratis untuk warga',
                '📊 Target: Kurangi 40% sampah kemasan dalam 1 tahun'
            ],
            'impact' => "🌍 Hemat pengeluaran warga 20-30%",
            'color' => 'primary'
        ];
    }
    

    if ($b3 > 0) {

        $recommendations[] = [
            'type' => 'b3',
            'priority' => 'critical',
            'icon' => 'radiation',
            'title' => '☢️ URGENT: Pengelolaan Sampah B3',
            'description' => "⚠️ {$b3} laporan B3 (baterai, elektronik, lampu, obat) berbahaya! Merkuri → kerusakan otak, Timbal → kanker, Kadmium → gagal ginjal.",
            'actions' => [
                '🏢 Koordinasi WAJIB dengan Dinas Lingkungan Hidup',
                '📦 Drop Box B3 khusus di balai desa (berlabel & terkunci)',
                '🚚 Pick-up B3 rutin 3 bulan sekali',
                '📋 Partner: Waste4Change (021-50202535), TPA B3 resmi'
            ],
            'impact' => "🚨 Cegah pencemaran air tanah & kanker. SEGERA!",
            'color' => 'danger'
        ];
        

        $recommendations[] = [
            'type' => 'b3',
            'priority' => 'high',
            'icon' => 'mobile',
            'title' => '📱 E-Waste: Emas dari Elektronik Bekas',
            'description' => "1 ton e-waste = Rp 15-30 juta (emas, tembaga, platinum). 1 HP = 0.2g emas + 10g tembaga. Partner: E-Wastra, Urban Mining.",
            'actions' => [
                '📦 Drop Box e-waste di balai desa',
                '🔒 Edukasi: Factory reset sebelum buang gadget',
                '♻️ Dapat sertifikat daur ulang untuk desa'
            ],
            'impact' => "💎 Logam mulia + Cegah pencemaran logam berat",
            'color' => 'warning'
        ];
    }
    

    

    if ($total > 50) {
        $recommendations[] = [
            'type' => 'general',
            'priority' => 'high',
            'icon' => 'award',
            'title' => '🏆 Target: Desa Zero Waste',
            'description' => "5 Pilar: Reduce, Reuse, Recycle, Compost, Replace. Target: 80% sampah terkelola di rumah dalam 2 tahun.",
            'actions' => [
                '💡 Infrastruktur: Bank sampah + komposter + biogas + TPST',
                '📚 Edukasi: Sekolah + masjid + PKK',
                '🏅 Penghargaan: Adipura, Adiwiyata Desa, Kampung Iklim',
                '💰 Budget: Rp 50-200jt (dana desa + CSR + hibah)'
            ],
            'impact' => "🌟 Desa percontohan nasional + Hibah + Wisata edukasi",
            'color' => 'success'
        ];
    }
    

    if ($total > 100) {
        $tpst_revenue = $total * 25000;
        $recommendations[] = [
            'type' => 'general',
            'priority' => 'high',
            'icon' => 'industry',
            'title' => '🏭 TPST 3R: Pabrik Sampah Desa',
            'description' => "Modal Rp 200-500jt (dana desa + CSR + hibah). Fasilitas: Sortir + komposter + pencacah + biogas + bank sampah.",
            'actions' => [
                '👷 SDM: 5-10 operator (lapangan kerja baru)',
                '📈 Revenue: Kompos + maggot + daur ulang = Rp 10-30jt/bulan',
                '🌍 ROI: 3-5 tahun'
            ],
            'impact' => "💵 BUMDes! Potensi Rp " . number_format($tpst_revenue, 0, ',', '.') . "/bulan",
            'color' => 'info'
        ];
    }
    

    $recommendations[] = [
        'type' => 'general',
        'priority' => 'medium',
        'icon' => 'chart-line',
        'title' => '📊 Sistem Monitoring Digital (App Ini!)',
        'description' => "Warga lapor via HP → Admin notifikasi → Petugas tindak lanjut. AI klasifikasi otomatis + Dashboard analytics + Export PDF untuk proposal.",
        'actions' => [
            '📱 Real-time monitoring 24/7',
            '🗺️ Peta hotspot sampah',
            '📧 Export PDF untuk Dinas LH/proposal hibah',
            '👥 Grup WhatsApp koordinasi'
        ],
        'impact' => "⚡ Respon 10x cepat + Data akurat + Smart village",
        'color' => 'primary'
    ];
    

    $priorityOrder = ['critical' => 1, 'high' => 2, 'medium' => 3, 'low' => 4];
    usort($recommendations, function($a, $b) use ($priorityOrder) {
        return $priorityOrder[$a['priority']] - $priorityOrder[$b['priority']];
    });
    
    return $recommendations;
}

$aiRecommendations = generateAIRecommendations($organikCount, $anorganikCount, $b3Count, $totalReports);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics & Engine Insight - Admin</title>
    
    
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <link rel="alternate icon" href="../favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="../favicon.svg">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.css" />
    <link rel="stylesheet" href="../assets/css/style.css?v=2.0">
    <link rel="stylesheet" href="../assets/css/vue-components.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        /* Custom Popup Styles */
        .custom-popup .leaflet-popup-content-wrapper {
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            padding: 0;
            overflow: hidden;
        }
        
        .custom-popup .leaflet-popup-content {
            margin: 0;
            padding: 0;
        }
        
        .custom-popup .leaflet-popup-tip {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        /* Map Controls Styling */
        .map-controls button {
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        
        .map-controls button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        /* Legend Responsive */
        @media (max-width: 768px) {
            .map-legend-professional {
                max-width: 200px !important;
                padding: 0.75rem !important;
                font-size: 0.8125rem;
            }
            
            .map-controls {
                flex-direction: row !important;
                flex-wrap: wrap;
                gap: 6px !important;
            }
            
            .map-controls button {
                font-size: 0.75rem;
                padding: 0.5rem 0.75rem;
            }
        }
        
        /* Enhanced Map Container */
        #analyticsMap {
            position: relative;
        }
        
        /* Loading overlay for map */
        .map-loading {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.95);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            border-radius: 0 0 12px 12px;
        }
        
        .map-loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #e5e7eb;
            border-top-color: #14b8a6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        /* ===== PREMIUM DESIGN SYSTEM ===== */
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);
            --info-gradient: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            --warning-gradient: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            --danger-gradient: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            --gold-gradient: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            --glass-bg: rgba(255, 255, 255, 0.95);
            --glass-border: rgba(255, 255, 255, 0.18);
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.08);
            --shadow-md: 0 4px 16px rgba(0,0,0,0.12);
            --shadow-lg: 0 10px 40px rgba(0,0,0,0.15);
            --shadow-xl: 0 20px 60px rgba(0,0,0,0.2);
        }
        
        /* Glass-morphism Card */
        .compact-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            box-shadow: var(--shadow-md);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            margin-bottom: 2rem;
            overflow: hidden;
            position: relative;
        }
        
        .compact-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
            transform: scaleX(0);
            transition: transform 0.4s ease;
        }
        
        .compact-card:hover {
            box-shadow: var(--shadow-xl);
            transform: translateY(-5px);
        }
        
        .compact-card:hover::before {
            transform: scaleX(1);
        }
        
        .recommendation-badge {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-right: 0.5rem;
            text-transform: uppercase;
        }
        
        .badge-critical {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }
        
        .badge-high {
            background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
            color: white;
        }
        
        .badge-medium {
            background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
            color: #000;
        }
        
        .recommendation-item {
            padding: 1.25rem;
            border-left: 4px solid #14b8a6;
            background: linear-gradient(135deg, #f0fdfa 0%, #ffffff 100%);
            border-radius: 12px;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .recommendation-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(20, 184, 166, 0.2);
        }
        
        .recommendation-card-compact {
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .recommendation-title {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            line-height: 1.4;
            color: #1f2937;
        }
        
        .recommendation-description {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 0.75rem;
            line-height: 1.5;
            flex-grow: 1;
        }
        
        .recommendation-actions {
            font-size: 0.8rem;
            color: #4b5563;
            margin-bottom: 0.75rem;
            max-height: 80px;
            overflow: hidden;
            position: relative;
        }
        
        .recommendation-actions::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 20px;
            background: linear-gradient(to bottom, transparent, white);
        }
        
        .recommendation-impact {
            font-size: 0.85rem;
            padding: 0.75rem;
            border-radius: 8px;
            margin-top: auto;
            font-weight: 600;
        }
        
        .recommendation-impact-full {
            font-size: 1.1rem;
            padding: 1.5rem;
            border-radius: 12px;
            margin-top: 1.5rem;
            font-weight: 600;
        }
        
        .action-list-compact {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .action-list-compact li {
            padding: 0.2rem 0;
            font-size: 0.8rem;
            padding-left: 1.2rem;
            position: relative;
        }
        
        .action-list-compact li:before {
            content: "•";
            position: absolute;
            left: 0;
            color: #14b8a6;
            font-weight: bold;
            font-size: 1.2rem;
        }
        
        /* Vue Transitions */
        .stats-fade-enter-active,
        .stats-fade-leave-active {
            transition: all 0.5s ease;
        }
        
        .stats-fade-enter-from {
            opacity: 0;
            transform: translateY(30px) scale(0.9);
        }
        
        .stats-fade-leave-to {
            opacity: 0;
            transform: translateY(-30px) scale(0.9);
        }
        
        .card-grid-enter-active {
            transition: all 0.5s ease;
        }
        
        .card-grid-enter-from {
            opacity: 0;
            transform: translateY(20px) scale(0.95);
        }
        
        .card-grid-move {
            transition: transform 0.5s ease;
        }
        
        .icon-bounce-enter-active {
            animation: bounceIn 0.6s ease;
        }
        
        .icon-bounce-leave-active {
            animation: bounceOut 0.4s ease;
        }
        
        @keyframes bounceIn {
            0% {
                transform: scale(0);
                opacity: 0;
            }
            50% {
                transform: scale(1.2);
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        @keyframes bounceOut {
            0% {
                transform: scale(1);
                opacity: 1;
            }
            100% {
                transform: scale(0);
                opacity: 0;
            }
        }
        
        .badge-scale-enter-active,
        .badge-scale-leave-active {
            transition: all 0.3s ease;
        }
        
        .badge-scale-enter-from,
        .badge-scale-leave-to {
            transform: scale(0);
            opacity: 0;
        }
        
        .slide-up-enter-active {
            transition: all 0.3s ease;
        }
        
        .slide-up-enter-from {
            transform: translateY(10px);
            opacity: 0;
        }
        
        .vue-stat-card {
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .vue-stat-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 12px 40px rgba(0,0,0,0.15);
        }
        
        .stat-icon-large {
            font-size: 3.5rem !important;
            animation: pulse 2s infinite;
            opacity: 1 !important;
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
        }
        
        .stat-card-glow {
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            border-radius: 50%;
            opacity: 0;
            transition: opacity 0.5s ease;
            pointer-events: none;
            filter: blur(40px);
        }
        
        .vue-stat-card:hover .stat-card-glow {
            opacity: 0.6;
        }
        
        .recommendation-item-clickable {
            position: relative;
            overflow: hidden;
            cursor: pointer;
            padding: 1.75rem !important;
            min-height: 400px;
        }
        
        .recommendation-item-clickable::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }
        
        .recommendation-item-clickable:hover::before {
            left: 100%;
        }
        
        .recommendation-item-clickable:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        }
        
        .recommendation-card-full {
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .recommendation-badge-large {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
            margin-right: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .recommendation-type-badge {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            font-size: 0.85rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
        }
        
        .recommendation-title-large {
            font-size: 1.35rem;
            font-weight: 800;
            line-height: 1.4;
            color: #1f2937;
        }
        
        .recommendation-description-large {
            font-size: 1rem;
            color: #4b5563;
            line-height: 1.7;
            margin-bottom: 1rem;
        }
        
        .recommendation-actions-preview {
            background: #f9fafb;
            padding: 1rem;
            border-radius: 12px;
            border-left: 4px solid #14b8a6;
        }
        
        .action-list-preview {
            list-style: none;
            padding: 0;
            margin: 0.5rem 0 0 0;
        }
        
        .action-list-preview li {
            padding: 0.5rem 0;
            font-size: 0.95rem;
            padding-left: 1.5rem;
            position: relative;
            line-height: 1.6;
            color: #374151;
        }
        
        .action-list-preview li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #14b8a6;
            font-weight: bold;
            font-size: 1rem;
        }
        
        .recommendation-impact-preview {
            font-size: 1rem;
            padding: 1rem;
            border-radius: 10px;
            font-weight: 600;
            margin-top: auto;
        }
        
        /* Modal Styles */
        .recommendation-modal {
            max-width: 900px;
        }
        
        .recommendation-modal-body {
            max-height: 70vh;
            overflow-y: auto;
        }
        
        .recommendation-full-content {
            line-height: 1.8;
        }
        
        .recommendation-full-actions {
            background: #f9fafb;
            padding: 1.5rem;
            border-radius: 12px;
            margin: 1.5rem 0;
        }
        
        .recommendation-full-actions ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .recommendation-full-actions li {
            padding: 0.75rem 0;
            font-size: 1rem;
            padding-left: 2rem;
            position: relative;
            line-height: 1.7;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .recommendation-full-actions li:last-child {
            border-bottom: none;
        }
        
        .recommendation-full-actions li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #14b8a6;
            font-weight: bold;
            font-size: 1.2rem;
            top: 0.75rem;
        }
        
        .recommendation-item.type-organik {
            border-left-color: #10b981;
            background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%);
        }
        
        .recommendation-item.type-anorganik {
            border-left-color: #3b82f6;
            background: linear-gradient(135deg, #eff6ff 0%, #ffffff 100%);
        }
        
        .recommendation-item.type-b3 {
            border-left-color: #ef4444;
            background: linear-gradient(135deg, #fef2f2 0%, #ffffff 100%);
        }
        
        .recommendation-item.type-general {
            border-left-color: #8b5cf6;
            background: linear-gradient(135deg, #f5f3ff 0%, #ffffff 100%);
        }
        
        .action-list {
            list-style: none;
            padding: 0;
            margin: 0.5rem 0;
        }
        
        .action-list li {
            padding: 0.4rem 0;
            padding-left: 1.5rem;
            position: relative;
        }
        
        .action-list li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #10b981;
            font-weight: bold;
        }
        
        /* Premium Chart Card */
        .chart-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            box-shadow: var(--shadow-md);
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }
        
        .chart-card:hover {
            box-shadow: var(--shadow-xl);
            transform: translateY(-5px);
        }
        
        .chart-card .card-header {
            background: var(--primary-gradient);
            color: white;
            font-weight: 700;
            font-size: 1.1rem;
            letter-spacing: -0.025em;
            border: none;
            padding: 1.25rem 1.75rem;
            position: relative;
            overflow: hidden;
        }
        
        .chart-card .card-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
            animation: shimmer 3s infinite;
        }
        
        @keyframes shimmer {
            0%, 100% { transform: translate(-50%, -50%) scale(0); opacity: 0; }
            50% { transform: translate(0, 0) scale(1); opacity: 1; }
        }
        
        .chart-card .card-body {
            padding: 2rem;
        }
        
        /* Premium Hotspot Items */
        .hotspot-item {
            padding: 1.5rem;
            border-radius: 16px;
            background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
            border: 2px solid transparent;
            margin-bottom: 1rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .hotspot-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, #14b8a6 0%, #059669 100%);
            opacity: 0;
            transition: opacity 0.4s ease;
            z-index: 0;
        }
        
        .hotspot-item:hover {
            border-color: #14b8a6;
            box-shadow: var(--shadow-lg);
            transform: translateX(5px) scale(1.02);
        }
        
        .hotspot-item:hover::before {
            opacity: 0.05;
        }
        
        .hotspot-item > * {
            position: relative;
            z-index: 1;
        }
        
        .hotspot-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--success-gradient);
            color: white;
            font-weight: 800;
            font-size: 1.25rem;
            margin-right: 1rem;
            box-shadow: 0 4px 12px rgba(20, 184, 166, 0.3);
            transition: all 0.3s ease;
        }
        
        .hotspot-item:hover .hotspot-number {
            transform: rotate(360deg) scale(1.1);
            box-shadow: 0 6px 20px rgba(20, 184, 166, 0.5);
        }
        
        /* Premium Stat Mini Badges */
        .stat-mini {
            display: inline-flex;
            align-items: center;
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            margin: 0.25rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        
        .stat-mini:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .stat-mini.organik {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
        }
        
        .stat-mini.anorganik {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
        }
        
        .stat-mini.b3 {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
        }
        
        /* Premium Nav Pills */
        .nav-pills .nav-link {
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-weight: 700;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .nav-pills .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }
        
        .nav-pills .nav-link:hover {
            background: linear-gradient(135deg, #f0fdfa 0%, #ccfbf1 100%);
            color: #14b8a6;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(20, 184, 166, 0.2);
        }
        
        .nav-pills .nav-link:hover::before {
            left: 100%;
        }
        
        .nav-pills .nav-link.active {
            background: var(--success-gradient);
            color: white;
            box-shadow: 0 6px 20px rgba(20, 184, 166, 0.4);
        }
        
        /* Premium Button Group */
        .btn-group .btn {
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.3);
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .btn-group .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);
        }
        
        .btn-group .btn.active {
            background: var(--info-gradient) !important;
            border-color: transparent !important;
            color: white !important;
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
            transform: translateY(-3px);
        }
        
        /* Dropdown Styling */
        .dropdown-menu {
            background: white;
            border: 1px solid rgba(20, 184, 166, 0.2);
        }
        
        .dropdown-item {
            padding: 0.75rem 1.25rem;
            border-radius: 8px;
            margin: 0.25rem 0;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            font-weight: 500;
        }
        
        .dropdown-item:hover {
            background: linear-gradient(135deg, #f0fdfa 0%, #e0f2fe 100%);
            color: #14b8a6;
            transform: translateX(5px);
        }
        
        .dropdown-item.active {
            background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
            color: white;
        }
        
        .dropdown-item.active i {
            color: white !important;
        }
        
        .dropdown-toggle::after {
            margin-left: 0.75rem;
        }
        
        /* Premium Background Pattern */
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e3e8ef 100%);
            background-attachment: fixed;
            position: relative;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                radial-gradient(circle at 20% 50%, rgba(20, 184, 166, 0.03) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(59, 130, 246, 0.03) 0%, transparent 50%),
                radial-gradient(circle at 40% 20%, rgba(239, 68, 68, 0.03) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
        }
        
        .container-fluid {
            position: relative;
            z-index: 1;
        }
        
        /* Premium Table Styling */
        .table {
            border-collapse: separate;
            border-spacing: 0 0.5rem;
        }
        
        .table thead tr {
            background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .table thead th {
            border: none;
            padding: 1rem;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            color: #6b7280;
        }
        
        .table tbody tr {
            background: white;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .table tbody tr:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
        }
        
        .table tbody td {
            border: none;
            padding: 1rem;
            vertical-align: middle;
        }
        
        /* Premium Badge Styling */
        .badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            letter-spacing: 0.025em;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .badge:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .badge-organik {
            background: var(--success-gradient);
            color: white;
        }
        
        .badge-anorganik {
            background: var(--info-gradient);
            color: white;
        }
        
        .badge-b3 {
            background: var(--danger-gradient);
            color: white;
        }
        
        /* Gold Medal Effect */
        .table tbody tr:first-child {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            box-shadow: 0 4px 20px rgba(245, 158, 11, 0.3);
        }
        
        .table tbody tr:first-child:hover {
            box-shadow: 0 6px 30px rgba(245, 158, 11, 0.5);
        }
        
        /* Advanced Animations */
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .fade-in {
            animation: slideInUp 0.6s ease-out backwards;
        }
        
        /* Smooth Scrollbar */
        ::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #14b8a6 0%, #059669 100%);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
        }
        
        /* Alert Premium */
        .alert {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border-left: 4px solid;
        }
        
        .alert-info {
            background: linear-gradient(135deg, #e0f2fe 0%, #dbeafe 100%);
            border-left-color: #3b82f6;
        }
        
        .alert-warning {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-left-color: #f59e0b;
        }
        
        /* Responsive Improvements */
        @media (max-width: 992px) {
            .chart-card .card-body {
                padding: 1.5rem;
            }
            
            .hotspot-item {
                padding: 1rem;
            }
            
            .btn-group {
                flex-wrap: wrap;
            }
            
            .btn-group .btn {
                flex: 1;
                min-width: 120px;
                margin-bottom: 0.5rem;
            }
        }
        
        @media (max-width: 768px) {
            .compact-card {
                border-radius: 16px;
                margin-bottom: 1.5rem;
            }
            
            .stat-mini {
                font-size: 0.75rem;
                padding: 0.3rem 0.6rem;
            }
            
            .hotspot-number {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }
            
            .nav-pills .nav-link {
                padding: 0.6rem 1rem;
                font-size: 0.875rem;
            }
        }
        
        /* Print Styles - Professional PDF Layout */
        @media print {
            @page {
                size: A4; /* Portrait fits better for reports generally, or A4 landscape for dashboards */
                margin: 0.5cm;
            }
            
            html, body {
                width: 100%;
                height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
                overflow: visible !important;
                background: white !important;
                color: #000 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            
            /* Hide UI elements */
            .navbar, 
            .btn, 
            .no-print, 
            .dropdown,
            .map-controls,
            footer,
            .recommendation-section .dropdown,
            #filterDropdown {
                display: none !important;
            }

            /* Container fixes */
            .container-fluid {
                padding: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
                width: 100% !important;
            }
            
            /* PDF Header */
            .pdf-header {
                display: block !important;
                border-bottom: 2px solid #14b8a6;
                padding-bottom: 10px;
                margin-bottom: 20px;
                text-align: center;
            }
            
            .pdf-header h2 {
                color: #1f2937 !important;
                font-size: 18pt;
                font-weight: bold;
                margin: 0;
            }
            
            .pdf-date {
                font-size: 10pt;
                color: #6b7280;
                margin-top: 5px;
            }
            
            /* Grid System for Print */
            .row {
                display: flex !important;
                flex-wrap: wrap !important;
                margin-right: -10px !important;
                margin-left: -10px !important;
            }
            
            .col-xl-3, .col-lg-6, .col-md-6, .col-lg-8, .col-lg-4, .col-lg-7, .col-lg-5, .col-12 {
                position: relative;
                min-height: 1px;
                padding-right: 10px !important;
                padding-left: 10px !important;
                page-break-inside: avoid;
            }
            
            /* Stats Cards Row (4 across) */
            .row.g-3 .col-xl-3 {
                flex: 0 0 25%;
                max-width: 25%;
            }

            /* Charts Row */
            .col-lg-8 {
                width: 60% !important;
                float: left;
                flex: 0 0 60% !important;
                max-width: 60% !important;
            }
            .col-lg-4 {
                width: 35% !important;
                float: left;
                flex: 0 0 35% !important;
                max-width: 35% !important;
                margin-left: 2%;
            }

            /* Map & Hotspots */
            .col-12 {
                width: 100% !important;
                flex: 0 0 100% !important;
                max-width: 100% !important;
                clear: both;
            }
            
            /* Cards Styling */
            .card, .compact-card, .chart-card {
                border: 1px solid #ddd !important;
                box-shadow: none !important;
                margin-bottom: 20px !important;
                background: white !important;
                page-break-inside: avoid;
                break-inside: avoid;
                display: block !important; /* Ensure block display */
            }
            
            .card-header {
                background: #f8f9fa !important;
                border-bottom: 1px solid #ddd !important;
                color: #000 !important;
                padding: 10px 15px !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            
            .card-body {
                padding: 15px !important;
                background: white !important;
            }
            
            /* FORCE BLACK TEXT ON ALL CARD ELEMENTS */
            .card-body *,
            .compact-card *, 
            .vue-stat-card *,
            h3, h5, p, small, span, i, div {
                color: #000 !important;
                text-shadow: none !important;
                opacity: 1 !important;
            }

            /* Chart Specifics */
            .chart-container-print {
                position: relative;
                width: 100% !important;
                height: auto !important;
            }

            canvas {
                width: 100% !important;
                height: auto !important;
                min-height: 200px !important; /* Ensure minimum visibility */
                max-height: 350px !important;
                display: block !important;
                overflow: visible !important;
            }

            /* Typography */
            h5 { font-size: 14pt !important; font-weight: bold; }
            h3 { font-size: 20pt !important; }
            p, span, div { font-size: 10pt !important; }
            small { font-size: 8pt !important; }
            
            /* Table */
            .table { width: 100% !important; border-collapse: collapse !important; }
            .table td, .table th { border: 1px solid #eee !important; padding: 4px !important; }
        }
        
        /* Loading Animation */
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .loading {
            animation: spin 1s linear infinite;
        }
    </style>
</head>
<body>
    
    <?php include 'includes/navbar.php'; ?>

    
    <div class="container-fluid mt-4 mb-5" id="analyticsApp">
        
        <div class="pdf-header" style="display: none;">
            <h2>
                <i class="fas fa-brain" style="color: #14b8a6;"></i>
                Analytics & Laporan Wawasan Lingkungan
            </h2>
            <div class="pdf-date">
                <i class="fas fa-calendar-alt"></i>
                Dicetak pada: <?php echo date('d F Y, H:i:s'); ?> WIB
            </div>
            <div class="pdf-date">
                <i class="fas fa-filter"></i>
                Periode: <span id="pdf-period-label">6 Bulan Terakhir</span>
            </div>
        </div>
        
        
        <div class="row align-items-center mb-4 fade-in no-print">
            <div class="col-md-6">
                <h2 class="mb-2" style="color: #1f2937; font-weight: 700; font-size: 1.875rem;">
                    <i class="fas fa-brain" style="color: #14b8a6; margin-right: 0.5rem;"></i>
                    Analytics & Insight Strategis
                </h2>
                <p class="text-muted mb-0">
                    <i class="fas fa-chart-bar me-2"></i>Analisis data mendalam & rekomendasi otomatis untuk pengelolaan sampah desa
                </p>
            </div>
            <div class="col-md-6 text-end">
                <div class="d-inline-block me-3">
                    <div class="dropdown">
                        <button class="btn btn-primary dropdown-toggle" 
                                type="button" 
                                id="periodDropdown" 
                                data-bs-toggle="dropdown" 
                                aria-expanded="false" 
                                style="min-width: 180px; box-shadow: 0 4px 12px rgba(20, 184, 166, 0.3);">
                            <i class="fas fa-calendar-alt me-2"></i>
                            <span id="periodDropdownText">6 Bulan Terakhir</span>
                </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg" 
                            aria-labelledby="periodDropdown" 
                            style="border-radius: 12px; border: none; padding: 0.5rem; min-width: 200px;">
                            <li>
                                <a class="dropdown-item period-option" 
                                   href="javascript:void(0)" 
                                   onclick="changePeriod('1')"
                                   data-period="1">
                                    <i class="fas fa-calendar-day text-primary me-2"></i>1 Bulan Terakhir
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item period-option" 
                                   href="javascript:void(0)" 
                                   onclick="changePeriod('3')"
                                   data-period="3">
                                    <i class="fas fa-calendar-week text-info me-2"></i>3 Bulan Terakhir
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item period-option active" 
                                   href="javascript:void(0)" 
                                   onclick="changePeriod('6')"
                                   data-period="6">
                                    <i class="fas fa-calendar-alt text-success me-2"></i>6 Bulan Terakhir
                                    <i class="fas fa-check ms-auto text-success"></i>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item period-option" 
                                   href="javascript:void(0)" 
                                   onclick="changePeriod('12')"
                                   data-period="12">
                                    <i class="fas fa-calendar text-warning me-2"></i>1 Tahun Terakhir
                                </a>
                            </li>
                        </ul>
                </div>
                </div>
                <button class="btn btn-outline-primary" 
                        onclick="printPage()"
                        style="border-radius: 10px;">
                    <i class="fas fa-print me-1"></i> Cetak PDF
                </button>
            </div>
        </div>

        
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-lg-6 col-md-6 fade-in" style="animation-delay: 0.1s;">
                <div class="compact-card h-100" style="overflow: hidden; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <div class="card-body text-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem;">
                        <div class="d-flex justify-content-center align-items-center mb-3">
                            <i class="fas fa-database" style="font-size: 2.5rem; opacity: 0.9;"></i>
                </div>
                        <h3 class="mb-2" style="font-size: 2.5rem; font-weight: 800; line-height: 1;" id="stat-total"><?php echo $stats6Months['total']; ?></h3>
                        <p class="mb-1" style="font-size: 1rem; font-weight: 600; opacity: 0.95;">Total Laporan</p>
                        <small class="opacity-75" style="font-size: 0.85rem;" id="stat-period-label">6 Bulan Terakhir</small>
            </div>
                    <div class="stat-card-glow" style="background: rgba(102, 126, 234, 0.3);"></div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6 fade-in" style="animation-delay: 0.2s;">
                <div class="compact-card h-100" style="overflow: hidden; background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                    <div class="card-body text-center" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 2rem;">
                        <div class="d-flex justify-content-center align-items-center mb-3">
                            <i class="fas fa-leaf" style="font-size: 2.5rem; opacity: 0.9;"></i>
                </div>
                        <h3 class="mb-2" style="font-size: 2.5rem; font-weight: 800; line-height: 1;" id="stat-organik-percent"><?php echo $stats6Months['organik_percent']; ?>%</h3>
                        <p class="mb-1" style="font-size: 1rem; font-weight: 600; opacity: 0.95;">Organik</p>
                        <small class="opacity-75" style="font-size: 0.85rem;" id="stat-organik-count"><?php echo $stats6Months['organik']; ?> laporan</small>
            </div>
                    <div class="stat-card-glow" style="background: rgba(16, 185, 129, 0.3);"></div>
                    </div>
                </div>
            <div class="col-xl-3 col-lg-6 col-md-6 fade-in" style="animation-delay: 0.3s;">
                <div class="compact-card h-100" style="overflow: hidden; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
                    <div class="card-body text-center" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; padding: 2rem;">
                        <div class="d-flex justify-content-center align-items-center mb-3">
                            <i class="fas fa-recycle" style="font-size: 2.5rem; opacity: 0.9;"></i>
            </div>
                        <h3 class="mb-2" style="font-size: 2.5rem; font-weight: 800; line-height: 1;" id="stat-anorganik-percent"><?php echo $stats6Months['anorganik_percent']; ?>%</h3>
                        <p class="mb-1" style="font-size: 1rem; font-weight: 600; opacity: 0.95;">Anorganik</p>
                        <small class="opacity-75" style="font-size: 0.85rem;" id="stat-anorganik-count"><?php echo $stats6Months['anorganik']; ?> laporan</small>
                    </div>
                    <div class="stat-card-glow" style="background: rgba(59, 130, 246, 0.3);"></div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6 fade-in" style="animation-delay: 0.4s;">
                <div class="compact-card h-100" style="overflow: hidden; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                    <div class="card-body text-center" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; padding: 2rem;">
                        <div class="d-flex justify-content-center align-items-center mb-3">
                            <i class="fas fa-exclamation-triangle" style="font-size: 2.5rem; opacity: 0.9;"></i>
                        </div>
                        <h3 class="mb-2" style="font-size: 2.5rem; font-weight: 800; line-height: 1;" id="stat-b3-percent"><?php echo $stats6Months['b3_percent']; ?>%</h3>
                        <p class="mb-1" style="font-size: 1rem; font-weight: 600; opacity: 0.95;">B3 Berbahaya</p>
                        <small class="opacity-75" style="font-size: 0.85rem;" id="stat-b3-count"><?php echo $stats6Months['b3']; ?> laporan</small>
                    </div>
                    <div class="stat-card-glow" style="background: rgba(239, 68, 68, 0.3);"></div>
                </div>
            </div>
        </div>

        
        <div class="row g-4 mb-4 no-break">
            
            <div class="col-lg-8">
                <div class="chart-card no-break">
                    <div class="card-header">
                        <i class="fas fa-chart-line me-2"></i><span id="chart-title">Tren Laporan 6 Bulan Terakhir</span>
                    </div>
                    <div class="card-body">
                        <canvas id="monthlyTrendChart" height="80"></canvas>
                        </div>
                    </div>
                </div>
            
            
            <div class="col-lg-4">
                <div class="chart-card no-break">
                    <div class="card-header">
                        <i class="fas fa-chart-pie me-2"></i><span id="pie-title">Distribusi Kategori</span>
                    </div>
                    <div class="card-body">
                        <canvas id="categoryPieChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="row mb-4 no-break">
            <div class="col-12">
                <div class="compact-card no-break" style="overflow: hidden;">
                    <div class="card-header" style="background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%); color: white; border: none;">
                        <h5 class="mb-0" style="font-weight: 700;">
                            <i class="fas fa-map-marked-alt me-2"></i>Peta Persebaran Sampah - Visualisasi Profesional
                        </h5>
                        <small class="text-white opacity-75">
                            <i class="fas fa-info-circle me-1"></i> 
                            Interaktif map dengan clustering marker dan heatmap untuk analisis distribusi geografis
                        </small>
                    </div>
                    <div class="card-body p-0" style="position: relative;">
                        
                        <div class="map-controls" style="position: absolute; top: 15px; right: 15px; z-index: 1000; display: flex; flex-direction: column; gap: 10px;">
                            <button class="btn btn-sm btn-light shadow-sm" onclick="toggleHeatmap()" style="border: none; font-weight: 600;">
                                <i class="fas fa-fire me-1"></i>Toggle Heatmap
                            </button>
                            <button class="btn btn-sm btn-light shadow-sm" onclick="resetMapView()" style="border: none; font-weight: 600;">
                                <i class="fas fa-home me-1"></i>Reset View
                            </button>
                            <button class="btn btn-sm btn-light shadow-sm" onclick="toggleFullscreen()" style="border: none; font-weight: 600;">
                                <i class="fas fa-expand me-1"></i>Fullscreen
                            </button>
                        </div>
                        
                        
                        <div class="map-legend-professional" style="position: absolute; bottom: 20px; left: 20px; z-index: 1000; background: white; padding: 1rem; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); max-width: 250px;">
                            <h6 class="mb-3" style="font-weight: 700; color: #1f2937; font-size: 0.95rem;">
                                <i class="fas fa-legend me-2" style="color: #14b8a6;"></i>Legenda
                            </h6>
                            <div class="legend-items" style="display: flex; flex-direction: column; gap: 0.75rem;">
                                <div class="legend-item" style="display: flex; align-items: center; gap: 0.5rem;">
                                    <div style="width: 24px; height: 24px; border-radius: 50%; background: linear-gradient(135deg, #10b981, #059669); border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.2);"></div>
                                    <span style="font-size: 0.875rem; color: #374151; font-weight: 500;">Organik</span>
                                </div>
                                <div class="legend-item" style="display: flex; align-items: center; gap: 0.5rem;">
                                    <div style="width: 24px; height: 24px; border-radius: 50%; background: linear-gradient(135deg, #3b82f6, #2563eb); border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.2);"></div>
                                    <span style="font-size: 0.875rem; color: #374151; font-weight: 500;">Anorganik</span>
                                </div>
                                <div class="legend-item" style="display: flex; align-items: center; gap: 0.5rem;">
                                    <div style="width: 24px; height: 24px; border-radius: 50%; background: linear-gradient(135deg, #ef4444, #dc2626); border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.2);"></div>
                                    <span style="font-size: 0.875rem; color: #374151; font-weight: 500;">B3 Berbahaya</span>
                                </div>
                                <hr style="margin: 0.5rem 0;">
                                <div class="legend-item" style="display: flex; align-items: center; gap: 0.5rem;">
                                    <div style="width: 100%; height: 8px; background: linear-gradient(90deg, rgba(16, 185, 129, 0.3), rgba(239, 68, 68, 0.8)); border-radius: 4px;"></div>
                                    <span style="font-size: 0.75rem; color: #6b7280;">Heatmap Density</span>
                                </div>
                            </div>
                        </div>
                        
                        <div id="analyticsMap" style="height: 600px; width: 100%; border-radius: 0 0 12px 12px;"></div>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="row mb-4 no-break">
            <div class="col-12">
                <div class="compact-card no-break">
                    <div class="card-body">
                        <h5 class="mb-4" style="color: #1f2937; font-weight: 700;">
                            <i class="fas fa-fire text-danger me-2"></i>Top 5 Hotspot Lokasi Sampah
                        </h5>
                        <div class="row g-3">
                            <?php foreach ($hotspots as $index => $hotspot): ?>
                            <div class="col-lg-4 col-md-6">
                                <div class="hotspot-item">
                                    <div class="d-flex align-items-start">
                                        <span class="hotspot-number">#<?php echo $index + 1; ?></span>
                                        <div class="flex-grow-1">
                                            <div class="fw-bold text-dark mb-2">
                                                <i class="fas fa-map-marker-alt text-danger me-1"></i>
                                                <?php echo htmlspecialchars(substr($hotspot['address'], 0, 40)); ?><?php echo strlen($hotspot['address']) > 40 ? '...' : ''; ?>
                        </div>
                                            <div class="text-muted small mb-2">
                                                <i class="fas fa-globe me-1"></i>
                                                <?php echo number_format($hotspot['lat'], 6); ?>, <?php echo number_format($hotspot['lng'], 6); ?>
                                            </div>
                                            <div class="mb-2">
                                                <span class="stat-mini organik">
                                                    <i class="fas fa-leaf me-1"></i><?php echo $hotspot['organik']; ?>
                                                </span>
                                                <span class="stat-mini anorganik">
                                                    <i class="fas fa-recycle me-1"></i><?php echo $hotspot['anorganik']; ?>
                                                </span>
                                                <span class="stat-mini b3">
                                                    <i class="fas fa-exclamation-triangle me-1"></i><?php echo $hotspot['b3']; ?>
                                            </span>
                                        </div>
                                            <div class="mt-2">
                                                <strong style="color: #14b8a6; font-size: 1.1rem;">
                                                    <i class="fas fa-file-alt me-1"></i><?php echo $hotspot['count']; ?> laporan
                                                </strong>
                                    </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="row g-4 mb-4 no-break">
            
            <div class="col-lg-7">
                <div class="chart-card no-break">
                    <div class="card-header">
                        <i class="fas fa-chart-bar me-2"></i>Top 10 Jenis Sampah Spesifik di Desa
                    </div>
                    <div class="card-body">
                        <canvas id="wasteTypesChart" height="100"></canvas>
                        <div class="alert alert-info mt-3 mb-0 no-print">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Insight:</strong> Data ini menunjukkan jenis sampah paling banyak dilaporkan warga. Gunakan untuk fokus program pengelolaan sampah.
                        </div>
                    </div>
                            </div>
                        </div>
                        
            
            <div class="col-lg-5">
                <div class="compact-card h-100 no-break">
                    <div class="card-body">
                        <h5 class="mb-3" style="color: #1f2937; font-weight: 700;">
                            <i class="fas fa-trophy text-warning me-2"></i>Ranking Jenis Sampah
                        </h5>
                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-sm mb-0">
                                <thead class="sticky-top" style="background: white; z-index: 10;">
                                    <tr>
                                        <th style="width: 50px;">#</th>
                                        <th>Jenis Sampah</th>
                                        <th class="text-center" style="width: 80px;">Jumlah</th>
                                        <th class="text-center" style="width: 80px;">%</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                    <?php foreach ($topWasteTypes as $index => $waste): ?>
                                    <tr>
                                        <td class="fw-bold">
                                            <?php if ($index === 0): ?>
                                                <span style="color: #f59e0b;">🥇</span>
                                            <?php elseif ($index === 1): ?>
                                                <span style="color: #9ca3af;">🥈</span>
                                            <?php elseif ($index === 2): ?>
                                                <span style="color: #cd7f32;">🥉</span>
                                            <?php else: ?>
                                                <?php echo $index + 1; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo $waste['kategori']; ?> me-1" style="font-size: 0.7rem;">
                                                <?php echo strtoupper(substr($waste['kategori'], 0, 3)); ?>
                                                    </span>
                                            <strong><?php echo htmlspecialchars($waste['name']); ?></strong>
                                                </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary"><?php echo $waste['count']; ?></span>
                                                </td>
                                        <td class="text-center">
                                            <strong style="color: #14b8a6;"><?php echo $waste['percent']; ?>%</strong>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

        
        <div class="row mb-4 no-break">
            <div class="col-12">
                <div class="compact-card no-break">
                    <div class="card-body">
                        <h5 class="mb-4" style="color: #1f2937; font-weight: 700;">
                            <i class="fas fa-layer-group me-2"></i>Breakdown Jenis Sampah per Kategori
                        </h5>
                        <div class="row g-3">
                            
                            <div class="col-lg-4">
                                <div class="card border-success h-100">
                                    <div class="card-header" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none;">
                                        <i class="fas fa-leaf me-2"></i><strong>Organik</strong>
                                    </div>
                                    <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                                        <?php if (!empty($wasteTypesByCategory['organik'])): ?>
                                            <?php foreach ($wasteTypesByCategory['organik'] as $jenis => $count): ?>
                                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                                <span class="text-dark"><?php echo htmlspecialchars($jenis); ?></span>
                                                <span class="badge bg-success"><?php echo $count; ?></span>
                                            </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <p class="text-muted text-center mb-0">Tidak ada data</p>
                                        <?php endif; ?>
                                    </div>
            </div>
        </div>

                            
                            <div class="col-lg-4">
                                <div class="card border-primary h-100">
                                    <div class="card-header" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none;">
                                        <i class="fas fa-recycle me-2"></i><strong>Anorganik</strong>
                                    </div>
                                    <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                                        <?php if (!empty($wasteTypesByCategory['anorganik'])): ?>
                                            <?php foreach ($wasteTypesByCategory['anorganik'] as $jenis => $count): ?>
                                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                                <span class="text-dark"><?php echo htmlspecialchars($jenis); ?></span>
                                                <span class="badge bg-primary"><?php echo $count; ?></span>
                                            </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <p class="text-muted text-center mb-0">Tidak ada data</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            
                            <div class="col-lg-4">
                                <div class="card border-danger h-100">
                                    <div class="card-header" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; border: none;">
                                        <i class="fas fa-exclamation-triangle me-2"></i><strong>B3 (Berbahaya)</strong>
                    </div>
                                    <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                                        <?php if (!empty($wasteTypesByCategory['b3'])): ?>
                                            <?php foreach ($wasteTypesByCategory['b3'] as $jenis => $count): ?>
                                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                                <span class="text-dark"><?php echo htmlspecialchars($jenis); ?></span>
                                                <span class="badge bg-danger"><?php echo $count; ?></span>
                                            </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <p class="text-muted text-center mb-0">Tidak ada data</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
                        </div>

        
        
    </div>
    
        
        <div class="row recommendation-section">
            
        </div>
        
        
        <div class="pdf-footer" style="display: none;">
            <div>Laporan Analytics - Sistem Pelaporan Sampah Desa | Halaman <span class="page-number"></span></div>
        </div>
    </div>

    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <script src="../assets/js/vue-components.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <script>

        const periodData = {
            '1': {
                labels: <?php echo json_encode($data1Month['labels']); ?>,
                organik: <?php echo json_encode($data1Month['organik']); ?>,
                anorganik: <?php echo json_encode($data1Month['anorganik']); ?>,
                b3: <?php echo json_encode($data1Month['b3']); ?>,
                stats: <?php echo json_encode($stats1Month); ?>,
                title: '1 Bulan Terakhir',
                label: '1 Bulan Terakhir'
            },
            '3': {
                labels: <?php echo json_encode($data3Months['labels']); ?>,
                organik: <?php echo json_encode($data3Months['organik']); ?>,
                anorganik: <?php echo json_encode($data3Months['anorganik']); ?>,
                b3: <?php echo json_encode($data3Months['b3']); ?>,
                stats: <?php echo json_encode($stats3Months); ?>,
                title: '3 Bulan Terakhir',
                label: '3 Bulan Terakhir'
            },
            '6': {
                labels: <?php echo json_encode($data6Months['labels']); ?>,
                organik: <?php echo json_encode($data6Months['organik']); ?>,
                anorganik: <?php echo json_encode($data6Months['anorganik']); ?>,
                b3: <?php echo json_encode($data6Months['b3']); ?>,
                stats: <?php echo json_encode($stats6Months); ?>,
                title: '6 Bulan Terakhir',
                label: '6 Bulan Terakhir'
            },
            '12': {
                labels: <?php echo json_encode($data12Months['labels']); ?>,
                organik: <?php echo json_encode($data12Months['organik']); ?>,
                anorganik: <?php echo json_encode($data12Months['anorganik']); ?>,
                b3: <?php echo json_encode($data12Months['b3']); ?>,
                stats: <?php echo json_encode($stats12Months); ?>,
                title: '1 Tahun Terakhir',
                label: '1 Tahun Terakhir'
            }
        };
        

        window.addEventListener('beforeprint', function() {
            document.querySelectorAll('.page-number').forEach((el, index) => {
                el.textContent = (index + 1);
            });

            document.querySelectorAll('.pdf-header, .pdf-footer').forEach(el => {
                el.style.display = 'block';
            });
        });
        
        window.addEventListener('afterprint', function() {

            document.querySelectorAll('.pdf-header, .pdf-footer').forEach(el => {
                el.style.display = 'none';
            });
        });


        function generateEngineInsight() {
            const btn = document.getElementById('btn-ask-engine');
            const container = document.getElementById('engine-insight-container');
            const textEl = document.getElementById('engine-insight-text');
            const loader = document.getElementById('engine-loading');
            const icon = document.getElementById('engine-icon');
            

            container.style.display = 'block';
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses...';
            loader.style.display = 'inline-block';
            icon.style.display = 'none';
            textEl.innerHTML = 'Sedang menganalisis data statistik daur ulang terbaru...';
            

            const currentStats = periodData[currentPeriod].stats; // Get stats for current selected period

            const topItems = [
                <?php foreach(array_slice($topWasteTypes, 0, 3) as $t) echo "'" . addslashes($t['name']) . "',"; ?>
            ];
            
            fetch('../api/generate_report_insight.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    stats: currentStats,
                    topItems: topItems
                })
            })
            .then(async response => {
                const text = await response.text();
                try {
                    const data = JSON.parse(text);
                    if (!response.ok) {
                        throw new Error(data.error || 'Server Error: ' + response.statusText);
                    }
                    return data;
                } catch (e) {
                    console.error('Raw Response:', text);
                    throw new Error('Invalid Server Response: ' + text.substring(0, 50) + '...');
                }
            })
            .then(data => {
                if (data.success) {

                    textEl.innerHTML = '';
                    const text = data.insight;
                    let i = 0;
                    const speed = 20; // ms
                    function typeWriter() {
                        if (i < text.length) {
                            textEl.innerHTML += text.charAt(i);
                            i++;
                            setTimeout(typeWriter, speed);
                        }
                    }
                    typeWriter();
                } else {
                    textEl.innerHTML = 'Maaf, sistem sedang sibuk. (' + (data.error || 'Unknown error') + ')';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                textEl.innerHTML = 'Gagal: ' + error.message;
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-magic me-2 text-warning"></i>Generate Wawasan (Live)';
                loader.style.display = 'none';
                icon.style.display = 'inline-block';
            });
        }

        function closeEngineInsight() {
            document.getElementById('engine-insight-container').style.display = 'none';
        }

    </script>
    
    <script>

        document.addEventListener('DOMContentLoaded', function() {
            const filterOptions = document.querySelectorAll('.filter-option');
            const filterLabel = document.getElementById('filterLabel');
            const recommendationItems = document.querySelectorAll('.recommendation-item');
            
            filterOptions.forEach(option => {
                option.addEventListener('click', function(e) {
                    e.preventDefault();
                    

                    filterOptions.forEach(opt => opt.classList.remove('active'));
                    this.classList.add('active');
                    

                    const filterText = this.textContent.trim();
                    if (filterLabel) {
                        filterLabel.textContent = filterText;
                    }
                    

                    const filter = this.getAttribute('data-filter');
                    

                    recommendationItems.forEach(item => {
                        if (filter === 'all') {
                            item.style.display = '';
                        } else {
                            const itemType = item.getAttribute('data-type');
                            if (itemType === filter) {
                                item.style.display = '';
                            } else {
                                item.style.display = 'none';
                            }
                        }
                    });
                });
            });
        });
        

        function changePeriod(period) {
            if (currentPeriod === period) return;
            
            currentPeriod = period;
            const periodLabels = {
                '1': '1 Bulan Terakhir',
                '3': '3 Bulan Terakhir',
                '6': '6 Bulan Terakhir',
                '12': '1 Tahun Terakhir'
            };
            

            const dropdownText = document.getElementById('periodDropdownText');
            if (dropdownText) {
                dropdownText.textContent = periodLabels[period];
            }
            

            document.querySelectorAll('.period-option').forEach(option => {
                if (option.getAttribute('data-period') === period) {
                    option.classList.add('active');
                    if (!option.querySelector('.fa-check')) {
                        const check = document.createElement('i');
                        check.className = 'fas fa-check ms-auto text-success';
                        option.appendChild(check);
                    }
                } else {
                    option.classList.remove('active');
                    const check = option.querySelector('.fa-check');
                    if (check) check.remove();
                }
            });
            

            if (typeof periodData !== 'undefined' && periodData[period]) {
                const stats = periodData[period].stats;
                
                const totalEl = document.getElementById('stat-total');
                const organikEl = document.getElementById('stat-organik-percent');
                const anorganikEl = document.getElementById('stat-anorganik-percent');
                const b3El = document.getElementById('stat-b3-percent');
                const periodLabelEl = document.getElementById('stat-period-label');
                const organikCountEl = document.getElementById('stat-organik-count');
                const anorganikCountEl = document.getElementById('stat-anorganik-count');
                const b3CountEl = document.getElementById('stat-b3-count');
                
                if (totalEl) totalEl.textContent = stats.total;
                if (organikEl) organikEl.textContent = stats.organik_percent + '%';
                if (anorganikEl) anorganikEl.textContent = stats.anorganik_percent + '%';
                if (b3El) b3El.textContent = stats.b3_percent + '%';
                if (periodLabelEl) periodLabelEl.textContent = periodData[period].label;
                if (organikCountEl) organikCountEl.textContent = stats.organik + ' laporan';
                if (anorganikCountEl) anorganikCountEl.textContent = stats.anorganik + ' laporan';
                if (b3CountEl) b3CountEl.textContent = stats.b3 + ' laporan';
            }
            

            if (typeof monthlyChart !== 'undefined' && monthlyChart && periodData[period]) {
                const data = periodData[period];
                monthlyChart.data.labels = data.labels;
                monthlyChart.data.datasets[0].data = data.organik;
                monthlyChart.data.datasets[1].data = data.anorganik;
                monthlyChart.data.datasets[2].data = data.b3;
                monthlyChart.update();
            }
            
            if (typeof pieChart !== 'undefined' && pieChart && periodData[period]) {
                const data = periodData[period];
                pieChart.data.datasets[0].data = [
                    data.stats.organik,
                    data.stats.anorganik,
                    data.stats.b3
                ];
                pieChart.update();
            }
            

            const chartTitle = document.getElementById('chart-title');
            if (chartTitle && periodData[period]) {
                chartTitle.textContent = 'Tren Laporan ' + periodData[period].title;
            }
        }
        

        function printPage() {
            const pdfPeriodLabel = document.getElementById('pdf-period-label');
            if (pdfPeriodLabel) {
                const periodLabels = {
                    '1': '1 Bulan Terakhir',
                    '3': '3 Bulan Terakhir',
                    '6': '6 Bulan Terakhir',
                    '12': '1 Tahun Terakhir'
                };
                pdfPeriodLabel.textContent = periodLabels[currentPeriod] || '6 Bulan Terakhir';
            }
            setTimeout(() => {
                window.print();
            }, 1000);
        }



    </script>
    
    <script>

        let currentPeriod = '6';
        let monthlyChart, pieChart;
        

        function initCharts() {
            if (typeof periodData === 'undefined') {
                console.error('periodData not available for charts');
                return;
            }
            
            const data = periodData[currentPeriod];
            if (!data) {
                console.error('Data not available for period:', currentPeriod);
                return;
            }
            

            const monthlyCtx = document.getElementById('monthlyTrendChart').getContext('2d');
            monthlyChart = new Chart(monthlyCtx, {
            type: 'line',
            data: {
                    labels: data.labels,
                datasets: [
                    {
                        label: 'Organik',
                            data: data.organik,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Anorganik',
                            data: data.anorganik,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'B3',
                            data: data.b3,
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 15
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                    }
                },
                scales: {
                    y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                    }
                        }
                    },
                    animation: {
                        duration: 750,
                        easing: 'easeInOutQuart'
                }
            }
        });


            const pieCtx = document.getElementById('categoryPieChart').getContext('2d');
            pieChart = new Chart(pieCtx, {
            type: 'doughnut',
            data: {
                labels: ['Organik', 'Anorganik', 'B3'],
                datasets: [{
                        data: [
                            data.stats.organik,
                            data.stats.anorganik,
                            data.stats.b3
                        ],
                        backgroundColor: [
                            '#10b981',
                            '#3b82f6',
                            '#ef4444'
                        ],
                    borderWidth: 2,
                        borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 15
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const total = data.stats.total;
                                    const value = context.parsed;
                                    const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                    return context.label + ': ' + value + ' (' + percentage + '%)';
                                }
                            }
                        }
                    },
                    animation: {
                        duration: 750,
                        easing: 'easeInOutQuart'
                }
            }
        });
        }
        

        const activeTimers = {};
        

        function updateStats(period) {
            const stats = periodData[period].stats;
            

            function animateValue(id, start, end, duration) {

                if (activeTimers[id]) {
                    clearInterval(activeTimers[id]);
                    delete activeTimers[id];
                }
                
                const obj = document.getElementById(id);
                if (!obj) return;
                

                start = Math.floor(Number(start) || 0);
                end = Math.floor(Number(end) || 0);
                

                if (start === end) {
                    obj.textContent = end;
                    return;
                }
                
                const range = Math.abs(end - start);
                const increment = end > start ? 1 : -1;
                const stepTime = Math.max(10, Math.abs(Math.floor(duration / range))); // Min 10ms
                let current = start;
                
                const timer = setInterval(function() {
                    current += increment;
                    

                    if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
                        current = end;
                    obj.textContent = current;
                        clearInterval(timer);
                        delete activeTimers[id];
                    } else {
                        obj.textContent = current;
                    }
                }, stepTime);
                

                activeTimers[id] = timer;
            }
            

            const getCurrentValue = (id) => {
                const elem = document.getElementById(id);
                if (!elem) return 0;
                const text = elem.textContent.replace(/[^0-9-]/g, ''); // Remove non-numeric except minus
                const parsed = parseInt(text, 10);
                return isNaN(parsed) ? 0 : Math.max(0, parsed); // Ensure non-negative
            };
            
            const currentTotal = getCurrentValue('stat-total');
            const currentOrganik = getCurrentValue('stat-organik-count');
            const currentAnorganik = getCurrentValue('stat-anorganik-count');
            const currentB3 = getCurrentValue('stat-b3-count');
            

            const safeTotal = Math.floor(Number(stats.total) || 0);
            const safeOrganik = Math.floor(Number(stats.organik) || 0);
            const safeAnorganik = Math.floor(Number(stats.anorganik) || 0);
            const safeB3 = Math.floor(Number(stats.b3) || 0);
            

            animateValue('stat-total', currentTotal, safeTotal, 500);
            animateValue('stat-organik-count', currentOrganik, safeOrganik, 500);
            animateValue('stat-anorganik-count', currentAnorganik, safeAnorganik, 500);
            animateValue('stat-b3-count', currentB3, safeB3, 500);
            

            setTimeout(() => {
                document.getElementById('stat-organik-percent').textContent = stats.organik_percent + '%';
                document.getElementById('stat-anorganik-percent').textContent = stats.anorganik_percent + '%';
                document.getElementById('stat-b3-percent').textContent = stats.b3_percent + '%';
            }, 250);
            

            if (document.getElementById('stat-period-label')) {
            document.getElementById('stat-period-label').textContent = periodData[period].label;
            }

            if (document.getElementById('pdf-period-label')) {
                document.getElementById('pdf-period-label').textContent = periodData[period].label;
            }
        }
        

        function initWasteTypesChart() {
            const wasteTypesData = <?php echo json_encode($topWasteTypes); ?>;
            const labels = wasteTypesData.map(w => w.name);
            const data = wasteTypesData.map(w => w.count);
            const colors = wasteTypesData.map(w => {
                if (w.kategori === 'organik') return '#10b981';
                if (w.kategori === 'anorganik') return '#3b82f6';
                if (w.kategori === 'b3') return '#ef4444';
                return '#6b7280';
            });
            
            const ctx = document.getElementById('wasteTypesChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Jumlah Laporan',
                        data: data,
                        backgroundColor: colors,
                        borderColor: colors,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    indexAxis: 'y', // Horizontal bar
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const waste = wasteTypesData[context.dataIndex];
                                    return waste.name + ': ' + waste.count + ' laporan (' + waste.percent + '%)';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    },
                    animation: {
                        duration: 1000,
                        easing: 'easeInOutQuart'
                    }
                }
            });
        }
        

        document.addEventListener('DOMContentLoaded', function() {
            initCharts();
            initWasteTypesChart();
            initProfessionalMap();
            console.log('📊 Analytics Loaded:');
            console.log('Current Period:', periodData[currentPeriod].title);
            console.log('Recommendations:', <?php echo count($aiRecommendations); ?>);
            console.log('Top Waste Types:', <?php echo count($topWasteTypes); ?>);
        });


        let analyticsMap = null;
        let markersLayer = null;
        let heatmapLayer = null;
        let heatmapEnabled = false;
        
        function initProfessionalMap() {

            const reportsData = <?php echo json_encode(array_map(function($r) {
                return [
                    'id' => $r['id'],
                    'lat' => floatval($r['lokasi_latitude']),
                    'lng' => floatval($r['lokasi_longitude']),
                    'kategori' => $r['kategori'],
                    'jenis_sampah' => $r['jenis_sampah'] ?? '',
                    'alamat' => $r['alamat_lokasi'],
                    'created_at' => $r['created_at'],
                    'status' => $r['status'] ?? 'pending'
                ];
            }, $allReports)); ?>;
            
            if (reportsData.length === 0) {
                document.getElementById('analyticsMap').innerHTML = 
                    '<div style="display: flex; align-items: center; justify-content: center; height: 100%; background: #f9fafb; color: #6b7280;">' +
                    '<div class="text-center"><i class="fas fa-map-marked-alt fa-3x mb-3"></i><p>Tidak ada data laporan untuk ditampilkan</p></div></div>';
                return;
            }
            

            const avgLat = reportsData.reduce((sum, r) => sum + r.lat, 0) / reportsData.length;
            const avgLng = reportsData.reduce((sum, r) => sum + r.lng, 0) / reportsData.length;
            

            analyticsMap = L.map('analyticsMap', {
                zoomControl: true,
                scrollWheelZoom: true,
                doubleClickZoom: true,
                boxZoom: true,
                keyboard: true,
                dragging: true,
                touchZoom: true
            }).setView([avgLat, avgLng], 12);
            

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors | Data: Aplikasi Pelaporan Sampah',
                maxZoom: 19,
                minZoom: 3
            }).addTo(analyticsMap);
            

            markersLayer = L.markerClusterGroup({
                chunkedLoading: true,
                chunkInterval: 200,
                chunkDelay: 50,
                spiderfyOnMaxZoom: true,
                showCoverageOnHover: true,
                zoomToBoundsOnClick: true,
                maxClusterRadius: 50,
                iconCreateFunction: function(cluster) {
                    const count = cluster.getChildCount();
                    let size = 'small';
                    if (count > 50) size = 'large';
                    else if (count > 20) size = 'medium';
                    
                    return L.divIcon({
                        html: '<div style="background: linear-gradient(135deg, #14b8a6, #0d9488); color: white; border-radius: 50%; width: ' + 
                              (size === 'large' ? '50' : size === 'medium' ? '40' : '35') + 'px; height: ' +
                              (size === 'large' ? '50' : size === 'medium' ? '40' : '35') + 
                              'px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: ' +
                              (size === 'large' ? '1rem' : '0.875rem') + '; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3);">' +
                              count + '</div>',
                        className: 'marker-cluster-custom',
                        iconSize: L.point(size === 'large' ? 50 : size === 'medium' ? 40 : 35, 
                                         size === 'large' ? 50 : size === 'medium' ? 40 : 35)
                    });
                }
            });
            

            const heatmapData = reportsData.map(r => [r.lat, r.lng, 1]);
            

            reportsData.forEach(report => {
                const categoryColors = {
                    'organik': '#10b981',
                    'anorganik': '#3b82f6',
                    'b3': '#ef4444'
                };
                
                const categoryIcons = {
                    'organik': 'leaf',
                    'anorganik': 'recycle',
                    'b3': 'exclamation-triangle'
                };
                
                const color = categoryColors[report.kategori] || '#6b7280';
                const icon = categoryIcons[report.kategori] || 'trash';
                

                const customIcon = L.divIcon({
                    html: '<div style="background: linear-gradient(135deg, ' + color + ', ' + 
                          (report.kategori === 'organik' ? '#059669' : report.kategori === 'anorganik' ? '#2563eb' : '#dc2626') + 
                          '); width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; ' +
                          'border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3); cursor: pointer; transition: transform 0.2s;">' +
                          '<i class="fas fa-' + icon + '" style="color: white; font-size: 0.875rem;"></i>' +
                          '</div>',
                    className: 'custom-marker-icon',
                    iconSize: [32, 32],
                    iconAnchor: [16, 16]
                });
                
                const marker = L.marker([report.lat, report.lng], {
                    icon: customIcon
                });
                

                const popupContent = `
                    <div style="min-width: 200px; font-family: 'Inter', sans-serif;">
                        <div style="background: linear-gradient(135deg, ${color}15, ${color}05); padding: 0.75rem; border-radius: 8px 8px 0 0; margin: -10px -10px 10px -10px;">
                            <h6 style="margin: 0; color: ${color}; font-weight: 700; font-size: 0.95rem;">
                                <i class="fas fa-${icon} me-2"></i>${report.kategori.toUpperCase()}
                            </h6>
                        </div>
                        <div style="padding: 0.5rem 0;">
                            <p style="margin: 0.5rem 0; font-size: 0.875rem; color: #374151;">
                                <strong>ID:</strong> #${report.id}
                            </p>
                            ${report.jenis_sampah ? `<p style="margin: 0.5rem 0; font-size: 0.875rem; color: #374151;">
                                <strong>Jenis:</strong> ${report.jenis_sampah.replace(/_/g, ' ')}
                            </p>` : ''}
                            <p style="margin: 0.5rem 0; font-size: 0.875rem; color: #374151;">
                                <i class="fas fa-map-marker-alt text-danger me-1"></i>
                                <small>${report.alamat.substring(0, 60)}${report.alamat.length > 60 ? '...' : ''}</small>
                            </p>
                            <p style="margin: 0.5rem 0; font-size: 0.75rem; color: #6b7280;">
                                <i class="fas fa-calendar me-1"></i>
                                ${new Date(report.created_at).toLocaleDateString('id-ID', { 
                                    year: 'numeric', 
                                    month: 'short', 
                                    day: 'numeric' 
                                })}
                            </p>
                            <div style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid #e5e7eb;">
                                <a href="detail.php?id=${report.id}" 
                                   style="display: inline-block; padding: 0.5rem 1rem; background: ${color}; color: white; 
                                          border-radius: 6px; text-decoration: none; font-size: 0.875rem; font-weight: 600; 
                                          transition: all 0.2s; text-align: center; width: 100%;">
                                    <i class="fas fa-eye me-1"></i> Lihat Detail
                                </a>
                            </div>
                        </div>
                    </div>
                `;
                
                marker.bindPopup(popupContent, {
                    maxWidth: 300,
                    className: 'custom-popup'
                });
                
                markersLayer.addLayer(marker);
            });
            

            analyticsMap.addLayer(markersLayer);
            

            if (typeof L.heatLayer !== 'undefined') {
                heatmapLayer = L.heatLayer(heatmapData, {
                    radius: 25,
                    blur: 15,
                    maxZoom: 17,
                    gradient: {
                        0.0: 'rgba(16, 185, 129, 0)',
                        0.2: 'rgba(16, 185, 129, 0.4)',
                        0.4: 'rgba(59, 130, 246, 0.6)',
                        0.6: 'rgba(245, 158, 11, 0.7)',
                        0.8: 'rgba(239, 68, 68, 0.8)',
                        1.0: 'rgba(239, 68, 68, 1.0)'
                    }
                });
            }
            

            if (markersLayer.getBounds().isValid()) {
                analyticsMap.fitBounds(markersLayer.getBounds().pad(0.1));
            }
        }
        

        function toggleHeatmap() {
            if (!heatmapLayer) {
                alert('Heatmap plugin tidak tersedia');
                return;
            }
            
            heatmapEnabled = !heatmapEnabled;
            
            if (heatmapEnabled) {
                analyticsMap.addLayer(heatmapLayer);
                document.querySelector('[onclick="toggleHeatmap()"]').innerHTML = 
                    '<i class="fas fa-fire me-1"></i>Hide Heatmap';
            } else {
                analyticsMap.removeLayer(heatmapLayer);
                document.querySelector('[onclick="toggleHeatmap()"]').innerHTML = 
                    '<i class="fas fa-fire me-1"></i>Toggle Heatmap';
            }
        }
        

        function resetMapView() {
            if (markersLayer && markersLayer.getBounds().isValid()) {
                analyticsMap.fitBounds(markersLayer.getBounds().pad(0.1));
            }
        }
        

        function toggleFullscreen() {
            const mapContainer = document.getElementById('analyticsMap');
            if (!document.fullscreenElement) {
                mapContainer.requestFullscreen().catch(err => {
                    alert('Fullscreen tidak didukung oleh browser');
                });
                document.querySelector('[onclick="toggleFullscreen()"]').innerHTML = 
                    '<i class="fas fa-compress me-1"></i>Exit Fullscreen';
            } else {
                document.exitFullscreen();
                document.querySelector('[onclick="toggleFullscreen()"]').innerHTML = 
                    '<i class="fas fa-expand me-1"></i>Fullscreen';
            }
        }
    </script>
    
    
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
    
    <script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
</body>
</html>
