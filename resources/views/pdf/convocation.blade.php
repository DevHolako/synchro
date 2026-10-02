@extends('pdf.layout')

@section('title', __('documents.convocation_title', [], 'fr'))

@section('content')
    <div class="header">
        <div class="institution">{{ __('documents.institution', [], 'fr') }}</div>
        <div class="subtitle">{{ __('documents.session', ['period' => $exam->examPeriod->name, 'year' => $exam->examPeriod->academic_year], 'fr') }}</div>
    </div>

    <table>
        <tr>
            <td style="vertical-align: top;">
                <h1>{{ __('documents.convocation_title', [], 'fr') }}</h1>

                <table class="facts">
                    <tr><td class="label">{{ __('documents.name', [], 'fr') }}</td><td><strong>{{ $name }}</strong></td></tr>
                    <tr><td class="label">{{ __('documents.student_number', [], 'fr') }}</td><td>{{ $studentNumber }}</td></tr>
                    <tr><td class="label">{{ __('documents.group', [], 'fr') }}</td><td>{{ $group }}</td></tr>
                    <tr><td class="label">{{ __('documents.exam', [], 'fr') }}</td><td>{{ $exam->module->code }} · {{ $exam->module->name }}</td></tr>
                    <tr><td class="label">{{ __('documents.date', [], 'fr') }}</td><td>{{ $day }}</td></tr>
                    <tr><td class="label">{{ __('documents.time', [], 'fr') }}</td><td>{{ __('documents.time_range', ['start' => $exam->starts_at->format('H:i'), 'end' => $exam->ends_at->format('H:i')], 'fr') }}</td></tr>
                    <tr><td class="label">{{ __('documents.room', [], 'fr') }}</td><td><strong>{{ $room }}</strong></td></tr>
                    <tr><td class="label">{{ __('documents.seat', [], 'fr') }}</td><td><strong>{{ __('documents.seat_number', ['seat' => $seat], 'fr') }}</strong></td></tr>
                </table>
            </td>
            <td style="width: 170px; text-align: center; vertical-align: top;">
                <img src="{{ $qrCode }}" width="160" height="160" alt="">
                <div class="muted">{{ __('documents.qr_hint', [], 'fr') }}</div>
            </td>
        </tr>
    </table>

    <h2>{{ __('documents.instructions_title', [], 'fr') }}</h2>
    <ul>
        @foreach (__('documents.instructions', [], 'fr') as $instruction)
            <li>{{ $instruction }}</li>
        @endforeach
    </ul>

    <p class="muted">{{ __('documents.generated_at', ['date' => $generatedAt], 'fr') }}</p>
@endsection
