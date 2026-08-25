<?php

namespace Abra\AbraStatamicRedirect\Http\Controllers;

use Abra\AbraStatamicRedirect\Interfaces\RedirectRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;
use Statamic\Http\Controllers\CP\CpController;

class RedirectController extends CpController
{
    public function __construct(protected RedirectRepository $redirects) {}

    /**
     * Display a listing of redirects
     */
    public function index(): Response
    {
        return Inertia::render('abra-redirects::Index', [
            'statusCodes' => config('redirects.status_codes'),
            'jsonUrl' => cp_route('abra-statamic-redirects.json'),
            'actionUrl' => cp_route('abra-statamic-redirects.actions.run'),
        ]);
    }

    /**
     * Return a paginated, searchable, sortable listing of redirects for the Listing component
     */
    public function json(Request $request): JsonResponse
    {
        $redirects = collect($this->redirects->all());

        if ($search = $request->string('search')->toString()) {
            $redirects = $redirects->filter(fn (array $redirect): bool => str_contains(strtolower($redirect['host'] ?? ''), strtolower($search))
                || str_contains(strtolower($redirect['source']), strtolower($search))
                || str_contains(strtolower($redirect['destination']), strtolower($search)));
        }

        $sort = $request->string('sort', 'host')->toString();
        $descending = $request->string('order', 'asc')->toString() === 'desc';

        $redirects = $redirects
            ->sortBy(fn (array $redirect) => $redirect[$sort] ?? null, SORT_REGULAR, $descending)
            ->values();

        $perPage = $request->integer('perPage', config('statamic.cp.pagination_size'));
        $page = $request->integer('page', 1);

        $paginator = new LengthAwarePaginator(
            $redirects->forPage($page, $perPage)->values(),
            $redirects->count(),
            $perPage,
            $page,
        );

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'columns' => $this->listingColumns(),
                'activeFilterBadges' => [],
            ],
        ]);
    }

    /**
     * @return array<int, array{field: string, label: string, sortable: bool, visible: bool}>
     */
    private function listingColumns(): array
    {
        return [
            ['field' => 'host', 'label' => __('Host'), 'sortable' => true, 'visible' => true],
            ['field' => 'source', 'label' => __('Source'), 'sortable' => true, 'visible' => true],
            ['field' => 'destination', 'label' => __('Destination'), 'sortable' => true, 'visible' => true],
            ['field' => 'status_code', 'label' => __('Status code'), 'sortable' => true, 'visible' => true],
        ];
    }

    public function create(): Response
    {
        return Inertia::render('abra-redirects::Create', [
            'statusCodes' => config('redirects.status_codes'),
        ]);
    }

    /**
     * Store a new redirect
     */
    public function store(Request $request): RedirectResponse
    {
        /** @var array<string> $statusCodes */
        $statusCodes = config('redirects.status_codes');

        $validated = $request->validate([
            'host' => 'nullable|string',
            'source' => 'required|string',
            'destination' => 'required|string',
            'status_code' => 'required|integer|in:'.implode(',', array_keys($statusCodes)),
        ]);

        // Check if source already exists for this host
        if ($this->redirects->exists($validated['source'], $validated['host'] ?? null)) {
            return back()->withErrors(['source' => __('A redirect with this source URL already exists.')])->withInput();
        }

        $this->redirects->store($validated);

        return redirect()->route('statamic.cp.abra-statamic-redirects.index')->with('success', __('Redirect created successfully.'));
    }

    /**
     * Show the form for editing a redirect
     */
    public function edit(string $id): Response|RedirectResponse
    {
        $redirects = $this->redirects->all();
        $redirect = collect($redirects)->firstWhere('id', $id);

        if (! $redirect) {
            return redirect()->route('statamic.cp.abra-statamic-redirects.index')
                ->with('error', __('Redirect not found.'));
        }

        return Inertia::render('abra-redirects::Edit', [
            'redirect' => $redirect,
            'statusCodes' => config('redirects.status_codes'),
        ]);
    }

    /**
     * Update a redirect
     *
     * @param  string  $id
     * @return RedirectResponse
     */
    public function update(Request $request, $id)
    {
        /** @var array<string> $statusCodes */
        $statusCodes = config('redirects.status_codes');

        $validated = $request->validate([
            'host' => 'nullable|string',
            'source' => 'required|string',
            'destination' => 'required|string',
            'status_code' => 'required|integer|in:'.implode(',', array_keys($statusCodes)),
        ]);

        // Check if source already exists for this host (excluding this redirect)
        if ($this->redirects->exists($validated['source'], $validated['host'] ?? null, $id)) {
            return back()->withErrors(['source' => __('A redirect with this source URL already exists.')])->withInput();
        }

        $this->redirects->update($id, $validated);

        return redirect()->route('statamic.cp.abra-statamic-redirects.index')->with('success', __('Redirect updated successfully.'));
    }

    /**
     * Delete a redirect
     *
     * @return RedirectResponse
     */
    public function destroy(string $id)
    {
        $this->redirects->delete($id);

        return redirect()->route('statamic.cp.abra-statamic-redirects.index')->with('success', __('Redirect deleted successfully.'));
    }
}
