
let map;
let marker;
let defaultLat = -6.200000;
let defaultLng = 106.816666;


map = L.map('map').setView([defaultLat, defaultLng], 13);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors',
    maxZoom: 19
}).addTo(map);


function updateLocation(lat, lng) {
    document.getElementById('latitude').value = lat;
    document.getElementById('longitude').value = lng;
    document.getElementById('locationInfo').textContent = `Lokasi: ${lat.toFixed(6)}, ${lng.toFixed(6)}`;
    document.getElementById('locationInfo').style.color = '#4a9d5f';
    

    if (marker) {
        map.removeLayer(marker);
    }
    

    marker = L.marker([lat, lng], {
        draggable: true
    }).addTo(map);
    

    marker.on('dragend', function(e) {
        const position = marker.getLatLng();
        updateLocation(position.lat, position.lng);
    });
    

    map.setView([lat, lng], 15);
}


document.getElementById('getCurrentLocation').addEventListener('click', function() {
    if (navigator.geolocation) {
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mendapatkan lokasi...';
        this.disabled = true;
        
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                updateLocation(lat, lng);
                this.innerHTML = '<i class="fas fa-crosshairs"></i> Gunakan Lokasi Saya';
                this.disabled = false;
            },
            (error) => {
                alert('Tidak dapat mengakses lokasi. Pastikan Anda mengizinkan akses lokasi.');
                console.error('Error getting location:', error);
                this.innerHTML = '<i class="fas fa-crosshairs"></i> Gunakan Lokasi Saya';
                this.disabled = false;
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    } else {
        alert('Browser Anda tidak mendukung Geolocation.');
    }
});


map.on('click', function(e) {
    updateLocation(e.latlng.lat, e.latlng.lng);
});


document.getElementById('registerForm').addEventListener('submit', function(e) {
    const lat = document.getElementById('latitude').value;
    const lng = document.getElementById('longitude').value;
    
    if (!lat || !lng) {
        e.preventDefault();
        alert('Harap tentukan lokasi Anda terlebih dahulu!');
        return false;
    }
    
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (password !== confirmPassword) {
        e.preventDefault();
        alert('Password dan konfirmasi password tidak cocok!');
        return false;
    }
});



