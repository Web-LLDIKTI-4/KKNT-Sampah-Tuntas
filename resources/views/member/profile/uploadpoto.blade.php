<form method="post" id="form-file-upload" action="{{ url('mhsprofile/prosesuploadpoto') }}" >
    @csrf
    @method('PUT')
    <div class="form-floating form-floating-outline mb-6">
        <input class="form-control" type="file" id="formValidationFile" name="file_upload">
        <label for="file_upload">Profile Pic</label>
    </div>
</form>