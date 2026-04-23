<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class GroupIpMismatchFixerService
{
    public function countMismatches(?int $ipId = null): int
    {
        return $this->baseMismatchQuery($ipId)->count();
    }

    /**
     * @return int[]
     */
    public function mismatchGroupIds(?int $ipId = null): array
    {
        return $this->baseMismatchQuery($ipId)
            ->orderBy('g.id')
            ->pluck('g.id')
            ->map(fn($id) => (int) $id)
            ->all();
    }

    /**
     * @return array{ok:bool,error:?string}
     */
    public function fixGroupById(int $groupId): array
    {
        try {
            $ok = app(GroupIpAlignmentService::class)->alignGroupAndRelatedData($groupId);
            if ($ok) {
                return ['ok' => true, 'error' => null];
            }

            return ['ok' => false, 'error' => 'No valid facilitator IP found for this group'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * @return array{total:int,fixed:int,failed:int,failures:array<int,array{group_id:int,error:string}>}
     */
    public function run(?int $ipId = null, int $batchSize = 50, ?callable $progress = null): array
    {
        $batchSize = max(1, min(200, $batchSize));
        $ids = $this->baseMismatchQuery($ipId)->orderBy('g.id')->pluck('g.id')->all();

        $total = count($ids);
        $fixed = 0;
        $failed = 0;
        $failures = [];

        $aligner = app(GroupIpAlignmentService::class);

        if ($progress) {
            $progress([
                'status' => 'running',
                'total' => $total,
                'processed' => 0,
                'fixed' => 0,
                'failed' => 0,
                'percent' => 0,
                'message' => 'Starting group IP mismatch fix',
            ]);
        }

        foreach (array_chunk($ids, $batchSize) as $chunk) {
            foreach ($chunk as $groupId) {
                try {
                    $ok = $aligner->alignGroupAndRelatedData((int) $groupId);
                    if ($ok) {
                        $fixed++;
                    } else {
                        // Group may not have a valid facilitator IP; count as failed for visibility.
                        $failed++;
                        $failures[] = [
                            'group_id' => (int) $groupId,
                            'error' => 'No valid facilitator IP found for this group',
                        ];
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    $failures[] = [
                        'group_id' => (int) $groupId,
                        'error' => $e->getMessage(),
                    ];
                }

                if ($progress) {
                    $processed = $fixed + $failed;
                    $percent = $total > 0 ? (int) floor(($processed / $total) * 100) : 100;
                    $progress([
                        'status' => 'running',
                        'total' => $total,
                        'processed' => $processed,
                        'fixed' => $fixed,
                        'failed' => $failed,
                        'percent' => $percent,
                        'message' => "Processed {$processed}/{$total}",
                    ]);
                }
            }
        }

        if ($progress) {
            $progress([
                'status' => 'completed',
                'total' => $total,
                'processed' => $total,
                'fixed' => $fixed,
                'failed' => $failed,
                'percent' => 100,
                'message' => 'Group IP mismatch fix completed',
                'failures' => array_slice($failures, 0, 50),
            ]);
        }

        return [
            'total' => $total,
            'fixed' => $fixed,
            'failed' => $failed,
            'failures' => $failures,
        ];
    }

    private function baseMismatchQuery(?int $ipId = null)
    {
        return DB::table('ffs_groups as g')
            ->join('users as u', 'u.id', '=', 'g.facilitator_id')
            ->join('implementing_partners as ip', 'ip.id', '=', 'u.ip_id')
            ->whereNull('g.deleted_at')
            ->whereNotNull('g.facilitator_id')
            ->whereNotNull('u.ip_id')
            ->whereColumn('g.ip_id', '!=', 'u.ip_id')
            ->when($ipId, fn($q) => $q->where('u.ip_id', (int) $ipId));
    }
}
