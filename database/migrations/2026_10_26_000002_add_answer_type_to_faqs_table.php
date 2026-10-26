<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            // Nilai: text | image | both, divalidasi di FormRequest
            $table->string('answer_type', 10)->default('text')->after('jawaban');
            $table->string('answer_image', 255)->nullable()->after('answer_type');
            $table->text('jawaban')->nullable()->comment('Plain text, bukan HTML')->change();
        });
    }

    public function down(): void
    {
        $nullCount = DB::table('faqs')->whereNull('jawaban')->count();

        if ($nullCount > 0) {
            throw new RuntimeException(
                "Rollback dibatalkan: {$nullCount} baris faqs masih memiliki jawaban NULL. Isi jawaban baris tersebut terlebih dahulu."
            );
        }

        Schema::table('faqs', function (Blueprint $table) {
            $table->text('jawaban')->nullable(false)->comment('Plain text, bukan HTML')->change();
            $table->dropColumn(['answer_type', 'answer_image']);
        });
    }
};
