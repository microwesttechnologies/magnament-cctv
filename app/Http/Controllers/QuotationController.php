<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Order\UseCases\ConvertApprovedQuotationToOrderUseCase;
use App\Application\Quotation\DTOs\CreateQuotationInput;
use App\Application\Quotation\DTOs\QuotationLineInput;
use App\Application\Quotation\UseCases\CreateQuotationUseCase;
use App\Application\Quotation\UseCases\TransitionQuotationStatusUseCase;
use App\Application\Quotation\UseCases\UpdateQuotationLinesUseCase;
use App\Domain\Quotation\Enums\QuotationStatus;
use App\Domain\Quotation\Exceptions\InvalidQuotationTransition;
use App\Domain\Quotation\Exceptions\QuotationNotConvertible;
use App\Domain\Quotation\Exceptions\QuotationNotFoundException;
use App\Domain\Quotation\Ports\QuotationPdfGeneratorInterface;
use App\Domain\Quotation\Repositories\QuotationRepositoryInterface;
use App\Domain\Quotation\ValueObjects\QuotationId;
use App\Models\AuditLog;
use App\Models\Project;
use App\Models\Quotation;
use App\Support\Cache\CatalogCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class QuotationController extends Controller
{
    public function index(Request $request, CatalogCache $catalog): View
    {
        return view('quotations.index', [
            'quotations' => Quotation::query()
                ->select(['id', 'project_id', 'code', 'status', 'total', 'vat_rate_percent', 'created_at'])
                ->with('project:id,name')
                ->latest()
                ->paginate(25)
                ->withQueryString(),
            'projects' => $catalog->projectsPicker(),
            'openCreateModal' => $request->boolean('crear') || ($request->session()->has('errors') && old('_form') === 'quotation-create'),
        ]);
    }

    public function create(Project $project): View
    {
        return view('quotations.form', [
            'project' => $project,
            'quotation' => null,
            'projects' => collect(),
            'standalone' => false,
        ]);
    }

    public function createStandalone(CatalogCache $catalog): RedirectResponse
    {
        return redirect()->route('cotizaciones', ['crear' => 1]);
    }

    public function storeQuickProject(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $project = Project::createRecord([
            'name' => $validated['name'],
        ]);

        Log::info('[FIX] Quick project created from quotations module', [
            'project_id' => $project->id,
            'name' => $project->name,
        ]);

        return response()->json([
            'id' => (int) $project->id,
            'name' => $project->name,
            'code' => $project->code,
        ], 201);
    }

    public function storeStandalone(
        Request $request,
        CreateQuotationUseCase $useCase,
    ): RedirectResponse {
        try {
            $validated = $this->validatePayload($request, requireProject: true);
        } catch (ValidationException $e) {
            return $this->redirectStandaloneCreateFailure($e);
        }

        $project = Project::query()->findOrFail((int) $validated['project_id']);

        try {
            $quotation = $useCase->execute(new CreateQuotationInput(
                projectId: (int) $project->id,
                workDescription: $validated['work_description'],
                designedSolution: $validated['designed_solution'] ?? '',
                lines: $this->mapLines($validated['lines']),
                createdBy: Auth::id(),
                vatRatePercent: isset($validated['vat_rate_percent'])
                    ? (string) $validated['vat_rate_percent']
                    : null,
            ));

            Log::info('[FIX] Standalone quotation created', [
                'quotation_id' => $quotation->id()?->value(),
                'project_id' => $project->id,
            ]);

            return redirect()
                ->route('cotizaciones')
                ->with('status', 'Cotización creada: '.$quotation->code());
        } catch (Throwable $e) {
            Log::error('[QuotationController.storeStandalone] ERROR', ['error' => $e->getMessage()]);

            return $this->redirectStandaloneCreateFailure(message: $e->getMessage());
        }
    }

    public function store(
        Request $request,
        Project $project,
        CreateQuotationUseCase $useCase,
    ): RedirectResponse {
        $validated = $this->validatePayload($request);

        try {
            $quotation = $useCase->execute(new CreateQuotationInput(
                projectId: (int) $project->id,
                workDescription: $validated['work_description'],
                designedSolution: $validated['designed_solution'] ?? '',
                lines: $this->mapLines($validated['lines']),
                createdBy: Auth::id(),
                vatRatePercent: isset($validated['vat_rate_percent'])
                    ? (string) $validated['vat_rate_percent']
                    : null,
            ));

            return redirect()
                ->route('projects.quotations.show', [$project, $quotation->id()->value()])
                ->with('status', 'Cotización creada: '.$quotation->code());
        } catch (Throwable $e) {
            Log::error('[QuotationController.store] ERROR', ['error' => $e->getMessage()]);

            return back()->withInput()->withErrors(['quotation' => $e->getMessage()]);
        }
    }

    public function show(
        Project $project,
        int $quotation,
        QuotationRepositoryInterface $repository,
    ): View {
        $entity = $this->loadOwned($project, $quotation, $repository);
        $model = Quotation::query()
            ->select(['id', 'project_id', 'code', 'status'])
            ->with('installationOrder:id,quotation_id,code,status')
            ->findOrFail($quotation);
        $history = AuditLog::query()
            ->select(['id', 'action', 'created_at'])
            ->where('auditable_type', \App\Domain\Quotation\Entities\Quotation::class)
            ->where('auditable_id', $quotation)
            ->latest()
            ->limit(50)
            ->get();

        return view('quotations.show', [
            'project' => $project,
            'quotation' => $entity,
            'model' => $model,
            'history' => $history,
        ]);
    }

    public function edit(
        Project $project,
        int $quotation,
        QuotationRepositoryInterface $repository,
    ): View {
        $entity = $this->loadOwned($project, $quotation, $repository);
        abort_unless($entity->status()->isEditable(), 403);

        return view('quotations.form', [
            'project' => $project,
            'quotation' => $entity,
            'projects' => collect(),
            'standalone' => false,
        ]);
    }

    public function update(
        Request $request,
        Project $project,
        int $quotation,
        UpdateQuotationLinesUseCase $useCase,
        QuotationRepositoryInterface $repository,
    ): RedirectResponse {
        $this->loadOwned($project, $quotation, $repository);
        $validated = $this->validatePayload($request);

        try {
            $useCase->execute(
                quotationId: $quotation,
                workDescription: $validated['work_description'],
                designedSolution: $validated['designed_solution'] ?? '',
                lines: $this->mapLines($validated['lines']),
                userId: Auth::id(),
            );

            return redirect()
                ->route('projects.quotations.show', [$project, $quotation])
                ->with('status', 'Cotización actualizada.');
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['quotation' => $e->getMessage()]);
        }
    }

    public function transition(
        Request $request,
        Project $project,
        int $quotation,
        TransitionQuotationStatusUseCase $useCase,
        QuotationRepositoryInterface $repository,
    ): RedirectResponse {
        $this->loadOwned($project, $quotation, $repository);
        $validated = $request->validate([
            'status' => ['required', 'string'],
        ]);

        try {
            $target = QuotationStatus::from($validated['status']);
            $useCase->execute($quotation, $target, Auth::id());

            return back()->with('status', 'Estado actualizado a '.$target->value.'.');
        } catch (InvalidQuotationTransition|Throwable $e) {
            Log::warning('[QuotationController.transition] blocked', ['error' => $e->getMessage()]);

            return back()->withErrors(['status' => $e->getMessage()]);
        }
    }

    public function downloadPdf(
        Project $project,
        int $quotation,
        QuotationRepositoryInterface $repository,
        QuotationPdfGeneratorInterface $pdf,
    ): Response {
        $entity = $this->loadOwned($project, $quotation, $repository);

        return $pdf->download($entity, $project->name);
    }

    public function convert(
        Project $project,
        int $quotation,
        ConvertApprovedQuotationToOrderUseCase $useCase,
        QuotationRepositoryInterface $repository,
    ): RedirectResponse {
        $this->loadOwned($project, $quotation, $repository);

        try {
            $result = $useCase->execute($quotation, Auth::id());

            return redirect()
                ->route('projects.orders.show', [$project, $result['order_id']])
                ->with('status', 'Orden creada: '.$result['order_code']);
        } catch (QuotationNotConvertible|QuotationNotFoundException $e) {
            return back()->withErrors(['convert' => $e->getMessage()]);
        } catch (Throwable $e) {
            Log::error('[QuotationController.convert] ERROR', ['error' => $e->getMessage()]);

            return back()->withErrors(['convert' => $e->getMessage()]);
        }
    }

    private function loadOwned(
        Project $project,
        int $quotationId,
        QuotationRepositoryInterface $repository,
    ): \App\Domain\Quotation\Entities\Quotation {
        $entity = $repository->findById(QuotationId::fromInt($quotationId));
        abort_if($entity === null, 404);
        abort_unless($entity->projectId()->value() === (int) $project->id, 404);

        return $entity;
    }

    /** @return array{work_description: string, designed_solution?: string, lines: list<array<string, mixed>>, project_id?: int, vat_rate_percent?: string|null} */
    private function validatePayload(Request $request, bool $requireProject = false): array
    {
        $rules = [
            'work_description' => ['required', 'string', 'max:10000'],
            'designed_solution' => ['nullable', 'string', 'max:10000'],
            'vat_rate_percent' => ['nullable', 'numeric', 'gte:0', 'lte:100'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_name' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.brand' => ['nullable', 'string', 'max:255'],
            'lines.*.serial' => ['nullable', 'string', 'max:255'],
            'lines.*.unit_price' => ['required', 'numeric', 'gte:0'],
        ];

        if ($requireProject) {
            $rules['project_id'] = ['required', 'integer', 'exists:projects_tb,id'];
        }

        return $request->validate($rules);
    }

    private function redirectStandaloneCreateFailure(
        ?ValidationException $exception = null,
        ?string $message = null,
    ): RedirectResponse {
        $redirect = redirect()
            ->route('cotizaciones', ['crear' => 1])
            ->withInput();

        if ($exception !== null) {
            return $redirect->withErrors($exception->validator);
        }

        return $redirect->withErrors(['quotation' => $message ?? 'No se pudo crear la cotización.']);
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     * @return list<QuotationLineInput>
     */
    private function mapLines(array $lines): array
    {
        $mapped = [];
        foreach (array_values($lines) as $index => $line) {
            $brand = $line['brand'] ?? null;
            $serial = $line['serial'] ?? null;
            $mapped[] = new QuotationLineInput(
                productName: (string) $line['product_name'],
                quantity: (string) $line['quantity'],
                brand: $brand !== null && $brand !== '' ? (string) $brand : null,
                serial: $serial !== null && $serial !== '' ? (string) $serial : null,
                unitPrice: (string) $line['unit_price'],
                sortOrder: $index,
            );
        }

        return $mapped;
    }
}
