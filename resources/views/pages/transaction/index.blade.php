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
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            <i class="bi bi-exclamation-circle-fill me-2"></i>{{ session('error') }}
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
                    <option value="uncategorized" {{ request('category_id') === 'uncategorized' ? 'selected' : '' }}>Tanpa kategori</option>
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
                    <option value="adjustment" {{ request('type') === 'adjustment' ? 'selected' : '' }}>Koreksi Saldo</option>
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

    {{-- ── Table Card ───────────────────────────────────────────────── --}}
    <div class="table-card">
        <div class="table-card-header">
            <div class="table-card-title">
                Semua Transaksi
                <span class="tx-count-badge">{{ $transactions->total() }}</span>
                <span class="text-muted ms-2" style="font-size:11px;font-weight:normal;">Tekan <kbd>N</kbd> baru, <kbd>/</kbd> cari</span>
            </div>
            <div class="header-right">
                <a href="{{ route('import.show') }}" class="btn-dp btn-dp-default">
                    <i class="bi bi-clipboard-pulse"></i> Import
                </a>
                <button type="button" class="btn-dp btn-dp-default" onclick="triggerExport()">
                    <i class="bi bi-download"></i> Ekspor
                </button>
                <button class="btn-dp btn-dp-primary" data-bs-toggle="modal" data-bs-target="#txModal" onclick="openCreate()">
                    <i class="bi bi-plus-lg"></i> Tambah Transaksi
                </button>
            </div>
        </div>

        <div id="transactionTableContainer">
             @include('pages.transaction.partials.table')
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════════
         MODAL TRANSAKSI — Create / Edit (Income, Expense, Transfer, Split)
    ════════════════════════════════════════════════════════════ --}}
    <div class="modal fade" id="txModal" tabindex="-1" aria-labelledby="txModalLabel">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:520px;">
            <form id="txForm" class="modal-content" method="POST" action="{{ route('transaction.store') }}" enctype="multipart/form-data" style="border-radius:16px;border:1px solid #e5e7eb;">
                @csrf
                <span id="txMethodField"></span>
                <input type="hidden" name="type" id="txType" value="expense">

                <div class="modal-header" style="border-bottom:1px solid #f3f4f6;padding:18px 24px;">
                    <h5 class="modal-title" id="txModalLabel" style="font-size:15px;font-weight:700;">Tambah Transaksi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

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
                            
                            {{-- Split Checkbox Toggle --}}
                            <div class="mb-3" id="splitToggleSection">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_split" id="txIsSplit" value="1" onchange="toggleSplitMode()">
                                    <label class="form-check-label fw-semibold" for="txIsSplit" style="font-size:13px;">Pecah Transaksi (Split Transaction)</label>
                                </div>
                            </div>

                            {{-- Dynamic Split Area --}}
                            <div id="splitSection" style="display:none; border:1px solid #e5e7eb; border-radius:12px; padding:16px; margin-bottom:16px; background-color:#f9fafb;">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="fw-bold" style="font-size:12px;color:#374151;">Pecahan Transaksi</span>
                                    <button type="button" class="btn btn-sm btn-outline-primary" style="font-size:11px;border-radius:6px;padding:2px 8px;" onclick="addSplitRow()">
                                        <i class="bi bi-plus-lg"></i> Tambah Pos
                                    </button>
                                </div>
                                <div id="splitRowsContainer">
                                    <!-- Dynamic split rows go here -->
                                </div>
                                <div class="mt-2 text-end" style="font-size:11px;color:#6b7280;">
                                    Total pecahan: <span id="splitTotalSum" class="fw-bold text-dark">Rp 0</span>
                                    <span id="splitValidationMsg" class="d-block text-danger fw-semibold mt-1" style="display:none;">Jumlah pecahan harus sama dengan Jumlah total!</span>
                                </div>
                            </div>

                            <div class="mb-3" id="categoryGroup">
                                <label class="form-label" style="font-size:13px;font-weight:500;">
                                    Kategori <span style="color:#9ca3af;font-size:12px;">(opsional)</span>
                                </label>
                                <select name="category_id" id="txCategory" class="form-select" style="font-size:13px;border-radius:9px;" onchange="updateTypeFromCategory()">
                                    <option value="">— Tanpa kategori —</option>
                                    @if($categories->where('type','expense')->isNotEmpty())
                                        <optgroup label="Pengeluaran" id="optExpense">
                                            @foreach($categories->where('type','expense') as $cat)
                                                <option value="{{ $cat->id }}" data-type="expense">{{ $cat->name }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                    @if($categories->where('type','income')->isNotEmpty())
                                        <optgroup label="Pemasukan" id="optIncome">
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
                                   placeholder="cth. Beli makan siang, Gaji, Transfer BRI…" autocomplete="off">
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

                        {{-- Unggah Resi --}}
                        <div class="mb-3">
                            <label class="form-label" style="font-size:13px;font-weight:500;">Unggah Struk Fisik <span style="color:#9ca3af;font-size:12px;">(opsional)</span></label>
                            <input type="file" name="receipt_image" id="txReceiptImage" class="form-control" style="font-size:13px;border-radius:9px;" accept="image/*" onchange="previewReceiptImage(this)">
                            <div id="txReceiptPreviewContainer" style="display:none;margin-top:8px;">
                                <img id="txReceiptPreview" src="" alt="Resi Preview" style="max-height:100px;border-radius:8px;border:1px solid #e5e7eb;">
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

    {{-- Lightbox Struk Modal --}}
    <div class="modal fade" id="receiptLightboxModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 600px;">
            <div class="modal-content" style="border-radius:16px; overflow:hidden;">
                <div class="modal-header" style="border-bottom:none; padding:12px 16px; position:absolute; right:0; top:0; z-index:10;">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="background-color:#fff; padding:8px; border-radius:50%; box-shadow:0 2px 8px rgba(0,0,0,0.1);"></button>
                </div>
                <div class="modal-body p-0 text-center bg-black">
                    <img id="lightboxImage" src="" alt="Receipt Lightbox" style="max-width:100%; max-height:85vh; object-fit:contain;">
                </div>
            </div>
        </div>
    </div>

    {{-- Floating Bulk Action Bar --}}
    <div id="bulkActionBar" class="bulk-action-bar shadow-lg" style="display:none; position:fixed; bottom:24px; left:50%; transform:translateX(-50%); z-index:999; background:white; border:1px solid #e5e7eb; border-radius:12px; padding:12px 24px; align-items:center; gap:16px;">
        <div class="text-secondary" style="font-size:13px; font-weight:500;">
            Terpilih: <span id="bulkSelectedCount" class="fw-bold text-dark">0</span> transaksi
        </div>
        <div style="height:20px; width:1px; background:#e5e7eb;"></div>
        
        {{-- Ubah Kategori Massal --}}
        <form id="bulkUpdateCategoryForm" method="POST" action="{{ route('transaction.bulk-update-category') }}" style="display:flex; align-items:center; gap:8px; margin:0;">
            @csrf
            <select name="category_id" class="form-select form-select-sm" style="font-size:12px; border-radius:8px; width:160px;" required>
                <option value="">Ubah kategori ke...</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }} ({{ $cat->type === 'income' ? 'Masuk' : 'Keluar' }})</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-sm btn-outline-primary" style="font-size:12px; border-radius:8px;">Terapkan</button>
        </form>

        <div style="height:20px; width:1px; background:#e5e7eb;"></div>

        {{-- Hapus Massal --}}
        <form id="bulkDeleteForm" method="POST" action="{{ route('transaction.bulk-destroy') }}" style="margin:0;">
            @csrf
            <button type="submit" class="btn btn-sm btn-danger" style="font-size:12px; border-radius:8px;" onclick="return confirm('Hapus transaksi terpilih? Saldo dompet akan dikembalikan.')">
                <i class="bi bi-trash me-1"></i> Hapus Terpilih
            </button>
        </form>
    </div>

@endsection

@push('css')
<style>
    .bulk-action-bar {
        border-radius: 16px !important;
        border: 1px solid #e5e7eb !important;
        background: rgba(255, 255, 255, 0.95) !important;
        backdrop-filter: blur(10px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
        transition: all 0.3s ease;
    }
    .split-row {
        background: white;
        border: 1px solid #f3f4f6;
        border-radius: 8px;
        padding: 8px 4px;
    }
    .pagination-link {
        cursor: pointer;
    }
    .type-btn.transfer-active {
        background-color: #2563eb;
        color: white;
    }
    .tx-count-badge {
        background: #f3f4f6;
        color: #374151;
        font-size: 11px;
        font-weight: 600;
        padding: 2px 8px;
        border-radius: 9999px;
        margin-left: 6px;
        vertical-align: middle;
    }
</style>
@endpush

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
        
        // Hide split toggle for transfers and income
        const splitToggle = document.getElementById('splitToggleSection');
        if (type === 'expense') {
            splitToggle.style.display = 'block';
        } else {
            splitToggle.style.display = 'none';
            document.getElementById('txIsSplit').checked = false;
            toggleSplitMode();
        }
        
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
    window.editTx = function (id, desc, detail, amount, date, walletId, categoryId, type, toWalletId, isSplit) {
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

        // Handle Split loading
        if (isSplit) {
            document.getElementById('txIsSplit').checked = true;
            toggleSplitMode();
            
            const container = document.getElementById('splitRowsContainer');
            container.innerHTML = '<div class="text-center py-2" style="font-size:12px;color:#6b7280;"><i class="bi bi-hourglass-split"></i> Memuat pecahan...</div>';
            
            fetch(`/transaction/${id}/splits`)
                .then(res => res.json())
                .then(splits => {
                    container.innerHTML = '';
                    splits.forEach(split => {
                        addSplitRow(split.category_id, Math.abs(parseFloat(split.amount)), split.description);
                    });
                    calculateSplitSum();
                })
                .catch(err => {
                    console.error('Error fetching splits:', err);
                    container.innerHTML = '<div class="text-danger text-center py-2" style="font-size:12px;">Gagal memuat pecahan.</div>';
                });
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
        
        // Reset split sections
        document.getElementById('txIsSplit').checked = false;
        document.getElementById('splitSection').style.display = 'none';
        document.getElementById('splitRowsContainer').innerHTML = '';
        document.getElementById('txCategory').disabled = false;
        document.getElementById('txSubmitBtn').disabled = false;

        var adminFeeEl = document.getElementById('txAdminFee');
        if (adminFeeEl) adminFeeEl.value = '';
        document.getElementById('detailSection').style.display = 'none';
        document.getElementById('detailChevron').className = 'bi bi-chevron-down';
        
        // Reset receipt preview
        document.getElementById('txReceiptPreviewContainer').style.display = 'none';
        document.getElementById('txReceiptPreview').src = '';
        document.getElementById('txReceiptImage').value = '';

        selectTxType('expense');
    }

    document.getElementById('txModal').addEventListener('hidden.bs.modal', resetTxModal);

    /* ── Split Transaction Logic ─────────────────────────────────── */
    let splitRowIndex = 0;

    window.toggleSplitMode = function () {
        const isSplit = document.getElementById('txIsSplit').checked;
        const splitSection = document.getElementById('splitSection');
        const normalCategory = document.getElementById('txCategory');
        
        splitSection.style.display = isSplit ? 'block' : 'none';
        
        if (isSplit) {
            normalCategory.removeAttribute('required');
            normalCategory.disabled = true;
            
            const container = document.getElementById('splitRowsContainer');
            if (container.children.length === 0) {
                addSplitRow();
            }
        } else {
            normalCategory.disabled = false;
        }
        calculateSplitSum();
    };

    window.addSplitRow = function (categoryId = '', amount = '', description = '') {
        const container = document.getElementById('splitRowsContainer');
        const index = splitRowIndex++;
        
        let categoryOptions = '<option value="">Pilih kategori...</option>';
        const categoriesList = @json($categories->where('type', 'expense')->values());
        categoriesList.forEach(cat => {
            const selected = cat.id == categoryId ? 'selected' : '';
            categoryOptions += `<option value="${cat.id}" ${selected}>${cat.name}</option>`;
        });

        const rowHtml = `
            <div class="row g-2 mb-2 align-items-center split-row" id="splitRow_${index}">
                <div class="col-5">
                    <select name="splits[${index}][category_id]" class="form-select split-category-select" style="font-size:12px;border-radius:8px;" required>
                        ${categoryOptions}
                    </select>
                </div>
                <div class="col-4">
                    <input type="number" name="splits[${index}][amount]" value="${amount}" class="form-control split-amount-input" style="font-size:12px;border-radius:8px;" placeholder="Jumlah" min="1" required oninput="calculateSplitSum()">
                </div>
                <div class="col-3 d-flex gap-1 align-items-center">
                    <input type="text" name="splits[${index}][description]" value="${description}" class="form-control" style="font-size:12px;border-radius:8px;" placeholder="Ket (opsional)">
                    <button type="button" class="btn btn-sm btn-outline-danger" style="padding: 4px 8px;border-radius:8px;" onclick="removeSplitRow(${index})">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', rowHtml);
        calculateSplitSum();
    };

    window.removeSplitRow = function (index) {
        const row = document.getElementById(`splitRow_${index}`);
        if (row) row.remove();
        calculateSplitSum();
    };

    window.calculateSplitSum = function () {
        const amountInputs = document.querySelectorAll('.split-amount-input');
        let sum = 0;
        amountInputs.forEach(input => {
            sum += parseFloat(input.value) || 0;
        });

        const totalInput = document.getElementById('txAmount');
        const mainAmount = parseFloat(totalInput.value) || 0;
        
        const sumSpan = document.getElementById('splitTotalSum');
        if (sumSpan) {
            sumSpan.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(sum);
        }

        const validationMsg = document.getElementById('splitValidationMsg');
        const isSplit = document.getElementById('txIsSplit').checked;
        
        if (isSplit && sum !== mainAmount) {
            if (validationMsg) validationMsg.style.display = 'block';
            document.getElementById('txSubmitBtn').disabled = true;
        } else {
            if (validationMsg) validationMsg.style.display = 'none';
            document.getElementById('txSubmitBtn').disabled = false;
        }
    };

    document.getElementById('txAmount').addEventListener('input', calculateSplitSum);

    /* ── Receipt Image Preview ───────────────────────────────────── */
    window.previewReceiptImage = function (input) {
        const container = document.getElementById('txReceiptPreviewContainer');
        const img = document.getElementById('txReceiptPreview');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function (e) {
                img.src = e.target.result;
                container.style.display = 'block';
            };
            reader.readAsDataURL(input.files[0]);
        } else {
            img.src = '';
            container.style.display = 'none';
        }
    };

    /* ── Lightbox Receipt Preview ────────────────────────────────── */
    window.showReceiptLightbox = function (imageUrl) {
        const img = document.getElementById('lightboxImage');
        if (img) {
            img.src = imageUrl;
            const modal = new bootstrap.Modal(document.getElementById('receiptLightboxModal'));
            modal.show();
        }
    };

    /* ── Keyboard Shortcuts (Hotkeys) ────────────────────────────── */
    document.addEventListener('keydown', function (e) {
        const active = document.activeElement;
        if (active && (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA' || active.tagName === 'SELECT')) {
            if (e.key === 'Escape') {
                const modalEl = document.getElementById('txModal');
                if (modalEl && modalEl.classList.contains('show')) {
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                }
            }
            return;
        }

        if (e.key === 'n' || e.key === 'N') {
            e.preventDefault();
            const createBtn = document.querySelector('[data-bs-target="#txModal"]');
            if (createBtn) createBtn.click();
        } else if (e.key === '/') {
            e.preventDefault();
            const filterDateInput = document.querySelector('input[name="date_from"]');
            if (filterDateInput) filterDateInput.focus();
        }
    });

    /* ── Smart Category Suggestion ───────────────────────────────── */
    const descInput = document.getElementById('txDesc');
    if (descInput) {
        let debounceTimer;
        descInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            const query = this.value.trim();
            
            const currentType = document.getElementById('txType').value;
            if (currentType === 'transfer') return;
            if (document.getElementById('txIsSplit').checked) return; // skip for split

            if (query.length < 2) return;

            debounceTimer = setTimeout(() => {
                fetch(`/transaction/suggest-category?query=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.category_id) {
                            const categorySelect = document.getElementById('txCategory');
                            if (categorySelect) {
                                categorySelect.value = data.category_id;
                                
                                categorySelect.style.transition = 'background-color 0.3s ease';
                                categorySelect.style.backgroundColor = '#dcfce7'; // light green
                                setTimeout(() => {
                                    categorySelect.style.backgroundColor = '';
                                }, 1000);
                            }
                        }
                    })
                    .catch(err => console.error('Error suggesting category:', err));
            }, 300);
        });
    }

    /* ── AJAX Filter, Search & Pagination ────────────────────────── */
    const filterForm = document.querySelector('.filter-bar');
    if (filterForm) {
        filterForm.addEventListener('submit', function (e) {
            e.preventDefault();
            fetchTransactions();
        });
        filterForm.querySelectorAll('select').forEach(select => {
            select.addEventListener('change', fetchTransactions);
        });
        filterForm.querySelectorAll('input[type="date"]').forEach(input => {
            input.addEventListener('change', fetchTransactions);
        });

        const resetBtn = filterForm.querySelector('a.btn-dp-default');
        if (resetBtn) {
            resetBtn.addEventListener('click', function (e) {
                e.preventDefault();
                filterForm.reset();
                fetchTransactions(true);
            });
        }
    }

    function fetchTransactions(reset = false) {
        let url = new URL('{{ route('transaction.index') }}');
        if (!reset && filterForm) {
            const formData = new FormData(filterForm);
            for (let [key, val] of formData.entries()) {
                if (val) url.searchParams.set(key, val);
            }
        }
        
        const tableWrap = document.getElementById('txTableWrap');
        if (tableWrap) tableWrap.style.opacity = '0.5';

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.text())
        .then(html => {
            document.getElementById('transactionTableContainer').innerHTML = html;
            bindTableEvents();
            updateCountBadge();
        })
        .catch(err => console.error('Error fetching transactions:', err))
        .finally(() => {
            const newTableWrap = document.getElementById('txTableWrap');
            if (newTableWrap) newTableWrap.style.opacity = '1';
        });
    }

    function fetchTransactionsWithPage(url) {
        if (filterForm) {
            const formData = new FormData(filterForm);
            for (let [key, val] of formData.entries()) {
                if (val) url.searchParams.set(key, val);
            }
        }
        
        const tableWrap = document.getElementById('txTableWrap');
        if (tableWrap) tableWrap.style.opacity = '0.5';

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.text())
        .then(html => {
            document.getElementById('transactionTableContainer').innerHTML = html;
            bindTableEvents();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        })
        .catch(err => console.error('Error paginating transactions:', err));
    }

    function updateCountBadge() {
        const tableWrap = document.getElementById('txTableWrap');
        const badge = document.querySelector('.tx-count-badge');
        if (tableWrap && badge) {
            badge.textContent = tableWrap.getAttribute('data-total') || '0';
        }
    }

    /* ── Ekspor Data ─────────────────────────────────────────────── */
    window.triggerExport = function () {
        let url = new URL('{{ route('transaction.export') }}');
        if (filterForm) {
            const formData = new FormData(filterForm);
            for (let [key, val] of formData.entries()) {
                if (val) url.searchParams.set(key, val);
            }
        }
        window.location.href = url.toString();
    };

    /* ── Bulk Actions Logic ──────────────────────────────────────── */
    window.bindTableEvents = function () {
        // Pagination link click events
        document.querySelectorAll('.pagination-link').forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                const url = new URL(this.href);
                fetchTransactionsWithPage(url);
            });
        });

        // Bulk Selection checkbox events
        const masterCheckbox = document.getElementById('bulkSelectAll');
        const itemCheckboxes = document.querySelectorAll('.bulk-select-item');
        
        if (masterCheckbox) {
            masterCheckbox.addEventListener('change', function () {
                itemCheckboxes.forEach(cb => cb.checked = this.checked);
                updateBulkActionBar();
            });
        }

        itemCheckboxes.forEach(cb => {
            cb.addEventListener('change', function () {
                if (masterCheckbox) {
                    masterCheckbox.checked = Array.from(itemCheckboxes).every(c => c.checked);
                }
                updateBulkActionBar();
            });
        });
    };

    function updateBulkActionBar() {
        const itemCheckboxes = document.querySelectorAll('.bulk-select-item');
        const checkedBoxes = Array.from(itemCheckboxes).filter(cb => cb.checked);
        const checkedIds = checkedBoxes.map(cb => cb.value);
        
        const bar = document.getElementById('bulkActionBar');
        const countSpan = document.getElementById('bulkSelectedCount');
        
        if (checkedIds.length > 0) {
            bar.style.display = 'flex';
            countSpan.textContent = checkedIds.length;
            
            // Inject selected IDs into forms
            document.querySelectorAll('#bulkActionBar form').forEach(form => {
                form.querySelectorAll('input[name="transaction_ids[]"]').forEach(i => i.remove());
                checkedIds.forEach(id => {
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'transaction_ids[]';
                    hiddenInput.value = id;
                    form.appendChild(hiddenInput);
                });
            });
        } else {
            bar.style.display = 'none';
            countSpan.textContent = '0';
        }
    }

    // Initialize events on page load
    bindTableEvents();
    selectTxType('expense');
})();
</script>
@endpush
