<?php

namespace App\Http\Controllers\Web;

use App\Exports\AnalyticsExport;
use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use App\Support\Analytics\AbstractAnalyticsDefinition;
use App\Support\Analytics\AnalyticsRegistry;
use App\Support\DataTransfer\ExportFormat;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * The report builder.
 *
 * One screen for every dataset and every breakdown. What varies is the
 * definition it is handed, so a new way of slicing the numbers reaches the user
 * without this class changing at all.
 */
class AnalyticsController extends Controller
{
    public function __construct(
        private readonly AnalyticsRegistry $registry,
        private readonly AnalyticsService $analytics,
    ) {}

    /** The list of datasets the user may report on. */
    public function index(): View
    {
        $datasets = $this->allowedDatasets();

        abort_if($datasets === [], 403);

        return view('analytics.index', ['datasets' => $datasets]);
    }

    public function show(Request $request, AbstractAnalyticsDefinition $dataset): View
    {
        $this->authorize($dataset->permission());

        $result = $this->analytics->run(
            $dataset,
            $this->filterValues($request, $dataset),
            $request->query('group_by'),
            $request->query('then_by'),
        );

        $filters = $this->filterValues($request, $dataset);

        return view('analytics.show', [
            'definition' => $dataset,
            'datasets' => $this->allowedDatasets(),
            'result' => $result,
            'filters' => $filters,
            'active' => $this->analytics->activeFilters($dataset, $filters),
            // Passed rather than read from the shell composer: that one shares
            // with layouts.app only, so a child view cannot see it.
            'companyName' => $request->user()?->company?->company_name,
            'generatedAt' => Carbon::now(),
        ]);
    }

    /**
     * The same breakdown as a file.
     *
     * Exports what is on screen — same breakdown, same filters — rather than a
     * separate query that could answer a slightly different question.
     */
    public function export(
        Request $request,
        AbstractAnalyticsDefinition $dataset,
        AnalyticsExport $export,
    ): BinaryFileResponse|Response {
        $this->authorize($dataset->permission());

        $format = ExportFormat::tryFrom((string) $request->query('format', 'xlsx')) ?? ExportFormat::Xlsx;
        $filters = $this->filterValues($request, $dataset);

        $result = $this->analytics->run(
            $dataset,
            $filters,
            $request->query('group_by'),
            $request->query('then_by'),
        );

        return $export->download(
            $dataset,
            $result,
            $this->analytics->activeFilters($dataset, $filters),
            $format,
        );
    }

    /**
     * Only the keys this dataset declares, so a hand-edited URL cannot smuggle
     * anything past the definition.
     *
     * @return array<string, string>
     */
    private function filterValues(Request $request, AbstractAnalyticsDefinition $dataset): array
    {
        /** @var array<string, string> */
        return array_filter(
            $request->only($dataset->filterKeys()),
            static fn (mixed $value): bool => is_string($value) && $value !== '',
        );
    }

    /** @return list<AbstractAnalyticsDefinition> */
    private function allowedDatasets(): array
    {
        return $this->registry->visible(
            static fn (AbstractAnalyticsDefinition $definition): bool => Gate::allows($definition->permission()),
        );
    }
}
