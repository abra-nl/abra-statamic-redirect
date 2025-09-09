<?php

namespace Abra\AbraStatamicRedirect\Http\Controllers;

use Abra\AbraStatamicRedirect\Interfaces\RedirectRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Statamic\Http\Controllers\CP\CpController;

class RedirectController extends CpController
{
    public function __construct(protected RedirectRepository $redirects) {}

    /**
     * Display a listing of redirects
     */
    public function index(): View
    {
        $redirects = $this->redirects->all();

        return view('abra-redirects::index', [
            'redirects' => $redirects,
            'statusCodes' => config('redirects.status_codes'),
        ]);
    }

    public function create(): View
    {
        return view('abra-redirects::create', [
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
            'source' => 'required|string',
            'destination' => 'required|string',
            'status_code' => 'required|integer|in:'.implode(',', array_keys($statusCodes)),
        ]);

        // Check if source already exists
        if ($this->redirects->exists($validated['source'])) {
            return back()->withErrors(['source' => 'A redirect with this source URL already exists.'])->withInput();
        }

        $this->redirects->store($validated);

        return redirect()->route('statamic.cp.abra-statamic-redirects.index')->with('success', 'Redirect created successfully.');
    }

    /**
     * Show the form for editing a redirect
     */
    public function edit(string $id): View|RedirectResponse
    {
        $redirects = $this->redirects->all();
        $redirect = collect($redirects)->firstWhere('id', $id);

        if (! $redirect) {
            return redirect()->route('statamic.cp.abra-statamic-redirects.index')->with('error', 'Redirect not found.');
        }

        return view('abra-redirects::edit', [
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
            'source' => 'required|string',
            'destination' => 'required|string',
            'status_code' => 'required|integer|in:'.implode(',', array_keys($statusCodes)),
        ]);

        // Check if source already exists (excluding this redirect)
        if ($this->redirects->exists($validated['source'], $id)) {
            return back()->withErrors(['source' => 'A redirect with this source URL already exists.'])->withInput();
        }

        $this->redirects->update($id, $validated);

        return redirect()->route('statamic.cp.abra-statamic-redirects.index')->with('success', 'Redirect updated successfully.');
    }

    /**
     * Delete a redirect
     *
     * @return RedirectResponse
     */
    public function destroy(string $id)
    {
        $this->redirects->delete($id);

        return redirect()->route('statamic.cp.abra-statamic-redirects.index')->with('success', 'Redirect deleted successfully.');
    }
}
