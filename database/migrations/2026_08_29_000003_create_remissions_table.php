<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('remissions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('ambulance_id')->constrained('ambulances');
            $table->foreignId('driver_id')->constrained('users');
            $table->foreignId('patient_id')->constrained('patients');
            $table->string('origin_address');
            $table->string('destination_address');
            $table->enum('status', ['en_camino', 'trasladando', 'finalizado', 'cancelado'])->default('en_camino');
            $table->boolean('is_out_of_city')->default(false);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('transfer_started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->decimal('total_kilometers', 10, 3)->default(0.000);
            $table->decimal('fuel_consumed_gallons', 10, 3)->default(0.000);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('ambulance_id');
            $table->index('driver_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('remissions');
    }
};
