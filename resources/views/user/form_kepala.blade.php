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
            <label>Email (nama pengguna untuk masuk)</label>
        </div>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <select name="role" id="role" class="form-select form-select-sm" required>
            @foreach (['kepala' => 'Kepala', 'pemda' => 'Pemda'] as $value => $label)
                <option value="{{ $value }}" @selected(($user->role ?? 'kepala') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <label for="role">Peran</label>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <input type="password" name="password" class="form-control form-control-sm" minlength="8" maxlength="255" autocomplete="new-password" @unless ($user) required @endunless>
        <label>Kata Sandi{{ $user ? ' (kosongkan jika tidak diubah)' : '' }}</label>
    </div>
    <p class="small text-muted mb-4">Peran kepala &amp; pemda — hanya dapat melihat Dashboard KPI dan Laporan Capaian KPI.</p>
    <x-button.save formId="form-kepala">Simpan</x-button.save>
</form>
