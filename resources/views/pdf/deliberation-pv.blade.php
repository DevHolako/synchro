@extends('pdf.layout')

@section('title', __($retake ? 'documents.pv_title_retake' : 'documents.pv_title', [], 'fr'))

@section('content')
    <div class="header">
        <div class="institution">{{ __('documents.institution', [], 'fr') }}</div>
        <div class="subtitle">{{ __('documents.session', ['period' => $exam->examPeriod->name, 'year' => $exam->examPeriod->academic_year], 'fr') }}</div>
    </div>

    <h1>{{ __($retake ? 'documents.pv_title_retake' : 'documents.pv_title', [], 'fr') }}</h1>

    <table class="facts">
        <tr><td class="label">{{ __('documents.exam', [], 'fr') }}</td><td><strong>{{ $exam->module->label() }}</strong></td></tr>
        <tr><td class="label">{{ __('documents.date', [], 'fr') }}</td><td>{{ $day }} · {{ __('documents.time_range', ['start' => $exam->starts_at->format('H:i'), 'end' => $exam->ends_at->format('H:i')], 'fr') }}</td></tr>
        <tr><td class="label">{{ __('documents.pv_teacher', [], 'fr') }}</td><td>{{ $exam->module->teacher?->name ?? '—' }}</td></tr>
        <tr><td class="label">{{ __('documents.pv_weighting', [], 'fr') }}</td><td>{{ __('documents.pv_weighting_value', ['cc' => $weight, 'exam' => 100 - $weight], 'fr') }}</td></tr>
    </table>

    <h2>{{ __('documents.pv_grades', [], 'fr') }}</h2>
    <table class="grid" style="font-size: 9pt;">
        <tr>
            <th>{{ __('documents.pv_number', [], 'fr') }}</th>
            <th>{{ __('documents.name', [], 'fr') }}</th>
            <th>{{ __('documents.student_number', [], 'fr') }}</th>
            <th>{{ __('documents.group', [], 'fr') }}</th>
            @if ($weight > 0)
                <th>{{ __('documents.pv_cc', [], 'fr') }}</th>
            @endif
            <th>{{ __('documents.pv_exam', [], 'fr') }}</th>
            <th>{{ __('documents.pv_final', [], 'fr') }}</th>
            <th>{{ __('documents.pv_result', [], 'fr') }}</th>
        </tr>
        @forelse ($lines as $line)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $line['name'] }}</td>
                <td style="white-space: nowrap;">{{ $line['student_number'] }}</td>
                <td style="white-space: nowrap;">{{ $line['group'] }}</td>
                @if ($weight > 0)
                    <td>{{ $line['continuous_assessment_grade'] }}</td>
                @endif
                <td>{{ $line['is_absent'] ? __('documents.pv_absent', [], 'fr') : $line['exam_grade'] }}</td>
                <td><strong>{{ $line['final_grade'] }}</strong></td>
                <td>{{ $line['passed'] ? __('documents.pv_passed', [], 'fr') : __($retake ? 'documents.pv_failed' : 'documents.pv_retake', [], 'fr') }}</td>
            </tr>
        @empty
            <tr><td colspan="8">{{ __('documents.no_students', [], 'fr') }}</td></tr>
        @endforelse
    </table>

    <div class="keep-together">
        <h2>{{ __('documents.pv_summary', [], 'fr') }}</h2>
        <table class="facts">
            <tr><td class="label">{{ __('documents.pv_graded', [], 'fr') }}</td><td>{{ $stats['graded'] }}</td></tr>
            <tr><td class="label">{{ __('documents.pv_average', [], 'fr') }}</td><td>{{ $stats['average'] ?? '—' }}</td></tr>
            <tr><td class="label">{{ __('documents.pv_median', [], 'fr') }}</td><td>{{ $stats['median'] ?? '—' }}</td></tr>
            <tr><td class="label">{{ __('documents.pv_pass_rate', [], 'fr') }}</td><td>{{ $stats['pass_rate'] === null ? '—' : $stats['pass_rate'].' %' }}</td></tr>
            <tr><td class="label">{{ __($retake ? 'documents.pv_counts_retake' : 'documents.pv_counts', [], 'fr') }}</td><td>{{ __('documents.pv_counts_value', ['passing' => $stats['passing'], 'failing' => $stats['failing'], 'absent' => $stats['absent']], 'fr') }}</td></tr>
        </table>

        <table class="grid" style="margin-top: 14pt;">
            <tr>
                <th>{{ __('documents.pv_submitted', [], 'fr') }}</th>
                <th>{{ __('documents.pv_signature', [], 'fr') }}</th>
            </tr>
            <tr>
                <td style="vertical-align: top;">{{ $submittedBy ?? '—' }}<br><span class="muted">{{ $submittedAt }}</span></td>
                <td style="height: 60pt; vertical-align: top;">{{ $lockedBy }}<br><span class="muted">{{ __('documents.pv_locked_at', ['date' => $lockedAt], 'fr') }}</span></td>
            </tr>
        </table>
        <p class="muted">{{ __('documents.generated_at', ['date' => $generatedAt], 'fr') }}</p>
    </div>
@endsection
