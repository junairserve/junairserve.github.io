<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inspections', function (Blueprint $table): void {
            $table->id();
            $table->char('body_sn', 8);
            $table->string('cert_no', 100);
            $table->date('date');
            $table->string('place', 255);
            $table->foreignId('responsible_user_id')->constrained('users');
            $table->string('method', 255);
            $table->enum('result', ['PASS', 'FAIL']);
            $table->timestamps();

            $table->index(['body_sn', 'date']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspections');
    }
};
