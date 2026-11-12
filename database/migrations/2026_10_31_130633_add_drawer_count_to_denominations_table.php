<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('denominations', function (Blueprint $table) {
            $table->index(['cashier_id', 'business_date', 'type'], 'den_idx_cashier_date_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('denominations', function (Blueprint $table) {
            $table->dropIndex('den_idx_cashier_date_type');
        });
    }
};
