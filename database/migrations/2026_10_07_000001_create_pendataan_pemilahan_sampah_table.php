<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pendataan_pemilahan_sampah', function (Blueprint $table) {
            $table->uuid('id_pendataan')->primary();
            $table->string('email');
            $table->date('tanggal');
            $table->string('nama_kepala_keluarga', 150);
            $table->string('alamat_rumah');
            $table->string('rt', 5);
            $table->string('rw', 5);
            $table->boolean('memilah');
            $table->decimal('organik_kg', 10, 2);
            $table->decimal('anorganik_kg', 10, 2);
            $table->decimal('residu_kg', 10, 2);
            $table->timestamps();
            $table->index(['email', 'tanggal']);
        });

        $kolomSampahLama = [
            'nama_kepala_keluarga', 'alamat_rumah', 'rt', 'rw',
            'memilah', 'organik_kg', 'anorganik_kg', 'residu_kg',
        ];
        $sumberBerisiDataSampah = collect($kolomSampahLama)
            ->every(fn (string $kolom) => Schema::hasColumn('logkegiatan', $kolom));

        if ($sumberBerisiDataSampah) {
            DB::table('logkegiatan')->orderBy('id_log')->chunk(500, function ($logs) {
                DB::table('pendataan_pemilahan_sampah')->insertOrIgnore(
                    $logs->map(fn ($log) => [
                        'id_pendataan' => $log->id_log,
                        'email' => $log->email,
                        'tanggal' => $log->tanggal,
                        'nama_kepala_keluarga' => $log->nama_kepala_keluarga,
                        'alamat_rumah' => $log->alamat_rumah,
                        'rt' => $log->rt,
                        'rw' => $log->rw,
                        'memilah' => $log->memilah,
                        'organik_kg' => $log->organik_kg,
                        'anorganik_kg' => $log->anorganik_kg,
                        'residu_kg' => $log->residu_kg,
                        'created_at' => $log->created_at,
                        'updated_at' => $log->updated_at,
                    ])->all()
                );
            });
        }

        $kolomLogHarianLama = [
            'deskripsi' => fn (Blueprint $table) => $table->longText('deskripsi')->nullable(),
            'volume' => fn (Blueprint $table) => $table->string('volume', 100)->nullable(),
            'satuan' => fn (Blueprint $table) => $table->text('satuan')->nullable(),
            'id_kpi' => fn (Blueprint $table) => $table->uuid('id_kpi')->nullable()->index(),
            'tautan' => fn (Blueprint $table) => $table->string('tautan')->nullable(),
        ];

        Schema::table('logkegiatan', function (Blueprint $table) use ($kolomLogHarianLama) {
            foreach ($kolomLogHarianLama as $kolom => $buatKolom) {
                if (! Schema::hasColumn('logkegiatan', $kolom)) {
                    $buatKolom($table);
                }
            }
        });

        if ($sumberBerisiDataSampah) {
            Schema::table('logkegiatan', function (Blueprint $table) {
                $table->string('nama_kepala_keluarga', 150)->nullable()->change();
                $table->string('alamat_rumah')->nullable()->change();
                $table->string('rt', 5)->nullable()->change();
                $table->string('rw', 5)->nullable()->change();
                $table->boolean('memilah')->nullable()->change();
                $table->decimal('organik_kg', 10, 2)->nullable()->change();
                $table->decimal('anorganik_kg', 10, 2)->nullable()->change();
                $table->decimal('residu_kg', 10, 2)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pendataan_pemilahan_sampah');
    }
};
