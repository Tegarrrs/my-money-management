<?php

use App\Models\User;
use App\Services\Onboarding\DefaultCategoryService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('icon')->nullable()->after('type');
            $table->string('color', 20)->nullable()->after('icon');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('onboarding_step')->default(1)->after('remember_token');
            $table->timestamp('onboarding_completed_at')->nullable()->after('onboarding_step');
        });

        // Pengguna lama tidak dipaksa masuk wizard, tetapi tetap mendapat kategori awal.
        DB::table('users')->update(['onboarding_completed_at' => now()]);

        User::query()->eachById(function (User $user) {
            app(DefaultCategoryService::class)->ensureFor($user);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['onboarding_step', 'onboarding_completed_at']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['icon', 'color']);
        });
    }
};
