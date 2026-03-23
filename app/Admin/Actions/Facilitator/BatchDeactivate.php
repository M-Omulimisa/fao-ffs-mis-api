<?php

namespace App\Admin\Actions\Facilitator;

use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;

class BatchDeactivate extends BatchAction
{
    public $name = 'Deactivate Selected';

    public function handle(Collection $collection)
    {
        $count = 0;
        foreach ($collection as $model) {
            $model->status = 0;
            $model->save();
            $count++;
        }
        return $this->response()->success("Deactivated {$count} facilitator(s).")->refresh();
    }

    public function dialog()
    {
        $this->confirm('Deactivate all selected facilitators?');
    }
}
