<form id="form-kepala" method="post" action="{{ url($user ? 'user/updateuserkepala' : 'user/insertuserkepala') }}" data-ajax-form data-reload-url="{{ url('user/listdata') }}">
    @csrf
    @method('PUT')
    @if ($user)
        <input type="hidden" name="id" value="{{ $user->id }}">
    @endif
    <div class="row">
        <x-form.input name="name" label="Nama" :value="$user->name ?? ''" wrapper-class="form-group col-md-6 form-floating form-floating-outline mb-6" required maxlength="255" />
        <x-form.input name="email" label="Email (nama pengguna untuk masuk)" type="email" :value="$user->email ?? ''" wrapper-class="form-group col-md-6 form-floating form-floating-outline mb-6" required maxlength="255" autocomplete="off" />
    </div>
    <x-form.input name="password" type="password" minlength="8" maxlength="255" autocomplete="new-password" :required="! $user">
        <x-slot:label>Kata Sandi{{ $user ? ' (kosongkan jika tidak diubah)' : '' }}</x-slot:label>
    </x-form.input>
    <p class="small text-muted mb-4">Peran: kepala — hanya dapat melihat Dashboard KPI dan Laporan Capaian KPI.</p>
    <x-button.save formId="form-kepala">Simpan</x-button.save>
</form>
