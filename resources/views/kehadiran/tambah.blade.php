<div class="divider">
  <div class="divider-text"><h4><i class="ri-calendar-todo-line"></i> {{ date("Y-m-d") }}</h4></div>
</div>
<span id="tanggal_error" class="text-danger d-flex justify-content-center"></span>
<div class="d-flex justify-content-center">
    <div class="row">
        <div class="col ">
            <form id="form-datang" method="post" action="{{ url('logkehadiran/insert') }}" data-ajax-form data-close-modal>
                @csrf
                @method('PUT')
                <input type="hidden" name="mode" value="datang">
                <x-button.save formId="form-datang">Datang</x-button.save>
            </form>
        </div>
        <div class="col">
            <form id="form-pulang" method="post" action="{{ url('logkehadiran/insert') }}" data-ajax-form data-close-modal>
                @csrf
                @method('PUT')
                <input type="hidden" name="mode" value="pulang">
                <x-button.save formId="form-pulang">Pulang</x-button.save>
            </form>
        </div>
    </div>
</div>
