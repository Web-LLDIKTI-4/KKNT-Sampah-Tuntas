<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'dpl_mentoring';

    private const INDEX = 'dpl_mentoring_email_mahasiswa_unique';

    public function up(): void
    {
        if (! Schema::hasTable(self::TABLE) || Schema::hasIndex(self::TABLE, self::INDEX)) {
            return;
        }

        $duplicates = DB::table(self::TABLE)
            ->select('email_mahasiswa')
            ->whereNotNull('email_mahasiswa')
            ->groupBy('email_mahasiswa')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        if ($duplicates > 0) {
            throw new RuntimeException(sprintf(
                'Tidak bisa menambah unique index %s: ditemukan %d email_mahasiswa duplikat di tabel %s. '
                .'Satu mahasiswa hanya boleh punya satu DPL. Selesaikan duplikat secara manual terlebih dahulu, lalu jalankan ulang migration.',
                self::INDEX,
                $duplicates,
                self::TABLE
            ));
        }

        Schema::table(self::TABLE, function (Blueprint $table) {
            $table->unique('email_mahasiswa', self::INDEX);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::TABLE) || ! Schema::hasIndex(self::TABLE, self::INDEX)) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table) {
            $table->dropUnique(self::INDEX);
        });
    }
};
