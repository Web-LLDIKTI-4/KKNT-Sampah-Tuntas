<div class="row">
    <div class="col-12 table-responsive">
        <x-datatable id="dataTable" tableClass="table table-bordered table-sm">
            <x-slot:thead>
                <tr>
                    <th width="1">No</th>
                    <th>Id desa</th>
                    <th>Nama Kecamatan</th>
                    <th>Nama Kelurahan/Desa</th>
                    <th width="1">Peta</th>
                    <th width="1">Aksi</th>
                </tr>
            </x-slot:thead>
        </x-datatable>
    </div>
</div>
<script type="text/javascript">
  $(function () {
    // Partial bisa di-.load() ulang: bersihkan peta & observer milik instance sebelumnya
    if (window.desaMinimap) window.desaMinimap.destroy();

    var $table = $('#dataTable');
    var minimaps = new Map();
    var observer = ('IntersectionObserver' in window && window.L) ? new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) initMinimap(entry.target);
        });
    }, { rootMargin: '100px' }) : null;

    function initMinimap(el) {
        observer.unobserve(el);
        if (minimaps.has(el)) return;
        var lat = Number(el.dataset.lat);
        var lng = Number(el.dataset.lng);
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
        var map = L.map(el, {
            zoomControl: false,
            dragging: false,
            scrollWheelZoom: false,
            doubleClickZoom: false,
            boxZoom: false,
            keyboard: false,
            touchZoom: false,
            attributionControl: true
        }).setView([lat, lng], 14);
        map.attributionControl.setPrefix(false);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OSM'
        }).addTo(map);
        L.circleMarker([lat, lng], { radius: 6, color: '#ffffff', weight: 1.5, fillColor: '#1e63d6', fillOpacity: 0.9, interactive: false }).addTo(map);
        minimaps.set(el, map);
    }

    function toCoord(value) {
        if (value === null || value === undefined || value === '') return null;
        var num = Number(value);
        return Number.isFinite(num) ? num : null;
    }

    function clearMinimaps() {
        if (observer) observer.disconnect();
        minimaps.forEach(function (map) { map.remove(); });
        minimaps.clear();
    }

    window.desaMinimap = {
        destroy: function () {
            clearMinimaps();
            $table.off('.minimap');
            window.desaMinimap = null;
        }
    };

    $table
        .on('draw.dt.minimap', function () {
            clearMinimaps();
            if (!observer) return;
            $table.find('.desa-minimap').each(function () { observer.observe(this); });
        });

    var table = $('#dataTable').DataTable({
        searching: true,
        lengthChange: true,
        processing: true,
        serverSide: true,
        ajax: "{{ route('desa.listdataserver') }}",
        language: {
            search: "",
            searchPlaceholder: "Cari...",
            zeroRecords: "Tidak ada data yang tersedia",
            infoEmpty: "Tidak ada data yang ditemukan",
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center', orderable: false, searchable: false},
            {data: 'id_desa', name: 'id_desa', visible:false},
            {data: 'kecamatan', name: 'kecamatan'},
            {data: 'desa', name: 'desa'},
            {
                data: null, orderable: false, searchable: false,
                render: function (data, type, full) {
                    return toCoord(full.latitude) !== null && toCoord(full.longitude) !== null ? '<div class="desa-minimap" role="img" aria-label="Lokasi desa"></div>' : '<span class="text-muted small">Belum diisi</span>';
                },
                createdCell: function (td, cellData, rowData) {
                    var el = td.querySelector('.desa-minimap');
                    if (!el) return;
                    el.dataset.lat = String(toCoord(rowData.latitude));
                    el.dataset.lng = String(toCoord(rowData.longitude));
                }
            },
            {data: 'action', name: 'action', className: 'text-center', orderable: false, searchable: false},
        ],
        columnDefs: [
            {
                render: function (data, type, full, meta) {
                    return "<div class='text-wrap'>" + data + "</div>";
                },
                targets: 3
            }
        ],
        layout: {
            top1: {
                searchPanes: {
                    viewTotal: true
                }
            }
        }
    });
  });
</script>
