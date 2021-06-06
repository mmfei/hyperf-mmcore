<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateUserAuth extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_auth', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->primary();
            $table->string('name', 256)->default('')->unique();
            $table->string('token', 256)->default('')->unique();
            $table->string('email', 256)->default('')->unique();
            $table->string('phone', 256)->default('')->unique();
            $table->string('password', 256)->default('');
            $table->string('password_salt', 256)->default('');
            $table->timestamps();
        });
        Schema::create('user', function (Blueprint $table) {
            $table->bigIncrements('user_id');
            $table->string('name', 256)->default('')->unique();
            $table->string('nickname', 256)->default('')->unique();
            $table->string('image_path', 256)->default('');
            $table->timestamps();
            $table->unique(['user_id'], 'u_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user');
        Schema::dropIfExists('user_auth');
    }
}
