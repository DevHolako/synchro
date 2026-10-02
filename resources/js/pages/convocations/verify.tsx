import { Head, Link, router } from '@inertiajs/react';
import { ListChecks, UserCheck, Undo2 } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/i18n/LanguageContext';
import {
    destroy as undoCheckIn,
    store as checkIn,
} from '@/routes/exam-candidates/check-in';
import { toastErrors } from '@/lib/toast-errors';
import { checkIn as roomCheckIn } from '@/routes/exams/rooms';
import { CandidateCard } from './components/candidate-card';
import { CheckInBanner } from './components/check-in-banner';
import type {
    CheckInCandidate,
    CheckInExam,
    CheckInState,
} from './components/types';
import { useExamsBreadcrumbs } from '@/pages/exams/components/use-exams-breadcrumbs';

interface ConvocationVerifyProps {
    candidate: CheckInCandidate;
    exam: CheckInExam;
    checkIn: CheckInState;
    /** The scanning invigilator's room, to go back to its list. */
    viewerRoomId: number | null;
}

/** The door check-in screen a scanned convocation opens, built for a phone (ADR 0008). */
export default function ConvocationVerify({
    candidate,
    exam,
    checkIn: state,
    viewerRoomId,
}: ConvocationVerifyProps) {
    const { t } = useTranslation();
    const [processing, setProcessing] = useState(false);

    useExamsBreadcrumbs();

    const submit = (
        route: ReturnType<typeof checkIn> | ReturnType<typeof undoCheckIn>,
    ) =>
        router.visit(route, {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
            onError: (errors) => toastErrors(errors),
        });

    return (
        <>
            <Head title={t('convocations.title')} />

            <div className="mx-auto flex w-full max-w-md flex-col gap-4 p-4">
                <CheckInBanner candidate={candidate} checkIn={state} />
                <CandidateCard candidate={candidate} exam={exam} />

                {state.can_check_in ? (
                    <Button
                        size="lg"
                        className="h-14 text-base"
                        disabled={processing}
                        onClick={() => submit(checkIn(candidate.id))}
                    >
                        {processing ? (
                            <Spinner />
                        ) : (
                            <UserCheck className="mr-2 size-5" />
                        )}
                        {t('check_in.mark_present')}
                    </Button>
                ) : null}
                {state.can_undo ? (
                    <Button
                        variant="outline"
                        disabled={processing}
                        onClick={() => submit(undoCheckIn(candidate.id))}
                    >
                        <Undo2 className="mr-2 size-4" />
                        {t('check_in.undo')}
                    </Button>
                ) : null}
                {viewerRoomId !== null ? (
                    <Button variant="ghost" asChild>
                        <Link
                            href={
                                roomCheckIn({
                                    exam: exam.id,
                                    assignment: viewerRoomId,
                                }).url
                            }
                        >
                            <ListChecks className="mr-2 size-4" />
                            {t('check_in.back_to_room')}
                        </Link>
                    </Button>
                ) : null}
            </div>
        </>
    );
}
