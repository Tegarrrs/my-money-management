@extends('layouts.main')

@section('title', 'Dompet')
@section('subtitle', 'Kelola semua dompet keuanganmu')

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-3">
            <i class="bi bi-exclamation-circle-fill me-2"></i>
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="table-card">
        <div class="table-card-header">
            <h2 class="table-card-title">Dompet Saya</h2>
            <button class="btn-primary-dp" data-bs-toggle="modal" data-bs-target="#walletModal">
                <i class="bi bi-plus-lg"></i> Tambah Dompet
            </button>
        </div>

        @if($wallets->isEmpty())
            <div class="text-center py-5" style="color:var(--color-muted)">
                <i class="bi bi-wallet2 d-block mb-3" style="font-size:3rem;"></i>
                <p>Belum ada dompet. Tambahkan dompet pertamamu!</p>
            </div>
        @else
            <div class="row g-3 p-3">
                @foreach($wallets as $wallet)
                    <div class="col-sm-6 col-lg-4">
                        <div class="summary-card" style="position:relative;">
                            <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                                <div>
                                    <div class="card-icon ic-balance" style="background:{{ $wallet->color ?? '#e8f5e9' }}">
                                        <i class="bi {{ $wallet->icon ?? 'bi-wallet2' }}"></i>
                                    </div>
                                    <div class="card-label mt-2">{{ $wallet->name }}</div>
                                    <div style="font-size:11px;color:var(--color-muted);text-transform:capitalize;">{{ $wallet->type }}</div>
                                </div>
                                <div class="d-flex gap-1">
                                    <button class="btn-outline-dp py-1 px-2"
                                        style="font-size:12px;"
                                        onclick="editWallet({{ $wallet->id }}, '{{ addslashes($wallet->name) }}', '{{ $wallet->type }}', '{{ $wallet->icon }}', '{{ $wallet->color }}', {{ $wallet->allow_negative_balance ? 'true' : 'false' }}, {{ $wallet->balance }})"
                                        title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" action="{{ route('wallet.destroy', $wallet) }}"
                                          onsubmit="return confirm('Hapus dompet {{ addslashes($wallet->name) }}?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-outline-dp py-1 px-2" style="font-size:12px;color:var(--color-expense);border-color:var(--color-expense-ring);" title="Hapus">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <div class="card-amount mt-2">{{ $wallet->formatted_balance }}</div>
                            <div class="card-meta">
                                {{ $wallet->transactions()->count() }} transaksi
                                {{ $wallet->allow_negative_balance ? ' · Saldo negatif diizinkan' : '' }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Wallet Modal -->
    <div class="modal fade" id="walletModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" style="max-width:440px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="walletModalTitle">Tambah Dompet</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="walletForm" method="POST" action="{{ route('wallet.store') }}">
                    @csrf
                    <span id="walletMethodField"></span>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Dompet <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="wName" class="form-control"
                                   placeholder="cth. BCA, GoPay, Dompet Tunai" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jenis <span class="text-danger">*</span></label>
                            <select name="type" id="wType" class="form-select" required>
                                <option value="">Pilih jenis...</option>
                                <option value="cash">Uang Tunai</option>
                                <option value="bank">Rekening Bank</option>
                                <option value="e-wallet">E-Wallet</option>
                                <option value="investment">Investasi</option>
                                <option value="other">Lainnya</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ikon Dompet</label>
                            <input type="hidden" name="icon" id="wIcon" value="bi-wallet2">
                            <div class="d-flex flex-wrap gap-2" id="iconPickerGrid">
                                @php
                                    $icons = ['bi-wallet2', 'bi-bank', 'bi-cash-stack', 'bi-credit-card', 'bi-piggy-bank', 'bi-safe', 'bi-briefcase', 'bi-phone', 'bi-house', 'bi-basket'];
                                @endphp
                                @foreach($icons as $ic)
                                    <button type="button" class="btn btn-outline-secondary icon-btn" data-icon="{{ $ic }}" style="font-size: 1.25rem; width: 44px; height: 44px; padding: 0;">
                                        <i class="bi {{ $ic }}"></i>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" id="wBalanceLabel">Saldo Awal (Rp)</label>
                            <input type="number" name="initial_balance" id="wBalance" class="form-control"
                                   placeholder="0" min="0" value="0">
                            <div class="form-text" id="wBalanceHint">Isi jika dompet sudah punya saldo sebelumnya.</div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="allow_negative_balance"
                                       id="wAllowNeg" value="1">
                                <label class="form-check-label" for="wAllowNeg">
                                    Izinkan saldo negatif
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-outline-dp" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn-primary-dp">
                            <i class="bi bi-check-lg"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('js')
<script>
function editWallet(id, name, type, icon, color, allowNeg, balance) {
    document.getElementById('walletModalTitle').textContent = 'Edit Dompet';
    document.getElementById('wName').value    = name;
    document.getElementById('wType').value    = type;
    document.getElementById('wIcon').value    = icon || 'bi-wallet2';
    document.getElementById('wAllowNeg').checked = allowNeg;
    document.getElementById('wBalance').value = balance ?? 0;
    document.getElementById('wBalanceLabel').textContent = 'Koreksi Saldo (Rp)';
    document.getElementById('wBalanceHint').textContent  = 'Ubah hanya jika perlu koreksi saldo manual.';

    // Update Visual Icon Picker
    document.querySelectorAll('.icon-btn').forEach(b => {
        b.classList.remove('btn-primary', 'text-white');
        b.classList.add('btn-outline-secondary');
        if (b.dataset.icon === (icon || 'bi-wallet2')) {
            b.classList.remove('btn-outline-secondary');
            b.classList.add('btn-primary', 'text-white');
        }
    });

    const form = document.getElementById('walletForm');
    form.action = '/wallet/' + id;
    document.getElementById('walletMethodField').innerHTML = '<input type="hidden" name="_method" value="PUT">';

    new bootstrap.Modal(document.getElementById('walletModal')).show();
}

document.getElementById('walletModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('walletForm').reset();
    document.getElementById('wBalance').value = 0;
    document.getElementById('wBalanceLabel').textContent = 'Saldo Awal (Rp)';
    document.getElementById('wBalanceHint').textContent  = 'Isi jika dompet sudah punya saldo sebelumnya.';
    document.getElementById('walletForm').action = '{{ route('wallet.store') }}';
    document.getElementById('walletMethodField').innerHTML = '';
    document.getElementById('walletModalTitle').textContent = 'Tambah Dompet';
    document.getElementById('wIcon').value = 'bi-wallet2';
    
    // Reset Visual Icon Picker
    document.querySelectorAll('.icon-btn').forEach(b => {
        b.classList.remove('btn-primary', 'text-white');
        b.classList.add('btn-outline-secondary');
        if (b.dataset.icon === 'bi-wallet2') {
            b.classList.remove('btn-outline-secondary');
            b.classList.add('btn-primary', 'text-white');
        }
    });
});

// Icon Picker Click Event
document.addEventListener('DOMContentLoaded', function() {
    // Initial state
    document.querySelectorAll('.icon-btn').forEach(b => {
        if (b.dataset.icon === 'bi-wallet2') {
            b.classList.remove('btn-outline-secondary');
            b.classList.add('btn-primary', 'text-white');
        }
    });

    document.querySelectorAll('.icon-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.icon-btn').forEach(b => {
                b.classList.remove('btn-primary', 'text-white');
                b.classList.add('btn-outline-secondary');
            });
            this.classList.remove('btn-outline-secondary');
            this.classList.add('btn-primary', 'text-white');
            document.getElementById('wIcon').value = this.dataset.icon;
        });
    });
</script>
@endpush
