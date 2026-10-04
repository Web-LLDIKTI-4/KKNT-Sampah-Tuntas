<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Satu capaian per email per bulan; duplikat lama (bukan yang terbaru) dipindah ke tabel backup agar down() bisa memulihkan
return new class extends Migration
{
    private const COLUMNS = [
        'id_capaian', 'id_kpi', 'id_pjdesa', 'email', 'bulan', 'status_capaian',
        'tautan', 'permasalahan', 'solusi', 'kendala', 'created_at', 'updated_at',
    ];

    public function up(): void
    {
        Schema::table('kpi_capaian', function (Blueprint $table) {
            $table->date('bulan')->nullable()->after('email');
        });

        DB::table('kpi_capaian')->update([
            'bulan' => DB::raw("DATE_FORMAT(COALESCE(created_at, updated_at, CURRENT_TIMESTAMP), '%Y-%m-01')"),
        ]);

        Schema::create('kpi_capaian_dedup_backup', function (Blueprint $table) {
            $table->uuid('id_capaian')->primary();
            $table->uuid('id_kpi')->nullable();
            $table->uuid('id_pjdesa')->nullable();
            $table->string('email', 200)->nullable();
            $table->date('bulan')->nullable();
            $table->string('status_capaian', 5)->nullable();
            $table->text('tautan')->nullable();
            $table->text('permasalahan')->nullable();
            $table->text('solusi')->nullable();
            $table->text('kendala')->nullable();
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
        });

        // Ada baris lebih baru di (email, bulan) yang sama: created_at lebih besar, atau sama tapi id lebih besar
        $ids = DB::table('kpi_capaian as c')
            ->whereExists(fn (Builder $q) => $q->from('kpi_capaian as n')
                ->whereColumn('n.email', 'c.email')
                ->whereColumn('n.bulan', 'c.bulan')
                ->where(fn (Builder $w) => $w->whereColumn('n.created_at', '>', 'c.created_at')
                    ->orWhere(fn (Builder $e) => $e->whereColumn('n.created_at', 'c.created_at')
                        ->whereColumn('n.id_capaian', '>', 'c.id_capaian'))))
            ->pluck('c.id_capaian');

        foreach ($ids->chunk(500) as $chunk) {
            DB::table('kpi_capaian_dedup_backup')->insertUsing(
                [...self::COLUMNS, 'deleted_at'],
                DB::table('kpi_capaian')->select([...self::COLUMNS, DB::raw('CURRENT_TIMESTAMP')])->whereIn('id_capaian', $chunk)
            );
            DB::table('kpi_capaian')->whereIn('id_capaian', $chunk)->delete();
        }

        Schema::table('kpi_capaian', function (Blueprint $table) {
            $table->date('bulan')->nullable(false)->change();
            $table->unique(['email', 'bulan']);
        });
    }

    public function down(): void
    {
        Schema::table('kpi_capaian', function (Blueprint $table) {
            $table->dropUnique(['email', 'bulan']);
        });

        DB::table('kpi_capaian')->insertUsing(
            self::COLUMNS,
            DB::table('kpi_capaian_dedup_backup')->select(self::COLUMNS)
        );

        Schema::dropIfExists('kpi_capaian_dedup_backup');

        Schema::table('kpi_capaian', function (Blueprint $table) {
            $table->dropColumn('bulan');
        });
    }
};
