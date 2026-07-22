<div class="tx-table-wrap" id="txTableWrap" data-total="{{ $transactions->total() }}">
    <table class="tx-table">
        <thead>
            <tr>
                <th style="width: 40px; text-align: center;">
                    <input type="checkbox" id="bulkSelectAll" class="form-check-input" style="cursor:pointer;">
                </th>
                <th style="width:120px;">Tanggal</th>
                <th style="width:220px;">Deskripsi</th>
                <th style="width:140px;">Kategori</th>
                <th style="width:170px;">Dompet</th>
                <th style="width:110px;">Jenis</th>
                <th class="th-right" style="width:140px;">Jumlah</th>
                <th class="th-right" style="width:110px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $tx)
                @php
                    $isTransfer = !is_null($tx->transfer_group_id);
                    $isIncome   = !$isTransfer && $tx->amount >= 0;
                    $isSplit    = !is_null($tx->split_group_id);
                    $isAdjustment = (bool) $tx->is_balance_adjustment;
                @endphp
                <tr data-id="{{ $tx->id }}">
                    {{-- Checkbox bulk action --}}
                    <td style="text-align: center;">
                        <input type="checkbox" value="{{ $tx->id }}" class="bulk-select-item form-check-input" style="cursor:pointer;">
                    </td>

                    {{-- Tanggal --}}
                    <td class="cell-date">
                        <div class="date-main">{{ $tx->transaction_date->format('d M Y') }}</div>
                        <div class="date-day">{{ $tx->transaction_date->translatedFormat('l') }}</div>
                    </td>

                    {{-- Deskripsi --}}
                    <td class="cell-desc">
                        <span class="desc-main" title="{{ $tx->description }}">{{ $tx->description ?? '—' }}</span>
                        @if($isSplit)
                            <span class="type-badge badge-transfer" style="font-size: 10px; padding: 2px 6px; display: inline-block; margin-top: 4px;">
                                <i class="bi bi-scissors"></i> Pecahan
                            </span>
                        @endif
                        @if($tx->detail)
                            <span class="desc-detail" title="{{ $tx->detail }}">{{ $tx->detail }}</span>
                        @endif
                    </td>

                    {{-- Kategori --}}
                    <td class="cell-cat">
                        @if($isAdjustment)
                            <span class="type-badge" style="background:#f5f3ff;color:#7c3aed;">
                                <i class="bi bi-sliders"></i> Koreksi Saldo
                            </span>
                        @elseif($isTransfer)
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
                        @if($isAdjustment)
                            <span class="type-badge" style="background:#f5f3ff;color:#7c3aed;">Koreksi</span>
                        @elseif($isTransfer)
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
                        
                        {{-- Lampiran Struk --}}
                        @if($tx->receipt)
                            <button type="button" class="btn-icon me-1 text-primary" onclick="showReceiptLightbox('{{ route('receipts.show', $tx->receipt) }}')" title="Lihat Struk" aria-label="Lihat struk transaksi">
                                <i class="bi bi-receipt"></i>
                            </button>
                        @endif

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
                                {{ $toWalletId ?? 'null' }},
                                {{ $isSplit ? 'true' : 'false' }}
                            )"
                            title="Edit" aria-label="Edit transaksi">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" action="{{ route('transaction.destroy', $tx) }}" class="d-inline"
                              onsubmit="return confirm('Hapus transaksi ini? Saldo dompet akan dikembalikan.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-icon btn-icon-del" title="Hapus" aria-label="Hapus transaksi">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">
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
            <a href="{{ $transactions->previousPageUrl() }}" class="pg-btn pagination-link"><i class="bi bi-chevron-left"></i></a>
        @endif

        @foreach($transactions->getUrlRange(1, $transactions->lastPage()) as $page => $url)
            @if(abs($page - $transactions->currentPage()) <= 2 || $page === 1 || $page === $transactions->lastPage())
                <a href="{{ $url }}" class="pg-btn pagination-link {{ $page == $transactions->currentPage() ? 'active' : '' }}">{{ $page }}</a>
            @elseif(abs($page - $transactions->currentPage()) === 3)
                <span class="pg-btn disabled">…</span>
            @endif
        @endforeach

        @if($transactions->hasMorePages())
            <a href="{{ $transactions->nextPageUrl() }}" class="pg-btn pagination-link"><i class="bi bi-chevron-right"></i></a>
        @else
            <span class="pg-btn disabled"><i class="bi bi-chevron-right"></i></span>
        @endif
    </div>
</div>
@endif
