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
        Schema::create('ambulances', function (Blueprint $table) {
            $table->id();
            $table->string('plate', 10)->unique();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->decimal('km_per_gallon', 8, 2);
            $table->date('soat_expires_at');
            $table->date('tech_review_expires_at');
            $table->enum('status', ['available', 'in_service', 'maintenance'])->default('available');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ambulances');
    }
};
