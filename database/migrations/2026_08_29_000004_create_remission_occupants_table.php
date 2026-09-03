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
        Schema::create('remission_occupants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('remission_id')->constrained('remissions')->onDelete('cascade');
            $table->string('name');
            $table->string('identification')->nullable();
            $table->enum('role', ['doctor', 'nurse', 'paramedic', 'companion', 'other']);
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
        Schema::dropIfExists('remission_occupants');
    }
};
