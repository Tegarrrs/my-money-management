<x-guest-layout>
    <div class="auth-form-wrap">
        <div class="form-head">
            <div class="form-greeting" id="form-greeting">Selamat datang kembali</div>
            <h2 id="form-heading">Masuk ke Dompetra</h2>
            <p id="form-sub">Lanjutkan perjalanan finansialmu hari ini.</p>
        </div>

        <div class="auth-tabs" id="authTabs">
            <a href="{{ route('login') }}" class="auth-tab active" style="text-decoration: none;">Masuk</a>
            <a href="{{ route('register') }}" class="auth-tab" style="text-decoration: none;">Daftar</a>
        </div>

        @if (session('status'))
            <div class="form-alert alert-success visible">
                <i class="bi bi-check-circle-fill"></i>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="form-alert alert-error visible">
                <i class="bi bi-exclamation-circle-fill"></i>
                <span>Ada masalah dengan input Anda. Coba lagi.</span>
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" id="login-form">
            @csrf
            <div class="field-group">
                <label class="field-label" for="loginEmail">Email</label>
                <div class="field-wrap">
                    <input type="email" name="email" id="loginEmail" class="finia-input @error('email') error @enderror" placeholder="nama@email.com"
                        value="{{ old('email') }}" required autofocus autocomplete="username" />
                    <i class="bi bi-envelope field-icon"></i>
                </div>
                @error('email')
                <div class="field-error visible" id="loginEmail-err">{{ $message }}</div>
                @enderror
            </div>

            <div class="field-group">
                <label class="field-label" for="loginPass">Kata Sandi</label>
                <div class="field-wrap">
                    <input type="password" name="password" id="loginPass" class="finia-input @error('password') error @enderror" placeholder="Minimal 8 karakter"
                        required autocomplete="current-password" />
                    <i class="bi bi-lock field-icon"></i>
                    <i class="bi bi-eye field-icon-right" id="toggleLoginPass"
                        onclick="togglePass('loginPass','toggleLoginPass')"></i>
                </div>
                @error('password')
                <div class="field-error visible" id="loginPass-err">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-options">
                <label class="custom-check">
                    <input type="checkbox" name="remember" id="remember_me" />
                    <span>Ingat saya</span>
                </label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="forgot-link">Lupa kata sandi?</a>
                @endif
            </div>

            <button type="submit" class="btn-submit" id="loginBtn">
                <span class="btn-label"><i class="bi bi-arrow-right-circle me-1"></i> Masuk</span>
            </button>

            <div class="auth-divider">atau lanjutkan dengan</div>

            <a href="{{ route('auth.google', ['action' => 'login']) }}" class="social-btn" style="text-decoration: none;">
                <svg width="18" height="18" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M47.532 24.552c0-1.636-.142-3.21-.41-4.728H24.48v8.944h12.95c-.558 2.996-2.252 5.538-4.798 7.244v6.02h7.766c4.544-4.186 7.134-10.35 7.134-17.48z"
                        fill="#4285F4" />
                    <path
                        d="M24.48 48c6.48 0 11.918-2.148 15.89-5.82l-7.766-6.02c-2.152 1.44-4.906 2.29-8.124 2.29-6.248 0-11.546-4.222-13.434-9.9H2.998v6.218C6.952 42.954 15.128 48 24.48 48z"
                        fill="#34A853" />
                    <path
                        d="M11.046 28.55A14.432 14.432 0 0 1 10.522 24c0-1.58.274-3.112.762-4.55V13.23H2.998A23.976 23.976 0 0 0 .48 24c0 3.874.928 7.538 2.518 10.77l8.048-6.22z"
                        fill="#FBBC05" />
                    <path
                        d="M24.48 9.548c3.518 0 6.674 1.21 9.156 3.584l6.866-6.866C36.392 2.378 30.96 0 24.48 0 15.128 0 6.952 5.046 2.998 13.23l8.05 6.22c1.886-5.678 7.184-9.902 13.432-9.902z"
                        fill="#EA4335" />
                </svg>
                Lanjutkan dengan Google
            </a>
        </form>

        <div class="auth-switch" id="authSwitch">
            Belum punya akun? <a href="{{ route('register') }}">Daftar sekarang</a>
        </div>
        
        <script>
            document.getElementById('login-form').addEventListener('submit', function() {
                var btn = document.getElementById('loginBtn');
                btn.classList.add('loading');
                btn.innerHTML = '<div class="spinner" style="display:block;"></div>';
            });
        </script>
    </div>
</x-guest-layout>