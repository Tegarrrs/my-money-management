<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'preset' => 'nullable|in:this_month,last_month,last_3_months,last_6_months,year_to_date,custom',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ];
    }

    public function getStartDate(): Carbon
    {
        if ($this->selectedPreset() === 'custom' && $this->filled('start_date')) {
            return Carbon::parse($this->validated('start_date'))->startOfDay();
        }

        return match ($this->selectedPreset()) {
            'last_month' => now()->subMonthNoOverflow()->startOfMonth(),
            'last_3_months' => now()->subMonths(2)->startOfMonth(),
            'last_6_months' => now()->subMonths(5)->startOfMonth(),
            'year_to_date' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };
    }

    public function getEndDate(): Carbon
    {
        if ($this->selectedPreset() === 'custom' && $this->filled('end_date')) {
            return Carbon::parse($this->validated('end_date'))->endOfDay();
        }

        return $this->selectedPreset() === 'last_month'
            ? now()->subMonthNoOverflow()->endOfMonth()
            : now()->endOfDay();
    }

    public function selectedPreset(): string
    {
        if ($this->filled('start_date') || $this->filled('end_date')) {
            return 'custom';
        }

        return $this->validated('preset') ?? 'this_month';
    }
}
