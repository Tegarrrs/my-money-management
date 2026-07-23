<?php

namespace App\Services\Recurring;

use App\Actions\Transaction\CreateTransaction;
use App\Models\RecurringTransaction;
use App\Models\RecurringTransactionRun;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class RecurringTransactionProcessor
{
    public function __construct(
        private readonly CreateTransaction $createTransaction,
        private readonly RecurringSchedule $schedule,
    ) {}

    public function processDue(?int $userId = null, ?Carbon $now = null): array
    {
        $now ??= now();
        $query = RecurringTransaction::query()
            ->where('is_active', true)
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', $now)
            ->when($userId, fn ($builder) => $builder->where('user_id', $userId));

        $result = ['completed' => 0, 'failed' => 0];

        $query->pluck('id')->each(function (int $id) use ($now, &$result) {
            for ($attempt = 0; $attempt < 12; $attempt++) {
                $recurring = RecurringTransaction::find($id);
                if (! $recurring?->is_active || ! $recurring->next_run_at?->lte($now)) {
                    break;
                }
                if ($recurring->ends_at && $recurring->next_run_at->isAfter($recurring->ends_at->endOfDay())) {
                    $recurring->update(['is_active' => false]);
                    break;
                }

                $success = $this->runScheduled($id, $recurring->next_run_at);
                $result[$success ? 'completed' : 'failed']++;

                if (! $success) {
                    break;
                }
            }
        });

        return $result;
    }

    public function runNow(RecurringTransaction $recurring): bool
    {
        return $this->execute($recurring->id, now()->startOfMinute(), false);
    }

    private function runScheduled(int $id, Carbon $scheduledFor): bool
    {
        return $this->execute($id, $scheduledFor, true);
    }

    private function execute(int $id, Carbon $scheduledFor, bool $advanceSchedule): bool
    {
        try {
            return DB::transaction(function () use ($id, $scheduledFor, $advanceSchedule) {
                $recurring = RecurringTransaction::query()->lockForUpdate()->findOrFail($id);
                $run = RecurringTransactionRun::firstOrCreate(
                    [
                        'recurring_transaction_id' => $recurring->id,
                        'scheduled_for' => $scheduledFor,
                    ],
                    ['status' => 'processing'],
                );

                if ($run->status === 'completed') {
                    if ($advanceSchedule && $recurring->next_run_at?->equalTo($scheduledFor)) {
                        $this->advance($recurring, $scheduledFor);
                    }

                    return true;
                }

                $type = (float) $recurring->amount >= 0 ? 'income' : 'expense';
                $transaction = $this->createTransaction->execute($recurring->user, [
                    'wallet_id' => $recurring->wallet_id,
                    'category_id' => $recurring->category_id,
                    'type' => $type,
                    'amount' => abs((float) $recurring->amount),
                    'description' => $recurring->description,
                    'detail' => $recurring->detail,
                    'transaction_date' => $scheduledFor->toDateString(),
                ]);
                $transaction->update([
                    'recurring_transaction_id' => $recurring->id,
                    'recurring_run_at' => $scheduledFor,
                ]);
                $run->update([
                    'transaction_id' => $transaction->id,
                    'status' => 'completed',
                    'error_message' => null,
                ]);

                if ($advanceSchedule) {
                    $this->advance($recurring, $scheduledFor);
                } else {
                    $recurring->update(['last_run_at' => $scheduledFor]);
                }

                return true;
            });
        } catch (Throwable $exception) {
            RecurringTransactionRun::updateOrCreate(
                ['recurring_transaction_id' => $id, 'scheduled_for' => $scheduledFor],
                ['status' => 'failed', 'error_message' => mb_substr($exception->getMessage(), 0, 1000)],
            );

            report($exception);

            return false;
        }
    }

    private function advance(RecurringTransaction $recurring, Carbon $scheduledFor): void
    {
        $next = $this->schedule->next($scheduledFor, $recurring->frequency);
        $recurring->update([
            'last_run_at' => $scheduledFor,
            'next_run_at' => $next,
            'is_active' => ! $recurring->ends_at || $next->lte($recurring->ends_at->endOfDay()),
        ]);
    }
}
