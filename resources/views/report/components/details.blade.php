<div class="rp-card mt-4">
    <div class="rp-card-header flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
        <div>
            <div class="rp-card-title">Detail Transaksi</div>
            <div class="rp-card-sub">Menampilkan {{ count($transactions) }} transaksi dalam periode terpilih</div>
        </div>
        
        {{-- Interactive Filter & Search Bar --}}
        @if(count($transactions) > 0)
            <div class="d-flex flex-wrap gap-2 align-items-center w-100 w-md-auto">
                {{-- Search Input --}}
                <div class="position-relative" style="min-width: 220px;">
                    <i class="bi bi-search position-absolute text-muted" style="left: 10px; top: 50%; transform: translateY(-50%); font-size: 13px;"></i>
                    <input type="text" id="reportSearch" class="form-control form-control-sm ps-4" 
                           placeholder="Cari deskripsi, kategori..." 
                           style="border-radius: 8px; font-size: 13px; border: 1px solid #e5e7eb; padding: 6px 12px 6px 30px; height: 32px; background-color: #f9fafb;">
                </div>
                
                {{-- Type Filters --}}
                <div class="btn-group btn-group-sm" role="group" aria-label="Filter Jenis">
                    <button type="button" class="btn btn-outline-secondary active btn-filter-type" data-type="all" style="font-size: 12px; border-radius: 8px 0 0 8px; padding: 5px 12px;">Semua</button>
                    <button type="button" class="btn btn-outline-success btn-filter-type" data-type="income" style="font-size: 12px; padding: 5px 12px;">Pemasukan</button>
                    <button type="button" class="btn btn-outline-danger btn-filter-type" data-type="expense" style="font-size: 12px; padding: 5px 12px;">Pengeluaran</button>
                    <button type="button" class="btn btn-outline-primary btn-filter-type" data-type="transfer" style="font-size: 12px; border-radius: 0 8px 8px 0; padding: 5px 12px;">Transfer</button>
                    <button type="button" class="btn btn-outline-secondary btn-filter-type" data-type="adjustment" style="font-size: 12px; border-radius: 8px; padding: 5px 12px;">Koreksi</button>
                </div>
            </div>
        @endif
    </div>
    
    <div class="rp-card-body-flush">
        <div class="table-responsive" style="max-height: 480px; overflow-y: auto;">
            <table class="tx-table w-100" id="reportDetailsTable" style="border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="position: sticky; top: 0; background: #fff; z-index: 10; box-shadow: 0 1px 0 #e5e7eb;">
                        <th style="width:120px; padding: 12px 16px; background: #f9fafb; font-weight: 600; color: #4b5563; font-size: 12px; text-transform: uppercase; border-bottom: 1px solid #e5e7eb;">Tanggal</th>
                        <th style="width:250px; padding: 12px 16px; background: #f9fafb; font-weight: 600; color: #4b5563; font-size: 12px; text-transform: uppercase; border-bottom: 1px solid #e5e7eb;">Deskripsi</th>
                        <th style="width:150px; padding: 12px 16px; background: #f9fafb; font-weight: 600; color: #4b5563; font-size: 12px; text-transform: uppercase; border-bottom: 1px solid #e5e7eb;">Kategori</th>
                        <th style="width:180px; padding: 12px 16px; background: #f9fafb; font-weight: 600; color: #4b5563; font-size: 12px; text-transform: uppercase; border-bottom: 1px solid #e5e7eb;">Dompet</th>
                        <th style="width:110px; padding: 12px 16px; background: #f9fafb; font-weight: 600; color: #4b5563; font-size: 12px; text-transform: uppercase; border-bottom: 1px solid #e5e7eb;">Jenis</th>
                        <th class="th-right" style="width:140px; padding: 12px 16px; background: #f9fafb; font-weight: 600; color: #4b5563; font-size: 12px; text-transform: uppercase; border-bottom: 1px solid #e5e7eb; text-align: right;">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $tx)
                        @php
                            $isTransfer = !is_null($tx->transfer_group_id);
                            $isIncome   = !$isTransfer && $tx->amount >= 0;
                            $isSplit    = !is_null($tx->split_group_id);
                            $isAdjustment = (bool) $tx->is_balance_adjustment;
                            
                            $rowType = 'expense';
                            if ($isTransfer) {
                                $rowType = 'transfer';
                            } elseif ($isAdjustment) {
                                $rowType = 'adjustment';
                            } elseif ($isIncome) {
                                $rowType = 'income';
                            }
                        @endphp
                        <tr class="report-tx-row" 
                            data-type="{{ $rowType }}" 
                            data-search="{{ strtolower($tx->description . ' ' . ($tx->detail ?? '') . ' ' . ($tx->category?->name ?? '') . ' ' . ($tx->wallet?->name ?? '')) }}"
                            style="border-bottom: 1px solid #f3f4f6; transition: background 0.15s ease;">
                            
                            {{-- Tanggal --}}
                            <td class="cell-date" style="padding: 12px 16px; vertical-align: middle;">
                                <div class="date-main" style="font-weight: 600; color: #1f2937; font-size: 13px;">{{ $tx->transaction_date->format('d M Y') }}</div>
                                <div class="date-day" style="font-size: 11px; color: #9ca3af; margin-top: 1px;">{{ $tx->transaction_date->translatedFormat('l') }}</div>
                            </td>
                            
                            {{-- Deskripsi --}}
                            <td class="cell-desc" style="padding: 12px 16px; vertical-align: middle;">
                                <span class="desc-main" style="font-weight: 500; color: #1f2937; font-size: 13px;" title="{{ $tx->description }}">{{ $tx->description ?? '—' }}</span>
                                @if($isSplit)
                                    <span class="type-badge badge-transfer" style="font-size: 10px; padding: 2px 6px; display: inline-block; margin-top: 2px;">
                                        <i class="bi bi-scissors" style="font-size:9px;"></i> Pecahan
                                    </span>
                                @endif
                                @if($tx->detail)
                                    <span class="desc-detail" style="display: block; font-size: 11px; color: #6b7280; margin-top: 2px; line-height: 1.3;" title="{{ $tx->detail }}">{{ $tx->detail }}</span>
                                @endif
                            </td>
                            
                            {{-- Kategori --}}
                            <td class="cell-cat" style="padding: 12px 16px; vertical-align: middle;">
                                @if($isAdjustment)
                                    <span class="type-badge" style="background:#f5f3ff;color:#7c3aed;">
                                        <i class="bi bi-sliders" style="font-size:9px;"></i> Koreksi Saldo
                                    </span>
                                @elseif($isTransfer)
                                    <span class="type-badge badge-transfer" style="font-size: 11px; padding: 4px 8px; border-radius: 6px;">
                                        <i class="bi bi-arrow-left-right" style="font-size:9px;"></i> Transfer
                                    </span>
                                @elseif($tx->category)
                                    <span class="cat-pill" style="display: inline-flex; align-items: center; gap: 6px; background: #f3f4f6; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 500; color: #374151;">
                                        <span class="cat-dot" style="display: inline-block; width: 6px; height: 6px; border-radius: 50%; background:{{ $tx->category->type === 'income' ? '#15803d' : '#b91c1c' }}"></span>
                                        {{ $tx->category->name }}
                                    </span>
                                @else
                                    <span style="color:#9ca3af;font-size:12px;">—</span>
                                @endif
                            </td>
                            
                            {{-- Dompet --}}
                            <td class="cell-wallet" style="padding: 12px 16px; vertical-align: middle;">
                                @if($isAdjustment)
                                    <span class="type-badge" style="background:#f5f3ff;color:#7c3aed;">Koreksi</span>
                                @elseif($isTransfer)
                                    <div class="wallet-chip d-inline-flex align-items-center gap-1" style="background: #f3f4f6; padding: 4px 8px; border-radius: 6px; font-size: 12px;">
                                        <span style="font-weight:600;color:#374151;">{{ $tx->wallet?->name ?? '—' }}</span>
                                        <span class="wallet-arrow text-muted mx-1" style="font-size: 10px;"><i class="bi bi-arrow-right"></i></span>
                                        <span style="font-weight:600;color:#374151;">{{ $tx->destination_wallet_name ?? '—' }}</span>
                                    </div>
                                @else
                                    <div class="wallet-chip d-inline-flex align-items-center gap-1" style="background: #f3f4f6; padding: 4px 8px; border-radius: 6px; font-size: 12px;">
                                        <i class="bi bi-wallet2" style="font-size:11px;color:#6b7280;"></i>
                                        <span style="font-weight:600;color:#374151;">{{ $tx->wallet?->name ?? '—' }}</span>
                                    </div>
                                @endif
                            </td>
                            
                            {{-- Jenis --}}
                            <td style="padding: 12px 16px; vertical-align: middle;">
                                @if($isTransfer)
                                    <span class="type-badge badge-transfer" style="font-size: 11px; padding: 4px 8px; border-radius: 6px;">Transfer</span>
                                @elseif($isIncome)
                                    <span class="type-badge badge-income" style="font-size: 11px; padding: 4px 8px; border-radius: 6px;">Pemasukan</span>
                                @else
                                    <span class="type-badge badge-expense" style="font-size: 11px; padding: 4px 8px; border-radius: 6px;">Pengeluaran</span>
                                @endif
                            </td>
                            
                            {{-- Jumlah --}}
                            <td class="cell-amount text-end {{ $isTransfer ? 'amt-transfer' : ($isIncome ? 'amt-income' : 'amt-expense') }}" 
                                style="padding: 12px 16px; font-weight: 700; text-align: right; white-space: nowrap; vertical-align: middle; font-size: 13px;">
                                @if($isTransfer)
                                    {{ $tx->formatted_amount }}
                                @else
                                    {{ $isIncome ? '+' : '−' }} {{ $tx->formatted_amount }}
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="chart-empty py-5 text-center text-muted">
                                    <i class="bi bi-inbox d-block mb-2" style="font-size: 32px; color: #d1d5db;"></i>
                                    Belum ada transaksi pada periode ini
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        {{-- Interactive No Match State --}}
        <div id="noMatchState" class="chart-empty py-5 text-center text-muted d-none">
            <i class="bi bi-search d-block mb-2" style="font-size: 32px; color: #d1d5db;"></i>
            Tidak ada transaksi yang cocok dengan pencarian
        </div>
    </div>
</div>

@if(count($transactions) > 0)
@push('js')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('reportSearch');
        const filterBtns = document.querySelectorAll('.btn-filter-type');
        const rows = document.querySelectorAll('.report-tx-row');
        const noMatchState = document.getElementById('noMatchState');
        const table = document.getElementById('reportDetailsTable');
        
        let activeType = 'all';
        let searchQuery = '';
        
        function filterTable() {
            let visibleCount = 0;
            
            rows.forEach(row => {
                const type = row.getAttribute('data-type');
                const searchVal = row.getAttribute('data-search');
                
                const matchesType = (activeType === 'all' || type === activeType);
                const matchesSearch = (!searchQuery || searchVal.includes(searchQuery));
                
                if (matchesType && matchesSearch) {
                    row.classList.remove('d-none');
                    visibleCount++;
                } else {
                    row.classList.add('d-none');
                }
            });
            
            if (visibleCount === 0) {
                noMatchState.classList.remove('d-none');
                table.style.display = 'none';
            } else {
                noMatchState.classList.add('d-none');
                table.style.display = '';
            }
        }
        
        // Search filter
        if (searchInput) {
            searchInput.addEventListener('input', function(e) {
                searchQuery = e.target.value.toLowerCase().trim();
                filterTable();
            });
        }
        
        // Type button filter
        filterBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                filterBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                activeType = this.getAttribute('data-type');
                filterTable();
            });
        });
    });
</script>
@endpush
@endif
