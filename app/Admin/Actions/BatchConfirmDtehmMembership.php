<?php

namespace App\Admin\Actions;

use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class BatchConfirmDtehmMembership extends BatchAction
{
    public $name = 'Batch Confirm Payments';

    public function handle(Collection $collection, Request $request)
    {
        $confirmed = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($collection as $membership) {
            try {
                // Skip if already confirmed
                if ($membership->status === 'CONFIRMED') {
                    $skipped++;
                    continue;
                }

                // Confirm the membership
                $membership->confirm(\Admin::user()->id);
                $confirmed++;

            } catch (\Exception $e) {
                $failed++;
                \Log::error('Batch confirm DTEHM membership failed', [
                    'membership_id' => $membership->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $message = "Confirmed: {$confirmed}";
        if ($skipped > 0) {
            $message .= ", Skipped (already confirmed): {$skipped}";
        }
        if ($failed > 0) {
            $message .= ", Failed: {$failed}";
        }

        return $this->response()->success($message)->refresh();
    }

    public function dialog()
    {
        $this->confirm('Are you sure you want to confirm all selected DTEHM membership payments?');
    }
}
