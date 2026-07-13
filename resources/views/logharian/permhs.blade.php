@extends('layouts.app')
@section('title','Data Kegiatan Mahasiswa')
@section('container')
<x-page-header /> 

<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-12 table-responsive">
                <table class="table table-bordered user_datatable" id="dataTable">
                    <thead>
                        <tr>
                            <th width="1">No</th>
                            <th>Tanggal</th>
                            <th>Deskripsi</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th width="1">No</th>
                            <th>Tanggal</th>
                            <th>Deskripsi</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
$(function(){
    var table = $('#dataTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ url('admlogharian/permhsserver') }}/{{$email}}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'tanggal', name: 'tanggal'},           
            {
                data: 'deskripsi',
                name: 'deskripsi',
                render: function (data, type, row) {
                    // Membuat sebuah div sementara untuk membersihkan tag HTML
                    var tempDiv = document.createElement('div');
                    tempDiv.innerHTML = data;
                    // Mengambil teks dari div tersebut yang sudah bersih dari tag HTML
                    var strippedText = tempDiv.textContent || tempDiv.innerText || '';
                    return "<div class='text-wrap width-200'>" +strippedText+ "</div>";
                }
            },           
            {data: 'action', name: 'action', orderable: false, searchable: false, visible:false},
        ],
        initComplete: function () {
            var table = this;
            this.api()
                .columns()
                .every(function (index) {
                    var column = this;
                    // Periksa apakah footer ada sebelum mencoba mengakses propertinya
                    var footer = column.footer(); // Dapatkan footer kolom

                    // Periksa apakah footer ada sebelum mencoba mengakses propertinya
                    if (footer) {
                        var title = column.footer().textContent;
        
                        // Create input element and add event listener
                        if (index !== 0) { // Skip column "No" (index 0)
                            $('<input type="text" class="form-control form-control-sm p-1" placeholder="Search ' + title + '" />')
                                .appendTo($(column.footer()).empty())
                                .on('keyup change clear', function () {
                                    if (column.search() !== this.value) {
                                        column.search(this.value).draw();
                                    }
                                });
                        }
                    }
                });
        },
        layout: {
            top1: {
                searchPanes: {
                    viewTotal: true
                }
            }
        }
    });
})    

</script>
@stop 