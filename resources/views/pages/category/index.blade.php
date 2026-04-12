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
            <div class="rp-card h-100 mb-0">
                <div class="rp-card-header">
                    <div>
                        <div class="rp-card-title" style="color:#15803d">
                            <i class="bi bi-arrow-down-left me-2"></i>Pemasukan
                        </div>
                    </div>
                    <button class="btn-dp btn-dp-primary" onclick="openCreate('income')" data-bs-toggle="modal"
                        data-bs-target="#categoryModal">
                        <i class="bi bi-plus-lg"></i> Tambah
                    </button>
                </div>
                <div class="rp-card-body-flush" style="max-height:400px;overflow-y:auto;">
                    <table class="tx-table">
                        <thead style="position:sticky;top:0;z-index:1;background:#fff;">
                            <tr>
                                <th>Nama</th>
                                <th>Total Transaksi</th>
                                <th class="th-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categories->where('type', 'income') as $cat)
                                <tr>
                                    <td>
                                        <span class="cat-pill">
                                            <span class="cat-dot" style="background:#15803d;"></span>
                                            {{ $cat->name }}
                                        </span>
                                    </td>
                                    <td style="color:#9ca3af;font-size:12px;font-weight:500;">
                                        {{ $cat->transactions()->count() }} transaksi
                                    </td>
                                    <td class="cell-actions">
                                        <button class="btn-icon me-1"
                                            onclick="editCategory({{ $cat->id }}, '{{ addslashes($cat->name) }}', '{{ $cat->type }}')"
                                            data-bs-toggle="modal" data-bs-target="#categoryModal"
                                            title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" action="{{ route('category.destroy', $cat) }}" class="d-inline"
                                            onsubmit="return confirm('Hapus kategori {{ addslashes($cat->name) }}?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn-icon btn-icon-del" title="Hapus">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3">
                                        <div class="empty-state" style="padding:32px 20px;">
                                            <i class="bi bi-inbox" style="font-size:24px;"></i>
                                            Belum ada kategori pemasukan.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Pengeluaran --}}
        <div class="col-lg-6">
            <div class="rp-card h-100 mb-0">
                <div class="rp-card-header">
                    <div>
                        <div class="rp-card-title" style="color:#b91c1c">
                            <i class="bi bi-arrow-up-right me-2"></i>Pengeluaran
                        </div>
                    </div>
                    <button class="btn-dp btn-dp-primary" onclick="openCreate('expense')" data-bs-toggle="modal"
                        data-bs-target="#categoryModal">
                        <i class="bi bi-plus-lg"></i> Tambah
                    </button>
                </div>
                <div class="rp-card-body-flush" style="max-height:400px;overflow-y:auto;">
                    <table class="tx-table">
                        <thead style="position:sticky;top:0;z-index:1;background:#fff;">
                            <tr>
                                <th>Nama</th>
                                <th>Total Transaksi</th>
                                <th class="th-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categories->where('type', 'expense') as $cat)
                                <tr>
                                    <td>
                                        <span class="cat-pill">
                                            <span class="cat-dot" style="background:#b91c1c;"></span>
                                            {{ $cat->name }}
                                        </span>
                                    </td>
                                    <td style="color:#9ca3af;font-size:12px;font-weight:500;">
                                        {{ $cat->transactions()->count() }} transaksi
                                    </td>
                                    <td class="cell-actions">
                                        <button class="btn-icon me-1"
                                            onclick="editCategory({{ $cat->id }}, '{{ addslashes($cat->name) }}', '{{ $cat->type }}')"
                                            data-bs-toggle="modal" data-bs-target="#categoryModal"
                                            title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" action="{{ route('category.destroy', $cat) }}" class="d-inline"
                                            onsubmit="return confirm('Hapus kategori {{ addslashes($cat->name) }}?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn-icon btn-icon-del" title="Hapus">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3">
                                        <div class="empty-state" style="padding:32px 20px;">
                                            <i class="bi bi-inbox" style="font-size:24px;"></i>
                                            Belum ada kategori pengeluaran.
                                        </div>
                                    </td>
                                </tr>
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
            <div class="modal-content" style="border-radius:16px;border:1px solid #e5e7eb;">
                <div class="modal-header" style="border-bottom:1px solid #f3f4f6;padding:18px 24px;">
                    <h5 class="modal-title" id="categoryModalTitle" style="font-size:15px;font-weight:700;">Tambah Kategori</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="categoryForm" method="POST" action="{{ route('category.store') }}">
                    @csrf
                    <span id="categoryMethodField"></span>
                    <div class="modal-body" style="padding:20px 24px;">
                        <div class="mb-4">
                            <label class="form-label fw-semibold" style="font-size:13px;">Jenis <span class="text-danger">*</span></label>
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
                            <label class="form-label" style="font-size:13px;font-weight:500;">Nama Kategori <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="cName" class="form-control"
                                style="font-size:13px;border-radius:9px;"
                                placeholder="cth. Gaji, Makanan, Transportasi" required>
                        </div>
                    </div>
                    <div class="modal-footer" style="padding:16px 24px;border-top:1px solid #f3f4f6;">
                        <button type="button" class="btn-dp btn-dp-default" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn-dp btn-dp-primary"><i class="bi bi-check-lg"></i> Simpan</button>
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
            document.getElementById('cTypeIncome').className = 'type-btn' + (type === 'income' ? ' income-active' : '');
        }
        selectCatType('expense');

        function openCreate(defaultType) {
            resetCategoryModal();
            selectCatType(defaultType);
        }

        function editCategory(id, name, type) {
            resetCategoryModal();
            document.getElementById('categoryModalTitle').textContent = 'Edit Kategori';
            document.getElementById('cName').value = name;
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