<?php

namespace Abra\AbraStatamicRedirect\Http\Controllers\CP;

use Abra\AbraStatamicRedirect\Interfaces\RedirectRepository;
use Illuminate\Support\Collection;
use Statamic\Http\Controllers\CP\ActionController;

class RedirectActionController extends ActionController
{
    public function __construct(protected RedirectRepository $redirects) {}

    /**
     * @param  Collection<int, string>  $items
     * @param  array<string, mixed>  $context
     * @return Collection<int, array{id: string, host: string, source: string, destination: string, status_code: int, created_at: string, updated_at: string}>
     */
    protected function getSelectedItems($items, $context): Collection
    {
        $redirects = collect($this->redirects->all());

        return $items
            ->map(fn (string $id) => $redirects->firstWhere('id', $id))
            ->filter()
            ->values();
    }
}
