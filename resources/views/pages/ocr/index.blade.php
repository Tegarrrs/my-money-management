@extends('layouts.main')

@section('title', 'Import OCR Struk')
@section('subtitle', 'Foto struk belanja → sistem baca otomatis → konfirmasi → simpan')

@section('content')

{{-- ─── Flash alerts ──────────────────────────────────────────── --}}
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

{{-- ─── Step Indicator ─────────────────────────────────────────── --}}
<div class="ocr-steps mb-4">
    <div class="ocr-step active" id="stepDot1">
        <div class="ocr-step-circle">
            <i class="bi bi-upload"></i>
        </div>
        <span class="ocr-step-label">Upload Gambar</span>
    </div>
    <div class="ocr-step-connector" id="conn1"></div>
    <div class="ocr-step" id="stepDot2">
        <div class="ocr-step-circle">
            <i class="bi bi-table"></i>
        </div>
        <span class="ocr-step-label">Preview & Edit</span>
    </div>
    <div class="ocr-step-connector" id="conn2"></div>
    <div class="ocr-step" id="stepDot3">
        <div class="ocr-step-circle">
            <i class="bi bi-send-check"></i>
        </div>
        <span class="ocr-step-label">Konfirmasi</span>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════
     STEP 1 – Upload receipt image
═══════════════════════════════════════════════════════════ --}}
<div id="ocrStep1" class="ocr-card">
    <div class="ocr-card-header">
        <div class="ocr-card-icon" style="background:var(--color-primary-light,#eef2ff);color:var(--color-primary,#6366f1);">
            <i class="bi bi-file-earmark-image-fill"></i>
        </div>
        <div>
            <h2 class="ocr-card-title">Upload Foto / Scan Struk</h2>
            <p class="ocr-card-sub">Sistem akan membaca teks dari gambar secara otomatis menggunakan OCR.</p>
        </div>
    </div>

    {{-- Drop zone --}}
    <div class="ocr-dropzone" id="ocrDropzone" onclick="document.getElementById('ocrImageInput').click()">
        <div class="dz-idle" id="dzIdle">
            <div class="dz-icon"><i class="bi bi-cloud-arrow-up-fill"></i></div>
            <p class="dz-text">Klik atau seret gambar struk ke sini</p>
            <p class="dz-hint">Format: JPG, PNG, WEBP · Maks 5 MB</p>
        </div>
        <div class="dz-preview d-none" id="dzPreview">
            <img id="dzImg" src="" alt="Preview struk" class="dz-thumb">
            <div class="dz-meta" id="dzMeta"></div>
            <button type="button" class="dz-remove" onclick="clearImage(event)" title="Hapus gambar">
                <i class="bi bi-x-circle-fill"></i>
            </button>
        </div>
        <input type="file" id="ocrImageInput" accept="image/*" class="d-none" onchange="onFileSelect(event)">
    </div>

    {{-- Tips --}}
    <div class="ocr-tips mt-3">
        <div class="tip-title"><i class="bi bi-lightbulb-fill me-1"></i> Tips agar OCR lebih akurat:</div>
        <ul class="tip-list">
            <li>Gunakan foto dengan pencahayaan yang cukup dan tidak blur</li>
            <li>Posisikan kamera tegak lurus di atas struk</li>
            <li>Pastikan seluruh teks struk masuk dalam frame</li>
            <li>Hindari bayangan yang menutupi angka harga</li>
        </ul>
    </div>

    <div class="d-flex justify-content-end mt-4">
        <button id="btnScanOCR" class="btn-primary-dp" onclick="doOCR()" disabled>
            <span id="btnScanIdle"><i class="bi bi-cpu-fill me-1"></i> Scan & Baca Struk</span>
            <span id="btnScanSpinner" class="d-none">
                <span class="spinner-border spinner-border-sm me-1" role="status"></span> Memproses…
            </span>
        </button>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════
     STEP 2 – Preview & edit table
═══════════════════════════════════════════════════════════ --}}
<div id="ocrStep2" class="ocr-card d-none">
    <div class="ocr-card-header">
        <div class="ocr-card-icon" style="background:#f0fdf4;color:#16a34a;">
            <i class="bi bi-table"></i>
        </div>
        <div>
            <h2 class="ocr-card-title">Hasil Pembacaan OCR</h2>
            <p class="ocr-card-sub">Edit nama atau nominal jika ada yang kurang tepat. Hapus baris yang tidak perlu.</p>
        </div>
        <div class="ms-auto">
            <button class="btn-outline-dp btn-sm-dp" onclick="ocrBackToStep1()">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Ulangi
            </button>
        </div>
    </div>

    <div id="ocrParseError" class="alert alert-warning d-none mb-3">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <span id="ocrParseErrorMsg"></span>
    </div>

    {{-- Action bar --}}
    <div class="d-flex align-items-center flex-wrap gap-3 mb-3">
        <span id="ocrSummaryBadge" class="badge-count"></span>
        <button class="btn-outline-dp btn-sm-dp ms-auto" id="btnAddRow" onclick="addEmptyRow()">
            <i class="bi bi-plus-lg me-1"></i> Tambah Baris
        </button>
        <button class="btn-primary-dp btn-sm-dp" id="btnToStep3" onclick="ocrGoToStep3()">
            <i class="bi bi-check2-all me-1"></i> Konfirmasi & Lanjut
        </button>
    </div>

    <div class="table-responsive">
        <table class="table dompetra-table" id="ocrPreviewTable">
            <thead>
                <tr>
                    <th style="width:36px;">
                        <input type="checkbox" class="form-check-input" id="ocrCheckAll" checked onchange="ocrToggleAll(this)">
                    </th>
                    <th>Nama Item</th>
                    <th class="text-end" style="width:180px;">Jumlah (Rp)</th>
                    <th style="width:48px;"></th>
                </tr>
            </thead>
            <tbody id="ocrPreviewBody"></tbody>
        </table>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════
     STEP 3 – Confirm & save
═══════════════════════════════════════════════════════════ --}}
<div id="ocrStep3" class="ocr-card d-none">
    <div class="ocr-card-header">
        <div class="ocr-card-icon" style="background:#fffbeb;color:#d97706;">
            <i class="bi bi-send-check-fill"></i>
        </div>
        <div>
            <h2 class="ocr-card-title">Konfirmasi Simpan</h2>
            <p class="ocr-card-sub">Pilih dompet, kategori, jenis, dan tanggal lalu simpan semua item terpilih.</p>
        </div>
    </div>

    <form id="ocrImportForm" method="POST" action="{{ route('ocr.store') }}">
        @csrf
        <input type="hidden" name="items" id="ocrItemsPayload">

        <div class="row g-3 mb-4">
            {{-- Wallet --}}
            <div class="col-sm-6 col-md-3">
                <label class="form-label fw-semibold">Dompet <span class="text-danger">*</span></label>
                <select name="wallet_id" class="form-select" required id="ocrWallet">
                    <option value="">Pilih dompet…</option>
                    @foreach($wallets as $w)
                        <option value="{{ $w->id }}">{{ $w->name }} ({{ $w->formatted_balance }})</option>
                    @endforeach
                </select>
            </div>

            {{-- Category --}}
            <div class="col-sm-6 col-md-3">
                <label class="form-label fw-semibold">Kategori <span style="color:var(--color-muted);font-size:12px;">(opsional)</span></label>
                <select name="category_id" class="form-select" id="ocrCategory">
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
            <div class="col-sm-6 col-md-3">
                <label class="form-label fw-semibold">Jenis <span class="text-danger">*</span></label>
                <div class="type-toggle mt-1">
                    <div class="type-btn expense-active" id="ocrTypeExpense" onclick="ocrSelectType('expense')">
                        <i class="bi bi-arrow-up-right me-1"></i> Pengeluaran
                    </div>
                    <div class="type-btn" id="ocrTypeIncome" onclick="ocrSelectType('income')">
                        <i class="bi bi-arrow-down-left me-1"></i> Pemasukan
                    </div>
                </div>
                <input type="hidden" name="type" id="ocrType" value="expense">
            </div>

            {{-- Date --}}
            <div class="col-sm-6 col-md-3">
                <label class="form-label fw-semibold">Tanggal Transaksi <span class="text-danger">*</span></label>
                <input type="date" name="date" class="form-select" id="ocrDate" value="{{ now()->format('Y-m-d') }}" required>
            </div>
        </div>

        {{-- Confirm summary box --}}
        <div class="ocr-confirm-box mb-4" id="ocrConfirmBox"></div>

        <div class="d-flex gap-2 justify-content-between flex-wrap">
            <button type="button" class="btn-outline-dp" onclick="ocrBackToStep2()">
                <i class="bi bi-arrow-left me-1"></i> Kembali Edit
            </button>
            <button type="submit" class="btn-primary-dp" id="ocrBtnSave">
                <i class="bi bi-cloud-upload me-1"></i>
                <span id="ocrBtnSaveLabel">Simpan Transaksi</span>
            </button>
        </div>
    </form>
</div>

@endsection

@push('css')
<style>
/* ── Step indicator ─────────────────────────────────────────── */
.ocr-steps {
    display: flex;
    align-items: center;
}
.ocr-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    min-width: 80px;
    text-align: center;
}
.ocr-step-circle {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    background: var(--color-border, #e2e8f0);
    color: var(--color-muted, #94a3b8);
    transition: background .3s, color .3s, box-shadow .3s, transform .3s;
}
.ocr-step.active  .ocr-step-circle {
    background: var(--color-primary, #6366f1);
    color: #fff;
    box-shadow: 0 0 0 5px rgba(99,102,241,.18);
    transform: scale(1.08);
}
.ocr-step.done .ocr-step-circle {
    background: #22c55e;
    color: #fff;
}
.ocr-step-label {
    font-size: 11px;
    font-weight: 500;
    color: var(--color-muted);
    white-space: nowrap;
}
.ocr-step.active .ocr-step-label { color: var(--color-primary, #6366f1); font-weight: 700; }
.ocr-step-connector {
    flex: 1;
    height: 2px;
    background: var(--color-border, #e2e8f0);
    margin-bottom: 24px;
    transition: background .3s;
}
.ocr-step-connector.done { background: #22c55e; }

/* ── OCR card ──────────────────────────────────────────────── */
.ocr-card {
    background: var(--color-card, #fff);
    border: 1px solid var(--color-border, #e2e8f0);
    border-radius: 16px;
    padding: 28px;
    margin-bottom: 24px;
    box-shadow: 0 1px 4px rgba(0,0,0,.04);
    transition: box-shadow .2s;
}
.ocr-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.07); }
.ocr-card-header {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    margin-bottom: 24px;
    flex-wrap: wrap;
}
.ocr-card-icon {
    width: 52px; height: 52px;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 24px;
    flex-shrink: 0;
}
.ocr-card-title {
    font-size: 17px; font-weight: 700; margin: 0;
    color: var(--color-text, #0f172a);
}
.ocr-card-sub {
    font-size: 13px; color: var(--color-muted, #64748b); margin: 4px 0 0;
}

/* ── Drop zone ─────────────────────────────────────────────── */
.ocr-dropzone {
    border: 2px dashed var(--color-primary, #6366f1);
    border-radius: 14px;
    background: var(--color-primary-light, #eef2ff);
    min-height: 180px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    position: relative;
    transition: background .2s, border-color .2s;
    overflow: hidden;
}
.ocr-dropzone:hover { background: #e0e7ff; }
.ocr-dropzone.drag-over {
    background: #c7d2fe;
    border-color: #4f46e5;
    transform: scale(1.01);
}
.dz-idle { text-align: center; padding: 24px; }
.dz-icon { font-size: 48px; color: var(--color-primary, #6366f1); margin-bottom: 10px; line-height: 1; }
.dz-text { font-size: 15px; font-weight: 600; color: var(--color-text, #1e293b); margin: 0; }
.dz-hint { font-size: 12px; color: var(--color-muted); margin: 4px 0 0; }
.dz-preview { width: 100%; padding: 12px; display: flex; align-items: center; gap: 16px; }
.dz-thumb {
    max-height: 140px; max-width: 180px;
    border-radius: 10px;
    object-fit: cover;
    border: 2px solid #c7d2fe;
    flex-shrink: 0;
}
.dz-meta { font-size: 13px; color: var(--color-text); flex: 1; }
.dz-meta strong { display: block; font-size: 14px; margin-bottom: 4px; }
.dz-remove {
    position: absolute; top: 10px; right: 10px;
    background: #fff; border: none; border-radius: 50%;
    font-size: 22px; color: #ef4444;
    cursor: pointer; padding: 0; line-height: 1;
    box-shadow: 0 1px 4px rgba(0,0,0,.15);
    transition: transform .15s;
}
.dz-remove:hover { transform: scale(1.15); }

/* ── Tips ──────────────────────────────────────────────────── */
.ocr-tips {
    background: var(--color-sidebar-bg, #f8fafc);
    border: 1px solid var(--color-border, #e2e8f0);
    border-radius: 10px;
    padding: 14px 18px;
}
.tip-title {
    font-size: 12px; font-weight: 700;
    color: var(--color-primary, #6366f1);
    margin-bottom: 8px;
}
.tip-list {
    margin: 0; padding-left: 20px;
    font-size: 12.5px; color: var(--color-muted);
    line-height: 1.9;
}

/* ── Badge ─────────────────────────────────────────────────── */
.badge-count {
    background: var(--color-primary-light, #eef2ff);
    color: var(--color-primary, #6366f1);
    border-radius: 999px;
    padding: 4px 14px;
    font-size: 13px; font-weight: 600;
}
.btn-sm-dp { padding: 6px 14px !important; font-size: 13px !important; }

/* ── Preview table editable cells ─────────────────────────── */
.ocr-cell-edit {
    border: 1px solid transparent;
    border-radius: 7px;
    padding: 5px 8px;
    width: 100%;
    font-size: 13px;
    background: transparent;
    color: var(--color-text, #1e293b);
    transition: border-color .15s, background .15s;
    outline: none;
}
.ocr-cell-edit:focus {
    border-color: var(--color-primary, #6366f1);
    background: var(--color-primary-light, #eef2ff);
}
.ocr-cell-amount {
    text-align: right;
    font-variant-numeric: tabular-nums;
    font-weight: 600;
    width: 140px;
}
.btn-row-del {
    background: none; border: none;
    color: var(--color-muted); cursor: pointer;
    padding: 3px 7px; border-radius: 7px;
    transition: color .15s, background .15s;
}
.btn-row-del:hover { color: #ef4444; background: #fff1f2; }

/* ── Confirm box ───────────────────────────────────────────── */
.ocr-confirm-box {
    background: linear-gradient(135deg, #eef2ff 0%, #f5f3ff 100%);
    border: 1px solid #c7d2fe;
    border-radius: 14px;
    padding: 20px 24px;
    font-size: 14px;
    color: var(--color-text, #1e293b);
}
.ocr-confirm-box strong { color: var(--color-primary, #6366f1); }

/* ── Dark mode patches ─────────────────────────────────────── */
[data-theme="dark"] .ocr-card            { background: var(--color-card); }
[data-theme="dark"] .ocr-dropzone        { background: #1e1b4b; }
[data-theme="dark"] .ocr-dropzone:hover  { background: #2e2a7a; }
[data-theme="dark"] .ocr-tips            { background: #1e293b; }
[data-theme="dark"] .ocr-confirm-box     { background: #1e1b4b; border-color: #4338ca; color: #e2e8f0; }
[data-theme="dark"] .ocr-cell-edit       { color: #e2e8f0; }
[data-theme="dark"] .ocr-cell-edit:focus { background: #1e1b4b; }
[data-theme="dark"] .dz-thumb            { border-color: #4f46e5; }
</style>
@endpush

@push('js')
<script>
(function () {
    'use strict';

    // ── State ───────────────────────────────────────────────────────
    let ocrItems   = [];   // ParsedItemDTO[] from server
    let rowIdSeq   = 0;    // auto-increment row id

    // ── Step navigation ─────────────────────────────────────────────
    function ocrSetStep(n) {
        [1, 2, 3].forEach(i => {
            const el = document.getElementById('ocrStep' + i);
            if (el) el.classList.toggle('d-none', i !== n);
            const dot = document.getElementById('stepDot' + i);
            if (dot) {
                dot.classList.remove('active', 'done');
                if (i < n)  dot.classList.add('done');
                if (i === n) dot.classList.add('active');
            }
        });
        ['conn1', 'conn2'].forEach((id, idx) => {
            const el = document.getElementById(id);
            if (el) el.classList.toggle('done', idx < n - 1);
        });
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    window.ocrBackToStep1 = () => ocrSetStep(1);
    window.ocrBackToStep2 = () => ocrSetStep(2);

    // ── Drop zone ────────────────────────────────────────────────────
    const dropzone = document.getElementById('ocrDropzone');

    dropzone.addEventListener('dragover', e => {
        e.preventDefault();
        dropzone.classList.add('drag-over');
    });
    ['dragleave', 'dragend'].forEach(ev => {
        dropzone.addEventListener(ev, () => dropzone.classList.remove('drag-over'));
    });
    dropzone.addEventListener('drop', e => {
        e.preventDefault();
        dropzone.classList.remove('drag-over');
        const file = e.dataTransfer.files[0];
        if (file) applyFile(file);
    });

    window.onFileSelect = function (e) {
        const file = e.target.files[0];
        if (file) applyFile(file);
    };

    function applyFile(file) {
        if (!file.type.startsWith('image/')) {
            alert('Hanya file gambar yang diterima.');
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            alert('Ukuran gambar melebihi 5 MB.');
            return;
        }
        const reader = new FileReader();
        reader.onload = ev => {
            document.getElementById('dzImg').src = ev.target.result;
            document.getElementById('dzMeta').innerHTML =
                `<strong>${escHtml(file.name)}</strong>
                 ${formatBytes(file.size)} · ${file.type.split('/')[1].toUpperCase()}`;
            document.getElementById('dzIdle').classList.add('d-none');
            document.getElementById('dzPreview').classList.remove('d-none');
            document.getElementById('btnScanOCR').disabled = false;
        };
        reader.readAsDataURL(file);
        // Keep real file on input for FormData
        const dt = new DataTransfer();
        dt.items.add(file);
        document.getElementById('ocrImageInput').files = dt.files;
    }

    window.clearImage = function (e) {
        e.stopPropagation();
        document.getElementById('dzPreview').classList.add('d-none');
        document.getElementById('dzIdle').classList.remove('d-none');
        document.getElementById('ocrImageInput').value = '';
        document.getElementById('btnScanOCR').disabled = true;
    };

    // ── STEP 1 → 2: call OCR preview endpoint ───────────────────────
    window.doOCR = async function () {
        const fileInput = document.getElementById('ocrImageInput');
        if (!fileInput.files || fileInput.files.length === 0) {
            alert('Pilih gambar terlebih dahulu.');
            return;
        }

        setBtnLoading(true);

        const formData = new FormData();
        formData.append('image', fileInput.files[0]);
        formData.append('_token', '{{ csrf_token() }}');

        try {
            const resp = await fetch('{{ route("ocr.preview") }}', {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: formData,
            });

            const data = await resp.json();

            if (!resp.ok || !data.success) {
                showOCRError(data.message || data.errors?.image?.[0] || 'Gagal membaca gambar.');
                ocrSetStep(2);
                return;
            }

            ocrItems = data.items;
            renderOCRTable(ocrItems);
            ocrSetStep(2);

        } catch (err) {
            console.error(err);
            showOCRError('Terjadi kesalahan koneksi: ' + err.message);
            ocrSetStep(2);
        } finally {
            setBtnLoading(false);
        }
    };

    function setBtnLoading(loading) {
        const btn = document.getElementById('btnScanOCR');
        document.getElementById('btnScanIdle').classList.toggle('d-none', loading);
        document.getElementById('btnScanSpinner').classList.toggle('d-none', !loading);
        btn.disabled = loading;
    }

    // ── Render step 2 table ──────────────────────────────────────────
    function renderOCRTable(items) {
        const errEl = document.getElementById('ocrParseError');
        errEl.classList.add('d-none');

        const tbody = document.getElementById('ocrPreviewBody');
        tbody.innerHTML = '';
        rowIdSeq = 0;

        if (items.length === 0) {
            showOCRError('Tidak ada item yang bisa dibaca dari gambar ini. Coba foto yang lebih jelas.');
            return;
        }

        items.forEach(item => addRow(item.name, item.amount));
        updateOCRSummary();
    }

    function addRow(name = '', amount = 0) {
        const id  = ++rowIdSeq;
        const tr  = document.createElement('tr');
        tr.id     = 'ocrRow' + id;
        tr.dataset.rowId = id;
        tr.innerHTML = `
            <td>
                <input type="checkbox" class="form-check-input ocr-row-check"
                    data-id="${id}" checked onchange="updateOCRSummary()">
            </td>
            <td>
                <input type="text"
                    class="ocr-cell-edit"
                    value="${escHtml(name)}"
                    placeholder="Nama item…"
                    oninput="updateOCRSummary()"
                    data-field="name" data-id="${id}">
            </td>
            <td>
                <input type="number"
                    class="ocr-cell-edit ocr-cell-amount"
                    value="${amount}"
                    min="0"
                    placeholder="0"
                    oninput="updateOCRSummary()"
                    data-field="amount" data-id="${id}">
            </td>
            <td>
                <button type="button" class="btn-row-del" title="Hapus baris"
                    onclick="ocrDeleteRow(${id})">
                    <i class="bi bi-x-lg"></i>
                </button>
            </td>`;
        document.getElementById('ocrPreviewBody').appendChild(tr);
    }

    window.addEmptyRow  = () => { addRow(); updateOCRSummary(); };
    window.ocrDeleteRow = (id) => {
        document.getElementById('ocrRow' + id)?.remove();
        updateOCRSummary();
    };

    window.ocrToggleAll = function (master) {
        document.querySelectorAll('.ocr-row-check').forEach(cb => {
            cb.checked = master.checked;
        });
        updateOCRSummary();
    };

    window.updateOCRSummary = function () {
        const rows    = [...document.getElementById('ocrPreviewBody').rows];
        let   count   = 0;
        let   total   = 0;

        rows.forEach(tr => {
            const cb  = tr.querySelector('.ocr-row-check');
            if (!cb || !cb.checked) return;
            const amt = parseFloat(tr.querySelector('[data-field="amount"]')?.value || 0);
            count++;
            total += amt;
        });

        document.getElementById('ocrSummaryBadge').textContent =
            `${count} item dipilih · Total ${formatRupiah(total)}`;
    };

    // ── STEP 2 → 3 ──────────────────────────────────────────────────
    window.ocrGoToStep3 = function () {
        const chosen = collectCheckedRows();
        if (chosen.length === 0) { alert('Pilih minimal satu item.'); return; }

        document.getElementById('ocrItemsPayload').value = JSON.stringify(chosen);

        const total = chosen.reduce((s, r) => s + r.amount, 0);
        document.getElementById('ocrConfirmBox').innerHTML =
            `<div style="font-size:15px;font-weight:700;margin-bottom:8px;">
                <i class="bi bi-receipt me-2" style="color:var(--color-primary);"></i>
                Ringkasan Import
             </div>
             Siap mengimpor <strong>${chosen.length} item</strong>
             dengan total <strong>${formatRupiah(total)}</strong>.
             <div class="mt-2" style="font-size:12px;color:var(--color-muted);">
                Pastikan dompet, kategori, jenis, dan tanggal sudah benar.
             </div>`;

        document.getElementById('ocrBtnSaveLabel').textContent = `Simpan ${chosen.length} Transaksi`;
        ocrSetStep(3);
    };

    function collectCheckedRows() {
        const results = [];
        document.querySelectorAll('.ocr-row-check:checked').forEach(cb => {
            const id  = cb.dataset.id;
            const row = document.getElementById('ocrRow' + id);
            if (!row) return;
            const name   = row.querySelector('[data-field="name"]')?.value?.trim() ?? '';
            const amount = parseInt(row.querySelector('[data-field="amount"]')?.value || 0, 10);
            if (name && amount > 0) results.push({ name, amount });
        });
        return results;
    }

    // ── Type toggle ──────────────────────────────────────────────────
    window.ocrSelectType = function (type) {
        document.getElementById('ocrType').value = type;
        ['expense', 'income'].forEach(t => {
            document.getElementById('ocrType' + capitalize(t)).className =
                'type-btn' + (t === type ? ' ' + t + '-active' : '');
        });
    };

    // ── Prevent double-submit ────────────────────────────────────────
    document.getElementById('ocrImportForm').addEventListener('submit', function () {
        const btn = document.getElementById('ocrBtnSave');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Menyimpan…';
    });

    // ── Helpers ──────────────────────────────────────────────────────
    function showOCRError(msg) {
        const el = document.getElementById('ocrParseError');
        document.getElementById('ocrParseErrorMsg').textContent = msg;
        el.classList.remove('d-none');
    }
    function escHtml(str) {
        return String(str)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
            .replace(/"/g,'&quot;').replace(/'/g,'&#039;');
    }
    function formatRupiah(n) {
        return 'Rp\u00a0' + Number(n).toLocaleString('id-ID');
    }
    function formatBytes(b) {
        if (b < 1024)        return b + ' B';
        if (b < 1024 * 1024) return (b / 1024).toFixed(1) + ' KB';
        return (b / (1024 * 1024)).toFixed(1) + ' MB';
    }
    function capitalize(s) { return s.charAt(0).toUpperCase() + s.slice(1); }

    // Init
    ocrSetStep(1);
    ocrSelectType('expense');
})();
</script>
@endpush
