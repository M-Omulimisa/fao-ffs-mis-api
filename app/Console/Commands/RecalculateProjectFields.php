<?php

namespace App\Console\Commands;

use App\Models\Project;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculateProjectFields extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'projects:recalculate {--project_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate all computed fields for projects from their transactions';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $projectId = $this->option('project_id');
        
        if ($projectId) {
            // Recalculate single project
            $project = Project::find($projectId);
            
            if (!$project) {
                $this->error("Project with ID {$projectId} not found.");
                return 1;
            }
            
            $this->info("Recalculating fields for project: {$project->title}");
            
            $project->recalculateFromTransactions();
            
            $this->info("✓ Complete!");
            $this->table(
                ['Field', 'Value'],
                [
                    ['shares_sold', $project->shares_sold],
                    ['total_investment', number_format($project->total_investment, 2)],
                    ['total_returns', number_format($project->total_returns, 2)],
                    ['total_expenses', number_format($project->total_expenses, 2)],
                    ['total_profits', number_format($project->total_profits, 2)],
                ]
            );
            
        } else {
            // Recalculate all projects
            $this->info("Recalculating fields for all projects...");
            
            $bar = $this->output->createProgressBar(Project::count());
            $bar->start();
            
            $updated = 0;
            $errors = 0;
            
            Project::chunk(100, function ($projects) use ($bar, &$updated, &$errors) {
                foreach ($projects as $project) {
                    try {
                        $project->recalculateFromTransactions();
                        $updated++;
                    } catch (\Exception $e) {
                        $this->error("\nError updating project {$project->id}: " . $e->getMessage());
                        $errors++;
                    }
                    $bar->advance();
                }
            });
            
            $bar->finish();
            $this->newLine(2);
            
            $this->info("✓ Recalculation complete!");
            $this->info("Updated: {$updated} projects");
            
            if ($errors > 0) {
                $this->warn("Errors: {$errors} projects");
            }
        }
        
        return 0;
    }
}
