<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SavedFilter;
use App\Support\DataTransfer\Contracts\ResourceDefinitionContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Named filter sets, saved per user.
 *
 * Personal rather than shared: a saved filter is a bookmark, and one person's
 * shortcut is rarely another's. Only the filter keys the module declares are
 * stored, so nothing arbitrary from the query string is kept or replayed.
 */
class SavedFilterController extends Controller
{
    public function store(Request $request, ResourceDefinitionContract $resource): RedirectResponse
    {
        $permission = $resource->permissionFor('view');

        if ($permission !== null) {
            $this->authorize($permission);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'query' => ['nullable', 'string', 'max:2000'],
        ]);

        parse_str((string) ($validated['query'] ?? ''), $parsed);

        $filters = array_intersect_key(
            // parse_str yields strings and arrays, never null.
            array_filter($parsed, static fn (array|string $value): bool => $value !== '' && $value !== []),
            array_flip($resource->filterKeys()),
        );

        // updateOrCreate against the unique (user, resource, name) key, so
        // saving over a name replaces it instead of quietly duplicating.
        SavedFilter::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'resource_key' => $resource->key(),
                'name' => $validated['name'],
            ],
            [
                'company_id' => $request->user()->company_id,
                'query' => $filters,
            ],
        );

        return back()->with('success', __('data_transfer.filters_saved', ['name' => $validated['name']]));
    }

    public function destroy(SavedFilter $savedFilter): RedirectResponse
    {
        // Tenant-scoped by the model; the policy adds the personal boundary.
        $this->authorize('delete', $savedFilter);

        $savedFilter->delete();

        return back()->with('success', __('data_transfer.filters_deleted'));
    }
}
