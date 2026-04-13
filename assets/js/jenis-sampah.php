<?php

function getJenisSampahInfo($jenis) {
    $info = [

        'plastik' => ['icon' => '♻️', 'label' => 'Plastik', 'color' => '#2196f3'],
        'daun' => ['icon' => '🍃', 'label' => 'Daun', 'color' => '#4caf50'],
        'kertas' => ['icon' => '📄', 'label' => 'Kertas', 'color' => '#ff9800'],
        'elektronik' => ['icon' => '📱', 'label' => 'Elektronik', 'color' => '#e91e63'],
        'logam' => ['icon' => '🔩', 'label' => 'Logam', 'color' => '#9e9e9e'],
        'baterai' => ['icon' => '🔋', 'label' => 'Baterai', 'color' => '#f44336'],
        'kain' => ['icon' => '👕', 'label' => 'Kain', 'color' => '#9c27b0'],
        'kaca' => ['icon' => '🍾', 'label' => 'Kaca', 'color' => '#00bcd4'],
        'kayu' => ['icon' => '🪵', 'label' => 'Kayu', 'color' => '#795548'],
        'makanan' => ['icon' => '🍎', 'label' => 'Makanan', 'color' => '#8bc34a'],
        'lainnya' => ['icon' => '🔸', 'label' => 'Lainnya', 'color' => '#607d8b'],
        'tidak_diketahui' => ['icon' => '❓', 'label' => 'Tidak Diketahui', 'color' => '#9e9e9e'],
        

        'botol_plastik' => ['icon' => '🍾', 'label' => 'Botol Plastik', 'color' => '#2196f3'],
        'kardus' => ['icon' => '📦', 'label' => 'Kardus', 'color' => '#f59e0b'],
        'karton' => ['icon' => '📦', 'label' => 'Karton', 'color' => '#fb8c00'],
        'gelas_plastik' => ['icon' => '🥤', 'label' => 'Gelas Plastik', 'color' => '#06b6d4'],
        'kantong_plastik' => ['icon' => '🛍️', 'label' => 'Kantong Plastik', 'color' => '#8b5cf6'],
        'sedotan_plastik' => ['icon' => '🥤', 'label' => 'Sedotan Plastik', 'color' => '#ec4899'],
        'kaleng_minuman' => ['icon' => '🥫', 'label' => 'Kaleng Minuman', 'color' => '#6b7280'],
        'styrofoam' => ['icon' => '📦', 'label' => 'Styrofoam', 'color' => '#ef4444'],
        'lampu' => ['icon' => '💡', 'label' => 'Lampu', 'color' => '#f59e0b'],
        'oli' => ['icon' => '🛢️', 'label' => 'Oli/Minyak Bekas', 'color' => '#d32f2f'],
        

        'buah_sayur' => ['icon' => '🥬', 'label' => 'Buah/Sayur Busuk', 'color' => '#689f38'],
        'kotoran_hewan' => ['icon' => '🐾', 'label' => 'Kotoran Hewan', 'color' => '#827717'],
        'tulang' => ['icon' => '🦴', 'label' => 'Tulang', 'color' => '#9e9e9e'],
        'kulit_telur' => ['icon' => '🥚', 'label' => 'Kulit Telur', 'color' => '#c8e6c9'],
        'ampas_kopi' => ['icon' => '☕', 'label' => 'Ampas Kopi/Teh', 'color' => '#6d4c41'],
        'kotoran_dapur' => ['icon' => '🍴', 'label' => 'Sampah Dapur', 'color' => '#558b2f'],
        'kertas_tisu' => ['icon' => '🧻', 'label' => 'Tisu/Kertas Basah', 'color' => '#9ccc65'],
        'serbuk_gergaji' => ['icon' => '🪚', 'label' => 'Serbuk Gergaji', 'color' => '#8d6e63'],
        

        'kantong_plastik' => ['icon' => '🛍️', 'label' => 'Kantong Plastik/Kresek', 'color' => '#03a9f4'],
        'gelas_plastik' => ['icon' => '🥤', 'label' => 'Gelas/Cup Plastik', 'color' => '#00bcd4'],
        'sedotan_plastik' => ['icon' => '🥤', 'label' => 'Sedotan Plastik', 'color' => '#0097a7'],
        'styrofoam' => ['icon' => '📦', 'label' => 'Styrofoam', 'color' => '#ff5722'],
        'plastik_lainnya' => ['icon' => '♻️', 'label' => 'Plastik Lainnya', 'color' => '#1976d2'],
        'kemasan_plastik' => ['icon' => '📦', 'label' => 'Kemasan Plastik', 'color' => '#4fc3f7'],
        'ember_plastik' => ['icon' => '🪣', 'label' => 'Ember/Wadah Plastik', 'color' => '#0288d1'],
        'mainan_plastik' => ['icon' => '🧸', 'label' => 'Mainan Plastik', 'color' => '#29b6f6'],
        'jerigen_plastik' => ['icon' => '🧴', 'label' => 'Jerigen Plastik', 'color' => '#01579b'],
        

        'kertas' => ['icon' => '📄', 'label' => 'Kertas/Kardus', 'color' => '#ff9800'],
        'koran' => ['icon' => '📰', 'label' => 'Koran/Majalah', 'color' => '#f57c00'],
        'buku' => ['icon' => '📚', 'label' => 'Buku Bekas', 'color' => '#e65100'],
        'karton' => ['icon' => '📦', 'label' => 'Kardus/Karton', 'color' => '#fb8c00'],
        'kertas_kantor' => ['icon' => '🗂️', 'label' => 'Kertas Kantor', 'color' => '#ffa726'],
        'amplop' => ['icon' => '✉️', 'label' => 'Amplop', 'color' => '#ffb74d'],
        'dus_bekas' => ['icon' => '📦', 'label' => 'Dus Bekas', 'color' => '#ff8a65'],
        

        'kaleng_minuman' => ['icon' => '🥫', 'label' => 'Kaleng Minuman (Aluminium)', 'color' => '#9e9e9e'],
        'kaleng_makanan' => ['icon' => '🥫', 'label' => 'Kaleng Makanan', 'color' => '#757575'],
        'kawat' => ['icon' => '🔗', 'label' => 'Kawat', 'color' => '#757575'],
        'paku' => ['icon' => '🔨', 'label' => 'Paku/Sekrup', 'color' => '#424242'],
        'seng' => ['icon' => '🏗️', 'label' => 'Seng/Besi Bekas', 'color' => '#546e7a'],
        'foil_aluminium' => ['icon' => '🎁', 'label' => 'Aluminium Foil', 'color' => '#90a4ae'],
        

        'botol_kaca' => ['icon' => '🍾', 'label' => 'Botol Kaca', 'color' => '#00bcd4'],
        'pecahan_kaca' => ['icon' => '🪟', 'label' => 'Pecahan Kaca', 'color' => '#0097a7'],
        'cermin' => ['icon' => '🪞', 'label' => 'Cermin Bekas', 'color' => '#00838f'],
        'stoples_kaca' => ['icon' => '🫙', 'label' => 'Stoples/Toples Kaca', 'color' => '#006064'],
        

        'sepatu' => ['icon' => '👟', 'label' => 'Sepatu Bekas', 'color' => '#7b1fa2'],
        'tas' => ['icon' => '🎒', 'label' => 'Tas Bekas', 'color' => '#8e24aa'],
        'selimut' => ['icon' => '🛏️', 'label' => 'Selimut/Sprei', 'color' => '#ab47bc'],
        'boneka' => ['icon' => '🧸', 'label' => 'Boneka Kain', 'color' => '#ba68c8'],
        'topi' => ['icon' => '🧢', 'label' => 'Topi Bekas', 'color' => '#ce93d8'],
        

        'ban_bekas' => ['icon' => '🛞', 'label' => 'Ban Bekas', 'color' => '#212121'],
        'sandal_karet' => ['icon' => '🩴', 'label' => 'Sandal Karet', 'color' => '#424242'],
        'sarung_tangan' => ['icon' => '🧤', 'label' => 'Sarung Tangan Karet', 'color' => '#616161'],
        'balon' => ['icon' => '🎈', 'label' => 'Balon Karet', 'color' => '#757575'],
        

        'pipa_pralon' => ['icon' => '🔧', 'label' => 'Pipa/Pralon', 'color' => '#607d8b'],
        'keramik' => ['icon' => '🏺', 'label' => 'Keramik Pecah', 'color' => '#795548'],
        'bata' => ['icon' => '🧱', 'label' => 'Bata/Puing', 'color' => '#8d6e63'],
        'karpet' => ['icon' => '🧶', 'label' => 'Karpet Bekas', 'color' => '#a1887f'],
        'kasur' => ['icon' => '🛏️', 'label' => 'Kasur Bekas', 'color' => '#6d4c41'],
        'furniture' => ['icon' => '🪑', 'label' => 'Furniture Bekas', 'color' => '#5d4037'],
        'gabus' => ['icon' => '📦', 'label' => 'Gabus/Packing', 'color' => '#d7ccc8'],
        'lilin' => ['icon' => '🕯️', 'label' => 'Lilin Bekas', 'color' => '#bcaaa4'],
        

        'lampu' => ['icon' => '💡', 'label' => 'Lampu/Bohlam', 'color' => '#ff5722'],
        'hp_bekas' => ['icon' => '📱', 'label' => 'HP/Smartphone Bekas', 'color' => '#ec407a'],
        'komputer' => ['icon' => '💻', 'label' => 'Komputer/Laptop', 'color' => '#f06292'],
        'tv_bekas' => ['icon' => '📺', 'label' => 'TV/Monitor Bekas', 'color' => '#ef5350'],
        'kabel_elektronik' => ['icon' => '🔌', 'label' => 'Kabel Elektronik', 'color' => '#e57373'],
        'charger' => ['icon' => '🔌', 'label' => 'Charger/Adaptor', 'color' => '#ff6f00'],
        'oli' => ['icon' => '🛢️', 'label' => 'Oli/Minyak Bekas', 'color' => '#d32f2f'],
        'cat' => ['icon' => '🎨', 'label' => 'Cat/Thinner', 'color' => '#c62828'],
        'obat' => ['icon' => '💊', 'label' => 'Obat Kadaluarsa', 'color' => '#b71c1c'],
        'pestisida' => ['icon' => '☠️', 'label' => 'Pestisida/Racun', 'color' => '#880e4f'],
        'semprot_serangga' => ['icon' => '🪰', 'label' => 'Obat Nyamuk/Semprot', 'color' => '#ad1457'],
        'tinta_printer' => ['icon' => '🖨️', 'label' => 'Tinta/Toner Printer', 'color' => '#c51162'],
        'kaleng_aerosol' => ['icon' => '💨', 'label' => 'Kaleng Aerosol/Spray', 'color' => '#d81b60'],
        'aki' => ['icon' => '🔋', 'label' => 'Aki/Accu Bekas', 'color' => '#e53935'],
        'termometer' => ['icon' => '🌡️', 'label' => 'Termometer Raksa', 'color' => '#f4511e'],
        

        'masker' => ['icon' => '😷', 'label' => 'Masker Bekas', 'color' => '#ff6f00'],
        'jarum_suntik' => ['icon' => '💉', 'label' => 'Jarum Suntik', 'color' => '#d84315'],
        'sarung_tangan_medis' => ['icon' => '🧤', 'label' => 'Sarung Tangan Medis', 'color' => '#bf360c'],
        'perban' => ['icon' => '🩹', 'label' => 'Perban/Plester', 'color' => '#ff8a80']
    ];
    
    return $info[$jenis] ?? $info['lainnya'];
}
?>

