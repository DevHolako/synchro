<?php

namespace App\Models\Concerns;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Bookings stored as a same-day `starts_at`/`ends_at` window of school wall-clock time.
 */
trait OverlapsInTime
{
    /**
     * Bookings overlapping the half-open interval [start, end): touching edges do not overlap.
     *
     * Bookings never cross midnight, so only those starting the same day can overlap;
     * bounding `starts_at` on both sides keeps the lookup a narrow index range scan.
     *
     * @param  Builder<static>  $query
     */
    public function scopeOverlapping(Builder $query, CarbonInterface $start, CarbonInterface $end): void
    {
        $query->where($query->qualifyColumn('starts_at'), '>=', $start->copy()->startOfDay())
            ->where($query->qualifyColumn('starts_at'), '<', $end)
            ->where($query->qualifyColumn('ends_at'), '>', $start);
    }
}
