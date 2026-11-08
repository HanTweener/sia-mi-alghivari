<?php

namespace App\Services\Reports;

use App\Models\ReportCard;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ReportPdfService
{
    /**
     * Render and store the report card PDF privately.
     *
     * @throws RuntimeException
     */
    public function renderAndStore(ReportCard $report): string
    {
        $report->loadMissing([
            'enrollment.student',
            'enrollment.classroom.activeHomeroom.teacher',
            'term.academicYear',
            'items.classSubject.subject',
            'extracurricularAssessments',
            'p5Assessments',
            'homeroomAssignment.teacher',
            'finalizedBy',
        ]);

        $viewData = $this->prepareViewData($report);

        // Render PDF with Dompdf
        $pdf = Pdf::loadView('reports.report_card', $viewData)
            ->setPaper('a4', 'portrait')
            ->setOption([
                'isRemoteEnabled' => false,
                'isHtml5ParserEnabled' => true,
                'defaultFont' => 'Helvetica',
            ]);

        $binary = $pdf->output();

        // Private storage path (never public)
        $fileName = "reports/report_card_{$report->id}_rev_{$report->revision}.pdf";
        Storage::disk('local')->put($fileName, $binary);

        $report->update([
            'private_file_path' => $fileName,
        ]);

        return $fileName;
    }

    /**
     * Prepare data dictionary for the Blade report card template (Q7.1).
     *
     * @return array<string, mixed>
     */
    public function prepareViewData(ReportCard $report): array
    {
        $identity = $report->identity_snapshot ?? [];

        $student = $identity['student'] ?? [
            'name' => $report->enrollment?->student?->name ?? 'Peserta Didik',
            'nis' => $report->enrollment?->student?->nis ?? '-',
            'nisn' => $report->enrollment?->student?->nisn ?? null,
        ];

        $classroom = $identity['classroom'] ?? [
            'name' => $report->enrollment?->classroom?->name ?? '-',
        ];

        $term = $identity['term'] ?? [
            'name' => $report->term?->name ?? 'Semester',
            'semester' => $report->term?->semester ?? 1,
            'academic_year' => $report->term?->academicYear?->name ?? '2026/2027',
        ];

        $homeroom = $identity['homeroom'] ?? [
            'name' => $report->homeroomAssignment?->teacher?->name ?? 'Wali Kelas',
            'nip' => $report->homeroomAssignment?->teacher?->nip ?? '-',
        ];

        $principal = $identity['principal'] ?? [
            'name' => 'Drs. H. Mulyadi, M.Pd',
            'nip' => '196805121994031002',
        ];

        $school = $identity['school'] ?? [
            'name' => 'MADRASAH IBTIDAIYAH AL-GHIVARI',
            'address' => 'Jl. Cisaranten Kulon No. 112, Arcamanik, Kota Bandung, Jawa Barat',
            'npsn' => '69900000',
        ];

        // Format items with attendance data
        $items = $report->items->map(function ($item) {
            $breakdown = $item->component_breakdown_snapshot ?? [];
            $attComponent = $breakdown['attendance']['attendance'] ?? null;

            return [
                'id' => $item->id,
                'subject_snapshot' => $item->subject_snapshot,
                'kkm_snapshot' => $item->kkm_snapshot,
                'final_score' => $item->final_score,
                'kkm_status' => $item->kkm_status,
                'attendance' => $attComponent ?? [
                    'present' => 0,
                    'excused' => 0,
                    'sick' => 0,
                    'absent' => 0,
                    'total_sessions' => 0,
                    'rate' => null,
                ],
            ];
        })->all();

        $datePublished = $report->published_at
            ? $report->published_at->format('d/m/Y')
            : ($report->finalized_at ? $report->finalized_at->format('d/m/Y') : date('d/m/Y'));

        return [
            'schoolName' => $school['name'],
            'schoolAddress' => $school['address'],
            'schoolNpsn' => $school['npsn'],
            'academicYear' => $term['academic_year'],
            'semester' => $term['semester'],
            'termName' => $term['name'],
            'studentName' => $student['name'],
            'studentNis' => $student['nis'],
            'studentNisn' => $student['nisn'],
            'className' => $classroom['name'],
            'items' => $items,
            'homeroomNote' => $report->homeroom_note,
            'homeroomTeacherName' => $homeroom['name'],
            'homeroomTeacherNip' => $homeroom['nip'],
            'principalName' => $principal['name'],
            'principalNip' => $principal['nip'],
            'datePublished' => $datePublished,
            'revision' => $report->revision,
            'sourceVersion' => $report->source_version ?? '-',
            'extracurriculars' => $report->extracurricularAssessments->all(),
            'p5Assessments' => $report->p5Assessments->all(),
        ];
    }
}
