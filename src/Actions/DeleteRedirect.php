<?php

namespace Abra\AbraStatamicRedirect\Actions;

use Abra\AbraStatamicRedirect\Interfaces\RedirectRepository;
use Exception;
use Illuminate\Support\Collection;
use Statamic\Actions\Action;

use function Statamic\trans as __;
use function Statamic\trans_choice;

class DeleteRedirect extends Action
{
    protected string $icon = 'trash';

    /** @var bool */
    protected $dangerous = true;

    public function __construct(protected RedirectRepository $redirects)
    {
        parent::__construct();
    }

    public static function title(): string
    {
        return __('Delete');
    }

    public function confirmationText(): string
    {
        /** @translation */
        return 'Are you sure you want to delete this redirect?|Are you sure you want to delete these :count redirects?';
    }

    /**
     * @param  Collection<int, array{id: string}>  $items
     * @param  array<string, mixed>  $values
     * @return array{message: string, callback: array{0: string, 1: Collection<int, string>}|null}
     */
    public function run($items, $values): array
    {
        $failures = $items->reject(fn (array $redirect): bool => $this->redirects->delete($redirect['id']));

        if ($failures->isNotEmpty()) {
            throw new Exception(trans_choice('Redirect could not be deleted|Redirects could not be deleted', $failures->count()));
        }

        $ids = $items->pluck('id')->values();

        return [
            'message' => trans_choice('Redirect deleted|Redirects deleted', $items->count()),
            'callback' => $ids->isNotEmpty() ? ['removeFromSelections', $ids] : null,
        ];
    }
}
