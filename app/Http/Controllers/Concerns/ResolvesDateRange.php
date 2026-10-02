<?php

namespace App\Http\Controllers\Concerns;

use App\Support\SchoolClock;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

trait ResolvesDateRange
{
    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    protected function resolveDateRange(Request $request): array
    {
        if ($request->filled('from') && $request->filled('until')) {
            return [
                CarbonImmutable::parse($request->string('from')->value()),
                CarbonImmutable::parse($request->string('until')->value()),
            ];
        }

        if ($request->filled('from')) {
            $from = CarbonImmutable::parse($request->string('from')->value());

            return [$from, $from->addDays(7)];
        }

        if ($request->filled('date')) {
            $anchor = CarbonImmutable::parse($request->string('date')->value());
            $from = $anchor->startOfWeek();

            return [$from, $from->addDays(7)];
        }

        $from = SchoolClock::today()->startOfWeek();

        return [$from, $from->addDays(7)];
    }
}
