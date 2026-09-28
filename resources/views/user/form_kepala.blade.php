<form id="form-kepala" method="post" action="{{ url($user ? 'user/updateuserkepala' : 'user/insertuserkepala') }}" data-ajax-form data-reload-url="{{ url('user/listdata') }}">
    @csrf
    @method('PUT')
    @if ($user)
        <input type="hidden" name="id" value="{{ $user->id }}">
    @endif
    <div class="row">
        <div class="form-group col-md-6 form-floating form-floating-outline mb-6">
            <input type="text" name="name" class="form-control form-control-sm" required maxlength="255" value="{{ $user->name ?? '' }}">
            <label>Nama</label>
        </div>
        <div class="form-group col-md-6 form-floating form-floating-outline mb-6">
            <input type="email" name="email" class="form-control form-control-sm" required maxlength="255" value="{{ $user->email ?? '' }}" autocomplete="off">
            <label>Email (username login)</label>
        </div>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <input type="password" name="password" class="form-control form-control-sm" minlength="8" maxlength="255" autocomplete="new-password" @unless ($user) required @endunless>
        <label>Password{{ $user ? ' (kosongkan jika tidak diubah)' : '' }}</label>
    </div>
    <p class="small text-muted mb-4">Role: kepala — hanya dapat melihat Dashboard KPI dan Laporan Capaian KPI.</p>
    <x-btn-save formId="form-kepala">Simpan</x-btn-save>
</form>
