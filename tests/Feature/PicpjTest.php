<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PicpjTest extends TestCase
{
    use RefreshDatabase;

    private const LIST_URL = 'picpj/listdataserver?draw=1&start=0&length=10'
        .'&columns[0][data]=kodept&columns[0][name]=kodept&columns[0][searchable]=true&columns[0][orderable]=true';

    private const AJAX = ['X-Requested-With' => 'XMLHttpRequest'];

    public function test_listdataserver_malformed_order_does_not_500(): void
    {
        User::factory()->role('pt')->create(['email' => '002002']);
        User::factory()->role('pt')->create(['email' => '001001']);

        // >50 columns: extras are dropped, order pointing at a dropped column is ignored
        $many = implode('&', array_map(fn ($i) => "columns[$i][data]=x$i", range(0, 59))).'&order[0][column]=55';

        foreach (['kepala', 'pemda'] as $role) {
            $this->loginAs($role);

            // Order rusak dibuang; urutan default (kode PT) tetap berlaku
            foreach ([
                'order=x',
                'order[0][dir][]=x',
                'order[0][column]=1&columns[1]=x',
                'columns=x',
                'columns[0][search]=x',
                'columns[0][data][]=x&columns[0][searchable]=true',
                'columns[0][data]=nama&columns[0][searchable]=true&columns[0][search][value][]=x',
                'columns[x][data]=nama&order[0][column]=x',
                'columns[0][data]=a;b&columns[0][name]=a;b&columns[0][orderable]=true&order[0][column]=0&order[0][dir]=asc',
                'search=x',
                'search[value][]=x',
                'columns[-1][data]=kodept&columns[-1][orderable]=true&order[0][column]=-1&order[0][dir]=asc',
                'columns[99999][data]=kodept&columns[99999][orderable]=true&order[0][column]=99999&order[0][dir]=asc',
                'columns[99999999999999999999][data]=kodept&order[0][column]=99999999999999999999',
                'columns[7][data]=kodept&columns[7][orderable]=true&columns[3][data]=kodept&order[0][column]=7&order[0][dir]=asc',
                'columns[0][search][regex]=true&search[regex]=true',
                'columns[0][data]=kodept%0A&columns[0][name]=kodept%0A&columns[0][orderable]=true&order[0][column]=0&order[0][dir]=asc',
                'searchPanes[kodept][]=x',
                $many,
            ] as $order) {
                $this->getJson(self::LIST_URL.'&'.$order, self::AJAX)
                    ->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('data.0.kodept', '001001');
            }
        }

        // 2500-term search is capped to 100 chars / 10 terms
        $start = microtime(true);
        $this->getJson(self::LIST_URL.'&columns[0][search][value]='.str_repeat('a+', 2500).'&search[value]='.str_repeat('a+', 2500), self::AJAX)->assertOk();
        $this->assertLessThan(2, microtime(true) - $start);

        // regex=true is forced off: '[' becomes a LIKE match instead of a REGEXP error
        $this->getJson(self::LIST_URL.'&columns[0][search][value]=%5B&columns[0][search][regex]=true', self::AJAX)->assertOk()->assertJsonCount(0, 'data');
    }
}
