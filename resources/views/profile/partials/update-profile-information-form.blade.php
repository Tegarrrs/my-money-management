<form id="send-verification" method="post" action="{{ route('verification.send') }}">
    @csrf
</form>

<form method="post" action="{{ route('profile.update') }}">
    @csrf
    @method('patch')

    <div class="mb-3">
        <label for="name" class="form-label" style="font-size:13px;font-weight:600;">Nama Lengkap</label>
        <input id="name" name="name" type="text" class="form-control" style="font-size:13px;border-radius:9px;" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name" />
        @error('name')<div class="text-danger mt-1" style="font-size:12px;">{{ $message }}</div>@enderror
    </div>

    <div class="mb-4">
        <label for="email" class="form-label" style="font-size:13px;font-weight:600;">Email</label>
        <input id="email" name="email" type="email" class="form-control" style="font-size:13px;border-radius:9px;" value="{{ old('email', $user->email) }}" required autocomplete="username" />
        @error('email')<div class="text-danger mt-1" style="font-size:12px;">{{ $message }}</div>@enderror

        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div class="mt-2 text-warning" style="font-size:12px;">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> Email Anda belum diverifikasi. 
                <button form="send-verification" class="btn btn-link p-0 m-0 align-baseline text-warning" style="font-size:12px;text-decoration:underline;box-shadow:none;">Kirim ulang email verifikasi.</button>
            </div>
            @if (session('status') === 'verification-link-sent')
                <div class="mt-2 text-success" style="font-size:12px;">
                    <i class="bi bi-check-circle-fill me-1"></i> Link verifikasi baru telah dikirim ke email Anda.
                </div>
            @endif
        @endif
    </div>

    <div class="d-flex align-items-center gap-3">
        <button type="submit" class="btn-dp btn-dp-primary"><i class="bi bi-floppy"></i> Simpan Pilihan</button>
        @if (session('status') === 'profile-updated')
            <span class="text-success" style="font-size:13px;font-weight:500;"><i class="bi bi-check-circle"></i> Berhasil disimpan.</span>
        @endif
    </div>
</form>
