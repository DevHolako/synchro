@extends('pdf.layout')

@section('title', __('documents.roster_title', [], 'fr'))

@section('content')
    @foreach ($rooms as $room)
        <div class="header">
            <div class="institution">{{ __('documents.institution', [], 'fr') }}</div>
            <div class="subtitle">{{ $exam->module->label() }} · {{ $day }} · {{ __('documents.time_range', ['start' => $exam->starts_at->format('H:i'), 'end' => $exam->ends_at->format('H:i')], 'fr') }}</div>
        </div>

        <h1>{{ __('documents.door_list_title', [], 'fr') }} · {{ $room['name'] }}</h1>
        <p>
            @if ($room['from'])
                <strong>{{ __('documents.range', ['from' => $room['from'], 'to' => $room['to']], 'fr') }}</strong> ·
            @endif
            {{ __('documents.students_count', ['count' => count($room['students'])], 'fr') }}
        </p>
        <table class="grid">
            <tr><th>{{ __('documents.seat', [], 'fr') }}</th><th>{{ __('documents.name', [], 'fr') }}</th><th>{{ __('documents.group', [], 'fr') }}</th></tr>
            @forelse ($room['students'] as $student)
                <tr><td>{{ $student['seat'] }}</td><td>{{ $student['name'] }}</td><td>{{ $student['group'] }}</td></tr>
            @empty
                <tr><td colspan="3">{{ __('documents.no_students', [], 'fr') }}</td></tr>
            @endforelse
        </table>

        <div class="page-break"></div>

        <div class="header">
            <div class="institution">{{ __('documents.institution', [], 'fr') }}</div>
            <div class="subtitle">{{ $exam->module->label() }} · {{ $day }} · {{ __('documents.time_range', ['start' => $exam->starts_at->format('H:i'), 'end' => $exam->ends_at->format('H:i')], 'fr') }}</div>
        </div>

        <h1>{{ __('documents.roster_title', [], 'fr') }} · {{ $room['name'] }}</h1>
        <table class="grid">
            <tr>
                <th>{{ __('documents.seat', [], 'fr') }}</th>
                <th>{{ __('documents.name', [], 'fr') }}</th>
                <th>{{ __('documents.student_number', [], 'fr') }}</th>
                <th class="signature-cell">{{ __('documents.signature', [], 'fr') }}</th>
            </tr>
            @foreach ($room['students'] as $student)
                <tr><td>{{ $student['seat'] }}</td><td>{{ $student['name'] }}</td><td>{{ $student['student_number'] }}</td><td class="signature-cell"></td></tr>
            @endforeach
        </table>

        <div class="keep-together">
            <p>{{ __('documents.present_count', [], 'fr') }}</p>
            <table class="grid">
                <tr><th>{{ __('documents.invigilators', [], 'fr') }}</th></tr>
                <tr><td style="height: 50pt;"></td></tr>
            </table>
        </div>

        @unless ($loop->last)
            <div class="page-break"></div>
        @endunless
    @endforeach
@endsection
