<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('serial_pool', function (Blueprint $table): void {
            $table->id();
            $table->enum('type', ['BODY', 'PCB']);
            $table->char('sn', 8);
            $table->enum('status', ['UNUSED', 'LINKED', 'INSPECTED', 'VOID'])->default('UNUSED');
            $table->string('lot_name', 100)->nullable();
            $table->timestamps();

            $table->unique(['type', 'sn']);
            $table->index(['type', 'status']);
            $table->index(['type', 'sn']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('serial_pool');
    }
};
