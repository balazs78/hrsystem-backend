<?php

declare(strict_types=1);

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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('gender');
            $table->string('email')->unique();
            $table->string('phone');
            $table->string('address');
            $table->date('birthdate');
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->foreignId('position_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->date('hired_at');
            $table->foreignId('manager_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('status');
            $table->date('left_at')->nullable();
            $table->string('profile_image')->nullable();
            $table->date('contract_end_date')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('department_id');
            $table->index('position_id');
            $table->index('manager_id');
            $table->index('status');
            $table->index('contract_end_date');
            $table->index('hired_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
