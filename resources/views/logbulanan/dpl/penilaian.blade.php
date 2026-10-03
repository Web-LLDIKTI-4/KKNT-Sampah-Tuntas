<div>{!! \App\Support\HtmlSanitizer::clean($logbulanan->deskripsi) !!}</div>
<hr>
<form method="POST" id="form-simpan" action="{{ url('admlogbulanan/updatenilai') }}" data-ajax-form>
    @csrf
    @method('PUT')
    <input type="hidden" name="id_logbulanan" value="{{$logbulanan->id_logbulanan}}">
    <x-form.select name="nilai" label="Nilai" :placeholder="false">
            @if($anilai)
                @foreach($anilai as $value)
                    <option value="{{$value}}" @if($logbulanan->nilai == $value) selected @endif>{{ $value }}</option>
                @endforeach
            @endif
    </x-form.select>
    <x-form.textarea name="hasil_verifikasi" label="Catatan Hasil Verifikasi" :value="$logbulanan->hasil_verifikasi" />
    <div>
        <x-button.save formId="form-simpan">
            Simpan
        </x-button.save>
    </div>
</form>

<script>
$(document).on("submit", "#form-simpan", function(e){
    e.preventDefault();

    var form = $(this);
    var action = form.attr("action");
    var id = form.attr("id");
    var dString = form.serialize();

    $.ajax({
        url: action,
        type: "POST",
        data: dString,
        dataType: "json",

        beforeSend: function () {
            btnLoading($("#btnSubmit_" + id), true);
        },

        complete: function () {
            btnLoading($("#btnSubmit_" + id), false);
        },

        success: function (ret) {
            if (ret.success) {
                $("#modalKu").modal("hide");
                toastr.success(ret.message);
                $('#dataTable').DataTable().ajax.reload(null, false);
            } else {
                toastr.warning(ret.message);

                if (ret.errors) {
                    $.each(ret.errors, function(key, value) {
                        $("#" + key + "_error").html(value[0]);
                    });
                }
            }
        },

        error: function (xhr) {
            console.log(xhr.responseText);
            toastr.error("Terjadi kesalahan saat menyimpan data.");
        }
    });
});
</script>
