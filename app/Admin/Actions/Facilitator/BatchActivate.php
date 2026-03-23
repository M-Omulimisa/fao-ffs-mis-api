<?php

namespace App\Admin\Actions\Facilitator;

use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;

class BatchActivate extends BatchAction
{
    public $name = 'Activate Selected';

    public function handle(Collection $collection)
    {
        $count = 0;
        foreach ($collection as $model) {
            $model->status = 1;
            $model->save();
            $count++;
        }
        return $this->response()->success("Activated {$count} facilitator(s).")->refresh();
    }

    public function dialog()
    {
        $this->confirm('Activate all selected facilitators?');
    }
}
