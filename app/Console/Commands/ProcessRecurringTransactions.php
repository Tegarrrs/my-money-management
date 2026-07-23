<?php

namespace App\Console\Commands;

use App\Services\Recurring\RecurringTransactionProcessor;
use Illuminate\Console\Command;

class ProcessRecurringTransactions extends Command
{
    protected $signature = 'transactions:process-recurring {--user= : Batasi ke ID pengguna tertentu}';

    protected $description = 'Mencatat semua transaksi berulang yang sudah jatuh tempo';

    public function handle(RecurringTransactionProcessor $processor): int
    {
        $result = $processor->processDue(
            $this->option('user') ? (int) $this->option('user') : null
        );

        $this->info("Selesai: {$result['completed']}, gagal: {$result['failed']}.");

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
