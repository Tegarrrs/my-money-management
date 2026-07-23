<section class="report-budget-card">
    <div class="report-budget-main">
        <div class="report-budget-icon"><i class="bi bi-bullseye"></i></div>
        <div>
            <span>Realisasi anggaran {{ $budgetSummary['period']->translatedFormat('F Y') }}</span>
            @if($budgetSummary['has_budgets'])
                <strong>
                    Rp {{ number_format($budgetSummary['total_spent'], 0, ',', '.') }}
                    <small>dari Rp {{ number_format($budgetSummary['total_budget'], 0, ',', '.') }}</small>
                </strong>
            @else
                <strong>Belum ada anggaran</strong>
            @endif
        </div>
    </div>

    @if($budgetSummary['has_budgets'])
        <div class="report-budget-progress-wrap">
            <div>
                <span>Terpakai {{ number_format($budgetSummary['percentage'], 1, ',', '.') }}%</span>
                <b class="{{ $budgetSummary['status'] }}">
                    {{ match($budgetSummary['status']) { 'exceeded' => 'Melebihi', 'warning' => 'Perhatian', default => 'Aman' } }}
                </b>
            </div>
            <div class="report-budget-progress {{ $budgetSummary['status'] }}">
                <span style="width:{{ $budgetSummary['bar_percentage'] }}%"></span>
            </div>
        </div>
        <div class="report-budget-stat">
            <span>Sisa</span>
            <strong class="{{ $budgetSummary['total_remaining'] < 0 ? 'negative' : '' }}">
                Rp {{ number_format(abs($budgetSummary['total_remaining']), 0, ',', '.') }}
            </strong>
        </div>
    @else
        <a href="{{ route('budget.index', ['month' => $budgetSummary['period']->format('Y-m')]) }}" class="btn-rp btn-rp-primary">
            Buat anggaran
        </a>
    @endif

    <a href="{{ route('budget.index', ['month' => $budgetSummary['period']->format('Y-m')]) }}" class="report-budget-link" aria-label="Buka anggaran">
        <i class="bi bi-chevron-right"></i>
    </a>
</section>
