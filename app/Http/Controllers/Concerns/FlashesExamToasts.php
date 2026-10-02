<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * The toasts exam controllers share: the outcome of a write (naming any invigilators it
 * released), and "still being prepared" for a document not generated yet.
 */
trait FlashesExamToasts
{
    /**
     * @param  array<int, string>  $released  The released invigilators' names, by id.
     */
    protected function flashExamOutcome(string $successMessage, array $released): void
    {
        Inertia::flash('toast', $released === []
            ? ['type' => 'success', 'message' => $successMessage]
            : ['type' => 'warning', 'message' => __('messages.exam_outcome_with_released', [
                'outcome' => $successMessage,
                'names' => implode(', ', $released),
            ])]);
    }

    /**
     * Back to the page, saying the document is still being generated in the background.
     */
    protected function documentPending(): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'info', 'message' => __('messages.exam_document_pending')]);

        return back();
    }
}
