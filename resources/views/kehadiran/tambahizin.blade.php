<div class="divider">
  <div class="divider-text"><h4><i class="ri-calendar-todo-line"></i> {{ date("Y-m-d") }}</h4></div>
</div>
<span id="tanggal_error" class="text-danger d-flex justify-content-center"></span>
<form id="form-tambah" method="post" action="{{ url('logkehadiran/insertizin') }}" data-ajax-form data-reload-page>
    @csrf
    @method('PUT')
    <x-form.select name="status_kehadiran" label="Status Izin" input-class="form-control form-control-sm" :placeholder="false" required>
            @if($status_kehadiran) 
                @foreach($status_kehadiran as $row)
                    <option value="{{$row}}">{{ ucfirst($row) }}</option>
                @endforeach
            @endif
    </x-form.select>
    <x-form.textarea name="keterangan" label="Keterangan" required maxlength="1000" />
    <hr>
    <x-button.save formId="form-tambah">Simpan</x-button.save>
</form>
        
<script>
    $(function(){})
  </script>