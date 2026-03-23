<?php

namespace App\Admin\Actions\Facilitator;

use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class BatchSetContractType extends BatchAction
{
    public $name = 'Set Contract Type';

    public function handle(Collection $collection, Request $request)
    {
        $count = 0;
        foreach ($collection as $model) {
            $model->contract_type = $request->get('contract_type');
            $model->save();
            $count++;
        }
        return $this->response()->success("Updated contract type for {$count} facilitator(s).")->refresh();
    }

    public function form()
    {
        $this->select('contract_type', 'Contract Type')
            ->options([
                'Full Time' => 'Full Time',
                'Part Time' => 'Part Time',
            ])->rules('required');
    }
}
