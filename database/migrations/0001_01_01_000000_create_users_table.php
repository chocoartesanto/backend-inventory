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
        // CAMBIO: Schema::create() en lugar de Schema::table()
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username', 50)->unique();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password'); // Mantenemos 'password' (Laravel estándar)
            $table->unsignedBigInteger('role_id')->default(2);
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
            
            // Foreign key constraint (después de crear la tabla roles)
            // $table->foreign('role_id')->references('id')->on('roles');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};