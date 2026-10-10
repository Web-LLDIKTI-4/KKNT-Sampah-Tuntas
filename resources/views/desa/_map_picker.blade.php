<div class="desa-map-picker mb-2" style="height: 250px; border-radius: .375rem;"></div>
<div class="d-flex justify-content-between align-items-start gap-2 mb-6">
    <small class="text-muted">Opsional, untuk peta sebaran. Isi manual atau klik/geser titik di peta.</small>
    <button type="button" class="btn btn-sm btn-outline-secondary desa-map-clear text-nowrap">Hapus titik</button>
</div>
<script>
(function () {
    var el = document.querySelector('.desa-map-picker:not([data-init])');
    if (!el || typeof L === 'undefined') return;
    el.dataset.init = '1';

    var form = el.closest('form');
    var latInput = form.querySelector('[name="latitude"]');
    var lngInput = form.querySelector('[name="longitude"]');
    var clearBtn = form.querySelector('.desa-map-clear');
    var icon = L.icon({
        iconUrl: @json(asset('assets/vendor/libs/leaflet/images/marker-icon.png')),
        iconRetinaUrl: @json(asset('assets/vendor/libs/leaflet/images/marker-icon-2x.png')),
        iconSize: [25, 41],
        iconAnchor: [12, 41]
    });

    function readInputs() {
        var lat = parseFloat(latInput.value), lng = parseFloat(lngInput.value);
        if (!Number.isFinite(lat) || !Number.isFinite(lng) || Math.abs(lat) > 90 || Math.abs(lng) > 180) return null;
        return L.latLng(lat, lng);
    }

    var initial = readInputs();
    var map = L.map(el).setView(initial || [-6.9175, 107.6191], initial ? 15 : 11);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    var marker = null;

    function writeInputs(latlng) {
        latInput.value = latlng.lat.toFixed(7);
        lngInput.value = latlng.lng.toFixed(7);
    }

    function placeMarker(latlng) {
        if (marker) {
            marker.setLatLng(latlng);
            return;
        }
        marker = L.marker(latlng, { draggable: true, icon: icon }).addTo(map);
        marker.on('dragend', function () { writeInputs(marker.getLatLng()); });
    }

    if (initial) placeMarker(initial);

    map.on('click', function (e) {
        placeMarker(e.latlng);
        writeInputs(e.latlng);
    });

    [latInput, lngInput].forEach(function (input) {
        input.addEventListener('input', function () {
            var latlng = readInputs();
            if (!latlng) return;
            placeMarker(latlng);
            map.panTo(latlng);
        });
    });

    clearBtn.addEventListener('click', function () {
        latInput.value = '';
        lngInput.value = '';
        if (marker) {
            map.removeLayer(marker);
            marker = null;
        }
    });

    // Fix gray tiles when rendered inside a hidden/animating modal
    setTimeout(function () { map.invalidateSize(); }, 300);
    $('#modalku').one('shown.bs.modal', function () { map.invalidateSize(); });
})();
</script>
