<form method="post" action="{{ route('password.update') }}">
    @csrf
    @method('put')

    @if(auth()->user()->password)
    <div class="mb-3">
        <label for="update_password_current_password" class="form-label" style="font-size:13px;font-weight:600;">Password Saat Ini</label>
        <input id="update_password_current_password" name="current_password" type="password" class="form-control" style="font-size:13px;border-radius:9px;" autocomplete="current-password" />
        @error('current_password', 'updatePassword')<div class="text-danger mt-1" style="font-size:12px;">{{ $message }}</div>@enderror
    </div>
    @endif

    <div class="mb-3">
        <label for="update_password_password" class="form-label" style="font-size:13px;font-weight:600;">Password Baru</label>
        <input id="update_password_password" name="password" type="password" class="form-control" style="font-size:13px;border-radius:9px;" autocomplete="new-password" />
        @error('password', 'updatePassword')<div class="text-danger mt-1" style="font-size:12px;">{{ $message }}</div>@enderror
    </div>

    <div class="mb-4">
        <label for="update_password_password_confirmation" class="form-label" style="font-size:13px;font-weight:600;">Konfirmasi Password Baru</label>
        <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="form-control" style="font-size:13px;border-radius:9px;" autocomplete="new-password" />
        @error('password_confirmation', 'updatePassword')<div class="text-danger mt-1" style="font-size:12px;">{{ $message }}</div>@enderror
    </div>

    <div class="d-flex align-items-center gap-3">
        <button type="submit" class="btn-dp btn-dp-primary"><i class="bi bi-key"></i> Ubah Password</button>
        @if (session('status') === 'password-updated')
            <span class="text-success" style="font-size:13px;font-weight:500;"><i class="bi bi-check-circle"></i> Password berhasil diubah.</span>
        @endif
    </div>
</form>
