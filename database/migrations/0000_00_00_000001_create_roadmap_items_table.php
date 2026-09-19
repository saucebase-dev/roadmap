<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roadmap_items', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('status')->index();
            $table->string('type');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('official_response')->nullable();
            $table->timestamp('official_response_at')->nullable();
            $table->foreignId('merged_into_id')->nullable()->constrained('roadmap_items')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roadmap_items');
    }
};
