@extends('layouts.main')

@section('title', 'Import Transaksi')
@section('subtitle', 'Tempel catatan pengeluaran lalu simpan ke dompet sekaligus')

@section('content')

{{-- ─── Alerts ──────────────────────────────────────────────── --}}
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
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- ─── Step indicator ──────────────────────────────────────── --}}
<div class="import-steps mb-4">
    <div class="import-step active" id="stepDot1">
        <div class="step-circle">1</div>
        <span class="step-label">Tempel Teks</span>
    </div>
    <div class="step-connector"></div>
    <div class="import-step" id="stepDot2">
        <div class="step-circle">2</div>
        <span class="step-label">Preview</span>
    </div>
    <div class="step-connector"></div>
    <div class="import-step" id="stepDot3">
        <div class="step-circle">3</div>
        <span class="step-label">Konfirmasi</span>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════
     STEP 1 – Paste raw text
═══════════════════════════════════════════════════════════ --}}
<div id="step1" class="import-card">
    <div class="import-card-header">
        <div class="import-card-icon">
            <i class="bi bi-clipboard2-pulse"></i>
        </div>
        <div>
            <h2 class="import-card-title">Tempel Catatan Pengeluaran</h2>
            <p class="import-card-sub">Format: tanggal sebagai header, lalu deskripsi + nominal per baris.</p>
        </div>
    </div>

    <div class="import-format-hint">
        <div class="hint-label"><i class="bi bi-info-circle-fill me-1"></i> Contoh format yang didukung:</div>
        <pre class="hint-code">1 Mei 2025
Kopi Susu 15.000
Makan Siang 35.000
Bensin 50.000

2 Mei 2025
Rokok 25.000
Parkir 3.000</pre>
    </div>

    <div class="mb-3">
        <label for="rawText" class="form-label fw-semibold">Catatan Pengeluaran</label>
        <textarea id="rawText" rows="12" class="form-control import-textarea"
            placeholder="Tempel atau ketik catatan di sini…"></textarea>
        <div class="form-text text-muted mt-1">
            <i class="bi bi-shield-check me-1"></i>
            Teks hanya diproses di server Anda — tidak dikirim ke pihak luar.
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <button id="btnParse" class="btn-primary-dp" onclick="doPreview()">
            <span id="btnParseIdle"><i class="bi bi-magic me-1"></i> Parse & Preview</span>
            <span id="btnParseSpinner" class="d-none">
                <span class="spinner-border spinner-border-sm me-1" role="status"></span> Memproses…
            </span>
        </button>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════
     STEP 2 – Preview table
═══════════════════════════════════════════════════════════ --}}
<div id="step2" class="import-card d-none">
    <div class="import-card-header">
        <div class="import-card-icon" style="background:var(--color-income-bg);color:var(--color-income);">
            <i class="bi bi-table"></i>
        </div>
        <div>
            <h2 class="import-card-title">Preview Hasil Parsing</h2>
            <p class="import-card-sub">Cek data sebelum disimpan. Kamu bisa hapus baris yang tidak perlu.</p>
        </div>
    </div>

    <div id="parseError" class="alert alert-danger d-none mb-3"></div>

    <div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
        <span id="parseSummary" class="badge-count"></span>
        <div class="ms-auto d-flex gap-2 flex-wrap">
            <button class="btn-outline-dp btn-sm-dp" onclick="backToStep1()">
                <i class="bi bi-arrow-left me-1"></i> Ubah Teks
            </button>
            <button class="btn-primary-dp btn-sm-dp" onclick="goToStep3()" id="btnConfirm">
                <i class="bi bi-check2-all me-1"></i> Konfirmasi & Lanjut
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table dompetra-table" id="previewTable">
            <thead>
                <tr>
                    <th style="width:36px;">
                        <input type="checkbox" id="checkAll" class="form-check-input" checked onchange="toggleAll(this)">
                    </th>
                    <th>Tanggal</th>
                    <th>Deskripsi</th>
                    <th class="text-end">Jumlah</th>
                    <th style="width:40px;"></th>
                </tr>
            </thead>
            <tbody id="previewBody"></tbody>
        </table>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════
     STEP 3 – Confirm & choose wallet / category / type
═══════════════════════════════════════════════════════════ --}}
<div id="step3" class="import-card d-none">
    <div class="import-card-header">
        <div class="import-card-icon" style="background:#fffbeb;color:#d97706;">
            <i class="bi bi-send-check"></i>
        </div>
        <div>
            <h2 class="import-card-title">Konfirmasi Import</h2>
            <p class="import-card-sub">Pilih dompet tujuan lalu simpan semua transaksi terpilih.</p>
        </div>
    </div>

    <form id="importForm" method="POST" action="{{ route('import.store') }}">
        @csrf
        <input type="hidden" name="items" id="itemsPayload">

        <div class="row g-3 mb-4">
            {{-- Wallet --}}
            <div class="col-sm-6 col-md-4">
                <label class="form-label fw-semibold">Dompet <span class="text-danger">*</span></label>
                <select name="wallet_id" class="form-select" required id="importWallet">
                    <option value="">Pilih dompet…</option>
                    @foreach($wallets as $w)
                        <option value="{{ $w->id }}">{{ $w->name }} ({{ $w->formatted_balance }})</option>
                    @endforeach
                </select>
            </div>

            {{-- Category (optional) --}}
            <div class="col-sm-6 col-md-4">
                <label class="form-label fw-semibold">Kategori <span style="color:var(--color-muted);font-size:12px;">(opsional)</span></label>
                <select name="category_id" class="form-select" id="importCategory">
                    <option value="">— Tanpa kategori —</option>
                    @if($categories->where('type','expense')->isNotEmpty())
                        <optgroup label="Pengeluaran">
                            @foreach($categories->where('type','expense') as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </optgroup>
                    @endif
                    @if($categories->where('type','income')->isNotEmpty())
                        <optgroup label="Pemasukan">
                            @foreach($categories->where('type','income') as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </optgroup>
                    @endif
                </select>
            </div>

            {{-- Type --}}
            <div class="col-sm-6 col-md-4">
                <label class="form-label fw-semibold">Jenis Transaksi <span class="text-danger">*</span></label>
                <div class="type-toggle mt-1">
                    <div class="type-btn expense-active" id="importTypeExpense" onclick="selectImportType('expense')">
                        <i class="bi bi-arrow-up-right me-1"></i> Pengeluaran
                    </div>
                    <div class="type-btn" id="importTypeIncome" onclick="selectImportType('income')">
                        <i class="bi bi-arrow-down-left me-1"></i> Pemasukan
                    </div>
                </div>
                <input type="hidden" name="type" id="importType" value="expense">
            </div>
        </div>

        {{-- Summary box --}}
        <div class="confirm-summary mb-4" id="confirmSummary"></div>

        <div class="d-flex gap-2 justify-content-end flex-wrap">
            <button type="button" class="btn-outline-dp" onclick="backToStep2()">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </button>
            <button type="submit" class="btn-primary-dp" id="btnSave">
                <i class="bi bi-cloud-upload me-1"></i> <span id="btnSaveLabel">Simpan Transaksi</span>
            </button>
        </div>
    </form>
</div>

@endsection

@push('css')
<style>
/* ── Step indicators ───────────────────────────────────────── */
.import-steps {
    display: flex;
    align-items: center;
    gap: 0;
}
.import-step {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-direction: column;
    min-width: 72px;
    text-align: center;
}
.step-circle {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 14px;
    background: var(--color-border, #e2e8f0);
    color: var(--color-muted, #94a3b8);
    transition: background .25s, color .25s, box-shadow .25s;
}
.import-step.active .step-circle {
    background: var(--color-primary, #6366f1);
    color: #fff;
    box-shadow: 0 0 0 4px rgba(99,102,241,.18);
}
.import-step.done .step-circle {
    background: #22c55e;
    color: #fff;
}
.step-label {
    font-size: 11px;
    color: var(--color-muted);
    font-weight: 500;
}
.import-step.active .step-label { color: var(--color-primary, #6366f1); font-weight: 600; }
.step-connector {
    flex: 1;
    height: 2px;
    background: var(--color-border, #e2e8f0);
    margin-bottom: 18px;
    transition: background .25s;
}
.step-connector.done { background: #22c55e; }

/* ── Import card ───────────────────────────────────────────── */
.import-card {
    background: var(--color-card, #fff);
    border: 1px solid var(--color-border, #e2e8f0);
    border-radius: 14px;
    padding: 28px;
    margin-bottom: 24px;
}
.import-card-header {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 20px;
}
.import-card-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    background: var(--color-primary-light, #eef2ff);
    color: var(--color-primary, #6366f1);
    flex-shrink: 0;
}
.import-card-title {
    font-size: 17px;
    font-weight: 700;
    margin: 0;
    color: var(--color-text, #0f172a);
}
.import-card-sub {
    font-size: 13px;
    color: var(--color-muted, #64748b);
    margin: 0;
}

/* ── Format hint ───────────────────────────────────────────── */
.import-format-hint {
    background: var(--color-sidebar-bg, #f8fafc);
    border: 1px solid var(--color-border, #e2e8f0);
    border-radius: 10px;
    padding: 14px 18px;
    margin-bottom: 20px;
}
.hint-label {
    font-size: 12px;
    font-weight: 600;
    color: var(--color-primary, #6366f1);
    margin-bottom: 8px;
}
.hint-code {
    font-size: 12.5px;
    line-height: 1.7;
    margin: 0;
    color: var(--color-text, #334155);
    font-family: 'Fira Mono', 'Courier New', monospace;
}

/* ── Textarea ──────────────────────────────────────────────── */
.import-textarea {
    font-family: 'Fira Mono', 'Courier New', monospace;
    font-size: 13px;
    line-height: 1.7;
    resize: vertical;
    min-height: 220px;
}

/* ── Badge count ───────────────────────────────────────────── */
.badge-count {
    background: var(--color-primary-light, #eef2ff);
    color: var(--color-primary, #6366f1);
    border-radius: 999px;
    padding: 4px 14px;
    font-size: 13px;
    font-weight: 600;
}

/* ── Small button variant ──────────────────────────────────── */
.btn-sm-dp {
    padding: 6px 14px !important;
    font-size: 13px !important;
}

/* ── Confirm summary box ───────────────────────────────────── */
.confirm-summary {
    background: linear-gradient(135deg, #eef2ff 0%, #f5f3ff 100%);
    border: 1px solid #c7d2fe;
    border-radius: 12px;
    padding: 18px 22px;
    font-size: 14px;
    color: var(--color-text, #1e293b);
}
.confirm-summary strong { color: var(--color-primary, #6366f1); }

/* ── Row delete btn ────────────────────────────────────────── */
.btn-row-del {
    background: none;
    border: none;
    color: var(--color-muted);
    cursor: pointer;
    padding: 2px 6px;
    border-radius: 6px;
    transition: color .15s, background .15s;
}
.btn-row-del:hover { color: #ef4444; background: #fff1f2; }

/* ── Dark mode patches ─────────────────────────────────────── */
[data-theme="dark"] .import-card  { background: var(--color-card); }
[data-theme="dark"] .hint-code    { color: #cbd5e1; }
[data-theme="dark"] .import-format-hint { background: #1e293b; }
[data-theme="dark"] .confirm-summary { background: #1e1b4b; border-color: #4338ca; color: #e2e8f0; }
</style>
@endpush

@push('js')
<script>
// ── State ──────────────────────────────────────────────────────────
let parsedItems = [];   // full parsed array from server
let selectedIdxs = [];  // indexes of checked rows

// ── Step navigation ────────────────────────────────────────────────
function setStep(n) {
    [1, 2, 3].forEach(i => {
        const stepEl = document.getElementById('step' + i);
        if (stepEl) stepEl.classList.toggle('d-none', i !== n);
        
        const dot = document.getElementById('stepDot' + i);
        if (dot) {
            dot.classList.remove('active', 'done');
            if (i < n) dot.classList.add('done');
            if (i === n) dot.classList.add('active');
        }
    });
    // connectors
    document.querySelectorAll('.step-connector').forEach((el, idx) => {
        if (el) el.classList.toggle('done', idx < n - 1);
    });
}

function backToStep1() { setStep(1); }
function backToStep2() { setStep(2); }

// ── STEP 1 → 2: parse ─────────────────────────────────────────────
async function doPreview() {
    const raw = document.getElementById('rawText').value.trim();
    if (!raw) { alert('Teks tidak boleh kosong.'); return; }

    // Loading state
    document.getElementById('btnParseIdle')?.classList.add('d-none');
    document.getElementById('btnParseSpinner')?.classList.remove('d-none');
    const btnParse = document.getElementById('btnParse');
    if (btnParse) btnParse.disabled = true;

    try {
        const resp = await fetch('{{ route("import.preview") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ raw_text: raw }),
        });

        const data = await resp.json();

        if (!resp.ok) {
            showParseError(data.message || 'Terjadi kesalahan pada server.');
            setStep(2);
            return;
        }

        if (!data.items || data.items.length === 0) {
            showParseError('Tidak ada transaksi yang berhasil diparse. Periksa format teks kamu.');
            setStep(2);
            return;
        }

        parsedItems = data.items;
        renderPreviewTable(parsedItems);
        setStep(2);

    } catch (e) {
        console.error("Kesalahan JS:", e);
        alert('Terjadi kesalahan pada aplikasi: ' + e.message);
    } finally {
        document.getElementById('btnParseIdle')?.classList.remove('d-none');
        document.getElementById('btnParseSpinner')?.classList.add('d-none');
        const btnParse = document.getElementById('btnParse');
        if (btnParse) btnParse.disabled = false;
    }
}

function showParseError(msg) {
    const el = document.getElementById('parseError');
    if (el) {
        el.textContent = msg;
        el.classList.remove('d-none');
    }
}

// ── Render preview table rows ──────────────────────────────────────
function renderPreviewTable(items) {
    const errEl = document.getElementById('parseError');
    if (errEl) errEl.classList.add('d-none');

    const tbody = document.getElementById('previewBody');
    tbody.innerHTML = '';
    selectedIdxs = items.map((_, i) => i); // all selected by default

    items.forEach((item, idx) => {
        const tr = document.createElement('tr');
        tr.id = 'previewRow' + idx;
        tr.innerHTML = `
            <td>
                <input type="checkbox" class="form-check-input row-check"
                    data-idx="${idx}" checked onchange="onRowCheck(this)">
            </td>
            <td><span class="tx-date">${formatDate(item.date)}</span></td>
            <td><span class="tx-desc">${escHtml(item.description)}</span></td>
            <td class="amount-cell amount-expense text-end">
                ${formatRupiah(item.amount)}
            </td>
            <td>
                <button type="button" class="btn-row-del" title="Hapus baris"
                    onclick="deleteRow(${idx})">
                    <i class="bi bi-x-lg"></i>
                </button>
            </td>`;
        tbody.appendChild(tr);
    });

    document.getElementById('checkAll').checked = true;
    updateSummary();
}

function deleteRow(idx) {
    document.getElementById('previewRow' + idx).remove();
    selectedIdxs = selectedIdxs.filter(i => i !== idx);
    parsedItems[idx] = null; // mark deleted
    updateSummary();
}

function onRowCheck(cb) {
    const idx = parseInt(cb.dataset.idx);
    if (cb.checked) {
        if (!selectedIdxs.includes(idx)) selectedIdxs.push(idx);
    } else {
        selectedIdxs = selectedIdxs.filter(i => i !== idx);
    }
    updateSummary();
}

function toggleAll(masterCb) {
    document.querySelectorAll('.row-check').forEach(cb => {
        cb.checked = masterCb.checked;
        onRowCheck(cb);
    });
}

function updateSummary() {
    const count = selectedIdxs.length;
    const total = selectedIdxs.reduce((sum, i) => {
        return parsedItems[i] ? sum + parsedItems[i].amount : sum;
    }, 0);
    document.getElementById('parseSummary').textContent =
        `${count} transaksi dipilih · Total ${formatRupiah(total)}`;
}

// ── STEP 2 → 3 ────────────────────────────────────────────────────
function goToStep3() {
    const chosen = selectedIdxs
        .filter(i => parsedItems[i] !== null)
        .map(i => parsedItems[i]);

    if (chosen.length === 0) {
        alert('Pilih minimal satu transaksi.');
        return;
    }

    document.getElementById('itemsPayload').value = JSON.stringify(chosen);

    const total = chosen.reduce((s, t) => s + t.amount, 0);
    document.getElementById('confirmSummary').innerHTML =
        `Siap mengimpor <strong>${chosen.length} transaksi</strong> dengan total
         <strong>${formatRupiah(total)}</strong> ke dompet yang kamu pilih.`;

    document.getElementById('btnSaveLabel').textContent =
        `Simpan ${chosen.length} Transaksi`;

    setStep(3);
}

// ── Type toggle (step 3) ───────────────────────────────────────────
function selectImportType(type) {
    document.getElementById('importType').value = type;
    document.getElementById('importTypeExpense').className =
        'type-btn' + (type === 'expense' ? ' expense-active' : '');
    document.getElementById('importTypeIncome').className =
        'type-btn' + (type === 'income' ? ' income-active' : '');
}

// ── Helpers ────────────────────────────────────────────────────────
function escHtml(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
              .replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}

function formatRupiah(n) {
    return 'Rp\u00a0' + Number(n).toLocaleString('id-ID');
}

function formatDate(iso) {
    const [y, m, d] = iso.split('-');
    const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    return `${parseInt(d)} ${months[parseInt(m)-1]} ${y}`;
}

// Prevent double-submit
document.getElementById('importForm').addEventListener('submit', function () {
    const btn = document.getElementById('btnSave');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Menyimpan…';
});

// Init
setStep(1);
</script>
@endpush
