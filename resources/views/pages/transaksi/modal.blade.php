<div class="modal fade" id="addTxModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:440px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Transaksi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Jenis Transaksi</label>
                    <div class="type-toggle">
                        <div class="type-btn expense-active" id="typeExpense" onclick="selectType('expense')"><i
                                class="bi bi-arrow-up-right me-1"></i> Pengeluaran</div>
                        <div class="type-btn" id="typeIncome" onclick="selectType('income')"><i
                                class="bi bi-arrow-down-left me-1"></i> Pemasukan</div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Deskripsi</label>
                    <input type="text" class="form-control" placeholder="cth. Belanja di Alfamart" />
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label">Jumlah (Rp)</label>
                        <input type="number" class="form-control" placeholder="0" />
                    </div>
                    <div class="col-6">
                        <label class="form-label">Tanggal</label>
                        <input type="date" class="form-control" value="2025-06-30" />
                    </div>
                </div>
                <div class="mt-3">
                    <label class="form-label">Kategori</label>
                    <select class="form-select">
                        <option value="">Pilih kategori…</option>
                        <option>Makanan &amp; Minuman</option>
                        <option>Transportasi</option>
                        <option>Belanja</option>
                        <option>Hiburan</option>
                        <option>Kesehatan</option>
                        <option>Tagihan &amp; Utilitas</option>
                        <option>Gaji</option>
                        <option>Lainnya</option>
                    </select>
                </div>
                <div class="mt-3">
                    <label class="form-label">Catatan (opsional)</label>
                    <textarea class="form-control" rows="2" placeholder="Tambahkan catatan…"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-outline-dp" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn-primary-dp" onclick="saveTransaction()"><i class="bi bi-check-lg"></i>
                    Simpan Transaksi</button>
            </div>
        </div>
    </div>
</div>