@extends('layouts.main')

@section('title', 'Transaksi')
@section('subtitle', 'Kelola semua transaksi keuanganmu')



@section('content')

    {{-- Flash messages --}}
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

    {{-- ── Filter Bar ──────────────────────────────────────────────── --}}
    <form method="GET" action="{{ route('transaction.index') }}" class="filter-bar mb-3">
        <div class="filter-bar-label">Filter transaksi</div>
        <div class="filter-row">
            <div class="filter-group">
                <label>Dari tanggal</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}">
            </div>
            <div class="filter-group">
                <label>Sampai tanggal</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}">
            </div>
            <div class="filter-group">
                <label>Dompet</label>
                <select name="wallet_id">
                    <option value="">Semua dompet</option>
                    @foreach($wallets as $w)
                        <option value="{{ $w->id }}" {{ request('wallet_id') == $w->id ? 'selected' : '' }}>{{ $w->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-group">
                <label>Kategori</label>
                <select name="category_id">
                    <option value="">Semua kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-group">
                <label>Jenis</label>
                <select name="type">
                    <option value="">Semua jenis</option>
                    <option value="income"    {{ request('type') === 'income'   ? 'selected' : '' }}>Pemasukan</option>
                    <option value="expense"   {{ request('type') === 'expense'  ? 'selected' : '' }}>Pengeluaran</option>
                    <option value="transfer"  {{ request('type') === 'transfer' ? 'selected' : '' }}>Transfer</option>
                </select>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn-dp btn-dp-primary">
                    <i class="bi bi-funnel-fill"></i> Filter
                </button>
                <a href="{{ route('transaction.index') }}" class="btn-dp btn-dp-default">Reset</a>
            </div>
        </div>
    </form>

    {{-- ── Summary Cards ───────────────────────────────────────────── --}}
    @php
        $totalIncome   = $transactions->getCollection()->whereNull('transfer_group_id')->where('amount', '>', 0)->sum('amount');
        $totalExpense  = abs($transactions->getCollection()->whereNull('transfer_group_id')->where('amount', '<', 0)->sum('amount'));
        $totalBalance  = $totalIncome - $totalExpense;
    @endphp
    <div class="summary-grid">
        <div class="summary-card">
            <div class="s-label">Pemasukan</div>
            <div class="s-value s-income">Rp {{ number_format($totalIncome, 0, ',', '.') }}</div>
            <div class="s-sub">periode ini</div>
        </div>
        <div class="summary-card">
            <div class="s-label">Pengeluaran</div>
            <div class="s-value s-expense">Rp {{ number_format($totalExpense, 0, ',', '.') }}</div>
            <div class="s-sub">periode ini</div>
        </div>
        <div class="summary-card">
            <div class="s-label">Selisih</div>
            <div class="s-value s-balance" style="color:{{ $totalBalance >= 0 ? '#15803d' : '#b91c1c' }}">
                {{ $totalBalance >= 0 ? '' : '-' }}Rp {{ number_format(abs($totalBalance), 0, ',', '.') }}
            </div>
            <div class="s-sub">pemasukan - pengeluaran</div>
        </div>
    </div>

    {{-- ── Table Card ───────────────────────────────────────────────── --}}
    <div class="table-card">
        <div class="table-card-header">
            <div class="table-card-title">
                Semua Transaksi
                <span class="tx-count-badge">{{ $transactions->total() }}</span>
            </div>
            <div class="header-right">
                <a href="{{ route('import.show') }}" class="btn-dp btn-dp-default">
                    <i class="bi bi-clipboard-pulse"></i> Import
                </a>
                <button class="btn-dp btn-dp-default">
                    <i class="bi bi-download"></i> Ekspor
                </button>
                <button class="btn-dp btn-dp-primary" data-bs-toggle="modal" data-bs-target="#txModal" onclick="openCreate()">
                    <i class="bi bi-plus-lg"></i> Tambah Transaksi
                </button>
            </div>
        </div>

        <div class="tx-table-wrap">
            <table class="tx-table">
                <thead>
                    <tr>
                        <th style="width:120px;">Tanggal</th>
                        <th style="width:220px;">Deskripsi</th>
                        <th style="width:140px;">Kategori</th>
                        <th style="width:170px;">Dompet</th>
                        <th style="width:110px;">Jenis</th>
                        <th class="th-right" style="width:140px;">Jumlah</th>
                        <th class="th-right" style="width:90px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $tx)
                        @php
                            $isTransfer = !is_null($tx->transfer_group_id);
                            $isIncome   = !$isTransfer && $tx->amount >= 0;
                        @endphp
                        <tr>
                            {{-- Tanggal --}}
                            <td class="cell-date">
                                <div class="date-main">{{ $tx->transaction_date->format('d M Y') }}</div>
                                <div class="date-day">{{ $tx->transaction_date->translatedFormat('l') }}</div>
                            </td>

                            {{-- Deskripsi --}}
                            <td class="cell-desc">
                                <span class="desc-main" title="{{ $tx->description }}">{{ $tx->description ?? '—' }}</span>
                                @if($tx->detail)
                                    <span class="desc-detail" title="{{ $tx->detail }}">{{ $tx->detail }}</span>
                                @endif
                            </td>

                            {{-- Kategori --}}
                            <td class="cell-cat">
                                @if($isTransfer)
                                    <span class="type-badge badge-transfer">
                                        <i class="bi bi-arrow-left-right" style="font-size:10px;"></i> Transfer
                                    </span>
                                @elseif($tx->category)
                                    <span class="cat-pill">
                                        <span class="cat-dot" style="background:{{ $tx->category->type === 'income' ? '#15803d' : '#b91c1c' }}"></span>
                                        {{ $tx->category->name }}
                                    </span>
                                @else
                                    <span style="color:#e5e7eb;font-size:12px;">—</span>
                                @endif
                            </td>

                            {{-- Dompet --}}
                            <td class="cell-wallet">
                                @if($isTransfer)
                                    @php
                                        $toWallet = \App\Models\Transaction::where('transfer_group_id', $tx->transfer_group_id)
                                            ->where('amount', '>', 0)->first()?->wallet;
                                    @endphp
                                    <div class="wallet-chip">
                                        <span style="font-weight:600;color:#111827;">{{ $tx->wallet?->name ?? '—' }}</span>
                                        <span class="wallet-arrow"><i class="bi bi-arrow-right"></i></span>
                                        <span style="font-weight:600;color:#111827;">{{ $toWallet?->name ?? '—' }}</span>
                                    </div>
                                @else
                                    <div class="wallet-chip">
                                        <i class="bi bi-wallet2" style="font-size:11px;flex-shrink:0;"></i>
                                        <span style="font-weight:600;color:#111827;overflow:hidden;text-overflow:ellipsis;">{{ $tx->wallet?->name ?? '—' }}</span>
                                    </div>
                                @endif
                            </td>

                            {{-- Jenis --}}
                            <td>
                                @if($isTransfer)
                                    <span class="type-badge badge-transfer">Transfer</span>
                                @elseif($isIncome)
                                    <span class="type-badge badge-income">Pemasukan</span>
                                @else
                                    <span class="type-badge badge-expense">Pengeluaran</span>
                                @endif
                            </td>

                            {{-- Jumlah --}}
                            <td class="cell-amount {{ $isTransfer ? 'amt-transfer' : ($isIncome ? 'amt-income' : 'amt-expense') }}">
                                @if($isTransfer)
                                    {{ $tx->formatted_amount }}
                                @else
                                    {{ $isIncome ? '+' : '−' }} {{ $tx->formatted_amount }}
                                @endif
                            </td>

                            {{-- Aksi --}}
                            <td class="cell-actions">
                                @php
                                    $toWalletId = null;
                                    if ($isTransfer) {
                                        $toWalletId = \App\Models\Transaction::where('transfer_group_id', $tx->transfer_group_id)
                                            ->where('amount', '>', 0)->value('wallet_id');
                                    }
                                @endphp
                                <button class="btn-icon me-1"
                                    data-bs-toggle="modal" data-bs-target="#txModal"
                                    onclick="editTx(
                                        {{ $tx->id }},
                                        '{{ addslashes($tx->description ?? '') }}',
                                        '{{ addslashes($tx->detail ?? '') }}',
                                        {{ abs($tx->amount) }},
                                        '{{ $tx->transaction_date->format('Y-m-d') }}',
                                        {{ $tx->wallet_id ?? 'null' }},
                                        {{ $tx->category_id ?? 'null' }},
                                        '{{ $isTransfer ? 'transfer' : ($isIncome ? 'income' : 'expense') }}',
                                        {{ $toWalletId ?? 'null' }}
                                    )"
                                    title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" action="{{ route('transaction.destroy', $tx) }}" class="d-inline"
                                      onsubmit="return confirm('Hapus transaksi ini? Saldo dompet akan dikembalikan.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-del" title="Hapus">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <i class="bi bi-inbox"></i>
                                    Belum ada transaksi. Klik <strong>Tambah Transaksi</strong> untuk memulai.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($transactions->hasPages())
        <div class="tx-pagination">
            <div class="pg-info">
                Menampilkan {{ $transactions->firstItem() }}–{{ $transactions->lastItem() }}
                dari {{ $transactions->total() }} transaksi
            </div>
            <div class="pg-btns">
                @if($transactions->onFirstPage())
                    <span class="pg-btn disabled"><i class="bi bi-chevron-left"></i></span>
                @else
                    <a href="{{ $transactions->previousPageUrl() }}" class="pg-btn"><i class="bi bi-chevron-left"></i></a>
                @endif

                @foreach($transactions->getUrlRange(1, $transactions->lastPage()) as $page => $url)
                    @if(abs($page - $transactions->currentPage()) <= 2 || $page === 1 || $page === $transactions->lastPage())
                        <a href="{{ $url }}" class="pg-btn {{ $page == $transactions->currentPage() ? 'active' : '' }}">{{ $page }}</a>
                    @elseif(abs($page - $transactions->currentPage()) === 3)
                        <span class="pg-btn disabled">…</span>
                    @endif
                @endforeach

                @if($transactions->hasMorePages())
                    <a href="{{ $transactions->nextPageUrl() }}" class="pg-btn"><i class="bi bi-chevron-right"></i></a>
                @else
                    <span class="pg-btn disabled"><i class="bi bi-chevron-right"></i></span>
                @endif
            </div>
        </div>
        @endif
    </div>

    {{-- ════════════════════════════════════════════════════════════
         MODAL TRANSAKSI — Create / Edit (Income, Expense, Transfer)
    ════════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="txModal" tabindex="-1" aria-labelledby="txModalLabel">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:500px;">
            <div class="modal-content" style="border-radius:16px;border:1px solid #e5e7eb;">
                <div class="modal-header" style="border-bottom:1px solid #f3f4f6;padding:18px 24px;">
                    <h5 class="modal-title" id="txModalLabel" style="font-size:15px;font-weight:700;">Tambah Transaksi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form id="txForm" method="POST" action="{{ route('transaction.store') }}">
                    @csrf
                    <span id="txMethodField"></span>
                    <input type="hidden" name="type" id="txType" value="expense">

                    <div class="modal-body" style="padding:20px 24px;">

                        {{-- Jenis --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold" style="font-size:13px;">Jenis Transaksi <span class="text-danger">*</span></label>
                            <div class="type-toggle">
                                <div class="type-btn expense-active" id="txTypeExpense" onclick="selectTxType('expense')">
                                    <i class="bi bi-arrow-up-right me-1"></i> Pengeluaran
                                </div>
                                <div class="type-btn" id="txTypeIncome" onclick="selectTxType('income')">
                                    <i class="bi bi-arrow-down-left me-1"></i> Pemasukan
                                </div>
                                <div class="type-btn" id="txTypeTransfer" onclick="selectTxType('transfer')">
                                    <i class="bi bi-arrow-left-right me-1"></i> Transfer
                                </div>
                            </div>
                        </div>

                        {{-- Normal fields --}}
                        <div id="normalSection">
                            <div class="mb-3">
                                <label class="form-label" style="font-size:13px;font-weight:500;">Dompet <span class="text-danger">*</span></label>
                                <select name="wallet_id" id="txWallet" class="form-select" style="font-size:13px;border-radius:9px;">
                                    <option value="">Pilih dompet…</option>
                                    @foreach($wallets as $w)
                                        <option value="{{ $w->id }}">{{ $w->name }} ({{ $w->formatted_balance }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" style="font-size:13px;font-weight:500;">
                                    Kategori <span style="color:#9ca3af;font-size:12px;">(opsional)</span>
                                </label>
                                <select name="category_id" id="txCategory" class="form-select" style="font-size:13px;border-radius:9px;" onchange="updateTypeFromCategory()">
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
                        </div>

                        {{-- Transfer fields --}}
                        <div id="transferSection" style="display:none;">
                            <div class="mb-3">
                                <label class="form-label" style="font-size:13px;font-weight:500;">Dompet Asal <span class="text-danger">*</span></label>
                                <select name="wallet_id" id="txFromWallet" class="form-select" style="font-size:13px;border-radius:9px;" disabled>
                                    <option value="">Pilih dompet asal…</option>
                                    @foreach($wallets as $w)
                                        <option value="{{ $w->id }}">{{ $w->name }} ({{ $w->formatted_balance }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" style="font-size:13px;font-weight:500;">Dompet Tujuan <span class="text-danger">*</span></label>
                                <select name="to_wallet_id" id="txToWallet" class="form-select" style="font-size:13px;border-radius:9px;" disabled>
                                    <option value="">Pilih dompet tujuan…</option>
                                    @foreach($wallets as $w)
                                        <option value="{{ $w->id }}">{{ $w->name }} ({{ $w->formatted_balance }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" style="font-size:13px;font-weight:500;">Biaya Admin <span style="color:#9ca3af;font-size:12px;">(jika ada)</span></label>
                                <input type="number" name="admin_fee" id="txAdminFee" class="form-control"
                                       style="font-size:13px;border-radius:9px;" placeholder="0" min="0" disabled>
                                <div class="form-text" style="font-size:11px;">Biaya admin akan didebet otomatis dari Dompet Asal.</div>
                            </div>
                        </div>

                        {{-- Deskripsi --}}
                        <div class="mb-3">
                            <label class="form-label" style="font-size:13px;font-weight:500;">Deskripsi</label>
                            <input type="text" name="description" id="txDesc" class="form-control"
                                   style="font-size:13px;border-radius:9px;"
                                   placeholder="cth. Beli makan siang, Gaji, Transfer BRI…">
                        </div>

                        {{-- Jumlah & Tanggal --}}
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label" style="font-size:13px;font-weight:500;">Jumlah (Rp) <span class="text-danger">*</span></label>
                                <input type="number" name="amount" id="txAmount" class="form-control"
                                       style="font-size:13px;border-radius:9px;"
                                       placeholder="0" min="1" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label" style="font-size:13px;font-weight:500;">Tanggal <span class="text-danger">*</span></label>
                                <input type="date" name="transaction_date" id="txDate" class="form-control"
                                       style="font-size:13px;border-radius:9px;"
                                       value="{{ now()->format('Y-m-d') }}" required>
                            </div>
                        </div>

                        {{-- Detail (collapsible) --}}
                        <div class="mb-1">
                            <button type="button" class="detail-toggle-btn" onclick="toggleDetail()">
                                <i class="bi bi-chevron-down" id="detailChevron"></i>
                                Tambah catatan / detail
                            </button>
                            <div id="detailSection" style="display:none;margin-top:8px;">
                                <textarea name="detail" id="txDetail" class="form-control" rows="3"
                                          style="font-size:13px;border-radius:9px;"
                                          placeholder="Item belanja, nomor referensi, catatan tambahan…"></textarea>
                            </div>
                        </div>

                    </div>

                    <div class="modal-footer" style="border-top:1px solid #f3f4f6;padding:16px 24px;">
                        <button type="button" class="btn-dp btn-dp-default" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn-dp btn-dp-primary" id="txSubmitBtn">
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
(function () {
    'use strict';

    /* ── Type toggle ─────────────────────────────────────────────── */
    window.selectTxType = function (type) {
        document.getElementById('txType').value = type;
        ['expense','income','transfer'].forEach(function (t) {
            var el = document.getElementById('txType' + t.charAt(0).toUpperCase() + t.slice(1));
            el.className = 'type-btn' + (t === type ? ' ' + t + '-active' : '');
        });
        var isTransfer = type === 'transfer';
        document.getElementById('normalSection').style.display   = isTransfer ? 'none' : 'block';
        document.getElementById('transferSection').style.display = isTransfer ? 'block' : 'none';
        document.getElementById('txWallet').disabled     = isTransfer;
        document.getElementById('txCategory').disabled   = isTransfer;
        document.getElementById('txFromWallet').disabled = !isTransfer;
        document.getElementById('txToWallet').disabled   = !isTransfer;
        
        var adminFeeEl = document.getElementById('txAdminFee');
        if (adminFeeEl) adminFeeEl.disabled = !isTransfer;
    };

    window.updateTypeFromCategory = function () {
        var sel  = document.getElementById('txCategory');
        var type = sel.options[sel.selectedIndex]?.getAttribute('data-type');
        if (type && type !== 'transfer') selectTxType(type);
    };

    /* ── Detail toggle ───────────────────────────────────────────── */
    window.toggleDetail = function () {
        var sec  = document.getElementById('detailSection');
        var chv  = document.getElementById('detailChevron');
        var open = sec.style.display === 'none';
        sec.style.display = open ? '' : 'none';
        chv.className = open ? 'bi bi-chevron-up' : 'bi bi-chevron-down';
    };

    /* ── Open create modal ───────────────────────────────────────── */
    window.openCreate = function () { resetTxModal(); };

    /* ── Open edit modal ─────────────────────────────────────────── */
    window.editTx = function (id, desc, detail, amount, date, walletId, categoryId, type, toWalletId) {
        resetTxModal();
        document.getElementById('txModalLabel').textContent = 'Edit Transaksi';
        selectTxType(type || 'expense');
        document.getElementById('txDesc').value   = desc;
        document.getElementById('txAmount').value = amount;
        document.getElementById('txDate').value   = date;

        if (type === 'transfer') {
            if (walletId)   document.getElementById('txFromWallet').value = walletId;
            if (toWalletId) document.getElementById('txToWallet').value   = toWalletId;
        } else {
            if (walletId)   document.getElementById('txWallet').value   = walletId;
            if (categoryId) {
                document.getElementById('txCategory').value = categoryId;
                updateTypeFromCategory();
            }
        }

        if (detail && detail.trim() !== '') {
            document.getElementById('txDetail').value = detail;
            document.getElementById('detailSection').style.display = '';
            document.getElementById('detailChevron').className = 'bi bi-chevron-up';
        }

        document.getElementById('txForm').action = '/transaction/' + id;
        document.getElementById('txMethodField').innerHTML = '<input type="hidden" name="_method" value="PUT">';
    };

    /* ── Reset modal ─────────────────────────────────────────────── */
    function resetTxModal() {
        document.getElementById('txModalLabel').textContent = 'Tambah Transaksi';
        document.getElementById('txForm').reset();
        document.getElementById('txForm').action = '{{ route('transaction.store') }}';
        document.getElementById('txMethodField').innerHTML = '';
        document.getElementById('txDate').value = '{{ now()->format('Y-m-d') }}';
        var adminFeeEl = document.getElementById('txAdminFee');
        if (adminFeeEl) adminFeeEl.value = '';
        document.getElementById('detailSection').style.display = 'none';
        document.getElementById('detailChevron').className = 'bi bi-chevron-down';
        selectTxType('expense');
    }

    document.getElementById('txModal').addEventListener('hidden.bs.modal', resetTxModal);
    selectTxType('expense');
})();
</script>
@endpush