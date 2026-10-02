    <form method="post" id="form-create" action="{{ url('user/insert') }}">
        <x-button.save formId="form-create">Tambah Pengguna</x-button.save>
        <hr>
        @csrf
        @method('PUT')
        <div class="table-responsive">
            <table class="table table-bordered table-sm" id="tabel-data">
                <thead>
                    <tr>
                        <th width="1">No</th>
                        <th>Nim</th>
                        <th>Nama</th>
                        <th>Email</th>
                        {{-- <th>Nomor Telepon</th> --}}
                        <th>Perguruan Tinggi</th>
                        <th>Lokasi Program KKN</th>
                        <th class="no-sort text-center" width="1">Aksi <input type="checkbox" id="checkAll"></th>
                    </tr>
                </thead>
                <tbody>
                    @if($data->isEmpty())
                        
                    @else
                        @foreach($data as $mahasiswa)
                            <tr>
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td>{{ $mahasiswa->nim }}</td>
                                <td>{{ $mahasiswa->nama }}</td>
                                <td>{{ $mahasiswa->email }}</td>
                                {{-- <td>{{ $mahasiswa->phone }}</td> --}}
                                <td>
                                    @if($mahasiswa->sp && $mahasiswa->sp->nm_lemb)
                                        {{ $mahasiswa->sp->nm_lemb }}
                                    @else
                                        {{ $mahasiswa->kodept }}
                                    @endif
                                </td>
                                <td>{{ $mahasiswa->locationProgram->nama_lokasi }}</td>
                                <td class="text-center">
                                    <input type="checkbox" name="createuser[]" value="{{ $mahasiswa->email }}">
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </form>
<script>
    
// Function to uncheck all checkboxes
function uncheckAllCheckboxes() {
    $("input[name='createuser[]']").prop('checked', false);
}
$('#checkAll').change(function () {
    $('input[name="createuser[]"]').prop('checked', $(this).prop('checked'));
});

$('#modalku').on('show.bs.modal', function (e) {
    // Uncheck all checkboxes when the modal is opened
    uncheckAllCheckboxes();
    // Optionally, you can clear the checked state object and storage
    checkedState = {};
    saveCheckedState(checkedState);
});    
    // Function to get checked state from storage
function getCheckedState() {
    var checkedState = sessionStorage.getItem('checkedState');
    return checkedState ? JSON.parse(checkedState) : {};
}

// Function to save checked state to storage
function saveCheckedState(checkedState) {
    sessionStorage.setItem('checkedState', JSON.stringify(checkedState));
}
$(function () {
    let checkedState = getCheckedState(); // Initialize checked state

    let table = $('#tabel-data').DataTable({
        paging: true,
        searching: true,
        lengthChange: true,
        ordering: true,
        info: true,
        autoWidth: false,
        responsive: true,
        serverSide: false,
        language: {
            search: "",
            searchPlaceholder: "Cari...",
            zeroRecords: "Tidak ada data yang tersedia",
            infoEmpty: "Tidak ada data yang ditemukan",
        },
        columnDefs: [
            { targets: 'no-sort', orderable: false } // Tambahkan class 'no-sort' pada kolom 'Aksi'
        ],
    });

    // Handle checkbox changes
    $("#tabel-data tbody").on("change", "input[name='createuser[]']", function () {
        var nip = $(this).val();
        checkedState[nip] = $(this).prop('checked');
        console.log('Checked state:', checkedState); // Log the checked state
        saveCheckedState(checkedState);
    });

    $("#form-create").on("submit",function(e){
        e.preventDefault();

        // Get unique checked checkboxes on all pages
        var uniqueCheckedCheckboxes = [];

        table.$("input[name='createuser[]']:checked").each(function () {
            var value = $(this).val();
            if (!uniqueCheckedCheckboxes.includes(value)) {
                uniqueCheckedCheckboxes.push(value);
            }
        });

        // Log the checked checkboxes
        console.log('Checked checkboxes:', uniqueCheckedCheckboxes);

        // Create a unique checked checkboxes object
        var uniqueCheckedCheckboxesObject = {};
        uniqueCheckedCheckboxes.forEach(function (value) {
            uniqueCheckedCheckboxesObject[value] = true;
        });

        // Append the checked checkboxes to the form data
        var formData = $(this).serializeArray().filter(function (item) {
            // Filter out duplicates in formData
            return !uniqueCheckedCheckboxesObject[item.value];
        });

        // Append unique checked checkboxes to formData
        $.each(uniqueCheckedCheckboxes, function (index, value) {
            formData.push({ name: 'createuser[]', value: value });
        });

        var action = $(this).attr("action");
        var id = $(this).attr("id");
        $.ajax({
            url: action,
            type:'POST',
            data: formData,
            beforeSend:function(){
                btnLoading($("#btnSubmit_" + id), true);			
            },
            complete:function(){
                btnLoading($("#btnSubmit_" + id), false);	
            },
            success: function(data) {                
                console.log(data.error);
                if($.isEmptyObject(data.error)){  
                    $("#modalku").modal("hide");
                    // console.log('data:', data.data); // Log the success message
                    toastr.success(data.success)	
                    var table = $('#table-data').DataTable(); // Menginisialisasi objek tabel
                    // // Memuat ulang data tabel secara manual
                    table.ajax.reload();
                    window.location.reload(); // Redirect to the user page    
                }else{                    
                    toastr.warning(data.error)	 
                }
            },
            error:function(xhr,ajaxOptions,thrownError){
                alert(xhr.status+"\n"+xhr.responseText+"\n"+thrownError);				
            }	

        });

    });
}); 
</script>
