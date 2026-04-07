@extends('layouts.main')

@section('title', 'Kategori')
@section('subtitle', 'Kelola kategori pemasukan & pengeluaran')

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

    <div class="row g-3">
        {{-- Pemasukan --}}
        <div class="col-lg-6">
            <div class="table-card">
                <div class="table-card-header">
                    <h2 class="table-card-title" style="color:var(--color-income)"><i class="bi bi-arrow-down-left me-2"></i>Pemasukan</h2>
                    <button class="btn-primary-dp" onclick="openCreate('income')" data-bs-toggle="modal" data-bs-target="#categoryModal">
                        <i class="bi bi-plus-lg"></i> Tambah
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table dompetra-table">
                        <thead><tr><th>Nama</th><th>Transaksi</th><th></th></tr></thead>
                        <tbody>
                            @forelse($categories->where('type','income') as $cat)
                                <tr>
                                    <td>
                                        <span class="cat-badge" style="background:#f0fdf4;color:#15803d;">
                                            <span class="cat-dot" style="background:#15803d;"></span>
                                            {{ $cat->name }}
                                        </span>
                                    </td>
                                    <td style="color:var(--color-muted);font-size:12px;">{{ $cat->transactions()->count() }} transaksi</td>
                                    <td class="text-end" style="white-space:nowrap;">
                                        <button class="btn-outline-dp py-1 px-2 me-1" style="font-size:12px;"
                                            onclick="editCategory({{ $cat->id }}, '{{ addslashes($cat->name) }}', '{{ $cat->type }}', '{{ $cat->icon }}')"
                                            data-bs-toggle="modal" data-bs-target="#categoryModal">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" action="{{ route('category.destroy', $cat) }}" class="d-inline"
                                              onsubmit="return confirm('Hapus kategori {{ addslashes($cat->name) }}?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn-outline-dp py-1 px-2" style="font-size:12px;color:var(--color-expense);border-color:var(--color-expense-ring);">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center py-3" style="color:var(--color-muted);font-size:13px;">Belum ada kategori pemasukan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Pengeluaran --}}
        <div class="col-lg-6">
            <div class="table-card">
                <div class="table-card-header">
                    <h2 class="table-card-title" style="color:var(--color-expense)"><i class="bi bi-arrow-up-right me-2"></i>Pengeluaran</h2>
                    <button class="btn-primary-dp" onclick="openCreate('expense')" data-bs-toggle="modal" data-bs-target="#categoryModal">
                        <i class="bi bi-plus-lg"></i> Tambah
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table dompetra-table">
                        <thead><tr><th>Nama</th><th>Transaksi</th><th></th></tr></thead>
                        <tbody>
                            @forelse($categories->where('type','expense') as $cat)
                                <tr>
                                    <td>
                                        <span class="cat-badge" style="background:#fff1f2;color:#b91c1c;">
                                            <span class="cat-dot" style="background:#b91c1c;"></span>
                                            {{ $cat->name }}
                                        </span>
                                    </td>
                                    <td style="color:var(--color-muted);font-size:12px;">{{ $cat->transactions()->count() }} transaksi</td>
                                    <td class="text-end" style="white-space:nowrap;">
                                        <button class="btn-outline-dp py-1 px-2 me-1" style="font-size:12px;"
                                            onclick="editCategory({{ $cat->id }}, '{{ addslashes($cat->name) }}', '{{ $cat->type }}', '{{ $cat->icon }}')"
                                            data-bs-toggle="modal" data-bs-target="#categoryModal">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" action="{{ route('category.destroy', $cat) }}" class="d-inline"
                                              onsubmit="return confirm('Hapus kategori {{ addslashes($cat->name) }}?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn-outline-dp py-1 px-2" style="font-size:12px;color:var(--color-expense);border-color:var(--color-expense-ring);">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center py-3" style="color:var(--color-muted);font-size:13px;">Belum ada kategori pengeluaran.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Modal -->
    <div class="modal fade" id="categoryModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" style="max-width:400px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="categoryModalTitle">Tambah Kategori</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="categoryForm" method="POST" action="{{ route('category.store') }}">
                    @csrf
                    <span id="categoryMethodField"></span>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Kategori <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="cName" class="form-control"
                                   placeholder="cth. Gaji, Makanan, Transportasi" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jenis <span class="text-danger">*</span></label>
                            <div class="type-toggle">
                                <div class="type-btn" id="cTypeExpense" onclick="selectCatType('expense')">
                                    <i class="bi bi-arrow-up-right me-1"></i> Pengeluaran
                                </div>
                                <div class="type-btn" id="cTypeIncome" onclick="selectCatType('income')">
                                    <i class="bi bi-arrow-down-left me-1"></i> Pemasukan
                                </div>
                            </div>
                            <input type="hidden" name="type" id="cType" value="expense">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ikon (Bootstrap Icons)</label>
                            <input type="text" name="icon" id="cIcon" class="form-control"
                                   placeholder="cth. bi-cart">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-outline-dp" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn-primary-dp"><i class="bi bi-check-lg"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('js')
<script>
function selectCatType(type) {
    document.getElementById('cType').value = type;
    document.getElementById('cTypeExpense').className = 'type-btn' + (type === 'expense' ? ' expense-active' : '');
    document.getElementById('cTypeIncome').className  = 'type-btn' + (type === 'income'  ? ' income-active'  : '');
}
selectCatType('expense');

function openCreate(defaultType) {
    resetCategoryModal();
    selectCatType(defaultType);
}

function editCategory(id, name, type, icon) {
    resetCategoryModal();
    document.getElementById('categoryModalTitle').textContent = 'Edit Kategori';
    document.getElementById('cName').value = name;
    document.getElementById('cIcon').value = icon || '';
    selectCatType(type);

    const form = document.getElementById('categoryForm');
    form.action = '/category/' + id;
    document.getElementById('categoryMethodField').innerHTML = '<input type="hidden" name="_method" value="PUT">';
}

function resetCategoryModal() {
    document.getElementById('categoryModalTitle').textContent = 'Tambah Kategori';
    document.getElementById('categoryForm').reset();
    document.getElementById('categoryForm').action = '{{ route('category.store') }}';
    document.getElementById('categoryMethodField').innerHTML = '';
    selectCatType('expense');
}

document.getElementById('categoryModal').addEventListener('hidden.bs.modal', resetCategoryModal);
</script>
@endpush
