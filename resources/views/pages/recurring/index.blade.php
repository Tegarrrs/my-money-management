@extends('layouts.main')

@section('title', 'Transaksi Berulang')
@section('subtitle', 'Otomatiskan tagihan dan pemasukan rutin')

@push('css')
    <link rel="stylesheet" href="{{ asset('assets/recurring.css') }}">
@endpush

@section('content')
    @foreach(['success' => 'success', 'error' => 'danger'] as $key => $style)
        @if(session($key))
            <div class="alert alert-{{ $style }} alert-dismissible fade show mb-3">
                {{ session($key) }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
    @endforeach
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-3">
            <ul class="mb-0 ps-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="recurring-summary">
        <article><i class="bi bi-arrow-repeat"></i><span>Jadwal aktif<strong>{{ $activeCount }}</strong></span></article>
        <article class="{{ $dueCount ? 'danger' : '' }}"><i class="bi bi-clock-history"></i><span>Jatuh tempo<strong>{{ $dueCount }}</strong></span></article>
        <article><i class="bi bi-calendar2-check"></i><span>Estimasi rutin bulanan<strong>Rp {{ number_format($monthlyExpenseEstimate, 0, ',', '.') }}</strong></span></article>
        <button class="btn-rp btn-rp-primary" data-bs-toggle="modal" data-bs-target="#recurringModal" onclick="openRecurringCreate()">
            <i class="bi bi-plus-lg"></i> Tambah jadwal
        </button>
    </div>

    <section class="recurring-card">
        <div class="recurring-card-header">
            <div><span>Daftar otomatisasi</span><h2>Jadwal transaksi</h2></div>
            <small>Scheduler memproses jadwal aktif setiap menit tanpa membuat duplikat.</small>
        </div>

        <div class="recurring-list">
            @forelse($recurringTransactions as $item)
                @php
                    $isExpense = (float) $item->amount < 0;
                    $isDue = $item->is_active && $item->next_run_at?->isPast();
                    $frequencyLabel = match($item->frequency) {
                        'daily' => 'Harian', 'weekly' => 'Mingguan',
                        'yearly' => 'Tahunan', default => 'Bulanan'
                    };
                    $editPayload = [
                        'id' => $item->id,
                        'wallet_id' => $item->wallet_id,
                        'category_id' => $item->category_id,
                        'type' => $isExpense ? 'expense' : 'income',
                        'amount' => abs((float) $item->amount),
                        'description' => $item->description,
                        'detail' => $item->detail,
                        'frequency' => $item->frequency,
                        'next_run_at' => $item->next_run_at?->format('Y-m-d'),
                        'ends_at' => $item->ends_at?->format('Y-m-d'),
                    ];
                @endphp
                <article class="recurring-item {{ !$item->is_active ? 'paused' : '' }} {{ $isDue ? 'due' : '' }}">
                    <div class="recurring-icon {{ $isExpense ? 'expense' : 'income' }}">
                        <i class="bi {{ $item->category?->icon ?? ($isExpense ? 'bi-arrow-up-right' : 'bi-arrow-down-left') }}"></i>
                    </div>
                    <div class="recurring-info">
                        <div class="recurring-name-row">
                            <strong>{{ $item->description }}</strong>
                            <span class="{{ $item->is_active ? 'active' : 'paused' }}">{{ $item->is_active ? 'Aktif' : 'Dijeda' }}</span>
                        </div>
                        <small>{{ $item->category?->name ?? 'Tanpa kategori' }} · {{ $item->wallet?->name ?? 'Dompet dihapus' }} · {{ $frequencyLabel }}</small>
                        <div class="recurring-next {{ $isDue ? 'due' : '' }}">
                            <i class="bi bi-calendar-event"></i>
                            @if($isDue) Terlambat sejak {{ $item->next_run_at->translatedFormat('d M Y') }}
                            @else Berikutnya {{ $item->next_run_at?->translatedFormat('d M Y') ?? '—' }}
                            @endif
                        </div>
                    </div>
                    <div class="recurring-amount {{ $isExpense ? 'expense' : 'income' }}">
                        {{ $isExpense ? '−' : '+' }}Rp {{ number_format(abs((float) $item->amount), 0, ',', '.') }}
                    </div>
                    <div class="recurring-actions">
                        <form method="POST" action="{{ route('recurring.run', $item) }}">@csrf
                            <button title="Jalankan sekarang"><i class="bi bi-play-fill"></i></button>
                        </form>
                        <form method="POST" action="{{ route('recurring.toggle', $item) }}">@csrf @method('PATCH')
                            <button title="{{ $item->is_active ? 'Jeda' : 'Aktifkan' }}"><i class="bi bi-{{ $item->is_active ? 'pause-fill' : 'play-circle' }}"></i></button>
                        </form>
                        <button title="Edit" data-bs-toggle="modal" data-bs-target="#recurringModal"
                            data-recurring="{{ json_encode($editPayload) }}"
                            onclick="openRecurringEdit(JSON.parse(this.dataset.recurring))"><i class="bi bi-pencil"></i></button>
                        <form method="POST" action="{{ route('recurring.destroy', $item) }}" onsubmit="return confirm('Hapus jadwal ini?')">
                            @csrf @method('DELETE')<button class="delete" title="Hapus"><i class="bi bi-trash3"></i></button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="recurring-empty">
                    <i class="bi bi-arrow-repeat"></i><h3>Belum ada transaksi berulang</h3>
                    <p>Otomatiskan gaji, langganan, cicilan, dan tagihan rutin agar tidak terlupa.</p>
                    <button class="btn-rp btn-rp-primary" data-bs-toggle="modal" data-bs-target="#recurringModal" onclick="openRecurringCreate()">Buat jadwal pertama</button>
                </div>
            @endforelse
        </div>
    </section>

    <div class="modal fade" id="recurringModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content recurring-modal">
                <form method="POST" id="recurringForm" action="{{ route('recurring.store') }}">
                    @csrf <span id="recurringMethod"></span>
                    <div class="modal-header"><div><span>Otomatisasi</span><h5 id="recurringTitle">Tambah transaksi berulang</h5></div><button class="btn-close" data-bs-dismiss="modal" type="button"></button></div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6"><label>Jenis</label><select class="form-select" name="type" id="rType"><option value="expense">Pengeluaran</option><option value="income">Pemasukan</option></select></div>
                            <div class="col-md-6"><label>Jumlah</label><div class="input-group"><span class="input-group-text">Rp</span><input class="form-control" type="number" min="1" name="amount" id="rAmount" required></div></div>
                            <div class="col-md-7"><label>Deskripsi</label><input class="form-control" name="description" id="rDescription" required placeholder="Contoh: Langganan internet"></div>
                            <div class="col-md-5"><label>Frekuensi</label><select class="form-select" name="frequency" id="rFrequency"><option value="daily">Harian</option><option value="weekly">Mingguan</option><option value="monthly" selected>Bulanan</option><option value="yearly">Tahunan</option></select></div>
                            <div class="col-md-6"><label>Dompet</label><select class="form-select" name="wallet_id" id="rWallet" required>@foreach($wallets as $wallet)<option value="{{ $wallet->id }}">{{ $wallet->name }}</option>@endforeach</select></div>
                            <div class="col-md-6"><label>Kategori</label><select class="form-select" name="category_id" id="rCategory"><option value="">Tanpa kategori</option>@foreach($categories as $category)<option value="{{ $category->id }}" data-type="{{ $category->type }}">{{ $category->name }}</option>@endforeach</select></div>
                            <div class="col-md-6"><label>Jalankan berikutnya</label><input class="form-control" type="date" name="next_run_at" id="rNext" value="{{ now()->toDateString() }}" required></div>
                            <div class="col-md-6"><label>Berakhir (opsional)</label><input class="form-control" type="date" name="ends_at" id="rEnds"></div>
                            <div class="col-12"><label>Catatan (opsional)</label><textarea class="form-control" name="detail" id="rDetail" rows="2"></textarea></div>
                        </div>
                    </div>
                    <div class="modal-footer"><button class="btn-rp btn-rp-default" type="button" data-bs-dismiss="modal">Batal</button><button class="btn-rp btn-rp-primary" type="submit">Simpan jadwal</button></div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('js')
<script>
    const recurringForm = document.getElementById('recurringForm');
    const recurringType = document.getElementById('rType');
    const recurringCategory = document.getElementById('rCategory');
    function syncRecurringCategories() {
        Array.from(recurringCategory.options).forEach(option => {
            if (!option.dataset.type) return;
            option.hidden = option.dataset.type !== recurringType.value;
            option.disabled = option.hidden;
        });
        if (recurringCategory.selectedOptions[0]?.disabled) recurringCategory.value = '';
    }
    function openRecurringCreate() {
        recurringForm.reset(); recurringForm.action = @json(route('recurring.store'));
        document.getElementById('recurringMethod').innerHTML = '';
        document.getElementById('recurringTitle').textContent = 'Tambah transaksi berulang';
        document.getElementById('rNext').value = @json(now()->toDateString());
        syncRecurringCategories();
    }
    function openRecurringEdit(data) {
        recurringForm.reset(); recurringForm.action = '/recurring/' + data.id;
        document.getElementById('recurringMethod').innerHTML = '<input type="hidden" name="_method" value="PUT">';
        document.getElementById('recurringTitle').textContent = 'Edit transaksi berulang';
        Object.entries({rType:data.type,rAmount:data.amount,rDescription:data.description,rFrequency:data.frequency,rWallet:data.wallet_id,rCategory:data.category_id ?? '',rNext:data.next_run_at,rEnds:data.ends_at ?? '',rDetail:data.detail ?? ''})
            .forEach(([id,value]) => document.getElementById(id).value = value);
        syncRecurringCategories(); document.getElementById('rCategory').value = data.category_id ?? '';
    }
    recurringType.addEventListener('change', syncRecurringCategories);
    syncRecurringCategories();
</script>
@endpush
