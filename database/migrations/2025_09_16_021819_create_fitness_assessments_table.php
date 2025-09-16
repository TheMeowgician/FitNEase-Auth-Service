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
        Schema::create('fitness_assessments', function (Blueprint $table) {
            $table->id('assessment_id');
            $table->unsignedBigInteger('user_id');
            $table->string('assessment_type', 100);
            $table->json('assessment_data');
            $table->decimal('score', 5, 2)->nullable();
            $table->timestamp('assessment_date')->default(now());
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('created_by')->references('user_id')->on('users')->onDelete('set null');
            $table->index(['user_id', 'assessment_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fitness_assessments');
    }
};
