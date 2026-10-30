<?php

namespace Tests\Feature;

use App\Http\Controllers\Concerns\HandlesDataTablesOrder;
use App\Models\CapaianKegiatan;
use App\Models\Mahasiswa;
use App\Models\Pjdesa;
use App\Models\Satuanpendidikan;
use App\Models\User;
use App\Support\LikeKeyword;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use LogicException;
use Tests\TestCase;

// Regression DoS DataTables B-1/B-2: cap klausa AND, dedup kolom, helper whereRelatedLike
class HandlesDataTablesOrderTest extends TestCase
{
    use RefreshDatabase;

    private function helper(): object
    {
        return new class
        {
            use HandlesDataTablesOrder;

            public function sanitize(): void
            {
                $this->sanitizeDataTablesInput();
            }

            public function related(...$args)
            {
                return $this->whereRelatedLike(...$args);
            }
        };
    }

    private function sanitized(array $query): Request
    {
        $this->app->instance('request', Request::create('/dt', 'GET', $query));
        $this->helper()->sanitize();

        return request();
    }

    private function terms(Request $request): array
    {
        return preg_split('/ +/', (string) $request->input('search.value'), -1, PREG_SPLIT_NO_EMPTY);
    }

    private function tenTerms(): string
    {
        return implode(' ', array_map(fn ($i) => "t$i", range(1, 10)));
    }

    public function test_bypass_payload_blacklisted_column_still_caps_global_terms(): void
    {
        // Kolom kedua di luar whitelist/blacklisted (dibuang yajra) tidak boleh menaikkan jumlah term
        $request = $this->sanitized([
            'columns' => [
                ['data' => 'pjdesa', 'searchable' => 'true'],
                ['data' => 'password', 'searchable' => 'true'],
            ],
            'search' => ['value' => $this->tenTerms()],
        ]);

        $this->assertSame(['t1', 't2', 't3', 't4', 't5'], $this->terms($request));
    }

    public function test_column_searches_capped_at_three_and_reduce_global_terms(): void
    {
        $columns = [];
        foreach (['a', 'b', 'c', 'd', 'e'] as $i => $col) {
            $columns[] = ['data' => $col, 'searchable' => 'true', 'search' => ['value' => "cs$i"]];
        }
        $request = $this->sanitized(['columns' => $columns, 'search' => ['value' => $this->tenTerms()]]);

        $values = array_column(array_column($request->input('columns'), 'search'), 'value');
        $this->assertSame(['cs0', 'cs1', 'cs2', null, null], $values);
        // 5 - 3 column search = 2 term global
        $this->assertSame(['t1', 't2'], $this->terms($request));

        // Satu column search -> 4 term
        $request = $this->sanitized([
            'columns' => [['data' => 'a', 'searchable' => 'true', 'search' => ['value' => 'x']]],
            'search' => ['value' => $this->tenTerms()],
        ]);
        $this->assertSame(['t1', 't2', 't3', 't4'], $this->terms($request));
    }

    public function test_duplicate_columns_are_deduped_and_order_follows_first(): void
    {
        $columns = [['data' => 'bulan', 'orderable' => 'true']];
        foreach (range(1, 49) as $i) {
            $columns[] = ['data' => 'pjdesa', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => "dup$i"]];
        }
        $request = $this->sanitized([
            'columns' => $columns,
            'search' => ['value' => $this->tenTerms()],
            'order' => [['column' => 30, 'dir' => 'desc']],
        ]);

        $this->assertSame(['bulan', 'pjdesa'], array_column($request->input('columns'), 'data'));
        // Hanya duplikat pertama yang tersisa: satu column search aktif
        $this->assertSame('dup1', $request->input('columns.1.search.value'));
        $this->assertCount(4, $this->terms($request));
        $this->assertSame([['column' => 1, 'dir' => 'desc']], $request->input('order'));
    }

    public function test_where_related_like_matches_where_has_with_escaped_wildcards(): void
    {
        $ids = [];
        foreach (['xa_b%y', 'aXb%', 'a_bZ', 'lain'] as $nama) {
            $mhs = Mahasiswa::factory()->create(['nama' => $nama]);
            Pjdesa::create(['email' => $mhs->email, 'id_desa' => \App\Models\Desa::factory()->create()->id_desa]);
            $ids[$nama] = CapaianKegiatan::factory()->create(['email' => $mhs->email])->id_capaian;
        }
        CapaianKegiatan::factory()->create(['email' => 'tanpa-pjdesa@pps.test']);

        foreach (['a_b%' => ['xa_b%y'], 'b%' => ['xa_b%y', 'aXb%'], '_' => ['xa_b%y', 'a_bZ']] as $keyword => $expected) {
            $helper = $this->helper()->related(CapaianKegiatan::query(), 'pjdesa.mahasiswa', 'nama', $keyword)
                ->orderBy('id_capaian')->pluck('id_capaian')->all();
            $legacy = CapaianKegiatan::whereHas('pjdesa.mahasiswa', fn ($q) => $q->where('nama', 'like', LikeKeyword::contains($keyword)))
                ->orderBy('id_capaian')->pluck('id_capaian')->all();

            $this->assertSame($legacy, $helper, $keyword);
            $this->assertSame(array_values(array_intersect_key($ids, array_flip($expected))), $helper, $keyword);
        }

        // HasOne satu hop (Mahasiswa -> sp) juga setara
        $sp = Satuanpendidikan::factory()->create(['nm_lemb' => 'Univ 100%_Uji']);
        Mahasiswa::factory()->create(['kodept' => $sp->npsn]);
        $helper = $this->helper()->related(Mahasiswa::query(), 'sp', 'nm_lemb', '100%_')->pluck('id_mahasiswa')->sort()->values()->all();
        $legacy = Mahasiswa::whereHas('sp', fn ($q) => $q->where('nm_lemb', 'like', LikeKeyword::contains('100%_')))->pluck('id_mahasiswa')->sort()->values()->all();
        $this->assertCount(1, $helper);
        $this->assertSame($legacy, $helper);

        $sql = $this->helper()->related(CapaianKegiatan::query(), 'pjdesa.mahasiswa', 'nama', 'x')->toSql();
        $this->assertSame(2, substr_count($sql, 'NO_SEMIJOIN'));
        $this->assertStringNotContainsStringIgnoringCase('exists', $sql);
    }

    public function test_where_related_like_rejects_morph_relations(): void
    {
        $model = new class extends Model
        {
            protected $table = 'capaian_kegiatan';

            public function owner()
            {
                return $this->morphTo();
            }

            public function users()
            {
                return $this->morphMany(User::class, 'owner');
            }
        };

        foreach (['owner', 'users'] as $relation) {
            try {
                $this->helper()->related($model->newQuery(), $relation, 'name', 'x');
                $this->fail("$relation harus ditolak");
            } catch (LogicException $e) {
                $this->assertStringContainsString("[$relation]", $e->getMessage());
            }
        }
    }
}
