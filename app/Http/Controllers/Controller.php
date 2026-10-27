<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Resolve the "per page" value for a paginated listing, constrained to the
     * sizes offered in the UI's rows-per-page dropdown.
     */
    protected function perPage(Request $request, int $default = 10): int
    {
        $allowed = [5, 10, 20, 50, 100];
        $perPage = $request->integer('per_page', $default);

        return in_array($perPage, $allowed, true) ? $perPage : $default;
    }

    /**
     * Resolve the "sort by" column and direction for a listing from the request,
     * constrained to a whitelist of sortable columns. Returns null when no valid
     * sort was requested, so the caller can fall back to its own default order.
     *
     * @param  list<string>  $sortable
     * @return array{0: string, 1: 'asc'|'desc'}|null
     */
    protected function resolveSort(Request $request, array $sortable): ?array
    {
        $sort = $request->string('sort')->toString();

        if ($sort === '' || ! in_array($sort, $sortable, true)) {
            return null;
        }

        $direction = $request->string('direction')->toString() === 'desc' ? 'desc' : 'asc';

        return [$sort, $direction];
    }
}
