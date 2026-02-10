<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('device_pcb_links', function (Blueprint $table): void {
            $table->id();
            $table->char('body_sn', 8);
            $table->char('pcb_sn', 8);
            $table->timestamp('linked_at');
            $table->timestamp('unlinked_at')->nullable();
            $table->foreignId('linked_by_user_id')->constrained('users');
            $table->foreignId('unlinked_by_user_id')->nullable()->constrained('users');
            $table->string('unlink_reason', 255)->nullable();

            $table->index(['body_sn', 'unlinked_at']);
            $table->index(['pcb_sn', 'unlinked_at']);
            $table->index('linked_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_pcb_links');
    }
};
