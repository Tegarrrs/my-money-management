<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recurring_transactions', function (Blueprint $table) {
            $table->text('detail')->nullable()->after('description');
            $table->date('ends_at')->nullable()->after('last_run_at');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('recurring_transaction_id')
                ->nullable()
                ->after('receipt_id')
                ->constrained()
                ->nullOnDelete();
            $table->timestamp('recurring_run_at')->nullable()->after('recurring_transaction_id');
        });

        Schema::create('recurring_transaction_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recurring_transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('scheduled_for');
            $table->string('status', 20)->default('processing');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(['recurring_transaction_id', 'scheduled_for'], 'recurring_run_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_transaction_runs');

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recurring_transaction_id');
            $table->dropColumn('recurring_run_at');
        });

        Schema::table('recurring_transactions', function (Blueprint $table) {
            $table->dropColumn(['detail', 'ends_at']);
        });
    }
};
