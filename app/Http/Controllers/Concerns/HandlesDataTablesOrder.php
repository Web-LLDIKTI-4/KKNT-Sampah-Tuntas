<?php

namespace App\Http\Controllers\Concerns;

use App\Support\LikeKeyword;
use Illuminate\Contracts\Database\Query\Builder;
use Symfony\Component\HttpFoundation\InputBag;
use Yajra\DataTables\Facades\DataTables;
use Yajra\DataTables\QueryDataTable;

trait HandlesDataTablesOrder
{
    protected int $maxDataTablesColumns = 50;

    protected int $maxDataTablesSearchLength = 100;

    protected int $maxDataTablesSearchTerms = 10;

    protected int $maxDataTablesLength = 100;

    // Call after sanitizeDataTablesInput(); pass the endpoint whitelist so orders yajra will drop don't count
    protected function hasClientOrder(array $allowed = []): bool
    {
        $dtRequest = DataTables::getRequest();

        return collect($dtRequest->orderableColumns())
            ->contains(fn ($order) => ! $allowed || in_array($dtRequest->columnName($order['column']), $allowed, true));
    }

    // Yajra's multi-term search adds a clause set per space-separated term; cap length and term count
    protected function limitDataTablesSearch(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = mb_substr($value, 0, $this->maxDataTablesSearchLength);
        $terms = preg_split('/ +/', $value, -1, PREG_SPLIT_NO_EMPTY);

        return count($terms) > $this->maxDataTablesSearchTerms
            ? implode(' ', array_slice($terms, 0, $this->maxDataTablesSearchTerms))
            : $value;
    }

    // Normalize columns/search/order so yajra never hits TypeError or array-offset errors on malformed payloads
    protected function sanitizeDataTablesInput(): void
    {
        $request = request();
        // GET/HEAD endpoints only read the query string; a JSON body would bypass max_input_vars
        if ($request->isMethodSafe() && $request->isJson()) {
            $request->setJson(new InputBag());
        }
        // No endpoint defines searchPane(); yajra would hit undefined array keys
        foreach (['searchPanes', 'searchBuilder'] as $key) {
            $request->query->remove($key);
            $request->request->remove($key);
            if ($request->isJson()) {
                $request->json()->remove($key);
            }
        }
        $scalar = fn ($value) => is_scalar($value) ? (string) $value : null;
        // Always set start/length: yajra skips paging when either is missing or length is -1
        $start = $request->input('start');
        $length = $request->input('length');
        // Missing/non-numeric -> DataTables default 10; -1, 0 or oversized -> max
        $length = is_numeric($length) ? (int) $length : 10;
        $merge = [
            'start' => is_numeric($start) ? max(0, (int) $start) : 0,
            'length' => $length >= 1 && $length <= $this->maxDataTablesLength ? $length : $this->maxDataTablesLength,
        ];
        $named = [];

        if ($request->has('columns')) {
            $columns = [];
            $raw = $request->input('columns');
            foreach (is_array($raw) ? $raw : [] as $index => $column) {
                if (! is_int($index) || $index < 0 || ! is_array($column)) {
                    continue;
                }

                $name = $scalar($column['name'] ?? null);
                $data = $scalar($column['data'] ?? null);
                $resolved = $name !== null && $name !== '' ? $name : $data;
                // Same allowlist yajra enforces in getColumnName(); invalid names would throw there
                if ($resolved !== null && ! preg_match('/^[a-zA-Z0-9_.\-> ]+$/D', $resolved)) {
                    $name = $data = $resolved = null;
                }

                $clean = ['data' => $data, 'name' => $name];
                foreach (['searchable', 'orderable'] as $flag) {
                    if (array_key_exists($flag, $column)) {
                        $clean[$flag] = is_scalar($column[$flag]) ? $column[$flag] : 'false';
                    }
                }
                if (is_array($column['search'] ?? null)) {
                    $clean['search'] = ['value' => $this->limitDataTablesSearch($scalar($column['search']['value'] ?? null)), 'regex' => 'false'];
                }

                // Mirrors yajra isColumnOrderable(); orders on non-orderable columns are dropped
                $orderable = ($clean['orderable'] ?? 'true') == 'true';
                $columns[$index] = [$clean, $resolved !== null && $orderable];
            }

            // Reindex to 0..N-1 (yajra iterates by count) and cap the column count
            ksort($columns);
            $columns = array_slice($columns, 0, $this->maxDataTablesColumns, true);
            $merge['columns'] = [];
            foreach ($columns as $index => [$clean, $canOrder]) {
                if ($canOrder) {
                    $named[$index] = count($merge['columns']);
                }
                $merge['columns'][] = $clean;
            }
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $merge['search'] = ['value' => is_array($search) ? $this->limitDataTablesSearch($scalar($search['value'] ?? null)) : null, 'regex' => 'false'];
        }

        if ($request->has('order')) {
            $order = [];
            $raw = $request->input('order');
            foreach (is_array($raw) ? $raw : [] as $entry) {
                $index = is_array($entry) ? ($entry['column'] ?? null) : null;
                $dir = is_array($entry) ? ($entry['dir'] ?? null) : null;
                $index = is_int($index) || (is_string($index) && ctype_digit($index)) ? (int) $index : null;

                // Only keep entries pointing at an orderable column yajra can resolve to a name
                if ($index === null || ! isset($named[$index]) || ($dir !== null && ! is_scalar($dir))) {
                    continue;
                }

                $order[] = ['column' => $named[$index], 'dir' => $scalar($dir)];
            }
            $merge['order'] = $order;
        }

        $request->merge($merge);
    }

    // Escaped "contains" pattern for filterColumn closures
    protected function likeContains(string $keyword): string
    {
        return LikeKeyword::contains($keyword);
    }

    // Same filter/search/order/scope as the table, unpaged; tie-breaker keeps lazy() chunks stable
    protected function dataTablesExportQuery(QueryDataTable $dataTable, string $tieBreaker): Builder
    {
        return $dataTable->skipPaging()
            // Counts are never sent for an export; skip both count queries
            ->setTotalRecords(0)
            ->setFilteredRecords(0)
            ->getFilteredQuery()
            ->orderBy($tieBreaker);
    }
}
