<?php

namespace App\Actions\Exams;

use App\Models\Exam;
use App\Models\ExamCandidate;
use App\Services\Documents\QrCode;
use App\Support\SchoolClock;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\URL;

/**
 * A candidate's official convocation (in French): identity, exam, room and seat, instructions,
 * and a QR code of the signed verification link the invigilator scans at the door (ADR 0008).
 */
class RenderConvocationPdfAction
{
    /**
     * @return string The PDF document.
     */
    public function execute(ExamCandidate $candidate): string
    {
        return Pdf::loadView('pdf.convocation', $this->viewData($candidate))
            ->setPaper('a4')
            ->setOption('isFontSubsettingEnabled', true)
            ->output();
    }

    /**
     * What the convocation template prints, the QR code included.
     *
     * @return array{exam: Exam, name: string, studentNumber: string, group: string, day: string, room: string, seat: int, qrCode: string, generatedAt: string}
     */
    public function viewData(ExamCandidate $candidate): array
    {
        $candidate->loadMissing([
            'exam.module:id,code,name',
            'exam.examPeriod:id,name,academic_year',
            'student.studentProfile.studentGroup:id,name',
            'roomAssignment.room.building:id,name',
        ]);
        $profile = $candidate->student->studentProfile;
        $room = $candidate->roomAssignment->room;

        return [
            'exam' => $candidate->exam,
            'name' => $candidate->student->officialName(),
            'studentNumber' => $profile->student_number ?? '—',
            'group' => $profile?->studentGroup->name ?? '—',
            'day' => $candidate->exam->starts_at->settings(['locale' => 'fr'])->isoFormat('dddd D MMMM YYYY'),
            'room' => "{$room->name} · {$room->building->name}",
            'seat' => $candidate->seat_number,
            'qrCode' => QrCode::svgDataUri($this->verificationUrl($candidate)),
            'generatedAt' => SchoolClock::now()->format('d/m/Y H:i'),
        ];
    }

    /**
     * The signed link in the QR code: HMAC-SHA256 over the URL with the app key, no expiry.
     */
    public function verificationUrl(ExamCandidate $candidate): string
    {
        return URL::signedRoute('convocations.verify', ['uuid' => $candidate->convocation_uuid]);
    }
}
