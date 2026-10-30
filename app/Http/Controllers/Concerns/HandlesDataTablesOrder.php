<?php

namespace App\Http\Controllers\Concerns;

use App\Support\LikeKeyword;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use LogicException;
use Symfony\Component\HttpFoundation\InputBag;
use Yajra\DataTables\Facades\DataTables;
use Yajra\DataTables\QueryDataTable;

trait HandlesDataTablesOrder
{
    protected int $maxDataTablesColumns = 50;

    protected int $maxDataTablesSearchLength = 100;

    protected int $maxDataTablesSearchTerms = 10;

    protected int $maxDataTablesLength = 100;

    // ANDed search clauses: each column search plus each global term (yajra ANDs one group per term).
    // Unconditional: yajra may drop client columns, leaving one relation EXISTS per term; MySQL/MariaDB
    // flatten ANDed relation subqueries into one join whose planning time grows factorially.
    protected int $maxDataTablesAndedSearches = 5;

    // Keep below $maxDataTablesAndedSearches so at least one global term survives
    protected int $maxDataTablesColumnSearches = 3;

    // Page size cap for tables offering 500 / "All" (-1)
    protected int $maxDataTablesLargeLength = 500;

    // Call after sanitizeDataTablesInput(); pass the endpoint whitelist so orders yajra will drop don't count
    protected function hasClientOrder(array $allowed = []): bool
    {
        $dtRequest = DataTables::getRequest();

        return collect($dtRequest->orderableColumns())
            ->contains(fn ($order) => ! $allowed || in_array($dtRequest->columnName($order['column']), $allowed, true));
    }

    // Yajra's multi-term search adds a clause set per space-separated term; cap length and term count
    protected function limitDataTablesSearch(?string $value, ?int $maxTerms = null): ?string
    {
        if ($value === null) {
            return null;
        }

        $maxTerms ??= $this->maxDataTablesSearchTerms;
        $value = mb_substr($value, 0, $this->maxDataTablesSearchLength);
        $terms = preg_split('/ +/', $value, -1, PREG_SPLIT_NO_EMPTY);

        return count($terms) > $maxTerms
            ? implode(' ', array_slice($terms, 0, $maxTerms))
            : $value;
    }

    // Normalize columns/search/order so yajra never hits TypeError or array-offset errors on malformed payloads
    // $largePages: allow up to $maxDataTablesLargeLength and -1 ("All", no paging)
    protected function sanitizeDataTablesInput(bool $largePages = false): void
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
        $maxLength = $largePages ? $this->maxDataTablesLargeLength : $this->maxDataTablesLength;
        $merge = [
            'start' => is_numeric($start) ? max(0, (int) $start) : 0,
            'length' => ($largePages && $length === -1) || ($length >= 1 && $length <= $maxLength) ? $length : $maxLength,
        ];
        $named = [];
        $columnSearches = 0;

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
                $columns[$index] = [$clean, $resolved, $resolved !== null && $orderable];
            }

            // Reindex to 0..N-1 (yajra iterates by count) and cap the column count
            ksort($columns);
            $columns = array_slice($columns, 0, $this->maxDataTablesColumns, true);
            $merge['columns'] = [];
            $first = [];
            foreach ($columns as $index => [$clean, $resolved, $canOrder]) {
                // Duplicate name/data repeats the same filter; drop it, its order follows the first occurrence
                if ($resolved !== null && isset($first[$resolved])) {
                    if (isset($named[$first[$resolved]])) {
                        $named[$index] = $named[$first[$resolved]];
                    }

                    continue;
                }
                if ($resolved !== null) {
                    $first[$resolved] = $index;
                }
                // Mirrors yajra isColumnSearchable()
                if ($resolved !== null && in_array($clean['searchable'] ?? 'true', ['true', true], true)) {
                    $hasColumnSearch = ($clean['search']['value'] ?? '') !== '';
                    if ($hasColumnSearch && $columnSearches >= $this->maxDataTablesColumnSearches) {
                        $clean['search']['value'] = null;
                    } elseif ($hasColumnSearch) {
                        $columnSearches++;
                    }
                }
                if ($canOrder) {
                    $named[$index] = count($merge['columns']);
                }
                $merge['columns'][] = $clean;
            }
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $maxTerms = $this->maxDataTablesAndedSearches - $columnSearches;
            $merge['search'] = ['value' => is_array($search) ? $this->limitDataTablesSearch($scalar($search['value'] ?? null), $maxTerms) : null, 'regex' => 'false'];
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

    // Same rows as whereHas($relation, $column like %keyword%) but as uncorrelated IN subqueries;
    // NO_SEMIJOIN stops MySQL flattening stacked search clauses into one join (factorial planning)
    protected function whereRelatedLike(EloquentBuilder $query, string $relation, mixed $column, string $keyword): EloquentBuilder
    {
        [$name, $nested] = array_pad(explode('.', $relation, 2), 2, null);
        $related = Relation::noConstraints(fn () => $query->getModel()->{$name}());
        $sub = $related->getQuery();
        $nested === null
            ? $sub->where($column, 'like', $this->likeContains($keyword))
            : $this->whereRelatedLike($sub, $nested, $column, $keyword);
        // Other relation types fail loudly instead of filtering wrong (morph would lose its type constraint)
        [$parentKey, $relatedKey] = match (true) {
            $related instanceof MorphTo, $related instanceof MorphOneOrMany => throw new LogicException("Morph relation [$name] is not supported"),
            $related instanceof BelongsTo => [$related->getQualifiedForeignKeyName(), $related->getQualifiedOwnerKeyName()],
            $related instanceof HasOneOrMany => [$related->getQualifiedParentKeyName(), $related->getQualifiedForeignKeyName()],
        };

        return $query->whereIn($parentKey, $sub->selectRaw('/*+ NO_SEMIJOIN() */ '.$sub->getGrammar()->wrap($relatedKey)));
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

    // Maps export params (q, order_col, order_dir) onto DataTables input so the table's own search/order applies
    protected function useExportAsDataTablesInput(array $params, array $columns): void
    {
        $orderIndex = array_search($params['order_col'] ?? null, $columns, true);

        request()->merge([
            'columns' => array_map(fn ($column) => ['data' => $column, 'name' => $column, 'searchable' => 'true', 'orderable' => 'true'], $columns),
            'search' => ['value' => $params['q'] ?? null],
            'order' => $orderIndex === false ? [] : [['column' => $orderIndex, 'dir' => $params['order_dir'] ?? 'asc']],
        ]);
    }

    // Same key tie-breaker as dataTablesExportQuery(), appended after yajra's ordering so table pages match the export
    protected function makeWithTieBreaker(QueryDataTable $dataTable, string $tieBreaker): JsonResponse
    {
        $dataTable->prepareQuery()->getQuery()->orderBy($tieBreaker);

        return $dataTable->make(true);
    }
}
