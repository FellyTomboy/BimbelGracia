<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait SearchAndSort
{
    /**
     * Apply search filter on given columns.
     * Columns can be:
     *   - "table.column" (table-qualified, e.g., "teachers.name")
     *   - "relation.column" (relation name, e.g., "user.email" for belongsTo)
     * Relations are detected by trying to call the method on the model.
     */
    protected function applySearch(Builder $query, ?string $search, array $columns): Builder
    {
        if (! $search) {
            return $query;
        }

        $search = trim($search);

        return $query->where(function (Builder $q) use ($search, $columns) {
            $model = $q->getModel();

            foreach ($columns as $column) {
                if (str_contains($column, '.')) {
                    [$first, $second] = explode('.', $column, 2);
                    // Try to detect if it's a relation or table-qualified column
                    $isRelation = false;
                    try {
                        $relation = $model->{$first}();
                        $isRelation = $relation instanceof \Illuminate\Database\Eloquent\Relations\Relation;
                    } catch (\Throwable) {
                        $isRelation = false;
                    }

                    if ($isRelation && $first !== $model->getTable()) {
                        $q->orWhereHas($first, fn (Builder $sub) => $sub->where($second, 'LIKE', "%{$search}%"));
                    } else {
                        $q->orWhere($column, 'LIKE', "%{$search}%");
                    }
                } else {
                    $q->orWhere($column, 'LIKE', "%{$search}%");
                }
            }
        });
    }

    /**
     * Apply sorting with allowed columns.
     */
    protected function applySort(Builder $query, ?string $sort, ?string $direction, array $allowedColumns): Builder
    {
        $direction = $direction === 'asc' ? 'asc' : 'desc';

        if ($sort && in_array($sort, $allowedColumns, true)) {
            return $query->orderBy($sort, $direction);
        }

        return $query->latest();
    }

    /**
     * Extract search, sort, direction from request.
     *
     * @return array{search: string|null, sort: string|null, direction: string|null}
     */
    protected function getSearchSortParams(Request $request): array
    {
        return [
            'search' => $request->input('search'),
            'sort' => $request->input('sort'),
            'direction' => $request->input('direction'),
        ];
    }
}