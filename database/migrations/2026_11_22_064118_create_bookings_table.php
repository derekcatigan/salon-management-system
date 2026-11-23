<?php

use App\Enum\BookingStatusEnum;
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
        Schema::create('bookings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('reference_no', 30)->unique();

            $table->foreignUuid('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('service_id')->constrained('services')->restrictOnDelete();

            // Snapshot of service at booking time
            $table->string('service_name');
            $table->string('service_category', 50);
            $table->decimal('service_price', 10, 2);
            $table->integer('service_duration');

            $table->string('full_name', 150);
            $table->string('phone', 30);
            $table->string('email', 150)->nullable();
            $table->date('booking_date');
            $table->string('time_slot', 20);
            $table->text('notes')->nullable();

            // String column, values come from BookingStatusEnum
            $table->string('status', 20)->default(BookingStatusEnum::Pending->value);

            $table->timestamps();

            $table->unique(['booking_date', 'time_slot'], 'unique_slot_per_day');
            $table->index(['customer_id', 'status']);
            $table->index('booking_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
