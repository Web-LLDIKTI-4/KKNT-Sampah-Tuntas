<div>{!! $logbulanan->deskripsi !!}</div>
<hr>
<form method="POST" id="form-simpan" action="{{ url('admlogbulanan/updatenilai') }}">
    @csrf
    @method('PUT')
    <input type="hidden" name="id_logbulanan" value="{{$logbulanan->id_logbulanan}}">
    <div class="form-group form-floating form-floating-outline mb-6">
        <select name="nilai" class="form-control">
            @if($anilai)
                @foreach($anilai as $value)
                    <option value="{{$value}}" @if($logbulanan->nilai == $value) selected @endif>{{ $value }}</option>
                @endforeach
            @endif
        </select>
        <label>Nilai</label>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <textarea name="hasil_verifikasi" class="form-control">{{ $logbulanan->hasil_verifikasi }}</textarea>
        <label>Catatan Hasil Verifikasi</label>
    </div>
    <div>
        <x-btn-save formId="form-simpan">
            Simpan
        </x-btn-save>
    </div>
</form>

<script>
$(document).on("submit", "#form-simpan", function(e){
    e.preventDefault();

    var form = $(this);
    var action = form.attr("action");
    var id = form.attr("id");
    var btnHtml = $("#btnSubmit_" + id).html();
    var dString = form.serialize();

    $.ajax({
        url: action,
        type: "POST",
        data: dString,
        dataType: "json",

        beforeSend: function () {
            $("#btnSubmit_" + id)
                .prop("disabled", true)
                .html("<span class='spinner-border spinner-border-sm'></span> Loading...");
        },

        complete: function () {
            $("#btnSubmit_" + id)
                .prop("disabled", false)
                .html(btnHtml);
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
