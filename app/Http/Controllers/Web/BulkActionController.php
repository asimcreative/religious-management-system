<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataTransfer\BulkActionRequest;
use App\Services\DataTransfer\BulkActionService;
use App\Support\DataTransfer\BulkAction;
use App\Support\DataTransfer\Contracts\ResourceDefinitionContract;
use Illuminate\Http\RedirectResponse;

/**
 * Bulk delete and bulk status change, for every module.
 *
 * Like the transfer controller, the module arrives as a route parameter, so
 * this file does not grow when a module is added.
 */
class BulkActionController extends Controller
{
    public function __construct(
        private readonly BulkActionService $bulk,
    ) {}

    public function __invoke(BulkActionRequest $request, ResourceDefinitionContract $resource): RedirectResponse
    {
        $user = $request->user();

        $action = $request->action();

        $result = match ($action) {
            BulkAction::Delete => $this->bulk->delete($resource, $request->ids(), $user),
            BulkAction::Status => $this->bulk->changeStatus(
                $resource,
                $request->ids(),
                (string) $request->validated('status'),
                $user,
            ),
        };

        $label = $action === BulkAction::Delete
            ? __('data_transfer.bulk.deleted')
            : __('data_transfer.bulk.updated');

        // Back to the list the user was on, filters and page intact.
        $redirect = back()->with(
            $result->didNothing() ? 'error' : 'success',
            $result->message($label),
        );

        if ($result->reasons !== []) {
            $redirect->with('bulk_reasons', $result->reasons);
        }

        return $redirect;
    }
}
