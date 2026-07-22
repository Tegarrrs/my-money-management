@extends('layouts.main')

@section('title', 'Mulai dengan Dompetra')
@section('subtitle', 'Siapkan akun keuanganmu dalam tiga langkah')

@section('content')
    <div class="onboarding-shell">
        <div class="onboarding-heading">
            <span class="onboarding-kicker">Pengaturan awal</span>
            <h2>Selamat datang, {{ explode(' ', auth()->user()->name)[0] }}!</h2>
            <p>Lengkapi tiga langkah singkat agar laporan dan insight keuanganmu langsung berguna.</p>
        </div>

        <div class="onboarding-progress" aria-label="Progres onboarding">
            @foreach([1 => 'Buat dompet', 2 => 'Kategori awal', 3 => 'Transaksi pertama'] as $number => $label)
                <div class="onboarding-progress-item {{ $step >= $number ? 'active' : '' }} {{ $step > $number ? 'done' : '' }}">
                    <span>{{ $step > $number ? '✓' : $number }}</span>
                    <small>{{ $label }}</small>
                </div>
            @endforeach
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <section class="onboarding-card">
            @if($step === 1)
                <div class="onboarding-card-icon"><i class="bi bi-wallet2"></i></div>
                <h3>Buat dompet pertamamu</h3>
                <p class="onboarding-card-sub">Dompet mewakili rekening bank, uang tunai, atau e-wallet yang kamu gunakan.</p>

                <form method="POST" action="{{ route('onboarding.wallet') }}" class="onboarding-form">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label" for="onboardingWalletName">Nama dompet</label>
                            <input class="form-control" id="onboardingWalletName" name="name" value="{{ old('name') }}" placeholder="Contoh: BCA Utama" required autofocus>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="onboardingWalletType">Jenis</label>
                            <select class="form-select" id="onboardingWalletType" name="type" required>
                                <option value="bank">Rekening Bank</option>
                                <option value="cash">Uang Tunai</option>
                                <option value="e-wallet">E-Wallet</option>
                                <option value="investment">Investasi</option>
                                <option value="other">Lainnya</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="onboardingInitialBalance">Saldo saat ini</label>
                            <div class="input-group"><span class="input-group-text">Rp</span><input type="number" min="0" class="form-control" id="onboardingInitialBalance" name="initial_balance" value="{{ old('initial_balance', 0) }}"></div>
                            <div class="form-text">Saldo awal dicatat sebagai koreksi saldo dan tidak dihitung sebagai pemasukan.</div>
                        </div>
                    </div>
                    <button class="btn-rp btn-rp-primary onboarding-primary" type="submit">Simpan & lanjutkan <i class="bi bi-arrow-right"></i></button>
                </form>
            @elseif($step === 2)
                <div class="onboarding-card-icon"><i class="bi bi-tags"></i></div>
                <h3>Kategori awal sudah disiapkan</h3>
                <p class="onboarding-card-sub">Kategori membantu Dompetra membaca pola pemasukan dan pengeluaranmu. Semuanya dapat diubah nanti.</p>

                <div class="onboarding-category-columns">
                    <div>
                        <h4><i class="bi bi-arrow-down-left text-success"></i> Pemasukan</h4>
                        <div class="onboarding-category-list">
                            @foreach($incomeCategories as $category)<span>{{ $category->name }}</span>@endforeach
                        </div>
                    </div>
                    <div>
                        <h4><i class="bi bi-arrow-up-right text-danger"></i> Pengeluaran</h4>
                        <div class="onboarding-category-list">
                            @foreach($expenseCategories as $category)<span>{{ $category->name }}</span>@endforeach
                        </div>
                    </div>
                </div>

                <div class="onboarding-actions">
                    <a href="{{ route('category.index') }}" class="btn-rp btn-rp-default">Sesuaikan kategori</a>
                    <form method="POST" action="{{ route('onboarding.categories') }}">@csrf<button class="btn-rp btn-rp-primary" type="submit">Kategori sudah cocok <i class="bi bi-arrow-right"></i></button></form>
                </div>
            @else
                <div class="onboarding-card-icon"><i class="bi bi-arrow-left-right"></i></div>
                <h3>Catat transaksi pertamamu</h3>
                <p class="onboarding-card-sub">Gunakan transaksi nyata agar dashboard mulai memberikan gambaran kondisi keuangan.</p>

                <form method="POST" action="{{ route('onboarding.transaction') }}" class="onboarding-form">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="onboardingType">Jenis transaksi</label>
                            <select class="form-select" id="onboardingType" name="type" required>
                                <option value="expense" {{ old('type') === 'expense' ? 'selected' : '' }}>Pengeluaran</option>
                                <option value="income" {{ old('type') === 'income' ? 'selected' : '' }}>Pemasukan</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="onboardingWallet">Dompet</label>
                            <select class="form-select" id="onboardingWallet" name="wallet_id" required>
                                @foreach($wallets as $wallet)<option value="{{ $wallet->id }}" {{ (string) old('wallet_id') === (string) $wallet->id ? 'selected' : '' }}>{{ $wallet->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label" for="onboardingDescription">Deskripsi</label>
                            <input class="form-control" id="onboardingDescription" name="description" value="{{ old('description') }}" placeholder="Contoh: Belanja kebutuhan harian" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="onboardingAmount">Jumlah</label>
                            <div class="input-group"><span class="input-group-text">Rp</span><input type="number" min="1" class="form-control" id="onboardingAmount" name="amount" value="{{ old('amount') }}" required></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="onboardingCategory">Kategori</label>
                            <select class="form-select" id="onboardingCategory" name="category_id">
                                <option value="">Tanpa kategori</option>
                                <optgroup label="Pengeluaran">@foreach($expenseCategories as $category)<option value="{{ $category->id }}" data-type="expense" {{ (string) old('category_id') === (string) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>@endforeach</optgroup>
                                <optgroup label="Pemasukan">@foreach($incomeCategories as $category)<option value="{{ $category->id }}" data-type="income" {{ (string) old('category_id') === (string) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>@endforeach</optgroup>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="onboardingDate">Tanggal</label>
                            <input type="date" class="form-control" id="onboardingDate" name="transaction_date" value="{{ old('transaction_date', now()->toDateString()) }}" required>
                        </div>
                    </div>
                    <button class="btn-rp btn-rp-primary onboarding-primary" type="submit">Simpan & buka dashboard <i class="bi bi-check-lg"></i></button>
                </form>
            @endif
        </section>

        <form method="POST" action="{{ route('onboarding.skip') }}" class="onboarding-skip">@csrf<button type="submit">Lewati untuk sekarang</button></form>
    </div>
@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const type = document.getElementById('onboardingType');
            const category = document.getElementById('onboardingCategory');
            if (!type || !category) return;

            function syncCategories() {
                Array.from(category.options).forEach(function (option) {
                    if (!option.dataset.type) return;
                    option.hidden = option.dataset.type !== type.value;
                    option.disabled = option.hidden;
                });

                if (category.selectedOptions[0]?.disabled) category.value = '';
            }

            type.addEventListener('change', syncCategories);
            syncCategories();
        });
    </script>
@endpush
