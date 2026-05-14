<?php

namespace App\Admin\Controllers;

use App\Admin\Traits\IpScopeable;
use App\Models\AesaSession;
use App\Models\FfsGroup;
use App\Models\FfsSessionResolution;
use App\Models\FfsTrainingSession;
use App\Models\ImplementingPartner;
use App\Models\Project;
use App\Models\User;
use App\Models\VslaMeeting;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use League\Csv\Writer;

class ExportCentreController extends AdminController
{
    use IpScopeable;

    protected $title = 'Export Centre';

    // =========================================================================
    // MAIN PAGE
    // =========================================================================

    public function index(Content $content)
    {
        $ipId         = $this->getAdminIpId();
        $isSuperAdmin = $this->isSuperAdmin();

        // ── Groups (for filter dropdowns) ───────────────────────────────────
        $groupQuery = FfsGroup::query()->orderBy('name');
        if ($ipId) $groupQuery->where('ip_id', $ipId);
        $groups = $groupQuery->select('id', 'name')->get();

        // ── VSLA Cycles (for filter dropdown) ──────────────────────────────
        $cycleQuery = Project::where('is_vsla_cycle', 'Yes')->orderBy('cycle_name');
        if ($ipId) $cycleQuery->whereHas('group', fn($q) => $q->where('ip_id', $ipId));
        $cycles = $cycleQuery->select('id', 'cycle_name', 'group_id')->get();

        // ── IPs (super admin filter) ─────────────────────────────────────────
        $ips = $isSuperAdmin
            ? ImplementingPartner::where('status', 'Active')->orderBy('name')->get(['id', 'name', 'short_name'])
            : collect();

        // ── Recent meetings count ───────────────────────────────────────────
        $meetingsQuery = VslaMeeting::query();
        if ($ipId) $meetingsQuery->whereHas('group', fn($q) => $q->where('ip_id', $ipId));
        $meetingCount = $meetingsQuery->count();

        // ── Recent AESA sessions count ──────────────────────────────────────
        $aesaQuery = AesaSession::query();
        if ($ipId) $aesaQuery->where('ip_id', $ipId);
        $aesaCount = $aesaQuery->count();

        // ── Recent meetings for quick-access table ──────────────────────────
        $recentMeetings = (clone $meetingsQuery)
            ->with(['group', 'cycle'])
            ->where('processing_status', 'completed')
            ->orderByDesc('meeting_date')
            ->limit(50)
            ->get();

        // ── Cycles with meeting counts for cycle report table ───────────────
        $cyclesWithStats = Project::where('is_vsla_cycle', 'Yes')
            ->when($ipId, fn($q) => $q->whereHas('group', fn($g) => $g->where('ip_id', $ipId)))
            ->withCount(['vslaMeetings as completed_meetings' => fn($q) => $q->where('processing_status', 'completed')])
            ->with('group')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        // ── Recent AESA sessions for quick-access table ─────────────────────
        $recentAesaSessions = (clone $aesaQuery)
            ->with(['group', 'facilitator'])
            ->withCount(['observations', 'cropObservations'])
            ->orderByDesc('observation_date')
            ->limit(50)
            ->get();

        // ── Training sessions ────────────────────────────────────────────────
        $trainingQuery = FfsTrainingSession::query();
        if ($ipId) $trainingQuery->where('ip_id', $ipId);
        $trainingCount = $trainingQuery->count();

        $recentTrainingSessions = (clone $trainingQuery)
            ->with(['group', 'facilitator', 'coFacilitator'])
            ->withCount(['participants', 'resolutions'])
            ->orderByDesc('session_date')
            ->limit(50)
            ->get();

        return $content
            ->title('Export Centre')
            ->description('Download VSLA meeting reports, cycle summaries, AESA field observation data, and FFS training sessions')
            ->body(view('admin.export-centre', compact(
                'groups', 'cycles', 'ips',
                'meetingCount', 'aesaCount', 'trainingCount',
                'recentMeetings', 'cyclesWithStats', 'recentAesaSessions',
                'recentTrainingSessions',
                'isSuperAdmin', 'ipId'
            )));
    }

    // =========================================================================
    // VSLA MEETING CSV EXPORT  (web route — browser download)
    // =========================================================================

    public function exportMeetingsCsv(Request $request)
    {
        try {
            $ipId = $this->getAdminIpId();

            $query = VslaMeeting::with(['group', 'cycle', 'creator']);
            if ($ipId) $query->whereHas('group', fn($q) => $q->where('ip_id', $ipId));

            if ($request->filled('group_id'))  $query->where('group_id',         $request->group_id);
            if ($request->filled('cycle_id'))  $query->where('cycle_id',         $request->cycle_id);
            if ($request->filled('status'))    $query->where('processing_status', $request->status);
            if ($request->filled('date_from')) $query->where('meeting_date',     '>=', $request->date_from);
            if ($request->filled('date_to'))   $query->where('meeting_date',     '<=', $request->date_to);

            $meetings = $query->orderBy('meeting_date', 'desc')->get();

            $writer = Writer::fromString();
            $writer->insertOne([
                'Meeting #', 'Group', 'Cycle', 'Meeting Date', 'Status',
                'Members Present', 'Members Absent', 'Attendance Rate (%)',
                'Total Savings (UGX)', 'Total Loans Disbursed (UGX)',
                'Total Loans Repaid (UGX)', 'Total Fines (UGX)',
                'Total Welfare (UGX)', 'Net Cash In (UGX)',
                'Loans Count', 'Repayments Count', 'Submitted By', 'Created At',
            ]);

            foreach ($meetings as $m) {
                $attendance   = $this->safeJson($m->attendance_data);
                $transactions = $this->safeJson($m->transactions_data);
                $loans        = $this->safeJson($m->loans_data);
                $repayments   = $this->safeJson($m->loan_repayments_data);

                [$present, $absent] = $this->countAttendance($attendance);
                $total = $present + $absent;
                $rate  = $total > 0 ? round(($present / $total) * 100, 1) : 0;

                [$savings, $welfare, $fines] = $this->sumTransactionsByType($transactions);
                $disbursed = $this->sumLoans($loans);
                $repaid    = $this->sumRepayments($repayments);
                $net       = $savings + $welfare + $fines + $repaid - $disbursed;

                $writer->insertOne([
                    $m->meeting_number,
                    $m->group->name ?? 'N/A',
                    $m->cycle->cycle_name ?? $m->cycle->name ?? 'N/A',
                    $m->meeting_date ? \Carbon\Carbon::parse($m->meeting_date)->format('d/m/Y') : '',
                    ucfirst($m->processing_status ?? 'unknown'),
                    $present, $absent, $rate,
                    $this->fmt($savings), $this->fmt($disbursed), $this->fmt($repaid),
                    $this->fmt($fines), $this->fmt($welfare), $this->fmt($net),
                    count($loans), count($repayments),
                    $m->creator ? trim(($m->creator->first_name ?? '') . ' ' . ($m->creator->last_name ?? '')) : 'N/A',
                    $m->created_at ? $m->created_at->format('d/m/Y H:i') : '',
                ]);
            }

            $filename = 'vsla-meetings-' . now()->format('Ymd-His') . '.csv';
            return response($writer->toString(), 200, [
                'Content-Type'        => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);

        } catch (\Exception $e) {
            Log::error('Admin export meetings CSV failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'CSV export failed: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // VSLA SINGLE MEETING PDF  (web route)
    // =========================================================================

    public function exportMeetingPdf($id)
    {
        try {
            $ipId    = $this->getAdminIpId();
            $meeting = VslaMeeting::with(['cycle', 'group', 'group.implementingPartner', 'creator', 'implementingPartner'])
                ->findOrFail($id);

            if ($ipId && $meeting->group && (int)$meeting->group->ip_id !== (int)$ipId) {
                abort(403, 'Access denied');
            }

            $adminUser   = Admin::user();
            $generatedBy = $adminUser ? ($adminUser->name ?? $adminUser->username) : 'System';

            $pdf = Pdf::loadView('exports.vsla-meeting-pdf', [
                'meeting'     => $meeting,
                'generatedAt' => now()->format('d/m/Y H:i'),
                'generatedBy' => $generatedBy,
            ]);
            $pdf->setPaper('A4', 'portrait');
            $pdf->setOptions(['dpi' => 110, 'defaultFont' => 'DejaVu Sans', 'isHtml5ParserEnabled' => true]);

            $filename = 'vsla-meeting-' . ($meeting->group->name ?? 'group')
                . '-meeting' . $meeting->meeting_number
                . '-' . now()->format('Ymd') . '.pdf';

            return $pdf->download(preg_replace('/[^a-zA-Z0-9\-_\.]/', '-', $filename));

        } catch (\Exception $e) {
            Log::error('Admin export meeting PDF failed', ['id' => $id, 'error' => $e->getMessage()]);
            abort(500, 'PDF generation failed: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // VSLA CYCLE REPORT PDF  (web route)
    // =========================================================================

    public function exportCycleReport($id)
    {
        try {
            $ipId  = $this->getAdminIpId();
            $cycle = Project::where('id', $id)->where('is_vsla_cycle', 'Yes')->firstOrFail();
            $group = FfsGroup::find($cycle->group_id);

            if ($ipId && $group && (int)$group->ip_id !== (int)$ipId) {
                abort(403, 'Access denied');
            }

            $meetings = VslaMeeting::with(['creator'])
                ->where('cycle_id', $id)
                ->where('processing_status', 'completed')
                ->orderBy('meeting_number')
                ->get();

            [$stats, $memberSavings, $loanPortfolio] = $this->computeCycleStats($meetings);

            $startDate    = $meetings->min('meeting_date') ?? $cycle->start_date ?? null;
            $endDate      = $meetings->max('meeting_date') ?? now()->format('Y-m-d');
            $reportPeriod = $startDate
                ? Carbon::parse($startDate)->format('d M Y') . ' – ' . Carbon::parse($endDate)->format('d M Y')
                : 'All Meetings';

            $adminUser   = Admin::user();
            $generatedBy = $adminUser ? ($adminUser->name ?? $adminUser->username) : 'System';

            $pdf = Pdf::loadView('exports.vsla-cycle-report', [
                'cycle'         => $cycle,
                'group'         => $group,
                'meetings'      => $meetings,
                'stats'         => $stats,
                'memberSavings' => $memberSavings,
                'loanPortfolio' => $loanPortfolio,
                'reportPeriod'  => $reportPeriod,
                'generatedAt'   => now()->format('d/m/Y H:i'),
                'generatedBy'   => $generatedBy,
            ]);
            $pdf->setPaper('A4', 'portrait');
            $pdf->setOptions(['dpi' => 110, 'defaultFont' => 'DejaVu Sans', 'isHtml5ParserEnabled' => true]);

            $cycleName = preg_replace('/[^a-zA-Z0-9\-_]/', '-', $cycle->cycle_name ?? $cycle->name ?? 'cycle');
            return $pdf->download('vsla-cycle-report-' . $cycleName . '-' . now()->format('Ymd') . '.pdf');

        } catch (\Exception $e) {
            Log::error('Admin export cycle report failed', ['id' => $id, 'error' => $e->getMessage()]);
            abort(500, 'Report generation failed: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // AESA CSV EXPORT  (web route)
    // =========================================================================

    public function exportAesaCsv(Request $request)
    {
        try {
            $ipId = $this->getAdminIpId();
            $type = strtolower($request->get('type', 'animals'));

            $query = AesaSession::with(['observations', 'cropObservations', 'group', 'facilitator']);
            if ($ipId) $query->where('ip_id', $ipId);

            if ($request->filled('group_id'))  $query->where('group_id',          $request->group_id);
            if ($request->filled('status'))    $query->where('status',             $request->status);
            if ($request->filled('date_from')) $query->where('observation_date',  '>=', $request->date_from);
            if ($request->filled('date_to'))   $query->where('observation_date',  '<=', $request->date_to);

            $sessions = $query->orderBy('observation_date', 'desc')->get();

            $animalHeaders = [
                'Data Sheet #', 'Group', 'District', 'Sub-County', 'Village',
                'Date', 'Time', 'Facilitator', 'Mini-Group', 'Location', 'Status',
                'Animal Tag', 'Animal Type', 'Breed', 'Sex', 'Age Category',
                'Weight (kg)', 'Height (cm)', 'Owner', 'Health Status', 'Body Condition',
                'Risk Level', 'Main Problem', 'Immediate Action',
                'Follow-up Date', 'Responsible Person', 'Health Score',
            ];
            $cropHeaders = [
                'Data Sheet #', 'Group', 'District', 'Sub-County', 'Village',
                'Date', 'Time', 'Facilitator', 'Mini-Group', 'Location', 'Status',
                'Plot ID', 'Crop Type', 'Variety', 'Growth Stage', 'Plot Size (ac)',
                'Farmer', 'Pest Pressure', 'Disease Pressure', 'Risk Level',
                'Main Problem', 'Immediate Action',
                'Follow-up Date', 'Responsible Person', 'Crop Health Score',
            ];

            $animalRows = [];
            $cropRows   = [];

            foreach ($sessions as $session) {
                $base = [
                    $session->data_sheet_number,
                    $session->group_name_display,
                    $session->district_name_display,
                    $session->sub_county_name_display,
                    $session->village_name_display,
                    $session->formatted_date,
                    $session->formatted_time,
                    $session->facilitator_name_display,
                    $session->mini_group_name,
                    $session->location_display,
                    $session->status_text,
                ];

                if ($type !== 'crops') {
                    foreach ($session->observations as $obs) {
                        $animalRows[] = array_merge($base, [
                            $obs->animal_id_tag, $obs->animal_type_display ?? $obs->animal_type,
                            $obs->breed_display ?? $obs->breed, $obs->sex, $obs->age_category,
                            $obs->weight_kg, $obs->height_cm,
                            $obs->owner_display ?? $obs->owner_name,
                            $obs->health_status_display ?? $obs->animal_health_status,
                            $obs->body_condition, $obs->risk_level,
                            $obs->main_problem ?? $obs->main_problem_other,
                            $obs->immediate_action ?? $obs->immediate_action_other,
                            $obs->follow_up_date ? $obs->follow_up_date->format('d/m/Y') : '',
                            $obs->responsible_person ?? $obs->responsible_person_other,
                            $obs->health_score,
                        ]);
                    }
                }

                if ($type !== 'animals') {
                    foreach ($session->cropObservations as $crop) {
                        $cropRows[] = array_merge($base, [
                            $crop->plot_id, $crop->crop_type_display ?? $crop->crop_type,
                            $crop->variety, $crop->growth_stage, $crop->plot_size_acres,
                            $crop->farmer_display ?? $crop->farmer_name,
                            $crop->pest_pressure_level, $crop->disease_pressure_level,
                            $crop->risk_level,
                            $crop->main_problem ?? $crop->main_problem_other,
                            $crop->immediate_action ?? $crop->immediate_action_other,
                            $crop->follow_up_date ? $crop->follow_up_date->format('d/m/Y') : '',
                            $crop->responsible_person ?? $crop->responsible_person_other,
                            $crop->crop_health_score,
                        ]);
                    }
                }
            }

            $writer = Writer::fromString();
            $suffix = now()->format('Ymd-His');

            if ($type === 'crops') {
                $writer->insertOne($cropHeaders);
                $writer->insertAll($cropRows);
                $filename = 'aesa-crop-observations-' . $suffix . '.csv';
            } elseif ($type === 'all') {
                $writer->insertOne($animalHeaders);
                $writer->insertAll($animalRows);
                $writer->insertOne([]);
                $writer->insertOne($cropHeaders);
                $writer->insertAll($cropRows);
                $filename = 'aesa-all-observations-' . $suffix . '.csv';
            } else {
                $writer->insertOne($animalHeaders);
                $writer->insertAll($animalRows);
                $filename = 'aesa-animal-observations-' . $suffix . '.csv';
            }

            return response($writer->toString(), 200, [
                'Content-Type'        => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);

        } catch (\Exception $e) {
            Log::error('Admin export AESA CSV failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'CSV export failed: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // AESA SESSION PDF  (web route)
    // =========================================================================

    public function exportAesaSessionPdf($id)
    {
        try {
            $ipId    = $this->getAdminIpId();
            $session = AesaSession::with([
                'observations', 'cropObservations', 'group',
                'facilitator', 'district', 'subCounty', 'village', 'implementingPartner',
            ])->findOrFail($id);

            if ($ipId && $session->ip_id && (int)$session->ip_id !== (int)$ipId) {
                abort(403, 'Access denied');
            }

            $adminUser   = Admin::user();
            $generatedBy = $adminUser ? ($adminUser->name ?? $adminUser->username) : 'System';

            $pdf = Pdf::loadView('exports.aesa-session-pdf', [
                'session'     => $session,
                'generatedAt' => now()->format('d/m/Y H:i'),
                'generatedBy' => $generatedBy,
            ]);
            $pdf->setPaper('A4', 'landscape');
            $pdf->setOptions(['dpi' => 110, 'defaultFont' => 'DejaVu Sans', 'isHtml5ParserEnabled' => true]);

            $filename = 'aesa-session-' . ($session->data_sheet_number ?? $id) . '-' . now()->format('Ymd') . '.pdf';
            return $pdf->download(preg_replace('/[^a-zA-Z0-9\-_\.]/', '-', $filename));

        } catch (\Exception $e) {
            Log::error('Admin export AESA session PDF failed', ['id' => $id, 'error' => $e->getMessage()]);
            abort(500, 'PDF generation failed: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // FFS TRAINING SESSIONS CSV EXPORT  (web route)
    // =========================================================================

    public function exportTrainingsCsv(Request $request)
    {
        try {
            $ipId = $this->getAdminIpId();

            $query = FfsTrainingSession::with(['group', 'facilitator', 'coFacilitator'])
                ->withCount(['participants', 'resolutions']);
            if ($ipId) $query->where('ip_id', $ipId);

            if ($request->filled('group_id'))     $query->where('group_id',     $request->group_id);
            if ($request->filled('session_type')) $query->where('session_type', $request->session_type);
            if ($request->filled('status'))       $query->where('status',       $request->status);
            if ($request->filled('report_status'))$query->where('report_status',$request->report_status);
            if ($request->filled('date_from'))    $query->where('session_date', '>=', $request->date_from);
            if ($request->filled('date_to'))      $query->where('session_date', '<=', $request->date_to);

            $sessions = $query->orderBy('session_date', 'desc')->get();

            $writer = Writer::fromString();
            $writer->insertOne([
                'Session Title', 'Group', 'Session Type', 'Date', 'Venue',
                'Status', 'Report Status',
                'Expected Participants', 'Actual Participants', 'Participants Recorded',
                'GAP Resolutions',
                'Facilitator', 'Co-Facilitator',
                'Topic', 'Notes', 'Challenges', 'Recommendations',
            ]);

            foreach ($sessions as $s) {
                $writer->insertOne([
                    $s->title ?? '',
                    $s->group->name ?? '',
                    $s->session_type_text ?? ucfirst($s->session_type ?? ''),
                    $s->session_date ? $s->session_date->format('d/m/Y') : '',
                    $s->venue ?? '',
                    $s->status_text ?? ucfirst($s->status ?? ''),
                    $s->report_status_text ?? ucfirst($s->report_status ?? ''),
                    (int)($s->expected_participants ?? 0),
                    (int)($s->actual_participants ?? 0),
                    (int)($s->participants_count ?? 0),
                    (int)($s->resolutions_count ?? 0),
                    $s->facilitator ? trim(($s->facilitator->first_name ?? '') . ' ' . ($s->facilitator->last_name ?? '')) : '',
                    $s->coFacilitator ? trim(($s->coFacilitator->first_name ?? '') . ' ' . ($s->coFacilitator->last_name ?? '')) : '',
                    $s->topic ?? '',
                    $s->notes ?? '',
                    $s->challenges ?? '',
                    $s->recommendations ?? '',
                ]);
            }

            $filename = 'ffs-training-sessions-' . now()->format('Ymd-His') . '.csv';
            return response($writer->toString(), 200, [
                'Content-Type'        => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);

        } catch (\Exception $e) {
            Log::error('Admin export trainings CSV failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'CSV export failed: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // FFS GAP RESOLUTIONS CSV EXPORT  (web route)
    // =========================================================================

    public function exportTrainingGapsCsv(Request $request)
    {
        try {
            $ipId = $this->getAdminIpId();

            $query = FfsSessionResolution::with([
                'session.group', 'session.facilitator', 'responsiblePerson',
            ]);

            if ($ipId) {
                $query->whereHas('session', fn($q) => $q->where('ip_id', $ipId));
            }

            if ($request->filled('group_id')) {
                $query->whereHas('session', fn($q) => $q->where('group_id', $request->group_id));
            }
            if ($request->filled('gap_category')) $query->where('gap_category', $request->gap_category);
            if ($request->filled('status'))        $query->where('status',       $request->status);
            if ($request->filled('date_from')) {
                $query->whereHas('session', fn($q) => $q->where('session_date', '>=', $request->date_from));
            }
            if ($request->filled('date_to')) {
                $query->whereHas('session', fn($q) => $q->where('session_date', '<=', $request->date_to));
            }

            $resolutions = $query->orderByDesc('created_at')->get();

            $writer = Writer::fromString();
            $writer->insertOne([
                'Session Title', 'Group', 'Session Date', 'Facilitator',
                'GAP Category', 'Resolution / Action Item', 'Description',
                'Responsible Person', 'Target Date', 'Status',
                'Follow-up Notes', 'Completed At',
            ]);

            foreach ($resolutions as $r) {
                $session = $r->session;
                $writer->insertOne([
                    $session->title ?? '',
                    $session->group->name ?? '',
                    $session && $session->session_date ? $session->session_date->format('d/m/Y') : '',
                    $session && $session->facilitator
                        ? trim(($session->facilitator->first_name ?? '') . ' ' . ($session->facilitator->last_name ?? ''))
                        : '',
                    $r->gap_category_text ?? ucfirst($r->gap_category ?? ''),
                    $r->resolution ?? '',
                    $r->description ?? '',
                    $r->responsiblePerson
                        ? trim(($r->responsiblePerson->first_name ?? '') . ' ' . ($r->responsiblePerson->last_name ?? ''))
                        : '',
                    $r->target_date ? $r->target_date->format('d/m/Y') : '',
                    $r->status_text ?? ucfirst($r->status ?? ''),
                    $r->follow_up_notes ?? '',
                    $r->completed_at ? $r->completed_at->format('d/m/Y H:i') : '',
                ]);
            }

            $filename = 'ffs-gap-resolutions-' . now()->format('Ymd-His') . '.csv';
            return response($writer->toString(), 200, [
                'Content-Type'        => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);

        } catch (\Exception $e) {
            Log::error('Admin export GAP resolutions CSV failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'CSV export failed: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    private function safeJson($value): array
    {
        if (is_array($value)) return $value;
        return json_decode($value ?? '[]', true) ?? [];
    }

    private function countAttendance(array $attendance): array
    {
        $present = $absent = 0;
        foreach ($attendance as $a) {
            $val = $a['isPresent'] ?? $a['is_present'] ?? false;
            if ($val === true || $val === 1 || $val === '1' || $val === 'true') $present++;
            else $absent++;
        }
        return [$present, $absent];
    }

    private function sumTransactionsByType(array $transactions): array
    {
        $savings = $welfare = $fines = 0;
        foreach ($transactions as $t) {
            $type   = strtolower($t['accountType'] ?? $t['account_type'] ?? '');
            $amount = (float)($t['amount'] ?? 0);
            if ($type === 'saving') $savings += $amount;
            elseif (str_contains($type, 'welfare') || str_contains($type, 'social_fund') || str_contains($type, 'socialfund')) $welfare += $amount;
            elseif ($type === 'fine') $fines += $amount;
        }
        return [$savings, $welfare, $fines];
    }

    private function sumLoans(array $loans): float
    {
        return array_sum(array_map(fn($l) => (float)($l['amount'] ?? $l['loanAmount'] ?? $l['principal'] ?? 0), $loans));
    }

    private function sumRepayments(array $repayments): float
    {
        return array_sum(array_map(fn($r) => (float)($r['totalAmount'] ?? $r['total_amount']
            ?? (($r['principalAmount'] ?? $r['principal_amount'] ?? $r['principal'] ?? 0)
               + ($r['interestAmount'] ?? $r['interest_amount'] ?? 0))), $repayments));
    }

    private function fmt(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }

    private function computeCycleStats($meetings): array
    {
        $totalSavings = $totalDisbursed = $totalRepaid = $totalFines = $totalWelfare = 0;
        $totalPresent = $totalPossible = 0;
        $memberSavingsMap = [];
        $memberLoanMap    = [];

        foreach ($meetings as $m) {
            $attendance   = $this->safeJson($m->attendance_data);
            $transactions = $this->safeJson($m->transactions_data);
            $loans        = $this->safeJson($m->loans_data);
            $repayments   = $this->safeJson($m->loan_repayments_data);

            [$present] = $this->countAttendance($attendance);
            $totalPresent  += $present;
            $totalPossible += count($attendance);

            foreach ($transactions as $t) {
                $type   = strtolower($t['accountType'] ?? $t['account_type'] ?? '');
                $amount = (float)($t['amount'] ?? 0);
                $mid    = $t['memberId'] ?? $t['member_id'] ?? 'unknown';
                $mname  = $t['memberName'] ?? $t['member_name'] ?? $mid;

                if ($type === 'saving') {
                    $totalSavings += $amount;
                    $memberSavingsMap[$mid] ??= ['name' => $mname, 'total' => 0, 'count' => 0];
                    $memberSavingsMap[$mid]['total'] += $amount;
                    $memberSavingsMap[$mid]['count']++;
                } elseif (str_contains($type, 'welfare') || str_contains($type, 'social_fund') || str_contains($type, 'socialfund')) {
                    $totalWelfare += $amount;
                } elseif ($type === 'fine') {
                    $totalFines += $amount;
                }
            }

            foreach ($loans as $l) {
                $amount = (float)($l['amount'] ?? $l['loanAmount'] ?? $l['principal'] ?? 0);
                $totalDisbursed += $amount;
                $mid   = $l['memberId'] ?? $l['member_id'] ?? 'unknown';
                $mname = $l['memberName'] ?? $l['member_name'] ?? $mid;
                $memberLoanMap[$mid] ??= ['name' => $mname, 'disbursed' => 0, 'repaid' => 0, 'loans' => 0];
                $memberLoanMap[$mid]['disbursed'] += $amount;
                $memberLoanMap[$mid]['loans']++;
            }

            foreach ($repayments as $r) {
                $total = $this->sumRepayments([$r]);
                $totalRepaid += $total;
                $mid = $r['memberId'] ?? $r['member_id'] ?? 'unknown';
                if (isset($memberLoanMap[$mid])) $memberLoanMap[$mid]['repaid'] += $total;
            }
        }

        $avgAttendance = $totalPossible > 0 ? round(($totalPresent / $totalPossible) * 100, 1) : 0;

        uasort($memberSavingsMap, fn($a, $b) => $b['total'] <=> $a['total']);
        $memberSavings = array_values($memberSavingsMap);

        $loanPortfolio = array_values(array_map(function ($row) {
            $row['outstanding'] = max(0, $row['disbursed'] - $row['repaid']);
            return $row;
        }, $memberLoanMap));
        usort($loanPortfolio, fn($a, $b) => $b['disbursed'] <=> $a['disbursed']);

        $stats = [
            'total_meetings'        => $meetings->count(),
            'total_savings'         => $totalSavings,
            'total_loans_disbursed' => $totalDisbursed,
            'total_loans_repaid'    => $totalRepaid,
            'total_fines'           => $totalFines,
            'total_welfare'         => $totalWelfare,
            'avg_attendance_rate'   => $avgAttendance,
            'total_members'         => count($memberSavingsMap),
            'outstanding_loans'     => max(0, $totalDisbursed - $totalRepaid),
            'repayment_rate'        => $totalDisbursed > 0 ? round(($totalRepaid / $totalDisbursed) * 100, 1) : 0,
        ];

        return [$stats, $memberSavings, $loanPortfolio];
    }
}
