<?php

namespace App\Console\Commands;

use App\Models\VslaMeeting;
use App\Services\MeetingProcessingService;
use Illuminate\Console\Command;

class ReprocessMeeting extends Command
{
    protected $signature = 'meeting:reprocess {meeting_id}';
    protected $description = 'Reprocess a VSLA meeting';

    public function handle()
    {
        $meetingId = $this->argument('meeting_id');
        $meeting = VslaMeeting::find($meetingId);

        if (!$meeting) {
            $this->error("Meeting {$meetingId} not found");
            return 1;
        }

        $this->info("Reprocessing Meeting #{$meeting->meeting_number}");
        $this->info("Current Status: {$meeting->processing_status}");

        // Reset meeting
        $meeting->processing_status = 'pending';
        $meeting->has_errors = false;
        $meeting->has_warnings = false;
        $meeting->errors = null;
        $meeting->warnings = null;
        $meeting->processed_at = null;
        $meeting->save();

        // Reprocess
        $service = new MeetingProcessingService();
        $result = $service->processMeeting($meeting);

        $this->newLine();
        $this->info("Processing Complete!");
        $this->info("Success: " . ($result['success'] ? 'YES' : 'NO'));
        $this->info("Status: " . $meeting->fresh()->processing_status);
        $this->info("Errors: " . count($result['errors']));
        $this->info("Warnings: " . count($result['warnings']));

        if (!empty($result['errors'])) {
            $this->newLine();
            $this->error("Errors:");
            foreach ($result['errors'] as $error) {
                $this->line("  - {$error['type']}: {$error['message']}");
            }
        }

        if (!empty($result['warnings'])) {
            $this->newLine();
            $this->warn("Warnings:");
            foreach ($result['warnings'] as $warning) {
                $this->line("  - {$warning['type']}: {$warning['message']}");
            }
        }

        return 0;
    }
}
