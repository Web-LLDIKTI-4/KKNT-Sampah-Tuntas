<div class="divider">
  <div class="divider-text"><h4><i class="ri-calendar-todo-line"></i> {{ date("Y-m-d") }}</h4></div>
</div>
<span id="tanggal_error" class="text-danger d-flex justify-content-center"></span>
<form id="form-tambah" method="post" action="{{ url('logkehadiran/insertizin') }}" data-ajax-form data-reload-page>
    @csrf
    @method('PUT')
    <div class="form-group form-floating form-floating-outline mb-6">
        <select name="status_kehadiran" class="form-control form-control-sm" required>
            @if($status_kehadiran) 
                @foreach($status_kehadiran as $row)
                    <option value="{{$row}}">{{ ucfirst($row) }}</option>
                @endforeach
            @endif
        </select>
        <label>Status Izin</label>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <textarea name="keterangan" class="form-control" required maxlength="1000"></textarea>
        <label>Keterangan</label>
    </div>
    <hr>
    <x-button.save formId="form-tambah">Simpan</x-button.save>
</form>
        
<script>
    $(function(){})
  </script>