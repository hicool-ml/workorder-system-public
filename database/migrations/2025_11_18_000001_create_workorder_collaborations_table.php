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
        Schema::create('workorder_collaborations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workorder_id')->constrained()->onDelete('cascade');
            $table->foreignId('inviter_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('collaborator_id')->constrained('users')->onDelete('cascade');
            $table->text('invitation_reason')->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->timestamp('accepted_at')->nullable();
            $table->text('response_note')->nullable();
            $table->timestamps();
            
            $table->index(['workorder_id', 'collaborator_id']);
            $table->index(['workorder_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workorder_collaborations');
    }
};