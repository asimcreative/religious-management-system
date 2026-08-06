<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Company settings.
 *
 * Not a CRUD module: the keys are fixed by SettingService::catalogue(), and
 * this screen only edits their values. Anything not in that catalogue is
 * ignored rather than stored, so the screen cannot create configuration that
 * nothing reads.
 */
class SettingController extends Controller
{
    public function __construct(
        private readonly SettingService $service,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('settings.view');

        return view('settings.index', [
            'values' => $this->service->all((int) $request->user()->company_id),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('settings.update');

        $rules = [];

        foreach (SettingService::catalogue() as $key => $meta) {
            $rules[$key] = $meta['rules'];
        }

        // Only catalogue keys are validated, and only validated keys are
        // written — a stray field in the request reaches nothing.
        $this->service->update($request->user(), $request->validate($rules));

        return redirect()
            ->route('settings.index')
            ->with('success', __('settings.saved'));
    }
}
