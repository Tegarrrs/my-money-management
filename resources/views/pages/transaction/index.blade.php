@extends('layouts.main')

@section('title', 'Transaksi')
@section('subtitle', 'Kelola semua transaksi keuanganmu')

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
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Filter Bar -->
    <form method="GET" action="{{ route('transaction.index') }}" class="filter-bar mb-3">
        <div class="row g-3 align-items-end">
            <div class="col-sm-6 col-md-3">
                <label class="form-label">Dari Tanggal</label>
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>
            <div class="col-sm-6 col-md-3">
                <label class="form-label">Sampai Tanggal</label>
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
            </div>
            <div class="col-sm-6 col-md-2">
                <label class="form-label">Kategori</label>
                <select name="category_id" class="form-select">
                    <option value="">Semua kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6 col-md-2">
                <label class="form-label">Jenis</label>
                <select name="type" class="form-select">
                    <option value="">Semua jenis</option>
                    <option value="income"  {{ request('type') === 'income'  ? 'selected' : '' }}>Pemasukan</option>
                    <option value="expense" {{ request('type') === 'expense' ? 'selected' : '' }}>Pengeluaran</option>
                </select>
            </div>
            <div class="col-sm-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn-primary-dp"><i class="bi bi-funnel-fill"></i> Filter</button>
                <a href="{{ route('transaction.index') }}" class="btn-outline-dp">Reset</a>
            </div>
        </div>
    </form>

    <div class="table-card">
        <div class="table-card-header">
            <h2 class="table-card-title">
                Semua Transaksi
                <span style="font-size:13px;font-weight:500;color:var(--color-muted)">({{ $transactions->total() }})</span>
            </h2>
            <div class="d-flex gap-2">
                <button class="btn-outline-dp"><i class="bi bi-download"></i> Ekspor</button>
                <button class="btn-primary-dp" data-bs-toggle="modal" data-bs-target="#txModal" onclick="openCreate()">
                    <i class="bi bi-plus-lg"></i> Tambah Transaksi
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table dompetra-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Deskripsi</th>
                        <th>Kategori</th>
                        <th>Dompet</th>
                        <th>Jenis</th>
                        <th class="text-end">Jumlah</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $tx)
                        @php $isIncome = $tx->amount >= 0; @endphp
                        <tr>
                            <td><span class="tx-date">{{ $tx->transaction_date->format('d M Y') }}</span></td>
                            <td><span class="tx-desc">{{ $tx->description ?? '-' }}</span></td>
                            <td>
                                @if($tx->category)
                                    <span class="cat-badge">
                                        <span class="cat-dot" style="background:{{ $tx->category->type === 'income' ? '#15803d' : '#b91c1c' }}"></span>
                                        {{ $tx->category->name }}
                                    </span>
                                @else
                                    <span style="color:var(--color-muted);font-size:12px;">—</span>
                                @endif
                            </td>
                            <td style="font-size:12px;color:var(--color-muted)">{{ $tx->wallet?->name ?? '—' }}</td>
                            <td>
                                @if($isIncome)
                                    <span class="cat-badge" style="background:#f0fdf4;color:#15803d;"><span class="cat-dot" style="background:#15803d;"></span>Pemasukan</span>
                                @else
                                    <span class="cat-badge" style="background:#fff1f2;color:#b91c1c;"><span class="cat-dot" style="background:#b91c1c;"></span>Pengeluaran</span>
                                @endif
                            </td>
                            <td class="amount-cell {{ $isIncome ? 'amount-income' : 'amount-expense' }}">
                                {{ $isIncome ? '+' : '−' }} {{ $tx->formatted_amount }}
                            </td>
                            <td class="text-end" style="white-space:nowrap;">
                                <button class="btn-outline-dp py-1 px-2 me-1" style="font-size:12px;"
                                    onclick="editTx({{ $tx->id }}, '{{ addslashes($tx->description ?? '') }}', {{ abs($tx->amount) }}, '{{ $tx->transaction_date->format('Y-m-d') }}', {{ $tx->wallet_id ?? 'null' }}, {{ $tx->category_id ?? 'null' }}, '{{ $isIncome ? 'income' : 'expense' }}')"
                                    data-bs-toggle="modal" data-bs-target="#txModal"
                                    title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" action="{{ route('transaction.destroy', $tx) }}" class="d-inline"
                                      onsubmit="return confirm('Hapus transaksi ini? Saldo dompet akan dikembalikan.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-outline-dp py-1 px-2"
                                        style="font-size:12px;color:var(--color-expense);border-color:var(--color-expense-ring);"
                                        title="Hapus">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5" style="color:var(--color-muted)">
                                <i class="bi bi-inbox d-block mb-2" style="font-size:2.5rem;"></i>
                                Belum ada transaksi.
                                <button class="btn-primary-dp ms-2" data-bs-toggle="modal" data-bs-target="#txModal" onclick="openCreate()">Tambah sekarang</button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="dp-pagination">
            <span class="page-info">
                Menampilkan {{ $transactions->firstItem() ?? 0 }}–{{ $transactions->lastItem() ?? 0 }} dari {{ $transactions->total() }} transaksi
            </span>
            <div class="d-flex gap-1">
                @if($transactions->onFirstPage())
                    <div class="page-btn disabled"><i class="bi bi-chevron-left"></i></div>
                @else
                    <a href="{{ $transactions->previousPageUrl() }}" class="page-btn"><i class="bi bi-chevron-left"></i></a>
                @endif

                @foreach($transactions->getUrlRange(1, $transactions->lastPage()) as $page => $url)
                    <a href="{{ $url }}" class="page-btn {{ $page == $transactions->currentPage() ? 'active' : '' }}">{{ $page }}</a>
                @endforeach

                @if($transactions->hasMorePages())
                    <a href="{{ $transactions->nextPageUrl() }}" class="page-btn"><i class="bi bi-chevron-right"></i></a>
                @else
                    <div class="page-btn disabled"><i class="bi bi-chevron-right"></i></div>
                @endif
            </div>
        </div>
    </div>

    <!-- Transaction Modal -->
    <div class="modal fade" id="txModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" style="max-width:440px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="txModalTitle">Tambah Transaksi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="txForm" method="POST" action="{{ route('transaction.store') }}">
                    @csrf
                    <span id="txMethodField"></span>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Jenis Transaksi <span class="text-danger">*</span></label>
                            <div class="type-toggle">
                                <div class="type-btn" id="txTypeExpense" onclick="selectTxType('expense')">
                                    <i class="bi bi-arrow-up-right me-1"></i> Pengeluaran
                                </div>
                                <div class="type-btn" id="txTypeIncome" onclick="selectTxType('income')">
                                    <i class="bi bi-arrow-down-left me-1"></i> Pemasukan
                                </div>
                            </div>
                            <input type="hidden" name="type" id="txType" value="expense">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kategori <span style="color:var(--color-muted);font-size:12px;">(opsional – akan diisi AI nanti)</span></label>
                            <select name="category_id" id="txCategory" class="form-select" onchange="updateTypeFromCategory()">
                                <option value="">— Tanpa kategori —</option>
                                @if($categories->where('type','expense')->isNotEmpty())
                                    <optgroup label="Pengeluaran">
                                        @foreach($categories->where('type','expense') as $cat)
                                            <option value="{{ $cat->id }}" data-type="expense">{{ $cat->name }}</option>
                                        @endforeach
                                    </optgroup>
                                @endif
                                @if($categories->where('type','income')->isNotEmpty())
                                    <optgroup label="Pemasukan">
                                        @foreach($categories->where('type','income') as $cat)
                                            <option value="{{ $cat->id }}" data-type="income">{{ $cat->name }}</option>
                                        @endforeach
                                    </optgroup>
                                @endif
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Dompet <span class="text-danger">*</span></label>
                            <select name="wallet_id" id="txWallet" class="form-select" required>
                                <option value="">Pilih dompet…</option>
                                @foreach($wallets as $w)
                                    <option value="{{ $w->id }}">{{ $w->name }} ({{ $w->formatted_balance }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <input type="text" name="description" id="txDesc" class="form-control"
                                   placeholder="cth. Belanja di Alfamart">
                        </div>
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label">Jumlah (Rp) <span class="text-danger">*</span></label>
                                <input type="number" name="amount" id="txAmount" class="form-control"
                                       placeholder="0" min="1" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                                <input type="date" name="transaction_date" id="txDate" class="form-control"
                                       value="{{ now()->format('Y-m-d') }}" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-outline-dp" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn-primary-dp">
                            <i class="bi bi-check-lg"></i> Simpan Transaksi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('js')
<script>
function selectTxType(type) {
    document.getElementById('txType').value = type;
    document.getElementById('txTypeExpense').className = 'type-btn' + (type === 'expense' ? ' expense-active' : '');
    document.getElementById('txTypeIncome').className  = 'type-btn' + (type === 'income'  ? ' income-active'  : '');
}

function updateTypeFromCategory() {
    const sel  = document.getElementById('txCategory');
    const type = sel.options[sel.selectedIndex]?.getAttribute('data-type');
    if (type) selectTxType(type);
}

function openCreate() { resetTxModal(); }

function editTx(id, desc, amount, date, walletId, categoryId, type) {
    resetTxModal();
    document.getElementById('txModalTitle').textContent = 'Edit Transaksi';
    document.getElementById('txDesc').value   = desc;
    document.getElementById('txAmount').value = amount;
    document.getElementById('txDate').value   = date;
    selectTxType(type || 'expense');

    if (walletId)   document.getElementById('txWallet').value   = walletId;
    if (categoryId) {
        document.getElementById('txCategory').value = categoryId;
        updateTypeFromCategory();
    }

    const form = document.getElementById('txForm');
    form.action = '/transaction/' + id;
    document.getElementById('txMethodField').innerHTML = '<input type="hidden" name="_method" value="PUT">';
}

function resetTxModal() {
    document.getElementById('txModalTitle').textContent = 'Tambah Transaksi';
    document.getElementById('txForm').reset();
    document.getElementById('txForm').action = '{{ route('transaction.store') }}';
    document.getElementById('txMethodField').innerHTML = '';
    document.getElementById('txDate').value = '{{ now()->format('Y-m-d') }}';
    selectTxType('expense');
}

document.getElementById('txModal').addEventListener('hidden.bs.modal', resetTxModal);
selectTxType('expense');
</script>
@endpush
